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

class Agent_count_new_model extends MY_Model
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
        'withdraw_money' => 0.000,
        'bet_money_sum'=>0.000,
        'prize_money_sum'=>0.000,
        'gift_money_sum'=>0.000,
        'rebate_money_sum'=>0.000,
        'bet_num'=>0,
        'register_num'=>0,
        'first_charge_num'=>0,
        'charge_money_sum'=>0.000,
        'withdraw_money_sum'=>0.000,
        'self_rebate_money'=>0.000,
    ];


    /*
     * 分析统计返水表信息，并生成返水，写入记录
     */
    public function analyze_statistic_rebate($start = null)
    {
        $start = empty($start) ? date('Y-m-d 00:00:00'):date('Y-m-d 00:00:00',strtotime($start));
        $end = date('Y-m-d 00:00:00',strtotime('+1 day',strtotime($start)));
        $today = date('Y-m-d',strtotime($start));
        try {
            // 删除 禁止反水的代理 的 记录
            $sql = "DELETE a FROM gc_agent_rebate a INNER JOIN gc_agent_line b ON a.uid=b.uid AND b.ban=1";
            $this->db->query($sql);

            if (date("G") < 2) {
                //每天0点到2点，执行删除8天前的返水
                $del_time = date("Y-m-d",strtotime('-8 day'));
                $del_flag_id = $this->db->from('agent_rebate')
                    ->where(['created < ' => $del_time])
                    ->select('id')
                    ->order_by('id','desc')
                    ->limit(1)
                    ->get()
                    ->result_array();
                $del_flag_id = $del_flag_id ? $del_flag_id[0]['id'] : 0;
                if ($del_flag_id) {
                    $this->db->where('id < ',$del_flag_id)->delete('agent_rebate');
                }
            }


            // 开始统计反水并写入账户余额
            $res = $this->db->from('agent_rebate')
                ->where('status = 1')
                ->where("updated >= '$start'")
                ->where("updated < '$end'")
                ->select('SUM(`price_rebate`) as rebate_money,COUNT(`id`) as rebate_num,uid,gid,MAX(updated) as end')
                ->group_by('uid,gid')
                ->get()->result_array();
//            $res = $this->db->from('agent_rebate')
//                ->where('status = 1')
//                ->where("updated >= '$start'")
//                ->where("updated < '$end'")
//                ->select('SUM(`price_rebate`) as rebate_money,COUNT(`id`) as rebate_num,GROUP_CONCAT(`order_num`) AS order_num,GROUP_CONCAT(`id`) AS ids,uid,gid')
//                ->group_by('uid,gid')
                //->get();
            //->get_compiled_select();

            foreach ($res as $item) {
                //根据uid gid 遍历 id、order_num
                $res1 = $this->db->from('agent_rebate')
                    ->where('status = 1')
                    ->where("updated >= '$start'")
                    ->where("updated <= '{$item['end']}'")
                    ->where("uid = ".$item['uid'])
                    ->where("gid = ".$item['gid'])
                    ->select('order_num,id')
                    ->get()->result_array();
                $ids = array_column($res1,'id');
                $order_num_arr = array_unique(array_column($res1,'order_num'));
                $order_num = implode(',',$order_num_arr);
                $order_num1 = array_splice($order_num_arr,0,10);
                $order_num1 = implode(',',$order_num1);

                $flag1 = $this->update_rebate_record_status($ids);
                //$uid,$gid,$today,$num_return,$return_price,$order_num
                $flag2 = $this->record_rebate_to_report($item['uid'], $item['gid'], $today, $item['rebate_num'], $item['rebate_money'],$order_num);
                if ($flag1 && $flag2) {
                    $data = ['uid'=>$item['uid'],'gid'=>$item['gid'],'rebate_money'=>$item['rebate_money'],'order_num'=>$order_num,'type'=>19,'remark'=>'代理退佣'];
                    $success =  $this->update_banlace($item['uid'], $item['rebate_money'], $order_num1, 19, '代理退傭');
                    if ( !$success ){
                        @wlog(APPPATH.'logs/'.$this->sn.'_agent_rebate_error_'.date('Ymd').'.log', "[analyze_statistic_rebate:update_banlace error]data:".json_encode($data));
                    } else {
                        @wlog(APPPATH.'logs/'.$this->sn.'_agent_rebate_success_'.date('Ymd').'.log', "[analyze_statistic_rebate:update_banlace success]data:".json_encode($data));
                    }
                }
            }
            $this->update_report_agent_id($start);
            return true;
        } catch (Exception $e) {
            wlog(APPPATH.'logs/'.$this->sn.'_agent_rebate_error_'.date('Ymd').'.log', 'analyze_statistic_rebate: fail:' ."start:$start---end:$end,msg:".$e->getMessage());
            return false;
        }
    }

    /**
     * 更新今日代理报表 每个用户自己的下注金额
     */
    public function update_bet_money($day)
    {
        $today = date('Y-m-d',strtotime($day));
        try {
            $res = $this->db->from('report as a')
                ->join('agent_line as b','a.uid=b.uid','inner')
                ->where("report_date = '$today'")
                ->where("valid_price > 0")
                ->select('SUM(`valid_price`) as bet_money,a.uid')
                ->group_by('a.uid')
                ->get()
                ->result_array();
            foreach ($res as $item) {
                $old = $this->redis_exists(TOKEN_CODE_AGENT . ':report:'.$today.':' . $item['uid']);
                if ($old === false) {
                    $this->redis_hmset(TOKEN_CODE_AGENT . ':report:' . $today . ':' . $item['uid'], $this->init_agent_report);
                    $this->redis_expire(TOKEN_CODE_AGENT . ':report:' . $today . ':' . $item['uid'], 2 * 24 * 3600);
                }
                $this->redis_hset(TOKEN_CODE_AGENT .':report:'.$today.':'. $item['uid'],'bet_money',$item['bet_money']);
            }

            wlog(APPPATH . "logs/agent_{$this->sn}_" . date('Ymd') . '.log', 'update_bet_money: success:' ."day:$today,num:".count($res));
        } catch (Exception $e) {
            wlog(APPPATH . "logs/agent_{$this->sn}_" . date('Ymd') . '.log', 'update_bet_money: fail:' ."day:$today,msg:".$e->getMessage());

        }

    }

    /**
     * 更新今日代理报表 每个用户自己的返佣金额
     */
    public function update_rebate_money($day)
    {
        $today = date('Y-m-d',strtotime($day));
        try {
            $res = $this->db->from('report as a')
                ->join('agent_line as b','a.uid=b.uid','inner')
                ->where("report_date = '$today'")
                ->where("return_price > 0")
                ->select('SUM(`return_price`) as rebate_money,a.uid')
                ->group_by('a.uid')
                ->get()
                ->result_array();
            foreach ($res as $item) {
                $old = $this->redis_exists(TOKEN_CODE_AGENT . ':report:'.$today.':' . $item['uid']);
                if ($old === false) {
                    $this->redis_hmset(TOKEN_CODE_AGENT . ':report:' . $today . ':' . $item['uid'], $this->init_agent_report);
                    $this->redis_expire(TOKEN_CODE_AGENT . ':report:' . $today . ':' . $item['uid'], 2 * 24 * 3600);
                }
                $this->redis_hset(TOKEN_CODE_AGENT .':report:'.$today.':'. $item['uid'],'rebate_money',$item['rebate_money']);
            }
            wlog(APPPATH . "logs/agent_{$this->sn}_" . date('Ymd') . '.log', 'update_rebate_money: success:' ."day:$today,num:".count($res));
        } catch (Exception $e) {
            wlog(APPPATH . "logs/agent_{$this->sn}_" . date('Ymd') . '.log', 'update_rebate_money: fail:' ."day:$today,msg:".$e->getMessage());

        }

    }

    /**
     * 更新今日代理报表 每个用户自己的中奖金额
     */
    public function update_prize_money($day)
    {
        $today = date('Y-m-d',strtotime($day));
        try {
            $res = $this->db->from('report as a')
                ->join('agent_line as b','a.uid=b.uid','inner')
                ->where("report_date = '$today'")
                ->where("lucky_price > 0")
                ->select('SUM(`lucky_price`) as prize_money,a.uid')
                ->group_by('a.uid')
                ->get()
                ->result_array();
            foreach ($res as $item) {
                $old = $this->redis_exists(TOKEN_CODE_AGENT . ':report:'.$today.':' . $item['uid']);
                if ($old === false) {
                    $this->redis_hmset(TOKEN_CODE_AGENT . ':report:' . $today . ':' . $item['uid'], $this->init_agent_report);
                    $this->redis_expire(TOKEN_CODE_AGENT . ':report:' . $today . ':' . $item['uid'], 2 * 24 * 3600);
                }
                $this->redis_hset(TOKEN_CODE_AGENT .':report:'.$today.':'. $item['uid'],'prize_money',$item['prize_money']);
            }
            wlog(APPPATH . "logs/agent_{$this->sn}_" . date('Ymd') . '.log', 'update_prize_money: success:' ."day:$today,num:".count($res));
        } catch (Exception $e) {
            wlog(APPPATH . "logs/agent_{$this->sn}_" . date('Ymd') . '.log', 'update_prize_money: fail:' ."day:$today,msg:".$e->getMessage());
        }

    }

    /**
     * 更新今日代理报表 每个用户自己的活动礼金
     */
    public function update_gift_money($day)
    {
        $today = date('Y-m-d',strtotime($day));
        $start = strtotime($today);
        $end = strtotime('+1 day',$start);
        try {
            $res = $this->db->from('cash_list as a')
                ->where('a.amount > 0')
                ->where_in('a.type', [11,20,22])
                ->where("a.addtime >= $start")
                ->where("a.addtime < $end")
                ->join("agent_line as b","a.uid=b.uid",'inner')
                ->select('SUM(a.amount) as gift_money,a.uid')
                ->group_by('a.uid')
                ->get();
            //->get_compiled_select();
            $res = $res->result_array();
            foreach ($res as $item) {
                $old = $this->redis_exists(TOKEN_CODE_AGENT . ':report:'.$today.':' . $item['uid']);
                if ($old === false) {
                    $this->redis_hmset(TOKEN_CODE_AGENT . ':report:' . $today . ':' . $item['uid'], $this->init_agent_report);
                    $this->redis_expire(TOKEN_CODE_AGENT . ':report:' . $today . ':' . $item['uid'], 2 * 24 * 3600);
                }
                $this->redis_hset(TOKEN_CODE_AGENT .':report:'.$today.':'. $item['uid'],'gift_money',$item['gift_money']);
            }
            wlog(APPPATH . "logs/agent_{$this->sn}_" . date('Ymd') . '.log', 'update_gift_money: success:' ."day:$today,num:".count($res));
        } catch (Exception $e) {
            wlog(APPPATH . "logs/agent_{$this->sn}_" . date('Ymd') . '.log', 'update_gift_money: fail:' ."day:$today,msg:".$e->getMessage());
        }

    }

    /**
     * 更新今日代理报表 每个用户自己的充值金额
     */
    public function update_charge_money($day)
    {
        $today = date('Y-m-d',strtotime($day));
        $start = strtotime($today);
        $end = strtotime('+1 day',$start);
        try {
            $res = $this->db->from('cash_list as a')
                ->where('a.amount > 0')
                ->where_in('a.type', [5,6,7,8,12])
                ->where("a.addtime >= $start")
                ->where("a.addtime < $end")
                ->join("agent_line as b","a.uid=b.uid",'inner')
                ->select('SUM(a.amount) as charge_money,a.uid')
                ->group_by('a.uid')
                ->get();
            //->get_compiled_select();
            $res = $res->result_array();
            foreach ($res as $item) {
                $old = $this->redis_exists(TOKEN_CODE_AGENT . ':report:'.$today.':' . $item['uid']);
                if ($old === false) {
                    $this->redis_hmset(TOKEN_CODE_AGENT . ':report:' . $today . ':' . $item['uid'], $this->init_agent_report);
                    $this->redis_expire(TOKEN_CODE_AGENT . ':report:' . $today . ':' . $item['uid'], 2 * 24 * 3600);
                }
                $this->redis_hset(TOKEN_CODE_AGENT .':report:'.$today.':'. $item['uid'],'charge_money',$item['charge_money']);
            }
            wlog(APPPATH . "logs/agent_{$this->sn}_" . date('Ymd') . '.log', 'update_charge_money: success:' ."day:$today,num:".count($res));
        } catch (Exception $e) {
            wlog(APPPATH . "logs/agent_{$this->sn}_" . date('Ymd') . '.log', 'update_charge_money: fail:' ."day:$today,msg:".$e->getMessage());
        }

    }

    /**
     * 更新今日代理报表 每个用户自己的提现金额
     */
    public function update_withdraw_money($day)
    {
        $today = date('Y-m-d',strtotime($day));
        $start = strtotime($today);
        $end = strtotime('+1 day',$start);
        try {
            $sql = "SELECT SUM(`withdraw_money`) as withdraw_money,uid FROM  ((SELECT `actual_price` AS withdraw_money,a.`uid` FROM `gc_cash_out_manage` a INNER JOIN gc_agent_line b ON a.uid=b.uid WHERE a.`status` = 2 AND a.`updated` >= {$start} AND a.`updated` < {$end} ) UNION ALL (SELECT `price` AS withdraw_money,c.`uid` FROM `gc_cash_out_people` c INNER JOIN `gc_agent_line` d ON c.`uid`=d.`uid` WHERE c.`type` = 4 AND c.`addtime` >= {$start} AND c.`addtime` < {$end} )) as tmp_tb GROUP BY uid";
            $res = $this->db->query($sql)->result_array();
            foreach ($res as $item) {
                $old = $this->redis_exists(TOKEN_CODE_AGENT . ':report:'.$today.':' . $item['uid']);
                if ($old === false) {
                    $this->redis_hmset(TOKEN_CODE_AGENT . ':report:' . $today . ':' . $item['uid'], $this->init_agent_report);
                    $this->redis_expire(TOKEN_CODE_AGENT . ':report:' . $today . ':' . $item['uid'], 2 * 24 * 3600);
                }
                $this->redis_hset(TOKEN_CODE_AGENT .':report:'.$today.':'. $item['uid'],'withdraw_money',abs($item['withdraw_money']));
            }
            wlog(APPPATH . "logs/agent_{$this->sn}_" . date('Ymd') . '.log', 'update_withdraw_money: success:' ."day:$today,num:".count($res));
        } catch (Exception $e) {
            wlog(APPPATH . "logs/agent_{$this->sn}_" . date('Ymd') . '.log', 'update_withdraw_money: fail:' ."day:$today,msg:".$e->getMessage());
        }
    }

    /**
     * 更新每日报表代理团队汇总数据
     */
    public function update_team_huizong_money($day)
    {
        $today = date('Y-m-d',strtotime($day));
        $start = strtotime($today);
        $end = strtotime('+1 day',$start);
        $res = $this->db->select('a.uid,a.level,b.agent_id')->from('agent_line as a')->join('user as b','a.uid=b.id','inner')->get()->result_array();
        $arr = [];
        $agents_arr = [];
        foreach ($res as $item) {
            if (isset($arr["{$item['level']}"])) {
                array_push($arr["{$item['level']}"],$item['uid']);
            } else {
                $arr["{$item['level']}"][] = $item['uid'];
            }
            if (isset($agents_arr[$item['agent_id']])) {
                $agents_arr[$item['agent_id']][] = $item['uid'];
            } else {
                $agents_arr[$item['agent_id']] = [$item['uid']];
            }
        }
        krsort($arr);//将金字塔无限代理按照层级逆序排列，等级靠后得排前面
        // 获取新注册用户ids
        $new_register_ids = $this->db->select('id')->where(['addtime>='=>$start,'addtime<'=>$end])->get('user')->result_array();
        $new_register_ids = array_column($new_register_ids,'id');
        // 获取首充用户ids
        $fitst_change_ids = $this->db->select('id')->where(['first_time>='=>$start,'first_time<'=>$end])->get('user')->result_array();
        $fitst_change_ids = array_column($fitst_change_ids,'id');
        wlog(APPPATH . "logs/agent_{$this->sn}_" . date('Ymd') . '.log', '' ."update_team_huizong_money开始计算并写入redis:".date('Y-m-d H:i:s'));
        foreach ($arr as $level => $ids){
            foreach ($ids as $uid){
                $data = [
                    'bet_money_sum'=>0.000,
                    'prize_money_sum'=>0.000,
                    'gift_money_sum'=>0.000,
                    'rebate_money_sum'=>0.000,
                    'bet_num'=>0,
                    'register_num'=>0,
                    'first_charge_num'=>0,
                    'charge_money_sum'=>0.000,
                    'withdraw_money_sum'=>0.000,
                    'self_rebate_money'=>0.000,
                    //'agent_salary'=>0.000,
                    //'agent_fenhong'=>0.000
                ];
                if (in_array($uid,$new_register_ids)) {
                    $data['register_num'] = 1;
                }
                if (in_array($uid,$fitst_change_ids)) {
                    $data['first_charge_num'] = 1;
                }
                //找当前代理的直属下级
                $junior_ids = isset($agents_arr[$uid])?$agents_arr[$uid]:[];
                if (!empty($junior_ids)) {
                    foreach ($junior_ids as $junior_id) {
                        $temp = $this->redis_hgetall(TOKEN_CODE_AGENT .':report:'.$today.':'. $junior_id);
                        if (empty($temp)) {
                            continue;
                        }
                        $data['bet_money_sum'] += isset($temp['bet_money_sum'])?$temp['bet_money_sum']:0;
                        $data['prize_money_sum'] += isset($temp['prize_money_sum'])?$temp['prize_money_sum']:0;
                        $data['gift_money_sum'] += isset($temp['gift_money_sum'])?$temp['gift_money_sum']:0;
                        $data['rebate_money_sum'] += isset($temp['rebate_money_sum'])?$temp['rebate_money_sum']:0;
                        $data['charge_money_sum'] += isset($temp['charge_money_sum'])?$temp['charge_money_sum']:0;
                        $data['withdraw_money_sum'] += isset($temp['withdraw_money_sum'])?abs($temp['withdraw_money_sum']):0;
                        $data['register_num'] += isset($temp['register_num'])?$temp['register_num']:0;
                        $data['first_charge_num'] += isset($temp['first_charge_num'])?$temp['first_charge_num']:0;
                        $data['bet_num'] += isset($temp['bet_num'])?$temp['bet_num']:0;
                    }
                }
                $temp = $this->redis_hgetall(TOKEN_CODE_AGENT .':report:'.$today.':'. $uid);
                if (!empty($temp)) {
                    $data['bet_money_sum'] += isset($temp['bet_money'])?$temp['bet_money']:0;
                    $data['prize_money_sum'] += isset($temp['prize_money'])?$temp['prize_money']:0;
                    $data['gift_money_sum'] += isset($temp['gift_money'])?$temp['gift_money']:0;
                    $data['rebate_money_sum'] += isset($temp['rebate_money'])?$temp['rebate_money']:0;
                    $data['charge_money_sum'] += isset($temp['charge_money'])?$temp['charge_money']:0;
                    $data['withdraw_money_sum'] += isset($temp['withdraw_money'])?abs($temp['withdraw_money']):0;
                    $data['self_rebate_money'] = isset($temp['rebate_money'])?$temp['rebate_money']:0;
                    if (isset($temp['bet_money']) && $temp['bet_money'] > 0) {
                        $data['bet_num'] += 1;
                    }
                }
                $flag = $data['bet_money_sum']+$data['gift_money_sum']+$data['charge_money_sum']+$data['withdraw_money_sum']+$data['register_num'];
                if ($flag>0) {
                    // 有数据才写入
                    $this->redis_hmset(TOKEN_CODE_AGENT .':report:'.$today.':'. $uid,$data);
                }

            }
        }
    }

    /*
     * 更新每日代理报表首充人数
     */
    public function update_first_charge_num($start = null,$end = null)
    {
        empty($start)?$start = strtotime(date('Y-m-d',strtotime('-1 day'))):'';
        empty($end)?$end = strtotime(date('Y-m-d')):'';
        is_int($start)?'':$start = strtotime($start);
        is_int($end)?'':$end = strtotime($end);
        try{
            $first_charge_uids = $this->get_list('id as uid','user',['first_time >= '=>$start,'first_time < '=>$end]);
            $first_charge_uids = array_column($first_charge_uids,'uid');
            $arr = [];
            if (!empty($first_charge_uids)) {
                $arr = $this->get_list('count(descendant) as num,ancestor as agent_id','agent_tree',[],['wherein'=>['descendant'=>$first_charge_uids],'groupby'=>['ancestor']]);
            }
            $agent_ids = array_column($arr,'agent_id');
            foreach ($first_charge_uids as $uid){
                if (in_array($uid,$agent_ids)) {
                    $index = array_search($uid,$agent_ids);
                    $arr[$index]['num'] += 1;
                } else {
                    array_push($arr,['num'=>1,'agent_id'=>$uid]);
                }
            }
            foreach ($arr as $item) {
                $day = date('Y-m-d',$start);
                $data = ['first_charge_num'=>$item['num']];
                $where = ['agent_id'=>$item['agent_id'],'report_date'=>"$day"];
                $this->db->update('agent_report_day',$data,$where);
            }
            wlog(APPPATH . "logs/agent_{$this->sn}_" . date('Ymd') . '.log', 'yesterday_report:update_first_charge_num: success:' ."start:".date('Y-m-d H:i:s',$start)."---end:".date('Y-m-d H:i:s',$end).",num:".count($arr));
        } catch (Exception $e) {
            wlog(APPPATH . "logs/agent_{$this->sn}_" . date('Ymd') . '.log', 'yesterday_report:update_first_charge_num: fail:' ."start:".date('Y-m-d H:i:s',$start)."---end:".date('Y-m-d H:i:s',$end).",msg:".$e->getMessage());
        }
    }

    /*
     * 更新投注人数
     */
    public function update_bet_num($start = null,$end = null)
    {
        if (empty($start)) {
            if (date('d') === '01') {
                $start = date('Y-m-01',strtotime('-1 month'));
                $end = date('Y-m-d',strtotime('-1 day',strtotime(date('Y-m-01'))));
            } else {
                $start = date('Y-m-01');
                $end = date('Y-m-d');
            }
        }
        empty($end)?$end = date('Y-m-d'):'';
        is_int($start)?$start = date('Y-m-d',$start):'';
        is_int($end)?$end = date('Y-m-d',$end):'';
        try {
            //$ouids = $this->get_list('DISTINCT(uid)','report',['valid_price >'=>0,'report_date >= '=>$start,'report_date < '=>$end]);
            $ouids = $this->db->select('DISTINCT(a.uid)')
                ->from('report as a')
                ->join('agent_line as b','a.uid = b.uid','inner')
                ->where('report_date >= ',$start)
                ->where('report_date <= ',$end)
                ->where("valid_price > 0")
                ->get()
                ->result_array();
            $ouids = array_column($ouids,'uid');
            $arr = [];
            if (!empty($ouids)) {
                $uids = implode(',',$ouids);
                $sql = "SELECT count(`descendant`) AS num,`ancestor` AS agent_id FROM `gc_agent_tree` WHERE `descendant` IN ($uids) GROUP BY `ancestor`";
                //$arr = $this->get_list('count(descendant) as num,ancestor as agent_id','agent_tree',[],['wherein'=>['descendant'=>$ouids],'groupby'=>['ancestor']]);
                $arr = $this->db->query($sql)->result_array();
            }
            $agent_ids = array_column($arr,'agent_id');
            foreach ($ouids as $uid){
                if (in_array($uid,$agent_ids)) {
                    $index = array_search($uid,$agent_ids);
                    $arr[$index]['num'] += 1;
                } else {
                    array_push($arr,['num'=>1,'agent_id'=>$uid]);
                }
            }
            $table = 'agent_report_month';
            $day = $end;
            $where = ['report_month'=>$day];
            $this->db->trans_start();
            $i = 0;
            foreach ($arr as $item) {
                $i++;
                $data['bet_num'] = $item['num'];
                $where['agent_id'] = $item['agent_id'];
                $this->db->update($table,$data,$where);
                if ($i%5000==0) {
                    $this->db->trans_complete();
                    $this->db->trans_start();
                }
            }
            $this->db->trans_complete();
            wlog(APPPATH . "logs/agent_{$this->sn}_" . date('Ymd') . '.log', 'month_report:update_bet_num: success:' ."start:".date('Y-m-d H:i:s',strtotime($start))."---end:".date('Y-m-d H:i:s',strtotime($end)).",num:".count($arr));
        } catch (Exception $e) {
            wlog(APPPATH . "logs/agent_{$this->sn}_" . date('Ymd') . '.log', 'month_report:update_bet_num: fail:' ."start:".date('Y-m-d H:i:s',strtotime($start))."---end:".date('Y-m-d H:i:s',strtotime($end)).",msg:".$e->getMessage());
        }
    }

    /*
     * 更新agent_rebate表的记录状态
     */
    public function update_rebate_record_status($ids)
    {
        if (empty($ids)) {
            return true;
        }
        if (is_string($ids)) {
            $ids = explode(',',$ids);
        }
        $data = ['status'=>2,'updated'=>date('Y-m-d H:i:s')];
        $flag = $this->db->where_in('id',$ids)->update('agent_rebate',$data);
        if ($flag) {
            @wlog(APPPATH.'logs/'.$this->sn.'_agent_rebate_success_'.date('Ymd').'.log', "[analyze_statistic_rebate:update_rebate_record_status_success]修改返水表状态: ".implode(",", $ids)."修改:".json_encode($data));
            return true;
        } else {
            @wlog(APPPATH.'logs/'.$this->sn.'_agent_rebate_error_'.date('Ym').'.log', "[analyze_statistic_rebate:update_rebate_record_status_error]修改返水表状态".implode(",", $ids)."修改:".json_encode($data));
            return false;
        }
    }

    /*
     * 记录返水统计到gc_report表
     */
    public function record_rebate_to_report($uid,$gid,$today,$num_return,$return_price,$order_nums)
    {
        $v = [
            'gid' => $gid,
            'uid' => $uid,
            'report_date'=>$today,
            'num_return' => $num_return,
            'return_price' => $return_price,
            'report_time' => time()
        ];
        $sql = $this->db->insert_string('report', $v);
        $sql .= " ON DUPLICATE KEY UPDATE num_return=num_return+{$v['num_return']},return_price=return_price+{$v['return_price']},report_time='{$v['report_time']}'";
        $flag = $this->db->query($sql);
        $v['order_num'] = $order_nums;
        $v['update_time'] = date('Y-m-d H:i:s',$v['report_time']);
        if ($flag) {
            @wlog(APPPATH.'logs/'.$this->sn.'_agent_rebate_success_'.date('Ymd').'.log', "[analyze_statistic_rebate:record_rebate_to_report success]更新: ".json_encode($v));
            return true;
        } else {
            @wlog(APPPATH.'logs/'.$this->sn.'_agent_rebate_error_'.date('Ym').'.log', "[analyze_statistic_rebate:record_rebate_to_report error]更新".json_encode($v));
            return false;
        }
    }

    /*
     * 更新gc_report表的agent_id,方便后台查询统计
     */
    public function update_report_agent_id($start = null)
    {
        if (empty($start)) {
            $start = date('Y-m-d');
        }
        $start = strtotime($start);
        $sql = "UPDATE `gc_report` AS `a` INNER JOIN `gc_user` AS `b` ON `a`.`uid` = `b`.`id` AND `b`.`agent_id` <> 0 SET `a`.`agent_id` = `b`.`agent_id` WHERE `a`.`agent_id` = 0 AND `a`.`report_time` >= {$start}";
        $this->db->query($sql);
    }
}
