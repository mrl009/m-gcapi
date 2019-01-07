<?php

/**
 * Created by PhpStorm.
 * Date: 2018/4/22
 */
class Agent_line_model extends MY_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->select_db('private');
    }
    public function get_rebate($user_id)
    {
        try {
            $this->select_db('private');
            $where = [
                'uid' => $user_id,
                'type'=> 2
            ];
            $data = $this->get_one('line,level,rebate', 'agent_line', $where);
            return $data;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * 添加代理线数据
     * uid 新注册用户
     * code_data 邀请码信息 //为空时代表未使用邀请码
     */
    public function record($uid,$code_data = null)
    {
        if ($code_data != null) {
            //上级代理信息
            $upper = $this->get_one('line,rebate','agent_line',['uid'=>$code_data['uid']]);
            if (empty($upper)) {
                return false;
            }
            $data = [];
            $data['uid'] = $uid;
            $data['invite_code'] = $code_data['invite_code'];
            $data['type'] = $code_data['junior_type'];
            $data['rebate'] = $code_data['rebate'];
            $data['level'] = $code_data['level'];
            $upper['line'] = json_decode($upper['line'],true);
            //$upper['rebate'] = json_decode($upper['rebate'],true);

            $data['line'] = $upper['line'];
            $rebate = json_decode($code_data['rebate'],true);
            $data['line'][$uid] = $rebate;
            //$upper_uid = $code_data['uid'];
            //$upper_rebate = $upper['rebate'];
            // 代理线信息，返佣从上级开始计算，上级返水比率为上级返点和当前用户返点的差值
//            foreach ($rebate as $k => $v){
//                $data['line'][$upper_uid][$k] = round(floatval($upper_rebate[$k] - $v), 1);
//            }
            $data['line'] = json_encode($data['line']);
        } else {
            $data['uid'] = $uid;
            $data['type'] = 1;//todo 不通过邀请码注册进来的是代理还是玩家？
            $data['level'] = 1;
            $default_rebate = $this->core->get_gcset(['default_rebate']);
            $default_rebate = json_decode($default_rebate['default_rebate'],true);
            $data['rebate'] = json_encode($default_rebate);
            $data['line'] = json_encode([$uid=>$default_rebate]);
        }
        try {
            $this->write('agent_line',$data);
            if ($code_data != null) {
                //$this->init_today_agent_report($data['uid']);
            }
            return ['code' => 200];
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function init_today_agent_report($uid)
    {
        $day = date('Y-m-d');
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
        $this->redis_hmset(TOKEN_CODE_AGENT .':report:'.$day.':'. $uid,$data);
        $this->redis_expire(TOKEN_CODE_AGENT .':report:'.$day.':'. $uid,2*24*3600);
        return true;
    }

    /*
     * 写入代理树信息
     * uid 新注册用户id
     * agent_id 代理id
     */
    public function update_agent_tree($uid,$agent_id)
    {
        $sql = "INSERT IGNORE INTO `gc_agent_tree`(ancestor,descendant) SELECT t.ancestor,{$uid}
    FROM `gc_agent_tree` AS t WHERE t.descendant = {$agent_id} UNION SELECT {$agent_id},{$uid}";
        try {
            $this->db->query($sql);
            return ['code' => 200];
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function create_agent_data($user_id, $phone, $email, $qq, $user_memo)
    {
        $user_info = $this->user_cache($user_id);

        try {
            $this->select_db('private');
            $data = [
                'user_id' => $user_id,
                'name' => $user_info['username'],
                'phone' => $phone,
                'email' => $email,
                'qq' => $qq,
                'addtime' => time(),
                'user_memo' => $user_memo,
                'status' => 1,
            ];

            $where = array();
            $this->write('agent_review', $data, $where);

            return ['code' => 200];

        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

}
