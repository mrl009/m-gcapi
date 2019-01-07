<?php
/**
 * @模块   报表
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
        $this->load->model('form/Report_model', 'report_model');
    }

    /**
     * 报表查询页面数据
     */
    public function index()
    {
        $data = array();

        /* 报表最下面模块 */
        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', time()-86400);
        // 输赢结果（今天与昨天）
        $result_t = $this->report_model->date_result_price($today, $today);
        $data['win_lose']['today'] = $result_t['diff_price']
                            ? $result_t['diff_price'] : 0.00;
        $result_y = $this->report_model->date_result_price($yesterday, $yesterday);
        $data['win_lose']['yesterday'] = $result_y['diff_price']
                            ? $result_y['diff_price'] : 0.00;
        // 盈利最多的彩种，亏损最多的彩种（今天与昨天）
        $result_t = $this->report_model->game_result_price($today);
        $result_y = $this->report_model->game_result_price($yesterday);
        $data['win']['today'] = $result_t ? current($result_t) : 0;
        $data['win']['yesterday'] = $result_y ? current($result_y) : 0;
        $data['lose']['today'] = $result_t ? end($result_t) : 0;
        $data['lose']['yesterday'] = $result_y ? end($result_y) : 0;
        /* 报表右边模块 */
        $min_date = $this->report_model->min_report_date();
        $data['month_num'] = $this->_months($min_date['min']);
        $this->report_model->select_db('private');
        $data['levels'] = $this->report_model->get_list(
            'id, level_name as name', 'level');
        array_unshift($data['levels'], array('id'=>0,'name'=>'全部'));

        if (is_array($data['win']['today'])) {
            $data['win']['today'] = $data['win']['today']['diff_price'].
                '('.$data['win']['today']['name'].')';
        }
        if (is_array($data['win']['yesterday'])) {
            $data['win']['yesterday'] = $data['win']['yesterday']['diff_price'].'('.$data['win']['yesterday']['name'].')';
        }
        if (is_array($data['lose']['today'])) {
            $data['lose']['today'] = $data['lose']['today']['diff_price'].
                '('.$data['lose']['today']['name'].')';
        }
        if (is_array($data['lose']['yesterday'])) {
            $data['lose']['yesterday'] = $data['lose']['yesterday']['diff_price'].'('.$data['lose']['yesterday']['name'].')';
        }
        $this->return_json(OK, $data);
    }

    /**
     * 获取报表数据接口
     * @access !public
     * @param
     * @return json
     */
    public function get_list()
    {
        $form_type = (int)$this->G('form_type');
        //精确条件
        $basic = array(
            'a.agent_id ='   => (int)$this->G('agent_id'),
            'a.report_date >=' => empty($this->G('time_start')) ? date('Y-m-d') : $this->G('time_start'),
            'a.report_date <=' => empty($this->G('time_end')) ? date('Y-m-d') : $this->G('time_end'),
            'b.level_id =' => (int)$this->G('level_id'),
            'b.username =' => $this->G('username'),
            'a.gid =' => (int)$this->G('gid'),
            'a.uid =' => (int)$this->G('uid')
            );

        /*** 查询时间跨度不能超过两个月 ***/
        $start_time = strtotime($basic['a.report_date >=']);
        $end_time = strtotime($basic['a.report_date <=']);
        $diff_time = $end_time - $start_time;
        if ($diff_time > ADMIN_QUERY_TIME_SPAN) {
            $basic['a.report_date <='] = date('Y-m-d',$start_time+ADMIN_QUERY_TIME_SPAN);
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
        $order = array();
        $sort_field = array('total_price', 'valid_price', 'lucky_price', 'total_num', 'total_users','return_price', 'diff_price', 'bets_num');
        if (in_array($this->G('sort'), $sort_field)) {
            $order = array($this->G('sort') => $this->G('order'));
        }
        $data = $this->report_model->form_list($form_type, $basic, $page, $order);
        if (!empty($data) && !empty($data['rows']) && array_key_exists('diff_price', $order)) {
            foreach ($data['rows'] as $k=>$v) {
                $tag1[] = $v['diff_price'];
            }
            $or = strtolower($order['diff_price']) == 'asc' ? SORT_ASC : SORT_DESC;
            array_multisort($tag1, $or, $data['rows']);
        }

        /**** 格式化小数点 ****/
        // $history['rows'] = stript_float($history['rows']);
        $data = stript_float($data);
        
        $this->return_json(OK, $data);
    }

    /**
     * 获取给定时间内的输赢
     *
     * @access public
     * @return json
     */
    public function account()
    {
        $start = strtotime($this->G('start_time').' 00:00:00');
        $end = strtotime($this->G('end_time').' 23:59:59');
        if ($end-$start > ADMIN_QUERY_TIME_SPAN) {
            $this->return_json(E_ARGS, '查询时间阔度只能两个月');
        }
        $start = date('Y-m-d', $start);
        $end = date('Y-m-d', $end);
        $result = $this->report_model->date_result_price($start, $end);
        $this->return_json(OK, $result);
    }

    /**
     * 获取每月报表信息数据API
     *
     * @access public
     * @return json
     */
    public function months()
    {
        // 获取两年内的日期
        // $min_date = $this->report_model->min_report_date();
        
        $this->report_model->select_db('private');
        $year = $this->G('year') ? (int)$this->G('year') : date('Y');
        if ($year < 2017 || $year > date('Y')) {
            $this->return_json(E_ARGS, '年份错误');
        }
        $temp_date = $this->_months(date("{$year}-01-01"));
        $temp_date2[$year] = $temp_date[$year];
        // 获取报表抽水数据
        $start_time = strtotime((date('Y')-1).'-01-01');
        $end_time = strtotime('Y-'.(date('m')+1).'-01');
        $where = ['start_date >='=>$start_time, 'end_date <='=>$end_time];
        $select = 'id,start_date,total,rebate,pay_status';
        $report = $this->report_model->get_list($select, 'rebate_report', $where);
        // 格式化数据
        $temp_report = [];
        foreach ($report as $key => $value) {
            $value['start_date'] = date('Y-m-d', $value['start_date']);
            $temp_report[$value['start_date']] = $value;
        }
        // 拼接数据
        $data = [];
        $default = ['total'=>0, 'rebate'=>0, 'pay_status'=>0];
        foreach ($temp_date2 as $k1 => $v1) {
            sort($v1);
            foreach ($v1 as $k2 => $v2) {
                $temp = explode('~', $v2);
                if (empty($temp_report[$temp[0]])) {
                    $temp[0] = substr($temp[0], 5);
                    $temp[1] = substr($temp[1], 5);
                    $default['period'] = implode('~', $temp);
                    $default['nper'] = (int)(substr($default['period'],0,2));
                    $data[$k1][] = $default;
                } else {
                    $temp_1 = $temp[0];
                    $temp[0] = substr($temp[0], 5);
                    $temp[1] = substr($temp[1], 5);
                    $temp_report[$temp_1]['period'] = implode('~', $temp);
                    $temp_report[$temp_1]['nper'] = (int)(substr($temp_report[$temp_1]['period'],0,2));
                    unset($temp_report[$temp_1]['start_date']);
                    $data[$k1][] = $temp_report[$temp_1];
                    unset($temp_report[$temp_1]);
                }
            }
        }

        $this->return_json(OK, $data);
    }

    /**
     * 确认彩票账单状态
     */
    public function report_confirm()
    {
        $id = $this->P('id') ? (int)$this->P('id') : 0;
        if (empty($id)) {
            $this->return_json(E_ARGS, '参数错误');
        }
        $this->report_model->select_db('private');
        $this->report_model->write('rebate_report', ['pay_status' => 1], ['id' => $id]);
        $this->return_json(OK, '请求成功');
    }

    /**
     * 平台抽水API
     *
     * @access public
     * @param Intger $year 年份
     * @param Intger $month 月份
     * @return json
     */
    public function pumping()
    {
        // 数据验证
        $year = (int)$this->P('year');
        $month = (int)$this->P('month');
        if ($year < 2017) {
            $this->return_json(E_ARGS, '年份不能小于2017');
        }
        if ($month < 1 || $month > 12) {
            $this->return_json(E_ARGS, '一年只有12个月');
        }
        if ($year.$month >= date('Yn')) {
            $this->return_json(E_ARGS, '只能生成以前数据');
        }
        // 日期转换
        $start_str = $year.'-'.$month.'-01';
        $end_str = ($month == 12) ? (($year+1).'-01-01')
                    : ($year.'-'.($month+1).'-01');
        $start = date($start_str);
        $end = date('Y-m-d', strtotime($end_str)-1);
        // 获取数据
        $result = $this->report_model->date_result_price($start, $end);
        if (empty($result['diff_price'])) {
            $this->return_json(E_ARGS, '月份没有数据');
        }
        //$pumping = $this->report_model->pumping($result['diff_price']);
        $pumping = $this->report_model->new_pumping($result['diff_price']);
        if ($pumping < 0) {
            $pumping = 0;
        }
        // 添加数据：判断是添加还是更新
        $pumping = sprintf('%0.2f', ((int)($pumping*100))/100);
        $total = sprintf('%0.2f', ((int)($result['diff_price']*100))/100);
        $start_time = strtotime($start.' 00:00:00');
        $end_time = strtotime($end.' 23:59:59');
        $where = ['start_date'=>$start_time, 'end_date'=>$end_time];
        $rebateDate = $this->report_model->get_one('*', 'rebate_report', $where);
        // 1.没有数据则添加
        if (empty($rebateDate)) {
            $data = ['start_date'=>$start_time, 'end_date'=>$end_time,
                    'add_time'=>time(), 'total'=>$total,
                    'rebate'=>$pumping];
            $flag = $this->report_model->write('rebate_report', $data);
            if (!$flag) {
                $content = "添加报表抽水数据失败：".json_encode($data);
                $this->report_model->add_log($content);
                $this->return_json(E_ARGS,'添加失败');
            } else {
                $id = $this->report_model->db->insert_id();
                $content = "添加报表抽水数据成功ID({$id})：".json_encode($data);
                $this->report_model->add_log($content);
                $this->return_json(OK,['rebate'=>$pumping, 'total'=>$total]);
            }
        }
        // 2.有数据则更新
        else {
            if ($rebateDate['pay_status'] == 1) {
                $this->return_json(E_ARGS,'已支付无法刷新');
            }
            if (date('m',$rebateDate['start_date']) != date('m')) {
                $this->return_json(E_ARGS,'该月份已生成数据，无法刷新');
            }
            $data = ['total'=>$total,'rebate'=>$pumping];
            $flag = $this->report_model->write('rebate_report', $data, $where);
            if ($pumping == $rebateDate['rebate'] && $total == $rebateDate['total']) {
                $this->return_json(OK,['rebate'=>$pumping, 'total'=>$total]);
            }
            if (!$flag) {
                $content = "更新报表抽水数据失败ID({$rebateDate['id']})：".json_encode($data);
                $this->report_model->add_log($content);
                $this->return_json(E_ARGS,'更新失败');
            } else {
                $content = "更新报表抽水数据成功ID({$rebateDate['id']})：".json_encode($data);
                $this->report_model->add_log($content);
                $this->return_json(OK,['rebate'=>$pumping, 'total'=>$total]);
            }
        }
    }

    // 报表汇总
    public function total_report()
    {
        $start = $this->G('time_start') ? $this->G('time_start') : date('Y-m-01', strtotime(date("Y-m-d")));
        $end = $this->G('time_end') ? $this->G('time_end') :  date('Y-m-d', strtotime("$start +1 month -1 day"));
        $rs = $this->report_model->total_report($start, $end);
        $this->return_json(OK, $rs);
    }

    /**
     * 平台抽水支付API
     *
     * @access public
     * @return json
     
    public function pumppay()
    {
        $id = (int)$this->P('rid');
        if ($id < 1) {
            $this->return_json(E_ARGS, 'ID有小于0么？');
        }
        $this->report_model->select_db('private');
        $where['id'] = $id;
        $report = $this->report_model->get_one('*', 'rebate_report', $where);
        if (empty($report)) {
            $this->return_json(E_ARGS, '这个ID的数据是空的');
        }
        if ($report['pay_status'] == 1) {
            $this->return_json(E_ARGS, '已经完成支付了');
        }
        $start_month = date('Y-01-01');
        if ($report['start_date'] == $start_month) {
            $this->return_json(E_ARGS, '这个月还没有结束');
        }
        $data['pay_status'] = 1;
        $flag = $this->report_model->write('rebate_report', $data, $where);
        if (!$flag) {
            $content = "支付报表抽水数据失败ID{$id}";
            $this->report_model->add_log($content);
            $this->return_json(E_ARGS,'支付失败');
        } else {
            $content = "支付报表抽水数据成功ID{$id}";
            $this->report_model->add_log($content);
            $this->return_json(OK, '支付成功');
        }
    }
    */
    
    /**
     * @brief 每日已结/未结算统计接口
     * @access public/protected 
     * @param 
     * @return 
     */
    public function day_bets() /* {{{ */
    {
        $day = date('Ymd');
        $day2 = date('Ymd', time() - 86400);
        $data = [$day => [], $day2 => []];

        $bets_key = 'report:bets:'.$day;
        $stts_key = 'report:stts:'.$day;
        $bets_key2 = 'report:bets:'.$day2;
        $stts_key2 = 'report:stts:'.$day2;

        $this->report_model->select_redis_db(REDIS_LONG);
        $data[$day][] = (int) $this->report_model->redis_get($bets_key);
        $data[$day][] = (int) $this->report_model->redis_get($stts_key);
        $data[$day2][] = (int) $this->report_model->redis_get($bets_key2);
        $data[$day2][] = (int) $this->report_model->redis_get($stts_key2);

        $this->return_json(OK, $data);
    } /* }}} */

    /**
     * 获取月账期数
     * @access !protected 
     * @param
     * @return json
     */
    private function _months($start)
    {
        $time2 = time();
        $time1 = date('Y-m-01', strtotime($start)); // 自动为00:00:00 时分秒
        $time1 = strtotime($time1);

        $year2 = date('Y');
        $year1 = date('Y', $time1);
        if ($year2 - $year1 > 1) {
            $year2 -= 1;
            $time1 = strtotime("{$year2}-01-01");
        }
        do {
            $firstday = date('Y-m-01', $time2);
            $lastday = date('Y-m-d', strtotime("$firstday +1 month -1 day"));
            $monarr[date('Y', $time2)][date('n', $time2)] = $firstday . '~' . $lastday;
        } while (($time2 = strtotime('-1 month', $time2)) >= $time1);
        return $monarr;
    }

    public function home()
    {
        $this->report_model->select_db('private');
        $start = $this->P('start');
        $end = $this->P('end');
        if (empty($start) || empty($end)) {
            $this->return_json(E_ARGS,'参数错误');
        }
        $start_stamp = strtotime($start);
        $end_stamp = strtotime($end . ' +1 day');
        // 提现金额、提现笔数、活动礼金、充值笔数、提现笔数、人工提出金额、拒绝金额、
        $cash_report = $this->report_model->db
            ->select('SUM(in_company_total+in_online_total+in_people_total) as charge_money,SUM(in_company_num+in_online_num+in_people_num) as charge_num,SUM(out_company_total+out_people_total) as withdraw_money,SUM(out_people_total) as out_people_money,SUM(out_company_num+out_people_num) as withdraw_num,SUM(in_company_discount+in_online_discount+in_people_discount+in_register_discount+activity_total) as activity_money,SUM(in_member_out_deduction) as refuse_money')
            ->where('report_date >=',$start)->where('report_date <=',$end)
            ->from('cash_report')->get()->row_array();
        // 充值人数
        $charge_user = $this->report_model->db
            ->select('COUNT(DISTINCT uid) as charge_user_num')
            ->where('(in_company_total+in_online_total+in_people_total) > 0')
            ->where('report_date >=',$start)->where('report_date <=',$end)
            ->from('cash_report')->get()->row_array();
        // 提现人数
        $withdraw_user = $this->report_model->db
            ->select('COUNT(DISTINCT uid) as withdraw_user_num')
            ->where('(out_company_total+out_people_total) > 0')
            ->where('report_date >=',$start)
            ->where('report_date <=',$end)
            ->from('cash_report')->get()->row_array();
        // 活动人数
        $activity_user = $this->report_model->db
            ->select('COUNT(DISTINCT uid) as activity_user_num')
            ->where('(in_company_discount+in_online_discount+in_people_discount+in_register_discount+activity_total) > 0')
            ->where('report_date >=',$start)->where('report_date <=',$end)
            ->from('cash_report')->get()->row_array();
        // 注册人数、首冲人数
        $register_user = $this->report_model->db
            ->select('count(id) as register_num')
            ->where('addtime >=',$start_stamp)->where('addtime <',$end_stamp)
            ->from('user')->get()->row_array();
        $first_charge_user = $this->report_model->db
            ->select('count(id) as first_charge_num')
            ->where('first_time >=',$start_stamp)->where('first_time <',$end_stamp)
            ->from('user')->get()->row_array();
        // 返点金额、返点笔数、投注单量、投注金额、中奖金额
        $game_report = $this->report_model->db
            ->select('SUM(valid_price) as bet_money,SUM(bets_num) as bet_num,SUM(lucky_price) as prize_money,SUM(num_win) as prize_num,SUM(return_price) as rebate_money,SUM(num_return) as rebate_num')
            ->where('report_date >=',$start)
            ->where('report_date <=',$end)
            ->from('report')->get()->row_array();
        // 反水人数
        $rebate_user = $this->report_model->db
            ->select('COUNT(DISTINCT uid) as rebate_user_num')
            ->where('return_price > 0')
            ->where('report_date >=',$start)
            ->where('report_date <=',$end)
            ->from('report')->get()->row_array();
        // 撤单笔数
        $cancel_user = $this->report_model->db
            ->select('SUM(price_sum) as cancel_money,COUNT(id) as cancel_num')
            ->where('created >=',$start_stamp)
            ->where('created <=',$end_stamp)
            ->where('status',3)
            ->from('bet_wins')->get()->row_array();
        $res = array_merge($cash_report,$charge_user,$withdraw_user,$activity_user,$register_user,$first_charge_user,$game_report,$rebate_user,$cancel_user);
        $this->return_json(OK,$res);
    }
}

/* end file */

