<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Created by PhpStorm.
 * User: mrl
 * Date: 2017/4/14
 * Time: 下午3:10
 */
class User_msg_log_model extends MY_Model
{
    /**
     * 消息类型
     * @var array
     */
    public $msgType = [
        0 => '普通通知',
        1 => '优惠通知',
        2 => '出入款通知',
        3 => '推送广播'
    ];

    public $terminal = [
        0 => '所有平台',
        1 => 'IOS',
        2 => 'Android'
    ];

    /**
     * 获取消息类型
     * @return array
     */
    public function getMsgType()
    {
        return $this->msgType;
    }

    /**
     * 获取搜索条件
     * @param $condition
     * @return array
     */
    public function getBasicAndSenior($condition)
    {
        //初始化条件
        $rs = array(
            'basic' => [],
            'senior'=> [],
        );
        // 时间条件
        if (!empty($condition['from_time'])) {
            $rs['basic']['addtime >='] = strtotime($condition['from_time'].' 00:00:00');
        }
        if (!empty($condition['to_time'])) {
            $rs['basic']['addtime <='] = strtotime($condition['to_time'].' 23:59:59');
        }
        // 账号
        if (!empty($condition['account'])) {
            $id = $this->getIdByUserName($condition['account']);
            $rs['basic']['uid'] = isset($id['id']) ? $id['id'] : -1;
        }
        // 消息类型
        if (isset($condition['msg_type'])) {
            $rs['basic']['msg_type'] = $condition['msg_type'];
        }
        // 终端
        if (isset($condition['terminal'])) {
            $rs['basic']['terminal'] = $condition['terminal'];
        }
        return $rs;
    }

    /**
     * @param $data
     */
    public function formatData($data)
    {
        if (empty($data['rows']) || empty($data['total'])) {
            return $data;
        }
        if (is_array($data['rows'])) {
            // 用户名
            $userName = $this->getUsername($data['rows']);
            // 层级
            $levelName = $this->getLevelName($data['rows']);
            foreach ($data['rows'] as &$v) {
                // 添加时间
                $v['addtime'] = date('Y-m-d H:i:s', $v['addtime']);
                // 消息类型
                if (isset($this->msgType[$v['msg_type']])) {
                    $v['msg_type'] = $this->msgType[$v['msg_type']];
                }

                if (isset($this->terminal[$v['terminal']])) {
                    $v['terminal'] = $this->terminal[$v['terminal']];
                }
                // 体系
                if (!empty($v['uid'])) {
                    $v['type'] = '会员账号';
                } elseif (!empty($v['level_id'])) {
                    $v['type'] = '层级会员';
                } else {
                    $v['type'] = '所有会员';
                }
                // 用户名
                if (!empty($userName)) {
                    foreach ($userName as $item) {
                        $item['id'] == $v['uid'] && $v['user_level'] = $item['username'];
                    }
                }
                // 层级
                if (!empty($levelName)) {
                    foreach ($levelName as $item) {
                        $item['id'] == $v['level_id'] && $v['user_level'] = $item['level_name'];
                    }
                }
            }
        }
        return $data;
    }

    /**
     * 根据用户名获取ID
     * @param $username
     * @return array
     */
    private function getIdByUserName($username)
    {
        $rs = [];
        if (!empty($username)) {
            $rs = $this->get_one('id', 'user', array('username' => $username));
        }
        return $rs;
    }

    /**
     * 获取用户名
     * @param $data
     * @return array|mixed
     */
    private function getUsername($data)
    {
        $rs = [];
        $ids = array_column($data, 'uid');
        if (is_array($ids) && !empty($ids)) {
            $condition = array(
                'wherein' => array('id' => $ids)
            );
            $rs = $this->get_list('id,username', 'user', array(), $condition);
        }
        return $rs;
    }

    /**
     * 获取层级名
     * @param $data
     * @return array|mixed
     */
    private function getLevelName($data)
    {
        $rs = [];
        $ids = array_column($data, 'level_id');
        if (!empty($ids)) {
            $condition = array(
                'wherein' => array('id' => $ids)
            );
            $rs = $this->get_list('id,level_name', 'level', array(), $condition);
        }
        return $rs;
    }
}
