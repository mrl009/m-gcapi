<?php
/**
 * @brief 开奖计划
 * Created by PhpStorm.
 * Date: 2019/1/2
 * Time: 上午11:37
 */

defined('BASEPATH') OR exit('No direct script access allowed');

class Plan extends GC_Controller
{
    /**
     * @var object| Plan_model
     */
    public $plan;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Plan_model','plan');
    }

    /**
     * 设置长龙彩种数据
     */
    public function index($dsn)
    {
        if (!is_cli()) {
            header('HTTP/1.1 405 fuck u!');
            $this->return_json(E_METHOD, 'method nonsupport!');
        }
        if (empty($dsn)) {
            echo "请传入站点sn";
            die;
        }
        $this->plan->init($dsn);
        $this->plan->make_plan();
    }

    public function data($gid=0)
    {
        $ret = [];
        $this->plan->set_plan_gids()->get_plan_history($data);
        if ($gid) {
            $ret[$gid] = isset($data[$gid]) ? $data[$gid] : [];
        } else {
            $ret = $data;
        }
        $this->return_json(OK,$ret);
    }

    public function del($dsn)
    {
        if (!is_cli()) {
            header('HTTP/1.1 405 fuck u!');
            $this->return_json(E_METHOD, 'method nonsupport!');
        }
        if (empty($dsn)) {
            echo "请传入站点sn";
            die;
        }
        $this->plan->init($dsn);
        $this->plan->redis_del('plan_history');
    }





}
