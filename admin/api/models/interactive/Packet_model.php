<?php

class Packet_model extends MY_Model
{

    /**f
     * 构造函数
     */
    public function __construct()
    {
        parent::__construct();
    }

    public function get_packet_list($field,$where,$params){
        $packet_list = $this->db->select($field)
            ->from('user a')
            ->join('user_detail b','b.uid = a.id')
            ->join('red_packet c','c.uid = a.id')
            ->where($where)
            ->order_by($params['sort'],$params['order'])
            ->limit($params['rows'],$params['offset'])
            ->get()
            ->result_array();
        return $packet_list;
    }

    public function get_packet_detail($id,$params){
        $field = 'b.id,b.uid,b.money,b.addtime';
        $where['b.rid'] = $id;
        $result=$this->db->select($field)
            ->from('red_packet a')
            ->join('red_packet_get b','a.id = b.rid')
            ->where($where)
            ->order_by($params['sort'],$params['order'])
            ->limit($params['rows'],$params['offset'])
            ->get()
            ->result_array();

        $field2 = 'a.username,b.nickname';
        $i = 1;
        foreach ($result as $key => $v){
            $userinfo = $this->db->select($field2)->from('user a')
                ->join('user_detail b','a.id=b.uid')
                ->where('a.id = '.$v['uid'])->get()->row_array();
            $v['id'] = $i;
            $v['addtime'] = date('Y-m-d H:i:s',$v['addtime']);
            $result[$key] = array_merge($v,$userinfo);
            $i++;
        }
        return $result;
    }

    public function get_statistics_list($where,$params){
        $field = 'a.id,a.username,a.addtime,b.nickname,SUM(packet_in) packet_in,SUM(packet_out) packet_out,SUM(packet_refund) packet_refund,(packet_in+packet_refund-packet_out) packet_profit';
        $sql = $this->db->select($field)
                        ->from('user a')
                        ->join('user_detail b','b.uid=a.id')
                        ->join('red_packet_report c','c.uid = a.id','left')
                        ->where($where)
                        ->group_by('a.id')
                        ->order_by($params['sort'],$params['order'])
                        ->get_compiled_select();
        $sql1 = $sql." LIMIT {$params['offset']},{$params['rows']}";
        $list = $this->db->query($sql1)->result_array();
        $sql2 = 'SELECT COUNT(id) total_rows FROM ('.$sql.') as total_table';
        $total = $this->db->query($sql2)->row_array();

        $result['rows'] = $list;
        $result['total'] = $total['total_rows'];

        return $result;

    }
}
