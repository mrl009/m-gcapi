<?php
//session_start();
if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

include_once FCPATH.'api/core/SX_Model.php';

class Fund_model extends SX_Model {

	public function fund_write( $username, $type, $amount, $balance, $platform_name, $sn, $transfer_id  )
	{
		$this->select_db('shixun_w');
		return $this->db->insert( $platform_name . '_fund', [ 'sn' => $sn, 'transfer_id' => $transfer_id, 'gc_username' => $username, 'type' => $type, 'amount' => $amount, 'add_time' => time(), 'free_balance' => $balance ] );
	}
}