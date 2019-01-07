<?php

if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

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
	public function signup( $username, $actype, $password, $oddtype, $cur, $method = 'lg' )
	{
		$data = [
			'loginname' => $username,
			'method' => $method,
			'actype' => $actype,
			'password' => $password,
			'oddtype' => $oddtype,
			'cur' => $cur,
		];

		return self::send( $data );
	}

	/**
	 * @param $username
	 * @param $actype
	 * @param $password
	 * @param string $cur
	 * @param string $method
	 * @return bool|mixed
	 */
	public function get_balance( $username, $actype, $password, $cur = 'CNY', $method = 'gb' )
	{
		$data = [
			'loginname' => $username,
			'method' => $method,
			'actype' => $actype,
			'password' => $password,
			'cur' => $cur,
		];

		return self::send( $data );
	}

	/**
	 * @param $username
	 * @param $password
	 * @param $billno  流水号(唯一)
	 * @param $type    IN: 从网站账号转款到游戏账号; OUT: 從遊戲账號转款到網站賬號
	 * @param $credit  转款额度(如 000.00), 只保留小数点后两个位
	 * @param $actype  actype=1 代表真钱账号;  actype=0 代表试玩账号
	 * @param string $cur
	 * @param string $method
	 */
	public function transfer( $username, $password, $actype, $billno, $type, $credit, $cur = 'CNY', $method = 'tc' )
	{
		$data = [
			'loginname' => $username,
			'method' => $method,
			'actype' => $actype,
			'password' => $password,
			'cur' => $cur,
			'billno' => $billno,
			'type' => $type,
			'credit' => $credit
		];

		return self::send( $data );
	}

	/**
	 * 参数跟上面方法参数基本一致
	 * @param $username
	 * @param $password
	 * @param $actype
	 * @param $billno
	 * @param $type
	 * @param $credit
	 * @param $flag值 = 1 代表调用‘预备转账 PrepareTransferCredit’ API 成功 值 =0代表调用‘預備轉賬PrepareTransferCredit’出错或 出现错误码
	 * @param string $cur
	 * @param string $method
	 */
	public function confirm_transfer( $username, $password, $actype, $billno, $type, $credit, $flag, $cur = 'CNY', $method = 'tcc' )
	{
		$data = [
			'loginname' => $username,
			'method' => $method,
			'actype' => $actype,
			'password' => $password,
			'cur' => $cur,
			'billno' => $billno,
			'type' => $type,
			'credit' => $credit,
			'flag' => $flag
		];

		return self::send( $data );
	}

	/**
	 * 查询订单状态
	 * @param $billno
	 * @param $actype
	 * @param $cur
	 * @param string $method
	 */
	public function query_order_status( $billno, $actype, $cur = 'CNY', $method = 'qos' )
	{
		$data = [
			'method' => $method,
			'actype' => $actype,
			'cur' => $cur,
			'billno' => $billno,
		];

		return self::send( $data );
	}

	/**
	 * @param $username
	 * @param $password
	 * @param $actype
	 * @param $oddtype 盘口
	 * @param $dm ip地址
	 * @param $sid sid = (cagent+序列), 序列是唯一的 13~16 位数
	 * @param $lang 显示语言
	 * @param $game_type
	 * @param $mh5
	 * @param string $cur
	 */
	public function login( $username, $password, $actype, $oddtype, $dm, $mh5 = '', $game_type = '', $lang = 1, $cur = 'CNY' )
	{
		$number_hao = mt_rand( 1000000000000, 9999999999999999 );
		$data = [
			'loginname' => $username,
			'password' => $password,
			'actype' => $actype,
			'cur' => $cur,
			'oddtype' => $oddtype,
			'dm' => $dm,
			'sid' => self::AG_DEFAULT_CAGENT . $number_hao,
			'mh5' => $mh5,
			'gameType' => $game_type,
			'lang' => $lang
		];

		self::$is_gci = true;
		return self::send( $data );
	}
}