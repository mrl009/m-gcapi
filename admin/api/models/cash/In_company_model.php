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

class In_company_model extends MY_Model
{
    public $push_str = "";//消息队列提示信息
    public function __construct()
    {
        parent::__construct();
        $this->select_db('private');
    }

    /******************公共方法*******************/
    /**
     * 获取公司入款数据列表
     */
    public function get_in_company($basic, $senior, $page)
    {
        // 获取汇总数据
        $select = 'sum(a.price) as price,
                    count(*) as in_company_num,
                    sum(a.discount_price) as discount_price,
                    sum(a.total_price) as total_price';
        $footer = $this->get_list($select, 'cash_in_company',
                            $basic, $senior);
        foreach ($footer[0] as $key => $value) {
            $footer[0][$key] = floatval($value);
        }
        // 获取公司入款数据列表
        $select = 'b.username as admin_name,
                    a.agent_id as agent_id,
                    a.id as id,
                    a.uid as user_id,
                    a.name as user_name,
                    a.order_num as order_num,
                    a.admin_id as admin_id,
                    a.bank_id as bank_id,
                    a.bank_style as bank_style,
                    a.bank_card_id as card_id,
                    a.price as price,
                    a.total_price as total_price,
                    a.discount_price as discount_price,
                    a.status as status,
                    a.is_first as is_first,
                    a.addtime as addtime,
                    a.update_time as update_time,
                    a.remark as remark,
                    a.confirm,
                    a.from_way as from_way';
        $resu = $this->get_list($select, 'cash_in_company',
                            $basic, $senior, $page);
        $resu['footer'] = $footer[0];


        // 加入收款帐号列表并且把id转换为name
        $resu['rows'] = $this->_id_to_name($resu['rows']);
        $bank_style = array(0=>'未知',1=>'网银转帐',    2=>'ATM自动柜员',
                            3=>'ATM现金入款', 4=>'银行柜台',
                            5=>'手机转帐',    6=>'支付宝转帐',
                            7=>'微信支付',    8=>'qq 钱包',
                            9=>'京东钱包',    10=>'百度钱包',
                            11=>'小米钱包',   12=>'华为钱包',
                            13=>'三星钱包' , 14 => '一码付');
        foreach ($resu['rows'] as $k => $v) {
            if (!empty($v['bank_style'])) {
                $resu['rows'][$k]['bank_style'] =
                        $bank_style[(int)$v['bank_style']];
            } else {
                $resu['rows'][$k]['bank_style'] =
                        $bank_style[0];
            }
            if (empty($v['update_time'])) {
                $resu['rows'][$k]['update_time'] = '-';
            } else {
                $resu['rows'][$k]['update_time'] =
                        date('Y-m-d H:i:s', $v['update_time']);
            }
            $resu['rows'][$k]['timestamp'] = $v['addtime'];
            $resu['rows'][$k]['addtime'] =
                        date('Y-m-d H:i:s', $v['addtime']);
        }
        return $resu;
    }

    /**
     * 公司入款，取消入款
     */
    public function handle_in($id=0, $status=2, $admin,$remark=null,$aid=0)
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
        //判断bank_auto数据有对应id <edit cs 2018-9-17 14:58:21>
        if($aid > 0){
            $tarr ['status'] = 0;
            $tarr ['id'] = $aid;
            $flid = 'id,uid,remark,pay_card_name,card_num,pay_card_name,pay_amount,pay_time';
            $bankData = $this->db->select($flid)->get_where('bank_auto', $tarr)->row_array();
            if(empty($bankData)){
               $res['status'] = false;
               $res['content'] = '操作失败，操作状态不允许';
               return $res;
            }
        }

