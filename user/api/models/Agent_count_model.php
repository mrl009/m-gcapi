<?php
/**
 * @模块   demo
 * @版本   Version 1.0.0
 * @日期   2017-03-22
 * frank  退佣统计
 */
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Agent_count_model extends MY_Model
{
    public function __construct()
    {
        parent::__construct();
    }
    private $init_agent_report = [
        'bet_money' => 0.000,
        'rebate_money' => 0.000,
        'prize_money' => 0.000,
        'gift_money' => 0.000,
        'charge_money' => 0.000,
        'withdraw_money' => 0.000
    ];

    //获取对应的期数id
    public function get_agnet_month()
    {
        $this->select_db('public');
        $date = date('Y-m',strtotime("-1 day"));
        $data =  $this->get_one('','agent_month',['repor_date'=>$date]);
        $this->select_db('private');
        return $data;
    }
    //获取对应的期数id
    public function get_agnet_day()
    {
        $this->select_db('public');
        $date = date('Y-m-d',strtotime("-1 day"));
        $data = $this->get_one('','agent_day',['repor_date'=>$date]);
        $this->select_db('private');
        return $data;
    }


    public function day_count()
    {
        $kithe_id = 1;
        /*$kithe_id = $this->get_agnet_day();
        $kithe_id = $kithe_id['id'];*/
        $date = date('Y-m-d',strtotime("-1 day"));
        $keys = 'agent:count_'.$date;

        $sql = "UPDATE gc_report  a ,
                       gc_user b  
                 SET  a.agent_id = b.agent_id
                where a.uid=b.id AND  a.report_date='".date('Y-m-d')."' and b.agent_id>0";

        //先更新彩票报表的数据
        $this->db->query($sql);
        //更新流水
        $sql = "UPDATE gc_cash_list  a ,
                       gc_user b
                 SET  a.agent_id = b.agent_id
                where a.uid=b.id AND  a.addtime > '".strtotime(date('Y-m-d'))."' and b.agent_id>0 AND a.agent_Id=0";
        $this->db->query($sql);
        $bool = $this->redis_setnx($keys,1);
        if (!$bool) {
            die('已近处理过');
        }
        $this->redis_expire($keys,24*3600+20);

        $sql = "UPDATE gc_report  a ,
                       gc_user b
                 SET  a.agent_id = b.agent_id
                where a.uid=b.id AND  a.report_date='{$date}' and b.agent_id>0";

        //先更新彩票报表的数据
        $this->db->query($sql);

        $sql2 = "(select  COUNT(DISTINCT CASE WHEN b.in_company_total + b.in_online_total + b.in_people_total + b.in_card_total > 0 THEN b.uid END) AS valid_user from gc_cash_report b where b.report_date = '{$date}' AND b.agent_id = a.agent_id) ";
        $str      ="$kithe_id kithe_id , a.report_date,a.agent_id,SUM(a.valid_price) now_price , $sql2 valid_user, 2 status, 2 report_type";
        $where    = [
            'a.report_date =' => $date,
            'a.agent_id >' => " 0",
        ];
        $where2['groupby'] = ['a.agent_id'];
        //$where2['join']    = 'cash_report';
        //$where2['on']      = 'a.uid = b.uid';
        $arr   = $this->get_all($str,'report',$where,$where2);
        $level = $this->level_agent();
        foreach ($arr as &$data) {
            foreach ($level as $value) {
                //$data['valid_user'] >= $value['bet_amount']
                if ($data['now_price'] >= $value['bet_amount']) {
                    $data['rate']  = $value['rate'];
                    $data['rate_price']  = $value['rate']*$data['now_price']/100;
                    break;
                }
            }
        }
        $sn = $this->core->sn;
        if (empty($arr)) {
            wlog(APPPATH.'logs/agent_report_'.$sn.date('Ym').'.log',$date.'数据为空');
            die('数据为空');
        }
        foreach ($arr as $value) {
            $bool = $this->write('agent_report',$value);
            if ($bool) {
                $str  = "$date 代理报表存入成功 id:{$value['agent_id']} ";
            }else{
                $str  = "$date 代理报表存入失败 id:{$value['agent_id']}";
            }
            wlog(APPPATH.'logs/agent_report_'.$sn.date('Ym').'.log',$str);

        }
    }

    public function level_agent()
    {
        return $this->core->get_all('','agent_level',['status'=>1],['orderby'=>[ 'bet_amount' =>'desc' ]]);

    }

}
