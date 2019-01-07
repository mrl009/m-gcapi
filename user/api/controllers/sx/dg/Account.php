<?php

defined('BASEPATH') OR exit('No direct script access allowed');

include_once FCPATH . 'api/core/SX_Controller.php';

class Account extends SX_Controller
{
    protected $account_api;
    protected $user_api;
    protected $platform_name = 'dg';

    public function __construct()
    {
        parent::__construct();
        $this->load->library('BaseApi');
        $this->account_api = BaseApi::getinstance($this->platform_name, 'account', $this->sxuser['sn']);
        $this->user_api = BaseApi::getinstance($this->platform_name, 'dgUser', $this->sxuser['sn']);
    }

    /**
     * 会员存取款 拉取用户最新余额 用户加减余额 && 写入流水 && 判断两个余额是否一致，不一致则回滚
     * @param $usename
     * @param $amount 为存取款金额，正数存款负数取款，请确保保留不超过3位小数，否则将收到错误码11
     * @param $transfer_id 转账流水号
     */
    public function transfer()
    {
        $username = $this->sxuser['merge_username'];
        $snuid = $this->sxuser['id'];
        $amount = $this->sxuser['amount'];
        $username && $snuid && is_numeric($amount) OR exit('{"codeId":"' . E_ARGS . '","msg":"params invalid"}');
        $transfer_id = sn_to_num($this->sxuser['sn']) . sprintf("%08d", $snuid) . date('YmdHis');

        $type = $amount > 0 ? 1 : 2;
        //$amount = substr(number_format($amount, 3), 0, -1); //保留两位小数 非四舍五入
        $amount = sprintf("%.2f",$amount);
        //BIGCHAO
        $userMoney = $this->M->get_one('balance', 'user',array('username'=>substr($username, 3)));
        if ($userMoney['balance']<$amount) {
            $this->return_json(E_ARGS, '系统余额不足');
        }
        //ENDBIGCHAO
        
        $this->load->model('sx/dg/user_model');
        $this->load->model('sx/dg/fund_model');
        //共享钱包无法使用，回滚机制注释
        //$this->user_model->db->trans_begin();
        //$this->fund_model->db->trans_begin();
        try {
            //更新最新用户余额
            /*if( !$this->user_api->updateBalance( $username, $this->platform_name ) )
            {
                throw new Exception( '用户:' . $username . ',流水号:' . $transfer_id . ',拉取用户余额失败，本次存取款失败！' );
            }*/

            $data = $this->account_api->transfer($username, $amount, $transfer_id);
            if (!$data || $data['codeId'] != 0) {
                //$this->ajax_return( [ 'codeId' => $data[ 'codeId' ] ] );
                $this->return_json(OK, ['codeId' => $data['codeId']]);
                return false;
                //throw new Exception( '用户:' . $username . ',流水号:' . $transfer_id . ',dg平台充值失败，平台错误码:' . $data[ 'codeId' ] . '，本次存取款失败！' );
            }

            //更新余额并写入现金记录--GC_MODEL
            $flag = $this->M->update_banlace($snuid, -$amount, $transfer_id, 21, 'dg额度转换');
            if (!$flag) {
                $flag = $this->M->update_banlace($snuid, -$amount, $transfer_id, 21, 'dg额度转换');
                if (!$flag) {
                    wlog(APPPATH . 'logs/ag/' . date('Y_m_d') . '.log', $billNo . ' 更新会员额度失败!');
                }
            }

            $balance = $data['member']['balance'];
            //更新本地余额
            $this->user_model->update_balance($username, $balance, $this->platform_name);
            //$balance = $this->user_model->oper_user_balance( $username, $amount, $this->platform_name );
            $this->fund_model->fund_write($username, $type, $amount, $balance, $this->platform_name, $this->sxuser['sn'], $transfer_id);

            /*对比dg平台余额和本地余额
            if( $balance != $dg_balance )
            {
                throw new Exception( '用户:' . $username . ',流水号:' . $transfer_id . ',本地余额和平台余额不一致，本次存取款失败，回滚！' );
            }

            if( !$fund )
            {
                throw new Exception( '用户:' . $username . ',流水号:' . $transfer_id . ',流水表写入失败，本次存取款失败，回滚！' );
            }

            $this->user_model->db->trans_commit();
            $this->fund_model->db->trans_commit();*/
        } catch (Exception $e) {
            //$this->write( 'transfer', '当前时间:' . date( 'Y-m-d H:i:s' ) . $e->getMessage() . "\n" , $this->platform_name );
            wlog(APPPATH . 'logs/dg/' . date('Y_m_d') . '.log', 'error: ' . $e->getMessage());
            //$this->account_api->inform( $username, $amount, $transfer_id ); //合作方回滚
            //$this->user_model->db->trans_rollback();
            //$this->fund_model->db->trans_rollback();
        }

        if (isset($data['codeId']) && $data['codeId'] == 0) {
            //$this->ajax_return( [ 'codeId' => $data[ 'codeId' ], 'balance' => $data[ 'member' ][ 'balance' ] ] );
            $this->return_json(OK, array('balance' => $data['member']['balance']));
        } else {
            $this->return_json(OK, ['codeId' => $data['codeId']]);

        }

    }

    /**
     * 确认存取款结果接口
     * @param $transfer_id 转账流水号
     * @return bool|mixed
     * 该接口用于确认转账操作是否成功,
     * codeId=0表示对应的流水号已经存在, 98表示该笔流水还未处理
     */
    public function checkTransfer()
    {
        $data = $this->account_api->checkTransfer($this->sxuser['transfer_id']);
        //$this->ajax_return( [ 'codeId' => $data[ 'codeId' ] ] );
        $this->return_json(OK, ['codeId' => $data['codeId']]);
    }

    /**
     * 请求回滚转账事务
     * @param $username
     * @param $amount
     * @param $ticketId
     * @param $data
     * @return bool|mixed
     * 1.请求参数与待回滚的转账请求参数相同,以流水号为准
     * 2.该请求存取款应区别对待:
     * 如果amount < 0,查询数据库有对应记录则删除对应的扣钱操作,否则直接返回操作成功。
     * 如果amount >=0 ,如果发现无对应记录则插入一条加钱记录,否则无需处理直接返回操作成功
     * 3.该请求可能会有重复请求, 请确保数据库流水号唯一
     * 4.如需再次请求请返回codeId=98 PDF
     */
    public function inform()
    {
        $this->ajax_return($this->account_api->inform($this->sxuser['merge_username'], $this->sxuser['amount'], $this->sxuser['sn'] . $this->sxuser['transfer_id']));
    }

    /**
     * 注单ID
     * @param $ticketId
     * @return bool|mixed
     * 该接口用于对账查询
     */
    public function order()
    {
        $data = $this->account_api->order();
        if ($data)
            $this->ajax_return(['codeId' => $data['codeId'], 'list' => $data['list']]);
    }
}