        if (empty($inData)) {
            $res['status'] = false;
            $res['content'] = '操作失败，操作状态不允许';
            return $res;
        }
        $this->load->model('cash/Cash_common_model', 'comm');
        $bool  = $this->comm->check_in_or_out($admin['id'], $admin['max_credit_out_in'], $inData['price']);
        if ($bool !== true) {
            $res['status'] = false;
            $res['content'] = '操作失败,你的操作额度不够';
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
        $updateCashData['remark'] = $remark;
        $updateCashData['status'] = $status;
        $updateCashData['update_time'] = $_SERVER['REQUEST_TIME'];
        $updateCashData['admin_id'] = $admin['id'];
        $b1 = $this->write('cash_in_company', $updateCashData, $where);// 2、修改入款状态
        //根据bank_auto数据更新状态 <edit cs 2018-9-17 14:58:21>

        if($aid>0){
            $a_where['id'] = $aid;
            $updateAutoData['status'] =1;//改为已入帐状态
            $updateAutoData['uid'] =$inData['uid'];//改为确认入款状态
            $updateAutoData['order_num'] =$inData['order_num'];//订单号
            $updateAutoData['updated'] = $_SERVER['REQUEST_TIME'];
            $updateAutoData['remark'] = $bankData['remark'].','.$admin['id'];
            $a1 = $this->write('bank_auto', $updateAutoData, $a_where);//修改bank_auto状态
            if (!$a1) {
                // echo '更新状态出错';
                $this->db->trans_rollback();
                $res['status'] = false;
                $res['content'] = '更新状态出错';
                return $res;
            }
        }
        if (!$b1) {
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
            $this->load->model('Grade_mechanism_model');
            $gradeInfo = $this->Grade_mechanism_model->grade_doing($inData['uid'], $inData['total_price']);
            if (empty($gradeInfo['integral']) && empty($gradeInfo['vip_id'])) {
                $this->db->trans_rollback();
                $res['status'] = false;
                $res['content'] = '修改晋级信息失败';
                return $res;
            }
        }
        /*加钱，写入现金记录*/
        if ($inData['discount_price'] > 0) {
            //公司入款含优惠 此时写入流水表两条记录，一条存款金额，一条优惠金额
            $type = 6;
            // 写充值金额
            $bool = $this->comm->update_banlace($inData['uid'], $inData['price'], $inData['order_num'], $type, '公司入款', $inData['price'], $gradeInfo['integral'], $gradeInfo['vip_id']);
            $type1 = 11;//优惠活动
            $remark = '公司入款-存款优惠';
            // 写优惠金额
            $bool1 = $this->comm->update_banlace($inData['uid'], $inData['discount_price'], $inData['order_num'], $type1, $remark);
            $b2 = $bool && $bool1;
        } else {
            $type = 8;
            $b2 = $this->comm->update_banlace($inData['uid'], $inData['price'], $inData['order_num'], $type, '公司入款', $inData['price'], $gradeInfo['integral'], $gradeInfo['vip_id']);
        }

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
        $b3 = $this->comm->check_and_set_auth($inData['uid']);// 5、更新稽核
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
        $this->load->model('cash/Report_model');
        //添加首存标记
        $b5 = $this->Report_model->collect_cash_report($uid, $report_date, $cashData);
        if (!$b5) {
            $this->db->trans_rollback();
            $res['status'] = false;
            $res['content'] = '收集现金报表失败';
            return $res;
        }
        $b6 = $this->comm->incre_level_use($inData['total_price'], $payData['level_id']);
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
        $this->comm->cash_company($inData['bank_card_id'], $inData['price'], 1);
        $res['status'] = true;
        $this->push_str = "管理员{$admin['name']}确认公司入款";
        wlog(APPPATH.'logs/company_in_'.$this->sn.'_'.date('Ym').'.log', "公司入款{$inData['price']},优惠{$inData['discount_price']},订单号{$inData['order_num']},uid:$uid");
        $ci->push(MQ_COMPANY_RECHARGE, $this->push_str,$inData['order_num']);

        $res['content'] = '入款成功!';
        return $res;
    }
    /********************************************/



