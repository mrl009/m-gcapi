<?php
/**
 * @模块   现金系统／现金流水model
 * @版本   Version 1.0.0
 * @日期   2017-03-30
 * shensiming
 */

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Cash_list_model extends MY_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->select_db('private');
    }




    /*******************公共方法********************/
    /**
     * 获取现金流水数据
     */
    public function get_cash_list($basic, $senior, $page)
    {
        $this->select_db('private');
        $select = 'a.id,
                    a.uid as user_id,
                    a.type as type_id,
                    a.amount as amount,
                    a.balance as balance,
                    a.remark as remark,
                    a.order_num as order_num,
                    a.addtime as addtime';
        $resu = $this->get_list($select, 'cash_list',
                             $basic, $senior, $page);


        $select = 'id, name, show_id as sid, cash_category as is_io';
        $resu['types'] = $this->get_list($select, 'cash_type');
        $resu['rows'] = $this->_id_to_name($resu['types'],
                                            $resu['rows']);
        array_unshift($resu['types'],
                        array('id'=>0,'name'=>'全部'));
        $resu['footer'] = $this->getFooter($resu);
        return $resu;
    }
    /********************************************/





    /*******************私有方法********************/
    /**
     * 将id转换为name
     */
    private function _id_to_name($types, $data)
    {
        $types = array_make_key($types, 'id');
        $cache['user_id'][0] = ['username'=>'-'];
        foreach ($data as $k => $d) {
            $data[$k]['addtime'] = date('Y-m-d H:i:s',
                                    $data[$k]['addtime']);
            $data[$k]['type'] = $types[$d['type_id']]['name'];
            $data[$k]['is_io'] = $types[$d['type_id']]['is_io'];

            $user_id = $d['user_id'];
            if (empty($cache['user_id'][$user_id])) {
                $user = $this->user_cache($user_id);
                $cache['user_id'][$user_id] = $user;
            }
            $agent_id = $cache['user_id'][$user_id]['agent_id'];
            if (empty($cache['user_id'][$agent_id])) {
                $user = $this->user_cache($agent_id);
                $cache['user_id'][$agent_id] = $user;
            }
            $data[$k]['user_name'] = $cache['user_id'][$user_id]['username'];
            $data[$k]['agent_name'] = $cache['user_id'][$agent_id]['username'];

            unset($data[$k]['type_id']);
        }
        return $data;
    }
    private function getFooter($data) {
        // 初始化返回数据
        $rs = [
            'amount'    => 0,
            'balance'   => 0
        ];
        if (empty($data['rows']) || !is_array($data['rows'])) {
            return $rs;
        }
        foreach ($data['rows'] as $v) {
            $rs['amount'] += isset($v['amount']) ? $v['amount'] : 0;
        }
        $rs['amount'] = (float)sprintf('%0.3f', $rs['amount']);
        return array($rs);
    }
    /********************************************/
}
