<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Rebate_manage extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('MY_Model', 'core');
    }

    private $status = [
        1 => '已返代理',
        2 => '未返',
        3 => '挂起',
        4 => '已累加当期',
    ];

    /***********************************获取代理退佣设定*************************************/
    // 获取代理退佣设定列表
    public function getSetList()
    {
        // 获取搜索条件
        $page = array(
            'page' => $this->G('page'),
            'rows' => $this->G('rows'),
            'order' => $this->G('order'),
            'sort' => $this->G('sort'),
            'total' => -1,
        );
        $rs = $this->core->get_list('*', 'agent_level', array(), array(), $page);

        

        $this->return_json(OK, $rs);
    }

    // 新增修改代理退佣设定
    public function addSet()
    {
        $id = (int)$this->P('id');
        $name = $this->P('name');
        $profit_amount = $this->P('profit_amount');
        $bet_amount = $this->P('bet_amount');
        $user_sum = $this->P('user_sum');
        $rate = $this->P('rate');

        if (empty($name)) {
            $this->return_json(E_ARGS, 'Parameter is error');
        }

        $condition = ['bet_amount' => $bet_amount];
        if (!empty($id)) {
            $condition['id != '] = $id;
        }
        $rs = $this->core->get_one('*','agent_level',$condition);
        if (!empty($rs)) {
            $this->return_json(E_ALL, '有效打码量不能重复');
        }

        $data = array(
            'name' => $name,
            'profit_amount' => $profit_amount?$profit_amount:0,
            'bet_amount' => $bet_amount?$bet_amount:0,
            'user_sum' => $user_sum?$user_sum:0,
            'rate' => $rate,
            'status' => 1
        );
        $where = array();
        if (!empty($id)) {
            $where['id'] = $id;
        }
        $this->core->write('agent_level', $data, $where);
        // 记录操作日志
        $pre = !empty($id) ? '修改' : '新增';
        $this->load->model('log/Log_model');
        $this->Log_model->record($this->admin['id'], array('content' => "{$pre}'{$name}'代理退佣设定成功"));
        $this->return_json(OK, '执行成功');
    }

    // 获取设定明细
    public function getSetInfo()
    {
        $id = $this->G('id');
        if (empty($id)) {
            $this->return_json(E_DATA_EMPTY);
        }
        $arr = $this->core->get_one('*', 'agent_level', array('id' => $id));
        $this->return_json(OK, $arr);
    }

    // 更新设定状态
    public function updateSetStatus()
    {
        $id = $this->P('id');
        $status = $this->P('status');
        if (empty($id)) {
            $this->return_json(E_ARGS, 'Parameter is null');
        }
        $status = $status == 1 ? 0 : 1;
        $pre = $status == 1 ? '启用' : '停用';
        $this->core->write('agent_level', array('status' => $status), array('id' => $id));
        // 记录操作日志
        $this->load->model('log/Log_model');
        $this->Log_model->record($this->admin['id'], array('content' => "{$pre}了代理退佣设定ID为:{$id}"));
        $this->return_json(OK, '执行成功');
    }

    // 获取代理模式
    public function getRateType()
    {
        /**** 新站点获取 ****/
        // $arr = $this->core->get_one('rate_type', 'set', array('id' => 1));
        $arr = $this->core->get_gcset(['rate_type']);
        /**** end ****/
        $this->return_json(OK, $arr);
    }

    // 保存代理退佣设定模式
    public function saveRateType()
    {
        $rate_type = $this->P('rate_type');
        if (empty($rate_type)) {
            $this->return_json(E_ARGS, 'Parameter is null');
        }
        /**** 新站点配置 ****/
        $this->core->set_gcset(array('rate_type' => $rate_type));
        // $this->core->write('set', array('rate_type' => $rate_type), array('id' => 1));
        /**** end ****/
        
        // 记录操作日志
        $this->load->model('log/Log_model');
        $mode = $rate_type == 1 ? '总盈利退佣模式' : '有效打码量退佣模式';
        $this->Log_model->record($this->admin['id'], array('content' => "更新代理退佣设定为：{$mode}"));
        $this->return_json(OK, '执行成功');
    }

    /***********************************END代理退佣设定*************************************/

    /***********************************获取退佣查询****************************************/
    public function getSearchList()
    {
        // 接收数据
        $report_date = $this->G('report_date');
        $time_start = $this->G('time_start');
        $time_end = $this->G('time_end');
        $agent_id = $this->G('agent_id');
        $status = $this->G('status');
        // 获取搜索条件
        $page = array(
            'page' => $this->G('page'),
            'rows' => $this->G('rows'),
            'order' => $this->G('order'),
            'sort' => $this->G('sort'),
            'total' => -1,
        );
        // 精确条件
        $basic['agent_id'] = $agent_id;
        $basic['status'] = $status;
        if ($time_start || $time_end) {
            $basic['report_date >='] = $time_start;
            $basic['report_date <='] = $time_end;
        } elseif ($report_date) {
            $basic['report_date'] = $report_date;
        } else {
            $basic['report_date'] = date('Y-m-d', strtotime('-1 day'));
        }
        $senior = [
            'wherein' => array('status' => [1, 2])
        ];
        $rs = $this->core->get_list('*', 'agent_report', $basic, $senior, $page);
        if (!empty($rs['rows'])) {
            $bank_name = $this->core->get_list('uid,bank_name', 'user_detail', [], ['wherein' => array('uid' => array_column($rs['rows'],'agent_id'))], $page);
            foreach ($rs['rows'] as $k => &$v) {
                $v['status_name'] = $this->status[$v['status']];
                $v['username'] = $this->get_agent_name($v['agent_id']);
                if (empty($v['username'])) {
                    unset($rs['rows'][$k]);
                    continue;
                }
                foreach ($bank_name['rows'] as $item) {
                    $item['uid'] == $v['agent_id'] && $v['bank_name'] = $item['bank_name'];
                }
            }
        }
        /**** 格式化小数点 ****/
        $rs['rows'] = stript_float($rs['rows']);
        $rs['rows'] = array_values($rs['rows']);
        $this->return_json(OK, $rs);
    }
    /***********************************END退佣查询****************************************/

    /***********************************获取退佣统计****************************************/
    // 获取统计列表
    public function getCountList()
    {
        // 获取搜索条件
        $page = array(
            'page' => $this->G('page'),
            'rows' => $this->G('rows'),
            'order' => $this->G('order'),
            'sort' => $this->G('sort'),
            'total' => -1,
        );
        // 精确条件
        $fields = 'report_date,
                    COUNT(*) AS agent_num,
                    SUM(valid_user) total_user,SUM(now_price) as total_now_price,
                    SUM(IF(status = 1, rate_price, 0)) as rebate_price,
                    COUNT(IF(status = 1 && rate_price > 0, 1, NULL)) as rebate_num,
                    SUM(IF(status = 2, rate_price, 0)) as un_rebate_price,
                    COUNT(IF(status = 2 && rate_price > 0, 1, NULL)) as un_rebate_num';
        $senior = [
            'groupby' => array('report_date'),
            'wherein' => array('status' => [1, 2])
        ];
        $rs = $this->core->get_list($fields, 'agent_report', [], $senior, $page);
        /**** 格式化小数点 ****/
        $rs['rows'] = stript_float($rs['rows']);
        $this->return_json(OK, $rs);
    }
    /***********************************END退佣统计****************************************/

    /**
     * 根据代理id获取代理名：用redis
     * @param $id
     * @return string
     */
    private function get_agent_name($id)
    {
        $r = $this->core->user_cache($id);
        return isset($r['username']) ? $r['username'] : '';
    }
}