<?php
/**
 * @模块   会员中心／账户明细model
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

    /******************公共方法*******************/
    /**
     * 获取现金流水数据
     */
    public function get_cash_list($basic, $senior, $page)
    {
        $select = 'a.amount, a.addtime, a.uid, b.name, a.order_num';
        $table = 'cash_list';
        $data = $this->get_list($select, $table,
                        $basic, $senior, $page);
        unset($data['total']);
        foreach ($data['rows'] as $k => $v) {
            if (isset($v['order_num']{18})) {
                $data['rows'][$k]['order_num'] = substr($v['order_num'], 0,18).'..';
            }
            $data['rows'][$k]['addtime'] = date('Y-m-d H:i:s', $data['rows'][$k]['addtime']);
            $data['rows'][$k]['amount'] = (float)sprintf("%.3f", $data['rows'][$k]['amount']);
            if (!strcasecmp($v['name'], '公司入款无优惠')) {
                $data['rows'][$k]['name'] = '公司入款无优惠';
            } elseif (!strcasecmp($v['name'], '线上入款不含优惠')) {
                $data['rows'][$k]['name'] = '线上入款无优惠';
            } elseif (!strcasecmp($v['name'], '公司入款')) {
                $data['rows'][$k]['name'] = '银行转账';
            } elseif (!strcasecmp($v['name'], '优惠卡入款')) {
                $data['rows'][$k]['name'] = '彩豆充值';
            } elseif (!strcasecmp($v['name'], '人工取出')) {
                $data['rows'][$k]['name'] = '其他扣款方式';
            }
            $data['rows'][$k]['username'] = $this->get_agent_name($v['uid']);
        }
        return $data;
    }

    /**
     * 每查询一次线上入款
     * 将状态=1并且addtime<30分钟之后 更新为 status=3
     * @param $type 1 为更新公司入款 2 更新线上入款
     * @param $uid   用户id
     */
    public function update_status($type, $uid)
    {
        $tb = array('','gc_cash_in_company','gc_cash_in_online');
        if ($type == 1) {
            $gcSet = $this->get_gcset();
            $time = $gcSet['incompany_timeout']*60;
        } else {
            $time = CASH_AUTO_EXPIRATION*60;
        }
        $sql = 'UPDATE '.$tb[$type].' set status=3 WHERE addtime < unix_timestamp(now())-'.$time.' AND status=1 AND uid ='.$uid;
        $this->db->query($sql);
    }

    /**
     * 获取选项列表
     */
    public function get_opts()
    {
        $select = 'name as label, show_id as value';
        $resu['rows'] = $this->get_list($select, 'cash_type');
        return $resu;
    }
    /**
     * 根据代理id获取代理名：用redis
     * @param $id
     * @return string
     */
    private function get_agent_name($id)
    {
        $r = $this->core->user_cache($id);
        return isset($r['username']) ? $r['username'] : '';
    }
    /*******************************************/
}
