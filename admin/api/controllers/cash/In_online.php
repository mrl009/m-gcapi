<?php
/**
 * @模块   现金系统／线上入款
 * @版本   Version 1.0.0
 * @日期   2017-03-30
 * shensiming
 */

defined('BASEPATH') or exit('No direct script access allowed');


class In_online extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('cash/In_online_model', 'core');
    }


    /******************公共方法*******************/
    /**
     * 获取线上入款数据列表
     */
    public function get_list()
    {
        //精确条件
        $time_type = $this->G('time_type') ? (int)$this->G('time_type') : 1;
        $from_time = $time_type == 1 ? 'a.addtime >=' : 'a.update_time >=';
        $to_time = $time_type == 1 ? 'a.addtime <=' : 'a.update_time <=';
        $basic = array(
            'a.agent_id' => (int)$this->G('agent_id'),
            'a.pay_id'   => (int)$this->G('payId'),
            'a.from_way' => (int)$this->G('froms'),
            'a.order_num'=> $this->G('f_ordernum'),
            'a.price >=' => (int)$this->G('price_start'),
            'a.price <=' => (int)$this->G('price_end'),
            $from_time => strtotime($this->G('time_start').' 00:00:00'),
            $to_time => strtotime($this->G('time_end').' 23:59:59'),
            'a.online_id' => (int)$this->G('online_id'),
            'a.status'     => (int)$this->G('status')
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
        $discount = $this->G('discount');
        if (is_numeric($discount) && $discount != 9) {
            $basic['a.is_discount'] = ($discount == 0 ? "'0'" : $discount);
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

        //.按照操作者来搜索
        $admin = $this->G('f_admin');
        if (!empty($admin)) {
            $uid = $this->core->get_one('id', 'admin', ['username'=>$admin]);
            $basic['a.admin_id'] = empty($uid) ? '-1' : $uid['id'];
            //.不做单独条件
            if (empty($this->G('time_start')) && empty($this->G('time_end'))) {
                unset($basic[$from_time]);
                unset($basic[$to_time]);
            }
        }
        // 高级搜索
        $senior['join'] = [
            ['table' => 'admin as b','on' => 'b.id=a.admin_id']];
        $level_id = (int)$this->G('level_id');
        if ($level_id > 0) {
            $senior['join'][] = ['table' => 'user as c','on' => 'c.id=a.uid'];
            $basic['c.level_id ='] = $level_id;
        }

        // 分页
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
        $sort_field = array('order_num','admin_id',
                        'pay_id', 'status',
                        'is_first', 'addtime',
                        'update_time', 'is_discount',
                        'from_way', 'remark',
                        'price', 'total_price',
                        'discount_price');
        if (!in_array($page['sort'], $sort_field)) {
            $page['sort'] = 'id';
        }
        $this->load->model('cash/Cash_common_model', 'comm');
        //先更新过期订单
        // edit by wuya 20180726 更新过期未处理入款订单 改到 定时任务里处理
        // $this->comm->update_online_status(2);
        $arr = $this->core->get_in_online($basic, $senior, $page);
        $this->return_json(OK, $arr);
    }

    /**
     * 获取入款商户列表
     */
    public function get_banks()
    {
        $this->core->select_db('public');
        $res['rows'] = $this->core->get_list('id, online_bank_name as name', 'gc_bank_online');
        array_unshift($res['rows'], array('id'=>0,'name'=>'全部'));
        $this->return_json(OK, $res);
    }

    /**
     * 线上入款不在提醒
     */
    public function ignore()
    {
        $id = $this->P('id');
        if ($id <=0) {
            $this->return_json(E_ARGS, '参数错误');
        }
        $bool = $this->core->db->where_in('status',[1,4])->update('cash_in_online', ['status' => 3 ,'update_time' => time() ], ['id'=>$id]);
        if ($bool) {
            $this->return_json(OK);
        } else {
            $this->return_json(E_OK);
        }
    }

    /**
     * 线上入款掉单处理
     */
    public function online_doing()
    {
        $this->load->model('cash/Cash_common_model', 'comm');
        $id = $this->P('id');
        if ($id <=0) {
            $this->return_json(E_ARGS, '参数错误');
        }

        $admin = $this->admin;

        $data = $this->core->order_detail($id);
        if (empty($data)) {
            $this->return_json(E_ARGS);
        }
        $bool  = $this->comm->check_in_or_out($admin['id'], $admin['max_credit_out_in'], $data['price']);
        if ($bool !== true) {
            $this->return_json(E_OP_FAIL, '操作失败，你的操作额度不够');
        }
        $lock = "temp:online:".$data['order_num'];//加锁
        $bool = $this->comm->fbs_lock($lock);
        if (!$bool) {
            //$this->comm->fbs_unlock($lock);
            $this->return_json(E_OP_FAIL, '已经有人在操作了');
        }
        if ($data['status'] == 2) {
            $this->comm->fbs_unlock($lock);
            $this->return_json(E_OP_FAIL, '已入款');
        }

        // 开始事务
        $this->comm->db->trans_start();
        $data['max_income_price']>0?$first=0:$first=1;
        $updata = [
            'status'   => 2,
            'is_first' => $first,
            'admin_id' => $this->admin['id'],
            'update_time'   => $_SERVER['REQUEST_TIME']
        ];
        $bool = $this->comm->db->where_in('status',[1,4])->update('cash_in_online', $updata, ['id' => $id]);
        if (!$bool) {
            $this->comm->db->trans_rollback();
            $this->comm->fbs_unlock($lock);
            $this->return_json(E_OP_FAIL,'更新线上入款表失败');
        }

        //11.21加钱前先算好等级和积分，增加存款用户积分及晋级等级信息
        $set = $this->comm->get_gcset(['sys_activity']);
        if (in_array(1, explode(',', $set['sys_activity']))) {
            $this->load->model('Grade_mechanism_model');
            $gradeInfo = $this->Grade_mechanism_model->grade_doing($data['uid'], $data['total_price']);
            if (empty($gradeInfo['integral']) && empty($gradeInfo['vip_id'])) {
                $this->comm->db->trans_rollback();
                $this->comm->fbs_unlock($lock);
                $this->return_json(E_ARGS, '操作失败5');
            }
        } else {
            $gradeInfo = ['integral' => 0, 'vip_id' => 0];
        }
        $strpay = code_pay($data['pay_code']).'支付';
        if ($data['discount_price'] > 0) {
            //线上入款含优惠 此时写入流水表两条记录，一条存款金额，一条优惠金额
            $type = 5;
            // 写充值金额
            $bool = $this->comm->update_banlace($data['uid'], $data['price'], $data['order_num'], $type, $strpay, $data['price'], $gradeInfo['integral'], $gradeInfo['vip_id']);
            $type1 = 11;//优惠活动
            $remark = '线上入款-存款优惠';
            // 写优惠金额
            $bool1 = $this->comm->update_banlace($data['uid'], $data['discount_price'], $data['order_num'], $type1, $remark);
            $bool = $bool && $bool1;
        } else {
            $type = 7;
            $bool = $this->comm->update_banlace($data['uid'], $data['price'], $data['order_num'], $type, $strpay, $data['price'], $gradeInfo['integral'], $gradeInfo['vip_id']);
        }

        if (!$bool) {
            $this->comm->db->trans_rollback();
            $this->comm->fbs_unlock($lock);
            $this->return_json(E_ARGS, '操作失败1');
        }
        //查询稽核
        $bool = $this->comm->check_and_set_auth($data['uid']);
        if (!$bool) {
            $this->comm->db->trans_rollback();
            $this->comm->fbs_unlock($lock);
            $this->return_json(E_ARGS, '操作失败2');
        }
        //写入稽核
        $bool = $this->comm->set_user_auth($data['uid'], $data, 2);
        if ($bool['status'] == false) {
            $this->comm->fbs_unlock($lock);
            $this->comm->db->trans_rollback();
            $this->return_json(E_ARGS, '操作失败3');
        }
        //首存

        //写入现金报表
        $cashData['in_online_total'] = $data['price'];
        $cashData['in_online_discount'] = $data['discount_price'];
        if ($data['discount_price']  > 0) {
            $cashData['in_online_discount_num'] = 1;
        }
        $cashData['in_online_num'] = 1;
        $cashData['agent_id']      = $data['agent_id'];
        //8.30 添加首存标记
        $cashData['is_one_pay']    = $first;
        $this->load->model('cash/Report_model');
        $report_date = date('Y-m-d', $_SERVER['REQUEST_TIME']);
        $bool = $this->Report_model->collect_cash_report($data['uid'], $report_date, $cashData);
        if ($bool == false) {
            $this->comm->db->trans_rollback();
            $this->comm->fbs_unlock($lock);
            $this->return_json(E_ARGS, '操作失败4');
        }


        $bool = $this->comm->incre_level_use($data['price'], $data['level_id']);
        if (!$bool) {
            $this->comm->db->trans_rollback();
            $this->comm->fbs_unlock($lock);
            $this->return_json(E_OK);
        } else {
            $this->comm->db->trans_complete();
            $x="成功";
            $this->load->model('log/Log_model');
            $logData['content'] = "确认会员{$data['username']}的线上入款,订单号:{$data['order_num']}  状态$x";//内容自己对应好
            wlog(APPPATH.'logs/online_in2_'.$this->comm->sn.'_'.date('Ym').'.log', "管理员{$this->admin['username']}确认会员{$data['username']}线上入款,订单号:{$data['order_num']}  金额{$data['price']} 优惠{$data['discount_price']}");
            $this->Log_model->record($this->admin['id'], $logData);
            $this->comm->cash_company($data['online_id'], $data['price'], 2);
            $this->comm->fbs_unlock($lock);
            //$this->push(MQ_ONLINE_RECHARGE, $strpay.'管理员'.$this->admin['username'].'确认线上入款');
            $this->return_json(OK);
        }
    }


    /********************************************/
}
