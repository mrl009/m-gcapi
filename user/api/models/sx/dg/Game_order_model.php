<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

include_once FCPATH . 'api/core/SX_Model.php';

class game_order_model extends SX_Model
{
    public function __construct()
    {
        $this->select_db('shixun_w');
    }

    public function inset_order_bak($data, $platform_name)
    {
        $ci =& get_instance();
        $ci->load->model('sx/Bet_report_model', 'bet_report');
        $table = $platform_name . '_game_order' . date('m');
        foreach ($data as $k => $v) {
            if ($this->db->where('sx_id', $v['id'])->from($table)->count_all_results() != 0) {
                unset($data[$k]);
                continue;
            }
            $data[$k]['userName'] = $username = strtolower($v['userName']);
            $user = $this->get_user_info($username, $platform_name);
            $data[$k]['sx_id'] = $v['id'];
            $data[$k]['sn'] = $user['sn'];
            $data[$k]['snuid'] = $user['snuid'];
            $data[$k]['guid'] = $user['id'];
            $data[$k]['update_time'] = date('Y-m-d H:i:s');
            unset($data[$k]['id']);

            $ci->bet_report->dg_day_report($data[$k]);
            //统计用户打码量
            $this->init($user['sn']);
            $this->select_db('shixun_w');
            if (in_array($user['sn'], WX_DSN)) {
                $this->sx_redis->hincrbyfloat($user['sn'] . ':user:dml', $user['snuid'], $data[$k]['availableBet']);
            } else {
                $this->redis_select(REDIS_LONG);
                $this->redis_hincrbyfloat('user:dml', $user['snuid'], $data[$k]['availableBet']);
            }
        }

        if (!empty($data))
            $this->db->insert_batch($table, $data);
        return true;
    }

    public function inset_order($data, $platform_name)
    {
        $list_id = [];
        $ci =& get_instance();
        $ci->load->model('sx/Bet_report_model', 'bet_report');
        $table = $platform_name . '_game_order' . date('m');
        foreach ($data as $v) {
            if ($this->db->where('sx_id', $v['id'])->from($table)->count_all_results() != 0) {
                array_push($list_id, $v['id']);
                continue;
            }
            $username = strtolower($v['userName']);
            $user = $this->get_user_info($username, $platform_name);
            $data = $v;
            $data['userName'] = $username;
            $data['sx_id'] = $v['id'];
            $data['sn'] = $user['sn'];
            $data['snuid'] = $user['snuid'];
            $data['guid'] = $user['id'];
            $data['update_time'] = date('Y-m-d H:i:s');
            unset($data['id']);
            //写入视讯报表报表
            $flag = $ci->bet_report->dg_day_report($data);
            if ($flag) {
                $flag = $this->db->insert($table, $data);
                if ($flag) {
                    array_push($list_id, $v['id']);
                    //统计用户打码量
                    $this->init($user['sn']);
                    $this->select_db('shixun_w');
                    if (in_array($user['sn'], WX_DSN)) {
                        $this->sx_redis->hincrbyfloat($user['sn'] . ':user:dml', $user['snuid'], $data['availableBet']);
                    } else {
                        $this->redis_select(REDIS_LONG);
                        $this->redis_hincrbyfloat('user:dml', $user['snuid'], $data['availableBet']);
                    }
                }
            }
        }
        return $list_id;
    }

    public function get_user_info($username, $platform_name)
    {
        return $this->db->select('id,sn,snuid')->where('g_username', $username)->get($platform_name . '_user')->row_array();
    }
}