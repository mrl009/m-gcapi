<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Created by PhpStorm.
 * User: mrl
 * Date: 2017/3/27
 * Time: 下午8:06
 */
class Log_model extends MY_Model
{
    /**
     * 用户登陆类型
     */
    const LOG_USER_LOGIN = 'log_user_login';

    /**
     * 管理员登陆类型
     */
    const LOG_ADMIN_LOGIN = 'log_admin_login';

    /**
     * 管理员操作类型
     */
    const LOG_ADMIN_RECORD = 'log_admin_record';

    /**
     * 日志类型
     * @var
     */
    public $logType;

    /**
     * 来源
     * @var array
     */
    public $fromWay = [
        '1' => 'ios',
        '2' => 'android',
        '3' => 'PC',
        '4' => 'wap',
        '5' => '未知'
    ];

    /**
     * 获取日志类型
     * @param $logType
     * @return string
     */
    public function getLogType($logType)
    {
        switch ($logType) {
            case self::LOG_USER_LOGIN:
                $this->logType = self::LOG_USER_LOGIN;
                return $this->logType;
                break;
            case self::LOG_ADMIN_LOGIN:
                $this->logType = self::LOG_ADMIN_LOGIN;
                return $this->logType;
                break;
            case self::LOG_ADMIN_RECORD:
                $this->logType = self::LOG_ADMIN_RECORD;
                return $this->logType;
                break;
            default:
                return false;
                break;
        }
    }

    public function getBasicAndSenior($condition)
    {
        $rs = array(
            'basic' => [],
            'senior'=> []
        );
        $logTime = $this->logType == self::LOG_ADMIN_RECORD ? 'record_time' : 'login_time';
        $idType = $this->logType == self::LOG_USER_LOGIN ? 'uid' : 'admin_id';

        // id
        if (!empty($condition[$idType])) {
            $rs['basic'][$idType] = $condition[$idType];
        }
        // 时间
        if (!empty($condition['from_time'])) {
            $rs['basic'][$logTime . ' >='] = strtotime($condition['from_time'].' 00:00:00');
        }
        if (!empty($condition['to_time'])) {
            $rs['basic'][$logTime . ' <='] = strtotime($condition['to_time'].' 23:59:59');
        }
        // 账号
        if (!empty($condition['account'])) {
            $id = $this->getIdByAccount($condition['account']);
            $rs['basic'][$idType] = isset($id['id']) ? $id['id'] : -1;
        }
        // ip
        if (!empty($condition['ip'])) {
            $rs['basic']['ip'] = $condition['ip'];
        }
        // 是否成功
        if (!empty($condition['is_success'])) {
            $rs['basic']['is_success'] = $condition['is_success'];
        }

        return $rs;
    }

    /**
     * @param $data
     */
    public function formatData($data)
    {
        if (empty($data['rows'])) {
            return $data;
        }
        if (is_array($data['rows'])) {
            $userInfo = $this->getUserInfo($data['rows']);
            $this->redis_select(7);
            foreach ($data['rows'] as &$v) {
                // 登陆时间
                isset($v['login_time']) && $v['login_time'] = date('Y-m-d H:i:s', $v['login_time']);
                // 记录时间
                isset($v['record_time']) && $v['record_time'] = date('Y-m-d H:i:s', $v['record_time']);

                if (filter_var($v['ip'], FILTER_VALIDATE_IP) == false) {
                    $v['ip'] = $v['ip'];
                }

                //ip黑名单 判断增加
                if ($this->logType  == self::LOG_USER_LOGIN) {
                    $ipNum = $this->redis_get ('black_ip:' . $v['ip']);
                }elseif ($this->logType == self::LOG_ADMIN_LOGIN) {
                    $ipNum = $this->redis_get ('admin_black_ip:' . $v['ip']);
                }else{
                    $ipNum = 0;
                }
                if ($ipNum >= BLACK_IP_TIMES) {
                    $v['is_black'] = 1;
                }else{
                    $v['is_black'] = 0;
                }

                // 用户名
                if (!empty($userInfo)) {
                    foreach ($userInfo as $item) {
                        if (isset($v['uid']) && $item['id'] == $v['uid']) {
                            $v['username'] = $item['username'];
                            $v['addtime'] = date('Y-m-d H:i:s', $item['addtime']);
                        }
                        if (isset($v['admin_id']) && $item['id'] == $v['admin_id']) {
                            $v['username'] = $item['username'];
                        }
                    }
                }
            }
        }
        return $data;
    }

    /**
     * 获取用户信息
     * @param $data
     * @return array|mixed
     */
    public function getUserInfo($data)
    {
        $rs = [];
        $idType = $this->logType == self::LOG_USER_LOGIN ? 'uid' : 'admin_id';
        $table = $this->logType == self::LOG_USER_LOGIN ? 'user' : 'admin';

        $ids = array_column($data, $idType);
        if (!empty($ids)) {
            $condition = array(
                'wherein' => array('id' => $ids)
            );
            $rs = $this->get_list('id,username,addtime', $table, array(), $condition);
        }
        return $rs;
    }

    /**
     * 根据用户名获取ID(管理员id或用户id)
     * @param $account
     * @return array
     */
    private function getIdByAccount($account)
    {
        $table = $this->logType == self::LOG_USER_LOGIN ? 'user' : 'admin';
        return $this->get_one('id', $table, array('username' => $account));
    }

    /**
     * 记录日志
     * @param $admin_id    管理员ID
     * @param $data        记录的日志内容
     * @return void
     */
    public function record($admin_id, $data)
    {
        if (empty($admin_id)) {
            return;
        }
        $data['record_time'] = $_SERVER['REQUEST_TIME'];
        $data['admin_id'] = (int)$admin_id;
        $data['ip'] = get_ip();
        $this->write('log_admin_record', $data);
    }
}
