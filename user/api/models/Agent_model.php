<?php

/**
 * Created by PhpStorm.
 * User: dragon
 * Date: 2017/5/22
 * Time: 14:54
 */
class Agent_model extends MY_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->select_db('private');
    }

    public function create_agent_data($user_id, $phone, $email, $qq, $user_memo)
    {
        $user_info = $this->user_cache($user_id);

        try {
            $this->select_db('private');
            $data = [
                'user_id' => $user_id,
                'name' => $user_info['username'],
                'phone' => $phone,
                'email' => $email,
                'qq' => $qq,
                'addtime' => time(),
                'user_memo' => $user_memo,
                'status' => 1,
            ];

            $where = array();
            $this->write('agent_review', $data, $where);

            return ['code' => 200];

        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function check_agent_register($user_id)
    {
        try {
            $this->select_db('private');

            $where = [
                'id' => $user_id,
                'type' => 2,
            ];
            $data = $this->get_one('*', 'user', $where);
            return $data;

        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function check_agent_review_register($user_id)
    {
        try {
            $this->select_db('private');

            $where = [
                'user_id' => $user_id,
            ];
            $data = $this->get_one('*', 'agent_review', $where);
            return $data;

        } catch (Exception $e) {
            return $e->getMessage();
        }
    }


    public function update_agent_review_status($user_id, $phone, $email, $qq, $user_memo, $status)
    {
        try {
            $this->select_db('private');

            $where = [
                'user_id' => $user_id,
            ];

            $data = [
                'status' => $status,
                'phone' => $phone,
                'email' => $email,
                'qq' => $qq,
                'user_memo' => $user_memo,
                'addtime' => time(),
            ];
            $this->write('agent_review', $data, $where);
            return true;

        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function check_paramter($phone, $email, $qq)
    {
        try {
            $this->select_db('private');

            if ($phone != Array()) {
                $data = $this->get_one('*', 'agent_review', ['phone' => $phone]);
                if ($data != null) {
                    return [
                        'code' => 101,
                        'msg' => '该电话号码已存在',
                    ];
                }
            }

            if ($email != Array()) {
                $data = $this->get_one('*', 'agent_review', ['email' => $email]);
                if ($data != null) {
                    return [
                        'code' => 102,
                        'msg' => '该邮箱已存在',
                    ];
                }
            }

            if ($qq != Array()) {
                $data = $this->get_one('*', 'agent_review', ['qq' => $qq]);
                if ($data != null) {
                    return [
                        'code' => 103,
                        'msg' => '该QQ或微信号码已存在',
                    ];
                }
            }

            return [
                'code' => 200,
                'msg' => '成功',
            ];


        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    /*
     * 获取充值记录列表
     */
    public function get_charge_list($where, $condition)
    {
        //gc_cash_in_online
        $fields1 = 'a.uid,a.addtime,a.price,a.total_price,a.status,"线上入款" type,b.username';
        $table1 = 'cash_in_online as a';
        $type = (int)$this->P('type');
        if ( $type !== 0 ){
            $where['a.status'] = $type;
        }
        $this->db->from($table1)
            ->join('user as b','b.id=a.uid','inner')
            ->select($fields1)->where($where);
        if (isset($condition['wherein'])){
            foreach ($condition['wherein'] as $key => $val){
                $this->db->where_in($key,$val);
            }
        }
        $sql1 = $this->db->order_by('a.addtime','desc')->get_compiled_select();
        //gc_cash_in_company
        $fields2 = 'a.uid,a.addtime,a.price,a.total_price,a.status,"公司入款" type,b.username';
        $table2 = 'cash_in_company as a';
        $this->db->reset_query();
        $this->db->from($table2)
            ->join('user as b','b.id=a.uid','inner')
            ->select($fields2)->where($where);
        if (isset($condition['wherein'])){
            foreach ($condition['wherein'] as $key => $val){
                $this->db->where_in($key,$val);
            }
        }
        $sql2 = $this->db->order_by('a.addtime','desc')->get_compiled_select();
        if ($type && $type!=2) {
            $sql = "SELECT * FROM ((".$sql1 .") UNION ALL (".$sql2.")) as tb ORDER BY tb.addtime DESC LIMIT ".$condition['page_limit'][0]." OFFSET ".$condition['page_limit'][1];
        } else {
            //gc_cash_in_people
            $fields3 = 'a.uid,a.addtime,a.price,(a.price+a.discount_price) as total_price,2 status,"人工入款" type,b.username';
            $table3 = 'cash_in_people as a';
            $where3 = $where;
            unset($where3['a.status']);
            $this->db->reset_query();
            $this->db->from($table3)
                ->join('user as b','b.id=a.uid','inner')
                ->select($fields3)->where('a.type=1')->where($where3);
            if (isset($condition['wherein'])){
                foreach ($condition['wherein'] as $key => $val){
                    $this->db->where_in($key,$val);
                }
            }
            $sql3 = $this->db->order_by('a.addtime','desc')->get_compiled_select();
            $sql = "SELECT * FROM ((".$sql1 .") UNION ALL (".$sql2.") UNION ALL  (".$sql3.")) as tb ORDER BY tb.addtime DESC LIMIT ".$condition['page_limit'][0]." OFFSET ".$condition['page_limit'][1];
        }
        $query = $this->db->query($sql);
        if ($query) {
            $list = $query->result_array();
        } else {
            return false;
        }
        return $list;
    }

    /*
     * 获取提现记录列表
     */
    public function get_withdraw_list($where, $condition)
    {

        $fields = "(CASE a.out_type WHEN 1 THEN b.bank_id WHEN 2 THEN '支付宝' WHEN 3 THEN '微信' ELSE null END) AS bank,(CASE a.out_type WHEN 1 THEN b.bank_num WHEN 2 THEN b.alipay WHEN 3 THEN b.wechat ELSE null END) AS bank_num,(CASE a.`status` WHEN 4 THEN 1 WHEN 5 THEN 3 ELSE a.`status` END) AS `status`,a.price,a.addtime,c.username,b.bank_name as real_name";
        $table = 'cash_out_manage as a';
        $list = $this -> _query_withdraw_list($fields,$table,$where,$condition);
        $this->select_db('public');
        $banks = $this->get_list('id,bank_name','bank');
        $banks = array_column($banks,'bank_name','id');
        foreach ($list as &$item){
            if (isset($banks[$item['bank']])){
                $item['bank'] = $banks[$item['bank']];
            }
        }
//        $this->select_db('private');
//        $field1 = "'' as bank,'' as bank_num,a.price,a.addtime,2 status,c.username,b.bank_name as real_name";
//        $table1 = 'cash_out_people as a';
//        $list1 = $this -> _query_withdraw_list($field1,$table1,$where,$condition);
//        $result = array_merge($list,$list1);
        return $list;
    }

    public function _query_withdraw_list($fields,$table,$where,$condition)
    {
        // 状态，1：未确认，2：已确认，3：拒绝，4：预备出款，5：取消
        $type = (int)$this->P('type');
        if ( $type !== 0 ) {
            switch ($type) {
                case 1:
                    $condition['wherein']['a.status'] = [1,4];
                    break;
                case 2:
                    $condition['wherein']['a.status'] = [2];
                    break;
                case 3:
                    $condition['wherein']['a.status'] = [3,5];
                    break;
                default:
                    break;
            }
        }
        $this->db->from($table)
            ->join('user_detail as b','b.uid=a.uid','inner')
            ->join('user as c','c.id=a.uid','inner')
            ->select($fields)->where($where);
        if (isset($condition['wherein'])){
            foreach ($condition['wherein'] as $key => $val){
                $this->db->where_in($key,$val);
            }
        }
        $this->db->order_by('a.addtime','desc');
        $this->db->limit($condition['page_limit'][0])->offset($condition['page_limit'][1]);
        $query = $this->db->get();
        if ($query) {
            return $query->result_array();
        } else {
            return false;
        }
    }

}
