<?php

if ( !defined( 'BASEPATH' ) )
{
	exit('No direct script access allowed');
}

include_once FCPATH.'api/core/SX_Controller.php';

class User extends SX_Controller
{
	protected $user_api;
	protected $platform_name = 'lebo';

	public function __construct()
	{
		parent::__construct();
		$this->load->library('BaseApi');
		$this->user_api = BaseApi::getinstance( $this->platform_name, 'user', $this->sxuser[ 'sn' ] );
	}

	/**
	 * 会员登录账号不存在则创建之
	 */
	public function login()
	{
		$username = $this->sxuser[ 'merge_username' ];
		$sn = $this->sxuser[ 'sn' ];
		$snuid = $this->sxuser[ 'id' ];
		$game_type = isset($this->sxuser[ 'game_type' ]) ? $this->sxuser[ 'game_type' ]: 'pc';
        $act_type=isset($this->sxuser[ 'game_type' ]) ? $this->sxuser[ 'game_type' ]: '';
		$username && $sn && $snuid && $game_type || exit( '参数错误' );
		$data = $this->user_api->login( $username, $game_type,$act_type);
		$this->load->model( 'sx/ag/user_model','sx_user' );
		if( $data[ 'code' ] == 0 && !$this->sx_user->user_exists( $this->platform_name, $username ) )
		{
			$insert_data[ 'sn' ] = $sn;
			$insert_data[ 'snuid' ] = $snuid;
			$insert_data[ 'g_username' ] = $username;
			$insert_data[ 'status' ] = 1;
			$insert_data[ 'currency' ] = 'RMB';
			$insert_data[ 'createtime' ] = date( 'Y-m-d H:i:s' );
			$rs=$this->sx_user->user_add( 'lebo', $insert_data );
		}
        $this->return_json(OK, $data);
}

