<?php
/**
 * Created by PhpStorm.
 * User: ssl
 * Date: 2017/7/5
 * Time: 18:05
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class Agent_count extends GC_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Agent_count_model', 'core');
    }

    /**
     * 退佣计算
    */
    public function qishu_count($dsn)
    {
        if (empty($dsn)) {
            echo "请传入站点sn";
            die;
        }
        $this->core->init($dsn);
        /**if (!is_cli()) {
            header('HTTP/1.1 405 fuck u!');
            $this->return_json(E_METHOD, 'method nonsupport!');
        }**/
        //$gcSet = $this->core->get_gcset();
        //返佣类型 1按交收总盈利模式2按非交收总盈利模式 3按打码量
        //按打码计算
        $this->day_count();

        /*switch ($gcSet['rate_type']) {
            case 1:
                $this->month_count(1);
                break;
            case 2:
                $this->month_count(2);
                break;
            case 3:
                $this->date_count();
                break;
        }*/
    }



    /**
     * 一日一期 按打码量计算
     */
    private function day_count()
    {
        $this->core->day_count();
    }


    /**
     * 一月一期  盈利模式
    */
    private function month_count($type)
    {
        $date       = '2017-06-01';// date('Y-m-d',strtotime("-1 date"));
        $shangMonth = date('Y-m-t', strtotime('-1 month'));
        $valid_user = 0 ;
       //交收模式
        if ($type==2) {
            $b = "a.lucky_price - a.return_price - a.valid_price - b.in_company_discount - b.in_online_discount - b.in_people_discount - b.in_card_total - b.in_register_discount";

        }else{
            $b = 'a.lucky_price';
        }
        $sql = "SELECT a.agent_id, a.report_date,
                    COUNT(DISTINCT CASE WHEN {$a1} > 0 THEN b.uid END) AS valid_user, SUM(CASE WHEN  {$b}  > 0 THEN {$b} END) AS now_price, 1 AS report_type
                FROM gc_report a
                  left  JOIN gc_cash_report b ON a.agent_id = b.agent_id
                WHERE a.report_date = '{$date}'
                    AND a.agent_id > 0
                GROUP BY a.agent_id";
        $sql .= "insert into  gc_agent_report (agnet_id,report_date,valid_user,now_price,report_type)".$sql;
        $this->core->db->query($sql);
        //判断月结
        if ($date === $shangMonth) {
            $arr = $this->month_user($date);
        }


    }



    /**
     * 月结计算 上月结存 有效人数
     * @param $arr array 昨天日结数据
    */
    private function month_user()
    {
        //有效会员人数条件
        $a1  = "b.in_company_total + b.in_online_total + b.in_people_total + b.in_card_total";

        $date       = date('Y-m-d',strtotime("-1 date"));
        $twoMonth = date('Y-m-t', strtotime('-2 month'));
        $twostart = date('Y-m-01', strtotime('-2 month'));
        $str      = <<<xxx
                   a.agnet_id,SUM(CASE WHEN status=3 THEN a.front_price END) front_price ,
                   SUM(CASE WHEN status=2 THEN a.now_price END) now_price,
                   count(distinct CASE WHEN $a1 >0 AND b.report_date >'2017-05-31' THEN b.uid END) user_sum,
                   count(distinct CASE WHEN $a1> 0 AND a.status = 3 THEN b.uid END) usera
xxx;
        $where    = [
             'a.report_date >=' => $twostart,
             'a.report_date <=' => $date,
             'a.agnet_id >' => " 0",
        ];
        $where2['groupby'] = ['a.agnet_id'];
        $where2['join']    = 'cash_report';
        $where2['on']      = 'a.agnet_id = b.agent_id';
        $arr      = $this->core->get_all($str,'agent_report',$where,$where2);
        $levelArr = $this->core->get_all('','agent_level',['status'=>1],['orderby'=>[ 'profit_amount' =>'desc' ]]);
        foreach ($arr as &$value) {
            $value['valid_user'] = $value['user_sum']+$value['usera'];
            unset($value['user_sum']);
            unset($value['usera']);
            foreach ($levelArr as $level) {
                if ($value['now_price']+$value['front_price'] >= $level['profit_amount'] && $value >=$level['user_sum']) {
                    $value['rate'] = $level['rate'];
                    $value['status'] = 2;
                }
            }
            if (!isset($value['rate'])) {
                $value['status'] = 3;
                $value['rate']   = 0;
            }
            $value['check_status'] = 2;
            $value['report_type']  = 1;
        }
        $kithe_id=1;
        //todo 数据计算完毕 只需要插入素数据库
        //$agnet_month = $this->core->get_agnet_month();
        //$kithe_id    = $agnet_month['id'];
        foreach ($arr as $k=>$v) {
            $v['kithe_id'] = $kithe_id;
            $v['report_date'] = date('Y-m');
            if ($v['status'] == 2) {
                //$this->core->
            } else {

            }
        }
    }

}
