<?php
defined('BASEPATH') OR exit('No direct script access allowed');
// 引入自己的核心controller
require BASEPATH.'gc/common/GC_Controller.php';

class MY_Controller extends GC_Controller 
{
    public $admin = null;
    public function __construct()
    {
        parent::__construct();
        $this->load->helper('common_helper');
        $this->load->model('MY_Model','M');
        $super = get_auth_headers('AuthSuper');
        if ($super !== 'super') {
            // 超级后台去掉验证
            $this->admin = $this->check_token();
            $this->checkAuth();
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
            $this->return_json(E_ARGS,'参数出错token');
        }
        $res = $this->M->check_token($token);
        if(empty($res)){
            $this->return_json(E_ARGS,'没有登陆');
        }
        if(is_array($res)){
            return $res;//正常数据
        }
        if($res==TOKEN_TIME_OUT){
            $this->return_json(TOKEN_TIME_OUT,'token过期');
        }
        else if($res==TOKEN_BE_OUTED){
            $this->return_json(TOKEN_BE_OUTED,'被踢出');
        }
        else{
            $this->return_json(E_UNKNOW,'未知错误');
        }
    }

    /**
     * @检查控制器权限
     * @big chao
     * @param ...
     * @return 
     */
    public function checkAuth(){
        $path =get_auth_headers('ApiURL');
        //$path = PHP_SAPI=='apache2handler'?$_SERVER['PATH_INFO']:$_SERVER['HTTP_PATH_INFO'];
        if($path!='/ping/ping'){
            $rs = $this->M->get_one('id,privileges','admin',array('id'=>$this->admin['id']));
            if($rs == array()){
                $this->return_json(E_POWER,'无效的管理员');
            }else if($rs['privileges']!= '*'){
                $p = $this->M->get_one('id,f_en_name,auth','power',array('api_url'=>$path));
                if($p!=array()){

                    $this->_checkCtrlAuth($rs['privileges'],$p['f_en_name'],$p['auth']);
                }
            }
        }
    }

    /**
     * 根据域名转换成sn
     * @return string
     */
    public function get_sn()
    {
        $this->M->redisP_select(REDIS_PUBLIC);
        $snInfo = $this->M->redisP_hget('dsn', $this->_sn);
        if (!empty($snInfo)) {
            $snInfo = json_decode($snInfo, true);
        }
        return isset($snInfo['sn']) ? $snInfo['sn'] : $this->_sn;
    }

    /**
     * @检查控制器权限
     * @big chao
     * @param ...
     * @return 
     */
    private function _checkCtrlAuth($jq,$name,$auth)
    {

        $arr=json_decode($jq,true);
        $current = array();
        foreach($arr['public'] as $v){
            if(in_array($name,array_keys($v))){
                if(!in_array($auth,$v[$name])){
                    $this->return_json('NOAUTH','您没有权限进行此操作！');
                }else{
                    $current=$v[$name];
                    return true;
                }
            }else{
                $current = array();
            }
        }
        if($current == array())  $this->return_json('NOAUTH','您没有权限进行此操作！');
    }
    /**
     * @检测开始时间与结束的合法性
     * @frank
     * @param $start_date：起始时间，$end_date:结束时间，$must_between_date：两个时间的间隔多少天。
     * @return array,array['start_time']:开始时间戳，array['end_time']:开始时间戳
     */
    protected function check_bewteen_date($start_date,$end_date,$must_between_date=60)
    {
        if(empty($start_date)&&empty($end_date)){
            $dateArr['start_time'] = 0;
            $dateArr['end_time'] = 0;
            return $dateArr;
        }
        $start_time = strtotime($start_date);
        $end_time = strtotime($end_date);
        if(!$start_time){
            $this->return_json(E_ARGS, '开始时间错误');//返回错误信息
        }
        if(!$end_time){
            $this->return_json(E_ARGS, '结束时间错误');//返回错误信息
        }
        if($end_time<$start_time){
            $this->return_json(E_ARGS, '结束时间必需大于开始时间');//返回错误信息
        }
        if($end_time-$start_time>3600*24*$must_between_date){
            $this->return_json(E_ARGS, '查询区间不能超过60天');//返回错误信息
        }
        $dateArr['start_time'] = $start_time;
        $dateArr['end_time'] = $end_time+3600*24;
        return $dateArr;
    }

}

/* end file */
