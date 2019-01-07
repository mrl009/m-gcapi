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

class Cash_common_model extends MY_Model
{
    private $cashKey= '';
    public function __construct()
    {
        parent::__construct();
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
     * 写会员稽核
     * @param  $uid     int     用户ID
     * @param  $inData  array   入款总金额,入款优惠
     * @param  $type    int     1 为公司入款 2为线上入款
     * @param  $pay_set  array  用户的支付设定
     * @return $bool
     */
    public function set_user_auth($uid, $inData, $type, $pay_set=[])
    {
        $this->load->model('pay/Pay_set_model', 'ps');
        $payData = $this->ps->get_pay_set($uid, 'ps.pay_set_content');//获取一个会员的支付设定信息
        if (empty($payData)) {
            $res['status'] = false;
            $res['content'] = '更取不到支付设置，请联系管理员';
            return $res;
        }
        $auth_dml = "0";
        $limit    = "0";//放宽额度
        if ($type == 1) {
            if ($payData['line_is_ct_audit']) {
                $auth_dml = $inData['total_price'] * $payData['line_ct_audit']/100;
                $limit = $payData['line_ct_fk_audit'];
            }
        } else {
            if ($payData['ol_is_ct_audit']) {
                $auth_dml = $inData['total_price'] * $payData['ol_ct_audit']/100;
                $limit = $payData['ol_ct_fk_audit'];
            }
        }
        $is_pass = "0";
        if ($auth_dml == 0) {
            $is_pass =1 ;
        }

        $authData['uid'] = $inData['uid'];
        $authData['total_price'] = $inData['price'];
        $authData['discount_price'] = $inData['discount_price'];
        $authData['auth_dml'] = $auth_dml;
        $authData['start_time'] = $_SERVER['REQUEST_TIME'];
        $authData['is_pass'] = $is_pass;
        $authData['limit_dml']  = $limit;
        $authData['type'] = $type;
        $b4 = $this->write('auth', $authData);// 6、增加稽核
        if (!$b4) {
            $res['status'] = false;
            $res['content'] = '写入稽核日志失败';
            return $res;
        }
        $res['status'] = true;
        $res['content'] = '成功';
        return $res;
    }
    public function get_one_user($username='', $field='*')
    {
        if (is_numeric($username)) {
            $where['id'] = $username;
        } else {
            $where['username'] = $username;
        }
        return $this->db->select($field)->limit(1)->get_where('user', $where)->row_array();
        //return $this->get_one($field,'user',$where);
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
     * 检查管理员当天的存入和取出的额度
     * @param $adminId  管理员的id
     * @param $allow_money  管理员允许的金额
     * @param  $money    操作的金额
     * @param  $type    类型  company公司入款  online 线上入款  out 公司出款  people人工存款
     * @return  bool    成功为true  否则 返回剩余额度
    */
    public function check_in_or_out($adminId, $allow_money, $money, $is_people='')
    {
        if ($is_people==1) {
            $kes = 'cash:people:'.$adminId.':'.date('Y-m-d');
        } else {
            $kes = "cash:admin:$adminId:".date('Y-m-d');
        }
        $this->cashKey = $kes;
        if ($allow_money == 0) {
            return true;
        }
        return $allow_money >=$money;
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
     * 加钱成功后给自增长 每日额度
    */
    public function cash_increby($money)
    {
        $this->redis_INCRBYFLOAT($this->cashKey, $money);
    }

    /**
     * 加钱成功后给自增长 每日人工存取款额度
     */
   public function cash_people_increby($adminId, $money)
   {
       $key = 'cash:people:'.$adminId.':'.date('Y-m-d');
       $this->redis_INCRBYFLOAT($key, $money);
   }

    /**
     * 出入款自动过期
     * 将状态=1并且addtime<30分钟之后 更新为 status=3
     * @param $type   int 1 为更新公司入款 2 更新线上入款
     * @param $uid   int  会员id
     */
    public function update_online_status($type, $uid=null)
    {
        $tb   = array('','gc_cash_in_company','gc_cash_in_online');
        $gcSet = $this->get_gcset(['incompany_timeout']);
        $time = $_SERVER['REQUEST_TIME'] - $gcSet['incompany_timeout']*60;

        $str  = "";
        if ($uid) {
            $str = " and uid = $uid";
        }
        if ($type ==1) {
            $x = '';
            //公司入款addtime 有会员填入时间 用update_time
            //$sql = 'UPDATE '.$tb[$type].' set status=3 WHERE update_time < '.$time.' AND remark = \'\'  AND status=1'.$str;
            //$sql = 'UPDATE '.$tb[$type].' set status=3 WHERE update_time < '.$time.'  AND status=1'.$str;
            $sql = 'UPDATE '.$tb[$type].' set status=3 WHERE addtime < '.$time.'  AND status=1'.$str;
        } else {
            $timex = time();
            $sql = 'UPDATE '.$tb[$type].' set status=3 ,update_time='.$timex.' WHERE addtime < '.$time.' AND status=1'.$str;
        }
        $bool = $this->db->query($sql);
        $rosNum = 0;
        if ($bool) {
            $rosNum = $this->db->affected_rows();
        }
        if ($type == 1 && $rosNum > 1) {
            $data = $this->get_one('id','cash_in_company',[ 'status' => 1 ]);
            if (empty($data)) {
                $ci = get_instance();
                $ci->push(MQ_COMPANY_RECHARGE,'自动取消公司入款'.$rosNum.'笔');
            }
        }
    }
}
