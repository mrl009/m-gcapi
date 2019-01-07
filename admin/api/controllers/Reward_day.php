<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Reward_day extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 嘉獎機制
     */
    public function index()
    {
        $rs = $this->M->get_list('*', 'reward_day');
        $this->return_json(OK, $rs);
    }

    public function reward_info()
    {
        $id = $this->G('id');
        $rs = $this->M->get_one('*', 'reward_day', ['id' => $id]);
        $this->return_json(OK, $rs);
    }

    public function save_reward()
    {
        $id = $this->P('id');
        $d1_rate = $this->P('d1_rate');
        $d2_rate = $this->P('d2_rate');
        $d3_rate = $this->P('d3_rate');

        if (empty($id) || empty($d1_rate) || empty($d2_rate) || empty($d3_rate)) {
            $this->return_json(E_ARGS, '参数错误');
        }
        $data = array(
            'd1_rate' => $d1_rate,
            'd2_rate' => $d2_rate,
            'd3_rate' => $d3_rate,
        );
        $rs = $this->M->write('reward_day', $data, ['id' => $id]);
        $this->load->model('log/Log_model');
        if ($rs) {
            $this->Log_model->record($this->admin['id'], array('content' => "修改嘉獎機制成功,ID:{$id}"));
            $this->return_json(OK, '执行成功');
        } else {
            $this->Log_model->record($this->admin['id'], array('content' => "修改嘉獎機制失败,ID:{$id}"));
            $this->return_json(E_OP_FAIL, '执行失败');
        }
    }

    /**
     * 嘉獎詳情
     */
    public function get_detail()
    {
        $username = $this->G('username');
        $start = $this->G('start') ? $this->G('start') : date('Y-m-d', strtotime('-1 day'));
        $end = $this->G('end') ? $this->G('end') : date('Y-m-d');
        if (strtotime($end) - strtotime($start) > ADMIN_ORDER_QUERY) {
            $this->return_json(E_ARGS, '查询间隔不能大于一个月');
        }
        $basic = [
            'b.username' => $username,
            'a.reward_date >=' => $start,
            'a.reward_date <' => $end
        ];
        $senior['join'] = array(
            ['table' => 'user as b', 'on' => 'a.uid=b.id']
        );
        $page = array(
            'page' => $this->G('page'),
            'rows' => $this->G('rows'),
            'order' => $this->G('order'),
            'sort' => $this->G('sort')
        );
        $rs = $this->M->get_list('a.*,b.username,b.vip_id', 'reward_day_log', $basic, $senior, $page);
        $this->return_json(OK, $rs);
    }
}