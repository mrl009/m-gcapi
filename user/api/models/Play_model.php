<?php

/**
 *  彩种玩法的赔率
 */
class Play_model extends MY_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->select_db('private');
    }

    public function get_s_k3_play_data($gid = 62,$tid = null)
    {
        try {
            $this->select_db('private');
            $fields = 'rate,rate_min,name,tid';
            $where = [
                'gid'=>$gid,
            ];
            if ($tid) {
                $where['tid'] = $tid;
            }
            $res = $this->db->select($fields)
                ->where($where)
                ->from('games_products')
                ->order_by('id','asc')
                ->get()
                ->result_array();
            return $res;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

}
