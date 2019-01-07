<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Bet_report_model extends GC_Model
{

    public function __construct()
    {
        //$this->select_db('shixun');
    }
    public function mg_day_report($data){
        $this->select_db('shixun_w');
        $username = $data['username'];
        $date = date('Y-m-d', strtotime($data['game_end_time']));
        $where = ['username' => $username, 'bet_time' => $date];
        $count = $this->db->where($where)->from('mg_bet_report')->count_all_results();
        //var_dump($where);exit();
        if($count > 0){
            $res = $this->db->where($where)->from('mg_bet_report')->get()->row_array();
            $update_data['total_bet'] = $res['total_bet'] + $data['total_wager'];
            $update_data['total_v_bet'] = $res['total_bet'] + $data['total_wager'];
            $update_data['payout'] = $res['payout'] + $data['total_payout'];
            $win_or_lose = $update_data['payout']-$update_data['total_bet'];
            $update_data['win_or_lose'] = $res['win_or_lose'] + $win_or_lose;
            $is_win = $win_or_lose > 0 ? 1 : 0;
            $update_data['total_win_count'] = $res['total_win_count'] + $is_win;
            $update_data['total_count'] = $res['total_count'] + 1;
            $update_data['update_time'] = date('Y-m-d H:i:s');
            $rs=$this->db->where($where)->update('mg_bet_report', $update_data);
            return $rs;
        }else{
            $insert_data = [];
            $insert_data['sn'] = $data['sn'];
            $insert_data['snuid'] = $data['snuid'];
            $insert_data['username'] = $username;
            $insert_data['total_bet'] = $data['total_wager'];
            $insert_data['total_v_bet'] = $data['total_wager'];
            $insert_data['payout'] = $data['total_payout'];  //派彩金额=投注金额+输赢金额
            $win_or_lose = $insert_data['payout']-$insert_data['total_bet'];
            $insert_data['win_or_lose'] = $win_or_lose;
            //$insert_data[ 'cal_type' ] = '1';暂时用不上，注释之
            $insert_data['total_win_count'] = $insert_data['win_or_lose'] > 0 ? 1 : 0;
            $insert_data['total_count'] = 1;
            $insert_data['bet_time'] = $date;
            $insert_data['update_time'] = date('Y-m-d H:i:s');
            $insert_data['is_fs'] = 0; //默认为0
            return $this->db->insert('mg_bet_report', $insert_data);
        }
    }
    public function ky_day_report($data){
        $this->select_db('shixun_w');
        $date = date('Y-m-d', strtotime($data['bet_time']));
        $username = $data['username'];
        $where = ['username' => $username, 'bet_time' => $date];
        $count = $this->db->where($where)->from('ky_bet_report')->count_all_results();
        if ($count > 0) {
            $res = $this->db->where($where)->from('ky_bet_report')->get()->row_array();
            $update_data['total_bet'] = $res['total_bet'] + $data['bet_amount'];
            $update_data['total_v_bet'] = $res['total_v_bet'] + $data['valid_betamount'];
            $update_data['payout'] = $res['payout'] + $data['netamount']+ $data['bet_amount'];
            $win_or_lose = $data['netamount'];
            $update_data['win_or_lose'] = $res['win_or_lose'] + $win_or_lose;
            $is_win = $win_or_lose > 0 ? 1 : 0;
            $update_data['total_win_count'] = $res['total_win_count'] + $is_win;
            $update_data['total_count'] = $res['total_count'] + 1;
            $update_data['update_time'] = date('Y-m-d H:i:s');
            return $this->db->where($where)->update('ky_bet_report', $update_data);
        } else {
            $insert_data = [];
            $insert_data['sn'] = $data['sn'];
            $insert_data['snuid'] = $data['snuid'];
            $insert_data['username'] = $username;
            $insert_data['total_bet'] = $data['bet_amount'];
            $insert_data['total_v_bet'] = $data['valid_betamount'];
            $insert_data['payout'] = $data['netamount']+ $data['bet_amount'];  //派彩金额=投注金额+输赢金额
            $insert_data['win_or_lose'] = $data['netamount'];
            //$insert_data[ 'cal_type' ] = '1';暂时用不上，注释之
            $insert_data['total_win_count'] = $insert_data['win_or_lose'] > 0 ? 1 : 0;
            $insert_data['total_count'] = 1;
            $insert_data['bet_time'] = $date;
            $insert_data['update_time'] = date('Y-m-d H:i:s');
            $insert_data['is_fs'] = 0; //默认为0
            return $this->db->insert('ky_bet_report', $insert_data);
        }
    }
    public function dg_day_report($data)
    {
        $this->select_db('shixun_w');
        $date = date('Y-m-d', strtotime($data['betTime']));
        $username = $data['userName'];
        $where = ['username' => $username, 'bet_time' => $date];
        $count = $this->db->where($where)->from('dg_bet_report')->count_all_results();

        if ($count > 0) {
            $res = $this->db->where($where)->from('dg_bet_report')->get()->row_array();
            $update_data['total_bet'] = $res['total_bet'] + $data['betPoints'];
            $update_data['total_v_bet'] = $res['total_v_bet'] + $data['availableBet'];
            $update_data['payout'] = $res['payout'] + $data['winOrLoss'];
            $win_or_lose = $data['winOrLoss'] - $data['betPoints'];
            $update_data['win_or_lose'] = $res['win_or_lose'] + $win_or_lose;
            $is_win = $win_or_lose > 0 ? 1 : 0;
            $update_data['total_win_count'] = $res['total_win_count'] + $is_win;
            $update_data['total_count'] = $res['total_count'] + 1;
            $update_data['update_time'] = date('Y-m-d H:i:s');
            return $this->db->where($where)->update('dg_bet_report', $update_data);
        } else {
            $insert_data = [];
            $insert_data['sn'] = $data['sn'];
            $insert_data['snuid'] = $data['snuid'];
            $insert_data['username'] = $username;
            $insert_data['total_bet'] = $data['betPoints'];
            $insert_data['total_v_bet'] = $data['availableBet'];
            $insert_data['payout'] = $data['winOrLoss'];
            $insert_data['win_or_lose'] = $data['winOrLoss'] - $data['betPoints'];
            //$insert_data[ 'cal_type' ] = '1';暂时用不上，注释之
            $insert_data['total_win_count'] = $insert_data['win_or_lose'] > 0 ? 1 : 0;
            $insert_data['total_count'] = 1;
            $insert_data['bet_time'] = $date;
            $insert_data['update_time'] = date('Y-m-d H:i:s');
            $insert_data['is_fs'] = 0; //默认为1
            return $this->db->insert('dg_bet_report', $insert_data);
        }
    }

    public function lebo_day_report($data)
    {
        $this->select_db('shixun_w');
        $date = date('Y-m-d', time());
        $username = $data['user_name'];
        $where = ['username' => $username, 'bet_time' => $date];
        $count = $this->db->where($where)->from('lebo_bet_report')->count_all_results();
        if ($count > 0) {
            $res = $this->db->where($where)->from('lebo_bet_report')->get()->row_array();
            $update_data['total_bet'] = $res['total_bet'] + $data['total_bet_score'];
            $update_data['total_v_bet'] = $res['total_v_bet'] + $data['valid_bet_score_total'];
            $pay_out =$data['valid_bet_score_total']+$data['total_win_score'];
            $update_data['payout'] = $res['payout'] + $pay_out;
            $update_data['win_or_lose'] = $res['win_or_lose'] + $data['total_win_score'];
            $is_win = $data['total_win_score'] > 0 ? 1 : 0;
            $update_data['total_win_count'] = $res['total_win_count'] + $is_win;
            $update_data['total_count'] = $res['total_count'] + 1;
            $update_data['update_time'] = date('Y-m-d H:i:s');
            $this->db->where($where)->update('lebo_bet_report', $update_data);
        } else {
            $insert_data = [];
            $insert_data['sn'] = $data['sn'];
            $insert_data['snuid'] = $data['snuid'];
            $insert_data['username'] = $username;
            $insert_data['total_bet'] = $data['total_bet_score'];
            $insert_data['total_v_bet'] = $data['valid_bet_score_total'];
            $insert_data['payout'] = $data['total_win_score'] > 0 ? $data['total_win_score'] : 0;
            $insert_data['win_or_lose'] =$data['total_win_score'];
            //$insert_data[ 'cal_type' ] = '1';暂时用不上，注释之
            $insert_data['total_win_count'] = $data['total_win_score'] > 0 ? 1 : 0;
            $insert_data['total_count'] = 1;
            $insert_data['bet_time'] = $date;
            $insert_data['update_time'] = date('Y-m-d H:i:s');
            $insert_data['is_fs'] = 0; //默认为1
            $this->db->insert('lebo_bet_report', $insert_data);
        }

        return true;
    }

    public function ag_day_report($data)
    {
        $this->select_db('shixun_w');
        $type = $data['data_type'];
        if ($type == 'HSR') {
            $date = date('Y-m-d', strtotime($data['bet_time']));
            $game_type = 3;
        } else if ($type == 'BR' || $type == 'EBR') {
            if ($type == 'BR') {
                $game_type = 1;
            } else {
                $game_type = 2;
            }

            $date = date('Y-m-d', strtotime($data['bet_time']));
        }

        $insert_data = [];

        $username = $data['username'];
        // @modify Jhon 報表不區分game_type 2018-12-24
        //$where = ['username' => $username, 'bet_time' => $date, 'game_type' => $game_type];
        $where = ['username' => $username, 'bet_time' => $date];
        $count = $this->db->where($where)->from('ag_bet_report')->count_all_results();

        if ($count > 0) {
            $res = $this->db->where($where)->from('ag_bet_report')->get()->row_array();
            if ($game_type == 1 || $game_type == 2) {
                $update_data['total_bet'] = $res['total_bet'] + $data['bet_amount'];
                $update_data['total_v_bet'] = $res['total_v_bet'] + $data['valid_betamount'];
                $payout = $data['netamount'] > 0 ? $data['netamount'] : 0;
                $update_data['payout'] = $res['payout'] + $payout;
                $update_data['win_or_lose'] = $res['win_or_lose'] + $data['netamount'];
                $is_win = $data['netamount'] > 0 ? 1 : 0;
                $update_data['total_win_count'] = $res['total_win_count'] + $is_win;
                $update_data['total_count'] = $res['total_count'] + 1;
            } else if ($game_type == 3) {
                $update_data['total_bet'] = $res['total_bet'] + abs($data['transfer_amount']);
                $update_data['total_v_bet'] = $res['total_v_bet'] + abs($data['transfer_amount']);
                $payout = $data['current_amount'] - $data['previous_amount'];
                $update_data['payout'] = $res['payout'] + $payout > 0 ? $payout : 0;;
                $update_data['win_or_lose'] = $res['win_or_lose'] + $payout;
                $is_win = $payout > 0 ? 1 : 0;
                $update_data['total_win_count'] = $res['total_win_count'] + $is_win;
                $update_data['total_count'] = $res['total_count'] + 1;
            }

            $update_data['update_time'] = date('Y-m-d H:i:s');
            $this->db->where($where)->update('ag_bet_report', $update_data);
        } else {
            $insert_data['sn'] = $data['sn'];
            $insert_data['snuid'] = $data['snuid'];
            $insert_data['username'] = $data['username'];
            if ($game_type == 1 || $game_type == 2) {
                $insert_data['total_bet'] = $data['bet_amount'];
                $insert_data['total_v_bet'] = $data['valid_betamount'];
                $insert_data['payout'] = $data['netamount'] > 0 ? $data['netamount'] : 0;
                $insert_data['win_or_lose'] = $data['netamount'];
                $insert_data['total_win_count'] = $data['netamount'] > 0 ? 1 : 0;
                $insert_data['total_count'] = 1;
            } else if ($game_type == 3) {
                /*$insert_data['total_bet'] = abs($data['transfer_amount']);
                $insert_data['total_v_bet'] = abs($data['transfer_amount']);
                $payout = $data['current_amount'] - $data['previous_amount'];
                $insert_data['payout'] = $payout > 0 ? $payout : 0;
                $insert_data['win_or_lose'] = $payout;
                $insert_data['total_win_count'] = $payout > 0 ? 1 : 0;
                $insert_data['total_count'] = 1;*/
                $insert_data['total_bet'] = $data['bet_amount'];
                $insert_data['total_v_bet'] = $data['valid_betamount'];
                $insert_data['payout'] = $data['netamount'] > 0 ? $data['netamount'] : 0;
                $insert_data['win_or_lose'] = $data['netamount'];
                $insert_data['total_win_count'] = $data['netamount'] > 0 ? 1 : 0;
                $insert_data['total_count'] = 1;
            }

            $insert_data['game_type'] = $game_type;
            $insert_data['bet_time'] = $date;
            $insert_data['update_time'] = date('Y-m-d H:i:s');
            $insert_data['is_fs'] = 0; //默认为1
            $this->db->insert('ag_bet_report', $insert_data);
        }

        return true;
    }

    public function pt_day_report($data)
    {
        $this->select_db('shixun_w');
        //1电子，2视讯
        $type = strpos($data['game_type'], 'live') === false ? 1 : 2;
        $username = $data['username'];
        $bet_time = date('Y-m-d', strtotime($data['game_date']));
        $where = ['username' => $username, 'bet_time' => $bet_time, 'game_type' => $type];
        $count = $this->db->where($where)->from('pt_bet_report')->count_all_results();

        if ($count > 0) {
            $res = $this->db->where($where)->from('pt_bet_report')->get()->row_array();
            $update_data['total_bet'] = $res['total_bet'] + $data['bet'];
            $update_data['total_v_bet'] = $res['total_v_bet'] + $data['bet'];
            $update_data['payout'] = $res['payout'] + $data['win'];
            $update_data['win_or_lose'] = $res['win_or_lose'] + ($data['win'] - $data['bet']);
            $is_win = ($data['win'] - $data['bet']) > 0 ? 1 : 0;
            $update_data['total_win_count'] = $res['total_win_count'] + $is_win;
            $update_data['total_count'] = $res['total_count'] + 1;
            $update_data['update_time'] = date('Y-m-d H:i:s');
            $this->db->where($where)->update('pt_bet_report', $update_data);
        } else {
            $insert_data = [];
            $insert_data['sn'] = $data['sn'];
            $insert_data['snuid'] = $data['snuid'];
            $insert_data['username'] = $username;
            $insert_data['total_bet'] = $data['bet'];
            $insert_data['total_v_bet'] = $data['bet'];
            $insert_data['payout'] = $data['win'];
            $insert_data['win_or_lose'] = $data['win'] - $data['bet'];
            $insert_data['game_type'] = $type;
            $is_win = $insert_data['win_or_lose'] > 0 ? 1 : 0;
            $insert_data['total_win_count'] = $is_win;
            $insert_data['total_count'] = 1;
            $insert_data['bet_time'] = $bet_time;
            $insert_data['update_time'] = $data['update_time'];
            $this->db->insert('pt_bet_report', $insert_data);
        }

        return true;
    }

    public function get_report($platform, $data)
    {
        $this->select_db('shixun');
        $field = 'SUM(total_count) as bets_num, SUM(total_bet) as bets_total, SUM(total_v_bet) as total_v_bet, SUM(win_or_lose) as win_or_lose';
        $where = [
            'snuid' => isset($data['uid']) ? $data['uid'] : 0,
            'sn' => isset($data['sn']) ? $data['sn'] : '',
            'bet_time >=' => isset($data['start']) ? $data['start'] : '',
            'bet_time <=' => isset($data['end']) ? $data['end'] : '',
        ];
        $rs = $this->db->select($field)->where($where)->get($platform . '_bet_report')->row_array();
        return [
            'total' => (float)$rs['bets_num'],
            'total_bet' => (float)$rs['bets_total'],
            'total_v_bet' => (float)$rs['total_v_bet'],
            'win_or_lose' => (float)$rs['win_or_lose'],
        ];
    }
}