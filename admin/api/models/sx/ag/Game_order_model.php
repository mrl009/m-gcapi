<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Game_order_model extends GC_Model
{
    public function __construct()
    {
        $this->select_db('shixun_w');
    }

    public function insert_order($data,$all_users)
    {
        $ci = &get_instance();
        $ci->load->model('sx/User_model', 'user_model');
        if (!in_array($data['playerName'],$all_users)) {
            wlog(APPPATH . 'logs/ag/' . date('Y-m-d') . 'ag_order_error.log', json_encode($data));
            return false;
        }else{
            $user = $this->user_model->get_user_info($data['playerName'], 'ag');
        }
        if ($data['dataType'] == 'BR' || $data['dataType'] == 'EBR') {
            $table = 'ag_game_order' . date('m');
            $allow_add_report = $this->db->where('bill_no', $data['billNo'])->from($table)->count_all_results() == 0 ? true : false;
            $insert_data['data_type'] = $data['dataType'];
            $insert_data['bill_no'] = $data['billNo'];
            $insert_data['username'] = $data['playerName'];
            $insert_data['agent_code'] = $data['agentCode'];
            $insert_data['game_code'] = $data['gameCode'];
            $insert_data['netamount'] = $data['netAmount'];
            $insert_data['bet_time'] = $data['betTime'];
            $insert_data['game_type'] = $data['gameType'];
            $insert_data['bet_amount'] = $data['betAmount'];
            $insert_data['valid_betamount'] = $data['validBetAmount'];
            $insert_data['flag'] = $data['flag'];
            $insert_data['play_type'] = $data['playType'];
            $insert_data['currency'] = $data['currency'];
            $insert_data['table_code'] = $data['tableCode'];
            $insert_data['login_ip'] = $data['loginIP'];
            $insert_data['recalcu_time'] = $data['recalcuTime'];
            $insert_data['platform_id'] = isset($data['platformId']) ? $data['platformId'] : '';
            $insert_data['platform_type'] = $data['platformType'];
            $insert_data['remark'] = $data['remark'];
            $insert_data['round'] = $data['round'];
            $insert_data['result'] = $data['result'];
            $insert_data['before_credit'] = $data['beforeCredit'];
            $insert_data['device_type'] = $data['deviceType'];
            $insert_data['update_time'] = date('Y-m-d H:i:s');
            $insert_data['sn'] = $user['sn'];
            $insert_data['snuid'] = $user['snuid'];
        } else if ($data['dataType'] == 'HSR') {
            $table = 'ag_game_order' . date('m');
            $allow_add_report = $this->db->where('id', $data['ID'])->from($table)->count_all_results() == 0 ? true : false;
            $insert_data['data_type'] = $data['dataType'];
            $insert_data['agent_code'] = isset($data['agentCode']) ? $data['agentCode'] : '';
            $insert_data['bill_no'] = $data['sceneId'];
            $insert_data['platform_type'] = $data['platformType'];
            $insert_data['username'] = $data['playerName'];
            $insert_data['currency'] = $data['currency'];
            $insert_data['bet_time'] = $data['creationTime'];
            $insert_data['game_code'] = $data['gameCode'];
            $insert_data['bet_amount'] = $data['Cost'];
            $insert_data['valid_betamount'] = $data['Cost'];
            $insert_data['netamount'] = $data['currentAmount'] - $data['previousAmount'];
            $insert_data['currency'] = $data['currency'];
            $insert_data['login_ip'] = $data['IP'];
            $insert_data['flag'] = $data['flag'];
            $insert_data['device_type'] = $data['deviceType'];
            $insert_data['remark'] = isset($data['remark']) ? $data['remark'] : '';
            $insert_data['sn'] = $user['sn'];
            $insert_data['update_time'] = date('Y-m-d H:i:s');
            $insert_data['snuid'] = $user['snuid'];
        } else {
            return false;
        }

        if ($allow_add_report) {
            //统计用户打码量
            $this->init($user['sn']);
            $this->select_db('shixun_w');
            $this->redis_select(REDIS_LONG);
            $this->redis_hincrbyfloat('user:dml',  $insert_data['snuid'], $insert_data['valid_betamount']);
            $ci->load->model('sx/Bet_report_model');
            $this->Bet_report_model->ag_day_report($insert_data);
        }

        return $this->db->replace($table, $insert_data);
    }
}