<?php
/**
 * @模块   现金系统／线上入款model
 * @版本   Version 1.0.0
 * @日期   2017-03-30
 * shensiming
 */

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class In_online_model extends MY_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->select_db('private');
    }



    /******************公共方法*******************/
    /**
     * 获取数据列表
     */
    public function get_in_online($basic, $senior, $page)
    {
        // 获取汇总数据
        $select = 'sum(a.price) as price, 
                    count(*) as in_online_num,
                    sum(a.discount_price) as discount_price,
                    sum(a.total_price) as total_price';
        $footer = $this->get_list($select, 'cash_in_online',
                            $basic, $senior);
        foreach ($footer[0] as $key => $value) {
            $footer[0][$key] = floatval($value);
        }
        // 获取数据列表
        $select = 'a.id,b.username as admin_name,a.uid as user_id,
                    a.order_num as order_num,
                    a.admin_id as admin_id,
                    a.pay_id as pay_id,
                    a.price as price,
                    a.total_price as total_price,
                    a.discount_price as discount_price,
                    a.status as status,
                    a.is_first as is_first,
                    a.addtime as addtime,
                    a.update_time as update_time,
                    a.is_discount as is_discount,
                    a.from_way as from_way,
                    a.remark as remark,
                    a.pay_code as pay_code,
                    a.online_id as spay_id,
                    a.pay_serve_type as st,
                    a.agent_id as agent_id';
        $resu = $this->get_list($select, 'cash_in_online',
                                $basic, $senior, $page);
        $resu['footer'] = $footer[0];


        // 加入入款商户列表并且把id转换为name
        $resu['rows'] = $this->_id_to_name($resu['rows']);
        foreach ($resu['rows'] as $k => $v) {
            if (empty($v['update_time'])) {
                $resu['rows'][$k]['update_time'] = '-';
            } else {
                $resu['rows'][$k]['update_time'] =
                        date('Y-m-d H:i:s', $v['update_time']);
            }
            $resu['rows'][$k]['addtime'] =
                        date('Y-m-d H:i:s', $v['addtime']);
            $resu['rows'][$k]['pay_name'] .= code_pay($resu['rows'][$k]['pay_code']);
        }
        return $resu;
    }

    /**
     * 根据订获取用支付的信息
     * cash_in_online
     */
    public function order_detail($id)
    {
        $data = $this->db->select('a.*,b.pay_key,c.level_id,c.username,c.max_income_price,max_out_price')
            ->from('cash_in_online as a')
            ->join('bank_online_pay as b', 'b.id = a.online_id', 'left')
            ->join('user as c', 'a.uid = c.id', 'left')
            ->where(['a.id'=> $id])
            ->where_in('a.status', [1,4])
            ->limit(1)
            ->get()
            ->row_array();
        return $data;
    }
    /********************************************/





    /*******************私有方法********************/
    /**
     * 获取某个表的全部数据
     */
    public function _table_list($select, $table, $db = 'private',
        $where = array(), $condition = array())
    {
        $this->select_db($db);
        $res = $this->get_list($select, $table, $where, $condition);
        return $res;
    }


    /**
     * 将层级id，管理员id，支付id等转换为名称
     *
     * @access private
     * @param Array $data   数据数组
     * @return $data        转换后结果
     */
    private function _id_to_name($data)
    {
        if (empty($data)) return $data;

        /* 新增快速直通车支付信息 */
        $fast_arr = $this->get_list('id,platform_name as pay_name','bank_fast_pay');
        $fast_arr = array_make_key($fast_arr, 'id');
        $fast_arr[0] = ['pay_name'=>'-','id'=>'-'];

        $this->select_db('public');
        $pay_arr = $this->get_list('id, online_bank_name as pay_name','bank_online');
        $pay_arr = array_make_key($pay_arr, 'id');
        $pay_arr[0] = ['pay_name'=>'-','id'=>'-'];

        // 初始化0的值
        $cache['pay_id'][0] = '-';
        $cache['leve_id'][0] = '-';
        $cache['user_id'][0] = ['username'=>'-'];
        foreach ($data as $k => $v) {
            $user_id = $v['user_id'];
            $pay_id = $v['pay_id'];
            $agent_id = $v['agent_id'];

            if (empty($cache['user_id'][$user_id])) {
                $user = $this->user_cache($user_id);
                $cache['user_id'][$user_id] = $user;
            }
            $leve_id = $cache['user_id'][$user_id]['level_id'];
            $v['level_id'] = $leve_id;

            if (empty($cache['user_id'][$agent_id])) {
                $user = $this->user_cache($agent_id);
                $cache['user_id'][$agent_id] = $user;
            }

            if (empty($cache['leve_id'][$leve_id])) {
                $leve = $this->level_cache($leve_id);
                $cache['leve_id'][$leve_id] = $leve;
            }

            if (empty($v['admin_name'])) {
                $v['admin_name'] = '-';
            }

            if (!empty($cache['leve_id'][$leve_id])) {
                $v['leve_name'] = $cache['leve_id'][$leve_id];
            } else {
                $v['leve_name'] = '-';
            }
            
            /* 快速直通车支付名称 */
            $v['pay_name'] = '-';
            if ('fast' == $v['st'])
            {
                if ($fast_arr[$v['spay_id']]['pay_name'] && 
                    !empty($fast_arr[$v['spay_id']]['id']))
                {
                    $tid = $fast_arr[$v['spay_id']]['id'];
                    $tn = $fast_arr[$v['spay_id']]['pay_name'];
                    $v['pay_name'] = "{$tn}(ID:$tid)";
                }
            } else {
                if ($pay_arr[$pay_id]['pay_name'] && 
                    !empty($pay_arr[$pay_id]['id']))
                {
                    $tid = $pay_arr[$pay_id]['id'];
                    $tn = $pay_arr[$pay_id]['pay_name'];
                    $v['pay_name'] = "{$tn}(ID:$tid)";
                }
            }
            
            if (!empty($cache['user_id'][$user_id]['username'])) {
                $v['user_name'] = $cache['user_id'][$user_id]['username'];
            } else {
                $v['user_name'] = '-';
            }
            
            if (!empty($cache['user_id'][$agent_id]['username'])) {
                $v['agent_name'] = $cache['user_id'][$agent_id]['username'];
            } else {
                $v['agent_name'] = '-';
            }
            
            $data[$k] = $v;
        }
        return $data;
    }
    /********************************************/
}
