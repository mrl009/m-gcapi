<?php
defined('BASEPATH') OR exit('No direct script access allowed');
// 引入自己的核心controller
require BASEPATH.'gc/common/GC_Controller.php';

class MY_Controller extends GC_Controller {
	public $user = null;
	public function __construct()
    {
        parent::__construct();
        /* 不需要登陆权限的控制器和方法[小写] */
        $pass = [
                'games' => ['index','info','play','lhc_sx','cleancache'],
                'open_time' => ['get_zhkithe_list','get_games_list', 'get_type_list', 'get_issue', 'timer_record_log', 'timer_update_kithe'],
                'red_bag' => ['index','bag_level','bag_list'],
                'welcome' => ['index'],
                'ping' => ['test'],
                'dragon' => ['data'],
        ];

        $this->load->helper('common_helper');
        $this->load->model('MY_Model','M');
        /* 是否维护 */
        $is_close = $this->M->get_gcset(['close', 'close_info', 'qq', 'online_service']);
        if (!empty($is_close['close']) && $is_close['close'] == STATUS_OFF) {
            $this->return_json(E_DENY, $is_close);
        }
        $this_class = strtolower($this->router->class);
        $this_method = strtolower($this->router->method);
        if ((isset($pass[$this_class]) && in_array($this_method, $pass[$this_class]))) {
            $this->user = $this->get_userinfo();
        } else {
            $this->user = $this->check_token();
            //$this->user = array('id'=>9,'username'=>'mrl003','agent_id'=>0,'status'=>1);
        }
    }

    /**
     * 检测token，判断是否过期
     * @param   string   $token   登陆token
     */
    protected function check_token()
    {
        $token = $this->_token;
        if (empty($token) || !isset($token{10})) {
            $this->return_json(E_TOKEN, '参数出错token');
        }
        $res = $this->M->check_token($token);
        if(empty($res)){
            $this->return_json(E_TOKEN, '没有登陆');
        }
        if(is_array($res)){
            if(isset($res[TOKEN_TIME_OUT])){
                $this->return_json(TOKEN_TIME_OUT,$res[TOKEN_TIME_OUT]);
                die;
            }

            return $res;//正常数据
        }

        else if($res==TOKEN_BE_OUTED){
            $this->return_json(TOKEN_BE_OUTED,'被踢出');
        }
        else{
            $this->return_json(E_UNKNOW,'未知错误');
        }
    }

    /**
     * 只获取用户登陆信息，有则获取，没则返回
     * @param   string   $token   token
     */
    protected function get_userinfo()
    {
        $token = $this->_token;
        if (empty($token) || !isset($token{10})) {
            return null;
        }
        $res = $this->M->check_token($token);
        if (empty($res)) {
            return null;
        }
        if (is_array($res)) {
            if (isset($res[TOKEN_TIME_OUT])) {
                return null;
            }
            return $res;
        }
        return null;
    }
}
