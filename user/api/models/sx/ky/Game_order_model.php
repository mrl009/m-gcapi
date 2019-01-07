<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}
include_once FCPATH.'api/core/SX_Model.php';
class Game_order_model extends SX_Model
{
    public function inset_order($data, $platform_name)
    {
        $this->select_db('shixun_w');
        $ci =& get_instance();
        $ci->load->model('sx/Bet_report_model', 'bet_report');
        $table = $platform_name . '_game_order' . date('m');
        $arr=[];
        for ($i=0;$i<count($data['GameID']);$i++){
            if ($this->db->where('game_code', $data['GameID'][$i])->from($table)->count_all_results() != 0) {
                unset($arr[$i]);
                continue;
            }
            $order_data['game_code']=$data['GameID'][$i];
            $order_data['valid_betamount']=$data['CellScore'][$i];
            $order_data['agent_code']=$data['ChannelID'][$i];
            $order_data['sn']=ltrim($data['LineCode'][$i],$data['ChannelID'][$i].'_');
            $order_data['username']=ltrim($data['Accounts'][$i],$data['ChannelID'][$i].'_');
            $user=$this->get_user_info($order_data['username'],'ky');
            $this->select_db('shixun_w');
            $order_data['snuid']=$user['snuid'];
            $order_data['bet_amount']=$data['AllBet'][$i];
            $order_data['netamount']=$data['Profit'][$i];
            $order_data['renevue']=$data['Revenue'][$i];
            $order_data['bet_time']=$data['GameStartTime'][$i];
            $order_data['recalcu_time']=$data['GameEndTime'][$i];
            $order_data['game_type']=$data['KindID'][$i];
            $order_data['flag']=1;
            $order_data['currency']='CNY';
            $order_data['table_code']=$data['TableID'][$i];
            $order_data['login_ip']='000.000.00.00';
            $order_data['card_value']=$data['CardValue'][$i];
            $order_data['device_type']=0;
            $order_data['update_time']=date('Y-m-d H:i:s',time());
            $order_data['remark']=$data['ServerID'][$i].'-'.$data['ChairID'][$i].'-'.$data['UserCount'][$i];
            $arr[$i]=$order_data;

            $ci->bet_report->ky_day_report($order_data);
            //统计用户打码量
            $this->init($user['sn']);
            $this->select_db('shixun_w');
            if (in_array($user['sn'], WX_DSN)) {
                $this->sx_redis->hincrbyfloat($user['sn'] . ':user:dml', $user['snuid'], $order_data['valid_betamount']);
            } else {
                $this->redis_select(REDIS_LONG);
                $this->redis_hincrbyfloat('user:dml', $user['snuid'], $order_data['valid_betamount']);
            }
        }
        if (!empty($arr)){
            $res=$this->db->insert_batch($table, $arr);
        }
        return true;
    }
    public function get_user_info($username, $platform_name)
    {
        $this->select_db('shixun');
        return $this->db->select('id,sn,snuid')->where('g_username', $username)->get($platform_name . '_user')->row_array();
    }
}