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
        //获取公库的配置,拿到剩余额度限额
        //$sx_total_limit=trim($this->M->get_sx_set('credit'),'"');
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
        $lock=$this->M->get_sx_set('lock:'.$snUid);
        if($lock){
            $this->return_json(E_OP_FAIL,'您的操作过于频繁');
        }else{
            $rs=$this->M->update_sx_set('lock:'.$snUid,'lock',10);
        }
        //检测是否注册，未注册直接注册
        $this->load->model('sx/dg/user_model', 'dg_user');
        $user_info = $this->dg_user->user_info($username);
        if (empty($user_info)) {
            $this->return_json(E_ARGS, '该会员尚未在该平台注册!');
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
                        wlog(APPPATH . 'logs/ag/' . date('Y_m_d') . '.log', $billNo . ' 更新会员额度失败!');
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
            } else { //失败调用查询订单状态
                $status = $this->user_api->query_order_status($billNo, $user_info['actype']);
                if (isset($status['info'])) {
                    switch ($status['info']) {
                        case 0: //成功
                            wlog(APPPATH . 'logs/ag/' . date('Y_m_d') . '.log', $billNo . ' confirm_transfer failed but queryOrderStatus is OK');
                            $balance_data = $this->user_api->get_balance($username, $user_info['actype'], $user_info['g_password']);
                            $this->load->model('sx/dg/fund_model');
                            //更新余额度
                            $price = $type == 'IN' ? 0 - $credit : $credit;
                            $flag = $this->M->update_banlace($snUid, $price, $billNo, 21, 'ag额度转换');
                            if (!$flag) {
                                $flag = $this->M->update_banlace($snUid, $price, $billNo, 21, 'ag额度转换');
                                if (!$flag) {
                                    wlog(APPPATH . 'logs/ag/' . date('Y_m_d') . '.log', $billNo . ' 更新会员额度失败!');
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
                                wlog(APPPATH . 'logs/ag/' . date('Y_m_d') . '.log', $billNo . ' confirm_transfer failed twice!');
                            }
                            break;
                        case 2: //因无效的转账金额引致的失败
                            wlog(APPPATH . 'logs/ag/' . date('Y_m_d') . '.log', $billNo . ' confirm_transfer failed: amount invalid!');
                            break;
                        default:
                            wlog(APPPATH . 'logs/ag/' . date('Y_m_d') . '.log', $billNo . ' network_error_1');
                    }
                } else {
                    wlog(APPPATH . 'logs/ag/' . date('Y_m_d') . '.log', $billNo . ' network_error_2:' . json_encode($status, 320));
                }
            }
        } else {
            wlog(APPPATH . 'logs/ag/' . date('Y_m_d') . '.log', __FUNCTION__ . ' PrepareTransferCredit failed!');
            if (empty($data['msg'])) {
                $data['msg'] = 'PrepareTransferCredit failed!';
            }
            $this->return_json(E_OP_FAIL, $data['msg']);
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
}
