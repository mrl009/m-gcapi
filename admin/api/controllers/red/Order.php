<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * 红包模块---订单列表---控制器
 * @模块   红包中心／获取订单列表
 * 
 * @author      ssm
 * @package     controllers/red/Order
 * @version     v1.0 2017/09/04
 * @copyright
 * @link
 */
class Order extends MY_Controller
{

    /**
     * 构造函数
     *
     */
    public function __construct()
    {
        parent::__construct();
        $this->load->model('red/Red_model', 'core');
    }


    /**
     * 获取订单设置列表
     *
     * @access public
     * @return json
     */
    public function all()
    {
        $page = $this->_get_page();
        $where = $this->_get_where('order');
        $where2 = $this->_get_where2();
        $data = $this->core->get_order_list([
                            'where'=>$where,
                            'where2'=>$where2,
                            'page'=>$page]);
        return $this->return_json(OK, $data);
    }


    /**
     * 验证数据
     *
     * @access private
     * @return Array
     */
    private function _verify_data()
    {
        $data = [];
        return $data;
    }

    /**
     * 获取where查询条件
     *
     * @access private
     * @return Array
     */
    private function _get_where()
    {
        if (empty($this->G('start_time'))) {
            $start = strtotime(date('Y-m-d 00:00:00'));
        } else {
            $start = strtotime($this->G('start_time').' 00:00:00');
        }
        if (empty($this->G('end_time'))) {
            $end = strtotime(date('Y-m-d 23:59:59'));
        } else {
            $end = strtotime($this->G('end_time').' 23:59:59');
        }
        if (!empty($this->G('username'))) {
            $uid = $this->core->get_one('id', 'user',
                ['username'=>$this->G('username')]);
            $uid = empty($uid) ? -1 : $uid['id'];
        }
        /************************/
        $where['uid ='] = empty($uid)?null:$uid;
        $where['add_time >='] = $start;
        $where['add_time <='] = $end;

        if (!empty($end) && !empty($start) &&
            ($end - $start) > ADMIN_QUERY_TIME_SPAN ){
            $this->return_json(E_ARGS,'查询时间跨度不能超过2个月');
        }
        if (!empty($this->G('rid'))) {
            $where = [];
            $where['bag_id'] = (int)$this->G('rid');
        }
        return $where;
    }

    /**
     * 获取where2查询条件
     *
     * @access private
     * @param String $scene 场景
     * @return Array
     */
    private function _get_where2($scene='')
    {
        $where2['orderby'] = ['add_time'=>'desc'];
        return $where2;
    }

    /**
     * 验证page分页条件
     *
     * @access private
     * @return Array
     */
    private function _get_page()
    {
        // 分页
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
    }
}