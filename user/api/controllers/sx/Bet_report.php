<?php
/**
 * Created by PhpStorm.
 * User: mrl
 * Date: 2018/3/13
 * Time: 上午9:55
 */
defined('BASEPATH') or exit('No direct script access allowed');

include_once FCPATH . 'api/core/SX_Controller.php';

class Bet_report extends SX_Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 获取报表
     */
    public function report()
    {
        $data['uid'] = $this->sxuser['id'];
        $data['sn'] = $this->sxuser['sn'];
        $data['start'] = isset($this->sxuser['start']) ? $this->sxuser['start'] . ' 00:00:00' : date('Y-m-d') . ' 00:00:00';
        $data['end'] = isset($this->sxuser['end']) ? $this->sxuser['end'] . ' 23:59:59' : date('Y-m-d') . ' 23:59:59';
        if (strtotime($data['end']) - strtotime($data['start']) > ADMIN_QUERY_TIME_SPAN) {
            $this->return_json(E_ARGS, '查询跨度不能大于两个月');
        }
        $this->load->model('user/Bet_record_model', 'bet_record');
        $rs['user'] = $this->bet_record->get_report($data);
        //获取站点设置
        $this->load->model('sx/Bet_report_model', 'bet_report');
        $set = $this->M->get_gcset();
        $set = explode(',', $set['cp']);
        if (in_array(1001, $set)) {
            $rs['ag'] = $this->bet_report->get_report('ag', $data);
        }
        if (in_array(1002, $set)) {
            $rs['dg'] = $this->bet_report->get_report('dg', $data);
        }
        if (in_array(1003, $set)) {
            $rs['lebo'] = $this->bet_record->get_report('lebo', $data);
        }
        if (in_array(1004, $set)) {
            $rs['pt'] = $this->bet_report->get_report('pt', $data);
        }
        if (in_array(1006, $set)) {
            $rs['ky'] = $this->bet_report->get_report('ky', $data);
        }
        $this->return_json(OK, $rs);
    }
}