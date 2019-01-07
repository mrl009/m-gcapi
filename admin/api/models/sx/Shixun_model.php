<?php
/**
 * @模块   视讯
 * @版本   Version 1.0.0
 * @日期   2017-09-11
 * super
 */
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Shixun_model extends GC_Model
{
    public function __construct()
    {
        parent::__construct();
        //$this->select_db('shixun');
    }
    //获取视讯配置信息
    public function get_sx_set($tj = array())
    {
        $this->select_db('shixun');
        return $this->get_one($flids = '*','set',$tj);
    }
    //查询游戏注单详情
    public function find_game_order($game, $moon, $where, $condition, $page)
    {
        $this->select_db('shixun');
        return $this->get_list('*', $game . '_game_order' . $moon, $where, $condition, $page);
    }
    //获取用户信息
    public function find_user($tj){
        $this->select_db('shixun');
        return $this->get_one($flids = '*','dg_user',$tj);
    }
    public function find_plat_user($tj,$platform = 'ag'){
        $this->select_db('shixun');
        return $this->get_one($flids = '*',$platform.'_user',$tj);
    }
    public function all_find($field, $select, $table, $where, $condition, $page)
    {
        $this->select_db('shixun');
        $list = $this->get_list($field, $table, $where, $condition, $page);
        return $list;
//        $sum = $this->get_list($select, $table, $where, $condition, $page);
//        if (empty($sum['rows'][0])) {
//            $sum['rows'][0] = array();
//        }
//        $data['total'] = $list['total'];
//        $data['sum'] = $sum['rows'][0];
//        $data['rows'] = $list['rows'];
//        $data['sum'] = array_map(function ($v) {
//            return (float)$v;
//        }, $data['sum']);
//        return $data;
    }

    //查询视讯报表
    public function get_bet_report($platform = 'ag', $param)
    {
        $this->select_db('shixun');
        return $this->get_list($param['field'], $platform . '_bet_report', $param['where'], $param['condition'], $param['page']);
    }

    //查询视讯报表
    public function get_rebate_report($where)
    {
        $this->select_db('shixun');
        return $this->get_one('*','rebate_report', $where,[]);
    }
    //获取返水设置
    public function get_rebate_set($sn = '', $level = '')
    {
        $this->select_db('shixun');
        $condition = $level ? ['wherein' => array('level_id' => explode(',', $level))] : [];
        return $this->get_list('*', 'sx_rebate', ['sn' => $sn] , $condition);
    }

    /**
     * 新增一条返水报表数据
     * @param $params
     * @return int 自增ID
     */
    public function set_rebate_report($params)
    {
        $this->select_db('shixun_w');
        $this->write('rebate_report', $params);
        return $this->db->insert_id();
    }

    /**
     * 关联返水ID，修改返水状态
     * @param $uid
     * @param $time
     * @param $rebate_id
     * @param $auth_id
     * @param $is_fs
     */
    public function update_report_status($uid, $time, $rebate_id = 0, $auth_id = 0, $is_fs = 0)
    {
        $this->select_db('shixun_w');
        $data = [
            'is_fs' => $is_fs,
            'rebate_id' => $rebate_id,
            'auth_id' => $auth_id
        ];
        $where = [
            'snuid' => $uid,
            'bet_time' => $time
        ];
        // ag
        $this->db->update('ag_bet_report', $data, $where);
        // dg
        $this->db->update('dg_bet_report', $data, $where);
        // lebo
        $this->db->update('lebo_bet_report', $data, $where);
        // pt
        $this->db->update('pt_bet_report', $data, $where);
        //ky
        $this->db->update('ky_bet_report', $data, $where);
    }

    /**
     * 更新或删除报表数据
     * @param $rebate_id
     * @param $data
     * @param bool $is_delete
     * @param bool $is_cut
     */
    public function update_report_data($rebate_id, $data, $is_delete = false, $is_cut = true)
    {
        $this->select_db('shixun_w');
        if ($is_delete) {
            $this->delete('rebate_report', $rebate_id);
        } elseif ($is_cut) {
            $this->db->set('sum', 'sum-' . count($data), FALSE);
            $this->db->set('total', 'total-' . array_sum(array_column($data, 'total')), FALSE);
            $this->db->where(['id' => $rebate_id])->update('rebate_report');
        } else {
            $param = [
                'sum' => count($data),
                'total' => array_sum(array_column($data, 'rebate_total'))
            ];
            $this->write('rebate_report', $param, ['id' => $rebate_id]);
        }
    }

    /**
     * 获取视讯报表列表
     * @param $where
     * @param $page
     * @return array|mixed
     */
    public function get_rebate_report_list($where, $page)
    {
        $this->select_db('shixun');
        return $this->get_list('*', 'rebate_report', $where, [], $page);
    }
    //专门修改5号库sx配置
    public function set_sx_set($key,$value)
    {
        $this->redis_select(5);
        $keys = 'sx:'.$key;
        $rs=$this->redis_set($keys, json_encode($value));
        return $rs;
    }
    /*视讯credit修改*/
    public function update_credit($sx_total_limit,$price,$sn)
    {
        $sn=$sn?$sn:$this->_sn;
        $this->select_db('shixun_w');
        $result=$this->update_sx_set('credit',$sx_total_limit+$price,0);
        /*写入额度转换记录表*/
        $data['billno']=$sn.'_'.date('Y-m-d H:i:s',time());
        $data['sn']=$sn;
        //$data['platform']=$platform;
        $data['user']=$this->user['username'];
        $data['type']=$price>0?1:2;
        $data['credit']=$price;
        $data['after_credit']=$sx_total_limit+$price;
        $data['creater']=$this->user['username'];
        $data['remark']='后台调整';
        $rs=$this->write('credit_record',$data);
    }

    //获得会员信息
    public function user_info( $username, $field = '*', $platform = 'ag' )
    {
        $this->select_db('shixun');
        return $this->db->select( $field )->where( 'g_username', $username )->get( $platform . '_user' )->row_array();
    }

    //更新用户余额
    public function update_balance( $username, $balance, $platform_name )
    {
        $this->select_db('shixun_w');
        //var_dump($this->db);exit();
        return $this->db->where( 'g_username', $username )->update( $platform_name . '_user', [ 'balance' => $balance ] );
    }


}
