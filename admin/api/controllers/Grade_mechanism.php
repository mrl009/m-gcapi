<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Grade_mechanism控制器
 * 
 * @author      ssm
 * @package     controllers
 * @version     v1.0 2017/10/16
 */
class Grade_mechanism extends MY_Controller
{
	public function __construct()
    {
        parent::__construct();
        $this->load->model('Grade_mechanism_model', 'core');
    }

    /**
	 * 获取全部数据
	 *
	 * @access public
	 * @return Array
	 */
	public function all()
	{
		$where  = $this->all_functions('get_where');
		$where2 = $this->all_functions('get_where2');
		$page   = $this->all_functions('get_page');
		$result = $this->core->get_alls($where, $where2, $page);
		$this->return_json(OK, ['rows'=>$result, 'total' => count($result)]);
	}

    /**
     * 统计VIP的用户数量
     *
     * @access public
     * @return Array
     */
    public function user_count()
    {
        $vipId = (int)$this->G('vipId');
        list($code, $content) = $this->core->user_count($vipId);
        $this->return_json($code, $content);
    }

	/**
	 * 修改数据
	 *
	 * @access public
	 * @return Array
	 */
	public function upd()
	{
        exit;
		$id = (int)$this->P('id');
		$data = $this->upd_functions('verify_upd');
		$flag = $this->core->update_data($id, $data);
		if ($flag) {
			$this->return_json(OK, '修改成功');
		} else {
			$this->return_json(E_ARGS, '修改失败');
		}
	}

	/**
	 * 新增数据
	 *
	 * @access public
	 * @return Array
	 */
	public function add()
	{
        exit;
		$data = $this->upd_functions('verify_upd');
		$flag = $this->core->add_data($data);
		if ($flag) {
			$this->return_json(OK, '新增成功');
		} else {
			$this->return_json(E_ARGS, '新增失败');
		}
	}

	/**
     * 获取查询功能集
     *
     * @access protected
     * @param String $function 方法
     * @return Array
     */
    protected function all_functions($function='')
    {
        $class = [];

        // 查询条件
        $class['get_where'] = function() {
	        return [];
        };

        // 查询条件2
        $class['get_where2'] = function() {
	        return [];
        };

        // 分页条件
        $class['get_page'] = function() {
        	$page = (int)$this->G('page') > 0 ?
                	(int)$this->G('page') : 1;
	        $rows = (int)$this->G('rows') > 0 ?
	                (int)$this->G('rows') : 50;
	        if ($rows > 500) {
	            $rows = 500;
	        }
	        $page   = array(
	            'page'  => $page,
	            'rows'  => $rows,
	        );
	        return $page;
        };

        if (array_key_exists($function, $class)) {
        	return $class[$function]();
        }
        return $class;
    }

    /**
     * 获取修改功能集
     *
	 * @access public
	 * @param String $function 方法
	 * @return Array
     */
    protected function upd_functions($function='')
    {
    	$class = [];

    	// 获取修改数据
    	$class['get_upd_data'] = function () {
    		return array(
    			'title' => $this->P('title'),
    			'integral' => $this->P('integral'),
    			'promotion_award' => $this->P('promotion_award'),
    			'skip_award' => $this->P('skip_award'),
    			'user_sum' => $this->P('user_sum'),
    			'status' => $this->P('status')
    		);
    	};

    	// 验证修改数据
    	$class['verify_upd'] = function() use (&$class) {
    		$rule = array(
    			'title' => 'require',
    			'integral' => 'require|number|egt:0',
    			'promotion_award' => 'require|number|egt:0',
    			'skip_award' => 'require|number|egt:0',
    			'user_sum' => 'require|number|egt:0',
    			'status' => 'require|number|in:1,2',
    		);
    		$msg  = array(
    			'title.require' => '标题不能为空',
    			'integral.require' => '成长积分不能为空',
    			'promotion_award.require' => '晋级奖励不能为空',
    			'skip_award.require' => '跳级奖励不能为空',
    			'user_sum.require' => '会员人数不能为空',
    			'status.require' => '状态不能为空',

    			'integral.number' => '成长积分只能是数字',
    			'promotion_award.number' => '基金奖励只能是数字',
    			'skip_award.number' => '跳级奖励只能是数字',
    			'user_sum.number' => '会员人数只能是数字',

    			'integral.egt' => '成长积分必须大于1',
    			'promotion_award.egt' => '基金奖励必须大于1',
    			'skip_award.egt' => '跳级奖励必须大于1',
    			'user_sum.egt' => '会员人数必须大于1',

    			'status.in' => '状态只能是1正常或者2关闭',
    		);
    		$data = $class['get_upd_data']();
    		$this->validate->rule($rule, $msg);
    		$result = $this->validate->check($data);
	        if (!$result) {
	            $this->return_json(E_ARGS, $this->validate->getError());
	        }
	        return $data;
    	};

    	if (array_key_exists($function, $class)) {
        	return $class[$function]();
        }
        return $class;
    }
}