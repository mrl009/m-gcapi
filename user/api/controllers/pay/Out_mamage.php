<?php
/**
 * 会员中心出款
 * Created by PhpStorm.
 * User: shenshilin
 * Date: 2017/4/29
 * Time: 13:54
 */

defined('BASEPATH') or exit('No direct script access allowed');

class Out_mamage extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('pay/Pay_set_model', 'pay');
        if ($this->user && $this->user['status'] == 4) {
            $this->return_json(E_DENY,'没有权限');
        }
    }
    private $is_first=0;
    public function index()
    {
        echo "hello word";
    }

    /**
     * 出款页面展示数据
     * 会员余额 可提现金额
     * 银行卡信息
     * 扣除费用信息
    */
    public function out_show()
    {
        $uid = $this->user['id'];
        $name = $this->user['username'];
        $str = $this->pay->set_out_lock($uid);
        if (!empty($str)) {
            $this->return_json(E_ARGS, '你有一笔订单正在出款'.$str);
        }

        $this->load->model('Auth_model');
        $datax    = $this->Auth_model->get_auth($name);
        $userData = $this->pay->get_pay_set($uid);
        $bank     = $this->pay->user_bank($uid);

        if (isset($datax['rows'])) {
            unset($datax['rows']);
        };
        empty($userData['out_type_max'])?$userData['out_type_max']="0":true;
        $data = [
            'out_max'           => $userData['out_max'],
            'out_min'           => $userData['out_min'],
            'username'          => $userData['username'],
            'balance'           => $userData['balance'],
            'total_price'       => '0',
            'discount_price'       => '0',
            'auth_dml'       => '0',
            'dml'       => '0',
            'w_dml'       => '0',
            'is_w_dml'       => 0,
            'is_pass'       => 0,
            'total_ratio_price'       => '0',
            'out_fee'       => '0',
            'all_fee'       => '0',
            'start_date'       => '0',
            'end_date'       => '0',
        ];

        $data = array_merge($data, $datax);
        $data['out_fee'] = (float)$data['out_fee'];
        $bank_num         = $bank['bank_num'];
        $str = '';
        for ($i=0;$i<strlen($bank_num)-8;$i++) {
            $str .= '*';
        }
        $bank['bank_num'] = substr_replace($bank_num, $str, 4, -4) ;
        $data = array_merge($data, $bank);
        $data['out_type'] = $this->member_out_type($userData);
        $data['out_balance'] = $data['balance'] - $data['all_fee'] > 0 ? (float)sprintf('%0.3f', $data['balance'] - $data['all_fee']) : 0;
        unset($data['bank_id']);
        unset($data['bank_pwd']);
        // 免费额度:不计算行政费，手续费，可出款额度为免费额度
        $set = $this->pay->get_gcset(['win_dml']);
        if (isset($set['win_dml']) && $set['win_dml'] == 2 && $data['is_pass'] == 0) {
            $this->return_json(E_OP_FAIL, '未达到稽核不能提款');
        }
        if (isset($set['win_dml']) && $set['win_dml'] == 1) {
            $data['out_fee'] = 0;
            $data['all_fee'] = 0;
            $data['total_ratio_price'] = 0;
            $data['is_w_dml'] = 1;
            $data['out_balance'] = $data['is_pass'] == 1 ? $data['out_balance'] : $data['w_dml'];
        }
        //记录用户最后一次获取稽核的时间及数据
        $keys = "cash:out_auth:uid_$uid";
        $reidsdata = array_merge($data, array('auth_time' => $_SERVER['REQUEST_TIME']));
        $str = json_encode($reidsdata, JSON_UNESCAPED_UNICODE);
        $this->Auth_model->redis_setex($keys, 36000, $str);
        $this->return_json(OK, $data);
    }


    /**
     * 出款类型
     *
     * @access public
     * @auth shensiling
     * @return json
     */
    public function out_type()
    {
        // 1
        $money = (float)$this->P('money');
        if (empty($this->user['id'])) {
            $this->return_json(E_ARGS, "用户没有登录");
        }
        // 2
        $payData = $this->pay->get_pay_set($this->user['id']);
        $select = 'bank_num as bank, wechat, alipay';
        $where = ['uid'=>$this->user['id']];
        $userData = $this->pay->get_one($select,'user_detail',$where);
        // 3
        if ($money < $payData['out_min']) {
            $this->return_json(E_ARGS, "出款金额不能低于{$payData['out_min']}元");
        }
        if ($money > $payData['out_max']) {
            $this->return_json(E_ARGS, "出款金额不能高于{$payData['out_max']}元");
        }
        if (empty($userData['bank'])&&empty($userData['wechat'])&&empty($userData['alipay'])) {
            $this->return_json(E_NOOP, "请绑定提款支付类型:微信,支付宝,银行");
        }
        // 4
        $support = ['bank'=>(string)0,'wechat'=>(string)0,'alipay'=>(string)0];
        if (!empty($userData['bank'])) {
            $support['bank'] = (string)1;
        }
        if (!empty($userData['wechat']) &&
            $money <= $payData['wx_max'] && $payData['wx_max'] > 0) {
            $support['wechat'] = (string)1;
        }
        if (!empty($userData['alipay']) &&
            $money <= $payData['zfb_max'] && $payData['zfb_max'] > 0) {
            $support['alipay'] = (string)1;
        }
        if ($support['bank']==0&&$support['alipay']==0&&$support['wechat']==0) {
            $this->return_json(E_NOOP, "该提款金额可能不支持微信和支付宝，请绑定银行卡后在尝试");
        }
        $this->return_json(OK, $support);
    }

    /**
     * 根据会员绑定银行卡的信息返回可以使用的支付方式
     * @param $pay_set 会员的支付设定
    */
    private function member_out_type($pay_set=null)
    {
        $this->load->model('user/User_model');
        $userCard = $this->User_model->member_card();
        if (empty($userCard)) {
            return [];
        }
        $bankId = array_column($userCard,'bank_id');
        $data=[
            'bank' => ['is_bangding' => '0'],
            'wx'   => ['is_bangding' => '0'],
            'zfb'  => ['is_bangding' => '0'],
        ];
        empty($pay_set['out_max'])?$data['bank']['out_max']="0":$data['bank']['out_max']= (string)$pay_set['out_max'];
        empty($pay_set['wx_max'])?$data['wx']['out_max']="0":$data['wx']['out_max']= (string)$pay_set['wx_max'];
        empty($pay_set['zfb_max'])?$data['zfb']['out_max']="0":$data['zfb']['out_max']= (string)$pay_set['zfb_max'];

        foreach ($bankId as $value) {
            if ($value <= 50 || $value>=65) {
                $data['bank']['is_bangding'] = '1';
            } elseif ($value == 51) {
                $data['zfb']['is_bangding'] = '2';
            } elseif ($value == 52) {
                $data['wx']['is_bangding'] = '3';
            }
        }
        return $data;
    }


    /**
     * 会员出款提交地址
     * 支持微信支付宝提现
    */
    public function member_out()
    {
        $data = [
            'money'    => trim($this->P('money')),
            'bank_pwd' => trim($this->P('bank_pwd')),
            'out_type' => trim($this->P('out_type')),
        ];

        if (!is_numeric($data['money']) || $data['money'] != ceil($data['money'])) {
            $this->return_json(E_ARGS,'取款额度只能为整数');
        }

        $userData = $this->check_out($data);
        $this->load->model('Auth_model');
        $keys = "cash:out_auth:uid_{$this->user['id']}";
        $jsonStr  = $this->Auth_model->redis_get($keys);
        if (empty($jsonStr)) {
            $this->return_json(E_ARGS, "获取稽核信息失败,请刷新页面");
        }
        $authData     = json_decode($jsonStr, true);
        if (!is_array($authData)) {
            $this->return_json(E_ARGS, '解析错误');
        }
        if ($data['money'] > $authData['out_balance']) {
            $this->return_json(E_ARGS, '超过最大可提款额度');
        }

        if ($userData['balance']-$authData['out_fee']-$authData['total_ratio_price'] < $data['money']) {
            $this->return_json(E_ARGS, '余额不足');
        }

        $set = $this->pay->get_gcset(['win_dml']);
        if (isset($set['win_dml']) && $set['win_dml'] == 2 && $authData['is_pass'] == 0) {
            $this->return_json(E_OP_FAIL, '未达到稽核不能提款');
        }

        $bank = $this->pay->get_one('bank_pwd', 'user_detail', ['uid'=>$this->user['id']]);

        $this->check_bakn_pwd($bank['bank_pwd'], bank_pwd_md5($data['bank_pwd']) );

        $order  = order_num(5, 501);
        isset($_SESSION['HTTP_REFERER'])?$url=$_SESSION['HTTP_REFERER']:$url= '未知';

        $money = $data['money'];
        $totleMoney = $money+$authData['out_fee']+$authData['total_ratio_price'];
        $insert = [
            'order_num'    => $order,
            'uid'          => $this->user['id'],
            'is_first'     => $this->is_first,
            'price'        => $totleMoney,
            'hand_fee'     => $authData['out_fee'],
            'admin_fee'    => $authData['total_ratio_price'],
            'actual_price' => $money,
            'is_pass'      => $authData['is_pass'],
            'addtime'      => $_SERVER['REQUEST_TIME'],
            'status'       => 1,
            'url'          => $url,
            'agent_id'     => $this->user['agent_id'],
            'from_way'     => $this->from_way,
            'out_type'     => $data['out_type'],
        ];
        // 10.5添加备注
        $b=function($type){
            switch ($type) {
                case 1:
                    return '银行卡正在转账中,请稍等';
                case 2:
                    return '支付宝正在出款中,请稍等';
                case 3:
                    return '微信正在出款中,请稍等';
            }
        };
        $insert['remark'] = $b($data['out_type']);
        $this->chukuan($insert,$authData['auth_time']);
    }

    /**
     * 出款现金流
     * @param $inserData array 出库啊写入数据库
     * @param  $auth_time int 会员最后一次获取稽核的时间
    */
    private function chukuan($inserData,$auth_time)
    {

        $this->load->model('Comm_model', 'comm');
        $this->comm->db->trans_begin();
        $bool = $this->comm->write('cash_out_manage', $inserData);
        if ($bool) {
            //申请出款不写入流水确认出款才写入流水
            //$bool = $this->comm->db->set('balance', 'balance-'.$totleMoney, false)->update('user', [], array('id'=>$uid));
            $bool = $this->comm->update_banlace($inserData['uid'],$inserData['price']*-1,$inserData['order_num'],14,'公司出款');
            if ($bool) {
                $bool = $this->pay->set_out_lock($this->user['id'], $inserData['order_num']);
                if (!$bool) {
                    $this->comm->db->trans_rollback();
                    $str = $this->pay->set_out_lock($this->user['id']);
                    $this->return_json(E_ARGS, '你有一笔订单正在出款'.$str);
                }
                $this->comm->db->trans_commit();
                //记录会员的出款时间
                $keys = "cash:out_auth:uid_{$inserData['uid']}";
                $this->comm->redis_del($keys);
                $this->comm->out_user_time($inserData['uid'], $auth_time);
                //会员是否免稽核出款
                //$this->comm->out_user_w_dml($inserData['uid'], $is_w_dml);
                $str= "会员 申请出款";
                $this->push(MQ_USER_OUT, $str,$inserData['order_num']);
                $this->return_json(OK, '出款申请提交成功请等待审核...');
            } else {
                $this->pay->del_out_lock($this->user['id']);
                $this->comm->db->trans_rollback();
                $this->return_json(E_ARGS, '请重试');
            }
        } else {
            $this->pay->del_out_lock($this->user['id']);
            $this->comm->db->trans_rollback();
            $this->return_json(E_ARGS, '请重试');
        }
    }
    /**
     * 检查资金密码
     * @param 被检查的资金密码1
     * @param 被检查的资金密码2
     *
    */
    private function check_bakn_pwd($pwd1,$pwd2)
    {
        //todo 增加出款密码错误次数太多停用用户
        $pwdError = "user:error:bankpwd:".$this->user['id'];
        //8.12 密码相关更改
        if ($pwd1 != $pwd2 ) {
            $this->pay->redis_INCR($pwdError, 1);
            $errorNum = $this->pay->redis_get($pwdError);
            if ($errorNum >= USER_BANK_PWD_ERROR) {
                $this->load->model('Login_model');
                $this->pay->db->update('user', ['status'=>2], ['id'=>$this->user['id']]);
                $this->Login_model->login_be_out($this->user['id']);
                $this->pay->redis_del($pwdError);
                $this->return_json(E_ARGS, '取款密码错误次数过多账户停用!');
            } else {
                $this->return_json(E_ARGS, '取款密码错误剩余次数'.(USER_BANK_PWD_ERROR-$errorNum) . ',如忘记取款密码请联系客服修改!');
            }
        }
        $this->pay->redis_del($pwdError);
    }
    /**
     * 检查出款的数据
     * @param array $data 入款数据
    */
    private function check_out($data)
    {
        if ($data['out_type'] <=0) {
            $this->return_json(E_ARGS,'出款方式错误');
        }
        //获取用户的数据和用户所属层级的支付设定
        $userData   = $this->pay->get_pay_set($this->user['id']);
        $outNumkeys = 'user:out_num:_'.date('Y-m-d');
        $outNum     = $this->pay->redis_HGET($outNumkeys,$this->user['id']);
        if ($outNum >= $userData['out_num']) {
            $this->return_json(E_ARGS,'超出今日出款次数');
        }
        $userData['max_out_price'] >0 ?$this->is_first =0:$this->is_first=1;
        if ($data['money'] < $userData['out_min']) {
            $this->return_json(E_ARGS,'最低出款'.$userData['out_min']);
        }
        /*if ($data['money'] > $userData['out_max']) {
            $this->return_json(E_ARGS,'最高出款'.$userData['out_max']);
        }*/
        if (empty($data['bank_pwd'])) {
            $this->return_json(E_ARGS,'请输入资金密码');
        }
        $out_type = $this->member_out_type($userData);
        if (empty($out_type)) {
            $this->return_json(E_ARGS,'请先绑定出款方式');
        }
        $a = array_column($out_type,'is_bangding');
        $b=function($type){
            switch ($type) {
                case 1:
                    return '银行卡转账';
                case 2:
                    return '支付宝';
                case 3:
                    return '微信';
            }
        };
        // 出款方式最大出款如果不0 则表示停用
        if (in_array($data['out_type'],$a)) {
            foreach ($out_type as $value) {
                if ($value['is_bangding'] == $data['out_type'] && $data['money'] > $value['out_max']  ) {
                    if ( $value['out_max'] == 0) {
                        $this->return_json(E_ARGS,$b($data['out_type']).'目前暂不可用');

                    }
                    $this->return_json(E_ARGS,$b($data['out_type']).'最大出款'.$value['out_max']);
                }
            }
        }else{
            $this->return_json(E_ARGS,'出款方式错误');
        }


        return $userData;
    }


    /**
     * 出款提交地址
     *
    */

    public function out_do()
    {
        $money    = $this->P('money');
        $bank_pwd = $this->P('bank_pwd');

        if ((int)$money != $money) {
            $this->return_json(E_ARGS, '取款金额只能是整数');
        }

        $uid      = $this->user['id'];
        if (empty($uid)) {
            $this->return_json(E_ARGS, '请登录');
        }
        //获取用户的数据和用户所属层级的支付设定
        $userData   = $this->pay->get_pay_set($uid);
        $outNumkeys = 'user:out_num:_'.date('Y-m-d');
        $outNum     = $this->pay->redis_HGET($outNumkeys,$uid);
        if ($outNum >= $userData['out_num']) {
            $this->return_json(E_ARGS,'超出今日出款次数');
        }
        $userData['max_out_price'] >0 ?$is_first =0:$is_first=1;
        $max_min  = [
            $userData['out_max'],
            $userData['out_min']
        ];
        $data = [
            'money'    => $money,
            'bank_pwd' => $bank_pwd,
        ];

        $this->check_out_do($data, $max_min);

        /**提交的数据验证**/
        $this->load->model('Auth_model');
        $keys = "cash:out_auth:uid_$uid";
        $jsonStr  = $this->Auth_model->redis_get($keys);
        if (empty($jsonStr)) {
            $this->return_json(E_ARGS, "获取稽核信息失败,请刷新页面");
        }
        $authData     = json_decode($jsonStr, true);
        if (!is_array($authData)) {
            $this->return_json(E_ARGS, '解析错误');
        }
        $bank     = $this->pay->user_bank($uid);
        ;
        if (empty($bank['bank_num'])) {
            $this->return_json(E_ARGS, '请先绑定银行卡');
        }

        //todo 增加出款密码错误次数太多停用用户
        $pwdError = "user:error:bankpwd:".$uid;
        //8.12 密码相关更改
        if ($bank['bank_pwd'] != bank_pwd_md5($bank_pwd) ) {
            $this->load->model('Login_model');
            $this->pay->redis_INCR($pwdError, 1);
            $errorNum = $this->pay->redis_get($pwdError);
            if ($errorNum >= USER_BANK_PWD_ERROR) {
                $this->pay->db->update('user', ['status'=>2], ['id'=>$uid]);
                $this->Login_model->login_be_out($uid);
                $this->pay->redis_del($pwdError);
                $this->return_json(E_ARGS, '取款密码错误次数过多账户停用!');
            } else {
                $this->return_json(E_ARGS, '取款密码错误剩余次数'.(USER_BANK_PWD_ERROR-$errorNum));
            }
        }
        $this->pay->redis_del($pwdError);
        
        $userData = $this->pay->get_pay_set($uid);

        if ($money < $userData['out_min']) {
            $this->return_json(E_ARGS, '出款不能低于'.$userData['out_min']);
        }
        if ($money <= 0) {
            $this->return_json(E_ARGS, '出款金额不能小于0');
        }
        if ($money > $userData['out_max']) {
            $this->return_json(E_ARGS, '出款不能大于'.$userData['out_max']);
        }
        if ($userData['balance']-$authData['out_fee']-$authData['total_ratio_price'] < $money) {
            $this->return_json(E_ARGS, '余额不足');
        }

        $order  = order_num(5, 501);
        ($authData['total_ratio_price'] == 0)?$is_pass=1:$is_pass= 0;
        isset($_SESSION['HTTP_REFERER'])?$url=$_SESSION['HTTP_REFERER']:$url= '未知';

        $totleMoney = $money+$authData['out_fee']+$authData['total_ratio_price'];
        $insert = [
            'order_num'    => $order,
            'uid'          => $uid,
            'is_first'     => $is_first,
            'price'        => $totleMoney,
            'hand_fee'     => $authData['out_fee'],
            'admin_fee'    => $authData['total_ratio_price'],
            'actual_price' => $money,
            'is_pass'      => $is_pass,
            'addtime'      => $_SERVER['REQUEST_TIME'],
            'status'       => 1,
            'url'          => $url,
            'agent_id'     => $this->user['agent_id'],
            'from_way'     => $this->from_way,
        ];
        /*$bool = $this->pay->set_out_lock($uid, $order);
        if (!$bool) {
            $str = $this->pay->set_out_lock($uid);
            $this->return_json(E_ARGS, '你有一笔订单正在出款'.$str);
        }*/
        $this->load->model('Comm_model', 'comm');
        $this->comm->db->trans_begin();
        //申请出款不写入流水确认出款才写入流水
        $bool = $this->comm->update_banlace($uid,-$totleMoney,$order,14,'公司出款');
        //$bool = $this->comm->db->set('balance', 'balance-'.$totleMoney, false)->update('user', [], array('id'=>$uid));
        if ($bool) {
            $bool = $this->pay->write('cash_out_manage', $insert);
            if ($bool) {
                $bool = $this->pay->set_out_lock($uid, $order);
                if (!$bool) {
                    $this->comm->db->trans_rollback();
                    $str = $this->pay->set_out_lock($uid);
                    $this->return_json(E_ARGS, '你有一笔订单正在出款'.$str);
                }
                $this->comm->db->trans_commit();
                //记录会员的出款时间
                $this->Auth_model->redis_del($keys);
                $this->comm->out_user_time($uid, $authData['auth_time']);
                $str= "会员 申请出款";
                $this->push(MQ_USER_OUT, $str,$order);
                $this->return_json(OK, '出款申请提交成功请等待审核...');
            } else {
                $this->pay->del_out_lock($uid);
                $this->comm->db->trans_rollback();
                $this->return_json(E_ARGS, '请重试');
            }
        } else {
            $this->pay->del_out_lock($uid);
            $this->comm->db->trans_rollback();
            $this->return_json(E_ARGS, '请重试');
        }
    }

    private function check_out_do($data, $max_min)
    {
        $rule = [
            'money'    => 'require|number|between:'.$max_min[1].','.$max_min[0],
            'bank_pwd' => 'require',
        ];
        $msg  = [
            'bank_pwd' => '取款密码格式错误',
            'money'    => '出款金额最低'.$max_min[1]."最高为{$max_min[0]}",
        ];

        $this->validate->rule($rule, $msg);

        $result   = $this->validate->check($data);

        if (!$result) {
            $this->return_json(E_ARGS, $this->validate->getError());//返回错误信息        }else{
        } else {
            return true;
        }
    }
}
