<?php

defined('BASEPATH') OR exit('No direct script access allowed');
include_once FCPATH . 'api/core/SX_Controller.php';
class User extends SX_Controller
{
    protected $user_api;
    protected $platform_name = 'ag';

    public function __construct()
    {
        parent::__construct();
        $this->load->library('BaseApi');
        $this->load->helper('common_helper');
        $this->user_api = BaseApi::getinstance($this->platform_name, 'user', $this->sxuser['sn']);
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
        $data = $this->user_api->signup($username, $acType, $password, $oddType, 'CNY');
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
        if ($data['info'] == 0) {
            $this->M->select_db('shixun_w');
            $userInfo = $this->M->get_one('*', 'ag_user', ['g_username' => $username]);
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
                $insertId = $this->M->write('ag_user', $userInfo);
                !$insertId && $this->return_json(E_OP_FAIL, '添加ag会员数据失败');
            }
            $userInfo['is_signup'] = 1;
            return $userInfo;
        } else {
            $this->return_json(E_UNKNOW, $data['msg']);
        }
    }

    /**
     * 查询余额度
     * @param bool $flag
     * @return array
     */
    public function get_balance($flag = false)
    {
        $username = $this->sxuser['merge_username'];
        $this->load->model('sx/dg/user_model', 'dg_user');
        $user_info = $this->dg_user->user_info($username);
        if (empty($user_info)) {
            $this->return_json(OK, array('balance' => 0));
        }
        $data = $this->user_api->get_balance($username, $user_info['actype'], $user_info['g_password']);
        //更新余额度
        if (isset($data['info']) && is_numeric($data['info'])) {
            $this->update_balance($username, $data['info']);
        }
        if ($flag) {
            return ['balance' => $data['info']];
        } else {
            $this->return_json(OK, array('balance' => (float)$data['info']));
        }
    }

    /**
     * 更新视讯金额表
     * @param $username
     * @param $balance
     * @return bool
     */
    private function update_balance($username, $balance)
    {
        $this->load->model('sx/dg/user_model', 'dg_user');
        $this->dg_user->update_balance($username, $balance, $this->platform_name);
        return true;
    }

    /**
     * 预备转帐与确认转账;由于新加入总额度控制所以需要在每次转换前进行一系列剩余额度判断以及在转账完成后的额度加减操作
     * @param int $credit
     */
    public function transfer($credit = 0)
    {
        $this->transfer_new($credit);
        //获取公库的配置,拿到剩余额度限额
        $sx_total_limit=trim($this->M->get_sx_set('credit'),'"');
        $snUid = isset($this->sxuser['id']) ? $this->sxuser['id'] : '';
        $username = isset($this->sxuser['merge_username']) ? $this->sxuser['merge_username'] : '';
        $type = isset($this->sxuser['type']) ? $this->sxuser['type'] : '';
        $credit = $credit ? (int)$credit : $this->sxuser['credit'];
        if (empty($username) || empty($type) || !is_numeric($credit)) {
            $this->return_json(E_ARGS, '参数错误');
        }
        if($credit < 1){
            $this->return_json(E_ARGS, '转账金额过低,单次转账至少1元');
        }
        /*阻止重放攻击*/
        /*$lock=$this->M->get_sx_set('lock:'.$snUid);
        if($lock){
            $this->return_json(E_OP_FAIL,'您的操作过于频繁');
        }else{
            $this->M->update_sx_set('lock:'.$snUid,'lock',10);
        }*/
        //检测是否注册，未注册直接注册
        $this->load->model('sx/dg/user_model', 'dg_user');
        $user_info = $this->dg_user->user_info($username);
        if (empty($user_info)) {
            $this->signup();
        }
        $billNo = BaseApi::AG_DEFAULT_CAGENT . sn_to_num($this->sxuser['sn']) . sprintf("%08d", $snUid) . time();
        $credit = sprintf("%.2f", $credit);
        $userMoney = $this->M->get_one('balance', 'user', array('username' => substr($username, 3)));
        if ($type == 'IN' && $userMoney['balance'] < $credit) {
            $this->return_json(E_ARGS, '系统余额不足');
        }
        if ($type == 'IN' &&  $sx_total_limit < $credit) {
            $this->return_json(E_OP_FAIL, '平台可使用额度不足!');
        }
        // 加锁，转换成功立马解锁
        $ag_transfer_lock = 'transfer_lock:ag_' . $snUid;
        $lock = $this->M->fbs_lock($ag_transfer_lock, 5 * 60);
        if (!$lock) {
            $this->return_json(E_OP_FAIL, '额度转换异常，请稍后再试');
        }
        $data = $this->user_api->transfer($username, $user_info['g_password'], $user_info['actype'], $billNo, $type, $credit);
        if (isset($data['info']) && $data['info'] === '0') //预备转账成功
        {
            $flag = 1;
            $confirm_data = $this->user_api->confirm_transfer($username, $user_info['g_password'], $user_info['actype'], $billNo, $type, $credit, $flag);
            //更新余额度 && 写入流水
            if (isset($confirm_data['info']) && $confirm_data['info'] == 0) {
                $balance_data = $this->user_api->get_balance($username, $user_info['actype'], $user_info['g_password']);
                $this->load->model('sx/dg/fund_model');
                //更新余额度
                $price = $type == 'IN' ? 0 - $credit : $credit;
                //更新余额并写入现金记录--GC_MODEL
                $flag = $this->M->update_banlace($snUid, $price, $billNo, 21, 'ag额度转换');
                if (!$flag) {
                    $flag = $this->M->update_banlace($snUid, $price, $billNo, 21, 'ag额度转换');
                    if (!$flag) {
                        wlog(APPPATH . 'logs/ag/' . date('Y_m_d') . '_' . $this->sxuser['sn'] . '.log', $billNo . ' 更新会员额度失败!');
                    }
                }else{
                    //更新成功并且已经进行了相关操作后对总额度进行处理
                    //$rs=$this->M->update_sx_set('credit',$sx_total_limit+$price,0);
                    $rs=$this->M->update_sx_set('credit',$sx_total_limit+$price,0);
                    $this->load->model('sx/credit_model','credit');
                    $rs=$this->credit->update_credit($sx_total_limit,$price,'ag',$this->get_sn());
                }
                //更新AG库里会员金额
                $this->update_balance($username, $balance_data['info']);
                $balance = $this->dg_user->get_balance($username, $this->platform_name)['balance'];
                //写入对应视讯平台现金记录
                $this->fund_model->fund_write($username, $type == 'IN' ? 1 : 2, $type == 'OUT' ? '-' . $credit : $credit, $balance, $this->platform_name, $this->sxuser['sn'], $billNo);
                $this->M->fbs_unlock($ag_transfer_lock);
            } else { //失败调用查询订单状态
                $status = $this->user_api->query_order_status($billNo, $user_info['actype']);
                if (isset($status['info'])) {
                    switch ($status['info']) {
                        case 0: //成功
                            wlog(APPPATH . 'logs/ag/' . date('Y_m_d') . '_' . $this->sxuser['sn'] . '.log', $billNo . ' confirm_transfer failed but queryOrderStatus is OK');
                            $balance_data = $this->user_api->get_balance($username, $user_info['actype'], $user_info['g_password']);
                            $this->load->model('sx/dg/fund_model');
                            //更新余额度
                            $price = $type == 'IN' ? 0 - $credit : $credit;
                            $flag = $this->M->update_banlace($snUid, $price, $billNo, 21, 'ag额度转换');
                            if (!$flag) {
                                $flag = $this->M->update_banlace($snUid, $price, $billNo, 21, 'ag额度转换');
                                if (!$flag) {
                                    wlog(APPPATH . 'logs/ag/' . date('Y_m_d') . '_' . $this->sxuser['sn'] . '.log', $billNo . ' 更新会员额度失败!');
                                }
                            }
                            $this->update_balance($username, $balance_data['info']);
                            $balance = $this->dg_user->get_balance($username, $this->platform_name)['balance'];
                            $this->fund_model->fund_write($username, $type == 'IN' ? 1 : 2, $type == 'OUT' ? '-' . $credit : $credit, $balance, $this->platform_name, $this->sxuser['sn'], $billNo);
                            break;
                        case 1: //失败, 订单未处理状态
                            sleep(5);
                            $confirm_data = $this->user_api->confirm_transfer($username, $user_info['g_password'], $user_info['actype'], $billNo, $type, $credit, $flag);
                            if (isset($confirm_data['info']) && $confirm_data['info'] == 0) {
                                $balance_data = $this->user_api->get_balance($username, $user_info['actype'], $user_info['g_password']);
                                $this->load->model('sx/dg/fund_model');
                                //更新余额度
                                $this->update_balance($username, $balance_data['info']);
                                $balance = $this->dg_user->get_balance($username, $this->platform_name)['balance'];
                                $this->fund_model->fund_write($username, $type == 'IN' ? 1 : 2, $type == 'OUT' ? '-' . $credit : $credit, $balance, $this->platform_name, $this->sxuser['sn'], $billNo);
                            } else {
                                wlog(APPPATH . 'logs/ag/' . date('Y_m_d') . '_' . $this->sxuser['sn'] . '.log', $billNo . ' confirm_transfer failed twice!');
                            }
                            break;
                        case 2: //因无效的转账金额引致的失败
                            wlog(APPPATH . 'logs/ag/' . date('Y_m_d') . '_' . $this->sxuser['sn'] . '.log', $billNo . ' confirm_transfer failed: amount invalid!');
                            break;
                        default:
                            wlog(APPPATH . 'logs/ag/' . date('Y_m_d') . '_' . $this->sxuser['sn'] . '.log', $billNo . ' network_error_1');
                    }
                } else {
                    wlog(APPPATH . 'logs/ag/' . date('Y_m_d') . '_' . $this->sxuser['sn'] . '.log', $billNo . ' network_error_2:' . json_encode($status, 320));
                }
                $this->M->fbs_unlock($ag_transfer_lock);
            }
        } else {
            wlog(APPPATH . 'logs/ag/' . date('Y_m_d') . '_' . $this->sxuser['sn'] . '.log', __FUNCTION__ . ' PrepareTransferCredit failed!');
            if (empty($data['msg'])) {
                $data['msg'] = 'PrepareTransferCredit failed!';
            }
            $this->M->fbs_unlock($ag_transfer_lock);
            $this->return_json(E_OP_FAIL, $data['msg']);
        }
        $this->return_json(OK);
    }

    // ag额度转换优化
    public function transfer_new($credit = 0)
    {
        $uid = isset($this->sxuser['id']) ? $this->sxuser['id'] : '';
        $username = isset($this->sxuser['merge_username']) ? $this->sxuser['merge_username'] : '';
        $type = isset($this->sxuser['type']) ? $this->sxuser['type'] : '';
        $credit = $credit ? (int)$credit : $this->sxuser['credit'];
        $credit = sprintf("%.2f", $credit);
        if (empty($username) || empty($type) || !is_numeric($credit)) {
            $this->return_json(E_ARGS, '参数错误');
        }
        if ($credit < 1) {
            $this->return_json(E_ARGS, '转账金额过低,单次转账至少1元');
        }
        // 校验平台额度
        $sx_total_limit = trim($this->M->get_sx_set('credit'), '"');
        if ($type == 'IN' && $sx_total_limit < $credit) {
            $this->return_json(E_OP_FAIL, '平台可使用额度不足!');
        }
        // 如果入款则校验用户余额
        if ($type == 'IN') {
            $user_balance = $this->M->get_one('balance', 'user', array('username' => substr($username, 3)));
            if ($user_balance['balance'] < $credit) {
                $this->return_json(E_ARGS, '系统余额不足');
            }
        }
        //检测是否注册
        $user_info = $this->checkUser($username);
        if (isset($user_info['is_signup']) && $user_info['is_signup'] == 1) {
            $this->return_json(E_OP_FAIL, '转换失败,请重新转换');
        }
        // 一分钟内不得重复操作
        $lock = $this->M->fbs_lock('transfer_lock:one_minute_' . $uid, 60);
        if (!$lock) {
            $this->return_json(E_OP_FAIL, '一分钟内不得重复操作');
        }
        // 加锁，转换成功立马解锁
        $ag_transfer_lock = 'transfer_lock:ag_' . $uid;
        $lock = $this->M->fbs_lock($ag_transfer_lock, EXPIRE_1);
        if (!$lock) {
            $this->return_json(E_OP_FAIL, '额度转换中，请稍等');
        }
        $bill_no = BaseApi::AG_DEFAULT_CAGENT . sn_to_num($user_info['sn']) . sprintf("%08d", $uid) . time();
        //预备转账
        $data = $this->user_api->transfer($username, $user_info['g_password'], $user_info['actype'], $bill_no, $type, $credit);
        if (isset($data['info']) && $data['info'] === '0') {
            $price = $type == 'IN' ? 0 - $credit : $credit;
            $log_data = ['uid' => $uid, 'username' => $username, 'bill_no' => $bill_no, 'type' => $type, 'price' => $price];
            wlog(APPPATH . 'logs/ag/transfer_' . date('Y_m_d') . '_' . $user_info['sn'] . '.log', '预转账成功:' . json_encode($log_data));
            //正式转账
            $this->M->db->trans_start();
            $flag = $this->M->update_banlace($uid, $price, $bill_no, 21, 'ag额度转换');
            if (!$flag) {
                wlog(APPPATH . 'logs/ag/transfer_' . date('Y_m_d') . '_' . $user_info['sn'] . '.log', '更新会员额度失败:' . json_encode($log_data));
                $this->return_json(E_OP_FAIL, '更新会员额度失败');
            }
            $confirm_data = $this->user_api->confirm_transfer($username, $user_info['g_password'], $user_info['actype'], $bill_no, $type, $credit, 1);
            if (isset($confirm_data['info']) && $confirm_data['info'] == 0) {
                wlog(APPPATH . 'logs/ag/transfer_' . date('Y_m_d') . '_' . $user_info['sn'] . '.log', '转账成功:' . json_encode($log_data));
                $this->M->db->trans_commit();
                $this->M->fbs_unlock($ag_transfer_lock);
                //更新视讯额度
                $this->M->update_sx_set('credit', $sx_total_limit + $price, 0);
                $this->load->model('sx/credit_model', 'credit');
                $flag = $this->credit->update_credit($sx_total_limit, $price, 'ag', $user_info['sn']);
                if (!$flag) {
                    wlog(APPPATH . 'logs/ag/transfer_' . date('Y_m_d') . '_' . $user_info['sn'] . '.log', '更新视讯额度失败:' . json_encode($log_data));
                }
                //更新AG库里会员金额
                $balance_data = $this->user_api->get_balance($username, $user_info['actype'], $user_info['g_password']);
                $this->update_balance($username, $balance_data['info']);
                //写入对应视讯平台现金记录
                $this->load->model('sx/dg/fund_model');
                $this->fund_model->fund_write($username, $type == 'IN' ? 1 : 2, $type == 'OUT' ? '-' . $credit : $credit, $balance_data['info'], $this->platform_name, $user_info['sn'], $bill_no);
            }
        } else {
            $this->M->db->trans_rollback();
            $this->M->fbs_unlock($ag_transfer_lock);
            wlog(APPPATH . 'logs/ag/transfer_' . date('Y_m_d') . '_' . $user_info['sn'] . '.log', '预转账失败' . $bill_no);
            $this->return_json(E_OP_FAIL, '预转换失败');
        }
        $this->return_json(OK);
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
            $credit = $this->get_balance(true);
        } else {
            $this->return_json(E_ARGS, '参数错误');
        }
        $credit = isset($credit['balance']) ? $credit['balance'] : 0;
        $this->transfer($credit);
    }

    /**
     * 确认订单状态
     */
    public function query_order_status()
    {
        $username = $this->sxuser['merge_username'];
        $billno = $this->sxuser['billno'];
        $username && $billno OR exit('{"codeId":"' . E_ARGS . '","msg":"params invalid"}');

        $this->load->model('sx/dg/user_model', 'dg_user');
        $user_info = $this->dg_user->user_info($username);
        $data = $this->user_api->query_order_status($billno, $user_info['actype']);
        $this->return_json(OK, $data);
    }

    /**
     * 登录
     */
    public function login()
    {
        $username = $this->sxuser['merge_username'];
        $acType = isset($this->sxuser['actype']) ? $this->sxuser['actype'] : 1;
        if (empty($username)) {
            $this->return_json(E_ARGS, '参数错误');
        }
        $this->load->model('sx/dg/user_model', 'dg_user');
        $user_info = $this->dg_user->user_info($username);
        if (empty($user_info) || $acType != 1) {
            $user_info = $this->signup();
        }
        $mh5 = isset($this->sxuser['mh5']) ? $this->sxuser['mh5'] : '';
        $game_type = isset($this->sxuser['game_type']) ? $this->sxuser['game_type'] : '';
        $data = $this->user_api->login($username, $user_info['g_password'], $user_info['actype'], $user_info['oddtype'], get_ip(), $mh5, $game_type);
        $this->return_json(OK, $data);
    }

    // 检验用户如果没有则自动注册
    private function checkUser($username)
    {
        $this->load->model('sx/dg/user_model', 'dg_user');
        $user_info = $this->dg_user->user_info($username);
        empty($user_info) && $user_info = $this->signup();
        return $user_info;
    }
}
