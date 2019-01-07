<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * User_info控制器
 * @这个控制器也是用户的，主要是为了与其他分开
 * 
 * @author      lss
 * @package     controllers
 * @version     v1.0 2017/11/3
 */
class User_info extends MY_Controller
{
	public function __construct()
    {
        parent::__construct();
        $this->load->model('user/User_info_model', 'model');
    }

    /**
	 * 今日盈亏
	 *
	 * @access public
	 * @return Array
	 */
	public function profit()
	{
		$id = (int)$this->user['id'];
		$data = $this->model->profit($id);
		$this->return_json(OK, $data);
	}



	/**
	 * 个人信息
	 *
	 * @access public
	 * @return Array
	 */
	public function info()
	{
		$id = (int)$this->user['id'];
		$data = $this->model->info($id);
		$this->return_json(OK, $data);
	}


    /**
     * 个人信息
     *
     * @access public
     * @return Array
     */
    public function update_info()
    {
        $data['uid'] = (int)$this->user['id'];
        $data['nickname'] = $this->P('nickname');
        $data['birthday'] = $this->P('birthday');
        $data['sex'] = (int)$this->P('sex');
        $data['phone'] = $this->P('phone');
        $data['email'] = $this->P('email');
        $data['modify'] = $this->P('modify');

        if(empty($data['nickname'])) {
            unset($data['nickname']) ;
        }
        if(strlen($data['modify'])!=9){
            $this->return_json(E_ARGS, 'modify格式有误！');
        }
        if (stripos($data['modify'],'k')==false) {
            $modify = explode(',',$data['modify']);
        }else{
            $modify = explode('k',$data['modify']);
        }
        $i=0;
        foreach($modify as $key => $value){
            if($value!=1 && $value !=0){
                $this->return_json(E_ARGS, 'modify格式有误！');
            }
            $i++;
        }
        if($i!=5){
            $this->return_json(E_ARGS, 'modify格式有误！');
        }
        $data['modify']  = implode(',',$modify);
        if(!empty($data['sex'])) {
            if ($data['sex'] != 1 && $data['sex'] != 2 && $data['sex'] != 3) {
                $this->return_json(E_ARGS, '性别有误！');
            }
        }else{
            unset($data['sex']);
        }
        if(!empty($data['phone'])){
            $isMatched = preg_match_all('/(13\d|14[57]|15[^4,\D]|17[13678]|18\d)\d{8}|170[0589]\d{7}/', $data['phone'], $matches);
            if (!$isMatched){
                $this->return_json(E_ARGS, '手机号格式有误！');
            }
        }else{
            unset($data['phone']);
        }
        if(!empty($data['email'])){
            $isMatched2 = preg_match_all('/\w[-\w.+]*@([A-Za-z0-9][-A-Za-z0-9]+\.)+[A-Za-z]{2,14}/', $data['email'], $matches2);
            if (!$isMatched2){
                $this->return_json(E_ARGS, '邮箱格式有误！');
            }
        }else{
            unset($data['email']);
        }
        if(!empty($data['birthday'])){
            $data['birthday'] = strtotime($data['birthday']);
            if (!$data['birthday']){
                $this->return_json(E_ARGS, '生日格式有误！');
            }
        }else{
            unset($data['birthday']);
        }
        $is = $this->model->update_info($data);
        if($is){
            if ($data['nickname']) {
                $this->load->model('MY_Model', 'M');
                $key = 'token:User:'. $this->_token;
                $user = $this->M->check_token($this->_token);
                if (is_array($user)) {
                    $user['nickname'] = $data['nickname'];
                    $this->M->redis_set($key,json_encode($user));
                }
            }
            $this->return_json(OK, $data['modify']);
        }else{
            $this->return_json(E_OP_FAIL, '操作失败！');
        }
    }

	/**
	 * 等级头衔
	 *
	 * @access public
	 * @return Array
	 */
	public function nobility()
	{
		$id = (int)$this->user['id'];
		$data = $this->model->nobility($id);
		$this->return_json(OK, $data);
	}
}
