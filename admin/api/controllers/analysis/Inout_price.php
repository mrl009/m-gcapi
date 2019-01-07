<?php
/**
 * @模块   会员分析／出入款分析
 * @版本   Version 1.0.0
 * @日期   2017-03-30
 * shensiming
 */

defined('BASEPATH') or exit('No direct script access allowed');
class Inout_price extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('cash/Report_model', 'core');
    }


    /**
     * 出入款分析API
     *
     * @access pulbic
     * @return String json
     */
    public function get_list()
    {
        //精确条件
        $basic = array(
            //'a.agent_id'   => (int)$this->G('agent_id'),
            'a.report_date >=' => empty($this->G('time_start')) ? date('Y-m-d') : $this->G('time_start'),
            'a.report_date <=' => empty($this->G('time_end')) ? date('Y-m-d') : $this->G('time_end')
            );
        if ($this->G('agent_id')) {
            $basic['a.agent_id'] = (int)$this->G('agent_id');
        }
        /*** 查询时间跨度不能超过两个月 ***/
        $start_time = strtotime($basic['a.report_date >=']);
        $end_time = strtotime($basic['a.report_date <=']);
        $diff_time = $end_time - $start_time;
        if ($diff_time > ADMIN_QUERY_TIME_SPAN) {
            $basic['a.report_date <='] = date('Y-m-d',$start_time+ADMIN_QUERY_TIME_SPAN);
        }

        // 高级搜索
        $senior['groupby'] =  array('a.uid');
        $senior['join'][] =   array('table' => 'user as b', 'on' => 'a.agent_id=b.id');

        $username = trim($this->G('f_username'));
        if (!empty($username)) {
            $uid = $this->core->get_one('id', 'user', ['username'=>$username]);
            $basic['a.uid'] = empty($uid) ? '0' : $uid['id'];
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
            'rows'  => $rows);
        // 排序
        $sort_field = array('username',
                            'in_company_total', 'in_company_discount',
                            'in_company_num', 'in_online_total',
                            'in_online_discount', 'in_online_num',
                            'in_people_total', 'in_people_discount',
                            'in_people_num', 'in_card_total',
                            'in_card_num', 'out_people_total',
                            'out_company_total', 'report_date',
                            'out_company_num','in_register_discount');
        if (in_array($this->G('sort'), $sort_field)) {
            $senior['orderby'] = array($this->G('sort')=>
                                    $this->G('order'));
        }
        $impounded = $this->G('impounded');
        if ($impounded == 1) {
            $senior['wheresql'][] = '(a.in_company_discount > 0 or a.in_online_discount > 0 or a.in_people_discount > 0 or a.in_card_total > 0 or a.in_register_discount > 0 or a.activity_total > 0)';
        }

        $arr = $this->core->get_in_out($basic, $senior, $page);
        $footer = [];
        foreach ($arr['rows'] as $k => $v) {
            if ($impounded == 1) {
                    $v['in_company_total'] = '-';
                    $v['in_online_total'] = '-';
                    $v['in_people_total'] = '-';
                    $v['out_people_total'] = '-';
                    $v['out_people_num'] = '-';
                    $v['out_company_total'] = '-';

                    $v['in_company_num'] = $v['in_company_discount_num'];
                    $v['in_online_num'] = $v['in_online_discount_num'];
                    $v['in_people_num'] = $v['in_people_discount_num'];
                    $v['in_card_num'] = $v['in_card_num'];
                    $arr['rows'][$k] = $v;
                }   
            foreach ($v as $key => $value) {

                if (is_numeric($value)&&$key!='username') {
                    if (empty($footer[$key])) {
                        $footer[$key] = 0;
                    }
                    $footer[$key] += $value;
                }
            }
            if (is_numeric($arr['rows'][$k]['in_register_discount'])) {
                $arr['rows'][$k]['in_register_discount'] = $arr['rows'][$k]['in_register_discount'].'('.$arr['rows'][$k]['discount_num'].')';
            }
        }
        if ($impounded == 1 && isset($footer['in_register_discount'])) {
            $footer['in_register_discount'] = $footer['in_register_discount'].'('.$footer['discount_num'].')';
        }
        $arr['footer'][] = $footer;
        /**** 格式化小数点 ****/
        // $history['rows'] = stript_float($history['rows']);
        $arr = stript_float($arr);
        $this->return_json(OK, $arr);
    }

    /**
     * 给予优惠跳转API
     *
     * @access pulbic
     * @return String json
     */
    public function preferential_list()
    {
        //精确条件
        $basic = array(
            'a.agent_id'   => (int)$this->G('agent_id'),
            'a.report_date >=' => empty($this->G('time_start')) ? date('Y-m-d') : $this->G('time_start'),
            'a.report_date <=' => empty($this->G('time_end')) ? date('Y-m-d') : $this->G('time_end')
            );

        /*** 查询时间跨度不能超过两个月 ***/
        $start_time = strtotime($basic['a.report_date >=']);
        $end_time = strtotime($basic['a.report_date <=']);
        $diff_time = $end_time - $start_time;
        if ($diff_time > ADMIN_QUERY_TIME_SPAN) {
            $basic['a.report_date <='] = date('Y-m-d',$start_time+ADMIN_QUERY_TIME_SPAN);
        }

        // 高级搜索
        $senior = array('groupby' => array('a.uid'));
        $username = trim($this->G('f_username'));
        if (!empty($username)) {
            $uid = $this->core->get_one('id', 'user', ['username'=>$username]);
            $basic['a.uid'] = empty($uid) ? '0' : $uid['id'];
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
            'rows'  => $rows);
        // 排序
        $sort_field = array('username',
                            'in_company_total', 'in_company_discount',
                            'in_company_num', 'in_online_total',
                            'in_online_discount', 'in_online_num',
                            'in_people_total', 'in_people_discount',
                            'in_people_num', 'in_card_total',
                            'in_card_num', 'out_people_total',
                            'out_company_total', 'report_date',
                            'out_company_num','in_register_discount');
        if (in_array($this->G('sort'), $sort_field)) {
            $senior['orderby'] = array($this->G('sort')=>
                                    $this->G('order'));
        }
        $impounded = $this->G('impounded');
        if ($impounded == 1) {
            $senior['wheresql'][] = '(a.in_company_discount > 0 or a.in_online_discount > 0 or a.in_people_discount > 0 or a.in_card_total > 0 or a.in_register_discount > 0)';
        }
        $arr = $this->core->get_preferential($basic, $senior, $page);
        $this->return_json(OK, $arr);
    }
}
