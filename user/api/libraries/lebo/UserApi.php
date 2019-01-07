<?php

if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

class UserApi extends BaseApi
{
	/**
     * 登录
	 * @param $username
	 * @param $game_type
	 * @param string $currency
	 * @param string $method
	 * @param string $lang
	 * @return bool|mixed
	 */
	public function login( $username, $game_type,$act_type='',$currency = 'RMB', $method = 'UserLogin', $lang = 'zh-cn' )
    {
        $data = [
            'method' => $method,
            'username' => $username,
            'Lang' => $lang,
            'Viewname' => $username,
            'Currency' => $currency,
            'token' => '',
            'GameType' => $game_type=='pc'?1:2,
            'act_type' => $act_type,
        ];

        return self::send( $data );
    }

	/**
     * 获取会员信息
	 * @param $username
	 * @param string $method
	 * @return bool|mixed
	 */
    public function user_detail( $username, $method = 'UserDetail' )
    {
        $data = [
			'method' => $method,
			'username' => $username,
        ];

		return self::send( $data );
    }

	/**
     * 会员存款
	 * @param $username
	 * @param $amount
	 * @param string $method
	 * @return bool|mixed
	 */
    public function deposit( $username, $amount, $method = 'Deposit' )
    {
        $data = [
			'method' => $method,
			'username' => $username,
            'amount' => $amount
        ];

		return self::send( $data );
    }

	/**
	 * @param $username
	 * @param $amount
	 * @param string $method
	 * @return bool|mixed
	 */
    public function with_drawal( $username, $amount, $method = 'WithDrawal' )
    {
		$data = [
			'method' => $method,
			'username' => $username,
			'amount' => $amount
		];

		return self::send( $data );
    }

	/**
     * 查询存取款状态
	 * @param $username
	 * @param $order_num
	 * @param string $method
	 * @return bool|mixed
	 */
    public function transfer_status( $username, $order_num, $method = 'TransferLog' )
    {
		$data = [
			'method' => $method,
			'username' => $username,
			'serial' => $order_num
		];
		return self::send( $data );
    }

    /*
     *获取结算、撤销注单
     *@param $username
     *@return bool|mixed
     */
    public function get_bet_data( $username = '', $method = 'GetBetData' )
    {
        date_default_timezone_set('America/New_York');
        $data = [
            'method'   => $method,
            'username' => $username,
            'start_date'=>date('Y-m-d',time()-3600),
            'end_date'=>date('Y-m-d',time()),
        ];
        return self::send( $data );
    }

    /*
     *此功能用来标记接入商已收到的注单。接入商在收到注单，并成功处理后，请务必调用该接口对注单进行标记，避免相同注单。最大处理100条
     *@param $username
     *@return bool|mixed
     */
    public function mark_bet_data(array $tickets, $method = 'MarkBetData' )
    {
        $data = [
            'method'  => $method
        ];
        foreach ( $tickets as $val )
        {
            $data[] = $val;
        }
        return self::send( $data );
    }
    public function bet_record( $stime, $etime, $username = '', $method = 'BetRecord' )
    {
        $data = [
            'method'  => $method,
            'username'=> $username,
            'stime'   => $stime,
            'etime'   => $etime
        ];
        return self::send( $data );

    }
}