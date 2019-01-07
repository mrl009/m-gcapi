<?php

if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

class GameApi extends BaseApi
{
	public function get_bet_data($interval=5, $method = 'GetBetData' )
	{
		$data = [
			'method' => $method,
            'interval'=>$interval,
			'username' => ''
		];

		return self::send( $data );
	}

	public function mark_bet_data( $ticket, $method = 'MarkBetData' )
	{
		$data = [
			'method' => $method,
			'Ticket' => $ticket
		];

		return self::send( $data );
	}
}