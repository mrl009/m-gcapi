<?php
/**
 * Created by PhpStorm.
 * User: mr.l
 * Date: 2018/4/11
 * Time: 下午12:02
 */

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Sx_report_model extends MY_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->select_db('shixun');
    }

    /**
     * 视讯报表统计
     * @param int $type
     * @param $basic
     * @return array
     */
    public function sx_form_list($type = 3, $basic)
    {
        // 获取级别对应的用户id
        $this->select_db('private');
        $uid = $this->get_list('id', 'user', ['level_id' => $basic['level_id']], array('wherein' => ['username' => explode(',', $basic['username'])]));
        unset($basic['username']);
        unset($basic['level_id']);
        $senior = [];
        !empty($uid) && $senior['wherein'] = ['snuid' => array_column($uid, 'id')];
        //请求字段
        $field = 'snuid,username,if(sum(total_bet) is null, 0, sum(total_bet)) as total_price, 
                    if(sum(total_v_bet) is null, 0, sum(total_v_bet)) as valid_price,
                    if(sum(win_or_lose) is null, 0, sum(win_or_lose)) as lucky_price,
                    if(sum(total_count) is null, 0, sum(total_count)) as total_num,
				    if(sum(total_count) is null, 0, sum(total_count)) as bets_num';
        if ($type == 3) {
            $data = $this->get_user_report($field, $basic, $senior);
        } else {
            $data = $this->get_bet_report($field, $basic, $senior);
        }
        return $data;
    }

    /**
     * 视讯会员报表
     * @param $field
     * @param $basic
     * @param $senior
     * @return array
     */
    private function get_user_report($field, $basic, $senior)
    {
        $rs = [];
        $set = $this->get_gcset();
        $set = explode(',', $set['cp']);
        $senior['groupby'] = array('snuid', 'id');
        if (in_array(1001, $set)) {
            $this->select_db('shixun');
            $t = $this->get_list($field, 'ag_bet_report', $basic, $senior);
            $rs = $this->format_rows($t);
        }
        if (in_array(1002, $set)) {
            $this->select_db('shixun');
            $t = $this->get_list($field, 'dg_bet_report', $basic, $senior);
            $rows = $this->format_rows($t);
            $rs = $this->data_plus($rs, $rows);
        }
        if (in_array(1003, $set)) {
            $this->select_db('shixun');
            $t = $this->get_list($field, 'lebo_bet_report', $basic, $senior);
            $rows = $this->format_rows($t);
            $rs = $this->data_plus($rs, $rows);
        }
        if (in_array(1004, $set)) {
            $this->select_db('shixun');
            $t = $this->get_list($field, 'pt_bet_report', $basic, $senior);
            $rows = $this->format_rows($t);
            $rs = $this->data_plus($rs, $rows);
        }
        //
        if (in_array(1006, $set)) {
            $this->select_db('shixun');
            $t = $this->get_list($field, 'ky_bet_report', $basic, $senior);
            $rows = $this->format_rows($t);
            $rs = $this->data_plus($rs, $rows);
        }
        return $this->format_rs($rs);
    }

    /**
     * 视讯彩票报表
     * @param $field
     * @param $basic
     * @param $senior
     * @return array
     */
    private function get_bet_report($field, $basic, $senior)
    {
        $rs = [];
        $this->select_db('shixun');
        $set = $this->get_gcset();
        $set = explode(',', $set['cp']);
        if (in_array(1001, $set)) {
            $t = $this->get_list($field, 'ag_bet_report', $basic, $senior);
            array_push($rs, array_merge(['name' => 'AG视讯'], $t[0]));
        }
        if (in_array(1002, $set)) {
            $t = $this->get_list($field, 'dg_bet_report', $basic, $senior);
            array_push($rs, array_merge(['name' => 'DG视讯'], $t[0]));
        }
        if (in_array(1003, $set)) {
            $t = $this->get_list($field, 'lebo_bet_report', $basic, $senior);
            array_push($rs, array_merge(['name' => 'LEBO视讯'], $t[0]));
        }
        if (in_array(1004, $set)) {
            $t = $this->get_list($field, 'pt_bet_report', $basic, $senior);
            array_push($rs, array_merge(['name' => 'PT视讯'], $t[0]));
        }
        if (in_array(1006, $set)) {
            $t = $this->get_list($field, 'ky_bet_report', $basic, $senior);
            array_push($rs, array_merge(['name' => '开元棋牌'], $t[0]));
        }
        return $this->format_rs($rs);
    }

    /**
     * 格式化数据
     * @param $data
     * @return array
     */
    private function format_rows($data)
    {
        $rs = [];
        foreach ($data as $v) {
            /*if (!isset($rs[$v['snuid']]['name'])) {
                $username = $this->user_cache($v['snuid']);
                $rs[$v['snuid']]['name'] = isset($username['username']) ? $username['username'] : '';
            }*/
            $rs[$v['snuid']]['name'] = substr($v['username'], 3);
            $rs[$v['snuid']]['total_price'] = isset($rs[$v['snuid']]['total_price']) ? $rs[$v['snuid']]['total_price'] + $v['total_price'] : $v['total_price'];
            $rs[$v['snuid']]['valid_price'] = isset($rs[$v['snuid']]['valid_price']) ? $rs[$v['snuid']]['valid_price'] + $v['valid_price'] : $v['valid_price'];
            $rs[$v['snuid']]['lucky_price'] = isset($rs[$v['snuid']]['lucky_price']) ? $rs[$v['snuid']]['lucky_price'] + $v['lucky_price'] : $v['lucky_price'];
            $rs[$v['snuid']]['total_num'] = isset($rs[$v['snuid']]['total_num']) ? $rs[$v['snuid']]['total_num'] + $v['total_num'] : $v['total_num'];
            $rs[$v['snuid']]['bets_num'] = isset($rs[$v['snuid']]['bets_num']) ? $rs[$v['snuid']]['bets_num'] + $v['bets_num'] : $v['bets_num'];
        }
        return $rs;
    }

    /**
     * 合并数组
     * @param $da
     * @param $mr
     * @return array
     */
    private function data_plus($da, $mr)
    {
        $keys = array_keys($da);
        foreach ($mr as $k => $v) {
            if (in_array($k, $keys)) {
                $da[$k]['total_price'] += $v['total_price'];
                $da[$k]['valid_price'] += $v['valid_price'];
                $da[$k]['lucky_price'] += $v['lucky_price'];
                $da[$k]['total_num'] += $v['total_num'];
                $da[$k]['bets_num'] += $v['bets_num'];
            } else {
                $da[$k] = $v;
            }
            //$da[$k]['name'] = $this->get_username($k);
        }
        return $da;
    }

    /**
     * 结果重新组装
     * @param $rs
     * @return array
     */
    private function format_rs($rs)
    {
        foreach ($rs as $k => $v) {
            $rs[$k]['valid_price'] = sprintf("%.3f", floatval($v['valid_price']));
            $rs[$k]['diff_price'] = sprintf("%.3f", floatval($v['lucky_price']));
            $rs[$k]['cor_valid_price'] = sprintf("%.3f", floatval($v['valid_price']));
            $rs[$k]['total_price'] = sprintf("%.3f", floatval($v['total_price']));
            $rs[$k]['cor_diff_price'] = -$rs[$k]['diff_price'];
        }
        $footer = [
            'total_price' => sprintf("%.3f", array_sum(array_column($rs, 'total_price'))),
            'valid_price' => sprintf("%.3f", array_sum(array_column($rs, 'valid_price'))),
            'total_num' => array_sum(array_column($rs, 'total_num')),
            'bets_num' => array_sum(array_column($rs, 'bets_num')),
            //'diff_price' => array_sum(array_column($rs, 'diff_price')),
            'diff_price' => sprintf("%.3f", array_sum(array_column($rs, 'diff_price'))),
            'cor_valid_price' => sprintf("%.3f", array_sum(array_column($rs, 'cor_valid_price'))),
            //'cor_diff_price' => array_sum(array_column($rs, 'cor_diff_price')),
            'cor_diff_price' => sprintf("%.3f", array_sum(array_column($rs, 'cor_diff_price'))),
        ];
        return ['rows' => array_values($rs), 'total' => 1000, 'footer' => $footer];
    }

    private function get_username($id) {
        $username = $this->user_cache($id);
        return isset($username['username']) ? $username['username'] : '';
    }
}