<?php
/**
 * @模块   视讯
 * @版本   Version 1.0.0
 * @日期   2017-09-11
 * super
 */

defined('BASEPATH') or exit('No direct script access allowed');

class Fsset extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('MY_Model', 'core');
        $this->load->model('sx/Shixun_model', 'sx');
    }

    /*****************返点设置*******************/
    public function getfslist()
    {
        $this->core->select_db('shixun');
        $sn = $this->get_sn();
        $basic = array(
            'username' => $this->G('username'),
            'name' => $this->G('name'),
            'sn' => $sn
        ); //精确条件
        $senior = array(); //高级搜索
        $page = array(
            'page' => $this->G('page'),
            'rows' => $this->G('rows'),
            'order' => $this->G('order'),
            'sort' => $this->G('sort'),
            'total' => -1,
        );
        $arr = $this->core->get_list('*', 'sx_rebate', $basic, $senior, $page);
        foreach ($arr['rows'] as $k => $v) {
            $arr['rows'][$k]['leval_name'] = $this->core->level_cache($v['level_id']);
        }
        $rs = array('total' => $arr['total'], 'rows' => $arr['rows']);
        $this->return_json(OK, $rs);
    }

    /**获取返水设置明细**/
    public function get_info()
    {
        $sn = $this->get_sn();
        $id = $this->input->get('set_id');
        if (empty($id)) {
            $this->return_json(E_DATA_EMPTY);
        }
        $this->core->select_db('shixun');
        $arr = $this->core->get_one('*', 'sx_rebate', array('id' => $id, 'sn' => $sn));
        $this->return_json(OK, $arr);
    }

    //保存返水设置
    public function save_rebate()
    {
        $id = $this->P('id');
        $sn = $this->get_sn();
        $level_id = $this->P('level_id');
        $data = array(
            'level_id' => $level_id,
            'ag' => $this->P('ag')?$this->P('ag'):'0.00',
            'dg' => $this->P('dg')?$this->P('dg'):'0.00',
            'mg' => $this->P('mg')?$this->P('mg'):'0.00',
            'lebo' => $this->P('lebo')?$this->P('lebo'):'0.00',
            'pt' => $this->P('pt')?$this->P('pt'):'0.00',
            'ky' => $this->P('ky')?$this->P('ky'):'0.00',
            'yx_bet' => $this->P('yx_bet'),
            'limit_money' => $this->P('limit_money'),
            'sn' => $sn
        );
        $this->core->select_db('shixun');
        $rs = $this->core->get_one('id', 'sx_rebate', array('level_id' => $level_id, 'sn' => $sn));
        if ($rs != array() && $id != $rs['id']) {
            $this->return_json(E_ARGS, '该层级优惠已存在');
        }
        $where = array();
        if (!empty($id)) {
            $where['id'] = $id;
        }
        $this->core->select_db('shixun_w');
        $arr = $this->core->write('sx_rebate', $data, $where);
        if($arr)
        {
            $this->return_json(OK, '执行成功');
        }
    }
    /*****************返点设置 END*******************/

    /*****************优惠统计*******************/
    public function get_sx_rebate_list()
    {
        $time = $this->G('time');
        $level_id = $this->G('level_id');
        $sn = $this->get_sn();
        if (empty($time)) {
            $this->return_json(E_ARGS, '参数错误');
        }
        if (strtotime($time) >= strtotime(date('Y-m-d', time()))) {
            $this->return_json(E_ARGS, '只能查询昨天之前的数据');
        }
        // 获取查询层级会员
        $uid = [];
        if ($level_id) {
            $user_info = $this->core->get_list('id,level_id', 'user', [], array('wherein' => ['level_id' => explode(',', $level_id)]));
            $uid = array_column($user_info, 'id');
            if (empty($uid)) {
                $this->return_json(E_DATA_EMPTY, '没有数据');
            }
        }
        // 获取返水设置
        $rebate_set = $this->sx->get_rebate_set($sn, $level_id);
        if (empty($rebate_set)) {
            $this->return_json(E_DATA_EMPTY, '请先设置返水比例');
        }
        $rebate_set = array_make_key($rebate_set, 'level_id');
        // 获取站点设置
        $set = $this->core->get_gcset();
        $set = explode(',', $set['cp']);
        // 获取搜索条件
        $params = $this->get_params($time, $uid);
        //获取视讯相关统计
        $data = [];
        if (in_array(1001, $set)) {
            $ag = $this->sx->get_bet_report('ag', $params);
            $data['ag'] = array_make_key($ag['rows'], 'snuid');
        }
        if (in_array(1002, $set)) {
            $dg = $this->sx->get_bet_report('dg', $params);
            $data['dg'] = array_make_key($dg['rows'], 'snuid');
        }
        if(in_array(1003,$set)){
            $dg = $this->sx->get_bet_report('lebo', $params);
            $data['lebo'] = array_make_key($dg['rows'], 'snuid');
        }
        if(in_array(1004,$set)){
            $dg = $this->sx->get_bet_report('pt', $params);
            $data['pt'] = array_make_key($dg['rows'], 'snuid');
        }
        if(in_array(1006,$set)){
            $dg=$this->sx->get_bet_report('ky', $params);
            $data['ky'] = array_make_key($dg['rows'], 'snuid');
        }
        $rs = $this->format_data($rebate_set, $data);
        $this->return_json(OK, ['rows' => $rs, 'total' => 1000]);
    }

    //批量返水操作
    public function rebate_do()
    {
        $time = $this->P('time') ? $this->P('time') : '';
        $dmlbs = $this->P('zhbet') ? $this->P('zhbet') : 0;
        $data = $this->P('data') ? explode(',', $this->P('data')) : [];
        if (empty($data) || empty($time)) {
            $this->return_json(E_ARGS, '参数错误');
        }
        // 生成一条总返水记录
        $t_data = [];
        foreach ($data as $v) {
            $item = explode('-', $v);
            array_push($t_data, ['uid' => $item[0], 'total' => $item[1]]);
        }
        $sum = empty($t_data) ? count($data) : count(array_unique(array_column($t_data, 'uid')));
        $params = [
            'start_date' => $time,
            'end_date' => $time,
            'sum' => $sum,
            'total' => sprintf('%0.3f', array_sum(array_column($t_data, 'total'))),
            'createtime' => time(),
            'createadmin' => $this->admin['username'],
            'sn'=>$this->get_sn(),
            'dmlbs' => $dmlbs
        ];
        $rebate_id = $this->sx->set_rebate_report($params);
        if (empty($rebate_id)) {
            $this->return_json(E_OP_FAIL, '添加返水记录失败');
        }
        foreach ($t_data as $k => $v) {
            $b = $this->rebate_run($v, $time, $dmlbs);
            if ($b !== false) {
                // 修改每个视讯报表的返水状态及关联ID
                $auth_id = $b !== true ? $b : 0;
                $this->sx->update_report_status($v['uid'], $time, $rebate_id, $auth_id, 1);
                unset($t_data[$k]);
            } else {
                break;
            }
        }
        //检测是否全部成功，不成功则回退部分数据
        if (!empty($t_data)) {
            $is_delete = count($t_data) == $sum ? true : false;
            if ($is_delete) {
                $msg = '优惠返水失败';
            } else {
                $msg = '部分返水成功，为返水用户有：' . implode(',', array_column($t_data, 'uid'));
            }
            $this->sx->update_report_data($rebate_id, $t_data, $is_delete);
            $this->return_json(E_OP_FAIL, $msg);
        }
        $this->return_json(OK);
    }

    /**
     * 获取查询条件
     * @param $time
     * @param $uid
     * @return array
     */
    private function get_params($time, $uid)
    {
        $rs = [
            'field' => 'snuid,total_v_bet',
            'where' => ['bet_time' => $time, 'is_fs !=' => 1,'sn'=>$this->get_sn()],
            'condition' => !empty($uid) ? ['wherein' => array('snuid' => $uid)] : [],
            'page' => array(
                'page' => $this->G('page') ? $this->G('page') : 1,
                'rows' => $this->G('rows') ? $this->G('rows') : 20,
                'order' => $this->G('order'),
                'sort' => $this->G('sort'),
                'total' => -1,
            )
        ];
        return $rs;
    }

    /**
     * 组装数据
     * @param $rebate_set
     * @param $data
     * @return array
     */
    private function format_data($rebate_set, $data)
    {
        $rs = [];
        $uid = $this->get_uid($data);
        if (empty($uid)) {
            return $rs;
        }
        $user_info = $this->core->get_list('id,level_id', 'user', [], array('wherein' => ['id' => $uid]));
        foreach ($user_info as $v) {
            $t = [
                'id' => $v['id'],
                'auth_id' => $this->get_auth_id($data, $v['id']),
                'ag_bet' => isset($data['ag'][$v['id']]) ? $data['ag'][$v['id']]['total_v_bet'] : 0,
                'dg_bet' => isset($data['dg'][$v['id']]) ? $data['dg'][$v['id']]['total_v_bet'] : 0,
                'ky_bet' => isset($data['ky'][$v['id']]) ? $data['ky'][$v['id']]['total_v_bet'] : 0,
                'lebo_bet' => isset($data['lebo'][$v['id']]) ? $data['lebo'][$v['id']]['total_v_bet'] : 0,
            ];
            $t['bet_total'] = $t['ag_bet'] + $t['dg_bet']+$t['ky_bet'];
            $user = $this->core->user_cache($v['id']);
            $level = $this->core->level_cache($v['level_id']);
            $t['username'] = isset($user['username']) ? $user['username'] : '';
            $t['level'] = $level ? $level : '';
            $t['ag_rebate'] = isset($rebate_set[$v['level_id']]) ? sprintf('%0.3f', $t['ag_bet'] * $rebate_set[$v['level_id']]['ag'] / 100) : 0;
            $t['dg_rebate'] = isset($rebate_set[$v['level_id']]) ? sprintf('%0.3f', $t['dg_bet'] * $rebate_set[$v['level_id']]['dg'] / 100) : 0;
            $t['ky_rebate'] = isset($rebate_set[$v['level_id']]) ? sprintf('%0.3f', $t['ky_bet'] * $rebate_set[$v['level_id']]['ky'] / 100) : 0;
            $t['lebo_rebate'] = isset($rebate_set[$v['level_id']]) ? sprintf('%0.3f', $t['lebo_bet'] * $rebate_set[$v['level_id']]['lebo'] / 100) : 0;
            $t['rebate_total'] = sprintf('%0.3f', $t['ag_rebate'] + $t['dg_rebate']+$t['ky_rebate']+$t['lebo_rebate']);
            if($t['rebate_total']>$rebate_set[$v['level_id']]['limit_money']){
                //超过该人返水上限,就按照比例等比减小
                $t['ag_rebate']=sprintf('%0.3f',$t['ag_rebate']*$rebate_set[$v['level_id']]['limit_money']/$t['rebate_total']);
                $t['dg_rebate']=sprintf('%0.3f',$t['dg_rebate']*$rebate_set[$v['level_id']]['limit_money']/$t['rebate_total']);
                $t['ky_rebate']=sprintf('%0.3f',$t['ky_rebate']*$rebate_set[$v['level_id']]['limit_money']/$t['rebate_total']);
                $t['lebo_rebate']=sprintf('%0.3f',$t['lebo_rebate']*$rebate_set[$v['level_id']]['limit_money']/$t['rebate_total']);
                $t['rebate_total']=sprintf('%0.3f', $rebate_set[$v['level_id']]['limit_money']);
            }
            if ($t['rebate_total'] > 0) {
                array_push($rs, $t);
            }
        }
        return $rs;
    }

    private function get_uid($data)
    {
        $rs = [];
        if (isset($data['ag'])) {
            $rs = array_merge($rs, array_keys($data['ag']));
        }
        if (isset($data['dg'])) {
            $rs = array_merge($rs, array_keys($data['dg']));
        }
        if (isset($data['ky'])) {
            $rs = array_merge($rs, array_keys($data['ky']));
        }
        if (isset($data['lebo'])) {
            $rs = array_merge($rs, array_keys($data['lebo']));
        }
        return array_unique($rs);
    }

    private function get_auth_id($data, $id)
    {
        $auth_id = 0;
        if (isset($data['ag']) && isset($data['ag'][$id])) {
            $auth_id = $data['ag'][$id]['auth_id'];
        } elseif (isset($data['dg']) && isset($data['dg'][$id])) {
            $auth_id = $data['dg'][$id]['auth_id'];
        } elseif (isset($data['lebo']) && isset($data['lebo'][$id])) {
            $auth_id = $data['lebo'][$id]['auth_id'];
        } elseif (isset($data['pt']) && isset($data['pt'][$id])) {
            $auth_id = $data['pt'][$id]['auth_id'];
        }elseif (isset($data['ky']) && isset($data['ky'][$id])){
            $auth_id = $data['ky'][$id]['auth_id'];
        }
        return $auth_id;
    }

    /**
     * 返水开始
     * @param array $data 返水用户及金额 ['uid' => 1, 'total' => 1]
     * @param string $time 优惠返水的时间
     * @param int $dmlbs 打码量倍数
     * @return mixed 失败返回false 成功返回true或者稽核id
     */
    private function rebate_run($data, $time, $dmlbs)
    {
        $this->load->model('log/Log_model', 'lo');
        // 开始事务
        $this->core->db->trans_begin();
        // 每个会员进行返水,写入现金记录
        $error_log = ['content' => '用户：' . $data['uid'] . '视讯优惠退水失败，退水结算时间' . $time];
        $b = $this->core->update_banlace($data['uid'], $data['total'], order_num(BALANCE_RETURN, BALANCE_RETURN), BALANCE_RETURN, '视讯优惠退水,退水结算时间：' . $time, $data['total']);
        if (!$b) {
            $this->core->db->trans_rollback();
            $this->lo->record($this->admin['id'], $error_log);
            return false;
        }
        //增加稽核
        $auth_id = 0;
        if (!empty($dmlbs)) {
            $this->load->model('pay/Pay_set_model', 'ps');
            $pay_data = $this->ps->get_pay_set($data['uid'], 'ps.pay_set_content');//支付设定信息
            $auth_data = [
                'uid' => $data['uid'],
                'total_price' => $data['total'],
                'discount_price' => 0,
                'auth_dml' => intval($dmlbs * $data['total']),
                'limit_dml' => isset($pay_data['line_ct_fk_audit']) ? $pay_data['line_ct_fk_audit'] : 0,
                'start_time' => $_SERVER['REQUEST_TIME'],
                'is_pass' => 0,
                'type' => 4,
            ];
            $b = $this->core->write('auth', $auth_data);
            if (!$b) {
                $this->core->db->trans_rollback();
                $this->lo->record($this->admin['id'], $error_log);
                return false;
            }
            $auth_id = $this->core->db->insert_id();
        }
        //汇总现金报表
        $this->load->model('cash/Report_model', 'report');
        $cash_data = [
            'out_return_water' => $data['total'],
            'out_return_num' => 1,
        ];
        $b = $this->report->collect_cash_report($data['uid'], date('Y-m-d'), $cash_data);
        if (!$b) {
            $this->core->db->trans_rollback();
            $this->lo->record($this->admin['id'], $error_log);
            return false;
        }
        //写入日志
        if ($this->core->db->trans_status() === false) {
            $this->core->db->trans_rollback();
            $this->lo->record($this->admin['id'], $error_log);
            return false;
        } else {
            $this->core->db->trans_commit();
            $this->lo->record($this->admin['id'], ['content' => '用户：' . $data['uid'] . '视讯优惠退水成功，退水结算时间' . $time]);
            return $auth_id ? $auth_id : true;
        }
    }

    /*****************优惠统计 END*******************/

    /*****************优惠查询*******************/
    public function get_rebate_report_list()
    {
        $start = $this->G('start') ? $this->G('start') : date('Y-m-d');
        $end = $this->G('end') ? $this->G('end') : date('Y-m-d');
        if (strtotime($end . ' 23:59:59') - strtotime($start . ' 00:00:00') > ADMIN_QUERY_TIME_SPAN) {
            $this->return_json(E_ARGS, '查询跨度不能大于两个月');
        }
        $where = [
            'sn' => $this->get_sn(),
            'start_date >=' => $start,
            'end_date <=' => $end
        ];
        $page = array(
            'page' => $this->G('page'),
            'rows' => $this->G('rows'),
            'order' => $this->G('order'),
            'sort' => $this->G('sort'),
            'total' => -1,
        );
        $rs = $this->sx->get_rebate_report_list($where, $page);
        $this->return_json(OK, $rs);
    }
    /*****************优惠查询 END*******************/

    /*****************优惠明细*******************/
    /**
     * @param $id
     * @return array
     */
    public function get_rebate_detail_list($id = 0)
    {
        $rebate_id = $id ? $id : $this->G('rebate_id');
        $sn = $this->get_sn();
        if (empty($rebate_id)) {
            $this->return_json(E_ARGS, '参数错误');
        }
        // 获取返水设置
        $rebate_set = $this->sx->get_rebate_set($sn);
        if (empty($rebate_set)) {
            $this->return_json(E_DATA_EMPTY, '请先设置返水比例');
        }
        $rebate_set = array_make_key($rebate_set, 'level_id');
        // 获取站点设置
        $set = $this->core->get_gcset();
        $set = explode(',', $set['cp']);
        // 获取搜索条件
        $params = [
            'field' => 'snuid,total_v_bet,auth_id,bet_time',
            'where' => ['rebate_id' => $rebate_id,'sn'=>$this->get_sn()],
            'page' => array(
                'page' => $this->G('page') ? $this->G('page') : 1,
                'rows' => $this->G('rows') ? $this->G('rows') : 20,
                'order' => $this->G('order'),
                'sort' => $this->G('sort'),
                'total' => -1,
            )
        ];
        //获取视讯相关统计
        $data = [];
        if (in_array(1001, $set)) {
            $ag = $this->sx->get_bet_report('ag', $params);
            $data['ag'] = array_make_key($ag['rows'], 'snuid');
        }
        if (in_array(1002, $set)) {
            $dg = $this->sx->get_bet_report('dg', $params);
            $data['dg'] = array_make_key($dg['rows'], 'snuid');
        }
        if (in_array(1003, $set)) {
            $dg = $this->sx->get_bet_report('lebo', $params);
            $data['lebo'] = array_make_key($dg['rows'], 'snuid');
        }
        if (in_array(1006, $set)) {
            $dg = $this->sx->get_bet_report('ky', $params);
            $data['ky'] = array_make_key($dg['rows'], 'snuid');
        }
        $rs = $this->format_data($rebate_set, $data);
        $rebate_report=$this->sx->get_rebate_report(array('id'=>$rebate_id));
        if ($id) {
            return array(
                'rs'=>$rs,
                'date'=>date('Y-m-d',$rebate_report['createtime'])
            );
        } else {
            $this->return_json(OK, ['rows' => $rs, 'total' => count($rs),'date'=>date('Y-m-d',$rebate_report['createtime'])]);
        }
    }

    /**
     * 冲销
     */
    public function rebate_cx()
    {
        $time = $this->P('time') ? $this->P('time') : '';
        $rebate_id = $this->P('rebate_id') ? $this->P('rebate_id') : 0;
        if (empty($rebate_id)) {
            $this->return_json(E_ARGS, '参数错误');
        }
        $rebate_data = $this->get_rebate_detail_list($rebate_id);
        $data=$rebate_data['rs'];
        $create_time=$rebate_data['date'];
        if (empty($time) || empty($data)) {
            $this->return_json(OK, '没有需要冲销的数据');
        }
        foreach ($data as $k => $v) {
            $b = $this->cx_run($v, $create_time);
            if ($b) {
                $c=$this->sx->update_report_status($v['id'], $time);
                unset($data[$k]);
            } else {
                break;
            }
        }
        //检测是否全部成功，不成功则回退部分数据
        if (!empty($data)) {
            $this->sx->update_report_data($rebate_id, $data);
            $this->return_json(E_OP_FAIL, '优惠返水失败,失败用户有：' . implode(',', array_column($data, 'id')));
        } else {
            $this->sx->update_report_data($rebate_id, [], true);
        }
        $this->return_json(OK);
    }

    private function cx_run($data, $time)
    {
        $this->load->model('log/Log_model', 'lo');
        // 开始事务
        $this->core->db->trans_begin();
        // 减钱，写现金记录
        $b = $this->core->update_banlace($data['id'], -$data['rebate_total'], order_num(BALANCE_RETURN, BALANCE_RETURN), BALANCE_RETURN, '视讯冲销,冲销结算时间：' . $time, -$data['rebate_total']);
        if (!$b) {
            $this->core->db->trans_rollback();
            $this->lo->record($this->admin['id'], ['content' => '用户：' . $data['id'] . '视讯冲销失败,失败原因：退回返水失败，冲销结算时间' . $time]);
            return false;
        }
        //减少稽核
        if (!empty($data['auth_id'])) {
            $b = $this->core->delete('auth', $data['auth_id']);
            if (!$b) {
                $this->core->db->trans_rollback();
                $this->lo->record($this->admin['id'], ['content' => '用户：' . $data['id'] . '视讯冲销失败,失败原因：减少稽核失败，冲销结算时间' . $time]);
                return false;
            }
        }
        //汇总现金报表
        $this->load->model('cash/Report_model', 'report');
        $cash_data = [
            'out_return_water' => $data['rebate_total'],
            'out_return_num' => 1,
        ];
        $b = $this->report->dis_cash_report($data['id'], $time, $cash_data);
        if (!$b) {
            $this->core->db->trans_rollback();
            $this->lo->record($this->admin['id'], ['content' => '用户：' . $data['id'] . '视讯冲销失败,失败原因：更新现金报表汇总失败，冲销结算时间' . $time]);
            return false;
        }
        //写入日志
        if ($this->core->db->trans_status() === false) {
            $this->core->db->trans_rollback();
            $this->lo->record($this->admin['id'], ['content' => '用户：' . $data['id'] . '视讯冲销失败,失败原因：事务提交失败，冲销结算时间' . $time]);
            return false;
        } else {
            $this->core->db->trans_commit();
            $this->lo->record($this->admin['id'], ['content' => '用户：' . $data['id'] . '视讯冲销成功，退水结算时间' . $time]);
            return true;
        }
    }
    /*****************优惠明细 END*******************/
}