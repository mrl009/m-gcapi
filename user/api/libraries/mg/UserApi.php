<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class UserApi extends BaseApi
{
	/**
	 * @param $username  用户名
	 * @param $actype    actype=1 代表真錢账号;  actype=0 代表试玩账号
	 * @param $password  密码
	 * @param string $oddtype  盘口, 设定新玩家可下注的范围,默认为A
	 * @param string $method  数值 = “lg” 代表 ”检测并创建游戏账号
	 * @param string $cur  货币种类
	 */
	public function GetCurrenciesForAddAccount( $params = array(), $type = 5 )
	{
		$params['method'] = __FUNCTION__;
		$params['type']   = $type;
		return self::send( $params );
	}

	public function GetBettingProfileList( $params = array(), $type = 5 )
	{
		$params['method'] = __FUNCTION__;
		$params['type']   = $type;

		return self::send( $params );
	}
	
	public function AddAccount( $params = array(), $type = 5 )
	{
		$params['method'] = __FUNCTION__;
		$params['type']   = $type;

		return self::send( $params );
	}

	public function AddStationAccount( $params = array(), $type = 5 )
	{
		/*$data = [
			'password' => $params['password'],
			'nickName'     => $params['nickName'],
			'currency'     => $params['currency'],
			'bettingProfileId'     => $params['BettingProfileId'],
			'isProgressive'     => $params['isProgressive'],
			'isGeneratePassword'     => $params['isGeneratePassword'],
			//'isSendGame'     => $params['isSendGame'],
			//'email'     => $params['email'],
			//'mobileNumber'     => $params['mobileNumber'],
			//'isProgressive'     => $params['isProgressive'],
			'method' => __FUNCTION__,
			'type'   => $type
		];*/
		$params['method'] = __FUNCTION__;
		$params['type']   = $type;
		return self::send( $params );
	}
	public function AddAccountEx( $params = array(), $type = 5 ) 
	{
		$params['method'] = __FUNCTION__;
		$params['type']   = $type;
		return self::send( $params );
	}

	public function EditAccount( $params = array(), $type = 5 ) 
	{
		$params['method'] = __FUNCTION__;
		$params['type']   = $type;
		return self::send( $params );
	}
	public function GetAccountDetails( $params = array(), $type = 5 ) 
	{
		$params['method'] = __FUNCTION__;
		$params['type']   = $type;
		return self::send( $params );
	}

	public function GetCurrenciesForDeposit( $params = array(), $type = 5 ) 
	{
		$params['method'] = __FUNCTION__;
		$params['type']   = $type;
		return self::send( $params );
	}

	public function GetMyBalance( $params = array(), $type = 5 ) 
	{
		$params['method'] = __FUNCTION__;
		$params['type']   = $type;
		return self::send( $params );
	}

	public function Deposit( $params = array(), $type = 5 ) 
	{
		$params['method'] = __FUNCTION__;
		$params['type']   = $type;
		return self::send( $params );
	}

	public function GetAccountBalance( $params = array(), $type = 5 ) 
	{
		$params['method'] = __FUNCTION__;
		$params['type']   = $type;
		return self::send( $params );
	}

	public function Withdrawal( $params = array(), $type = 5 ) 
	{
		$params['method'] = __FUNCTION__;
		$params['type']   = $type;
		return self::send( $params );
	}

	public function WithdrawalAll( $params = array(), $type = 5 ) 
	{
		$params['method'] = __FUNCTION__;
		$params['type']   = $type;
		return self::send( $params );
	}

	public function SendMobileGame( $params = array(), $type = 5 ) 
	{
		$params['method'] = __FUNCTION__;
		$params['type']   = $type;
		return self::send( $params );
	}

	public function ChangeSuspendAccountStatus( $params = array(), $type = 5 ) 
	{
		$params['method'] = __FUNCTION__;
		$params['type']   = $type;
		return self::send( $params );
	}

	public function ResetLoginAttempts( $params = array(), $type = 5 ) 
	{
		$params['method'] = __FUNCTION__;
		$params['type']   = $type;
		return self::send( $params );
	}

	public function LockAccount( $params = array(), $type = 5 ) 
	{
		if( $type === 5 ) 
		{
			$params['method'] = 'LockAccounts';
			$params['type']   = 5;			
		}
		else
		{
			$params['method'] = 'LockAccount';
			$params['type']   = 3;		
		}
		return self::send( $params );
	}

	public function GetBetInfoDetails( $params = array(), $type = 5 ) 
	{
		$params['method'] = __FUNCTION__;
		$params['type']   = $type;
		return self::send( $params );
	}

	public function GameplayDetailedReport( $params = array(), $type = 5 ) 
	{
		$params['method'] = __FUNCTION__;
		$params['type']   = $type;
		return self::send( $params );
	}

	public function GetReportByName( $params = array(), $type = 5 ) 
	{
		$params['method'] = __FUNCTION__;
		$params['type']   = $type;
		return self::send( $params );
	}

	public function GetReportResult( $params = array(), $type = 5 ) 
	{
		$params['method'] = __FUNCTION__;
		$params['type']   = $type;
		return self::send( $params );
	}

	public function GetTransactionDetail( $params = array(), $type = 5 ) 
	{
		$params['method'] = __FUNCTION__;
		$params['type']   = $type;
		return self::send( $params );
	}

	public function GetLanguageList( $params = array(), $type = 5 ) 
	{
		$params['method'] = __FUNCTION__;
		$params['type']   = $type;
		return self::send( $params );
	}

	public function GetPlaycheckUrl( $params = array(), $type = 5 ) 
	{
		$params['method'] = __FUNCTION__;
		$params['type']   = $type;
		return self::send( $params );
	}

	public function SendMobileLink( $params = array(), $type = 5 ) 
	{
		$params['method'] = __FUNCTION__;
		$params['type']   = $type;
		return self::send( $params );
	}

	public function IsAccountAvailable( $params = array(), $type = 5 ) 
	{
		$params['method'] = __FUNCTION__;
		$params['type']   = $type;
		return self::send( $params );
	}

	public function GetPlayerFundsInPlayDetails( $params = array(), $type = 5 ) 
	{
		$params['method'] = __FUNCTION__;
		$params['type']   = $type;
		return self::send( $params );
	}

	/*
	 * API#5 下面的方法名是与API3不一样的,其他见上面与API#3方法名一样
	 */
	public function GetSpinBySpinData( $params = array(), $type = 5 ) 
	{
		$params['method'] = __FUNCTION__;
		$params['type']   = $type;
		return self::send( $params );
	}
	public function UpdateProgressiveStatus( $params = array(), $type = 5 ) 
	{
		$params['method'] = __FUNCTION__;
		$params['type']   = $type;
		return self::send( $params );
	}
	public function GetLiveGamesTransactions( $params = array(), $type = 5 ) 
	{
		$params['method'] = __FUNCTION__;
		$params['type']   = $type;
		return self::send( $params );
	}
	public function LiveGamesFraudCheck( $params = array(), $type = 5 ) 
	{
		$params['method'] = __FUNCTION__;
		$params['type']   = $type;
		return self::send( $params );
	}
	public function GetLiveDealerGames( $params = array(), $type = 5 ) 
	{
		$params['method'] = __FUNCTION__;
		$params['type']   = $type;
		return self::send( $params );
	}
	public function GetPlayersUpdatedBalance( $params = array(), $type = 5 ) 
	{
		$params['method'] = __FUNCTION__;
		$params['type']   = $type;
		return self::send( $params );
	}
	public function GetFinancialTransactionStatus( $params = array(), $type = 5 ) 
	{
		$params['method'] = __FUNCTION__;
		$params['type']   = $type;
		return self::send( $params );
	}
	public function GetOnlinePlayersBalance( $params = array(), $type = 5 ) 
	{
		$params['method'] = __FUNCTION__;
		$params['type']   = $type;
		return self::send( $params );
	}
	public function AddPlayerAccount( $params = array(), $type = 5 ) 
	{
		$params['method'] = __FUNCTION__;
		$params['type']   = $type;
		return self::send( $params );
	}
}