<?php

if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

class AccountApi extends BaseApi
{
	/**
	 * 会员存取款
	 * @param $usename
	 * @param $amount 为存取款金额，正数存款负数取款，请确保保留不超过3位小数，否则将收到错误码11
	 * @param $data 转账流水号
	 */
	public function transfer( $usename, $amount, $data )
	{
		$data = [
			'data' => $data,
			'member' => [ 'username' => $usename, 'amount' => $amount ]
		];

		return self::send( $data );
	}

	/**
	 * 确认存取款结果接口
	 * @param $data 转账流水号
	 * @return bool|mixed
	 * 该接口用于确认转账操作是否成功,
	 * codeId=0表示对应的流水号已经存在, 98表示该笔流水还未处理
	 */
	public function checkTransfer( $data )
	{
		$data = [
			'data' => $data
		];

		return self::send( $data );
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
	public function inform( $username, $amount, $data, $ticketId = 1 )
	{
		$data = [
			'ticketId' => $ticketId,
			'data' => $data,
			'member' => [ 'username' => $username, 'amount' => $amount ]
		];

		return self::send( $data );
	}

	/**
	 * 注单ID
	 * @param $ticketId
	 * @return bool|mixed
	 * 该接口用于对账查询
	 */
	public function order( $ticketId = 1 )
	{
		$data = [
			'ticketId' => $ticketId
		];

		return self::send( $data );
	}
}