	/**
	 * 获取会员信息
	 */
	public function user_detail()
	{
		$username = $this->sxuser[ 'merge_username' ];
        $this->load->model( 'sx/dg/user_model' );
        $user_info=$this->user_model->user_info($username,'*','lebo');
        if(empty($user_info)){//不存在的用户先注册
            $sn = $this->sxuser[ 'sn' ];
            $snuid = $this->sxuser[ 'id' ];
            $game_type = isset($this->sxuser[ 'game_type' ]) ? $this->sxuser[ 'game_type' ]: 'pc';
            $act_type=isset($this->sxuser[ 'game_type' ]) ? $this->sxuser[ 'game_type' ]: '';
            $username && $sn && $snuid && $game_type || exit( '参数错误' );
            $data = $this->user_api->login( $username, $game_type,$act_type);
            if( $data[ 'code' ] == 0)
            {
                $insert_data[ 'sn' ] = $sn;
                $insert_data[ 'snuid' ] = $snuid;
                $insert_data[ 'g_username' ] = $username;
                $insert_data[ 'status' ] = 1;
                $insert_data[ 'currency' ] = 'RMB';
                $insert_data[ 'createtime' ] = date( 'Y-m-d H:i:s' );
                $this->load->model( 'sx/dg/user_model' );
                $rs=$this->user_model->add_user( 'lebo', $insert_data );
            }
        }
        $user_info=$this->user_model->user_info($username,'*','lebo');
		$data = $this->user_api->user_detail( $username );
		if( $data[ 'code' ] ===0)
		{
			$this->user_model->update_balance( $username, $data[ 'result' ][ 'balance' ], $this->platform_name );
		}
		$this->return_json(OK, $data);
	}
	/*一键转账*/
	public function all_transfer()
    {
        $type = isset($this->sxuser['type']) ? $this->sxuser['type'] : '';
        $username = isset($this->sxuser['merge_username']) ? $this->sxuser['merge_username'] : '';
        //var_export($data['result']['balance']);exit();
        if ($type=='IN') {
            $credit = $this->M->get_one('balance', 'user', array('username' => substr($username, 3)));
            $this->deposit( $credit['balance']);
        } elseif ($type == 'OUT') {
            $data = $this->user_api->user_detail( $username );
            $credit = $data['result']['balance'];
            $this->with_drawal($credit);
        } else {
            $this->return_json(E_ARGS, '参数错误');
        }
    }
    /*
     * 转账接口*/
    public function transfer()
    {
        $credit=$this->sxuser['credit'];
        $type=$this->sxuser['type'];
        $snUid = isset($this->sxuser['id']) ? $this->sxuser['id'] : '';
        /*阻止重放攻击*/
        $lock=$this->M->get_sx_set('lock:'.$snUid);
        if($lock){
            $this->return_json(E_OP_FAIL,'您的操作过于频繁');
        }else{
            $rs=$this->M->update_sx_set('lock:'.$snUid,'lock',10);
        }
        if($credit<10){
            $this->return_json(E_ARGS, '转账额度最低10元');
        }
        if($type=='IN'){
            $this->deposit($credit);
        }else{
            $this->with_drawal($credit);
        }
    }
	/**
	 * 会员存款
	 */
	public function deposit($credit = 0)
	{
        //获取公库的配置,拿到剩余额度限额
        $sx_total_limit=trim($this->M->get_sx_set('credit'),'"');
		$username = $this->sxuser[ 'merge_username' ];
		$amount = $credit?$credit:$this->sxuser[ 'amount' ];
		$sn = $this->sxuser[ 'sn' ];
		$snuid = $this->sxuser['id'];
		$trans_id = $sn .uniqid();
		$trans_id && $username && $amount > 0 || exit( '参数错误' );
        if ($sx_total_limit < $amount) {
            $this->return_json(E_OP_FAIL, '平台可使用额度不足!');
        }
        $this->M->select_db('private');
        $userMoney = $this->M->get_one('balance', 'user', array('username' => substr($username, strlen($sn))));
        if ( $userMoney['balance'] < $amount) {
            $this->return_json(E_ARGS, '系统余额不足');
        }
        $billNo='lebo'.sn_to_num($this->sxuser['sn']) . sprintf("%08d", $snuid) . time();
		$this->load->model( 'sx/Cash_record_model', 'cash_record' );
        if( $this->cash_record->record_exists( $username, $trans_id ) )
        {
            exit( '{"code":"1001","msg":"重复的订单！"}' );
        }
        $uuid = md5( $username . $trans_id );
        //这里注意trans_id字段的长度为char(20),用户名暂时只能为6位
        $ret = $this->cash_record->add_record( $trans_id, $sn, $username, $this->platform_name, 1, $amount, $uuid );
        if( $ret == FALSE ) exit( '{"code":"1001","msg":"重复的订单！"}' );
		$data = $this->user_api->deposit( $username, $amount );
		if( $data[ 'code' ] == 0 && $data[ 'text' ] == 'Success' )
		{
			//更新余额并写入现金记录--GC_MODEL
            $flag = $this->M->update_banlace($snuid, -$amount, $trans_id, 21, 'lebo额度转换');
            if (!$flag) {
                $flag = $this->M->update_banlace($snuid, -$amount, $trans_id, 21, 'lebo额度转换');
            }else{
                $rs=$this->M->update_sx_set('credit',$sx_total_limit-$amount,0);
                $this->load->model('sx/credit_model','credit');
                $rs=$this->credit->update_credit($sx_total_limit,-$amount,'lebo',$this->get_sn());
            }
            $this->load->model('sx/dg/fund_model');
            //写入对应视讯平台现金记录
            $this->fund_model->fund_write($username, 1 , $amount, $userMoney['balance'], $this->platform_name, $this->sxuser['sn'], $billNo);
			$rs=$this->cash_record->update_record( $username, $trans_id, $data[ 'result' ][ 'order_sn' ], $data[ 'text' ] );
		}
		else
		{
			$this->cash_record->update_info( $username, $trans_id, $data[ 'text' ]['text'] );
		}
		$this->return_json(OK, $data);
	}

