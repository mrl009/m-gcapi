<?php
/**
 * @模块   会员分析／出入款分析model
 * @模块   会员分析／给予优惠model
 * @模块   现金系统／出入汇总model
 * @版本   Version 1.0.0
 * @日期   2017-03-30
 * shensiming
 */


if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Report_model extends MY_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->select_db('private');
    }


    /******************公共方法*******************/
    /**
     * 获取列表数据
     * 会员分析／出入款分析
     *
     * @access public
     * @param Array $basic 查询条件
     * @param Array $senior 复杂条件
     * @param Array $page 页数条件
     * @return Array
     */
    public function get_in_out($basic, $senior, $page)
    {
        $select = 'a.uid as id,a.uid as user_id,a.report_date,a.agent_id,
                    sum(a.in_company_total) as in_company_total,
                    sum(a.in_company_discount) as in_company_discount,
                    sum(a.in_company_discount_num) as in_company_discount_num,
                    sum(a.in_company_num) as in_company_num,

                    sum(a.in_online_total) as in_online_total,
                    sum(a.in_online_discount) as in_online_discount,
                    sum(a.in_online_discount_num) as in_online_discount_num,
                    sum(a.in_online_num) as in_online_num,

                    sum(a.in_people_total) as in_people_total,
                    sum(a.in_people_discount) as in_people_discount,
                    sum(a.in_people_discount_num) as in_people_discount_num,
                    sum(a.in_people_num) as in_people_num,

                    sum(a.in_card_total) as in_card_total,
                    sum(a.in_card_num) as in_card_num,

                    sum(a.out_people_total) as out_people_total,
                    sum(a.out_people_num) as out_people_num,

                    sum(a.out_company_total) as out_company_total,
                    sum(a.out_company_num) as out_company_num,

                    sum(a.activity_num) as activity_num,
                    sum(a.activity_total) as activity_total,
                    count(if(a.in_register_discount>0,"1",null)) as discount_num,
                    
                    sum(in_register_discount) as in_register_discount,
                     b.username as agent_user_name';
        $resu = $this->get_list($select, 'cash_report',
                                $basic, $senior, $page);

        /*** 把id转换为name ***/
        $resu['rows'] = $this->_id_to_name($resu['rows']);
        /*** 添加会员上级代理 ***/
        /*** 格式化出款数据 ***/
        $resu = $this->_format_data($resu);

        return $resu;
    }

    /**
     * 获取列表数据
     * 出入汇总／给予优惠跳转
     *
     * @access public
     * @param Array $basic 查询条件
     * @param Array $senior 复杂条件
     * @param Array $page 页数条件
     * @return Array
     */
    public function get_preferential($basic, $senior, $page)
    {
        $select = 'a.uid as user_id,a.report_date,
                    sum(a.in_company_discount) as in_company_discount,
                    sum(a.in_company_discount_num) as in_company_num,

                    sum(a.in_online_discount) as in_online_discount,
                    sum(a.in_online_discount_num) as in_online_num,

                    sum(a.in_people_discount) as in_people_discount,
                    sum(a.in_people_discount_num) as in_people_num,

                    sum(a.in_card_total) as in_card_total,
                    sum(a.in_card_num) as in_card_num,

                    sum(a.out_people_total) as out_people_total,
                    sum(a.out_people_num) as out_people_num,

                    sum(in_register_discount) as in_register_discount';
        $resu = $this->get_list($select, 'cash_report',
                                $basic, $senior, $page);

        /*** 把id转换为name ***/
        $resu['rows'] = $this->_id_to_name($resu['rows']);
        /*** 格式化出款数据 ***/
        $resu = $this->_format_data($resu);
        /*** 获取汇总数据 ***/
        $resu = $this->_get_footer($resu);

        return $resu;
    }

    /**
     * 现金系统／出入汇总
     * 获取汇总数据
     *
     * @param $basic
     * @param $other =1报表，=2图表调用
     */
    public function get_report($basic, $other=1)
    {
        $select = 'sum(a.in_company_total) as in_company_total,
                    sum(a.in_company_num) as in_company_num,
                    sum(a.in_online_total) as in_online_total,
                    sum(a.in_online_num) as in_online_num,
                    sum(a.in_people_total) as in_people_total,
                    sum(a.in_people_num) as in_people_num,
                    sum(a.in_card_total) as in_card_total,
                    sum(a.in_card_num) as in_card_num,
                    sum(a.in_member_out_deduction) as in_member_out_deduction,
                    sum(a.in_member_out_num) as in_member_out_num,
                    sum(a.out_company_total) as out_company_total,
                    sum(a.out_company_num) as out_company_num,
                    sum(a.in_company_discount) as in_company_discount,
                    sum(a.in_online_discount) as in_online_discount,
                    sum(a.in_people_discount) as in_people_discount,
                    sum(a.in_register_discount) as in_register_discount,
                    sum(a.activity_total) as activity_total,
                    sum(a.in_company_discount_num+a.in_people_discount_num+ a.in_online_discount_num+a.in_card_num+a.activity_num) as out_discount_num,
                    sum(a.out_people_total) as out_people_total,
                    sum(a.out_people_num) as out_people_num,
                    sum(a.out_return_water) as out_return_water,
                    sum(if(a.out_return_water>0,a.out_return_num,0)) as out_return_num,
                    count(if(a.in_register_discount>0,"1",null)) as discount_num';
        $condition = array();
        if (!empty($basic['b.username'])) {
            $condition = array(
                'join' => 'user', 'on' => 'a.uid=b.id'
            );
        }
        $resu = $this->get_list($select, 'cash_report', $basic, $condition);

        // 返水直接从gc_report获取
        $return_water = $this->get_list('sum(num_return) AS out_return_num, sum(return_price) AS out_return_water', 'report', $basic, $condition);
        $resu[0]['out_return_water'] = $return_water[0]['out_return_water'];
        $resu[0]['out_return_num'] = $return_water[0]['out_return_num'];
        
        // 下面这块是查询人数
        // <start>
        $this->open('cash_report');
        //$condition['groupby'] = ['a.uid'];
        if ($other == 1) {
            $condition['wheresql'][] = 'a.report_date >= \''.$basic["a.report_date >="].'\'';
            $condition['wheresql'][] = 'a.report_date <= \''.$basic["a.report_date <="].'\'';
        }
        if ($other == 2) {
            $condition['wheresql'][] = 'a.report_date = \''.$basic["a.report_date ="].'\'';
        }
        if (!empty($condition['join'])) {
            $condition['wheresql'][] = 'b.username = \''.$basic["b.username"].'\'';
        }
        if (!empty($basic['a.agent_id'])) {
            $condition['wheresql'][] = 'a.agent_id = '.$basic['a.agent_id'];
        }

        $condition['wheresql']['f'] = 'a.in_company_num > 0';
        $resu[0]['in_company_peo'] = $this->count_rows('DISTINCT a.uid', [], $condition);

        $condition['wheresql']['f'] = 'a.in_online_num > 0';
        $resu[0]['in_online_peo'] = $this->count_rows('DISTINCT a.uid', [], $condition);

        $condition['wheresql']['f'] = 'a.in_people_num > 0';
        $resu[0]['in_people_peo'] = $this->count_rows('DISTINCT a.uid', [], $condition);

        $condition['wheresql']['f'] = 'a.in_card_num > 0';
        $resu[0]['in_card_peo'] = $this->count_rows('DISTINCT a.uid', [], $condition);

        $condition['wheresql']['f'] = 'a.in_member_out_num > 0';
        $resu[0]['in_member_out_peo'] = $this->count_rows('DISTINCT a.uid', [], $condition);

        $condition['wheresql']['f'] = 'a.out_company_num > 0';
        $resu[0]['out_company_peo'] = $this->count_rows('DISTINCT a.uid', [], $condition);

        $condition['wheresql']['f'] = 'a.out_people_num > 0';
        $resu[0]['out_people_peo'] = $this->count_rows('DISTINCT a.uid', [], $condition);

        $condition['wheresql']['f'] = '( in_register_discount>0 or in_company_discount>0 or in_online_discount>0 or in_people_discount>0 or in_card_total>0 or activity_total >0)';
        $resu[0]['out_discount_peo'] = $this->count_rows('DISTINCT a.uid', [], $condition);

        $this->open('report');
        $condition['wheresql']['f'] = 'a.num_return > 0';
        $resu[0]['out_return_peo'] = $this->count_rows('DISTINCT a.uid', [], $condition);
        $this->open('cash_report');
        // <end>

        return $resu[0];
    }

    /**
     * 收集，汇总cash_report表数据
     * @param $uid  int 用户ID
     * @param $report_date  string 日期
     * @param $data  array 数据
     * @return bool
     */
    public function collect_cash_report($uid, $report_date, $data)
    {
        $where['uid'] = $uid;
        $where['report_date'] = $report_date;
        $if_one = $this->get_one('id', 'cash_report', $where);

        if (!$if_one) {
            /*没有记录，则为插入*/
            $data['uid'] = $uid;
            $data['report_date'] = $report_date;
            $b = $this->write('cash_report', $data);

            if ($b) {
                return true;
            } else {
                return false;
            }
        }
        /*有记录，则为更新*/

        /*公司入款*/
        if (isset($data['in_company_total']) && isset($data['in_company_discount'])) {
            $this->db->set('in_company_total', 'in_company_total+'.$data['in_company_total'], false);
            $this->db->set('in_company_discount', 'in_company_discount+'.$data['in_company_discount'], false);
            if ($data['in_company_discount'] > 0) {
                $this->db->set('in_company_discount_num', 'in_company_discount_num+1', false);
            }
            $this->db->set('in_company_num', 'in_company_num+1', false);
        }
        /*在线入款*/
        if (isset($data['in_online_total']) && isset($data['in_online_discount'])) {
            $this->db->set('in_online_total', 'in_online_total+'.$data['in_online_total'], false);
            $this->db->set('in_online_discount', 'in_online_discount+'.$data['in_online_discount'], false);
            if ($data['in_online_discount'] > 0) {
                $this->db->set('in_online_discount_num', 'in_online_discount_num+1', false);
            }
            $this->db->set('in_online_num', 'in_online_num+1', false);
        }
        /*人工入款*/
        if (isset($data['in_people_total']) && isset($data['in_people_discount'])) {
            $one = $this->get_one('in_people_total,in_people_discount,in_people_num,in_people_discount_num', 'cash_report', $where);
            $data['in_people_total'] += (float)$one['in_people_total'];
            $data['in_people_discount'] += (float)$one['in_people_discount'];
            $data['in_people_num'] += (int)$one['in_people_num'];
            $data['in_people_discount_num'] += (int)$one['in_people_discount_num'];
            $b = $this->db->update('cash_report', $data, $where);
            if ($b) {
                return true;
            } else {
                return false;
            }
           /* $this->db->set('in_people_total','in_people_total+'.$data['in_people_total'],FALSE);
            $this->db->set('in_people_discount','in_people_discount+'.$data['in_people_discount'],FALSE);
            $this->db->set('in_people_num','in_people_num+'.$data['in_people_num'],FALSE);
            $this->db->set('in_people_discount_num','in_people_discount_num+'.$data['in_people_discount_num'],FALSE);*/
        }
        /*优惠卡充值*/
        if (isset($data['in_card_total'])) {
            $this->db->set('in_card_total', 'in_card_total+'.$data['in_card_total'], false);
            $this->db->set('in_card_num', 'in_card_num+'.$data['in_card_num'], false);
        }
        /*会员出款被扣金额*/
        if (isset($data['in_member_out_deduction'])) {
            $this->db->set('in_member_out_deduction', 'in_member_out_deduction+'.$data['in_member_out_deduction'], false);
            $this->db->set('in_member_out_num', 'in_member_out_num+1', false);
        }
        /*人工出款*/
        if (isset($data['out_people_total'])) {
			$one = $this->get_one('out_people_total,out_people_num', 'cash_report', $where);

			$data['out_people_total'] = $data['out_people_total'] + (float)$one['out_people_total'];
			$data['out_people_num'] = $one['out_people_num'] + 1;
			$b = $this->db->update('cash_report', $data, $where);
			if ($b) {
				return true;
			} else {
				return false;
			}
           /* $this->db->set('out_people_total', 'out_people_total+'.$data['out_people_total'], false);
            $this->db->set('out_people_num', 'out_people_num+1', false);*/
        }
        /*线上出款*/
        if (isset($data['out_company_total'])) {
            $this->db->set('out_company_total', 'out_company_total+'.$data['out_company_total'], false);
            $this->db->set('out_company_num', 'out_company_num+1', false);
        }
        /*给予返水*/
        if (isset($data['out_return_water'])) {
            $this->db->set('out_return_water', 'out_return_water+'.$data['out_return_water'], false);
            $this->db->set('out_return_num', 'out_return_num+1', false);
        }
        /*活动优惠*/
        if(isset($data['activity_total'])){
            $this->db->set('activity_total','activity_total+'.$data['activity_total'],FALSE);
            $this->db->set('activity_num','activity_num+1',FALSE);
        }

        //8.30 报表更改
        if (!empty($data['is_one_pay']) && $data['is_one_pay'] == 1) {
            $this->db->set('is_one_pay',1);
        }

        $b = $this->db->update('cash_report', [], $where);
        if ($b) {
            return true;
        } else {
            return false;
        }
    }

    public function dis_cash_report($uid, $report_date, $data)
    {
        $where = [
            'uid' => $uid,
            'report_date' => $report_date
        ];
        $reportInfo = $this->get_one('id', 'cash_report', $where);
        if (!$reportInfo) {
            return;
        }
        /*给予返水*/
        if (isset($data['out_return_water'])) {
            $this->db->set('out_return_water', 'out_return_water-'.$data['out_return_water'], false);
            $this->db->set('out_return_num', 'out_return_num-'.$data['out_return_num'], false);
        }
        $b = $this->db->update('cash_report', [], $where);
        return $b ? true : false;
    }

    /**
     * 将id等转换为名称
     *
     * @access private
     * @param Array $data   数据数组
     * @return $data        转换后结果
     */
    private function _id_to_name($data)
    {
        if (empty($data)) {
            return $data;
        }

        // 初始化0的值
        $cache['user_id'] = [];
        foreach ($data as $k => $v) {
            $user_id = $v['user_id'];
            if (empty($cache['user_id'][$user_id])) {
                $user = $this->user_cache($user_id);
                $cache['user_id'][$user_id] = $user;
            }
            $v['username'] = $cache['user_id'][$user_id]['username'];
            /***增加会员上级代理*****/
            $agent_id = $cache['user_id'][$user_id]['agent_id'];
            if (empty($cache['user_id'][$agent_id])) {
                $user = $this->user_cache($agent_id);
                $cache['user_id'][$agent_id] = $user;
            }
            $v['agent_user_name'] = $cache['user_id'][$agent_id ]['username'];
            $data[$k] = $v;
        }
        return $data;
    }

    /**
     * 格式化数据
     *
     * @access private
     * @param Array $arr 数据数组
     * @return Array
     */
    private function _format_data($arr=[])
    {
        if (empty($arr)) {
            return $arr;
        }

        $field = array('username', 'report_date','agent_user_name');
        foreach ($arr['rows'] as $k => $v) {
            foreach ($v as $k1 => $v1) {
                if (!in_array($k1, $field)) {
                    if (is_numeric($v1) && $v1 != 0) {
                        $arr['rows'][$k][$k1] = (float)$v1;
                    } else {
                        $arr['rows'][$k][$k1] = '-';
                    }
                }
            }
        }
        return $arr;
    }

    /**
     * 获取汇总数据
     *
     * @access private
     * @param Array $arr 出款数据数组
     * @return Array
     */
    private function _get_footer($arr = [])
    {
        if (empty($arr)) {
            return $arr;
        }

        $key_arr = ['in_company_total'=>0,
                    'in_company_discount'=>0,
                    'in_company_num'=>0,
                    'in_online_total'=>0,
                    'in_online_discount'=>0,
                    'in_online_num'=>0,
                    'in_people_total'=>0,
                    'in_people_discount'=>0,
                    'in_people_num'=>0,
                    'in_card_total'=>0,
                    'in_card_num'=>0,
                    'out_people_total'=>0,
                    'out_people_num'=>0,
                    'out_company_total'=>0,
                    'out_company_num'=>0,
                    'in_register_discount'=>0];
        foreach ($arr['rows'] as $k1 => $v1) {
            foreach ($key_arr as $k2 => $v2) {
                if (!empty($v1[$k2]) && is_numeric($v1[$k2])) {
                    $key_arr[$k2] += $v1[$k2];
                }
            }
        }
        $key_arr['username'] = '总优惠金额：'.(float)sprintf('%0.3f',($key_arr['in_company_discount']+$key_arr['in_online_discount']+$key_arr['in_people_discount']+$key_arr['in_card_total']+$key_arr['in_register_discount']));
        foreach ($key_arr as $k2 => $v2) {
            if ($key_arr[$k2] === 0) {
                $key_arr[$k2] = '-';
            }
        }
        $arr['footer'] = [$key_arr];
        return $arr;
    }
    /********************************************/
}
