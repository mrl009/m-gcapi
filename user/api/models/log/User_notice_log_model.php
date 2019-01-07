<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Created by PhpStorm.
 * User: mrl
 * Date: 2017/4/14
 * Time: 下午3:12
 */
class User_notice_log_model extends MY_Model
{
    /**
     * 公告类型
     * @var array
     */
    public $noticeType = [
        0 => '最新公告',
        1 => '弹出（PC）',
        2 => '会员公告',
    ];

    /**
     * 显示位置
     * @var array
     */
    public $showLocation = [
        0 => '全部',
        1 => '安卓',
        2 => '苹果',
        3 => 'H5',
        4 => 'PC'
    ];

    /**
     * 获取公告类型
     * @return array
     */
    public function getNoticeType()
    {
        return $this->noticeType;
    }

    /**
     * 获取显示位置
     * @return array
     */
    public function getShowLocation()
    {
        return $this->showLocation;
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
        // 消息类型
        if (isset($condition['status'])) {
            $rs['basic']['status'] = $condition['status'];
        }
        // 内容
        if (!empty($condition['content'])) {
            $rs['basic']['content like'] = '%' . urldecode($condition['content']) . '%';
        }
        return $rs;
    }

    /**
     * 格式化数据
     * @param $data
     * @return mixed
     */
    public function formatData($data)
    {
        if (empty($data['rows']) || empty($data['total'])) {
            return $data;
        }
        if (is_array($data['rows'])) {
            $userName = $this->getUsername($data['rows']);
            $levelName = $this->getLevelName($data['rows']);
            foreach ($data['rows'] as &$v) {
                // 添加时间
                $v['addtime'] = date('Y-m-d H:i:s', $v['addtime']);
                // 公告类型
                if (isset($this->noticeType[$v['notice_type']])) {
                    $v['notice_type'] = $this->noticeType[$v['notice_type']];
                }
                // 位置
                if (isset($this->showLocation[$v['show_location']])) {
                    $v['show_location'] = $this->showLocation[$v['show_location']];
                }
                // 用户名
                if (!empty($userName)) {
                    foreach ($userName as $item) {
                        $item['id'] == $v['admin_id'] && $v['username'] = $item['username'];
                    }
                }
                // 层级
                if (!empty($levelName)) {
                    foreach ($levelName as $item) {
                        $item['id'] == $v['level_id'] && $v['level_name'] = $item['level_name'];
                    }
                }
            }
        }
        return $data;
    }

    /**
     * 获取用户名
     * @param $data
     * @return array|mixed
     */
    private function getUsername($data)
    {
        $rs = [];
        if (is_array($data) && !empty($data)) {
            $ids = array_column($data, 'admin_id');
            if (is_array($ids) && !empty($ids)) {
                $condition = array(
                    'wherein' => array('id' => $ids)
                );
                $rs = $this->get_list('id,username', 'admin', array(), $condition);
            }
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
