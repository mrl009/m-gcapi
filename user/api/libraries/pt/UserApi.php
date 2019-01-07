<?php

if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

class UserApi extends BaseApi
{
	/**
	 * 注册
	 */
	public function signup( $username, $password, $currency = 'CNY', $method = 'post:player/createplayer' )
	{
		$data = [
			'membercode' => $username,
			'password' => $password,
			'currency' => $currency,
			'method' => $method
		];

		return self::send( $data );
	}


	/**
	 * 用于检测玩家是否已经存在
	 */
	public function check_user_exists( $username, $method = 'get:player/checkplayerexists' )
	{
		$data = [
			'membercode' => $username,
			'method' => $method
		];

		return self::send( $data );
	}

	/**
	 * 查询玩家余额
	 */
	public function get_balance( $username, $producttype = 0, $method = 'get:account/getbalance' )
	{
		$data = [
			'membercode' => $username,
			'method' => $method,
			'producttype' => $producttype,
		];

		return self::send( $data );
	}

	/**
	 * 设置密码
	 */
	public function reset_password( $username, $password, $method = 'put:player/resetpassword' )
	{
		$data = [
			'membercode' => $username,
			'password' => $password,
			'method' => $method
		];

		return self::send( $data );
	}

	/**
	 * 删除玩家会话
     * @return Array
	 */
	public function kill_session( $username, $producttype = 0, $method = 'put:player/killsession' )
	{
		$data = [
			'membercode' => $username,
			'producttype' => $producttype,
			'method' => $method
		];

		return self::send( $data );
	}

	/* 验证玩家身份
	 * 此 API 用户验证游戏玩家身份，也可应用于运营商的网上支付站点。
	 */
	public function authenticate_player( $username, $password, $method = 'put:player/authenticateplayer' )
	{
		$data = [
			'membercode' => $username,
			'password' => $password,
			'method' => $method
		];

		return self::send( $data );
	}

	/* 2.7检查球员令牌
	 * 检查会员是否登录
	 */
	public function check_playertoken( $username, $producttype = 0, $token = 'test123', $method = 'get:player/checkplayertoken' )
	{
		$data = [
			'membercode' => $username,
			'producttype' => $producttype,
			'token' => $token,
			'method' => $method
		];

		return self::send( $data );
	}

	/* API#2.8 冻结玩家
	 * 冻结玩家
	 */
	public function freeze_player( $username, $frozenstatus, $method = 'post:player/freezeplayer' )
	{
		$data = [
			'membercode' => $username,
			'frozenstatus' => $frozenstatus,
			'method' => $method
		];

		return self::send( $data );
	}

	/**
	 * 存取筹码( 提款前， 请查询用户的户口有足够的钱 )
	 */
	public function create_transaction( $username, $amount, $externaltransactionid, $producttype = 0, $method = 'post:chip/createtransaction' )
	{
		$data = [
			'membercode' => $username,
			'amount' => $amount,
			'producttype' => $producttype,
			'externaltransactionid' => $externaltransactionid,
			'method' => $method
		];

		return self::send( $data );
	}

	/**
	 * 检查存取状态
	 */
	public function check_transaction( $username, $externaltransactionid, $producttype = 0, $method = 'get:chip/checktransaction' )
	{
		$data = [
			'membercode' => $username,
			'externaltransactionid' => $externaltransactionid,
			'producttype' => $producttype,
			'method' => $method
		];

		return self::send( $data );
	}
    /**
     * 玩家下注日志
     */
    public function get_bet_log( $username, $startdate, $enddate, $page = 1, $producttype = 0, $method = 'get:report/getbetlog' )
    {
        $data = [
            'membercode' => $username,
            'startdate' => $startdate,
            'enddate' => $enddate,
            'page' => $page,
            'producttype' => $producttype,
            'method' => $method,
        ];

        return self::send( $data );
    }
    /**
     * 玩家下注日志(无分页)
     */
    public function get_bet_log_nopage( $username, $startdate, $enddate, $producttype = 0, $method = 'get:report/getbetlog' )
    {
        $data = [
        	'membercode' => $username,
            'startdate' => $startdate,
            'enddate' => $enddate,            
            'producttype' => $producttype,            
            'method' => $method,
        ];
        return self::send( $data );

    }
    /**
     * 玩家游戏统计
     */
    public function get_game_stats( $startdate, $enddate, $producttype = 0, $currencycode = 'CNY', $method = 'get:report/getgamestats' )
    {
        $data = [
            'startdate' => $startdate,
			'enddate' => $enddate,
			'producttype' => $producttype,
			'currency' => $currencycode,
            'method' => $method,
        ];

        return self::send( $data );
    }

    /*
     * 玩家下注流量
	 *
	 *
	 * */
    public function get_bet_flow( $startdate, $enddate, $page = 1, $pagesize = 50, $producttype = 0, $currencycode, $method = 'get:report/getbetflow' )
    {
    	$data = [
            'startdate' => $startdate,
			'enddate' => $enddate,
			'page' => $page,
			'pagesize' => $pagesize,
			'producttype' => $producttype,
			'currency' => $currencycode,
            'method' => $method,
        ];

        return self::send( $data );
    }

