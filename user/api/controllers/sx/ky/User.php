<?php
defined('BASEPATH') OR exit('No direct script access allowed');
include_once FCPATH . 'api/core/SX_Controller.php';
class User extends SX_Controller
{
    protected $platform_name = 'ky';
    public function __construct()
    {
        parent::__construct();
        $this->load->library('ky/KyuserApi','','userapi');
        $this->load->model('sx/dg/user_model', 'dg_user');
    }
    /*会员登陆*/
    public function  login(){
        $username = $this->sxuser['merge_username'];
        $acType = isset($this->sxuser['actype']) ? $this->sxuser['actype'] : 1;
        if (empty($username)) {
           $this->return_json(E_ARGS, '参数错误');
       }
       $user_info = $this->dg_user->user_info($username,'*','ky');
       if (empty($user_info)) {
            $this->signup();
       }

        $sn = $this->sxuser['sn'];
        $data = [];
        $data['s'] = 0;
        $data['account'] = $username;
        $data['money'] = 0;
        $data['orderid'] = $this->userapi->getOrderId($this->get_sn());
        $data['ip'] = get_ip();
        $data['lineCode'] = $sn;
        $data['KindID'] = 0;
        $logininfo = $this->userapi->get_api_data($data,1,$this->get_sn());
        if(isset($logininfo['d']['code']) && $logininfo['d']['code']==0){
            $this->return_json(OK, $logininfo['d']['url']);
        }else{
            /*开元登陆失败返回失败原因*/
            $this->return_json(E_ARGS, $logininfo['d']['code']);
        }
    }


    /**
     * 会员注册
     *
     * @param sn
     * @param $username  用户名必須少于20個字元不可以带特殊字符，只可以数字，字母，下划线
     * @param $actype    actype=1 代表真錢账号;  actype=0 代表试玩账号
     * @param $password  密码
     * @param string $oddtype 盘口, 设定新玩家可下注的范围,默认为A
     * @param string $method 数值 = “lg” 代表 ”检测并创建游戏账号
     * @param string $cur 货币种类
     */
    public function signup()
    {
        $sn    = isset($this->sxuser['sn']) ? $this->sxuser['sn'] : '';
        $snUid = isset($this->sxuser['id']) ? $this->sxuser['id'] : '';
        $username = isset($this->sxuser['merge_username']) ? $this->sxuser['merge_username'] : '';
        $acType   = isset($this->sxuser['actype']) ? $this->sxuser['actype'] : 1;
        $oddType  = isset($this->sxuser['oddtype']) ? $this->sxuser['oddtype'] : 'A';
        $userInfo = $this->M->get_one('pwd', 'user', array('id' => $snUid));
        $password = isset($userInfo['pwd']) ? $userInfo['pwd'] : '';
        if ($acType != 0 && $acType != 1) {
            $this->return_json(E_ARGS, '参数错误');
        }
        if (empty($sn) || empty($username) || empty($password) || empty($oddType)) {
            $this->return_json(E_ARGS, '参数错误');
        }
        // 试玩用户不入库
        if ($acType != 1) {
            $userInfo = [
                'sn'    => $sn,
                'snuid' => $snUid,
                'g_username' => $username,
                'g_password' => $password,
                'status'  => 1,
                'actype'  => $acType,
                'oddtype' => $oddType,
                'currency'   => 'CNY',
                'createtime' => date('Y-m-d H:i:s'),
            ];
            return $userInfo;
        }
        $this->M->select_db('shixun_w');
        $userInfo = $this->M->get_one('*', 'ky_user', ['g_username' => $username]);
        if (empty($userInfo)) {
                $userInfo = [
                    'sn'    => $sn,
                    'snuid' => $snUid,
                    'g_username' => $username,
                    'g_password' => $password,
                    'status'   => 1,
                    'actype'   => $acType,
                    'oddtype'  => $oddType,
                    'currency' => 'CNY',
                    'createtime' => date('Y-m-d H:i:s'),
                ];
                $insertId = $this->M->write('ky_user', $userInfo);
                !$insertId && $this->return_json(E_OP_FAIL, '添加ky会员数据失败');
        }
    }


