<?php
/**
 * Created by PhpStorm.
 * User: mr.l
 * Date: 2018/4/11
 * Time: 上午10:53
 */

defined('BASEPATH') or exit('No direct script access allowed');

class Sx_report extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('form/Sx_report_model', 'report_model');
    }

    /**
     * 获取报表数据接口
     */
    public function get_list()
    {
        //
        $form_type = (int)$this->G('form_type');
        //精确条件
        $basic = array(
            'bet_time >=' => empty($this->G('time_start')) ? date('Y-m-d') : $this->G('time_start'),
            'bet_time <=' => empty($this->G('time_end')) ? date('Y-m-d') : $this->G('time_end'),
            'level_id' => (int)$this->G('level_id'),
            'username' => $this->G('username')
        );

        //查询时间跨度不能超过两个月
        $start_time = strtotime($basic['bet_time >=']);
        $end_time = strtotime($basic['bet_time <=']);
        $diff_time = $end_time - $start_time;
        if ($diff_time > ADMIN_ORDER_QUERY) {
            $basic['bet_time <='] = date('Y-m-d', $start_time + ADMIN_ORDER_QUERY);
        }
        $basic['sn'] = $this->get_sn();
        $data = $this->report_model->sx_form_list($form_type, $basic);
        $this->return_json(OK, $data);
    }
}