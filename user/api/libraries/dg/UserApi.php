<?php

if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

class UserApi extends BaseApi
{
	/**
	 * 会员注册
	 * $username 用户名
	 * $password(md5)
	 * $winLimit 奖金限额( 默认不限制 )
	 * $currencyName 货币名称( 默认CNY )
	 * $data 目标限红组号( 不填则为A组 )
	 * return code_message
	 */
	public function signup( $username, $password, $win_limit = 0, $data = 'A', $currency_name = 'CNY' )
	{
		$data = [
			'data' => $data,
			'member' => [ 'username' => $username, 'password' => $password, 'currencyName' => $currency_name, 'winLimit' => $win_limit ]
		];

		return self::send( $data );
	}

	/**
	 * 会员登录
	 * $username 用户名
	 * $password(md5) 可以不传,如果密码不同,将自动修改DG数据库保存的密码
	 * $lang 语言(默认为cn)
	 */
	public function login( $username, $password = '', $lang = 'cn' )
	{
		$data = [
				'lang' => $lang,
				'member' => [ 'username' => $username, 'password' => $password ]
		];

		//无需校验token
		self::$verify_token = false;
		return self::send( $data );
	}

	/**
	 * 会员试玩登入
	 * $device (设备类型,默认为web)
	 * $lang 语言(默认为en)
	 */
	public function free( $device = 1, $lang = 'cn' )
	{
		 $data = [
			 'lang' => $lang,
			 'device' => $device
		 ];

		//无需校验token
		self::$verify_token = false;
		return self::send( $data );
	}

	/**
	 * 更新会员信息
	 * @param $username 用户名
	 * @param $password 密码( md5 )
	 * @param int $win_milit $winLimit 奖金限额( 默认不限制 )
	 * @param int $status 会员状态：0:停用, 1:正常, 2:锁定(不能下注)（默认正常）
	 */
	public function update( $username, $password, $status = 1, $win_limit = 0 )
	{
		$data = [
			'member' => [ 'username' => $username, 'password' => $password, 'winLimit' => $win_limit, 'status' => $status ]
		];

		return self::send( $data );
	}

	/**
	 * 获取会员余额
	 * @param $username
	 * @return bool|mixed
	 */
	public function getBalance( $username )
	{
		$data = [
			'member' => [ 'username' => $username ],
			'method' => 'getBalance'
		];

		return  self::send( $data );
	}

	/**
	 * 更新用户余额
	 * @param $username
	 */
	public function updateBalance( $username, $platform_name )
	{
		$data = $this->getBalance( $username );
		if( isset( $data[ 'codeId' ] ) && $data[ 'codeId' ] == 0 && isset( $data[ 'member' ][ 'balance' ] ) && $username == strtolower( $data[ 'member' ][ 'username' ] ) )
		{
			$balance = $data[ 'member' ][ 'balance' ];
			self::$ci->load->model( 'sx/dg/user_model', 'user_model' );
			if( self::$ci->user_model->update_balance( $username, $balance, $platform_name ) )
			{
				return $data;
			}
		}

		return false;
	}

	/**
	 * 获取当前代理下在DG在线会员信息
	 * 请求间隔 30 秒
	 */
	public function onlineReport()
	{
		return self::send( [] );
	}
}