    /*进行转账,平台积分转入ky*/
    public function transfer($credit = 0){
        //获取公库的配置,拿到剩余额度限额
        $sx_total_limit=trim($this->M->get_sx_set('credit'),'"');
        $snUid = isset($this->sxuser['id']) ? $this->sxuser['id'] : '';
        $username = isset($this->sxuser['merge_username']) ? $this->sxuser['merge_username'] : '';
        $type = isset($this->sxuser['type']) ? $this->sxuser['type'] : 'IN';
        $type = strtoupper($type);
        $credit = $credit ? $credit : $this->sxuser['credit'];
        if(sprintf("%.2f", $credit)>$credit){
            $credit-=0.01;
        }
        $credit = sprintf("%.2f", $credit);
        /*阻止重放攻击*/
        $lock=$this->M->get_sx_set('lock:'.$snUid);
        if($lock){
            $this->return_json(E_OP_FAIL,'您的操作过于频繁');
        }else{
            $rs=$this->M->update_sx_set('lock:'.$snUid,'lock',10);
        }
        if (empty($username) || empty($type)) {
            $this->return_json(E_ARGS, '参数错误');
        }elseif ($credit < 1){
            $this->return_json(E_ARGS, '转账金额过低,单次转账至少1元');
        }
        if ($type == 'IN' &&  $sx_total_limit < $credit) {
            $this->return_json(E_OP_FAIL, '平台可使用额度不足!');
        }
        //检测是否注册，未注册直接注册
        $user_info = $this->dg_user->user_info($username,'*','ky');
        if (empty($user_info)) {
            $this->signup();
        }
        //$credit = sprintf("%.2f", $credit);
        $this->M->select_db('private');
        $userMoney = $this->M->get_one('balance', 'user', array('username' => substr($username, 3)));
        if ($type == 'IN' && $userMoney['balance'] < $credit) {
            $this->return_json(E_ARGS, '系统余额不足');
        }

        if($type == 'IN')
        {
            $info=$this->add_point($credit);
            $rs = $info['rs'];
            $price=$credit;
        }else
        {
            $info=$this->reduce_point($credit);
            $rs = $info['rs'];
            $price=$credit;
        }
        $billNo = $info['orderid'];
        if(isset($rs['d']['code']) &&  $rs['d']['code']==0)
        {
            $this->transfer_success($credit,$type,$snUid,$billNo,$sx_total_limit,$username);
        } else
        {
            $result = $this->search_order_status($billNo);
            if (isset($result['d']['status'])) {
                switch ($result['d']['status']) {
                    case 0: //成功
                        $this->transfer_success($credit,$type,$snUid,$billNo,$sx_total_limit,$username);
                    break;
                    case -1: //延迟加入失败
                        sleep(5);
                        if($type == 'IN') {
                            $info=$this->add_point($credit);
                            $rs = $info['rs'];
                            $price=$credit;
                        } else {
                            $info=$this->reduce_point($credit);
                            $rs = $info['rs'];
                            $price=$credit;
                        }
                        $billNo = $info['orderid'];
                        if(isset($rs['d']['code']) &&  $rs['d']['code']==0) {
                            $this->transfer_success($credit,$type,$snUid,$billNo,$sx_total_limit,$username);
                        } else {
                            wlog(APPPATH . 'logs/ky/' . date('Y_m_d') . '.log', $billNo . '开元棋牌更新会员额度失败,code是:'.$rs['d']['code']);
                            $this->return_json(E_OP_FAIL, '网络错误');
                        }
                    break;
                    case 2: //因无效的转账金额引致的失败
                        wlog(APPPATH . 'logs/ag/' . date('Y_m_d') . '.log', $billNo . ' amount invalid!');
                        $this->return_json(E_OP_FAIL, '无效的转账金额');
                        break;
                    default:
                        wlog(APPPATH . 'logs/ag/' . date('Y_m_d') . '.log', $billNo . ' network_error_1');
                        $this->return_json(E_OP_FAIL, '网络错误');
                }

            } else {
                wlog(APPPATH . 'logs/ag/' . date('Y_m_d') . '.log', $billNo . ' network_error_2:' . json_encode($result, 320));
                $this->return_json(E_OP_FAIL, '网络错误');
            }

        }
        $this->return_json(OK);
    }
   /*账户分数查询*/
    public function get_balance($type=1){
        $this->load->model('sx/dg/user_model', 'dg_user');
        $kyUser = $this->dg_user->user_info($this->sxuser['merge_username'], '*', 'ky');
        if (empty($kyUser)) {
            $this->return_json(OK, array('balance' => 0));
        }
        $username = $this->sxuser['merge_username'];
        $data = [];
        $data['s'] = 1;
        $data['account'] = $username;
        $rs = $this->userapi->get_api_data($data,1,$this->get_sn());
        if(isset($rs['d']['code'])&&$rs['d']['code']==0&&$type==1){
            /*同步ky_user信息*/
            $data = $this->update_balance($username,$rs['d']['money'],'ky');
            $this->return_json(OK,array('balance'=>$rs['d']['money']));
        }else{
            return $rs;
        }
    }
    /*账户分数查询*/
    public function search_point($type=1){
        $username = $this->sxuser['merge_username'];
        $data = [];
        $data['s'] = 1;
        $data['account'] = $username;
        $rs = $this->userapi->get_api_data($data,1,$this->get_sn());
        if(isset($rs['d']['code'])&&$rs['d']['code']==0&&$type==1){
            /*同步ky_user信息*/
            $data = $this->update_balance($username,$rs['d']['money'],'ky');
            $this->return_json(OK,array('money'=>$rs['d']['money']));
        }else{
            return $rs;
        }
    }
    /**************账户分数管理START****************/
    /*玩家上分*/
    public function add_point($money = 100){
        $username=$this->sxuser['merge_username'];
        $data['s']=2;
        $data['account'] = $username;
        $data['money']=$money;
        $data['orderid']=$this->userapi->getOrderId($this->get_sn());
        $rs = $this->userapi->get_api_data($data,1,$this->get_sn());
        $tempinfo['rs']=$rs;
        $tempinfo['orderid']=$data['orderid'];
        return $tempinfo;
    }
    /*玩家下分*/
    public function reduce_point($money = 100){
        $username=$this->sxuser['merge_username'];
        $data['s']=3;
        $data['account']=$username;
        $data['money']=$money;
        $data['orderid']=$this->userapi->getOrderId($this->get_sn());
        $rs = $this->userapi->get_api_data($data,1,$this->get_sn());
        $tempinfo['rs']=$rs;
        $tempinfo['orderid']=$data['orderid'];
        return $tempinfo;
    }

