<?php
/**
 * @模块   demo
 * @版本   Version 1.0.0
 * @日期   2017-03-22
 * frank  所有使用都以demo为准
 */
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Agent_model extends MY_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->select_db('private');
    }

    function get_agent_list($basic, $senior, $page)
    {

        $this->select_db('private');

        $select = 'b.bank_name as name,
                    a.id as id,
                    a.user_id as user_id,
                    a.phone as phone,
                    a.email as email,
                    a.qq as qq,
                    a.status as status,
                    a.user_memo as user_memo,
                    a.memo as memo,
                    a.addtime as addtime,
                   ';

        $resu = $this->get_list($select, 'agent_review',
            $basic, $senior, $page);

        if($resu != Array()){
            foreach ($resu['rows'] as $key => $value){
                $user_info = $this->user_cache($value['user_id']);
                $resu['rows'][$key]['user_id'] = $user_info['username'];
                $resu['rows'][$key]['userid'] = $value['user_id'];

            }
        }

        return $resu;
    }

    function get_agent_detail($where)
    {
        $this->select_db('private');

        $arr = $this->get_one('*', 'agent_review', $where);
        if($arr != Array()){
            $where = [
                'uid' => $arr['user_id']
            ];

            $user_info = $this->user_cache($arr['user_id']);
            $arr['username'] = $user_info['username'];

            $resu = $this->get_one('*', 'user_detail', $where);
            if($resu != Array()){
                $arr['name'] = $resu['bank_name'];
            }else{
                $arr['name'] = null;
            }

            return $arr;
        }else{
            return null;
        }
    }

    public function update_user_type($user_id)
    {
        try {
            $data = array(
                'type' => 2,
            );

            $where = array(
                'id' => $user_id,
            );
            $this->write('user', $data, $where);

            $result = [
                'code' => 200,
                'msg' => '更新成功',
                'status' => 'OK'
            ];
            return $result;

        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function update_user_detail($user_id,$phone,$email,$qq)
    {
        try {
            $data = array(
                'phone' => $phone,
                'email' => $email,
                'qq' => $qq,
            );

            $where = array(
                'uid' => $user_id,
            );
            $this->write('user_detail', $data, $where);

            $result = [
                'code' => 200,
                'msg' => '更新成功',
                'status' => 'OK'
            ];
            return $result;

        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function agent_detail_update($id, $status, $memo)
    {
        try {
            $data = array(
                'status' => $status,
                'memo' => $memo,
            );

            $where = array(
                'id' => $id,
            );
            $this->write('agent_review', $data, $where);

            $result = [
                'code' => 200,
                'msg' => '更新成功',
                'status' => 'OK'
            ];

            return $result;

        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    function get_agent_sub_user_list($basic, $senior, $page)
    {
        $this->select_db('private');
        $select = 'id,username,balance,addtime';
        $resu = $this->get_list($select, 'user',
            $basic, $senior, $page);

        return $resu;
    }

    /**
     * 申请成为代理的玩家写入代理线表
     */
    public function create_top_agent_line($user_id)
    {
        //todo 建议在gc_set添加一个默认的返点
        $default_rebate = $this->get_gcset(['default_rebate']);
        $default_rebate = $default_rebate['default_rebate'];
        $default_rebate = empty($default_rebate) ? ['ssc'=>8.0,'k3'=>8.5,'11x5'=>7.5,'fc3d'=>7.5,'pl3'=>7.5,'pk10'=>8.0,'lhc'=>10.0] : json_decode($default_rebate,true);
        try {
            $data = array(
                'uid' => $user_id,
                'level' => 1,
                'type' => 2,
                'rebate' => json_encode($default_rebate),
                'line' => json_encode([$user_id => $default_rebate])
            );

            $this->write('agent_line', $data);

            $result = [
                'code' => 200,
            ];
            return $result;

        } catch (Exception $e) {
            return $e->getMessage();
        }
    }


    public function get_report_list($where,$page){
        $pageSize = $page['rows'];
        $start = $where['a.report_date >='];
        $end = $where['a.report_date <='];
        if ($pageSize < 50) {
            $pageSize = 50;
        }
        if ($pageSize > 500) {
            $pageSize = 500;
        }
        $offset = ($page['page']-1) * $pageSize;
        if ($offset < 0) {
            $offset = 0;
        }
        $rows = 1;
        if (!isset($where['b.username'])) {
            $rows = $this->db->select('COUNT(DISTINCT a.agent_id) as num')
                ->from('agent_report_day as a')
                ->join('user as b','b.id=a.agent_id','inner')
                ->where($where)
                ->get()->result_array();
            $rows = $rows[0]['num'];
        }
        $select = "SUM(a.register_num) AS register_num,SUM(a.first_charge_num) AS first_charge_num,SUM(a.bet_money) AS bet_money,SUM(a.prize_money) AS prize_money,SUM(a.gift_money) AS gift_money,SUM(a.team_rebates) AS team_rebates,SUM(a.charge_money) AS charge_money,SUM(a.withdraw_money) AS withdraw_money,SUM(a.team_profit) AS team_profit,SUM(a.agent_rebates) AS agent_rebates,b.username,b.id,'$start' as start,'$end' as end,c.level";
        $sql = $this->db->select($select)
            ->from('agent_report_day as a')
            ->join('user as b','b.id=a.agent_id','inner')
            ->join('agent_line as c','b.id=c.uid','left')
            ->where($where)
            ->group_by('a.agent_id')
            ->order_by($page['sort'],$page['order'])
            ->get_compiled_select();
        $res = $this->db->query($sql." LIMIT {$offset},{$pageSize}")->result_array();
        $data['total'] = $rows;
        $data['rows'] = $res;
        //.将层级转换成中文
        $this->level_to_ch($data['rows']);
        return $data;
    }


    /**
      *@param $data array 二维数组
      ***/
    public function level_to_ch(&$data)
    {
        if(!is_array($data)) return false;
        foreach ($data as $k => $row) {
            $data[$k]['level'] .= '级代理';
        }
    }
}