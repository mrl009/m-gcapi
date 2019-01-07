<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

include_once FCPATH . 'api/core/SX_Controller.php';

class Game_order extends SX_Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    # 获取注单入口
    public function get_game_order()
    {
        $sn = $this->sxuser['sn'];
        $snUid = $this->sxuser['id'];
        $platform = isset($this->sxuser['platform']) ? $this->sxuser['platform'] : 'ag';
        $start = isset($this->sxuser['start']) ? $this->sxuser['start'] . ' 00:00:00' : date('Y-m-d') . ' 00:00:00';
        $end = isset($this->sxuser['end']) ? $this->sxuser['end'] . ' 23:59:59' : date('Y-m-d') . ' 23:59:59';
        $orderNum = isset($this->sxuser['order_num']) ? $this->sxuser['order_num'] : '';
        $page = isset($this->sxuser['page']) ? $this->sxuser['page'] : 1;
        $num = isset($this->sxuser['num']) ? $this->sxuser['num'] : 15;

        if (strtotime($start) > strtotime($end)) {
            $this->return_json(E_ARGS, '起始时间不能大于结束时间！');
        }

        if (date('m', strtotime($start)) != date('m', strtotime($end))) {
            $this->return_json(E_ARGS, '只能查询同一个月！');
        }

        $params = [
            'sn' => $sn,
            'snuid' => $snUid,
            'platform' => $platform,
            'start' => $start,
            'end' => $end,
            'order_num' => $orderNum,
            'page' => $page,
            'num' => $num,
            'm' => date('m', strtotime($start))
        ];
        $ci =& get_instance();
        $ci->load->model('sx/Game_order_model', 'game_order');
        $rs = $ci->game_order->bet_record($params);
        $this->return_json(OK, $rs);
    }
}