    /******************私有方法*******************/
    /**
     * 获取某个表的全部数据
     */
    public function _table_list($select, $table, $db = 'private',
        $where = array(), $condition = array())
    {
        $this->select_db($db);
        $res = $this->get_list($select, $table, $where, $condition);
        return $res;
    }



    /**
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
        $this->redis_select(5);
        $kye = 'user:in_company:'.$id;
        $arr = $this->redis_get($kye);
        if (empty($arr)) {
            $arr = $this->redis_get($kye);
        }
        $arr = json_decode($arr, true);
        if (empty($arr)) {
            return false;
        }
        foreach ($arr as $k => $value) {
            if ($order_num == $value['order_num']) {
                unset($arr[$k]);
                break;
            }
        }
        if (empty($arr) || count($arr) == 0) {
            $flag = $this->redis_del($kye);
            return $flag;
        } else {
            $gcSet = $this->get_gcset(['incompany_timeout']);
            $expire = $gcSet['incompany_timeout']*60;
            $flag = $this->redis_set($kye, json_encode($arr));
            $this->redis_EXPIRE($kye, $expire);
            return true;
        }
    }
    /**
     * 将id转换为name
     */
    private function _id_to_name($data)
    {
        if (!$data) {
            return $data;
        }

        // 初始化0的值
        $cache['user_id'][0] = ['username'=>'-'];
        $cache['leve_id'][0] = '-';
        foreach ($data as $k => $v) {
            $user_id = $v['user_id'];
            $agent_id = $v['agent_id'];

            if (empty($cache['user_id'][$user_id])) {
                $user = $this->user_cache($user_id);
                $cache['user_id'][$user_id] = $user;
            }
            $leve_id = $cache['user_id'][$user_id]['level_id'];
            $v['level_id'] = $leve_id;

            if (empty($cache['user_id'][$agent_id])) {
                $agent = $this->user_cache($agent_id);
                $cache['user_id'][$agent_id] = $agent;
            }

            if (empty($cache['leve_id'][$leve_id])) {
                $leve = $this->level_cache($leve_id);
                $cache['leve_id'][$leve_id] = $leve;
            }

            if (empty($v['admin_name'])) {
                $v['admin_name'] = '-';
            }

            if (empty($v['agent_name'])) {
                $v['agent_name'] = '-';
            }
            $u = $this->get_one('bank_name as u_bank_name', 'user_detail', array('uid'=>$user_id));
            $v['u_bank_name'] = $u['u_bank_name'];
            $v['leve_name'] = $cache['leve_id'][$leve_id];
            $v['loginname'] = $cache['user_id'][$user_id]['username'];
            $v['agent_name'] = $cache['user_id'][$agent_id]['username'];
            $data[$k] = $v;
        }

        $resu['card_'] = $this->core->_table_list(
            'id, card_username as name, card_num as num, card_address as address', 'bank_card');
        $resu['bank_'] = $this->core->_table_list(
            'id, bank_name as name', 'bank', 'public');
        $tt = array();
        foreach ($resu as $k => $v) {
            $tt[$k] = array_make_key($v, 'id');
        }
        /* 将id转换成name */
        foreach ($tt as $k => $v) {
            foreach ($data as $k1 => $v1) {
                if (empty($v[$v1[$k.'id']])) {
                    unset($data[$k1][$k.'id']);
                    $data[$k1][$k.'name'] = '-';
                    continue;
                }
                $r = $v[$v1[$k.'id']];
                unset($data[$k1][$k.'id']);
                $data[$k1][$k.'name'] = $r['name'];
                if ('card_' == $k) {
                    $data[$k1][$k.'num'] = $r['num'];
                    $data[$k1][$k.'address'] = $r['address'];
                }
            }
        }
        return $data;
    }
    /********************************************/

