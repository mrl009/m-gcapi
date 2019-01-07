<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * 日志列表显示(会员登陆，管理登陆，操作记录)
 * Created by PhpStorm.
 * User: mrl
 * Date: 2017/3/27
 * Time: 下午6:40
 */
class Log extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('MY_Model', 'core');
        $this->load->model('log/Log_model');
    }

    /**
     * 获取日志列表
     */
    public function getLogList()
    {
        //获取日志类型
        $logType = $this->Log_model->getLogType($this->G('log_type'));
        if (!$logType) {
            $this->return_json(E_ARGS, '参数错误');
        }
        //打开表
        $this->core->open($logType);
        // 获取搜索条件
        $condition = [
            'uid'       => $this->G('uid'),
            'admin_id'  => $this->G('admin_id'),
            'from_time' => $this->G('from_time'),
            'to_time'   => $this->G('to_time'),
            'account'   => $this->G('account'),
            'ip'        => $this->G('ip'),
            'is_success'=> $this->G('is_success'),
        ];
        if ($logType == "log_user_login") {
            if (!empty($condition['ip'])) {
                $condition['ip'] = $condition['ip'];
            }
        }
        $searchInfo = $this->Log_model->getBasicAndSenior($condition);
        $page = array(
            'page' => $this->G('page'),
            'rows' => $this->G('rows'),
            'order' => $this->G('order'),
            'sort' => $this->G('sort'),
            //'total' => -1,
        );
        $arr = $this->core->get_list('*', $logType, $searchInfo['basic'], $searchInfo['senior'], $page);
        // 格式化数据
        $arr = $this->Log_model->formatData($arr);
        $rs = array('total' => $arr['total'], 'rows' => $arr['rows']);
        $this->return_json(OK, $rs);
    }

    /**
     * 添加会员登录IP黑名单
    */
    public function add_black()
    {
        $ip = $this->P('ip');
        $is_admin = $this->P('is_admin');
        if (filter_var($ip, FILTER_VALIDATE_IP) == false) {
            $this->return_json(E_ARGS,'IP格式错误');
        }
        if ($is_admin == 1) {
            $keys = 'admin_black_ip:'.$ip;

        }else{
            $keys = 'black_ip:'.$ip;
        }
        $this->core->redis_select(7);
        $this->core->redis_set($keys,BLACK_IP_TIMES);
        $this->return_json(OK);
    }
    /**
     * 移除会员登录IP黑名单
     */
    public function rm_black()
    {
        $ip = $this->P('ip');
        $is_admin = $this->P('is_admin');

        if (filter_var($ip, FILTER_VALIDATE_IP) == false) {
            $this->return_json(E_ARGS,'IP格式错误');
        }
        if ($is_admin == 1) {
            $keys = 'admin_black_ip:'.$ip;

        }else{
            $keys = 'black_ip:'.$ip;
        }

        $this->core->redis_select(7);
        $this->core->redis_del($keys);
        $this->return_json(OK);
    }

}
