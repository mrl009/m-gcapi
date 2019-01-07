<?php

/**
 * Created by PhpStorm.
 * User: mr.xiaolin
 * Date: 2018/5/22
 * Time: 下午3:10
 */
class Reward_day extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Reward_day_model', 'core');
    }

    /**
     * 获取加奖信息
     */
    public function reward_info()
    {
        $uid = $this->user['id'];
        if (empty($uid)) {
            $this->return_json(E_DENY, '没有登陆');
        }
        $rs = $this->core->reward_info($uid);
        $this->return_json(OK, $rs);
    }

    /**
     * 用户领取每日加奖奖励操作
     * @step 加钱
     * @step 更新领取状态
     * @step 更新现金流水
     * @step 更新现金报表
     */
    public function reward_do()
    {
        $uid = $this->user['id'];
        $day = date('Y-m-d', strtotime('-1 day'));
        if (empty($uid)) {
            $this->return_json(E_DENY, '没有登陆');
        }
        /*判断该会员是否被每日奖励拉入黑名单*/
        $userRewoard = $this->core->get_one('*','activity_blacklist',array('id'=>$uid));
        if(in_array('2',explode(',',$userRewoard['activity_id']))){
            $this->return_json(E_OP_FAIL, '您的账号暂时无法领取奖励，请联系客服进行咨询！');
        }
        $where = array('uid' => $uid, 'status' => 1, 'reward_date' => $day);
        $rewardInfo = $this->core->get_one('*', 'reward_day_log', $where);
        if (empty($rewardInfo)) {
            $this->return_json(E_DATA_EMPTY, '没有可领取的加奖奖励');
        }

        $lock = 'reward_day:lock:uid:' . $uid;
        $fbs = $this->core->fbs_lock($lock);//加锁
        if (!$fbs) {
            $this->return_json(E_ARGS, '数据正在处理中');
        }
        // 开启事务
        $this->core->db->trans_start();
        // 加钱
        $bool = $this->core->update_banlace($uid, $rewardInfo['money'], order_num(10, 1), 22, '加奖奖励', $rewardInfo['money']);
        if (!$bool) {
            $this->core->db->trans_rollback();
            $this->core->fbs_unlock($lock);
            $this->return_json(E_ARGS, '加钱失败');
        }
        // 更新领取状态
        $bool = $this->core->update_status($where);
        if (!$bool) {
            $this->core->db->trans_rollback();
            $this->core->fbs_unlock($lock);
            $this->return_json(E_ARGS, '更新领取状态失败');
        }
        // 更新现金报表
        $cashData = [
            'activity_total' => $rewardInfo['money'],
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