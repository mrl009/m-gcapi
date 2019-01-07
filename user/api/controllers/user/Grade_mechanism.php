<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Grade_mechanism控制器
 *
 * @author      lss
 * @package     controllers
 * @version     v1.0 2017/11/3
 */
class Grade_mechanism extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('user/Grade_mechanism_model', 'core');
    }


    /**
     * 获取全部数据
     *
     * @access public
     * @return Array
     */
    public function all()
    {
        $result = $this->core->all();
        $this->return_json(OK, ['rows' => $result]);
    }

    /**
     * 获取用户当前等级信息、未领取的奖励金额
     */
    public function getUserGrade()
    {
        $uid = $this->user['id'];
        if (empty($uid)) {
            $this->return_json(E_DENY, '没有登陆');
        }
        $rs = $this->core->getUserGrade($uid);
        $this->return_json($rs['status'], $rs['msg']);
    }

    /**
     * 是否有晋级奖励
     */
    public function isReward()
    {
        $uid = $this->user['id'];
        if (empty($uid)) {
            $this->return_json(E_DENY, '没有登陆');
        }
        $rs = $this->core->isReward($uid);
        $this->return_json(OK, $rs);
    }

    /**
     * 用户领取晋级奖励操作
     * @step 加钱
     * @step 更新领取状态
     * @step 更新现金流水
     * @step 更新现金报表
     */
    public function rewardDo()
    {
        $uid = $this->user['id'];
        if (empty($uid)) {
            $this->return_json(E_DENY, '没有登陆');
        }
        $set = $this->core->get_gcset(['sys_activity']);
        if (!in_array(1, explode(',', $set['sys_activity']))) {
            $this->return_json(E_OP_FAIL, '晋级奖励领取已关闭');
        }
        $rewardInfo = $this->core->get_list('*', 'promotion_detail', array('uid' => $uid, 'is_receive' => 1));
        /*判断该会员是否被晋级奖励拉入黑名单*/
        $userRewoard = $this->core->get_one('*','activity_blacklist',array('id'=>$uid));
        if(in_array('1',explode(',',$userRewoard['activity_id']))){
            $this->return_json(E_OP_FAIL, '您的账号暂时无法领取奖励，请联系客服进行咨询！');
        }
        if (empty($rewardInfo)) {
            $this->return_json(E_DATA_EMPTY, '没有可领取的晋级奖励');
        }

        $lock = 'reward:lock:uid:' . $uid;
        $fbs = $this->core->fbs_lock($lock);//加锁
        if (!$fbs) {
            $this->return_json(E_ARGS, '数据正在处理中');
        }
        $amount = 0;
        foreach ($rewardInfo as $v) {
            $amount += isset($v['jj_money']) ? $v['jj_money'] : 0;
        }
        if ($amount == 0) {
            $this->core->fbs_unlock($lock);
            $this->return_json(E_DATA_EMPTY, '没有可领取的晋级奖励');
        }
        // 开启事务
        $this->core->db->trans_start();
        // 加钱
        $bool = $this->core->update_banlace($uid, $amount, order_num(10, 1), 20, '晋级奖励', $amount);
        if (!$bool) {
            $this->core->db->trans_rollback();
            $this->core->fbs_unlock($lock);
            $this->return_json(E_ARGS, '加钱失败');
        }
        // 更新领取状态
        $bool = $this->core->updateReceive($uid);
        if (!$bool) {
            $this->core->db->trans_rollback();
            $this->core->fbs_unlock($lock);
            $this->return_json(E_ARGS, '更新领取状态失败');
        }
        // 更新现金报表
        $cashData = [
            'activity_total' => $amount,
            'activity_num' => 1
        ];
        $report_date = date('Y-m-d');
        $this->load->model('Comm_model');
        $bool = $this->Comm_model->collect_cash_report($uid, $report_date, $cashData);
        if (!$bool) {
            $this->core->db->trans_rollback();
            $this->core->fbs_unlock($lock);
            $this->return_json(E_ARGS, '更新现金报表失败');
        } else {
            $this->core->fbs_unlock($lock);
            $this->core->db->trans_complete();
            $this->return_json(OK);
        }
    }
}