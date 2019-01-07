<?php
//session_start();
if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

include_once FCPATH.'api/core/SX_Model.php';

class Set_model extends SX_Model {

	public function __construct()
	{
		$this->select_db('shixun');
	}

	public function get_sn_key( $platform_name, $sn )
	{
		return $this->db->select( $platform_name . '_agentname,' . $platform_name . '_agentpwd' )->where( 'sn', $sn )->get( 'gc_set' )->row_array();
	}

    public function get_ky_key(  $sn )
    {
        return $this->db->select(  'ky_agentname,ky_deskey,ky_md5_key' )->where( 'sn', $sn )->get( 'gc_set' )->row_array();
    }
	public function get_all_site()
	{
		return $this->db->select( 'sn' )->get( 'gc_set' )->result_array();
	}
	public function get_sx_type($typeFixed=array())
	{
		$rKPidData = 'sys:data:sx';
		$data = $this->redis_hgetall($rKPidData);
		if(empty($data)){
			$pData = $this->db->get( 'sx_type' )->result_array();
			foreach ($pData as $d) {
				$type = $d['type'];
				$this->redis_hset($rKPidData,$type,json_encode($d));
			}
			$data = $this->redis_hgetall($rKPidData);
		}
		$res = array();
		foreach ($data as $type => $d) {
			if(is_numeric($typeFixed)){
				if($typeFixed==$type)return json_decode($d,true);
			}
			else{
				if(empty($typeFixed) || in_array($type, $typeFixed)){
					$res[$type] = json_decode($d,true);
				}
			}
		}
		return $res;
	}

}