    /*
     * 支出PT 回扣
	 *
	 *
	 * */
    public function pay_rebate( $username, $amount, $externaltranid, $startdate, $enddate, $gamecode, $viplevel = 1, $currencycode = 'CNY', $method = 'post:rebate/payrebate' )
    {
    	$data = [
            'membercode' => $username,
			'amount' => $amount,
			'externaltranid' => $externaltranid,
			'startdate' => $startdate,
			'enddate' => $enddate,
			'gamecode' => $gamecode,
			'viplevel' => $viplevel,
			'currencycode' => $currencycode,
			'method' => $method
        ];

        return self::send( $data );
    }
    /*
     * 扣除 PT回扣( 此API将玩家在规定的PT回扣期内所累积的金额扣除。扣除的金额是无法取回的 )
     *
	 *
	 *
	 * */
    public function clear_rebate( $username, $amount, $externaltranid, $startdate, $enddate, $gamecode, $viplevel = 1, $currencycode = 'CNY', $method = 'post:rebate/clearrebate' )
    {
    	$data = [
            'membercode' => $username,
			'amount' => $amount,
			'externaltranid' => $externaltranid,
			'startdate' => $startdate,
			'enddate' => $enddate,
			'gamecode' => $gamecode,
			'viplevel' => $viplevel,
			'currencycode' => $currencycode,
			'method' => $method
        ];

        return self::send( $data );
    }
    /*
     * 统一支出PT 回扣 (此API将规定的一组玩家在规定的PT回扣期内所累积的金额支出进PT主钱包)
	 * params.eg:"param":[{"membercode":"raheem42", "amount":"0.01", "externaltranid":" transid01"},
	 *	{"membercode":"alibababababab", "amount":"0.01", "externaltranid":" transid02"}]
	 *
	 * */
    public function mass_pay_rebate( $startdate, $enddate, $gamecode, $viplevel = 1, $currencycode = 'CNY', $params = [], $method = 'post:rebate/masspayrebate' )
    {
    	$data = [
            'startdate' => $startdate,
			'enddate' => $enddate,
			'gamecode' => $gamecode,
			'viplevel' => $viplevel,
			'currencycode' => $currencycode,
			'param' => $params,
			'method' => $method
        ];

        return self::send( $data );
    }
    /*
     * 玩家回扣日志 (此API返回为Playtech玩家所做的回扣（支付/清零))
	 *
	 *
	 * */
    public function get_rebate_log( $username, $startdate, $enddate, $page = 1, $producttype = 0, $method = 'get:report/getrebatelog' )
    {
    	$data = [
            'startdate' => $startdate,
			'enddate' => $enddate,
			'membercode' => $username,
			'page' => $page,
			'producttype' => $producttype,
			'method' => $method
        ];

        return self::send( $data );
    }
    /*
     * 启动游戏
	 *
	 *
	 * */
    public function launch_game( $username, $ipaddress, $gamecode, $langauge = 'ZH-CN', $producttype = 0, $method = 'post:game/launchgame' )
    {
    	$data = [
			'membercode' => $username,
			'gamecode' => $gamecode,
			'langauge' => $langauge,
			'ipaddress' => $ipaddress,
			'producttype' => $producttype,
			'method' => $method
		];

		return self::send( $data );
    }
    /*
     * 获取累积奖金 (此API用于查询并返回老虎机游戏的累积头奖金额，金额随输入的货币单位而变化)
	 *
	 *
	 * */
    public function get_jack_potlist( $producttype = 0, $currency= 'CNY', $method = 'get:casino/getjackpotlist' )
    {
    	$data = [
			'producttype' => $producttype,
			'currency' => $currency,
			'method' => $method
		];

		return self::send( $data );
    }

	/**
	 * 登录
	 */
	public function login( $username, $ipaddress, $gamecode,$user, $language = 'ZH-CN', $producttype = 0, $method = 'post:game/launchgame' )
	{

		$data = [
			'membercode' => $username,
			'gamecode' => $gamecode,
			'language' => $language,
			'ipaddress' => $ipaddress,
			'producttype' => $producttype,
			'method' => $method,
		];

		$ret_message = self::send( $data );

		if( $ret_message[ 'Code' ] == 0 )
		{

			$username = $ret_message[ 'PlaytechUserName' ];
            $password = $ret_message[ 'PlaytechPassword' ];

            $this->kill_session( $username );

            return '<form style="display:none;" id="loginform" name="loginform" method="post" ><input type="text" name="username" value="'.$username.'"><input type="text" name="password" value="'.$password.'"></form><script type="text/javascript" src="https://login.longsnake88.com/jswrapper/integration.js.php?casino=longsnake88"></script><script type="text/javascript">iapiSetCallout(\'Login\', calloutLogin);iapiSetCallout(\'Logout\', calloutLogout);function login(realMode) {iapiLogin(document.getElementById("loginform").username.value.toUpperCase(), document.getElementByI("loginform").password.value, realMode, "en");}function logout(allSessions, realMode) {  iapiLogout(allSessions, realMode);}function calloutLogin(response) {if (response.errorCode) {alert("Login failed, " + response.errorText);}else {window.location = "http://cache.download.banner.mightypanda88.com/casinoclient.html?game='.$gamecode.'&language=EN&nolobby=1";}}function calloutLogout(response) {if (response.errorCode) {alert("Logout failed, " + response.errorCode);}else {alert("LogoutOK"); }}</script></script><script type="text/javascript">login(1);</script>';
		}


	}


}
