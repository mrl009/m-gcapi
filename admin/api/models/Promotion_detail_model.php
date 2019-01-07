<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Grade_mechanism模块
 *
 * @author      ssm
 * @package     models
 * @version     v1.0 2017/10/16
 */
class Promotion_detail_model extends MY_Model
{

	/**
	 * 表名
	 */
	protected $table = 'promotion_detail';

	/**
	 * 获取gc_grade_mechanism表全部数据
	 *
	 * @access public
	 * @param Array $query 查询条件参数
	 * @return Array
	 */
	public function all($query)
	{
		list($field, $where, $where2, $page) = $this->check_all_query($query);
		$data = $this->get_list($field, $this->table, $where, $where2, $page);

		$cache = array('username');
		foreach ($data['rows'] as $key => &$value) {
			$user_id = $value['uid'];
            if (empty($cache['uid'][$user_id])) {
                $user = $this->user_cache($user_id);
                $cache['uid'][$user_id] = $user;
            }
            $value['username'] = $cache['uid'][$user_id]['username'];
            $value['add_time'] = date('Y-m-d H:i:s', $value['add_time']);
            unset($value['uid']);
		}

		return $data;
	}

	/**
	 * 对查询数据进行处理
	 *
	 * @access public
	 * @param Array $query 查询条件参数
	 		  ['username', 'start_date', 'end_date', 'page', 'rows']
	 * @return Array
	 */
	private function check_all_query($query)
	{
		$class = [];

		// 查询条件2
        $class['get_field'] = function() use (&$query) {
	        return 'a.id, a.uid, b.title,a.before_id, a.grade_id, a.jj_money, a.integral, a.is_tj, a.add_time, a.update_time, a.is_receive';
        };

        // 查询条件
        $class['get_where'] = function() use (&$query) {

        	$where = [];

        	$username = $query['username'];
        	if (!empty($username)) {
        		$uid = $this->core->get_one('id', 'user', ['username'=>$username]);
        		$where['a.uid'] = isset($uid['id']) ? $uid['id'] : 0;
        	}

        	if (!empty($query['start_date'])) {
                $where['add_time >='] = strtotime($query['start_date'].' 00:00:00');
            }

            if (!empty($query['end_date'])) {
                $where['add_time >='] = strtotime($query['end_date'].' 00:00:00');
            }
	        return $where;
        };

        // 查询条件2
        $class['get_where2'] = function() use (&$query) {
	        return  ['join' =>
	        	[
            		['table' => 'grade_mechanism as b','on' => 'b.id=a.grade_id']
            	]
        	];
        };

        // 分页条件
        $class['get_page'] = function() use (&$query) {
        	$page = (int)$query['page'] > 0 ?
                	(int)$query['page'] : 1;
	        $rows = (int)$query['rows'] > 0 ?
	                (int)$query['rows'] : 50;
	        if ($rows > 500) {
	            $rows = 500;
	        }
	        $page   = array(
	            'page'  => $page,
	            'rows'  => $rows,
	        );
	        return $page;
        };

        return [$class['get_field'](),
        		$class['get_where'](),
    			$class['get_where2'](),
    			$class['get_page']()];
	}
}