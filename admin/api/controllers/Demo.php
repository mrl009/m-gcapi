<?php
/**
 * @模块   demo
 * @版本   Version 1.0.0
 * @日期   2017-03-22
 * frank  所有使用都以demo为准
 */

defined('BASEPATH') OR exit('No direct script access allowed');
class Demo extends MY_Controller 
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model('Demo_model');
	}
	public function index()
	{
		// echo '--------------------Demo--------------------<br>';
		// 接收任何数据都需要验证
		$rule = array(
		    'name'  => 'require|max:25',
		    'age'   => 'number|between:1,120',
		    'email' => 'email',
		);
		$msg = array(
		    'name.require' => '名称必须',
		    'name.max'     => '名称最多不能超过25个字符',
		    'age.number'   => '年龄必须是数字',
		    'age.between'  => '年龄只能在1-120之间',
		    'email'        => '邮箱格式错误',
		);
		$data = array(
		    'name'  => 'thinkphp',
		    'age'   => 10,
		    'email' => 'thinkphp@qq.com',
		);
		$data1 = array(
			'id'  => 1,
		    'name'  => 'thinkphp',
		    'age'   => 1,
		    'email' => 'thinkphp@qq.com',
		);
		$data2 = array(
			'id'  => 2,
		    'name'  => 'thinkphp',
		    'age'   => 2,
		    'email' => 'thinkphp@qq.com',
		);
		$this->validate->rule($rule,$msg);//验证数据
		$result   = $this->validate->check($data);
		if(!$result){
			// echo $this->validate->getError();//显示错误
			$this->return_json(E_ARGS, $this->validate->getError());//返回错误信息
		}
		
		$oneData = $this->Demo_model->demo_get_one();
		$allData = $this->Demo_model->demo_get_list();
		// $this->json_result_no('密码出错');//直接输入信息
		
		//多表查询
		$joinData = $this->Demo_model->get_join_list();

		//多表查询
		$joinData = $this->Demo_model->get_more_join_list();

		$this->return_json(OK, $allData);//输入数据
	}

	function s_db(){
		$this->Demo_model->select_db('public');
		$a=$this->Demo_model->get_one('*','bank',array('id'=>1));
		print_r($a);
	}

	function join_db(){
		
	}
}
