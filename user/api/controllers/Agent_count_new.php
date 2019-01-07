<?php
/**
 * Created by PhpStorm.
 * User: ssl
 * Date: 2018/5/24
 * Time: 18:05
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class Agent_count_new extends GC_Controller
{
    public function __construct()
    {
        parent::__construct();
        if (!is_cli()) {
            header('HTTP/1.1 405 fuck u!');
            $this->return_json(E_METHOD, 'method nonsupport!');
        }
        $GLOBALS['memory_start'] = memory_get_usage();
        //打开报错，查看日志错误信息
        error_reporting(-1);
        ini_set('display_errors', 1);
        ini_set('memory_limit', '512M');
        $this->load->model('Agent_count_new_model', 'core');
    }

    public function __destruct()
    {
        $GLOBALS['memory_end'] = memory_get_usage();
        $GLOBALS['memory_max'] = memory_get_peak_usage();
        @wlog(APPPATH . "logs/agent_{$this->core->sn}_" . date('Ymd') . '.log', 'agent_count:memory_usage:' ."start:".$GLOBALS['memory_start']."---end:".$GLOBALS['memory_end'].",峰值:".$GLOBALS['memory_max']);
    }


    public function update_tree($dsn)
    {
        if (!is_cli()) {
            header('HTTP/1.1 405 fuck u!');
            $this->return_json(E_METHOD, 'method nonsupport!');
        }
        if (empty($dsn)) {
            echo "请传入站点sn";
            die;
        }
        $this->core->init($dsn);
        $last_id = @file_get_contents(APPPATH . "logs/last_update_agent_tree.log");//从上次更新tree的日志中提取
        $res = $this->core->get_list('id,agent_id','user',['id > '=>$last_id]);
        foreach ($res as $v) {
            if ($v['agent_id'] != 0) {
                $sql = "INSERT IGNORE INTO `gc_agent_tree`(ancestor,descendant) SELECT t.ancestor,{$v['id']}
    FROM `gc_agent_tree` AS t WHERE t.descendant = {$v['agent_id']} UNION SELECT {$v['agent_id']},{$v['id']}";
                $flag = $this->core->db->query($sql);
                var_dump($flag);
            }
            @file_put_contents(APPPATH . "logs/last_update_agent_tree.log",$v['id']);
        }
    }

    /*
     * 初始化第二天的代理报表基本信息 凌晨0点前5分钟执行
     */
    public function init_tomorrow_report($dsn, $day = null)
    {
        $time = time();
        if (!is_cli()) {
            header('HTTP/1.1 405 fuck u!');
            $this->return_json(E_METHOD, 'method nonsupport!');
        }
        if (empty($dsn)) {
            echo "请传入站点sn";
            die;
        }
        $this->core->init($dsn);
        $this->core->select_db('privite');
        if (empty($day)) {
            $day = date('Y-m-d',strtotime('+1 day'));
        }
        $users = $this->core->get_list('uid','agent_line');
        $data = [
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
        $this->core->redis_pipeline();
        foreach ($users as $user){
            //$this->core->redis_del(TOKEN_CODE_AGENT .':report:'.$day.':'. $user['uid']);
            $this->core->redis_hmset(TOKEN_CODE_AGENT .':report:'.$day.':'. $user['uid'],$data);
            $this->core->redis_expire(TOKEN_CODE_AGENT .':report:'.$day.':'. $user['uid'],2*24*3600);
        }
        $this->core->redis_exec();
        wlog(APPPATH . "logs/agent_{$dsn}_" . date('Ymd') . '.log', 'init_tomorrow_report:' ."start:".date('Y-m-d H:i:s',$time)."---end:".date('Y-m-d H:i:s').",耗时:".(time()-$time));
    }

    public function del_2day_ago_redis_report($dsn, $day = null)
    {
        if (!is_cli()) {
            header('HTTP/1.1 405 fuck u!');
            $this->return_json(E_METHOD, 'method nonsupport!');
        }
        if (empty($dsn)) {
            echo "请传入站点sn";
            die;
        }
        $this->core->init($dsn);
        $this->core->select_db('privite');
        if (empty($day)) {
            $day = date('Y-m-d',strtotime('-2 day'));
        }
        $keys = $this->core->redis_keys(TOKEN_CODE_AGENT . ':report:' . $day . ':*');
        $this->core->redis_pipeline();
        foreach ($keys as $item) {
            $uid = substr($item,strrpos($item, ":")+1);
            $this->core->redis_del(TOKEN_CODE_AGENT . ':report:' . $day . ':' . $uid);
        }
        $this->core->redis_exec();
    }

    /*
     * 定时更新今日的代理报表
     */
    public function update_today_report($dsn)
    {
        $time = time();
        if (!is_cli()) {
            header('HTTP/1.1 405 fuck u!');
            $this->return_json(E_METHOD, 'method nonsupport!');
        }
        if (empty($dsn)) {
            echo "请传入站点sn";
            die;
        }
        @wlog(APPPATH . "logs/agent_{$dsn}_" . date('Ymd') . '.log', 'update_today_report:' ."初始化私库前:".date('Y-m-d H:i:s'));
        $this->core->init($dsn);
        $this->core->select_db('privite');
        @wlog(APPPATH . "logs/agent_{$dsn}_" . date('Ymd') . '.log', 'update_today_report:' ."初始化私库后:".date('Y-m-d H:i:s'));
        $start = @file_get_contents(APPPATH . "logs/{$dsn}_update_rebate_last_record.log");//上次执行的时间
        if (empty($start)) {
            $start = date('Y-m-d 00:00:00');
        } else {
            $start = date('Y-m-d 00:00:00',strtotime($start));
        }
        $flag = $this->core->analyze_statistic_rebate($start);
        @wlog(APPPATH . "logs/agent_{$dsn}_" . date('Ymd') . '.log', 'update_today_report:' ."更新反水写入流水后:".date('Y-m-d H:i:s'));
        //记录本次记录的时间,下次使用
        if ($flag) {
            file_put_contents(APPPATH . "logs/{$dsn}_update_rebate_last_record.log",date('Y-m-d H:i:s'));
        }
        $this->update_redis_report_new('',$start);
        @wlog(APPPATH . "logs/agent_{$dsn}_" . date('Ymd') . '.log', 'update_today_report:' ."start:".date('Y-m-d H:i:s',$time)."---end:".date('Y-m-d H:i:s').",耗时:".(time()-$time));
    }


    /**
     * 更新今日报表数据到redis
     * @param $day
     */
    public function update_redis_report_new($dsn='', $day)
    {
        $time = time();
        $today = date('Y-m-d',strtotime($day));
        $start = strtotime($today);
        $end = strtotime($today)+86400;

        if (!is_cli()) {
            header('HTTP/1.1 405 fuck u!');
            $this->return_json(E_METHOD, 'method nonsupport!');
        }
        if ($dsn) {
            $this->core->init($dsn);
            $this->core->select_db('privite');
            //$this->init_tomorrow_report($dsn,$today);
        }

        // 获取今日的数据 投注、中奖、反水
        $sql = "SELECT SUM(valid_price) as bet_money,SUM(lucky_price) as prize_money,SUM(return_price) as rebate_money,uid FROM gc_report WHERE report_date='{$today}' GROUP BY uid";
        $bet_prize_rebate = $this->core->db->query($sql)->result_array();

        // 获取今日的数据 提现、充值、礼金
        $sql = "SELECT SUM(in_company_total+in_online_total+in_people_total) as charge_money,SUM(out_company_total+out_people_total) as withdraw_money,SUM(in_company_discount+in_online_discount+in_people_discount+in_register_discount+activity_total) as gift_money,uid FROM gc_cash_report WHERE report_date='{$today}' GROUP BY uid;";
        $charge_withdraw_gift = $this->core->db->query($sql)->result_array();

        // 获取今日的数据 投注用户uid、新注册用户uid、首充用户uid
        // 新注册用户ids
        $new_register_ids = $this->core->db->select('id')->where(['addtime>='=>$start,'addtime<'=>$end])->get('user')->result_array();
        $new_register_ids = array_column($new_register_ids,'id');

        // 首充用户ids
        $fitst_change_ids = $this->core->db->select('id')->where(['first_time>='=>$start,'first_time<'=>$end])->get('user')->result_array();
        $fitst_change_ids = array_column($fitst_change_ids,'id');

        // 整合用户自己的 投注、中奖、反水、提现、充值、礼金
        $bet_prize_rebate = array_make_key($bet_prize_rebate,'uid');
        $charge_withdraw_gift = array_make_key($charge_withdraw_gift,'uid');

        // 有今日数据的uid
        $data_uids = array_unique(array_merge(array_keys($bet_prize_rebate),array_keys($charge_withdraw_gift),$new_register_ids));
        sort($data_uids);

        // 如果没有数据 退出
        if (empty($data_uids)) {
            @wlog(APPPATH . "logs/agent_{$this->core->sn}_" . date('Ymd') . '.log', 'update_redis_report:' ."没有数据");
            return false;
        }

        // 查找需要更新代理报表数据的uid
        $ids = $this->core->db->select('DISTINCT(ancestor) as uid')->from('agent_tree')->where_in('descendant',$data_uids,false)->get()->result_array();
        $ids = array_column($ids,'uid');
        $ids = array_unique(array_merge($data_uids,$ids));
        sort($ids);

        // 查找需要更新代理报表数据的用户的等级和父级代理
        $res = $this->core->db->select('a.uid,a.level,b.agent_id')->from('agent_line as a')->join('user as b','a.uid=b.id','inner')->where_in('a.uid',$ids,false)->get()->result_array();
        $ids = array_column($res,'uid');
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
        $today_data = [];
        $init_data = ['bet_money'=>0,'prize_money'=>0,'rebate_money'=>0,'charge_money'=>0,'withdraw_money'=>0,'gift_money'=>0];
        foreach ($data_uids as $uid) {
            if (!in_array($uid,$ids)) {
                //只更新代理线表中的user
                continue;
            }
            isset($bet_prize_rebate[$uid])?'':$bet_prize_rebate[$uid] = [];
            isset($charge_withdraw_gift[$uid])?'':$charge_withdraw_gift[$uid] = [];
            $today_data[$uid] = $bet_prize_rebate[$uid]+$charge_withdraw_gift[$uid]+$init_data;
        }
        unset($bet_prize_rebate,$charge_withdraw_gift,$ids,$data_uids);
        foreach ($arr as $level => $ids){
            foreach ($ids as $uid){
                $data = [
                    'uid'=>$uid,
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
                        $temp = isset($today_data[$junior_id])?$today_data[$junior_id]:[];
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
                $temp = isset($today_data[$uid])?$today_data[$uid]:[];
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
                $today_data[$uid] = $data;
            }
        }
        ksort($today_data);
        $this->core->redis_pipeline();
        foreach ($today_data as $uid => $data){
            $this->core->redis_hmset(TOKEN_CODE_AGENT .':report:'.$today.':'. $uid,$data);
        }
        $this->core->redis_exec();

        @wlog(APPPATH . "logs/agent_{$this->core->sn}_" . date('Ymd') . '.log', 'update_redis_report:' ."start:".date('Y-m-d H:i:s',$time)."---end:".date('Y-m-d H:i:s').",耗时:".(time()-$time));
    }


    /*
     * 更新昨日报表  new add at 2018-07-23 by wuya
     */
    public function yesterday_report_new($dsn,$day = null)
    {
        $time = time();
        if (!is_cli()) {
            header('HTTP/1.1 405 fuck u!');
            $this->return_json(E_METHOD, 'method nonsupport!');
        }
        if (empty($dsn)) {
            echo "请传入站点sn";
            die;
        }
        $this->core->init($dsn);
        $this->core->select_db('privite');
        $today = $day?$day:date('Y-m-d');
        if ($day) {
            $yesterday = $today;
            $today = date('Y-m-d',strtotime('+1 day',strtotime($yesterday)));
        } else {
            $yesterday = date('Y-m-d', strtotime('-1 day',strtotime($today)));
        }
        $start = strtotime($yesterday);
        $end = strtotime($today);
        file_put_contents(APPPATH . "logs/{$dsn}_update_yesterday_report_".$yesterday.".log",date('Y-m-d H:i:s'));

        // 获取今日的数据 投注、中奖、反水
        $sql = "SELECT SUM(valid_price) as bet_money,SUM(lucky_price) as prize_money,SUM(return_price) as rebate_money,uid FROM gc_report WHERE report_date='{$yesterday}' GROUP BY uid";
        $bet_prize_rebate = $this->core->db->query($sql)->result_array();
        // 获取今日的数据 提现、充值、礼金
        $sql = "SELECT SUM(in_company_total+in_online_total+in_people_total) as charge_money,SUM(out_company_total+out_people_total) as withdraw_money,SUM(in_company_discount+in_online_discount+in_people_discount+in_register_discount+activity_total) as gift_money,uid FROM gc_cash_report WHERE report_date='{$yesterday}' GROUP BY uid;";
        $charge_withdraw_gift = $this->core->db->query($sql)->result_array();
        // 获取今日的数据 投注用户uid、新注册用户uid、首充用户uid
        // 新注册用户ids
        $new_register_ids = $this->core->db->select('id')->where(['addtime>='=>$start,'addtime<'=>$end])->get('user')->result_array();
        $new_register_ids = array_column($new_register_ids,'id');
        // 首充用户ids
        $fitst_change_ids = $this->core->db->select('id')->where(['first_time>='=>$start,'first_time<'=>$end])->get('user')->result_array();
        $fitst_change_ids = array_column($fitst_change_ids,'id');
        // 整合用户自己的 投注、中奖、反水、提现、充值、礼金
        $bet_prize_rebate = array_make_key($bet_prize_rebate,'uid');
        $charge_withdraw_gift = array_make_key($charge_withdraw_gift,'uid');

        // 昨日有数据的uid
        $data_uids = array_unique(array_merge(array_keys($bet_prize_rebate),array_keys($charge_withdraw_gift),$new_register_ids));
        sort($data_uids);

        // 如果没有数据 退出
        if (empty($data_uids)) {
            @wlog(APPPATH . "logs/agent_{$this->core->sn}_" . date('Ymd') . '.log', 'update_yesterday_report:' ."没有数据");
            return false;
        }

        // 查找需要更新代理报表数据的uid
        $ids = $this->core->db->select('DISTINCT(ancestor) as uid')->from('agent_tree')->where_in('descendant',$data_uids,false)->get()->result_array();
        $ids = array_column($ids,'uid');
        $ids = array_unique(array_merge($data_uids,$ids));
        sort($ids);

        // 查找需要更新代理报表数据的用户的等级和父级代理
        $res = $this->core->db->select('a.uid,a.level,b.agent_id')->from('agent_line as a')->join('user as b','a.uid=b.id','inner')->where_in('a.uid',$ids,false)->get()->result_array();
        $ids = array_column($res,'uid');
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

        $yesterday_data = [];
        $init_data = ['bet_money'=>0,'prize_money'=>0,'rebate_money'=>0,'charge_money'=>0,'withdraw_money'=>0,'gift_money'=>0];
        foreach ($data_uids as $uid) {
            if (!in_array($uid,$ids)) {
                //只更新代理线表中的user
                continue;
            }
            isset($bet_prize_rebate[$uid])?'':$bet_prize_rebate[$uid] = [];
            isset($charge_withdraw_gift[$uid])?'':$charge_withdraw_gift[$uid] = [];
            $yesterday_data[$uid] = $bet_prize_rebate[$uid]+$charge_withdraw_gift[$uid]+$init_data;
        }
        unset($bet_prize_rebate,$charge_withdraw_gift,$ids,$data_uids);
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
                        $temp = isset($yesterday_data[$junior_id])?$yesterday_data[$junior_id]:[];
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
                $temp = isset($yesterday_data[$uid])?$yesterday_data[$uid]:[];
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
                $yesterday_data[$uid] = $data;
            }
        }
        ksort($yesterday_data);
        $this->core->db->trans_start();
        $i = 0;
        foreach ($yesterday_data as $uid => $info) {
            $i++;
            $report['agent_id'] = $uid;
            $report['report_date'] = $yesterday;
            $report['bet_money'] = $info['bet_money_sum'];
            $report['prize_money'] = $info['prize_money_sum'];
            $report['gift_money'] = $info['gift_money_sum'];
            $report['team_rebates'] = $info['rebate_money_sum'];
            $report['team_profit'] = round(floatval( $info['prize_money_sum']+$info['gift_money_sum']+$info['rebate_money_sum']-$info['bet_money_sum']),3);
            $report['bet_num'] = $info['bet_num'];
            $report['register_num'] = $info['register_num'];
            $report['first_charge_num'] = $info['first_charge_num'];
            $report['charge_money'] = $info['charge_money_sum'];
            $report['withdraw_money'] = abs($info['withdraw_money_sum']);
            $report['agent_rebates'] = $info['self_rebate_money'];
            $sql = $this->core->db->insert_string('agent_report_day', $report);
            $sql .= " ON DUPLICATE KEY UPDATE bet_money={$report['bet_money']},prize_money={$report['prize_money']},gift_money={$report['gift_money']},team_rebates={$report['team_rebates']},team_profit={$report['team_profit']},bet_num={$report['bet_num']},register_num={$report['register_num']},first_charge_num={$report['first_charge_num']},charge_money={$report['charge_money']},withdraw_money={$report['withdraw_money']},agent_rebates={$report['agent_rebates']}";
            $flag = $this->core->db->query($sql);
            if ($i%5000==0) {
                $this->core->db->trans_complete();
                $this->core->db->trans_start();
            }
            if (!$flag) {
                @wlog(APPPATH . "logs/agent_{$dsn}_" . date('Ymd') . '.log', 'yesterday_report:failed:' ."uid:".$uid.",start:".date('Y-m-d H:i:s',$start)."---end:".date('Y-m-d H:i:s',$end).",info:".json_encode($report).",sql:".$sql);
            }
        }
        $this->core->db->trans_complete();
        unlink(APPPATH . "logs/{$dsn}_update_yesterday_report_".$yesterday.".log");
        @wlog(APPPATH . "logs/agent_{$dsn}_" . date('Ymd') . '.log', 'update_yesterday_report:' ."start:".date('Y-m-d H:i:s',$start)."---end:".date('Y-m-d H:i:s',$end).",共". count($yesterday_data) ."条数据,耗时:".(time()-$time));
    }


    /*
     * 更新月度报表 new add at 2018-07-23 by wuya
     */
    public function month_report_new($dsn,$day = null)
    {
        $time = time();
        if (!is_cli()) {
            header('HTTP/1.1 405 fuck u!');
            $this->return_json(E_METHOD, 'method nonsupport!');
        }
        if (empty($dsn)) {
            echo "请传入站点sn";
            die;
        }
        $today = $day?$day:date('Y-m-d');

        $this->core->init($dsn);
        $this->core->select_db('privite');
        if ($day) {
            $report_day = $day;
        } else {
            $report_day = $this->core->get_one('max(report_date) as date','agent_report_day');
            $report_day = $report_day['date'];
        }
        $max_id = $this->core->db->from('user')->select_max('id')->get()->row_array();
        $max_id = $max_id['id'];
        $start = date('Y-m-01',strtotime($report_day));
        //当昨日报表正在更新的时候，不执行此脚本
        $n = 0;
        while (file_exists(APPPATH . "logs/{$dsn}_update_yesterday_report_".$report_day.".log")) {
            sleep(60);
            $n++;
            if ($n>5) {
                wlog(APPPATH . "logs/agent_{$dsn}_" . date('Ymd') . '.log', 'month_report: fail: yesterday_report_update进程非正常结束');
                die(date('Y-m-d H:i:s') . '  month_report: fail: yesterday_report_update进程非正常结束');
            }
        }
        $min = 0;
        $sql = "SELECT SUM(bet_money) as bet_money,SUM(prize_money) as prize_money,SUM(gift_money) as gift_money,SUM(team_rebates) as team_rebates,SUM(team_profit) as team_profit,SUM(register_num) as register_num,SUM(first_charge_num) as first_charge_num,SUM(charge_money) as charge_money,SUM(withdraw_money) as withdraw_money,SUM(agent_rebates) as agent_rebates,SUM(bet_num) as bet_num,agent_id FROM gc_agent_report_day WHERE report_date>='{$start}' AND report_date<='{$report_day}'";
        do {
            $max = $min+5000;
            $sql_str = $sql . " AND agent_id>{$min} AND agent_id<={$max} GROUP BY agent_id";
            $data = $this->core->db->query($sql_str)->result_array();
            if (count($data) == 0){
                $min += 5000;
                continue;
            }
            $this->core->db->trans_start();
            foreach ($data as $item) {
                $item['report_month'] = $report_day;
                if ('01' === date('d',strtotime($report_day))) {
                    $_sql = $this->core->db->insert_string('agent_report_month', $item);
                    $_sql .= " ON DUPLICATE KEY UPDATE bet_money={$item['bet_money']},prize_money={$item['prize_money']},gift_money={$item['gift_money']},team_rebates={$item['team_rebates']},team_profit={$item['team_profit']},register_num={$item['register_num']},bet_num={$item['bet_num']},first_charge_num={$item['first_charge_num']},charge_money={$item['charge_money']},withdraw_money={$item['withdraw_money']},agent_rebates={$item['agent_rebates']}";
                } else {
                    unset($item['bet_num']);
                    $_sql = $this->core->db->insert_string('agent_report_month', $item);
                    $_sql .= " ON DUPLICATE KEY UPDATE bet_money={$item['bet_money']},prize_money={$item['prize_money']},gift_money={$item['gift_money']},team_rebates={$item['team_rebates']},team_profit={$item['team_profit']},register_num={$item['register_num']},first_charge_num={$item['first_charge_num']},charge_money={$item['charge_money']},withdraw_money={$item['withdraw_money']},agent_rebates={$item['agent_rebates']}";
                }
                $flag = $this->core->db->query($_sql);
                if (!$flag) {
                    wlog(APPPATH . "logs/agent_{$dsn}_" . date('Ymd') . '.log', 'month_report:failed:' ."uid:".$item['agent_id'].",start:".date('Y-m-d H:i:s',strtotime($start))."---end:".date('Y-m-d H:i:s',strtotime($report_day)).",info:".json_encode($data).",sql:".$sql);
                }
            }
            $this->core->db->trans_complete();
            $min += 5000;
        }while($min <= $max_id);
        if ('01' !== date('d',strtotime($report_day))) {
            $this->core->update_bet_num($start,$report_day);
        }
        @wlog(APPPATH . "logs/agent_{$dsn}_" . date('Ymd') . '.log', 'update_month_report:' ."start:".date('Y-m-d H:i:s',strtotime($start))."---end:".date('Y-m-d H:i:s',strtotime($report_day)).",耗时:".(time()-$time));
    }

    /*
     * 代理报表统计 综合到一个入口中 每10min执行一次
     */
    public function index($dsn)
    {
        if (!is_cli()) {
            header('HTTP/1.1 405 fuck u!');
            $this->return_json(E_METHOD, 'method nonsupport!');
        }
        if (empty($dsn)) {
            echo "请传入站点sn";
            die;
        }
        $time = time();

        $this->update_today_report($dsn);

        if ($time >= strtotime(date('Y-m-d 23:49:00')) && $time <= strtotime(date('Y-m-d 23:55:00'))) {

            /************  23:50分 删除二天前的报表 ***********/
            //$this->init_tomorrow_report($dsn);
            $this->del_2day_ago_redis_report($dsn);

        } elseif ($time >= strtotime(date('Y-m-d 00:09:00')) && $time <= strtotime(date('Y-m-d 00:15:00'))) {

            /************  凌晨00:10分 每日加奖日结表统计 ***********/
            $this->reward_day_count($dsn);

        } elseif ($time >= strtotime(date('Y-m-d 00:19:00')) && $time <= strtotime(date('Y-m-d 00:25:00'))) {

            /************  凌晨00:20分 更新昨日报表 ***********/
            $this->yesterday_report_new($dsn);

        } elseif ($time >= strtotime(date('Y-m-d 00:29:00')) && $time <= strtotime(date('Y-m-d 00:35:00'))) {

            /************  凌晨00:30分 更新本月报表 ***********/
            $this->month_report_new($dsn);

        }

    }

    /*
     * 每日嘉奖统计
     */
    public function reward_day_count($dsn)
    {
        $time = time();
        if (!is_cli()) {
            header('HTTP/1.1 405 fuck u!');
            $this->return_json(E_METHOD, 'method nonsupport!');
        }
        if (empty($dsn)) {
            echo "请传入站点sn";
            die;
        }
        $this->load->model('Reward_day_model', 'reward');
        $this->reward->init($dsn);
        $this->reward->day_count($dsn);
        @wlog(APPPATH . "logs/agent_{$dsn}_" . date('Ymd') . '.log', 'reward_day_count:' ."start:".date('Y-m-d H:i:s',$time)."---end:".date('Y-m-d H:i:s').",耗时:".(time()-$time));
    }

    /*
     * 手工处理某天的报表
     */
    public function deal_day_report($dsn,$day)
    {
        //$this->update_redis_report($dsn,$day);
        $this->yesterday_report_new($dsn,$day);
        $this->month_report_new($dsn,$day);
    }

    /*
     * 调试查看redis数据
     */
    public function getredis($key, $dsn = 'w01')
    {
        if (!is_cli()) {
            header('HTTP/1.1 405 fuck u!');
            $this->return_json(E_METHOD, 'method nonsupport!');
        }
        if (empty($dsn)) {
            echo "请传入站点sn";
            die;
        }
        $this->core->init($dsn);

        $info1 = $this->core->redis_exists($key);
        $info = $this->core->redis_hgetall($key);

        echo $key ." exists?";var_dump($info);
        var_dump($info1);
        echo "\r\n ========================= \r\n";
    }

    public function test_redis()
    {  
        echo date('Y-m-d H:i:s')."start,init 私库\r\n";
        $this->core->init('w01');
        echo date('Y-m-d H:i:s')."私库初始化完毕\r\n";
        $this->core->select_db('privite');
        $this->core->select_redis_db(1);
        echo date('Y-m-d H:i:s')."选择redis1号库完毕\r\n";
        $this->core->redis_flushDB();
        echo date('Y-m-d H:i:s')."清空redis1号库完毕\r\n";
        $day = date('Y-m-d');
        //$users = $this->core->get_list('uid','agent_line');
        $users = $this->core->get_list('uid', 'agent_line');
        echo date('Y-m-d H:i:s')."查询agent_line表所有uid完毕\r\n";
        $data = [
            'bet_money' => 0.000,
            'rebate_money' => 0.000,
            'prize_money' => 0.000,
            'gift_money' => 0.000,
            'charge_money' => 0.000,
            'withdraw_money' => 0.000,
            'bet_money_sum' => 0.000,
            'prize_money_sum' => 0.000,
            'gift_money_sum' => 0.000,
            'rebate_money_sum' => 0.000,
            'bet_num' => 0,
            'register_num' => 0,
            'first_charge_num' => 0,
            'charge_money_sum' => 0.000,
            'withdraw_money_sum' => 0.000,
            'self_rebate_money' => 0.000,
        ];
        $time_start = time();    
        $num = count($users);
        echo date('Y-m-d H:i:s')." 初始化redis报表,共{$num}条记录\r\n";   
        foreach ($users as $k => $user) {
            if ($k%100) {
                //echo date('Y-m-d H:i:s')."第 ($k+1) 条记录\r\n";
            }
            $data['bet_money'] = rand(0,50) * 0.2;  
            $data['rebate_money'] = rand(0,9) * 0.01;
            $data['prize_money'] = rand(0,30) * rand(0,1);
            $data['gift_money'] = rand(0,30) * rand(0,1);
            $data['charge_money'] = rand(0,9) * rand(0,1);
            $data['withdraw_money'] = rand(0,6) * rand(0,1);
            $this->core->redis_del(TOKEN_CODE_AGENT . ':report:' . $day . ':' . $user['uid']);
            $this->core->redis_hmset(TOKEN_CODE_AGENT . ':report:' . $day . ':' . $user['uid'], $data);
            $this->core->redis_expire(TOKEN_CODE_AGENT . ':report:' . $day . ':' . $user['uid'], 3600);
        }
        $time_end = time(); 
        $num = count($users);
        echo date('Y-m-d H:i:s').' 初始化报表结束,用时'. ($time_end-$time_start) ."s,共{$num}条记录。\r\n"; die;
        $time_start = time();
        echo date('Y-m-d H:i:s')." 开始统计团队报表：\r\n";
        $this->core->update_team_huizong_money($day);
        $time_end = time();
        echo date('Y-m-d H:i:s').'更新团队redis报表用时：'.($time_end-$time_start)."\r\n";
    }

    public function test_mysql()
    {
        $this->core->init('w01');
        $this->core->select_db('privite');
        $users = $this->core->get_list('uid', 'agent_line');
        $time = time();
        echo date('Y-m-d H:i:s') ."\r\n";
        $this->core->db->trans_start();
        foreach ($users as $user) {
            $report['agent_id'] = $user['uid'];
            $report['report_date'] = date('Y-m-d',strtotime('+1 day'));
            $report['bet_money'] = rand(0,30);
            $report['prize_money'] = rand(0,30);
            $report['gift_money'] = rand(0,30);
            $report['team_rebates'] = rand(0,30);
            $report['team_profit'] = rand(0,30);
            $report['bet_num'] = rand(0,30);
            $report['register_num'] = rand(0,30);
            $report['first_charge_num'] = rand(0,30);
            $report['charge_money'] = rand(0,30);
            $report['withdraw_money'] = rand(0,30);
            $report['agent_rebates'] = rand(0,30);
            $sql = $this->core->db->insert_string('agent_report_day', $report);
            $sql .= " ON DUPLICATE KEY UPDATE bet_money={$report['bet_money']},prize_money={$report['prize_money']},gift_money={$report['gift_money']},team_rebates={$report['team_rebates']},team_profit={$report['team_profit']},bet_num={$report['bet_num']},register_num={$report['register_num']},first_charge_num={$report['first_charge_num']},charge_money={$report['charge_money']},withdraw_money={$report['withdraw_money']},agent_rebates={$report['agent_rebates']}";
            $flag = $this->core->db->query($sql);
            if ($user['uid']%5000==0) {
                $this->core->db->trans_complete();
                $this->core->db->trans_start();
            }
        }
        $this->core->db->trans_complete();
        echo date('Y-m-d H:i:s') ."\r\n";
        echo '用时：'. (time()-$time) .'s';

    }
}
