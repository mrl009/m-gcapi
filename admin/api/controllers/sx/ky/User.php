<?php
defined('BASEPATH') OR exit('No direct script access allowed');
include_once FCPATH . 'api/core/SX_Controller.php';
class User extends SX_Controller
{
    protected $platform_name = 'ky';
    public function __construct()
    {
        //var_dump(123456);exit();
        parent::__construct();
        $this->load->library('ky/KyuserApi','','userapi');
        $this->load->model('sx/dg/user_model', 'dg_user');
    }
    /*进行转账,平台积分转入ky*/
    public function transfer($credit = 0){
        //var_dump(2222);exit();
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
            //$this->signup();
            $this->return_json(E_ARGS, '该账户并未注册');
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
        //$this->return_json(E_OP_FAIL, $info);
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