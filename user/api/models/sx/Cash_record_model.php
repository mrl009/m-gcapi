<?php
//session_start();
if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

include_once FCPATH.'api/core/SX_Model.php';

class Cash_record_model extends SX_Model
{
	public function __construct()
	{

	}

	/**
	 * @param $trans_id 流水id
	 * @param $sn
	 * @param $username
	 * @param $﻿platform 平台名称
	 * @param $﻿cash_type ﻿(1是向平台打款，2用户从平台取款到本地）
	 * @param $﻿credit   转款的金额
	 * @param $uuid  全球唯一识别码 md5( username + trans_id )
	 */
	public function add_record( $﻿trans_id, $sn, $username, $﻿platform, $﻿cash_type, $﻿credit, $uuid )
	{
        $this->select_db('shixun_w');
		$data = [
			'trans_id' => $﻿trans_id,
			'sn' => $sn,
			'username' => $username,
			'platform' => $﻿platform,
			'cash_type' => $﻿cash_type,
			'credit' => $﻿credit,
			'create_time' => date( 'Y-m-d H:i:s' ),
			'status' => 0,
			'uuid' => $uuid,
		];

		return $this->db->insert( 'gc_cash_record', $data );
	}

	public function record_exists( $username, $﻿trans_id )
	{
        $this->select_db('shixun');
		return $this->db->where( [ 'username' => $username, 'trans_id' => $﻿trans_id ] )->from( 'gc_cash_record' )->count_all_results() > 0 ? true : false;
	}

	//修改充值状态
	public function update_record( $username, $﻿trans_id, $transaction_id, $info ='' )
	{
        $this->select_db('shixun_w');
		$update_data = [
			'transaction_id' => $transaction_id,
			'status' => 1,
			'info' => $info,
			'update_time' => date( 'Y-m-d H:i:s' )
		];

		return $this->db->where( [ 'username' => $username, 'trans_id' => $﻿trans_id ] )->update( 'gc_cash_record', $update_data );
	}

	//修改info
	public function update_info( $username, $﻿trans_id, $info ='' )
	{
        $this->select_db('shixun_w');
		$update_data = [
			'info' => $info,
                        'update_time' => date( 'Y-m-d H:i:s' )
		];

		return $this->db->where( [ 'username' => $username, 'trans_id' => $﻿trans_id ] )->update( 'gc_cash_record', $update_data );
	}

	public function get_order_number( $username, $﻿trans_id )
	{
        $this->select_db('shixun');
		return $this->db->select( 'transaction_id' )->where( [ 'username' => $username, 'trans_id' => $﻿trans_id ] )->get( 'gc_cash_record' )->row_array();
	}
}