    /*
     * 获取bank_auto表数据
     */
    public function get_bankdata($where,$page){
        // 获取汇总数据
        $where['pay_time >='] = date('Y-m-d H:i:s',$where['pay_time >=']);
        $where['pay_time <='] = date('Y-m-d H:i:s',$where['pay_time <=']);
        foreach ($where as $k => $v) {
            if (isset($v) && !empty($v)&& $k !='status') {
                $twhere[$k] = $v;
                $twhere['status'] = $where['status'];
            }
        }
        if($twhere['status'] === null ||$twhere['status'] === ''){//<cz 2018-10-22 全部搜索>
            unset($twhere['status']);
        }
        $field = 'sum(pay_amount) as pay_amount,
                    count(*) as in_company_num';
        $twhere ['pay_amount >'] = 0;

        $footer = $this->db->select($field)->get_where('gc_bank_auto',$twhere)->result_array();

        foreach ($footer[0] as $key => $value) {
            $footer[0][$key] = floatval($value);
        }
        /*查询的字段*/
        $field='id,card_num,order_num,uid,pay_card_name,pay_card_num,pay_channel,pay_amount,pay_time,status';
        $arr['rows']=  $this->db->select($field)->get_where('bank_auto',$twhere)->result_array();
        $arr['footer'] = $footer[0];

        return $arr;
    }

    /*
     * 处理银行表数据
     */
    public function make_bank($aid, $status=1, $admin,$remark=null){
        $ci = get_instance();
        $tarr ['status'] = 0;
        $tarr ['id'] = $aid;
        $flid = 'id,uid,pay_card_name,card_num,pay_card_name,pay_amount,pay_time';
        $bankData = $this->db->select($flid)->get_where('bank_auto', $tarr)->row_array();
        if(empty($bankData)){
            $res['status'] = false;
            $res['content'] = '操作失败，操作状态不允许';
            return $res;
        }
        $a_where['id'] = $aid;
        $updateAutoData['status'] =$status;//改为已入帐状态
        $updateAutoData['updated'] = $_SERVER['REQUEST_TIME'];
        $updateAutoData['remark'] = $bankData['remark'].','.$admin['id'];
        $a1 = $this->write('bank_auto', $updateAutoData, $a_where);//修改bank_auto状态
        if (!$a1) {
            // echo '更新状态出错';
            $this->db->trans_rollback();
            $res['status'] = false;
            $res['content'] = '更新状态出错';
            return $res;
        }
        if($status==1){
            $res['status'] = true;
            $this->push_str = "管理员{$admin['name']}操作确认银行的入账";
            @wlog(APPPATH.'logs/bank_auto_'.$this->sn.'_'.date('Ym').'.log', "入账成功{$bankData['pay_amount']}");
            $ci->push(MQ_COMPANY_RECHARGE, $this->push_str,$bankData['id']);

            $res['content'] = '入账成功!';
            return $res;
        }elseif($status ==3){
            $this->db->trans_commit();//操作成功
            if (!empty($inData['confirm'])) {
                $this->del_confirm($inData['confirm']);
            }
            $this->push_str = "管理员{$admin['name']}操作为不处理状态";
            wlog(APPPATH.'logs/bank_auto_'.$this->sn.'_'.date('Ym').'.log', "取消处理入账{$bankData['pay_amount']}");
            $ci->push(MQ_COMPANY_RECHARGE, $this->push_str,$bankData['id']);

            $res['status'] = true;
            $res['content'] = '取消处理成功';
            return $res;
        }

    }
    public function move($id){
        $where['id'] = $id;
        $where['status'] = 1;
        $row = $this->get_one('order_num,addtime','cash_in_company',$where);
        //限制设置10时间内不允许操作
        $gcSet  = $this->get_gcset('income_time');
        $l_time = $gcSet['income_time']*60;
        $diff   = $_SERVER['REQUEST_TIME']-$row['addtime'];
        if($diff<$l_time){
            return '请先等待公司自动入款';
        }
    }
}
