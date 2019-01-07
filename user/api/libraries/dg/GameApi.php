<?php

if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

class GameApi extends BaseApi
{
	/**
	 * 修改会员限红组
	 * @param $username 目标限红组
	 * @param $data
	 */
	public function updateLimit( $username, $data )
	{
		$data = [
			'data' => $data,
			'member' => [ 'username' => $username ]
		];

		return self::send( $data );
	}

	/**
	 * 抓取注单
	 * @return bool|mixed
	 * 两次请求间隔最小为5秒钟,
	 * 单次查询最大数据量1000条,
	 * 抓取的单有可能有上次已经抓取过的抓单
	 */
	public function getReport( $data = [] )
	{
		return self::send( $data );
	}

	/**
	 * 标记已抓取注单
	 * @param array $list 待标记注单id集合
	 */
	public function markReport( array $list, $method = 'markReport' )
	{
		$data = [ 'list' => $list, 'method' => $method ];
		return self::send( $data );
	}

	/**
	 * 重新缓存注单
	 * @param $list ["起始日期", "结束日期"]
	 * @param $data 需要重新缓存的注单的ID
	 */
	public function initTickets( array $list, $data )
	{
		$data = [ 'list' => $list, 'data' => $data ];
		return self::send( $data );
	}
}