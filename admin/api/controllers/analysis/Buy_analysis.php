<?php
/**
 * @模块   会员分析／下注分析
 * @版本   Version 1.0.0
 * @日期   2017-03-30
 * shensiming
 */


defined('BASEPATH') or exit('No direct script access allowed');
class Buy_analysis extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('form/Report_model', 'core');
    }





    /******************公共方法*******************/
    /**
     * 下注分析页面数据
     */
    public function get_list()
    {
        //精确条件
        $basic = array(
            'b.agent_id' => (int)$this->G('agent_id'),
            'a.gid' => (int)$this->G('games'),
            //'a.report_time >=' => strtotime($this->G('time_start').' 00:00:00'),
            //'a.report_time <=' => strtotime($this->G('time_end').' 23:59:59'),
            'a.report_date >=' =>  $this->G('time_start')? $this->G('time_start'):date('Y-m-01', strtotime(date("Y-m-d"))),
            'a.report_date <=' => $this->G('time_end'),
            'a.num >' => 0.1
        );
        /*** 查询时间跨度不能超过两个月 ***/
        /*$diff_time = $basic['a.report_time <=']-$basic['a.report_time >='];
        if ($diff_time > ADMIN_QUERY_TIME_SPAN) {
            $basic['a.report_time <='] = $basic['a.report_time >=']+ADMIN_QUERY_TIME_SPAN;
        }*/
        $diff_time = strtotime($basic['a.report_date <=']) - strtotime($basic['a.report_date >=']);
        if ($diff_time > ADMIN_QUERY_TIME_SPAN) {
            $basic['a.report_date <='] = date('Y-m-d', strtotime($basic['a.report_date >=']) + ADMIN_QUERY_TIME_SPAN);
        }
        
        // 高级搜索
        $senior['join'] = 'user';
        $senior['on'] = 'a.uid=b.id';
        $username = $this->G('f_username');
        if (!empty($username)) {
            $this->core->select_db('private');
            $uid = $this->core->get_one('id', 'user', ['username'=>$username]);
            $basic['a.uid'] = empty($uid) ? '0' : $uid['id'];
            /*$basic['a.report_time >='] = null;
            $basic['a.report_time <='] = null;*/
        }
        // 分页
        $page = (int)$this->G('page') > 0 ?
                (int)$this->G('page') : 1;
        $rows = (int)$this->G('rows') > 0 ?
                (int)$this->G('rows') : 20;
        if ($rows > 500 || $rows < 1) {
            $rows = 50;
        }
        $page = array(
            'page'  => $page,
            'rows'  => $rows
        );
        // 排序
        $sort_field = array('total_price', 'valid_price',
                                'lucky_price', 'total_num',
                                'total_win_num');
        $order = $this->G('order');
        $sort = $this->G('sort');
        if (in_array($this->G('sort'), $sort_field)) {
            $senior['orderby'] = array($sort=>$order);
        }
        $data = $this->core->report_list($basic, $senior, $page);

        /* 计算 */
        foreach ($data['rows'] as $k => $v) {
            $data['rows'][$k]['total_lose_num'] = $v['total_num'] - $v['total_win_num'];
            if (!empty($v['total_num'])) {
                $data['rows'][$k]['win_lose_rate'] = (float)number_format(
                    $v['total_win_num'] / $v['total_num'] * 100, 2) . '%';
            } else {
                $data['rows'][$k]['win_lose_rate'] = '0%';
            }
            
            if ($data['rows'][$k]['win_lose_rate']>50) {
                $data['rows'][$k]['level'] = '高';
            } elseif ($data['rows'][$k]['win_lose_rate'] < 30) {
                $data['rows'][$k]['level'] = '低';
            } else {
                $data['rows'][$k]['level'] = '中';
            }
            $data['rows'][$k]['diff_price'] = (float)($v['lucky_price'] - $v['valid_price']);
            $data['rows'][$k] = $this->_format_float($data['rows'][$k]);
        }
        $data['footer']['diff_price'] = (float)($data['footer']['lucky_price'] - $data['footer']['valid_price']);
        $data['footer'] = $this->_format_float($data['footer']);

        /*** 排序 ***/
        if (!empty($data['rows']) && count($data['rows'])!==0 &&
            array_key_exists($sort, $data['rows'][0])) {
            foreach ($data['rows'] as $key => $value) {
                $tag1[] = (float)($value[$sort]);
            }
            $or = strtolower($order) == 'asc' ? SORT_ASC : SORT_DESC;
            array_multisort($tag1, $or, $data['rows']);
        }
        $footer = $data['footer'];
        unset($data['footer']);
        $data['footer'][] = $footer;
        /**** 格式化小数点 ****/
        // $history['rows'] = stript_float($history['rows']);
        $data = stript_float($data);
        $this->return_json(OK, $data);
    }
    /********************************************/






    /******************私有方法*******************/
    private function _format_float($floats)
    {
        $format_field = array('lucky_price', 'total_price',
                                'valid_price', 'diff_price');
        foreach ($floats as $k => $v) {
            if (in_array($k, $format_field)) {
                $floats[$k] = (float)sprintf("%.3f", $v);
            }
        }
        return $floats;
    }
    /********************************************/
}
