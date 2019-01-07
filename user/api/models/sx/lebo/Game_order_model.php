<?php

//session_start();
if (!defined('BASEPATH'))
{
	exit('No direct script access allowed');
}

include_once FCPATH.'api/core/SX_Model.php';

class game_order_model extends SX_Model
{
	public function inset_order( $data ,$sn)
	{
		$this->select_db('shixun');
		$tabel_name = 'lebo_game_order' . date( 'm' );
		$sql = '';
		$update_time = date( 'Y-m-d H:i:s' );
		$list_id = [];
		$this->load->model( 'sx/user_model' );
		$this->load->model( 'sx/bet_report_model' );
		foreach ( $data['data'] as $k => $value )
		{
            $this->select_db('shixun');
            $count=$this->db->where( 'round_no', $value['round_no'] )->from( $tabel_name )->count_all_results();
			$list_id[] = $value['game_id'];
			$user = $this->user_model->get_user_info( $value['user_name'], 'lebo' );
            $value['sn']=$user['sn'];
            $value['snuid']=$user['snuid'];
			if( !$count )
			{
                $this->select_db('shixun_w');
                $rs=$this->write($tabel_name,$value);
                $this->select_db('shixun');
				$this->bet_report_model->lebo_day_report( $value );
                //统计用户打码量
                $this->redis_select(REDIS_LONG);
                $this->redis_hincrbyfloat('user:dml', $value['snuid'], $value['valid_bet_score_total']);
			}
		}
		return $list_id;
	}
}