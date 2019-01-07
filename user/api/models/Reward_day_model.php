<?php

/**
 * Created by PhpStorm.
 * User: mr.xiaolin
 * Date: 2018/5/22
 * Time: 上午9:30
 */
class Reward_day_model extends MY_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @param $uid
     * @return array
     */
    public function reward_info($uid)
    {
        $user = $this->get_one('vip_id', 'user', ['id' => $uid]);
        $day = date('Y-m-d', strtotime('-1 day'));
        $report = $this->get_one('sum(valid_price) as valid_price', 'report', ['uid =' => $uid, 'report_date' => $day]);
        $info = $this->get_one('money,rate,status', 'reward_day_log', ['uid =' => $uid, 'reward_date' => $day]);
        $rs = array(
            'vip_id' => isset($user['vip_id']) ? $user['vip_id'] : 1,
            'valid_price' => isset($report['valid_price']) ? $report['valid_price'] : 0,
            'rate' => isset($info['rate']) ? $info['rate'] . '%' : '0.0%',
            'money' => isset($info['money']) ? $info['money'] : 0,
            'is_reward' => isset($info['status']) ? (int)$info['status'] : 0,
        );
        return $rs;
    }

    /**
     * 更新领取记录
     * @param $where
     * @return bool
     */
    public function update_status($where)
    {
        $this->db->set('status', 2);
        $this->db->set('update_time', time());
        return $this->db->where($where)->update('reward_day_log');
    }

    /**
     * 每日加奖日结表
     * @param $sn
     */
    public function day_count($sn)
    {
        //更新过期数据
        $this->update_before();
        //判断是否处理过
        $day = date('Y-m-d', strtotime('-1 day'));
        $keys = 'reward:count_' . $day;
        $r = $this->redis_setnx($keys, 1);
        !$r && die('已经处理过');
        $this->redis_expire($keys, EXPIRE_24);
        //加奖比例
        $rSet = $this->get_list('*', 'reward_day');
        if (empty($rSet)) {
            die('请先设置每日加奖比例');
        }
        $rSet = array_make_key($rSet, 'vip_id');
        //加奖数据
        $basic = [
            'a.report_date' => $day,
            'b.vip_id >=' => 3
        ];
        $senior = [
            'join' => 'user',
            'on' => 'a.uid=b.id',
            'groupby' => ['a.uid']
        ];
        $rList = $this->get_list('a.uid,sum(a.valid_price) valid_price,a.report_date,b.vip_id', 'report', $basic, $senior);
        if (empty($rList)) {
            wlog(APPPATH . 'logs/reward_day_' . $sn . '_' . date('Ym') . '.log', $day . '没有需要处理的数据');
            die('没有需要处理的数据');
        }
        //生成加奖记录
        $data = [];
        foreach ($rList as $v) {
            $set = isset($rSet[$v['vip_id']]) ? $rSet[$v['vip_id']] : '';
            if (empty($set) || $v['valid_price'] < 100) {
                continue;
            }
            $r = $this->format_reward($v['valid_price'], $set);
            if ($r['money'] <= 0 || $r['rate'] < 0) {
                continue;
            }
            $tmp = [
                'uid' => $v['uid'],
                'money' => $r['money'],
                'valid_price' => $v['valid_price'],
                'rate' => $r['rate'],
                'status' => 1,
                'reward_date' => $day,
                'add_time' => time()
            ];
            array_push($data, $tmp);
        }
        $b = $this->db->insert_batch('reward_day_log', $data);
        if ($b) {
            $str = $day . '每日加奖生成成功' . json_encode($data);
        } else {
            $str = $day . '每日加奖生成失败';
        }
        wlog(APPPATH . 'logs/reward_day_' . $sn . '_' . date('Ym') . '.log', $str);
    }

    /**
     * 更新已过期未领取记录
     */
    private function update_before()
    {
        $start = date('Y-m-d', strtotime('-2 day'));
        $end = date('Y-m-d', strtotime('-4 day'));
        $where = array(
            'status' => 1,
            'reward_date <=' => $start,
            'reward_date >=' => $end,
        );
        $this->write('reward_day_log', array('status' => 3, 'update_time' => time()), $where);
    }

    /**
     * 获取加奖金额、比例
     * @param $money
     * @param $set
     * @return array
     */
    private function format_reward($money, $set)
    {
        $rate = $this->get_rate($money, $set);
        return ['rate' => $rate, 'money' => sprintf("%.3f", $money * $rate / 100)];
    }

    /**
     * 加奖比例
     * @param $money
     * @param $set
     * @return int
     */
    private function get_rate($money, $set)
    {
        $gcSet = $this->get_gcset(['reward_day']);
        $gcSet = explode(',', $gcSet['reward_day']);
        if (empty($gcSet) || empty($set) || $money < 100) {
            $rate = 0;
        } elseif ($money >= $gcSet[0] && $money < $gcSet[1]) {
            $rate = isset($set['d1_rate']) ? $set['d1_rate'] : 0;
        } elseif ($money >= $gcSet[1] && $money < $gcSet[2]) {
            $rate = isset($set['d2_rate']) ? $set['d2_rate'] : 0;
        } elseif ($money >= $gcSet[2]) {
            $rate = isset($set['d3_rate']) ? $set['d3_rate'] : 0;
        } else {
            $rate = 0;
        }
        return $rate;
    }

    public function day_count_test($sn, $day)
    {
        empty($day) && $day = date('Y-m-d');
        //更新过期数据
        $this->update_before();
        //加奖比例
        $rSet = $this->get_list('*', 'reward_day');
        if (empty($rSet)) {
            die('请先设置每日加奖比例');
        }
        $rSet = array_make_key($rSet, 'vip_id');
        //加奖数据
        $basic = [
            'a.report_date' => $day,
            'b.vip_id >=' => 3
        ];
        $senior = [
            'join' => 'user',
            'on' => 'a.uid=b.id',
            'groupby' => ['a.uid']
        ];
        $rList = $this->get_list('a.uid,sum(a.valid_price) valid_price,a.report_date,b.vip_id', 'report', $basic, $senior);
        if (empty($rList)) {
            wlog(APPPATH . 'logs/reward_day_' . $sn . '_' . date('Ym') . '.log', $day . '没有需要处理的数据');
            die('没有需要处理的数据');
        }
        //生成加奖记录
        $data = [];
        foreach ($rList as $v) {
            $set = isset($rSet[$v['vip_id']]) ? $rSet[$v['vip_id']] : '';
            if (empty($set) || $v['valid_price'] < 100) {
                continue;
            }
            $r = $this->format_reward($v['valid_price'], $set);
            if ($r['money'] <= 0 || $r['rate'] < 0) {
                continue;
            }
            $tmp = [
                'uid' => $v['uid'],
                'money' => $r['money'],
                'valid_price' => $v['valid_price'],
                'rate' => $r['rate'],
                'status' => 1,
                'reward_date' => $day,
                'add_time' => time()
            ];
            array_push($data, $tmp);
        }
        $b = $this->db->insert_batch('reward_day_log', $data);
        if ($b) {
            $str = $day . '每日加奖生成成功' . json_encode($data);
        } else {
            $str = $day . '每日加奖生成失败';
        }
        echo $str;
        wlog(APPPATH . 'logs/reward_day_' . $sn . '_' . date('Ym') . '.log', $str);
    }
}