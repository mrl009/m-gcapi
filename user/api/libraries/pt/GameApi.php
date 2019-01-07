<?php

if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

class GameApi extends BaseApi
{
	public function get_report( $startdate, $enddate, $page = 1, $pagesize = 50, $currency = 'CNY', $producttype = 0, $method = 'get:report/GetBetLog' )
	{
		$data = [
			'startdate' => $startdate,
			'enddate' => $enddate,
			'page' => $page,
			'pagesize' => $pagesize,
			'producttype' => $producttype,
			'currency' => $currency,
			'method' => $method,
		];

		return self::send( $data );
	}
}