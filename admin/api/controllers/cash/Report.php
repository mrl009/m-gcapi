<?php
/**
 * @模块   现金系统／出入款汇总
 * @版本   Version 1.0.0
 * @日期   2017-03-30
 * shensiming
 */

defined('BASEPATH') or exit('No direct script access allowed');


class Report extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('cash/Report_model', 'core');
    }



    /******************公共方法*******************/
    /**
     * 获取出入汇总数据
     */
    public function get_list()
    {
        //精确条件
        $basic = array(
            'a.agent_id'   => (int)$this->G('agent_id'),
            'b.username'       => $this->G('f_username'),
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

        $data = $this->core->get_report($basic);
        $data['out_discount_total'] =
			$data['in_company_discount'] + $data['in_online_discount'] + $data['in_people_discount'] +
			$data['in_card_total'] + $data['in_register_discount'] +
            $data['activity_total'];
        $data['total_price'] =
			($data['in_company_total'] + $data['in_online_total'] + $data['in_people_total'] + $data['in_member_out_deduction']) -
			($data['out_company_total'] + $data['out_people_total'] + $data['out_return_water'] + $data['out_discount_total']);

        $data['win_lose'] =
			($data['in_company_total'] + $data['in_online_total'] + $data['in_people_total']) -
			($data['out_company_total'] + $data['out_people_total']);
        $data['out_discount_num'] += $data['discount_num'];
        unset($data['discount_num']);

        // 关于浮点数运算精度丢失转换
        foreach ($data as $key => $value) {
            $data[$key] = (float)sprintf("%.3f", $value);
        }
        $this->return_json(OK, $data);
    }

    /**
     * 统计每天不含优惠的出入款记录
     * cash_report
     * 入款包括   公司入款  线上支付 人工存入
     * 出款  线上  人工出款
     */
    public function cash_date_report()
    {
        $start = $this->G('start') or $start = date('Y-m-d', strtotime("-7 day"));
        $end   = $this->G('end')   or $end   = date('Y-m-d');
        $page   = [
            'page'  => $this->G('page'),
            'rows'  => $this->G('rows'),
            'order' => $this->G('order'),
            'sort'  => $this->G('sort'),
            'total' => -1,

        ];
        $where  = [
            'report_date >=' => $start,
            'report_date <=' => $end,
        ];
        $where2 = [
            'groupby' => array('report_date'),
        ];
        $str = 'SUM(in_company_total + in_online_total + in_people_total) in_money,
		        SUM(out_people_total + out_company_total ) out_money , report_date ';
        $data = $this->core->get_list($str, 'cash_report', $where, $where2, $page);
        $this->return_json(OK, $data);
    }
    /********************************************/
}
