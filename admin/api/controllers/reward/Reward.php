<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Reward extends MY_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('MY_Model', 'M');
        $this->load->model('reward/Reward_model', 'reward');
    }

    public function get_auth_list()
    {
        $sn = $this->G('site_id');
        $data = $this->reward->get_auth_list($sn);
        $this->return_json(OK, $data);
    }

    public function save_auth()
    {
        $sn = $this->P('site_id');
        $id = $this->P('id');
        $rebate = $this->P('rebate');
        if (empty($sn) || empty($rebate)) {
            $this->return_json(E_ARGS, '参数错误');
        }
        $rs = $this->reward->save_auth($sn, $id, $rebate);
        if ($rs) {
            $this->return_json(OK, '执行成功');
        } else {
            $this->return_json(E_OP_FAIL, '执行失败');
        }
    }

    public function reward_report()
    {
        $adminId = $this->admin['id'];
        if (empty($adminId)) {
            $this->return_json(E_DENY, '请先登录');
        }
        $start = $this->G('start') ? $this->G('start') : date('Y-m-01', time());
        $end = $this->G('end') ? $this->G('end') : date('Y-m-d', time());
        if (strtotime($end) - strtotime($start) > ADMIN_ORDER_QUERY) {
            $this->return_json(E_ARGS, '查询间隔不能大于一个月');
        }
        $rs = $this->reward->reward_report($adminId, $start, $end);
        $this->return_json(OK, $rs);

    }
}