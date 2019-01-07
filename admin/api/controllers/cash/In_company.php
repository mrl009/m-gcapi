<?php
/**
 * @模块   现金系统／公司入款
 * @版本   Version 1.0.0
 * @日期   2017-03-30
 * shensiming
 */


defined('BASEPATH') or exit('No direct script access allowed');


class In_company extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('cash/In_company_model', 'core');
    }



    /******************公共方法*******************/
    /**
     * 获取公司入款数据列表
     */
    public function get_list()
    {
        $time_type = $this->G('time_type') ? (int)$this->G('time_type') : 1;
        $from_time = $time_type == 1 ? 'a.addtime >=' : 'a.update_time >=';
        $to_time = $time_type == 1 ? 'a.addtime <=' : 'a.update_time <=';
        $basic = array(
            'a.agent_id' => (int)$this->G('agent_id'),
            'a.from_way' => (int)$this->G('froms'),
            'a.order_num' => $this->G('f_ordernum'),
            'a.price >=' => (int)$this->G('price_start'),
            'a.price <=' => (int)$this->G('price_end'),
            $from_time => strtotime($this->G('time_start') . ' 00:00:00'),
            $to_time => strtotime($this->G('time_end') . ' 23:59:59'),
            'a.status' => (int)$this->G('status')
        );

        /*** 查询时间跨度不能超过两个月 ***/
        $diff_time = $basic[$to_time] - $basic[$from_time];
        if ($diff_time > ADMIN_QUERY_TIME_SPAN) {
            $basic[$to_time] = $basic[$from_time] + ADMIN_QUERY_TIME_SPAN;
        }
        /*** 特殊查询则取消时间限制：订单号 ***/
        if (!empty($basic['a.order_num'])) {
            unset($basic[$from_time]);
            unset($basic[$to_time]);
        }
        $is_first = $this->G('is_first');
        if (is_numeric($is_first)) {
            $basic['a.is_first'] = ($is_first == 0 ? "'0'" : $is_first);
        }
        $username = $this->G('f_username');
        if (!empty($username)) {
            $uid = $this->core->get_one('id', 'user', ['username'=>$username]);
            $basic['a.uid'] = empty($uid) ? '0' : $uid['id'];
        }


        $discount = $this->G('discount');
        if (is_numeric($discount) && $discount != 9) {
            $basic['a.is_discount'] = ($discount == 0 ? "'0'" : $discount);
        }
        //.按照操作者来搜索
        $admin = $this->G('f_admin');
        if (!empty($admin)) {
            $uid = $this->core->get_one('id', 'admin', ['username'=>$admin]);
            $basic['a.admin_id'] = empty($uid) ? '-1' : $uid['id'];
            //.不做单独条件
            if(empty($this->G('time_start'))&&empty($this->G('time_end'))){
                unset($basic[$from_time]);
                unset($basic[$to_time]);
            }
        }
        // 高级搜索
        $bank_card = $this->G('bank_card');
        if (!empty($bank_card)) {
            $senior['wherein'] = ['a.bank_card_id' => explode(',', $bank_card)];
        }
        $senior['join'] = [
            ['table' => 'admin as b','on' => 'b.id=a.admin_id']];
        $level_id = (int)$this->G('level_id');
        if ($level_id > 0) {
            $senior['join'][] = ['table' => 'user as c','on' => 'c.id=a.uid'];
            $basic['c.level_id ='] = $level_id;
        }
        //$senior['join'][] = ['table' => 'user_detail as d','on' => 'd.uid=a.uid'];
        // 分页，排序
        $page = (int)$this->G('page') > 0 ?
                (int)$this->G('page') : 1;
        $rows = (int)$this->G('rows') > 0 ?
                (int)$this->G('rows') : 50;
        if ($rows > 500 || $rows < 1) {
            $rows = 50;
        }
        $page   = array(
            'page'  => $page,
            'rows'  => $rows,
            'sort'  => $this->G('sort'),
            'order' => $this->G('order')
        );
        // 排序
        $sort_field = array('order_num', 'admin_id',
                            'bank_id', 'bank_style', 'bank_card_id',
                            'status', 'is_first', 'addtime',
                            'update_time', 'remark',
                            'price', 'total_price', 'discount_price');
        if (!in_array($page['sort'], $sort_field)) {
            $page['sort'] = 'id';
        }
        $this->load->model('cash/In_company_model', 'core');
        $this->load->model('cash/Cash_common_model', 'comm');
        // 每查询一次判断是否需要更新
        // edit by wuya 20180726 更新过期未处理入款订单 改到 定时任务里处理
        // $this->comm->update_online_status(1);
        // 查询数据
        $arr = $this->core->get_in_company($basic, $senior, $page);
        /*针对入款时间限制的一些处理*/
        $set = $this->M->get_gcset();
        $arr['rows'] = array_map(function ($v) use ($set){
            if ($set["income_time"] > 0) {
                $v['limit'] = time() - strtotime($v['update_time']) - $set["income_time"]*60;
            } else {
                $v['limit'] = 1;
            }
            return $v;
        }, $arr['rows']);
        $this->return_json(OK, $arr);
    }

    /**
     * 获取收账账号列表
     */
    public function get_banks()
    {
        $res['rows'] = $this->core->get_list(
            'id, card_num as name,card_username,bank_id', 'bank_card');
        $this->core->select_db('public');
        $bankArr = $this->core->get_all('*', 'bank');
        $this->core->select_db('private');
        $arrBank=[];
        foreach ($bankArr as $item) {
            $arrBank[$item['id']]['bank_name'] = $item['bank_name'];
            $arrBank[$item['id']]['is_qcode']  = $item['is_qcode'];
        }
        foreach ($res['rows'] as $k=>$row) {
            $res['rows'][$k]['bank_name'] = $arrBank[$row['bank_id']]['bank_name'];
        }
        array_unshift($res['rows'], array('id'=>0,'name'=>'全部'));
        $this->return_json(OK, $res);
    }

    public function bank_revoke()
    {
        $id = (int)$this->P('id');
        $where = [
            'id' => $id,
            'status' => 3,
        ];
       /* if ($this->admin['id'] != 1) {
            $this->return_json(E_ARGS,'你不是admin不能操作!');
        }*/
        $indata = $this->core->get_one('order_num,uid,addtime','cash_in_company' ,$where);
        if (empty($indata)) {
            $this->return_json(E_ARGS,'该订单号状态不能做此操作');
        }
        if ($indata['addtime']+86400 <= time()) {
            $this->return_json(E_ARGS,'只能操作24小时的订单');
        }

        $data = [
            'status' => 1,
            'admin_id' => $this->admin['id'],
            'update_time' => $_SERVER['REQUEST_TIME'],
            'remark' => '取消过的订单',
        ];
        $bool = $this->core->db->update('cash_in_company',$data,$where);
        if ($bool) {
            $this->load->model('log/Log_model');
            $logData['content'] = "将订单号{$indata['order_num']}改为未确认 uid:{$indata['uid']}";
            $this->Log_model->record($this->admin['id'], $logData);
            wlog(APPPATH.'logs/company_in_'.$this->core->sn.'_'.date('Ym').'.log', $logData['content']);

            $this->return_json(OK);
        }else{
            $this->return_json(E_ARGS);
        }
    }
    /**
     * 确认入款
     * @auther frank
     * @return bool
     **/
    public function in_company_do()
    {
        // 1、锁定状态
        // 2、修改入款状态
        // 3、加钱
        // 4、更新稽核
        // 5、加入现金记录
        $id = (int)$this->P('id');
        $status = (int)$this->P('status');
        if ($status==2) {
            $logData['content'] = 'ID'.$id.'-确认入款';
        } else {
            $status=3;
            $logData['content'] = 'ID'.$id.'-取消入款';
        }
        if ($id<=0) {
            $this->return_json(E_ARGS, '参数出错');
        }
        $msg = $this->core->move($id);////限制设置10时间内不允许操作
        if(!empty($msg)){
            $this->return_json(E_ARGS, $msg);
        }
        $remark = $this->P('remark');
        if (!empty($remark)) {
            $reg = '/^[\x{4e00}-\x{9fa5}a-zA-Z0-9\_\-]+$/u';
            $bool = preg_match($reg,$remark);
            if (!$bool) {
                $this->return_json(E_ARGS,"备注只能是汉字、字母、数字和下划线_及破折号-");
            }
        }
        $rkinCompanyLock = 'cash:lock:in:'.$id;
        $fbs = $this->core->fbs_lock($rkinCompanyLock);//加锁
        if (!$fbs) {
            $this->return_json(E_ARGS, '数据正在处理中');
        }
        $res = $this->core->handle_in($id, $status, $this->admin,$remark);
        $this->core->fbs_unlock($rkinCompanyLock);//解锁
        $this->load->model('log/Log_model');
        if ($res['status']) {
            $logData['content'] = $this->core->push_str .'id:'.$id;
            $this->Log_model->record($this->admin['id'], $logData);
            $this->return_json(OK);
        } else {
            $logData['content'] = $this->core->push_str. 'id:'. $id;
            $this->Log_model->record($this->admin['id'], $logData);
            $this->return_json(E_OK, $res['content']);
        }
    }


     /**
     * 
     * @desc 人工备注
     * @return bool
     **/
    public function remark_company_do()
    {
        $id = (int)$this->P('id');
        $remark = $this->P('remark');
        $admin = $this->admin;
        if (!empty($remark)) {
            $reg = '/^[\x{4e00}-\x{9fa5}a-zA-Z0-9\_\-]+$/u';
            $bool = preg_match($reg,$remark);
            if (!$bool) {
                $this->return_json(E_ARGS,"备注只能是汉字、字母、数字和下划线_及破折号-");
            }
        }
        $rkinCompanyLock = 'cash:lock:in:'.$id;
        $fbs = $this->core->fbs_lock($rkinCompanyLock);//加锁
        if (!$fbs) {
            $this->return_json(E_ARGS, '数据正在处理中');
        }
        $where['id'] = $id;
        $updateCashData['remark'] = $remark;
        $updateCashData['update_time'] = $_SERVER['REQUEST_TIME'];
        $updateCashData['admin_id'] = $admin['id'];
        $b = $this->core->write('cash_in_company', $updateCashData, $where);
        $this->core->fbs_unlock($rkinCompanyLock);//解锁
        if ($b) {
            $this->return_json(OK);
        } else {
            $this->return_json(E_OK, '编辑失败');
        }
    }


    /**
     * 确认退佣
    */
    public function agent_rebate()
    {
        $id    = $this->P('id');
        if (empty($id)) {
            $this->return_json(E_ARGS);
        }
        $idArr = explode(',', $id);
        $where = [
            'status' => '2',
            //'id' =>$id,
        ];
        $where2['wherein'] =[ 'id'=>$idArr];

        $ratePrice = $this->core->get_all('id,rate_price,agent_id,report_date ', 'agent_report', $where ,$where2);
        if (empty($ratePrice)) {
            $this->return_json(E_ARGS, '没有该条未退佣记录');
        }
        $this->load->model('cash/Cash_common_model', 'ccm');
        $this->load->model('log/Log_model');
        $this->ccm->db->trans_start();
        foreach ($ratePrice as $value) {
            if ($value['rate_price'] <=0) {
                continue;
            }
            $str = "{$value['report_date']} 代理返佣";

            $id   = $value['id'];
            $order = order_num(10, 0);
            $bool  = $this->ccm->db->update('agent_report', ['status' => 1 ], ['id' => $id]);
            if (!$bool) {
                $this->ccm->db->trans_rollback();
                $this->return_json(E_ARGS, '更改状态失败');
            }
            $bool = $this->ccm->update_banlace($value['agent_id'], $value['rate_price'], $order, 19, $str);
            if (!$bool) {
                $this->ccm->db->trans_rollback();
                $this->return_json(E_ARGS, '加钱失败');
            }

            // 记录操作日志
            $this->Log_model->record(
                $this->admin['id'],
                array(
                    'content' => "确认返佣成功，返佣金额：".$value['rate_price']."，期数：". $value['report_date']. "，代理账号：". $this->get_agent_name($value['agent_id'])
                )
            );
        }
        $this->ccm->db->trans_complete();
        $this->return_json(OK);
    }

    /**
     * 获取所有的公司入款银行数据列表
     */
    public function get_autolist(){
        /*精确筛选条件*/
        $basic = array(
            'pay_card_name'       => $this->P('pay_card_name'),//姓名
            'order_num'           => $this->P('order_num'),
            'pay_amount >='       => (int)$this->P('price_start'),
            'pay_amount <='       => (int)$this->P('price_end'),
            'pay_time >='         => strtotime($this->P('time_start').' 00:00:00'),
            'pay_time <='         => strtotime($this->P('time_end').' 23:59:59'),
            'status'              => $this->P('status')
        );

        /*** 查询时间跨度不能超过两个月 ***/
        $diff_time = $basic['pay_time <=']-$basic['pay_time >='];
        if ($diff_time > ADMIN_QUERY_TIME_SPAN) {
            $basic['pay_time <='] = $basic['pay_time >=']+ADMIN_QUERY_TIME_SPAN;
        }
        /*** 特殊查询则取消时间限制：订单号 ***/
        if (!empty($basic['order_num'])) {
            unset($basic['pay_time >=']);
            unset($basic['pay_time <=']);
        }
        // 分页，排序
        $page = (int)$this->G('page') > 0 ?
            (int)$this->G('page') : 1;
        $rows = (int)$this->G('rows') > 0 ?
            (int)$this->G('rows') : 50;
        if ($rows > 500 || $rows < 1) {
            $rows = 50;
        }
        $page   = array(
            'page'  => $page,
            'rows'  => $rows,
            'sort'  => $this->G('sort'),
            'order' => $this->G('order')
        );
        // 排序
        $sort_field = array('order_num,pay_card_name,pay_channel');
        if (!in_array($page['sort'], $sort_field)) {
            $page['sort'] = 'id';
        }
        $this->load->model('cash/In_company_model', 'core');
        // 查询数据
        $arr = $this->core->get_bankdata($basic , $page);
        $this->return_json(OK, $arr);
    }

    /*
     * 公司入款:对比银行数据后手动确认
     */
    public function compare_do(){

        /*---------company字段--------------*/

          $id      = (int)$this->P('id');//订单id
          $remark  = $this->P('remark');
        /*---------bank_auto字段-----------*/
          $aid     = (int)$this->P('aid');//id编号
        if (!empty($id)&& !empty($aid)) {
            if ($id<=0 ||$aid <=0) {
                $this->return_json(E_ARGS, '参数出错');
            }
            $msg = $this->core->move($id);////限制设置10时间内不允许操作
            if(!empty($msg)){
                $this->return_json(E_ARGS, $msg);
            }

            $status=2;
            $logData['content'] = 'ID'.$id.'-确认入款'.'bank_auto的ID'.$aid.'-确认入账';
            if (!empty($remark)) {
                $reg = '/^[\x{4e00}-\x{9fa5}a-zA-Z0-9\_\-]+$/u';
                $bool = preg_match($reg,$remark);
                if (!$bool) {
                    $this->return_json(E_ARGS,"备注只能是汉字、字母、数字和下划线_及破折号-");
                }
            }

            $rkinCompanyLock = 'cash:lock:in:'.$id;
            $atuoLock        = 'auto:lock:in:'.$aid;
            $fbs = $this->core->fbs_lock($rkinCompanyLock);//加锁
            $ybs = $this->core->fbs_lock($atuoLock);//bank_auto 表加锁
            if (!$fbs||!$ybs) {
                $this->return_json(E_ARGS, '数据正在处理中');
            }
            $res = $this->core->handle_in($id, $status, $this->admin, $remark, $aid);
            $this->core->fbs_unlock($rkinCompanyLock);//解锁
            $this->core->fbs_unlock($atuoLock);//解锁
            $this->load->model('log/Log_model');
           if ($res['status']) {
               $logData['content'] = $this->core->push_str .'id:'.$id.'bank_auto'.'aid:'. $aid;
               $this->Log_model->record($this->admin['id'], $logData);
               $this->return_json(OK);
            } else {
               $logData['content'] = $this->core->push_str. 'id:'. $id.'bank_auto'.'aid:'. $aid;
               $this->Log_model->record($this->admin['id'], $logData);
               $this->return_json(E_OK, $res['content']);
            }
        }
    }

    /*
     * 单独处理bankauto表
     */
    public function bank_sure(){
        $id = (int)$this->P('id');
        $status = (int)$this->P('status');
        if ($status==1) {
            $logData['content'] = 'ID'.$id.'-确认入款';
        } else {
            $status=3;
            $logData['content'] = 'ID'.$id.'-不做处理';
        }
        if ($id<=0) {
            $this->return_json(E_ARGS, '参数出错');
        }

        $remark = $this->P('remark');
        if (!empty($remark)) {
            $reg = '/^[\x{4e00}-\x{9fa5}a-zA-Z0-9\_\-]+$/u';
            $bool = preg_match($reg,$remark);
            if (!$bool) {
                $this->return_json(E_ARGS,"备注只能是汉字、字母、数字和下划线_及破折号-");
            }
        }
        $autoLock = 'auto:lock:in:'.$id;
        $fbs = $this->core->fbs_lock($autoLock);//加锁
        if (!$fbs) {
            $this->return_json(E_ARGS, '数据正在处理中');
        }
        $res = $this->core->make_bank($id, $status, $this->admin,$remark);
        $this->core->fbs_unlock($autoLock);//解锁
        $this->load->model('log/Log_model');
        if ($res['status']) {
            $logData['content'] = $this->core->push_str .'bank_auto表id:'.$id;
            $this->Log_model->record($this->admin['id'], $logData);
            $this->return_json(OK);
        } else {
            $logData['content'] = $this->core->push_str. 'bank_auto表id:'. $id;
            $this->Log_model->record($this->admin['id'], $logData);
            $this->return_json(E_OK, $res['content']);
        }

    }

    /******************公共方法*******************/
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
