<?php
/**
 * @模块   demo
 * @版本   Version 1.0.0
 * @日期   2017-03-22
 * frank  所有使用都以demo为准
 */
if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

class Demo_model extends MY_Model 
{
	/**
	获取一条数据
	*参数说明
	get_one('字段','表名',条件数组)
	**/
	public function demo_get_one()
	{
		// $field = '*';
		// $table = 'admin';
		$where['id'] = 1;
		return $this->get_one('*','admin',$where);
	}
	/**
	分页查询
	*参数说明 
	get_list('字段',条件数组，分页数组，高级条件数组)
	必填（字段，表名，条件数组，分页数组）
	选填 高级条件
	array(
		'orderby'=>array('id'=>'desc'), // 排序
		'groupby'=>array('id','name'), // 排序
		'wherein'=>array('id','1,2,3,4,5'),
		'join'=>'table',
		'on'=>'a.id=b.id',
		'a_fields'=>'a表字段',
		'b_fields'=>'b表字段',
	)
	分页数组
	array(
		'page'=>当前页,
		'rows'=>一页显示条数,
		'sort'=>排序字段,
		'order'=>排序类型'desc',
		'total'=>总数, 空为10000条  -1为数据库统计
	);
	return array('total'=>总条数,'rows'=>'查询结果数组')
	**/
	public function demo_get_list()
	{
		// $field = '*';
		// $table = 'admin';
		$where = array();
		$condition = array(
			'orderby'=>array('id'=>'desc')
			);
		$page=array(
			'page'  =>1,
			'rows'  =>50,
		);
		return $this->get_list('*','admin',$where,$condition,$page);
	}

	function get_join_list(){
		$basic=array('username'=>'root21');
		$senior=array('join'=>'level','on'=>'a.level_id=b.id');
		return $arr=$this->get_list('*','user',$basic,$senior);
	}

	public function get_more_join_list(){
		$where['user.id'] = $uid;
		$this->db->where($where);
		$condition['join'] = 
			array(
				array('table'=>'level as l','on'=>'user.level_id = l.id'),
				array('table'=>'pay_set as ps','on'=>'ps.id = l.pay_id'),
			);
		$filed = 'a.*,ps.*';
		return $this->get_list($field,'user',$where,$condition);
	}
	//插入和更新
	public function write_data()
	{
		$data  = array('id'=>'1'); //更新的数据
		$where = array(); //update时要传入的条件  为空视为插入
		$this->write('admin',$data,$where);
	}
}