<?php

defined('BASEPATH') OR exit('No direct script access allowed');

include_once FCPATH.'api/core/SX_Controller.php';

class User extends SX_Controller
{
	protected $user_api;
	protected $platform_name = 'pt';

	public function __construct()
	{
		parent::__construct();
		$this->load->library('BaseApi');
        $this->load->model('sx/dg/user_model', 'dg_user');
		$this->user_api = BaseApi::getinstance( $this->platform_name, 'user', $this->sxuser['sn'] );
	}

	/**
	 * 注册
	 */
	public function signup()
	{
        $sn    = isset($this->sxuser['sn']) ? $this->sxuser['sn'] : '';
        $snUid = isset($this->sxuser['id']) ? $this->sxuser['id'] : '';
        $username = isset($this->sxuser['merge_username']) ? $this->sxuser['merge_username'] : '';
        $userInfo = $this->M->get_one('pwd', 'user', array('id' => $snUid));
        $password = isset($userInfo['pwd']) ? $userInfo['pwd'] : '';

        if (empty($sn) || empty($username) || empty($password) ) {
            $this->return_json(E_ARGS, '参数错误');
        }
		$username && $password || exit( '参数错误' );
        $this->load->model( 'sx/ag/user_model' );
        $data = $this->user_api->signup( $username, $password );

        if(  $data[ 'Code' ] == 0 && !$this->user_model->user_exists( $this->platform_name, $username ) )
        {
            $insert_data[ 'sn' ] = $this->sxuser[ 'sn' ];
            $insert_data[ 'snuid' ] = $this->sxuser[ 'id' ];
            $insert_data[ 'g_username' ] = $username;
            $insert_data[ 'g_password' ] = $password;
            $insert_data[ 'currency' ] = 'CNY';
            $insert_data[ 'createtime' ] = date( 'Y-m-d H:i:s' );
            $insert_data[ 'status' ] = 1;

            $this->user_model->db->insert( 'pt_user', $insert_data );

        }
		
	}

    /**
     * 检查存取状态
     */
    public function login()
    {
        $username = $this->sxuser['merge_username'];
        if (empty($username)) {
            $this->return_json(E_ARGS, '参数错误');
        }
        $user_info = $this->dg_user->user_info($username,'*','pt');

        if (empty($user_info)) {
            $this->signup();
        }


        $data = $this->user_api -> login ( $username ,get_ip(),'slot_01',$user_info);             $this->return_json(OK,'');

    }

    public function redirect()
    {
        // $id   = substr( $this->input->get( 'id', TRUE ), 5 );
        //$type = $this->input->get( 'type', TRUE );
        //	$id = substr($this->sxuser['id'], 5);
        //$type = $this->sxuser['type'];
        //$id = $_GET[ 'id' ] ? substr( $_GET[ 'id' ], 5 ) : 0;
        //$type = $_GET[ 'type' ] ? $_GET[ 'type' ] : '';
//		$id && $type || exit( 'invalid arguments!' );
//		$this->load->model( 'sx/User_model', 'user_model' );
//		$user = $this->user_model->getUserById($id, 'pt');

        $user[ 'type' ] = 'wap';
        $user[ 'username' ] = 'gc0zhufei1';
        $user[ 'type' ] = 'd2f6ac27f4c86ca622553e0acdc1ec0c';
        //print_r($user);
        $this->load->view('pt/redirect', $user );
    }

//	/**
//	 * 用于检测玩家是否已经存在
//	 */
//	public function check_user_exists()
//	{
//		$username = $this->sxuser[ 'merge_username' ];
//		$username || exit( '{"code":"4001","msg":"Invalid Paramter"}' );
//		$data = $this->user_api->check_user_exists( $username );
//		$this->ajax_return( $data );
//	}

	/**
	 * 查询玩家余额
	 */
	public function get_balance($flag=false)
	{
		$username = $this->sxuser[ 'merge_username' ];
		$username || exit( '{"code":"4001","msg":"Invalid Paramter"}' );
        $userinfo = $this->user_api->check_user_exists( $username );

        if(isset($userinfo['Code']) && $userinfo['Code'] == '53') {
            $this->signup();
        }

        if(isset($userinfo['Code']) && $userinfo['Code'] != '0') {
            $this->return_json(E_UNKNOW, '未知错误' );
        }
		$data = $this->user_api->get_balance( $username );

		if( $data[ 'Code' ] == 0 )
		{
			$this->load->model( 'sx/dg/user_model' );
			$this->user_model->update_balance( $username, $data[ 'Balance' ], $this->platform_name );
		}

        if ($flag) {
            return ['balance' => $data['Balance']];
        } else {
            $this->return_json(OK, array('balance' => (float)$data['Balance']));
        };
	}

	/**
	 * 设置密码
	 */
	public function reset_password()
	{
		$username = $this->sxuser[ 'merge_username' ];
		$password = $this->sxuser[ 'password' ];
		$username && $password || exit( '{"code":"4001","msg":"Invalid Paramter"}' );

		$data = $this->user_api->reset_password( $username, $password );
		if( $data[ 'Code' ] == 0 )
		{
			$this->load->model( 'sx/dg/user_model' );
			$this->user_model->reset_password( $username, $password, $this->platform_name );
		}
		$this->ajax_return( $data );
	}