    /**************账户分数管理END****************/

    /**
     * 更新视讯金额表
     * @param $username
     * @param $balance
     * @return bool
     */
    private function update_balance($username, $balance)
    {
        return $this->dg_user->update_balance($username, $balance, $this->platform_name);
    }
    /**
     * 一键转入转出
     */
    public function all_transfer()
    {
        $type = isset($this->sxuser['type']) ? $this->sxuser['type'] : '';
        $username = isset($this->sxuser['merge_username']) ? $this->sxuser['merge_username'] : '';
        if ($type == 'IN') {
            $credit = $this->M->get_one('balance', 'user', array('username' => substr($username, 3)));
        } elseif ($type == 'OUT') {
            $credit = $this-> get_balance(2);
        } else {
            $this->return_json(E_ARGS, '参数错误');
        }
        $credit = isset($credit['balance']) ? $credit['balance'] : $credit['d']['money'];
    /*    if(sprintf("%.2f", $credit)>$credit){
            $credit-=0.01;
        }*/
        $this->transfer($credit);
    }

    /**
     * 查询订单的状态
     */
    public function search_order_status($orderid)
    {
        $data['s']=4;
        $data['orderid']=$orderid;
        $rs = $this->userapi->get_api_data($data,1,$this->get_sn());
        return $rs;

    }


    /**
     * 上下分成功对数据库处理
     */
    public function transfer_success($credit,$type,$snUid,$billNo,$sx_total_limit,$username)
    {
        $user_balance=$this->get_balance(0);
        //更新余额度
        $price = $type == 'IN' ? 0 - $credit : $credit;

        $flag = $this->M->update_banlace($snUid, $price, $billNo, 21, 'ky额度转换');
        if (!$flag) {
            $flag = $this->M->update_banlace($snUid, $price, $billNo, 21, 'ky额度转换');
        }
        if (!$flag) {
            wlog(APPPATH . 'logs/ky/' . date('Y_m_d') . '.log', $billNo . ' 更新会员user和cash_list失败!');
        } else {
            //更新成功并且已经进行了相关操作后对总额度（redis）进行处理
            $result=$this->M->update_sx_set('credit',$sx_total_limit+$price,0);
            if (!$result) {
                wlog(APPPATH . 'logs/ky/' . date('Y_m_d') . '.log', $billNo . '更新总额度（redis）失败');
            }else{
                $this->load->model('sx/credit_model','credit');
                $res=$this->credit->update_credit($sx_total_limit,$price,'ky',$this->get_sn());
            }
        }

            $rs=$this->update_balance($username, $user_balance['d']['money']);
        if (!$rs) {
            wlog(APPPATH . 'logs/ky/' . date('Y_m_d') . '.log', $billNo . 'ky_user更新会员额度失败');
        }
        $balance = $this->dg_user->get_balance($username, $this->platform_name)['balance'];
        $this->load->model('sx/dg/fund_model');
        //写入对应视讯平台现金记录
        $fundstatus = $this->fund_model->fund_write($username, $type == 'IN' ? 1 : 2, $type == 'OUT' ? '-' . $credit : $credit, $balance, $this->platform_name, $this->sxuser['sn'], $billNo);
        if (!$fundstatus) {
            wlog(APPPATH . 'logs/ky/' . date('Y_m_d') . '.log', $billNo . 'ky_fund插入记录失败');
        }
    }
}