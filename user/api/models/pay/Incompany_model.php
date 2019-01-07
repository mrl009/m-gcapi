<?php
/**
 * @模块   现金系统／公司入款model
 * @版本   Version 1.0.0
 * @日期   2017-03-30
 * shensiming
 */

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Incompany_model extends GC_Model
{
    public $push_str = "";//消息队列提示信息
    public $st = 3;//取值开始时间小时
    public $et = 1;//取值截止时间小时
    public $b_s = 3;//bank_auto取值开始时间小时
    public $b_e = 2;//bank_auto取值截止时间小时
    public function __construct()
    {
        parent::__construct();
        $this->select_db('private');
    }

    /******************公共方法*******************/

    /**
     * 公司入款，取消入款
     */
    public function handle_in($aid=0,$id=0, $status=2,$remark=null)
    {
        // 2、修改入款状态
        // 3、加钱
        // 4、写入现金记录
        // 5、更新稽核
        // 6、增加稽核
        // 7、汇总现金报表-平台额度

        $ci = get_instance();
        $field='uid,agent_id,total_price,price,discount_price,order_num,confirm,bank_card_id';
        $where['id'] = $id;
        $where['status'] = 1;
        $inData = $this->get_one($field, 'cash_in_company', $where);
        if (empty($inData)) {
            $res['status'] = false;
            $res['content'] = '操作失败，操作状态不允许';
            return $res;
        }

        // 开启事务
        $this->db->trans_start();
        $this->load->model('pay/Pay_set_model', 'ps');
        $payData = $this->ps->get_pay_set($inData['uid'], 'ps.pay_set_content,user.level_id,user.max_income_price');//获取一个会员的支付设定信息
        $auth_dml = 0;
        if (empty($payData)) {
            $this->db->trans_rollback();
            $res['status'] = false;
            $res['content'] = '更取不到支付设置，请联系管理员';
            return $res;
        }

        $first = 0;
        if($status == 2 && $payData['max_income_price'] <=0){
            $updateCashData['is_first'] = 1;
            $first = 1;
        }
        $admin['id'] =0;//系统入款id
        $admin['name'] = '系统';
        $updateCashData['remark'] = $remark;
        $updateCashData['status'] = $status;
        $updateCashData['update_time'] = $_SERVER['REQUEST_TIME'];
        $updateCashData['admin_id'] = $admin['id'];
        $b1 = $this->write('cash_in_company', $updateCashData, $where);// 2、修改入款状态
        $a_where['id'] = $aid;
        $updateAutoData['status'] =1;//改为确认入款状态
        $updateAutoData['uid'] =$inData['uid'];//改为确认入款状态
        $updateAutoData['order_num'] =$inData['order_num'];//订单号
        $updateAutoData['updated'] = $_SERVER['REQUEST_TIME'];

        $o1 = $this->write('bank_auto', $updateAutoData, $a_where);// bank_auto状态
        if (!$b1 || !$o1) {
            // echo '更新状态出错';
            $this->db->trans_rollback();
            $res['status'] = false;
            $res['content'] = '更新状态出错';
            return $res;
        }
        if ($status==3) {
            /*如果为取消，下面代码都不需要处理*/
            $this->db->trans_commit();//操作成功
            $this->del_in_lock($inData['uid'],$inData['order_num']);
            if (!empty($inData['confirm'])) {
                $this->del_confirm($inData['confirm']);
            }
            $this->push_str = "管理员{$admin['name']}取消公司入款";
            wlog(APPPATH.'logs/company_in_'.$this->sn.'_'.date('Ym').'.log', "取消公司入款{$inData['price']},优惠{$inData['discount_price']},订单号{$inData['order_num']}");
            $ci->push(MQ_COMPANY_RECHARGE, $this->push_str,$inData['order_num']);

            $res['status'] = true;
            $res['content'] = '取消出款成功';
            return $res;
        }

        //11.21加钱前先算好等级和积分，增加存款用户积分及晋级等级信息
        $set = $this->get_gcset(['sys_activity']);
        if (in_array(1, explode(',', $set['sys_activity']))) {
            $gradeInfo = $this->grade_doing($inData['uid'], $inData['total_price']);
            if (empty($gradeInfo['integral']) && empty($gradeInfo['vip_id'])) {
                $this->db->trans_rollback();
                $res['status'] = false;
                $res['content'] = '修改晋级信息失败';
                return $res;
            }
        }
        empty($inData['discount_price'])?$type = 8:$type=6;
        /*加钱，写入现金记录*/

        $b2 = $this->update_banlace($inData['uid'], $inData['total_price'], $inData['order_num'], $type, '公司入款', $inData['price'], $gradeInfo['integral'], $gradeInfo['vip_id']);
        if (!$b2) {
            $this->db->trans_rollback();
            $res['status'] = false;
            $res['content'] = '加钱失败';
            return $res;
        }

        $limit_del = 0;
        if ($payData['line_is_ct_audit']) {
            $auth_dml  = $inData['total_price'] * $payData['line_ct_audit']/100;
            $limit_del = $payData['line_ct_fk_audit'];
        }
        $b3 = $this->check_and_set_auth($inData['uid']);// 5、更新稽核
        if (!$b3) {
            $this->db->trans_rollback();
            $res['status'] = false;
            $res['content'] = '稽核不成功';
            return $res;
        }
        $is_pass = 0;
        if (empty($auth_dml)) {
            $is_pass  =1;
        }
        $authData['uid'] = $inData['uid'];
        $authData['total_price'] = $inData['price'];
        $authData['discount_price'] = $inData['discount_price'];
        $authData['auth_dml'] = $auth_dml;
        $authData['limit_dml'] = $limit_del;
        $authData['start_time'] = $_SERVER['REQUEST_TIME'];
        $authData['is_pass'] = $is_pass;
        $authData['type'] = 1;
        $b4 = $this->write('auth', $authData);// 6、增加稽核
        if (!$b4) {
            $this->db->trans_rollback();
            $res['status'] = false;
            $res['content'] = '写入稽核日志失败';
            return $res;
        }
        /*7、汇总现金报表，计算平台额度*/
        $cashData['in_company_total'] = $inData['price'];
        $cashData['in_company_discount'] = $inData['discount_price'];
        if ($inData['discount_price'] > 0) {
            $cashData['in_company_discount_num'] = 1;
        }
        $cashData['in_company_num'] = 1;
        $cashData['agent_id'] = $inData['agent_id'];
        $cashData['is_one_pay'] = $first;

        $uid = $inData['uid'];
        //$report_date = date('Y-m-d');
        $report_date = date('Y-m-d', $_SERVER['REQUEST_TIME']);
        //添加首存标记
        $b5 = $this->collect_cash_report($uid, $report_date, $cashData);
        if (!$b5) {
            $this->db->trans_rollback();
            $res['status'] = false;
            $res['content'] = '收集现金报表失败';
            return $res;
        }
        $b6 = $this->incre_level_use($inData['total_price'], $payData['level_id']);
        if (!$b6) {
            $this->db->trans_rollback();
            $res['status'] = false;
            $res['content'] = '层级金额增加失败';
            return $res;
        }

        $this->db->trans_commit();//入款成功
        $this->del_in_lock($inData['uid'],$inData['order_num']);
        if (!empty($inData['confirm'])) {
            $this->del_confirm($inData['confirm']);
        }
        $this->cash_company($inData['bank_card_id'], $inData['price'], 1);
        $res['status'] = true;
        $this->push_str = "管理员{$admin['name']}确认公司入款";
        wlog(APPPATH.'logs/company_in_'.$this->sn.'_'.date('Ym').'.log', "公司入款{$inData['price']},优惠{$inData['discount_price']},订单号{$inData['order_num']},uid:$uid");
        $ci->push(MQ_COMPANY_RECHARGE, $this->push_str,$inData['order_num']);

        $res['content'] = '入款成功!';
        return $res;
    }
    /********************************************/
    /**
     *
     * 删除确认码
    */
    private function del_confirm($confirm)
    {
        $kes = 'temp:creat_confirm:'.$confirm;
        $this->redis_del($kes);
    }
    /**
     * 删除入款的key
     *
     * @param int $id 用户id
     * @param string $order_num 入款订单号
     *
    */
    private function del_in_lock($id, $order_num='')
    {
        $kye = 'user:in_company:'.$id;
        $arr = $this->redis_get($kye);
        $arr = json_decode($arr, true);
        if (empty($arr)) {
            return false;
        }
        foreach ($arr as $key => $value) {
            if ($order_num == $value['order_num']) {
                unset($arr[$key]);
                break;
            }
        }
        if (empty($arr) || count($arr) == 0) {
            return $this->redis_del($kye);
        } else {
            $gcSet = $this->get_gcset(['incompany_timeout']);
            $expire = $gcSet['incompany_timeout']*60;
            $this->redis_set($kye, json_encode($arr));
            $this->redis_EXPIRE($kye, $expire);
            return true;
        }
    }

    /*****************公司入款***************************/

    /**
     * "online:erro:";//线上入款错误记录
     * @param $id 线上支付的id
     * @param $str 错误信息
     */
    public function online_erro($id, $str)
    {
        $reidsKey = "incompany:erro:";
        $this->redis_select(4);
        $this->redis_setex($reidsKey.$id, 90000, $str);
        $this->redis_select(5);
    }
    /**
     *
     * 获取到支持银行 和 公司入款的的基本信息
     * @param $name  string 要获取的东西 bank || bank_incompany
     * @param $id  int 获取单条 || array where 条件
     * @return $arr  array
     */
    protected function base_incom_online($name = 'bank',$id=null,$str='*')
    {
        $this->select_db('public');
        if(!empty($id) && !is_array($id)){
            $wher = [
                'status' => 1,
                'id'     => $id,
            ];
            $arr  = $this->get_one($str,$name,$wher);
            return $arr;
        }
        $where = ['status'=>1];
        if (is_array($id)) {
            $where =  $id;
        }
        $arr = $this->get_all($str,$name,$where);
        $this->select_db('private');
        $temp = [];
        foreach ($arr as $k => $item) {
            $temp[$item['id']] = $item;
        }
        $this->select_db('private');//为什么要select两次private？
        return $temp;
    }

    /**
     * @param $value
     * @return mixed
     */
    public function get_card($value){
       return $this->db->get_where('gc_bank_card', ['id'=>$value['bank_card_id']])->row_array();
    }
    /**
     * 获取bank_auto表银行的数据
     * @param $uname
     * @param $addtime
     *
     * @return mixed
     */
    public function auto_list($uname,$addtime){
        $tarr ['status'] = 0;
        $tarr ['pay_card_name'] = $uname;
        $starttime = date('Y-m-d H:i:s',($addtime-$this->b_s*3600));
        $endtime = date('Y-m-d H:i:s',($addtime + $this->b_e*3600));
        $tarr['pay_time>='] = $starttime;
        $tarr['pay_time<='] = $endtime;
        $flid = 'id,uid,pay_card_name,card_num,pay_card_name,pay_amount,pay_time';
        $paydata = $this->db->select($flid)->get_where('gc_bank_auto', $tarr)->result_array();
        if(!empty($paydata)){
            reset($paydata);
            $end = current($paydata);//最后一条数据
            $first = end($paydata);//第一条数据
            //将每次取得数据写入日志中
            $this->load->model('log/Log_model');
            $logData['content'] = 'gc_bank_auto,本次从pay_time:'.$first['pay_time'].'到'.$end['pay_time'].'共取值'.count($paydata).'条订单id从'.$first['id'].'到'.$end['id'];
            $this->Log_model->record($this->admin['id'], $logData);
        }
        return $paydata;
    }

    /**获取从cash_in_company表取数据
     * @return mixed
     */
    public function get_paydata(){
        $field='id,uid,name,price,bank_id,bank_card_id,addtime';
        $starttime =  time()-$this->st*3600;
        $endtime   =  time() + $this->et*3600;
        $tarr['addtime>='] = $starttime;
        $tarr['addtime<='] = $endtime;
        $tarr['status'] = 1;
        $arr=  $this->db->select($field)->get_where('cash_in_company',$tarr)->result_array();
        if(!empty($arr)){
            reset($arr);
            $end = current($arr);//最后一条数据
            $first = end($arr);//第一条数据
            //将每次取得数据写入日志中
            $this->load->model('log/Log_model');
            $logData['content'] = '从cash_in_company表取数据,本次从addtime:'.$first['addtime'].'到'.$end['addtime'].'共取值'.count($arr).'条订单id从'.$first['id'].'到'.$end['id'];
            $this->Log_model->record($this->admin['id'], $logData);
        }
        return $arr;
    }
    /**
     * 线上入款、公司入款、人工入款确认会员等级晋级相关操作
     * 会员加积分、改等级、写晋级奖励记录
     * @param $uid int 用户ID
     * @param $integral int 要增加的积分
     * @return array ['integral' => 0, 'vip_id' => 0] 积分&等级
     */
    public function grade_doing($uid, $integral)
    {
        $res = ['integral' => 0, 'vip_id' => 0];
        if (empty($uid) || empty($integral)) {
            return $res;
        }
        // 取整
        $integral = intval($integral);
        // 获取用户当前等级积分信息
        $userInfo = $this->get_one('id,integral,vip_id', 'user', array('id' => $uid, 'status' => 1));
        if (empty($userInfo)) {
            return $res;
        }
        // 获取等级机制
        $gradeMechanism = $this->get_list('*', 'grade_mechanism', array('status' => 1), array('orderby' => array('integral' => 'desc')));
        if (empty($gradeMechanism)) {
            return $res;
        }
        $gradeInfo = [];
        foreach ($gradeMechanism as $v) {
            if ($integral + $userInfo['integral'] >= $v['integral']) {
                $gradeInfo = $v;
                break;
            }
        }
        if (empty($gradeInfo)) {
            // 没达到最低等级直接去加积分
            return ['integral' => $integral, 'vip_id' => $userInfo['vip_id']];
        }
        if ($userInfo['vip_id'] == $gradeInfo['id']) {
            // 没有晋级直接加积分
            return ['integral' => $integral, 'vip_id' => $userInfo['vip_id']];
        } else if ($userInfo['vip_id'] + 1 == $gradeInfo['id']) {
            // 晋级没有跳级
            $promotionData = [
                'uid' => $uid,
                'before_id' => $userInfo['vip_id'],
                'grade_id' => $gradeInfo['id'],
                'jj_money' => $gradeInfo['jj_money'],
                'integral' => $userInfo['integral'] + $integral,
                'is_tj' => 1,
                'is_receive' => 1,
                'add_time' => time(),
            ];
            $flag = $this->write('promotion_detail', $promotionData);
            return $flag == false ? $res : array('integral' => $integral, 'vip_id' => $gradeInfo['id']);
        } else if ($userInfo['vip_id'] + 1 < $gradeInfo['id']) {
            $beforeGrade = [];
            foreach ($gradeMechanism as $v) {
                if ($userInfo['vip_id'] == $v['id']) {
                    $beforeGrade = $v;
                    break;
                }
            }
            if (empty($beforeGrade)) {
                return $res;
            }
            // 晋级跳级
            $promotionData = [
                'uid' => $uid,
                'before_id' => $userInfo['vip_id'],
                'grade_id' => $gradeInfo['id'],
                'jj_money' => (int)$gradeInfo['tj_money'] - (int)$beforeGrade['tj_money'],
                'integral' => $userInfo['integral'] + $integral,
                'is_tj' => 2,
                'is_receive' => 1,
                'add_time' => time(),
            ];
            $flag = $this->write('promotion_detail', $promotionData);
            return $flag == false ? $res : array('integral' => $integral, 'vip_id' => $gradeInfo['id']);
        }
        return $res;
    }

    /**
     * 收集，汇总cash_report表数据
     * @param $uid  int 用户ID
     * @param $report_date  string 日期
     * @param $data  array 数据
     * @return bool
     */
    public function collect_cash_report($uid, $report_date, $data)
    {
        $where['uid'] = $uid;
        $where['report_date'] = $report_date;
        $if_one = $this->get_one('id', 'cash_report', $where);

        if (!$if_one) {
            /*没有记录，则为插入*/
            $data['uid'] = $uid;
            $data['report_date'] = $report_date;
            $b = $this->write('cash_report', $data);

            if ($b) {
                return true;
            } else {
                return false;
            }
        }
        /*有记录，则为更新*/

        /*公司入款*/
        if (isset($data['in_company_total']) && isset($data['in_company_discount'])) {
            $this->db->set('in_company_total', 'in_company_total+'.$data['in_company_total'], false);
            $this->db->set('in_company_discount', 'in_company_discount+'.$data['in_company_discount'], false);
            if ($data['in_company_discount'] > 0) {
                $this->db->set('in_company_discount_num', 'in_company_discount_num+1', false);
            }
            $this->db->set('in_company_num', 'in_company_num+1', false);
        }
        /*在线入款*/
        if (isset($data['in_online_total']) && isset($data['in_online_discount'])) {
            $this->db->set('in_online_total', 'in_online_total+'.$data['in_online_total'], false);
            $this->db->set('in_online_discount', 'in_online_discount+'.$data['in_online_discount'], false);
            if ($data['in_online_discount'] > 0) {
                $this->db->set('in_online_discount_num', 'in_online_discount_num+1', false);
            }
            $this->db->set('in_online_num', 'in_online_num+1', false);
        }
        /*人工入款*/
        if (isset($data['in_people_total']) && isset($data['in_people_discount'])) {
            $one = $this->get_one('in_people_total,in_people_discount,in_people_num,in_people_discount_num', 'cash_report', $where);
            $data['in_people_total'] += (float)$one['in_people_total'];
            $data['in_people_discount'] += (float)$one['in_people_discount'];
            $data['in_people_num'] += (int)$one['in_people_num'];
            $data['in_people_discount_num'] += (int)$one['in_people_discount_num'];
            $b = $this->db->update('cash_report', $data, $where);
            if ($b) {
                return true;
            } else {
                return false;
            }
            /* $this->db->set('in_people_total','in_people_total+'.$data['in_people_total'],FALSE);
             $this->db->set('in_people_discount','in_people_discount+'.$data['in_people_discount'],FALSE);
             $this->db->set('in_people_num','in_people_num+'.$data['in_people_num'],FALSE);
             $this->db->set('in_people_discount_num','in_people_discount_num+'.$data['in_people_discount_num'],FALSE);*/
        }
        /*优惠卡充值*/
        if (isset($data['in_card_total'])) {
            $this->db->set('in_card_total', 'in_card_total+'.$data['in_card_total'], false);
            $this->db->set('in_card_num', 'in_card_num+'.$data['in_card_num'], false);
        }
        /*会员出款被扣金额*/
        if (isset($data['in_member_out_deduction'])) {
            $this->db->set('in_member_out_deduction', 'in_member_out_deduction+'.$data['in_member_out_deduction'], false);
            $this->db->set('in_member_out_num', 'in_member_out_num+1', false);
        }
        /*人工出款*/
        if (isset($data['out_people_total'])) {
            $one = $this->get_one('out_people_total,out_people_num', 'cash_report', $where);

            $data['out_people_total'] = $data['out_people_total'] + (float)$one['out_people_total'];
            $data['out_people_num'] = $one['out_people_num'] + 1;
            $b = $this->db->update('cash_report', $data, $where);
            if ($b) {
                return true;
            } else {
                return false;
            }
            /* $this->db->set('out_people_total', 'out_people_total+'.$data['out_people_total'], false);
             $this->db->set('out_people_num', 'out_people_num+1', false);*/
        }
        /*线上出款*/
        if (isset($data['out_company_total'])) {
            $this->db->set('out_company_total', 'out_company_total+'.$data['out_company_total'], false);
            $this->db->set('out_company_num', 'out_company_num+1', false);
        }
        /*给予返水*/
        if (isset($data['out_return_water'])) {
            $this->db->set('out_return_water', 'out_return_water+'.$data['out_return_water'], false);
            $this->db->set('out_return_num', 'out_return_num+1', false);
        }
        /*活动优惠*/
        if(isset($data['activity_total'])){
            $this->db->set('activity_total','activity_total+'.$data['activity_total'],FALSE);
            $this->db->set('activity_num','activity_num+1',FALSE);
        }

        //8.30 报表更改
        if (!empty($data['is_one_pay']) && $data['is_one_pay'] == 1) {
            $this->db->set('is_one_pay',1);
        }

        $b = $this->db->update('cash_report', [], $where);
        if ($b) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 会员增加额度，并写入日志
     * @param  $uid         int       用户ID
     *
     * @param  $amount      float     会员增加或者减少额度
     * @param  $order_num   string    现金流订单号
     * @param  $type        int       现金流水类型
     * @param  $remark      string    备注
     * @param  $Prcie       float     实际金额 不含手续费和优惠费用
     * @return $bool
     */
    public function update_banlace_and_cash_list($uid=0, $amount=0, $order_num='', $type=6, $remark='', $price=0)
    {
        //计算用户最大最小出入款的cashtype'
        $cashType   = array(5,6,7,8,14);
        //$this->load->model('Comm_model','cm');
        $realAmount = $amount;
        $userData = $this->get_one_user($uid, 'balance,agent_id,max_income_price,max_out_price');
        $balance = $userData['balance'];
        $whereUser['id'] = $uid;

        if ($amount>0) {
            if ($price > $userData['max_income_price'] && in_array($type, [5,6,7,8])) {
                $this->db->set('max_income_price', $price);
            }
            $this->db->set('balance', 'balance+'.$amount, false);
            $balance = $balance+$amount;
            $whereUser['balance>='] = 0;
        } else {
            if (abs($price) > $userData['max_out_price'] && in_array($type, $cashType)) {
                $this->db->set('max_out_price', abs($price));
            }
            $amount = $amount*-1;
            $this->db->set('balance', 'balance-'.$amount, false);
            $whereUser['balance>='] = $amount;
            $balance = $balance-$amount;
        }
        $this->db->where($whereUser)->update('user');// 3、加钱
        if (!$this->db->affected_rows()) {
            return false;
        }
        $cashData['uid'] = $uid;
        $cashData['order_num'] = $order_num;
        $cashData['type'] = $type;
        $cashData['before_balance'] = $userData['balance'];
        $cashData['amount'] = $realAmount;
        $cashData['balance'] = $balance;
        $cashData['remark'] = $remark;
        $cashData['agent_id'] = $userData['agent_id'];
        $cashData['addtime'] = $_SERVER['REQUEST_TIME'];
        wlog(APPPATH.'logs/cash_list_'.$this->sn.'_'.date('Ym').'.log', "会员查询数据".json_encode($userData)."插入数据:".json_encode($cashData));

        $b5 = $this->write('cash_list', $cashData);// 4、加入现金记录
        if (!$b5) {
            return false;
        }
        return true;
    }

    /**
     * 存款时，检测稽核
     * @param  $uid     int     用户ID
     * @return $bool
     */
    public function check_and_set_auth($uid)
    {
        $where['uid'] = $uid;
        $where['is_pass'] = 0;
        $field = 'sum(auth_dml) as auth_dml,sum(limit_dml) as limit_dml';
        $authData = $this->get_one($field, 'auth', $where);
        $is_pass = true;
        $this->redis_select(REDIS_LONG);
        $rkUserAuthDml = 'user:dml';
        if (empty($authData['auth_dml'])) {
            $this->redis_hset($rkUserAuthDml, $uid, 0);
            return $is_pass;
        }
        $dml = $this->redis_hget($rkUserAuthDml, $uid);
        $dml = sprintf('%.3f', $dml);
        if ($dml >= $authData['auth_dml'] - $authData['limit_dml']) {
            $updateData['dml'] = $dml;
            $updateData['is_pass'] = 1;
            $updateData['end_time'] = $_SERVER['REQUEST_TIME'];
            if ($this->write('auth', $updateData, $where)) {
                $this->redis_hset($rkUserAuthDml, $uid, 0);
            } else {
                $is_pass = false;
            }
        }
        $this->redis_select(REDIS_DB);
        return $is_pass;
    }

    /**
     * 自增长层级累计额度
     * @param $price float 金额
     * @param  $level_id  int  层级id
     * @return  bool
     */
    public function incre_level_use($price, $lvel_id)
    {
        $sql = "UPDATE gc_level SET use_times=use_times+1,use_total=use_total+{$price} WHERE id ={$lvel_id}";
        return $this->db->query($sql);
    }

    /**
     * 记录公司入款线上入款银的 额度录 达到就停用
     * @param $id 银行卡的id
     * @param $money   入款金额 不带优惠
     * @param $type    1公司入款 ,2:线上入款
     */
    public function cash_company($id, $money, $type)
    {
        if ($type ==1) {
            $key = "cash:count:bank_card";
            $sql = "UPDATE gc_bank_card SET status = 2 WHERE id= $id AND max_amount <=";
            $rec_msg = "公司入款额度达到最大值,支付id:{$id}停用";
        } else {
            $key = "cash:count:online";
            $sql = "UPDATE gc_bank_online_pay SET status = 2 WHERE id= $id AND max_amount <=";
            $rec_msg = "线上入款额度达到最大值,支付id:{$id}停用";
        }
        $this->redis_HINCRBYFLOAT($key, $id, $money);
        $money = $this->redis_hget($key, $id);
        $sql.=$money;
        $this->db->query($sql);
        /*
         * 新增(线上入款额度到达最大值停用)记录 lqh 2018/08/07
        */
        $logData['content'] = $rec_msg;
        $this->load->model('log/Log_model','LOG');
        $this->LOG->record($id, $logData);
    }

    /**
     * 截取银行卡前四位和后四位
     * @param $strs:例如:$strs ='长城电子借记卡 6216******4796 安徽';
     * @return mixed
     */
    public  function match_number($strs){
        $patterns = "/\d+/";
        preg_match_all($patterns,$strs,$arr);
        return $arr[0];
    }


}
