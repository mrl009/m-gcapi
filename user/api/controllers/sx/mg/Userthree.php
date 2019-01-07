<?php

defined('BASEPATH') OR exit('No direct script access allowed');

include_once FCPATH.'api/core/SX_Controller.php';

class Userthree extends SX_Controller
{
	protected $user_api;
	protected $platform_name = 'mg';

	public function __construct()
	{
		parent::__construct();
		$this->load->library('BaseApi');
		$this->user_api = BaseApi::getinstance( $this->platform_name, 'user', $this->sxuser['sn'] );
	}
	//getcurrencies
	public function getcurrencies()
	{
		$data = $this->user_api->GetCurrenciesForAddAccount( NULL, $type = 3 );
		$this->ajax_return( $data );
	}
	//GetCurrenciesForAddAccount
	public function GetBettingProfileList()
	{
		$data = $this->user_api->GetBettingProfileList( NULL, 3 );
		$this->ajax_return( $data );
	}
	/***************************用户账户管理start********************************/
	/*
	* @sn require
	* @snuid require
	* @firstName require
	* @lastName require
	* @password require
	* @BettingProfileId require
	* @currency require
	* @isProgressive 是否允许账号进入游戏 默认false; 
	*
	*/
	public function AddAccount()
	{
		$sn = $this->sxuser[ 'sn' ];
		$snuid=$this->user['id'];
		$username = $this->sxuser[ 'merge_username' ];
		$check = $this->user_api->IsAccountAvailable( [ 'accountNumber'=>$username ], 3 );
		if( !isset($check['IsAccountAvailableResult']) ) {
			exit( '{"Code":4002,"msg":"system API IsAccountAvailable Error"}' );
		}elseif (isset($check['IsAccountAvailableResult']) && !$check['IsAccountAvailableResult']['IsAccountAvailable']) {
			exit( '{"Code":4002,"msg":"Account is unavailable"}' );
		}
		$data['accountNumber'] = $username;
		$data['firstName'] = $this->get_sn();
		$data['lastName'] = $username;
        $data['password']=123456;
		$data['currency'] = 100;
		$data['BettingProfileId'] = 839; //839 OR 859
		$sn && $username && $data['firstName'] && $data['lastName'] && $data['password'] && $data['currency'] && $data['BettingProfileId'] OR exit('{"Code":"4001","msg":"params invalid"}');
		if( isset($data['isSendGame']) ) {
			$data['email'] = $this->sxuser[ 'email' ];
			$data['mobileNumber'] = $this->sxuser[ 'mobileNumber' ];
			empty($data['email']) && empty($data['mobileNumber']) OR exit('{"Code":"4001","msg":"params invalid"}');
		}
		$ret = $this->user_api->AddAccount( $data, 3 );
		$this->load->model( 'sx/mg/user_model' );
		if( $ret[ 'AddAccountResult' ] && $ret['AddAccountResult']['IsSucceed'] && !$this->user_model->user_exists( $this->platform_name, $username ) )
		{
			$insert_data[ 'sn' ] = $sn;
			$insert_data[ 'snuid' ] = $snuid;
			$insert_data[ 'g_username' ] = $username;
			$insert_data[ 'g_password' ] = $data['password'];
			$insert_data[ 'firstName' ] = $data['firstName'];
			$insert_data[ 'lastName' ] = $data['lastName'];
			$insert_data[ 'isSendGame' ] = isset($data['isSendGame'])?$data['isSendGame']:0;
			$insert_data[ 'currency' ] = $data['currency'];
			$insert_data[ 'createtime' ] = date( 'Y-m-d H:i:s' );
			$insert_data['bettingProfileId'] = $data['BettingProfileId'];
			$insert_data[ 'customerId' ] = $ret['AddAccountResult']['CustomerId'];
			$insert_data[ 'casinoId' ] = $ret['AddAccountResult']['CasinoId'];
			$res=$this->user_model->add_user( 'mg', $insert_data );
            $this->return_json(OK);
		}
	}
    #EditAccount begin
    public function EditAccount()
    {
        $sn = $this->sxuser[ 'sn' ];
        $snuid = $this->sxuser[ 'snuid' ];
        $data['accountNumber'] = $this->sxuser[ 'merge_username' ];
        if( isset($this->sxuser[ 'firstName' ]) ) $data['firstName'] = $update_data['firstName'] = $this->sxuser[ 'firstName' ];
        if( isset($this->sxuser[ 'lastName' ]) ) $data['lastName'] = $update_data['lastName'] = $this->sxuser[ 'lastName' ];
        if( isset($this->sxuser[ 'password' ]) ) $data['password'] = $update_data['g_password'] = $this->sxuser[ 'password' ];
        if( isset($this->sxuser[ 'mobileNumber' ]) ) $data['mobileNumber'] = $update_data['mobileNumber'] = $this->sxuser[ 'mobileNumber' ];
        if( isset($this->sxuser[ 'eMail' ]) ) $data['eMail'] = $update_data['email'] = $this->sxuser[ 'eMail' ];
        if( isset($this->sxuser[ 'bettingProfileId' ]) ) $data['bettingProfileId'] = $update_data['bettingProfileId'] = $this->sxuser[ 'bettingProfileId' ];
        else $data[ 'bettingProfileId' ] = '';
        $sn && $snuid && $data['accountNumber'] OR exit('{"Code":"4001","msg":"params invalid"}');
        $ret = $this->user_api->EditAccount($data, 3);
        $this->load->model( 'sx/mg/user_model' );
        if( $ret[ 'EditAccountResult' ] && $ret['EditAccountResult']['IsSucceed'] == true )
        {
            $update_data[ 'sn' ] = $sn;
            $update_data[ 'snuid' ] = $snuid;
            $update_data[ 'g_username' ] = $data['accountNumber'];
            $this->user_model->update_userinfo( $this->platform_name, $update_data );
        }

        $this->ajax_return( $ret );
    }
    /***************************用户账户管理end********************************/
	public function _AddStationAccount()
	{
		$data['isGeneratePassword'] = false; // $this->sxuser[ 'isGeneratePassword' ]; //是否自动产生密码
		$data['password'] = $this->sxuser[ 'password' ] ?: '123456';
		$data['nickName'] = $this->sxuser[ 'nickName' ] ?: 'wxd314';
		$data['currency'] = $this->sxuser[ 'currency' ] ?: 8;		
		$data['BettingProfileId'] = $this->sxuser[ 'BettingProfileId' ] ?: 839; //839
		$data['isProgressive'] = $this->sxuser[ 'isProgressive' ] ?: true; //是否允许账号进入游戏 默认false
		$data['password'] && $data['nickName'] && $data['currency'] && $data['BettingProfileId'] OR exit('{"Code":"4001","msg":"params invalid"}'); 	
		$data = $this->user_api->AddStationAccount( $data, 3 );
		$this->ajax_return( $data );
	}
	//GetCurrenciesForAddAccount for 日本ncc docomo的電信商 暂时不用
	public function _AddAccountEx()
	{
		$data = [];		
		$data['firstName'] = $this->sxuser[ 'firstName' ] ?: 'Kingg';
		$data['lastName'] = $this->sxuser[ 'lastName' ] ?: 'Wang';	
		$data['password'] = $this->sxuser[ 'password' ] ?: '123456';
		$data['currency'] = $this->sxuser[ 'currency' ] ?: 8;		
		$data['BettingProfileId'] = $this->sxuser[ 'BettingProfileId' ] ?: 839; //839
		$data['email'] = $this->sxuser[ 'email' ] ?: 'wxd314@qq.com';
		$data['mobileNumber'] = $this->sxuser[ 'mobileNumber' ] ?: '';
		$data['isSendGame'] = $this->sxuser[ 'isSendGame' ] ?: false;
		$data['isProgressive'] = $this->sxuser[ 'isProgressive' ] ?: true; //是否允许账号进入游戏 默认false
		$data['password'] && $data['firstName'] && $data['lastName'] && $data['BettingProfileId'] && $data['currency'] OR exit('{"Code":"4001","msg":"params invalid"}');
		if( $data['isSendGame'] ) {
			empty($data['email']) && empty($data['mobileNumber']) OR exit('{"Code":"4001","msg":"params invalid"}');
		}
		$data = $this->user_api->AddAccountEx( $data, 3 );
		$this->ajax_return( $data );
	}
	//GetAccountDetails
	public function GetAccountDetails($type = 0)
	{
		$sn = $this->sxuser[ 'sn' ];
		$data['accountNumber'] = $this->sxuser[ 'merge_username' ];
		$sn &&  $data['accountNumber'] OR exit('{"Code":"4001","msg":"params invalid"}');
		$data = $this->user_api->GetAccountDetails( $data, 3 );
        if(isset($data['GetAccountDetailsResult'])&&$data['GetAccountDetailsResult']['ErrorCode']==0){
            $update_data['balance']=$data['GetAccountDetailsResult']['Balance'];
            $update_data['g_username']=$this->sxuser[ 'merge_username' ];
            $this->load->model( 'sx/mg/user_model' );
            $this->user_model->update_userinfo( $this->platform_name, $update_data );
            if($type==1){
                return $data;
            }
            $this->return_json(OK,$data);
        }
	}
	//GetCurrenciesForAddAccount
	public function GetCurrenciesForDeposit()
	{
		$data = $this->user_api->GetCurrenciesForDeposit( NULL, 3 );
	}
	//GetMyBalance
	public function GetMyBalance()
	{
		$data = $this->user_api->GetMyBalance( NULL, 3 );
	}
	/**************************转账控制start********************************/
	public function transfer($credit = 0)
    {
        $sx_total_limit=trim($this->M->get_sx_set('credit'),'"');
        $snUid = isset($this->sxuser['id']) ? $this->sxuser['id'] : '';
        $username = isset($this->sxuser['merge_username']) ? $this->sxuser['merge_username'] : '';
        $type = isset($this->sxuser['type']) ? $this->sxuser['type'] : 'IN';
        $type = strtoupper($type);
        $credit = $credit ? $credit : $this->sxuser['credit'];
        if (empty($username) || empty($type)) {
            $this->return_json(E_ARGS, '参数错误');
        }elseif ($credit == 0){
            $this->return_json(E_ARGS, '游戏余额不足,请充值');
        }
        if($type == 'IN'){
            $this->Deposit($credit);
        }else{
            $this->Withdrawal($credit);
        }
        //var_dump(123456);exit();
    }
    public function all_transfer()
    {
        $type = isset($this->sxuser['type']) ? $this->sxuser['type'] : '';
        $username = isset($this->sxuser['merge_username']) ? $this->sxuser['merge_username'] : '';
        if ($type == 'IN') {
            $credit = $this->M->get_one('balance', 'user', array('username' => substr($username, 3)));
        } elseif ($type == 'OUT') {
            //$credit = $this-> search_point(2);
            $rs=$this->GetAccountDetails(1);
            $credit=$rs['GetAccountDetailsResult']['CreditBalance'];
        } else {
            $this->return_json(E_ARGS, '参数错误');
        }
        $credit = isset($credit['balance']) ? $credit['balance'] : $credit['d']['money'];
        if(sprintf("%.2f", $credit)>$credit){
            $credit-=0.01;
        }
        $this->transfer($credit);
    }
    /**
     * 上下分成功对数据库处理
     */
    public function transfer_success($credit,$type,$snUid,$billNo,$sx_total_limit,$username)
    {
        //更新余额度
        $price = $type == 'IN' ? 0 - $credit : $credit;

        $flag = $this->M->update_banlace($snUid, $price, $billNo, 21, 'mg额度转换');
        if (!$flag) {
            $flag = $this->M->update_banlace($snUid, $price, $billNo, 21, 'mg额度转换');
        }
        if (!$flag) {
            wlog(APPPATH . 'logs/mg/' . date('Y_m_d') . '.log', $billNo . ' 更新会员user和cash_list失败!');
        } else {
            //更新成功并且已经进行了相关操作后对总额度（redis）进行处理
            //var_dump($sx_total_limit,$price);exit();
            $result=$this->M->update_sx_set('credit',$sx_total_limit+$price,0);
            if (!$result) {
                wlog(APPPATH . 'logs/mg/' . date('Y_m_d') . '.log', $billNo . '更新总额度（redis）失败');
            }else{
                $this->load->model('sx/credit_model','credit');
                $res=$this->credit->update_credit($sx_total_limit,$price,'mg',$this->get_sn());
            }
        }
    }
	//Deposit
	public function Deposit($amount = 0)
	{
        //获取公库的配置,拿到剩余额度限额
        $sx_total_limit=trim($this->M->get_sx_set('credit'),'"');
		$sn = $this->sxuser[ 'sn' ];
		$data['accountNumber'] = $this->sxuser[ 'merge_username' ];
		$data['amount'] = $amount?$amount:$this->sxuser[ 'amount' ];
		$data['currency'] = '100'; //一定要传此值，不然php soapClient会报错：SOAP-ERROR: Encoding: object has no 'currency' property
		$data['transactionReferenceNumber'] = $data['accountNumber'].'_'.date('YmdHis').mt_rand(10,99);
		$sn && $data['accountNumber'] && is_numeric($data['amount']) OR exit('{"Code":"4001","msg":"params invalid"}');
        $credit = $this->M->get_one('balance', 'user', array('username' => substr($data['accountNumber'], 3)));
        /*mg转账必须引入合理的账户余额控制*/
        if($credit['balance']<$data['amount']){
            $this->return_json(E_OP_FAIL,'账户余额不足请充值');
        }
        if ( $sx_total_limit < $data['amount']) {
            $this->return_json(E_OP_FAIL, '平台可使用额度不足!');
        }
		$ret = $this->user_api->Deposit( $data, 3 );
		if($ret['DepositResult'] && $ret['DepositResult']['IsSucceed'] == true) {
		    $this->transfer_success($data['amount'],'IN',$this->sxuser['id'],$ret['DepositResult']['TransactionId'],$sx_total_limit,$data['accountNumber']);
			$this->load->model('sx/mg/user_model');
			//写入资金流水表
			$this->user_model->insert_transfer( $this->platform_name, [
				'gc_username'  =>  $data['accountNumber'],
				'sn'           =>  $sn,
				'type'         =>  1,
				'add_time'     =>  date('Y-m-d H:i:s'),
				'transfer_id'  =>  $data['transactionReferenceNumber'],
				'amount'       =>  $data['amount'],
				'free_balance' =>  $ret['DepositResult']['Balance'],
				'transactionId'=>  $ret['DepositResult']['TransactionId']
				] );
			//更新用户信息表
			$this->user_model->update_userinfo( $this->platform_name, [
				'g_username'  =>  $data['accountNumber'],
				'balance'     =>  $ret['DepositResult']['Balance']
				] );
			$this->return_json(OK);
		}
	}
    /*
    * @sn require
    * @snuid require
    * @username require
    * @amount -1表示取完所有余额
    *
    */
    public function Withdrawal($amount = 0)
    {
        //获取公库的配置,拿到剩余额度限额
        $sx_total_limit=trim($this->M->get_sx_set('credit'),'"');
        $sn = $this->sxuser[ 'sn' ];
        $data['accountNumber'] = $this->sxuser[ 'merge_username' ];
        $data['amount'] = $amount?$amount:$this->sxuser[ 'amount' ];
        $data['transactionReferenceNumber'] = $data['accountNumber'].date('YmdHis').mt_rand(10,99);
        $sn && $data['accountNumber'] && is_numeric($data['amount']) OR exit('{"Code":"4001","msg":"params invalid"}');
        //$data['amount'] = substr( number_format(abs($data['amount']), 3), 0, -1 ); //保留两位小数 非四舍五入
        $data['amount'] =sprintf("%.2f", $data['amount']);
        $ret = $this->user_api->Withdrawal( $data, 3 );
        if($ret['WithdrawalResult'] && $ret['WithdrawalResult']['IsSucceed'] == true) {
            $this->transfer_success($data['amount'],'OUT',$this->sxuser['id'],$ret['WithdrawalResult']['TransactionId'],$sx_total_limit,$data['accountNumber']);
            $this->load->model('sx/mg/user_model');
            $rs=$this->user_model->insert_transfer( $this->platform_name, [
                'gc_username'  =>  $data['accountNumber'],
                'sn'           =>  $sn,
                'type'         =>  2,
                'add_time'     =>  date('Y-m-d H:i:s'),
                'transfer_id'  =>  $data['transactionReferenceNumber'],
                'amount'       =>  -$data['amount'],
                'free_balance' =>  $ret['WithdrawalResult']['Balance'],
                'transactionId'=>  $ret['WithdrawalResult']['TransactionId']
            ] );
            $rs=$this->user_model->update_userinfo( $this->platform_name, [
                'g_username'  =>  $data['accountNumber'],
                'balance'     =>  $ret['WithdrawalResult']['Balance']
            ] );
        }
        $this->return_json(OK);
    }
	//GetCurrenciesForAddAccount
	public function GetAccountBalance()
	{
		$sn = $this->sxuser[ 'sn' ];
		$snuid = $this->sxuser[ 'snuid' ];
		$data['delimitedAccountNumbers'] = $this->sxuser[ 'merge_username' ];
		$sn && $snuid && $data['delimitedAccountNumbers'] OR exit('{"Code":"4001","msg":"params invalid"}');
		$data = $this->user_api->GetAccountBalance( $data, 3 );
		if($data['GetAccountBalanceResult'] && $data['GetAccountBalanceResult']['BalanceResult']['IsSucceed'] == true) {
			$this->load->model('sx/mg/user_model');
			$this->user_model->update_userinfo( $this->platform_name, [
				'g_username'  =>  $this->sxuser[ 'merge_username' ],
				'balance'     =>  $data['GetAccountBalanceResult']['BalanceResult']['Balance']
				] );
		}
		$this->return_json(OK,$data);
	}
	//WithdrawalAll
	public function WithdrawalAll()
	{
		$sn = $this->sxuser[ 'sn' ];
		$data['accountNumber'] = $this->sxuser[ 'username' ];
		$data['transactionReferenceNumber'] = $data['accountNumber'].date('YmdHis').mt_rand(10,99);
		$sn && $data['accountNumber'] OR exit('{"Code":"4001","msg":"params invalid"}');
		$ret = $this->user_api->WithdrawalAll( $data, 3 );
		if( $ret['WithdrawalAllResult'] && $ret['WithdrawalAllResult']['IsSucceed'] == true ) {
			$this->load->model('sx/mg/user_model');
			$this->user_model->insert_transfer( $this->platform_name, [
				'gc_username'  =>  $data['accountNumber'],
				'sn'           =>  $sn,
				'type'         =>  2,
				'add_time'     =>  date('Y-m-d H:i:s'),
				'transfer_id'  =>  $data['transactionReferenceNumber'],
				'amount'       =>  $ret['WithdrawalAllResult']['TransactionAmount'],
				'free_balance' =>  $ret['WithdrawalAllResult']['Balance']
				] );
			$this->user_model->update_userinfo( $this->platform_name, [
				'g_username'  =>  $data['accountNumber'],
				'balance'     =>  $ret['WithdrawalAllResult']['Balance']
				] );
		}
		$this->return_json(OK);
	}
    /**************************转账控制end********************************/
	//SendMobileGame
	//关于SendMobileGame 这支API 技术回应 这支API是针对使用 AddAccountEx的用户所使用的 
	//意思就是为日本特定电信业者才能使用的API 因此呼叫会失败 暂不可用
	public function _SendMobileGame()
	{
	}
	//ChangeSuspendAccountStatus(暂停账户之后,仍然可以存取款)
	public function ChangeSuspendAccountStatus()
	{
		$sn = $this->sxuser[ 'sn' ];
		$data['accountNumber'] = $this->sxuser[ 'merge_username' ];
		$data['suspendAccountStatus'] = $this->sxuser[ 'suspendAccountStatus' ]; //Open,Suspended
		$sn && $data['accountNumber'] && in_array($data['suspendAccountStatus'], ['Open','Suspended']) OR exit('{"Code":"4001","msg":"params invalid"}');
		$data = $this->user_api->ChangeSuspendAccountStatus( $data, 3 );
		if($data['ChangeSuspendAccountStatusResult'] && $data['ChangeSuspendAccountStatusResult']['IsSucceed']==true) {
			$this->load->model('sx/mg/user_model');
			$this->user_model->update_userinfo( $this->platform_name, [
				'g_username'  =>  $this->sxuser[ 'merge_username' ],
				'status'     =>  $data['ChangeSuspendAccountStatusResult']['SuspendAccountStatus'] == 'Open' ? 1 : 0
				] );
		}
		$this->ajax_return( $data );
	}
	//ResetLoginAttempts
	public function ResetLoginAttempts()
	{
		$sn = $this->sxuser['sn'];
		$data['accountNumbers'] = $this->sxuser[ 'merge_username' ];
		$sn && !is_array($data['accountNumbers']) OR exit('{"Code":"4001","msg":"params invalid"}');
		$data = $this->user_api->ResetLoginAttempts( $data, 3 );
		$this->ajax_return( $data );
	}
	//GetCurrenciesForAddAccount
	public function LockAccount()
	{
		$sn = $this->sxuser['sn'];
		$data['strAccounts'] = [$this->sxuser[ 'merge_username' ]];
		$sn && $data['strAccounts'] OR exit('{"Code":"4001","msg":"params invalid"}');
		$data = $this->user_api->LockAccount( $data, 3 );
		$this->ajax_return( $data );
	}
	//GetBetInfoDetails
	public function GetBetInfoDetails()
	{
	}
	//GameplayDetailedReport
	public function GameplayDetailedReport()
	{
		$data['dateFrom'] = $this->sxuser[ 'dateFrom' ] ? '2017-03-21T14:05:57+08:00':'';
		$data = $this->user_api->GameplayDetailedReport( $data, 3 );
		if( $data['GameplayDetailedReportResult'] ) {
			$data = $this->GetReportResult( $data['GameplayDetailedReportResult'] );
		}
		$this->ajax_return( $data );
	}
	//GetReportByName _暂未测试通过
	public function GetReportByName()
	{
		$item = [
			'parameters' => [
				['ParameterName'=> 'FromDate','ParameterValue'=>'2017-05-01T00:00:00'],
				['ParameterName'=> 'ToDate','ParameterValue'=>'2017-05-01T23:59:59.997'],
				['ParameterName'=> 'AccountNumber','ParameterValue'=>'OC0025588498']
			]
		];
	}
	//GetCurrenciesForAddAccount
	public function GetReportResult( $no = '' )
	{
		$data['id'] = $this->sxuser[ 'id' ] ;
		if( $no !== '' ) $data['id'] = $no;
		//$data['nPage'] = $this->sxuser[ 'parameters' ] ?? 1; // 第几页
		$data['id'] && $data['nPage'] OR exit('{"Code":"4001","msg":"params invalid"}');
		$data = $this->user_api->GetReportResult( $data, 3 );
		return $data;
	}
	//GetTransactionDetail
	public function GetTransactionDetail()
	{
		$data['transactionId'] = $this->sxuser[ 'transactionId' ];
		$data['transactionId'] OR exit('{"Code":"4001","msg":"params invalid"}');
		$data = $this->user_api->GetTransactionDetail( $data, 3 );
		$this->ajax_return( $data );
	}
	//GetLanguageList
	public function GetLanguageList()
	{
		$sn = $this->sxuser[ 'sn' ];
		$snuid = $this->sxuser[ 'snuid' ];
		$sn && $snuid OR exit('{"Code":"4001","msg":"params invalid"}');
		$data = $this->user_api->GetLanguageList( NULL, 3 );
		$this->ajax_return( $data );
	}
	//GetCurrenciesForAddAccount
	public function GetPlaycheckUrl()
	{
		$data['accountNumber'] = $this->sxuser[ 'accountNumber' ];
		$data['password'] = $this->sxuser[ 'password' ];
		$data['playCheckType'] = $this->sxuser[ 'playCheckType' ];
		//$data['language'] = $this->sxuser[ 'language' ] ?? 3;
		//$data['transactionId'] = $this->sxuser[ 'transactionId' ] ?? '';
		$data['accountNumber'] && $data['password'] && $data['playCheckType'] && $data['language'] OR exit('{"Code":"4001","msg":"params invalid"}');
		$data = $this->user_api->GetPlaycheckUrl( $data, 3 );
		$this->ajax_return( $data );
	}
	//SendMobileLink
	public function SendMobileLink()
	{
		$data['accountNo'] = $this->sxuser[ 'accountNo' ];
		//$data['languageId'] = $this->sxuser[ 'languageId' ] ?? 3; // default Chinese
		$data['email'] = $this->sxuser[ 'email' ];
		$data['mobileNumber'] = $this->sxuser[ 'mobileNumber' ];
		$data['accountNo'] && $data['languageId'] OR exit('{"Code":"4001","msg":"params invalid"}');
		if( empty($data['email']) && empty($data['mobileNumber']) ) exit('{"Code":"4001","msg":"params invalid"}');
		$data = $this->user_api->SendMobileLink( $data, 3 );
		$this->ajax_return( $data );
	}
	//检测用户名是否可用
	public function IsAccountAvailable()
	{
		$sn = $this->sxuser['sn'];
		$snuid = $this->sxuser['snuid'];
		$data['accountNumber'] = $this->sxuser[ 'merge_username' ];
		$sn && $snuid && $data['accountNumber'] OR exit('{"Code":"4001","msg":"params invalid"}');
		$data = $this->user_api->IsAccountAvailable( $data, 3 );
		$this->ajax_return( $data );
	}
	//GetCurrenciesForAddAccount
	public function GetPlayerFundsInPlayDetails()
	{
		$sn = $this->sxuser['sn'];
		$snuid = $this->sxuser['snuid'];
		$data['accountNumber'] = $this->sxuser[ 'merge_username' ];
		$sn && $snuid && $data['accountNumber'] OR exit('{"Code":"4001","msg":"params invalid"}');
		$data = $this->user_api->GetPlayerFundsInPlayDetails( $data, 3 );
		$this->ajax_return( $data );
	}

}
