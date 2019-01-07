<?php

defined('BASEPATH') OR exit('No direct script access allowed');

include_once FCPATH . 'api/core/SX_Controller.php';

class User extends SX_Controller
{
    protected $user_api;
    protected $account_api;
    protected $platform_name = 'dg';

    public function __construct()
    {
        parent::__construct();
        $this->load->library('BaseApi');
        $this->user_api = BaseApi::getinstance($this->platform_name, 'dgUser', $this->sxuser['sn']);
        $this->account_api = BaseApi::getinstance($this->platform_name, 'account', $this->sxuser['sn']);
    }

    /**
     * 会员注册
     * $username 用户名
     * $password(md5)
     * $winLimit 奖金限额( 默认不限制 )
     * $currencyName 货币名称( 默认CNY )
     * $data 目标限红组号( 不填则为A组 )
     * return code_message
     */
    private function signup()
    {
        $win_limit = isset($this->sxuser['win_limit']) ? intval($this->sxuser['win_limit']) : 0;
        $oddtype = isset($this->sxuser['oddtype']) ? $this->sxuser['oddtype'] : 'A';
        $userInfo = $this->M->get_one('pwd', 'user', array('id' => $this->sxuser['id']));
        $data = $this->user_api->signup($this->sxuser['merge_username'], $userInfo['pwd'], $win_limit, $oddtype);
        if (isset($data['codeId']) && $data['codeId'] == 0) {
            $insert_data['sn'] = $this->sxuser['sn'];
            $insert_data['snuid'] = $this->sxuser['id'];
            $insert_data['g_username'] = $this->sxuser['merge_username'];
            $insert_data['g_password'] = $userInfo['pwd'];
            $insert_data['balance'] = '0.00';
            $insert_data['currency'] = 'CNY';
            $insert_data['createtime'] = date('Y-m-d H:i:s');
            $insert_data['status'] = 1;
            $insert_data['win_limit'] = $win_limit;
            $insert_data['oddtype'] = $oddtype;

            $this->load->model('sx/dg/user_model', 'user_model');
            if (!$this->user_model->add_user($this->platform_name, $insert_data)) {
                wlog(APPPATH . 'logs/dg/' . date('Y_m_d') . '.log', 'signup user:' . $this->sxuser['merge_username'] . ' error!');
            }
        }
        return $data;
    }
    /**
     * 会员存取款 拉取用户最新余额 用户加减余额 && 写入流水 && 判断两个余额是否一致，不一致则回滚
     * @param float $credit 为存取款金额
     * @comment $amount 为存取款金额 请确保保留不超过3位小数，否则将收到错误码11
     * @comment $transfer_id 转账流水号
     * @comment $type IN存入 OUT转出
     */
    public function transfer($credit = 0)
    {
        $set=$this->M->get_gcset();
        $sx_total_limit=trim($this->M->get_sx_set('credit'),'"');
        $username = $this->sxuser['merge_username'];
        $snuid = $this->sxuser['id'];
        $amount = $credit ? (float)$credit : $this->sxuser['credit'];
        $type = $this->sxuser['type'];
        if (empty($username) || empty($snuid) || !is_numeric($amount) || !in_array($type, ['IN', 'OUT'])) {
            $this->return_json(E_ARGS, '参数错误');
        }
        if(sprintf("%.2f", $amount)>$amount){
            $amount-=0.01;
        }
        $amount = $type == 'IN' ? sprintf("%.2f", $amount) : -sprintf("%.2f", $amount);
        //var_dump($amount);exit();
        if($amount > $sx_total_limit){
            $this->return_json(E_OP_FAIL, '平台可使用额度不足!');
        }
        if(abs($amount) < 1){
            $this->return_json(E_ARGS, '转账金额过低,单次转账至少1元');
        }
        $type = $amount > 0 ? 1 : 2;
        $transfer_id = $this->sn_to_num($this->sxuser['sn']) . sprintf("%08d", $snuid) . date('YmdHis');

        $userMoney = $this->M->get_one('balance', 'user', array('username' => substr($username, 3)));
        if ($userMoney['balance'] < $amount) {
            $this->return_json(E_ARGS, '系统余额不足');
        }

        $this->load->model('sx/dg/User_model','user_model');
        $dgUser = $this->user_model->user_info($this->sxuser['merge_username'], '*', 'dg');

        if (empty($dgUser)) {
            $this->signup();
        }

        $this->load->model('sx/dg/Fund_model','fund_model');
        try {
            $data = $this->account_api->transfer($username, $amount, $transfer_id);
            if (!$data || $data['codeId'] != 0) {
                $this->return_json(E_OP_FAIL, '额度转换失败');
            }
            //更新余额并写入现金记录--GC_MODEL
            $flag = $this->M->update_banlace($snuid, -$amount, $transfer_id, 21, 'dg额度转换');
            if (!$flag) {
                $flag = $this->M->update_banlace($snuid, -$amount, $transfer_id, 21, 'dg额度转换');
                if (!$flag) {
                    wlog(APPPATH . 'logs/dg/' . date('Y_m_d') . '.log', $transfer_id . ' 更新会员额度失败!');
                }else{
                    //更新成功并且已经进行了相关操作后对总额度进行处理
                    $rs=$this->M->update_sx_set('credit',$sx_total_limit-$amount,0);
                    $this->load->model('sx/credit_model','credit');
                    $rs=$this->credit->update_credit($sx_total_limit,-$amount,'dg',$this->get_sn());
                }
            }else{
                //更新成功并且已经进行了相关操作后对总额度进行处理
                $rs=$this->M->update_sx_set('credit',$sx_total_limit-$amount,0);
                $this->load->model('sx/credit_model','credit');
                $rs=$this->credit->update_credit($sx_total_limit,-$amount,'dg',$this->get_sn());
            }
            $balance = $data['member']['balance'];
            //更新本地余额
            $this->user_model->update_balance($username, $balance, $this->platform_name);
           $result= $this->fund_model->fund_write($username, $type, $amount, $balance, $this->platform_name, $this->sxuser['sn'], $transfer_id);
        } catch (Exception $e) {
            wlog(APPPATH . 'logs/dg/' . date('Y_m_d') . '.log', 'error: ' . $e->getMessage());
        }

        if (isset($data['codeId']) && $data['codeId'] == 0) {
            $this->return_json(OK, array('balance' => $data['member']['balance']));
        } else {
            $this->return_json(E_OP_FAIL, '额度转换失败');
        }
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
            $credit = $this->getBalance(true);
        } else {
            $this->return_json(E_ARGS, '参数错误');
        }
        $credit = isset($credit['balance']) ? $credit['balance'] : 0;
        $this->transfer($credit);
    }

    /**
     * 会员登录
     * $username 用户名
     * $password(md5) 可以不传,如果密码不同,将自动修改DG数据库保存的密码
     * $lang 语言(默认为cn)
     * 进入wap游戏应再拼接上语言类型,拼接格式为"wap 登入地址 + token + &language=lang"
     * return token + "list":["flash 登入地址","wap 登入地址","直接打开APP地址"]
     */
    public function login()
    {
        $lang = isset($this->sxuser['lang']) ? $this->sxuser['lang'] : 'cn';
        $password = isset($this->sxuser['pwd']) ? $this->sxuser['pwd'] : '';
        $this->load->model('sx/dg/user_model', 'dg_user');
        $dgUser = $this->dg_user->user_info($this->sxuser['merge_username'], '*', 'dg');
        //var_dump($dgUser);exit();
        if (empty($dgUser)) {
            $this->signup();
        }
        $data = $this->user_api->login($this->sxuser['merge_username'], $password, $lang);
        if ($data['token'] == '#') {
            $t = $this->signup();
            $data['token'] = $t['token'];
        }
        $this->return_json(OK, $data);
    }
    /**
     * 获取会员余额并实例化user_mode更新余额
     * @param $flag bool
     * @return array
     */
    public function getBalance($flag = false)
    {
        $this->load->model('sx/dg/user_model', 'dg_user');
        $dgUser = $this->dg_user->user_info($this->sxuser['merge_username'], '*', 'dg');
        if (empty($dgUser)) {
            $this->return_json(OK, array('balance' => 0));
        }
        if ($data = $this->user_api->updateBalance($this->sxuser['merge_username'], $this->platform_name)) {
            if (!$flag) {
                $this->return_json(OK, array('balance' => $data['member']['balance']));
            } else {
                return array('balance' => $data['member']['balance']);
            }
        }
        wlog(APPPATH . 'logs/dg/' . date('Y_m_d') . '.log', '拉取用户:' . $this->sxuser['merge_username'] . '余额的失败,错误代码:' . $data['codeId']);
    }
    /**
     * 获取会员余额并实例化user_mode更新余额
     * @param $flag bool
     * @return array
     */
    public function get_balance($flag = false)
    {
        $this->load->model('sx/dg/user_model', 'dg_user');
        $dgUser = $this->dg_user->user_info($this->sxuser['merge_username'], '*', 'dg');
        if (empty($dgUser)) {
            $this->return_json(OK, array('balance' => 0));
        }
        if ($data = $this->user_api->updateBalance($this->sxuser['merge_username'], $this->platform_name)) {
            if (!$flag) {
                $this->return_json(OK, array('balance' => $data['member']['balance']));
            } else {
                return array('balance' => (float)$data['member']['balance']);
            }
        }
        wlog(APPPATH . 'logs/dg/' . date('Y_m_d') . '.log', '拉取用户:' . $this->sxuser['merge_username'] . '余额的失败,错误代码:' . $data['codeId']);
    }

    private function sn_to_num($sn){
        $sn = strtolower($sn);
        $len=strlen($sn);
        if(preg_match('/[0-9]/', substr($sn,0,$len-1))) return $sn;

        $array=array('a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z');

        $num = '';
        for($i=0;$i<$len;$i++){
            $index=array_search($sn[$i],$array);
            if($index === false) { //未找到说明是数字
                $num.= $sn[$i].'0';
            }else{
                $var=sprintf("%02d", $index+1);//生成2位数，不足前面补0
                $num.=$var;
            }
        }
        return $num;
    }
}
