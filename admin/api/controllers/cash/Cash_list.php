<?php
/**
 * @模块   现金系统／现金流水
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
        $this->load->model('cash/Cash_list_model', 'core');
    }



    /******************公共方法*******************/
    /**
     * 获取现金流水数据
     */
    public function get_list()
    {
        //精确条件  locate
        $basic = array(
            'a.agent_id'   => (int)$this->G('agent_id'),
            'a.addtime >=' => strtotime($this->G('time_start').' 00:00:00'),
            'a.addtime <=' => strtotime($this->G('time_end').' 23:59:59'),
            );
        /*** 查询时间跨度不能超过两个月 ***/
        $diff_time = $basic['a.addtime <=']-$basic['a.addtime >='];
        if ($diff_time > ADMIN_QUERY_TIME_SPAN) {
            $basic['a.addtime <='] = $basic['a.addtime >=']+ADMIN_QUERY_TIME_SPAN;
        }

        if (!empty($this->G('f_ordernum'))) {
            $basic['a.addtime >='] = null;
            $basic['a.addtime <='] = null;
            $basic['a.order_num like '] = '%'.$this->G('f_ordernum').'%';
        }

        if (!empty($this->G('uid'))) {
            $basic = array_merge($basic, array('a.uid' => $this->G('uid')));
        }

        // 高级搜索
        $senior = [];
        $username = $this->G('f_username');
        if (!empty($username)) {
            $uid = $this->core->get_one('id', 'user', ['username'=>$username]);
            $basic['a.uid'] = empty($uid) ? '0' : $uid['id'];
        }

        $type = $this->G('types');
        if (!empty($type)) {
            $senior['wherein'] = array('a.type'=>explode(',', $type));
        }

        // 分页
        $page = (int)$this->G('page') > 0 ?
                (int)$this->G('page') : 1;
        $rows = (int)$this->G('rows') > 0 ?
                (int)$this->G('rows') : 50;
        if ($rows > 500 || $rows < 1) {
            $rows = 50;
        }
        $page   = array(
            'page'  => $page,
            'rows'  => $rows,
            'sort'  => $this->G('sort'),
            'order' => $this->G('order')
        );
        // 排序
        $sort_field = array('type', 'amount',
                            'balance', 'type',
                            'addtime', 'remark');
        if (!in_array($page['sort'], $sort_field)) {
            $page['sort'] = 'id';
        }

        $arr = $this->core->get_cash_list($basic, $senior, $page);
        $rs  = array('total'=>$arr['total'],
                    'rows'  =>$arr['rows'],
                    'types' =>$arr['types'],
                    'footer'=>$arr['footer'],);
        
        /**** 格式化小数点 ****/
        $rs['rows'] = stript_float($rs['rows']);
        $rs['footer'] = stript_float($rs['footer']);

        $this->return_json(OK, $rs);
    }


    public function get_types()
    {
        $select = 'id, name, show_id as sid, cash_category as is_io';
        $resu = $this->core->get_list($select, 'cash_type');
        foreach ($resu as $key => $value) {
            if (!strcasecmp($value['name'], '优惠卡入款')) {
                $resu[$key]['name'] = '彩豆充值';
            }
        }
        $this->return_json(OK, ['rows'=>$resu]);
    }
    /********************************************/
}
