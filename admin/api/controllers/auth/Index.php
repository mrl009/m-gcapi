<?php
/**
 * @模块   demo
 * @版本   Version 1.0.0
 * @日期   2017-03-22
 * frank  所有使用都以demo为准
 */

defined('BASEPATH') OR exit('No direct script access allowed');
class Index extends MY_Controller 
{
	public function __construct()
	{
		parent::__construct();
	}
	//稽核日志
	public function index()
	{
		// 接收任何数据都需要验证
		$rule = array(
		    'username'  => 'min:4|max:16',
		);
		$msg = array(
		    'username.min' => '用户名最多不能少于4个字符',
		    'username.max'     => '用户名最多不能超过16个字符',
		);
		$checkData['username'] = $this->G('username');
		$this->validate->rule($rule,$msg);//验证数据
		$result   = $this->validate->check($checkData);
		if(!$result){
			$this->return_json(E_ARGS, $this->validate->getError());//返回错误信息
		}
		$dateArr = $this->check_bewteen_date($this->G('start_date'),$this->G('end_date'));
		$start_time = $dateArr['start_time'];
		$end_time = $dateArr['end_time'];
		$uid = 0;
		if(!empty($this->G('username'))){
			$this->load->model('Comm_model');
            $userData = $this->Comm_model->get_one('id','user',array('username'=>$this->G('username')));
			if(empty($userData)){
				$this->return_json(E_ARGS, '用户不存在');//返回错误信息
			}
			$uid = $userData['id'];
		}
		$this->load->model('auth/Auth_model');
		$data = $this->Auth_model->get_log($start_time,$end_time,$uid);
		if(empty($data)){
			$this->return_json(E_ARGS, '数据为空');//返回错误信息
		}
		$this->return_json(OK, $data);//返回错误信息
	}
	// 稽核
	public function auth()
	{
		// 接收任何数据都需要验证
        $uid = $this->G('uid');
        $username = $this->G('username');
        if (empty($uid) && empty($username)) {
            $this->return_json(E_ARGS, '缺少参数');
        }
        $this->load->model('auth/Auth_model');
        if (empty($uid) && !empty($username)) {
            $user = $this->Auth_model->db->select('id as uid')
                ->where('username',$username)
                ->get('user',1)
                ->result_array();
            if (empty($user)) {
                $this->return_json(E_ARGS, '用户不存在');
            }
            $uid = $user[0]['uid'];
        }
		$data = $this->Auth_model->get_auth($uid);

		/**** 格式化小数点 ****/
        $data['rows'] = stript_float($data['rows']);
		$this->return_json(OK,$data);
	}
}
