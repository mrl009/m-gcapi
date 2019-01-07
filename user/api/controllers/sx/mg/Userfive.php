<?php

defined('BASEPATH') OR exit('No direct script access allowed');

include_once FCPATH.'api/core/SX_Controller.php';

class Userfive extends SX_Controller
{
	protected $user_api;
	protected $platform_name = 'mg';

	public function __construct()
	{
		parent::__construct();
		$this->load->library('BaseApi');
		$this->user_api = BaseApi::getinstance( $this->platform_name, 'user', $this->sxuser['sn'] );
	}
	/*
	* @LastRowId require
	* @CasinoId 对有多个游戏server的运营商必填
	* @return mixed
	* @返回的数据量很大,暂时不知道什么用
	*/
	public function GetSpinBySpinData()
	{
		//$data['LastRowId'] = isset($this->sxuser['LastRowId']) ? 27644523328:0;
        $data['LastRowId']=42474667479;
		//$data['CasinoId'] = $ths->sxuser['CasinoId'];
		$data = $this->user_api->GetSpinBySpinData( $data, 5 );
		var_dump($data);exit();
	}
	/*
	* *@https://redirect.CONTDELIVERY.COM/Casino/Default.aspx?applicationid=1023&usertype=0&csid=16113&serverid=16113*&theme=igamingA4&variant=instantplay&gameid=classic243Desktop&sEXT1=OC0025588498&sEXT2=123456&ul=en
	* @电子游戏
	* @return string 
	*/
	public function gameLogin()
	{
		$sn = $this->sxuser['sn'];
		$snuid = $this->sxuser['snuid'];
		$this->load->config('global');
		$mg_dz_url = $this->config->item('mg_dz_url'); //https://redirect.CONTDELIVERY.COM/Casino/Default.aspx
		$data['applicationid'] = 1023;
		$data['usertype'] = 0;
		$data['csid'] = 16113;
		$data['serverid'] = 16113;
		$data['theme'] = 'iGamingA4';
		$data['variant'] = 'instantplay';
		$data['gameid'] = $this->sxuser['gameid'];
		$data['sEXT1'] = $this->sxuser['merge_username'];
		$data['sEXT2'] = $this->sxuser['password'];
		$data['ul'] = $this->sxuser['ul'] ?? 'en';
		$sn && $snuid && $mg_dz_url && $data['gameid'] && $data['sEXT1'] && $data['sEXT2'] OR exit('{"Code":"4001","msg":"params invalid"}');
		$url = $mg_dz_url.'?'.http_build_query($data);
		$html = '<form style="display:none;" id="form1" name="form1" method="post" action=" ' . $url . ' "><script type="text/javascript">function load_submit(){document.form1.submit()}load_submit();</script>';
				
		$this->ajax_return( $html, 'text' );
	}
	/*
	* *@https://webservice.basestatic.net/ETILandingPage/?CasinoID=16113&LoginName=[LOGINNAME]&Password=[PASSWORD     ]&ClientID=4&UL=en&VideoQuality=AutoSD&BetProfileID=DesignStyleA&CustomLDParam=MultiTableMode^^1||LobbyMod     e^^C||CDNselection^^1&StartingTab=%20Baccarat&ClientType=1&ModuleID=70004&UserType=0&ProductID=2&Active     Currency=Credits&altProxy=TNG&GameTabCH=0
	* @真人视讯游戏
	* @return string 
	*/
	public function liveGameLogin()
	{
		$sn = $this->sxuser['sn'];
		$snuid = $this->sxuser['snuid'];
		$this->load->config('global');
		$mg_lg_url = $this->config->item('mg_lg_url'); //https://webservice.basestatic.net/ETILandingPage/

		$taobaoAPI = $this->config->item('ip_url'); //http://ip.taobao.com/service/getIpInfo.php
		$ip = $this->input->ip_address();
		$url = $taobaoAPI . '?ip=' . $ip;
		$ipinfo = file_get_contents($url);
		if( isset($ipinfo['code'], $ipinfo['data']) ){
			if($ipinfo['data']['country_id'] == 'CN'){ //china
				$gameURL = $mg_lg_url[0];
				$data['CustomLDParam'] = 'CDNselection^^1';
			}else{
				$gameURL = $mg_lg_url[1];
				$data['CustomLDParam'] = 'MultiTableMode^^1||LobbyMode^^C ||CDNselection^^1';
			}
		}else{ //未获取到客户端地区 视为国外地区
			$gameURL = $mg_lg_url[1];
			$data['CustomLDParam'] = 'MultiTableMode^^1||LobbyMode^^C ||CDNselection^^1';
		}

		$data['CasinoID'] = 16113;
		$data['LoginName'] = $this->sxuser['merge_username'];
		$data['Password'] = $this->sxuser['password'];
		$data['ClientID'] = 4;
		$data['UL'] = $this->sxuser['ul'] ?? 'zh-cn'; // language list
		$data['VideoQuality'] = 'AutoSD';
		$data['BetProfileID'] = 'DesignStyleA';
		$data['StartingTab'] = 'MPBaccarat'; //default;
		$data['ClientType'] = 1;
		$data['ModuleID'] = 70004;
		$data['UserType'] = 0; //real player
		$data['ProductID'] = 2;
		$data['ActiveCurrency'] = 'Credits';
		$data['altProxy'] = 'TNG';
		$data['GameTabCH'] = 0;		
		
		$sn && $snuid && $data['LoginName'] && $data['Password'] OR exit('{"Code":"4001","msg":"params invalid"}');
		$url = $gameURL.'?'.http_build_query($data);
		//header("Location: $url");
				
		$html = '<form style="display:none;" id="form1" name="form1" method="post" action=" ' . $url . ' "><script type="text/javascript">function load_submit(){document.form1.submit()}load_submit();</script>';
				
		$this->ajax_return( $html, 'text' );
	}
	/*
	* @ sn require
	* @ snuid required
	* @ username 用户名 required
	* @ 是否允许账号进入游戏
	*/
	public function UpdateProgressiveStatus()
	{
		$sn = $this->sxuser['sn'];
		$snuid = $this->sxuser['snuid'];
		$data['AccountNumber'] = $ths->sxuser['merge_username'];
		$data['IsProgressive'] = $ths->sxuser['IsProgressive'] ?? true;
		$sn && $snuid && $data['AccountNumber'] OR exit('{"Code":"4001","msg":"params invalid"}');
		$data = $this->user_api->UpdateProgressiveStatus( $data, 5 );
		/*

		*/
		$this->ajax_return( $data );
	}
	//GetBettingProfileList
	public function GetBettingProfileList()
	{
		$sn = $this->sxuser['sn'];
		$snuid = $this->sxuser['snuid'];
		$sn && $snuid OR exit('{"Code":"4001","msg":"params invalid"}');
		$data = $this->user_api->GetBettingProfileList( NULL, 5 );
				
		$this->ajax_return( $data );
	}
	//GetLiveGamesTransactions
	public function GetLiveGamesTransactions()
	{
		$sn = $this->sxuser['sn'];
		$snuid = $this->sxuser['snuid'];
		$data['FromDate'] = $ths->sxuser['FromDate'] ?? '2017-03-28T14:05:57';
		$data['ToDate'] = $ths->sxuser['ToDate'] ?? '2017-03-28T19:05:57';
		$sn && $snuid && $data['FromDate'] && $data['ToDate'] OR exit('{"Code":"4001","msg":"params invalid"}');
		$data = $this->user_api->GetLiveGamesTransactions( $data, 5 );
		/*

		*/
		
		$this->ajax_return( $data );
	}
	//LiveGamesFraudCheck
	public function LiveGamesFraudCheck()
	{
		$sn = $this->sxuser['sn'];
		$snuid = $this->sxuser['snuid'];
		$data['AccountName'] = $ths->sxuser['merge_username'];
		$data['FromDate'] = $ths->sxuser['FromDate'] ?? '2017-03-28T14:05:57';
		$data['ToDate'] = $ths->sxuser['ToDate'] ?? '2017-03-28T14:05:57';		
		$data['Game'] = $ths->sxuser['GameId']; //Blackjack
		//$data['RoundId'] = $ths->sxuser['RoundId'] ?? '2017-03-28T14:05:57';
		$sn && $snuid && $data['FromDate'] && $data['ToDate'] && $data['AccountName'] && $data['Game'] OR exit('{"Code":"4001","msg":"params invalid"}');
		$data = $this->user_api->LiveGamesFraudCheck( $data, 5 );
		/*

		*/		
		$this->ajax_return( $data );
	}
	//GetLiveDealerGames
	public function GetLiveDealerGames()
	{
		$data = $this->user_api->GetLiveDealerGames( NULL, 5 );
		/*

		*/
		$this->ajax_return( $data );
	}
	//GetPlayersUpdatedBalance
	public function GetPlayersUpdatedBalance()
	{
		$sn = $this->sxuser['sn'];
		$snuid = $this->sxuser['snuid'];
		$sn && $snuid OR exit('{"Code":"4001","msg":"params invalid"}');
		//$data['AccountNumber'] = $ths->sxuser['AccountNumber'] ?? 'OC0025588498';		
		$data = $this->user_api->GetPlayersUpdatedBalance( null , 5 );
		/*

		*/		
		$this->ajax_return( $data );
	}
	//GetFinancialTransactionStatus
	public function GetFinancialTransactionStatus()
	{
		$data['FromDate'] = $ths->sxuser['FromDate'] ?? '2017-03-28T14:05:57';
		$data['ToDate'] = $ths->sxuser['ToDate'] ?? '2017-03-29T14:05:57';
		$data['AccountNumber'] = $ths->sxuser['AccountNumber'] ?? 'OC0025588498';
		$data['FromDate'] && $data['ToDate'] OR exit('{"Code":"4001","msg":"params invalid"}');
		$data = $this->user_api->GetFinancialTransactionStatus( $data, 5 );
		/*

		*/		
		$this->ajax_return( $data );
	}
	//GetPlaycheckUrl
	public function GetPlaycheckUrl()
	{
		$data['AccountNumber'] = $ths->sxuser['AccountNumber'] ?? 'OC0025588498';
		$data['LanguageId'] = $ths->sxuser['LanguageId'] ?? 3;
		//$data['TransactionId'] = $ths->sxuser['TransactionId'] ?? '';
		$data['TimeZone'] = $ths->sxuser['TimeZone'] ?? 8.0;
		$data = $this->user_api->GetPlaycheckUrl( $data, 5 );
		/*

		*/
		print_r($data);
		//$this->ajax_return( $data );
	}
	//GetLanguageList
	public function GetLanguageList()
	{
		$data = $this->user_api->GetLanguageList( NULL, 5 );
		/*

		*/
		print_r($data);
		//$this->ajax_return( $data );
	}
	//Deposit
	public function Deposit()
	{
		$data['AccountNumber'] = $ths->sxuser['AccountNumber'] ?? 'OC0025588498';
		$data['Amount'] = $ths->sxuser['Amount'] ?? 1.00;
		$data['TransactionReferenceNumber'] = $ths->sxuser['TransactionReferenceNumber'] ?? '';
		$data['IdempotencyId'] = $ths->sxuser['IdempotencyId'] ?? '';
		$data['AccountNumber'] && $data['Amount'] > 0 OR exit('{"Code":"4001","msg":"params invalid"}');
		$data = $this->user_api->Deposit( $data, 5 );
		/*

		*/
		print_r($data);
		//$this->ajax_return( $data );
	}
	//Withdrawal
	public function Withdrawal()
	{
		$data['AccountNumber'] = $ths->sxuser['AccountNumber'] ?? 'OC0025588498';
		$data['Amount'] = $ths->sxuser['Amount'] ?? 1.00;
		$data['TransactionReferenceNumber'] = $ths->sxuser['TransactionReferenceNumber'] ?? '';
		$data['IdempotencyId'] = $ths->sxuser['IdempotencyId'] ?? '';
		$data['AccountNumber'] && $data['Amount'] > 0 OR exit('{"Code":"4001","msg":"params invalid"}');
		$data = $this->user_api->Withdrawal( $data, 5 );
		/*

		*/
		print_r($data);
		//$this->ajax_return( $data );
	}
	//WithdrawalAll
	public function WithdrawalAll()
	{
		$data['AccountNumber'] = $ths->sxuser['AccountNumber'] ?? 'OC0025588498';
		$data['TransactionReferenceNumber'] = $ths->sxuser['TransactionReferenceNumber'] ?? '';
		$data = $this->user_api->WithdrawalAll( $data, 5 );
		/*

		*/
		print_r($data);
		//$this->ajax_return( $data );
	}
	//GetOnlinePlayersBalance
	public function GetOnlinePlayersBalance()
	{
		$data['SessionDuration'] = $ths->sxuser['SessionDuration'] ?? '';
		$data['Fields'] = $ths->sxuser['Fields'] ?? 'AccountNumber';
		$data = $this->user_api->GetOnlinePlayersBalance( null, 5 );
		/*

		*/
		print_r($data);
		//$this->ajax_return( $data );
	}
	//GetAccountBalance
	public function GetAccountBalance()
	{
		$data['Accounts'] = $this->sxuser['Accounts'] ?? ['OC0025588498'];
		$data = $this->user_api->GetAccountBalance( $data, 5 );
		/*

		*/
		print_r($data);
		//$this->ajax_return( $data );
	}
	//ResetLoginAttempts
	public function ResetLoginAttempts()
	{
		$data['Accounts'] = $this->sxuser['Accounts'] ?? ['OC0025588498'];
		$data = $this->user_api->ResetLoginAttempts( $data, 5 );
		/*

		*/
		print_r($data);
		//$this->ajax_return( $data );
	}
	//SendMobileLink
	public function SendMobileLink()
	{
		$data['AccountNumber'] = $this->sxuser['AccountNumber'] ?? 'OC0025588498';
		$data['Email'] = $this->sxuser['Email'] ?? 'wxd314@163.com';
		$data['GameLanguageId'] = $this->sxuser['GameLanguageId'] ?? 1; //default English
		$data['MailLanguageId'] = $this->sxuser['MailLanguageId'] ?? 1; //default English
		$data['SendAccountDetails'] = $this->sxuser['SendAccountDetails'] ?? false;
		$data['AccountNumber'] && $data['Email'] OR exit('{"Code":"4001","msg":"params invalid"}');
		$data = $this->user_api->SendMobileLink( $data, 5 );
		/*

		*/
		print_r($data);
		//$this->ajax_return( $data );
	}
	//AddPlayerAccount
	public function AddPlayerAccount()
	{
		$sn = $this->sxuser[ 'sn' ];
		$snuid = $this->sxuser[ 'snuid' ];	
		
		$username = $this->sxuser[ 'merge_username' ];
		/*if( empty($username) ) {
			$this->load->helper('string');
			$username = $this->sxuser[ 'sn' ] . strtolower(random_string( 'alnum', 10 ));
		}*/		
		$check = $this->user_api->IsAccountAvailable( [ 'accountNumber'=>$username ], 3 );
		if( !isset($check['IsAccountAvailableResult']) OR !$check['IsAccountAvailableResult']['IsAccountAvailable'] ) {
			exit( '{"Code":4001,"msg":"The player has exists"}' );
		}
		$data['PreferredAccountNumber'] = $username;

		$data['FirstName'] = $this->sxuser['FirstName'];
		$data['LastName'] = $this->sxuser['LastName'];
		$data['Email'] = $this->sxuser['Email'] ?? '';
		$data['MobilePrefix'] = $this->sxuser['MobilePrefix'] ?? '';
		$data['MobileNumber'] = $this->sxuser['MobileNumber'] ?? '';
		$data['BirthDate'] = $this->sxuser['BirthDate'] ?? date('Y-m-d H:i:s');
		$data['DepositAmount'] = $this->sxuser['DepositAmount'];
		$data['PinCode'] = $this->sxuser['PinCode'] ?? '123456';
		$data['IsProgressive'] = $this->sxuser['IsProgressive'] ?? false;
		//$data['BettingProfiles'] = $this->sxuser['BettingProfiles'] ?? '';
		$username && $data['FirstName'] && $data['LastName'] && $data['DepositAmount'] > 0 OR exit('{"Code":"4001","msg":"params invalid"}');
		$ret = $this->user_api->AddPlayerAccount( $data, 5 );
		/*

		*/
		if( isset($ret['Status'],$ret['Result']['CustomerId']) ){
			$this->load->model( 'sx/mg/user_model' );
			$insert_data[ 'sn' ] = $sn;
			$insert_data[ 'snuid' ] = $snuid;
			$insert_data[ 'g_username' ] = $ret['Result']['AccountNumber'];
			$insert_data[ 'g_password' ] = $ret['Result']['PinCode'];
			$insert_data[ 'firstName' ] = $ret['Result']['FirstName'];
			$insert_data[ 'lastName' ] = $ret['Result']['LastName'];
			//$insert_data[ 'isSendGame' ] = $data['isSendGame'];			
			$insert_data[ 'createtime' ] = date( 'Y-m-d H:i:s' );
			$insert_data['bettingProfiles'] = json_encode($ret['Result']['BettingProfiles']);
			$insert_data[ 'customerId' ] = $ret['Result']['CustomerId'];
			$insert_data[ 'casinoId' ] = $ret['Result']['CasinoId'];

			$insert_data[ 'mobileNumber' ] = $ret['Result']['MobileNumber'];
			$insert_data[ 'currency' ] = $ret['Result']['CurrencyId'];
			$insert_data[ 'balance' ] = $ret['Result']['Balance'];
			$insert_data[ 'isProgressive' ] = empty($ret['Result']['IsProgressive']) ? 0 : 1 ;
			$this->user_model->db->insert( 'mg_user', $insert_data );
			
		}
		$this->ajax_return( $data );
	}
	//AddStationAccount
	public function _AddStationAccount()
	{
		$data['Name'] = $this->sxuser['Name'] ?? '';
		$data['PinCode'] = $this->sxuser['PinCode'] ?? '';
		$data['DepositAmount'] = $this->sxuser['DepositAmount'] ?? 1.00;
		$data['IsProgressive'] = $this->sxuser['IsProgressive'] ?? false;
		//$data['BettingProfiles'] = $this->sxuser['BettingProfiles'] ?? 'Jerry';
		$data['DepositAmount'] > 0 OR exit('{"Code":"4001","msg":"params invalid"}');
		$data = $this->user_api->AddStationAccount( $data, 5 );
		/*

		*/
		
		$this->ajax_return( $data );
	}
	//EditAccount
	public function EditAccount()
	{
		$sn = $this->sxuser[ 'sn' ];
		$snuid = $this->sxuser[ 'snuid' ];	
		$data['AccountNumber'] = $update_data['g_username'] = $this->sxuser[ 'merge_username' ];
		if( $this->sxuser[ 'firstName' ] ) $data['FirstName'] = $update_data['firstName'] = $this->sxuser[ 'firstName' ];
		if( $this->sxuser[ 'lastName' ] ) $data['LastName'] = $update_data['lastName'] = $this->sxuser[ 'lastName' ];
		if( $this->sxuser[ 'password' ] ) $data['PinCode'] = $update_data['g_password'] = $this->sxuser[ 'password' ];
		if( $this->sxuser[ 'mobileNumber' ] ) $data['MobileNumber'] = $update_data['mobileNumber'] = $this->sxuser[ 'mobileNumber' ];
		if( $this->sxuser[ 'eMail' ] ) $data['Email'] = $update_data['email'] = $this->sxuser[ 'eMail' ];
		if( $this->sxuser[ 'bettingProfiles' ] ) {
			if( is_null(json_decode($this->sxuser[ 'bettingProfiles' ])) ) exit('{"Code":"4001","msg":"params invalid"}');
			$data['BettingProfiles'] = $update_data['bettingProfiles'] = $this->sxuser[ 'bettingProfiles' ];
		}		
		$sn && $snuid && $data['accountNumber'] OR exit('{"Code":"4001","msg":"params invalid"}');

		/*$data['FirstName'] = $this->sxuser['FirstName'];
		$data['LastName'] = $this->sxuser['LastName'];
		$data['Email'] = $this->sxuser['Email'];
		$data['MobileNumber'] = $this->sxuser['MobileNumber'];
		$data['PinCode'] = $this->sxuser['PinCode'];
		$data['BettingProfiles'] = $this->sxuser['BettingProfiles'];
		$data['AccountNumber'] OR exit('{"Code":"4001","msg":"params invalid"}');*/
		$ret = $this->user_api->EditAccount( $data, 5 );
		if(isset($ret['Status']) && $ret['Status']['ErrorCode']==0 ){
			$this->load->model( 'sx/mg/user_model' );
			$update_data[ 'sn' ] = $sn;
			$update_data[ 'snuid' ] = $snuid;
			$update_data[ 'customerId' ] = $ret['Result']['CustomerId'];
			$update_data[ 'casinoId' ] = $ret['Result']['CasinoId'];
			$update_data[ 'nickName' ] = $ret['Result']['NickName'];
			$this->user_model->update_userinfo( $this->platform_name, $update_data );
		}
		
		$this->ajax_return( $data );
	}
	//GetAccountDetails
	public function GetAccountDetails()
	{
		$sn = $this->sxuser['sn'];
		$data['AccountNumber'] = $this->sxuser['merge_username'];
		$data['AccountNumber'] OR exit('{"Code":"4001","msg":"params invalid"}');
		$ret = $this->user_api->GetAccountDetails( $data, 5 );
		if( isset($ret['Status']) && $ret['Status']['ErrorCode']==0 ){
			$this->load->model('sx/mg/user_model');
			$this->user_model->update_userinfo($this->platform_name,[
				'g_username' =>$data['AccountNumber'],
				'firstName'     =>$ret['FirstName'],
				'lastName'     =>$ret['LastName'],
				'nickName'    =>$ret['NickName'],
				'email'           =>$ret['Email'],
				'mobileNumber'=>$ret['MobileNumber'],
				'casinoId'=>$ret['CasinoId'],
				'status' => $ret['AccountStatus'],
				'isProgressive'=>$ret['SuspendedAccountStatus'],
				'bettingProfiles'=>json_encode($ret['BettingProfiles'])
				]);
		}
		/*

		*/		
		$this->ajax_return( $data );

	}
	//LockAccounts
	public function LockAccounts()
	{
		$sn = $this->sxuser['sn'];
		$data['Accounts'] = [$this->sxuser['merge_username']] ;

		//$data['Accounts'] = $this->sxuser['Accounts'] ?? ['OC0025598278','OC0025588498'];
		$data['IsLock'] = $this->sxuser['IsLock'] ?? true;
		$sn && $data['Accounts'] OR exit('{"Code":"4001","msg":"params invalid"}');
		$data = $this->user_api->LockAccount( $data, 5 );
		/*

		*/
		if(isset($data['Result']) && $data['Result']['IsSucceeded'] == 1) {
			$this->load->model('sx/mg/user_model');
			$this->user_model->update_userinfo($this->platform_name, array('status'=>$data['IsLock'] ? 1 : 0,'g_username'=>$data['Accounts']) );
		}
		$this->ajax_return( $data );
	}
	//Returns current agent balance
	public function GetMyBalance()
	{
		$data = $this->user_api->GetMyBalance( null, 5 );
		/*

		*/
		$this->ajax_return( $data );
	}
	//Returns list of bets and payouts amount still on the table per account of the games he played.
	public function GetPlayerFundsInPlayDetails()
	{
		$sn = $this->sxuser['sn'];
		$data['AccountNumber'] = $this->sxuser['merge_username'] ?? 'OC0025598278';
		$sn && $data['AccountNumber'] OR exit('{"Code":"4001","msg":"params invalid"}');
		$data = $this->user_api->GetPlayerFundsInPlayDetails( $data, 5 );
		/*

		*/
		$this->ajax_return( $data );
	}
	//Returns true if account is available for create new player, otherwise return false.
	public function IsAccountAvailable()
	{
		$sn = $this->sxuser['sn'];
		$data['AccountNumber'] = $this->sxuser['merge_username'] ?? 'OC0025588498';
		$sn && $data['AccountNumber'] OR exit('{"Code":"4001","msg":"params invalid"}');
		$data = $this->user_api->IsAccountAvailable( $data, 5 );
		/*

		*/		
		$this->ajax_return( $data );
	}

}