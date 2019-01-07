<?php

//session_start();
if (!defined('BASEPATH'))
{
	exit('No direct script access allowed');
}

include_once FCPATH.'api/core/SX_Model.php';

class game_order_model extends SX_Model
{
	public function inset_order( $data )
	{
		$this->select_db('shixun');
		$this->load->model( 'sx/user_model' );
		$this->load->model( 'sx/Bet_report_model', 'report' );
		$table = 'pt_game_order' . date( 'm' );
		foreach ( $data as $val )
		{
			$allow_add_report = false;
			$insert = [];
			$insert[ 'game_code' ] = $val[ 'GameCode' ];
			$allow_add_report = $this->db->where( 'game_code', $insert[ 'game_code' ] )->from( $table )->count_all_results() == 0 ? true : false;

			$insert[ 'username' ] = strtolower( $val[ 'PlayerName' ] );
			$user = $this->user_model->get_user_info( $insert[ 'username' ], 'pt' );
			$insert[ 'sn' ] = $user[ 'sn' ] ?? '';
			$insert[ 'snuid' ] = $user[ 'snuid' ] ?? 0;
			$insert[ 'game_id' ] = $val[ 'GameId' ];
			$insert[ 'game_type' ] = $val[ 'GameType' ];
			$insert[ 'game_name' ] = $val[ 'GameName' ];
			$insert[ 'session_id' ] = $val[ 'SessionId' ];
			$insert[ 'bet' ] = $val[ 'Bet' ];
			$insert[ 'win' ] = $val[ 'Win' ];
			$insert[ 'progressive_bet' ] = $val[ 'ProgressiveBet' ];
			$insert[ 'progressive_win' ] = $val[ 'ProgressiveWin' ];
			$insert[ 'balance' ] = $val[ 'Balance' ];
			$insert[ 'current_bet' ] = $val[ 'CurrentBet' ];
			$insert[ 'game_date' ] = $val[ 'GameDate' ];
			$insert[ 'live_network' ] = $val[ 'LiveNetwork' ];
			$insert[ 'update_time' ] = date( 'Y-m-d H:i:s' );
			$insert[ 'window_code' ] = $val[ 'WindowCode' ];

			if( $allow_add_report )
			{
				$this->db->insert( $table, $insert );
				$this->report->pt_day_report( $insert );
			}
		}
	}
}