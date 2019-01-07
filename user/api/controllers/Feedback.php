<?php
/**
 * @模块   前端反馈
 * @版本   Version 1.0.0
 * @日期   2017-06-16
 * super
 */

defined('BASEPATH') or exit('No direct script access allowed');


class Feedback extends GC_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('MY_Model', 'core');
        $this->core->select_db('private');
    }

    public function index()
    {
        if (empty($_POST)) {
            $this->return_json(E_METHOD, '非法请求');
        }
        $data['level'] = (int)$this->P('level');
        $data['code'] = (int)$this->P('code');
        $data['content'] = $this->P('detail');
        $data['url'] = $this->P('url');
        $data['ip']  = $this->getClientIP();
        $data['src'] = (int)$this->P('fromway');
        $data['created'] = $_SERVER['REQUEST_TIME'];
        $is = $this->core->write('feedback', $data);
        $is?$this->return_json(OK):$this->return_json(E_OP_FAIL, '操作失败');
    }


    /*
     * 获取客户端IP
     */
    private function getClientIP()
    {
        global $ip;
        if (getenv("HTTP_CLIENT_IP")) {
            $ip = getenv("HTTP_CLIENT_IP");
        } elseif (getenv("HTTP_X_FORWARDED_FOR")) {
            $ip = getenv("HTTP_X_FORWARDED_FOR");
        } elseif (getenv("REMOTE_ADDR")) {
            $ip = getenv("REMOTE_ADDR");
        } else {
            $ip = "Unknow";
        }
        return $ip;
    }


    private function get_curPageURL()//获取客户端完整的url
    {
        $pageURL = 'http';
        if (!empty($_SERVER['HTTPS'])) {
            $pageURL .= 's';
        }
        $pageURL .= "://";
        if ($_SERVER['SERVER_PORT'] != "80") {
            $pageURL .= $_SERVER['SERVER_NAME'] . ':' . $_SERVER['SERVER_PORT'] . $_SERVER['REQUEST_URI'];
        } else {
            $pageURL .= $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
        }
        return $pageURL;
    }
}