	/**
	 * 冻结玩家
	 */
	public function freeze_player()
	{
		$username = $this->sxuser[ 'merge_username' ];
		$frozenstatus = $this->sxuser[ 'frozenstatus' ]; //1.冻结 0.不冻结
		$username && $frozenstatus OR exit( '{"code":"4001","msg":"Invalid Paramter"}' );
		$data = $this->user_api->freeze_player( $username, $frozenstatus );
		if( $data[ 'Code' ] == 0 )
		{
			$this->load->model( 'sx/dg/user_model' );
			$this->user_model->freeze_player( $username, $frozenstatus, $this->platform_name );
		}
		$this->ajax_return( $data );
	}

	/**
	 * 存取筹码
	 */
	public function create_transaction($amount = 0)
	{
        $sx_total_limit=trim($this->M->get_sx_set('credit'),'"');
		$username = isset($this->sxuser['merge_username']) ? $this->sxuser['merge_username'] : '';
		$amount = isset($this->sxuser['amount']) ? $this->sxuser['amount'] : 0;
        $snUid = isset($this->sxuser['id']) ? $this->sxuser['id'] : '';
		$transfer_id = uniqid();
		$sn = isset($this->sxuser[ 'sn' ]) ? $this->sxuser[ 'sn' ] : '';
		$sn && $username && $amount && $snUid && $transfer_id || exit( '参数错误' );
        if ($amount > 0 &&  $sx_total_limit < $amount) {
            $this->return_json(E_OP_FAIL, '平台可使用额度不足!');
        }

		$transfer_id = $sn . $transfer_id;
        $amount = sprintf("%.2f", $amount);

        $this->M->select_db('private');
        $userMoney = $this->M->get_one('balance', 'user', array('username' => substr($username, 3)));
        if ($amount > 0 && $userMoney['balance'] < $amount) {
            $this->return_json(E_ARGS, '系统余额不足');
        }

		$this->load->model( 'sx/Cash_record_model', 'cash_record' );
		$uuid = md5( $username . $transfer_id );
		if( $this->cash_record->record_exists( $username, $transfer_id ) )
		{
			exit( '重复的订单！' );
		}

		$cash_type = $amount > 0 ? 1 : 2;
        $this->cash_record->add_record( $transfer_id, $sn, $username, $this->platform_name, $cash_type, $amount, $uuid );
		$data = $this->user_api->create_transaction( $username, $amount, $transfer_id );
        if( isset($data[ 'Code' ]) && isset($data[ 'Message' ]) ) {
            if ($data['Code'] == 0) {
                $this->transfer_success($amount, $snUid, $transfer_id, $sx_total_limit, $username);
                $this->cash_record->update_record( $username, $transfer_id, $data[ 'TransactionId' ], $data[ 'Message' ] );
                $this->return_json(OK, '操作成功');
            } else {
                $this->cash_record->update_info($username, $transfer_id, $data['Message']);
                $this->return_json(error, '操作失败');
            }
        } else {
            $this->return_json(E_OP_FAIL, '请检测网络');
        }


	}

    /**
     * 上下分成功对数据库处理
     */
    public function transfer_success($price,$snUid,$billNo,$sx_total_limit,$username)
    {
        $user_balance=$this->get_balance(1);
        $flag = $this->M->update_banlace($snUid, 0-$price, $billNo, 21, 'pt额度转换');
        if (!$flag) {
            $flag = $this->M->update_banlace($snUid, 0-$price, $billNo, 21, 'pt额度转换');
        }

        if (!$flag) {
            wlog(APPPATH . 'logs/pt/' . date('Y_m_d') . '.log', $billNo . ' 更新会员user和cash_list失败!');
        } else {
            //更新成功并且已经进行了相关操作后对总额度（redis）进行处理
            $result=$this->M->update_sx_set('credit',$sx_total_limit-$price,0);
            if (!$result) {
                wlog(APPPATH . 'logs/pt/' . date('Y_m_d') . '.log', $billNo . '更新总额度（redis）失败');
            }
        }
        $balance = $this->dg_user->get_balance($username, $this->platform_name)['balance'];

        $this->load->model('sx/dg/fund_model');
        //写入对应视讯平台现金记录
        $fundstatus = $this->fund_model->fund_write($username, $price > 0 ? 1 : 2, $price, $balance, $this->platform_name, $this->sxuser['sn'], $billNo);
        if (!$fundstatus) {
            wlog(APPPATH . 'logs/pt/' . date('Y_m_d') . '.log', $billNo . 'pt_fund插入记录失败');
        }
    }



	/**
	 * 检查存取状态
	 */
	public function check_transaction()
	{
		$username = $this->sxuser['merge_username'];
		$transfer_id = $this->sxuser['transfer_id'];
		$username && $transfer_id || exit( '参数错误' );

		$transfer_id = $this->sxuser[ 'sn' ] . $transfer_id;
		$data = $this->user_api->check_transaction( $username, $transfer_id );
		$this->ajax_return( $data );
	}




	/**
	 * 验证玩家身份（密码）
	 */
	public function authenticate_player()
	{
	    $username = $this->sxuser['merge_username'];
		$password = $this->sxuser[ 'password' ];
		$username || $password || exit( '参数错误' );

        $data = $this->user_api->authenticate_player( $username, $password );

		$this->ajax_return( $data );
	}



}