	/**
	 * 会员取款 (ps:注意接口取款Amount必须为Double小数点后两位且>0，but数据库字段credit为负)
	 */
	public function with_drawal($credit = 0)
	{
        //获取公库的配置,拿到剩余额度限额
        $sx_total_limit=trim($this->M->get_sx_set('credit'),'"');
		$username = $this->sxuser[ 'merge_username' ];
        $amount = $credit?$credit:$this->sxuser[ 'amount' ]; //正数打款，负数取款
		$sn = $this->sxuser[ 'sn' ];
        $snuid = $this->sxuser['id'];
        $trans_id = $sn .uniqid();
		$trans_id && $username && is_numeric($amount) || exit( '参数错误' );
        $billNo='lebo'.sn_to_num($this->sxuser['sn']) . sprintf("%08d", $snuid) . time();
		$this->load->model( 'sx/Cash_record_model', 'cash_record' );
		if( $this->cash_record->record_exists( $username, $trans_id ) )
		{
            exit( '{"code":"1001","msg":"重复的订单！"}' );
		}

		$uuid = md5( $username . $trans_id );
		try
		{
			$this->cash_record->add_record( $trans_id, $sn, $username, $this->platform_name, 2, $amount, $uuid );
		}
		catch ( Exception $e )
		{
            exit( '{"code":"1001","msg":"重复的订单！"}' );
		}

		$data = $this->user_api->with_drawal( $username, abs($amount) );
        $this->M->select_db('private');
        $userMoney = $this->M->get_one('balance', 'user', array('username' => substr($username, strlen($sn))));
		if( $data[ 'code' ] == 0 && $data[ 'text' ] == 'Success' )
		{
            $flag = $this->M->update_banlace($snuid, $amount, $trans_id, 21, 'lebo额度转换');
            if (!$flag) {
                $flag = $this->M->update_banlace($snuid, $amount, $trans_id, 21, 'lebo额度转换');
            }else{
                $this->cash_record->update_record( $username, $trans_id, $data[ 'result' ][ 'order_sn' ], $data[ 'text' ] );
                $this->load->model('sx/dg/fund_model');
                //写入对应视讯平台现金记录
                $this->fund_model->fund_write($username, 1 , $amount, $userMoney['balance'], $this->platform_name, $this->sxuser['sn'], $billNo);
                $rs=$this->M->update_sx_set('credit',$sx_total_limit+$amount,0);
                $this->load->model('sx/credit_model','credit');
                $rs=$this->credit->update_credit($sx_total_limit,$amount,'lebo',$this->get_sn());
            }
		}
		else
		{
			$this->cash_record->update_info( $username, $trans_id, $data[ 'text' ]['text']);
		}
        $this->return_json(OK, $data);
	}

	/**
	 * 查询存取款状态(ps:此处传入的流水单号是trans_id字段)
	 * @return bool
	 */
	public function transfer_status()
	{
		$username = $this->sxuser[ 'merge_username' ];
		$trans_id = $this->sxuser[ 'transfer_id' ];
		$trans_id && $username || exit( '参数错误' );
		$this->load->model( 'sx/Cash_record_model', 'cash_record' );
		$transaction_id = $this->cash_record->get_order_number( $username, $trans_id )[ 'transaction_id' ];
        //var_dump($transaction_id);exit();
		if( !$transaction_id )
		{
            $this->return_json( [ 'code' => '1002', 'msg' => 'No this trans_id in DB' ] );
			return false;
		}

		$data = $this->user_api->transfer_status( $username, $transaction_id );
        $this->return_json( OK,$data );
	}
    /**
     * 此功能用来获取结算和撤销注单，所有注单按时间排序，最大100笔。抓取注单间隔不能小于15秒，否则系统不予处理。
     * 同时，由于供应商存在改注单及撤销情况，接入商需要对注单ID进行比对，及时对已存在注单进行更新
     * @return bool
     */
    public function get_bet_data()
    {
        $sn = $this->sxuser[ 'sn' ];
        $sn OR exit( '{"code":"1003","msg":"Invalid Parameter！"}' );
        $data = $this->user_api->get_bet_data();
        $this->ajax_return( $data );
    }
    /**
     * 此功能用来获取结算和撤销注单，所有注单按时间排序，最大100笔。抓取注单间隔不能小于15秒，否则系统不予处理。
     * 同时，由于供应商存在改注单及撤销情况，接入商需要对注单ID进行比对，及时对已存在注单进行更新
     * @return bool
     */
    public function mark_bet_data()
    {
        $sn = $this->sxuser[ 'sn' ];
        $ticketNo = $this->sxuser['ticket_no'];
        $sn && $ticketNo OR exit( '{"code":"1003","msg":"Invalid Parameter！"}' );
        $data = $this->user_api->mark_bet_data( $ticketNo );
        $this->ajax_return( $data );
    }
    /*
     * 时间段获取注单
     * 此功能用来获取一段时间内的结算和撤销注单，所有注单按时间排序，最大500笔，
     * 时间差不能超过24小时且抓取注单间隔不能小于15秒，否则系统不予处理。（此功能很少用）
     * @return bool|mixed
     * */
    public function bet_record()
    {
        $sn = $this->sxuser['sn'];
        $username = $this->sxuser[ 'merge_username' ] ? : '';  //username为空，查询所有会员注单信息；不为空，查询单个会员信息
        $startTime = $this->sxuser['start_time'];
        $endTime = $this->sxuser['end_time'];
        $sn && $startTime && $endTime OR exit( '{"code":"1003","msg":"Invalid Parameter！"}' );


        $data = $this->user_api->bet_record( $startTime, $endTime, $username );
        $this->ajax_return( $data );
    }
}