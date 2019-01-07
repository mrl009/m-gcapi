<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * 注单列表
 * Created by PhpStorm.
 * User: mrl
 * Date: 2017/4/7
 * Time: 下午2:49
 */
class Order extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('MY_Model', 'core');
        $this->load->model('order/Order_model');
        $this->load->model('order/Bet_record_model');
    }

    /**
     * 获取来源
     */
    public function getFromType()
    {
        $fromType = $this->Order_model->getFromType();
        $this->return_json(OK, $fromType);
    }

    /**
     * 获取注单列表
     *
     * @access public
     * @return Array
     */
    public function get_list()
    {
        $gid = (int)$this->G('gid');
        $ctg = $this->G('ctg');
        $src = (int)$this->G('src');
        $senior = array();
        // 获取搜索条件
        $where = [
            'a.agent_id'     => (int)$this->G('agent_id'),
            'a.issue'        => $this->G('issue'),
            'a.order_num'    => $this->G('order_num'),
            'b.status'       => (int)$this->G('status'),
            'c.username'     => $this->G('account'),
            'c.id'           => (int)$this->G('uid'),
            'a.created >='   => strtotime($this->G('from_time').' 00:00:00'),
            'a.created <='   => strtotime($this->G('to_time').' 23:59:59'),
        ];

        // 只能查两个月内的数据
        $from_time = time() - $where['a.created >='];
        $to_time =  $where['a.created <=']-time();
        if ($from_time > ADMIN_ORDER_QUERY) {
            $where['a.created >='] = time() - ADMIN_ORDER_QUERY;
            if ($to_time > ADMIN_ORDER_QUERY) {
                unset($where['a.created <=']);
            }
        }

        if (!empty($gid)) {
            $where['a.gid'] = $gid;
        } else {
            if (in_array($ctg, ['gc', 'sc'])) {
                $gids = $ctg == 'gc' ? GC : SC;
                $gids && $senior['wherein'] = array('a.gid' => explode(',', $gids));
            }
        }

        $senior['join'] = [
            ['table'=>'bet_wins as b', 'on'=>'a.order_num = b.order_num'],
            ['table'=>'user as c',     'on'=>'a.uid=c.id'],
        ];
        $page = (int)$this->G('page') > 0 ? 
                (int)$this->G('page') : 1;
        $rows = (int)$this->G('rows') > 0 ? 
                (int)$this->G('rows') : 50;
        if ($rows > 500 || $rows < 1) {
            $rows = 50;
        }
        $page = [
            'page' => $page,
            'rows' => $rows,
//            'total' => 100000
        ];

        // 如果查询订单号，则把日期抹去
        if (!empty($where['a.order_num'])) {
            $where['a.created >='] = 0;
            $where['a.created <='] = 0;
        }

        $data = $this->Bet_record_model->get_order_list($where, $senior, $page, $where['b.status'], $src);
        $this->return_json(OK, $data);
    }

    /**
     * 获取注单列表，测试接口
     *
     * @access public
     * @return Array
     */
    public function get_list2()
    {
        // 获取搜索条件
        $where = [
            'a.order_num'    => $this->G('order_num'),
            'b.status'       => (int)$this->G('status'),
            'c.username'     => $this->G('account'),
            'c.id'           => (int)$this->G('uid'),
            'c.from_way'     => $this->G('src'),
            'a.created >='   => strtotime($this->G('from_time').' 00:00:00'),
            'a.created <='   => strtotime($this->G('to_time').' 23:59:59'),
        ];
        $join['join'] = [
            ['table'=>'bet_wins_copy as b', 'on'=>'a.order_num = b.order_num'],
            ['table'=>'user as c',     'on'=>'a.uid=c.id'],
        ];
        $page = (int)$this->G('page') > 0 ? 
                (int)$this->G('page') : 1;
        $rows = (int)$this->G('rows') > 0 ? 
                (int)$this->G('rows') : 20;
        $page = [
            'page' => $page,
            'rows' => $rows,
        ];
        if(!empty($where['a.order_num']) && 
            $where['a.order_num'] < 100 ) {
            $gid = $where['a.order_num'] > 10 ? $where['a.order_num'] : '0'.$where['a.order_num'];
            unset($where['a.order_num']);
            $where['a.order_num like'] = '9'.$gid.'%';
        }

        $data = $this->Bet_record_model->get_order_list($where, $join, $page, $where['b.status']);
        $rs = ['total' => 100000, 'rows' => $data];
        $this->return_json(OK, $rs);
    }

    /**
     * 获取列表
     */
    public function getOrderList()
    {
        //打开表
        $this->core->open('bet_index');
        // 获取搜索条件
        $condition = [
            'order_num' => $this->G('order_num'),
            'order_type'=> $this->G('order_type'),
            'from_time' => $this->G('from_time'),
            'to_time'   => $this->G('to_time'),
            'account'   => $this->G('account'),
            'uid'       => $this->G('uid'),
            'src'       => $this->G('src'),
            'status'    => $this->G('status'),
        ];
        $searchInfo = $this->Order_model->getBasicAndSenior($condition);
        $page = array(
            'page' => $this->G('page'),
            'rows' => $this->G('rows'),
            'order' => $this->G('order'),
            'sort' => $this->G('sort'),
			//'total' => -1,
        );
        $arr = $this->core->get_list('*', 'bet_index', $searchInfo['basic'], $searchInfo['senior'], $page);
        // 格式化数据
        $data = $this->Order_model->formatData($arr, $condition);
        $rs = ['total' => 10000, 'rows' => $data['rows']];
        $this->return_json(OK, $rs);
    }

    /**
     * 详情
     */
    public function getOrderDetail()
    {
        $orderNum = $this->G('order_num');
        if (empty($orderNum)) {
            $this->return_json(E_ARGS, '参数错误');
        }
        $rs = $this->Order_model->getOrderDetail($orderNum);
        $this->return_json(OK, $rs);
    }

    /**********************权限控制分开国彩私彩取消订单*****************************/
    public function gc_cancel() {
        $order_num = $this->P('order_num');
        if (empty($order_num) || strlen($order_num) < 10) {
            $this->return_json(E_ARGS, '无效的order_num');
        }
        $this->cancel($order_num);
    }

    public function sc_cancel() {
        $order_num = $this->P('order_num');
        if (empty($order_num) || strlen($order_num) < 10) {
            $this->return_json(E_ARGS, '无效的order_num');
        }
        $this->cancel($order_num);
    }
    /**********************权限控制分开国彩私彩取消订单END*****************************/

    /**
     * @brief 取消订单
     *      过期未结算订单也可以撤单
     * @param int   $order_num    订单id
     *          取消订单
     * @return ok/false
     */
    public function cancel($order_num = 0) /* {{{ */
    {
        $gid = (int) substr($order_num, 1, 2);
        $this->load->model('games_model');
        $game = $this->games_model->info($gid);
        if (count($game) < 1) {
            $this->return_json(E_ARGS, '无效的gid');
        }

        $order = $this->Order_model->info($order_num, 'index');
        if (count($order) < 1) {
            $this->return_json(E_ARGS, '无效的order_num');
        }

        $dbn = $this->Order_model->sn;
        if ($this->Order_model->cancel_order($dbn, $game['sname'], $gid, $order['uid'], $order['order_num'])) {
            $this->return_json(OK);
        }

        $this->return_json(E_OP_FAIL, '无法撤单');
    } /* }}} */

    /**
     * 根据开奖号获取中奖内容
     */
    public function getWinContent()
    {
        $orderNum = $this->G('order_num');
        if (empty($orderNum)) {
            $this->return_json(E_ARGS, '参数错误');
        }
        $rs = $this->Order_model->getWinContent($orderNum);
        $this->return_json(OK, $rs);
    }
}
