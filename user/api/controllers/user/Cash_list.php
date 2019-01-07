<?php
/**
 * @模块   会员中心／账户明细
 * @版本   Version 1.0.0
 * @日期   2017-03-30
 * shensiming
 */

defined('BASEPATH') or exit('No direct script access allowed');

class Cash_list extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('user/Cash_list_model', 'core');
    }


    /******************公共方法*******************/
    /**
     * 获取列表数据
     */
    public function get_list()
    {
        if (empty($this->user['id'])) {
            $this->return_json(E_ARGS, '无效用户');
        }

        $start_time = strtotime($this->G('time_start'))
                    ? strtotime($this->G('time_start').' 00:00:00') : 0;
        if ($start_time < (time()-86440*60)) {
            $start_time = time()-86440*60;
        }
        // 精确条件
        $basic = array(
            'a.uid' => $this->user['id'],
            'a.addtime >=' => $start_time,
            'a.addtime <=' => strtotime($this->G('time_end'))
                    ? strtotime($this->G('time_end').' 23:59:59') : 0);
        // 高级搜索
        $senior = array(
            'join' => 'cash_type',
            'on'   => 'a.type=b.id');
        if (!empty($this->G('type'))) {
            //            if ($this->G('type') == ) {
//            }
            $senior['wherein'] = array('a.type'=>explode(',', $this->G('type')));
        }
        // 排序分页
        $page = (int)$this->G('page') > 0 ?
                (int)$this->G('page') : 1;
        $rows = 15;
        $page   = array(
            'page'  => $page,
            'rows'  => $rows,
        );
        $this->load->model('comm_model', 'comm');
        // edit by wuya 20180726 更新过期未处理入款订单 改到 定时任务里处理
        // $this->comm->update_online_status(2, $this->user['id']);
        $data = $this->core->get_cash_list($basic, $senior, $page);
        if ($data) {
            $this->return_json(OK, $data);
        } else {
            $this->return_json(OK, array('rows'=>array()));
        }
    }

    /**
     * 代理列表数据
     */
    public function get_agent_list()
    {
        if (empty($this->user['id'])) {
            $this->return_json(E_ARGS, '无效用户');
        }

        $start_time = strtotime($this->G('time_start')) ? strtotime($this->G('time_start').' 00:00:00') : 0;
        if ($start_time < (time()-86440*60)) {
            $start_time = time()-86440*60;
        }
        // 精确条件
        $basic = array(
            'a.agent_id' => $this->user['id'],
            'a.addtime >=' => $start_time,
            'a.addtime <=' => strtotime($this->G('time_end')) ? strtotime($this->G('time_end').' 23:59:59') : 0
        );
        // 获取代理用户id
        $username = $this->G('username');
        if (!empty($username)) {
            $uid = $this->core->get_one('id', 'user', array('username' => $username));
            if (isset($uid['id'])) {
                $basic['a.uid'] = $uid['id'];
            } else {
                $this->return_json(OK, ['total' => 0, 'rows' => []]);
            }
        }
        // 高级搜索
        $senior = array(
            'join' => 'cash_type',
            'on'   => 'a.type=b.id');
        if (!empty($this->G('type'))) {
            $senior['wherein'] = array('a.type'=>explode(',', $this->G('type')));
        }
        // 排序分页
        $page = (int)$this->G('page') > 0 ? (int)$this->G('page') : 1;
        $rows = 15;
        $page   = array(
            'page'  => $page,
            'rows'  => $rows,
        );
        $this->load->model('comm_model', 'comm');
        // edit by wuya 20180726 更新过期未处理入款订单 改到 定时任务里处理
        //$this->comm->update_online_status(2, $this->user['agent_id'], true);
        $data = $this->core->get_cash_list($basic, $senior, $page);
        if ($data) {
            $this->return_json(OK, $data);
        } else {
            $this->return_json(OK, array('rows'=>array()));
        }
    }

    /**
     * 获取筛选类型
     */
    public function get_type()
    {
        $data = $this->core->get_opts();
        array_push($data['rows'], array('label'=>'全部',  'value'=>0));
        foreach ($data['rows'] as $key => $value) {
            if (!strcasecmp($value['label'], '公司入款不含优惠')) {
                $data['rows'][$key]['label'] = '公司入款不含优惠';
            } elseif (!strcasecmp($value['label'], '线上入款不含优惠')) {
                $data['rows'][$key]['label'] = '线上入款无优惠';
            } elseif (!strcasecmp($value['label'], '公司入款')) {
                $data['rows'][$key]['label'] = '公司入款';
            } elseif (!strcasecmp($value['label'], '优惠卡入款')) {
                $data['rows'][$key]['label'] = '彩豆充值';
            } elseif (!strcasecmp($value['label'], '人工取出')) {
                $data['rows'][$key]['label'] = '其他扣款方式';
            }
        }
        $this->return_json(OK, $data);
    }
    /*******************************************/
}
