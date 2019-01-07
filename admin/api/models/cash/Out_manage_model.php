<?php
/**
 * @模块   现金系统／出款管理model
 * @版本   Version 1.0.0
 * @日期   2017-03-30
 * shensiming
 */

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Out_manage_model extends MY_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->select_db('private');
    }


    public $push_str = "";
    /******************公共方法*******************/
    /**
     * 获取出款管理数据
     */
    public function get_outmanage($basic, $senior, $page)
    {

        /*** 查询出款管理数据 ***/
        $select = 'd.balance as balance,
                    a.agent_id as agent_id,
                    c.username as admin_name,
                    e.bank_name as bank_name,
                    a.uid as uid,
                    a.id as id,
                    a.is_first as is_first,
                    a.order_num,
                    a.price as price,
                    a.hand_fee as hand_fee,
                    a.admin_fee as admin_fee,
                    a.people_remark as people_remark,
                    a.remark as remark,
                    a.actual_price as actual_price,
                    a.is_pass as is_pass,
                    a.addtime as addtime,
                    a.updated as updated,
                    a.status as status,
                    a.admin_id as admin_id,
                    a.url as url,
                    a.o_status as o_status,
                    a.from_way as from_way';
        $resu = $this->get_list($select, 'cash_out_manage',
                            $basic, $senior, $page);
        /*** 加入层级列表并且把id转换为name ***/
        $resu['rows'] = $this->_id_to_name($resu['rows']);
        /*** 格式化出款数据 ***/
        $resu = $this->_format_data($resu);
        /*** 获取汇总数据 ***/
        $resu = $this->_get_footer($resu);
        return $resu;
    }

    /**
     * 获取自动出款管理数据
     */
    public function get_auto_outmanage($basic, $senior, $page)
    {

        /*** 查询出款管理数据 ***/
        $select = '                   
                    c.username as admin_name,
                    a.uid as uid,
                    a.id as id,
                    a.o_id as o_id,
                    a.order_num,
                    a.price as price,
                    a.remark as remark,
                    a.addtime as addtime,
                    a.update_time as updated,
                    a.status as status,
                    a.admin_id as admin_id,';
        $resu = $this->get_list($select, 'cash_out_online',
                            $basic, $senior, $page);
        /*** 加入层级列表并且把id转换为name ***/
        $resu['rows'] = $this->_id_to_name($resu['rows']);
        /*** 格式化出款数据 ***/
        $resu = $this->_format_data($resu);
        /*** 获取汇总数据 ***/
        $select1 = 'sum(a.price) as price, 
                    count(*) as out_online_num';
        $footer = $this->get_list($select1, 'cash_out_online',
                            $basic, $senior);
        foreach ($footer[0] as $key => $value) {
            $footer[0][$key] = floatval($value);
        }
        $resu['footer'] = $footer;
        return $resu;
    }


    /**
     * 预备出款
     * @param int  出款ID
     * @return bool
    */
    public function out_prepare($id=0)
    {
        $data['status']  = OUT_PREPARE;
        $data['admin_id'] = $this->admin['id'];
        $data['updated'] = $_SERVER['REQUEST_TIME'];
        $where['id']     = $id;
        $where['status'] = OUT_NO;
        $res = $this->write('cash_out_manage', $data, $where);
        return $res;
    }

    /**
     * 拒绝出款
     * @param int   出款ID
     * @param sting   备注
     * @return bool
    */
    public function out_cancel($id=0, $remark='', $admin=[])
    {
        $a = 'cash_out_manage';
        $where[$a.'.id'] = $id;
        $where['wherein'] = array($a.'.status'=>array(OUT_NO,OUT_PREPARE));
        $where2  = [
            'join' => 'user',
            'on'   => $a.'.uid=b.id'
        ];
        $outData = $this->get_one("$a.uid,$a.order_num,$a.agent_id,$a.price,$a.actual_price,$a.addtime,b.balance,b.username", 'cash_out_manage', $where, $where2);

        if (empty($outData)) {
            return false;
        }
        $this->push_str   = "管理员{$admin['name']}拒绝会员出款,提款额度{$outData['price']},实际出款金额{$outData['actual_price']};会员id:{$outData['uid']}";
        $remarkstr        = "拒绝出款,提款额度{$outData['price']}实际出款金额:{$outData['actual_price']} 管理员备注:";
        $this->db->trans_start();
        $data['status']   = OUT_CANCEL;
        $data['updated']  = $_SERVER['REQUEST_TIME'];
        $data['admin_id'] = $this->admin['id'];
        $data['remark']   = $remarkstr.$remark;
        $b1 = $this->write('cash_out_manage', $data, $where);
        if ($b1) {
            /*清除所有稽核*/
            $this->clear_w_dml($outData['uid'], $outData['actual_price'], 0);
            $time = $this->get_del_out_user($outData['uid']);
            $b = $this->out_clear_auth($outData['uid'], '拒绝出款清除稽核', $time);
            if (!$b) {
                $this->db->trans_rollback();
                return false;
            }

            /*$cashList['uid']       = $outData['uid'];
            $cashList['order_num'] = $outData['order_num'];
            $cashList['before_balance'] = $outData['balance'] ;
            $cashList['type']      = 20;
            $cashList['amount']    = $outData['price'];
            $cashList['balance']   = $outData['balance'] + $outData['price'];
            $cashList['remark']    = '拒绝出款';
            $cashList['addtime']   = $_SERVER['REQUEST_TIME'];
            $b5 = $this->write('cash_list', $cashList);
            if (!$b5) {
                $this->db->trans_rollback();
                return false;
            }*/

            $cashData['in_member_out_deduction'] = $outData['price'];
            $cashData['in_member_out_num'] = 1;
            $cashData['agent_id'] = $outData['agent_id'];
            $this->load->model('cash/Report_model');
            $report_date = date('Y-m-d', $_SERVER['REQUEST_TIME']);
            $bool = $this->Report_model->collect_cash_report($outData['uid'], $report_date, $cashData);
            if (!$bool) {
                $this->db->trans_rollback();
                return false;
            }
            $this->out_unlock($outData['uid']);
            $this->db->trans_commit();
            $this->get_del_out_user($outData['uid'], false);
            wlog(APPPATH.'logs/cash_out2_'.$this->sn.'_'.date('Ym').'.log', $this->push_str);
            return true;
        } else {
            $this->db->trans_rollback();
            return false;
        }
    }

    /**
     * 取消出款
     * @param $id int   出款ID
     * @param $remark string   beizhu
     * @param $admin  array 管理员账号
     * @return bool
    */
    public function out_refuse($id=0, $remark='', $admin=[])
    {
        $a = 'cash_out_manage';
        $where[$a.'.id'] = $id;
        //$where[$a.'wherein'] = array('status'=>array(OUT_NO,OUT_PREPARE));
        //$outData = $this->get_one('uid,price,actual_price,admin_fee,hand_fee,order_num','cash_out_manage',$where);
        $outData = $this->db->select("$a.uid,$a.price,$a.actual_price,$a.hand_fee,$a.admin_fee,$a.is_pass,$a.addtime,$a.order_num ,,b.balance")
            ->join('user b', "b.id = $a.uid", 'left')->where($where)->where_in($a.'.status', array(OUT_NO,OUT_PREPARE))->limit(1)->get($a)->row_array();
        if (empty($outData)) {
            return false;
        }
        $this->push_str   = "管理员{$admin['name']}取消出款,提款额度{$outData['price']},实际出款金额{$outData['actual_price']}";
        $money = $outData['price']+$outData['balance'];
        $str = "取消出款 额度:{$outData['price']},账户余额{$money} 管理员备注:";
        $this->db->trans_start();
        $data['status'] = OUT_REFUSE;
        $data['updated'] = $_SERVER['REQUEST_TIME'];
        $data['admin_id'] = $this->admin['id'];
        $data['remark'] = $str.$remark;
        $b1 = $this->write('cash_out_manage', $data, $where);
        if ($b1) {
            /*把订单的钱加回给用户*/
            $this->load->model('cash/Cash_common_model', 'ccm');
            $outPrice = $outData['actual_price'] + $outData['hand_fee'] +  $outData['admin_fee'];
            //$b = $this->db->set('balance', 'balance+'.$outPrice, false)->update('user', [], array('id'=>$outData['uid']));
            //取消出款需要流水
            if (!empty($admin['name'])) {
                $strx = '公司出款-取消出款 管理员: '.$admin['name'].':'.$remark;

            }else{
                $strx = '公司出款-取消出款'.$remark;
            }
            $b = $this->update_banlace($outData['uid'],$outPrice,$outData['order_num'],18,$strx);
            if (!$b) {
                $this->db->trans_rollback();
            }
            $this->out_unlock($outData['uid']);
            $this->db->trans_commit();
            return true;
        } else {
            $this->db->trans_rollback();
            return false;
        }
    }

    /**
     * 确认出款
     * @param int   出款ID
     * @return bool
    */
    public function out_do($id=0, $admin=[])
    {
        $a = 'cash_out_manage';
        $where[$a.'.id'] = $id;
        $where[$a.'.status'] = OUT_PREPARE;
        $outData = $this->db->select("$a.uid,$a.agent_id,$a.price,$a.actual_price,$a.is_pass,$a.addtime,$a.order_num,$a.remark, b.max_out_price,b.balance")
                 ->join('user b', "b.id = $a.uid", 'left')->where($where)->limit(1)->get($a)->row_array();

        if (empty($outData)) {
            return false;
        }
        $uid = $outData['uid'];
        $this->push_str   = "管理员{$admin['name']}确认出款,提款额度{$outData['price']},实际出款金额{$outData['actual_price']}:会员id:$uid";
        $this->db->trans_start();
        $data['status']   = OUT_DO;
        $data['admin_id'] = $admin['id'];
        $data['updated']  = $_SERVER['REQUEST_TIME'];

        //10.6修改
        if (isset($outData['remark'])) {
            $data['remark'] = strtr($outData['remark'], ['正在转账中,请稍等'=>'转账成功', '正在出款中,请稍等'=>'出款成功']);
        } else {
            $data['remark'] = '出款成功';
        }
        //-------

        $b1 = $this->write('cash_out_manage', $data, $where);
        if ($b1) {
            /*更新用户最大出款数*/
            if ($outData['actual_price'] > $outData['max_out_price']) {
                $bool = $this->db->update('user', ['max_out_price'=>$outData['actual_price']], ['id'=>$uid]);
                if (!$bool) {
                    $this->db->trans_rollback();
                    return false;
                }
            }
            /*清除所有稽核*/
            $set = $this->get_gcset(['win_dml']);
            $this->clear_w_dml($outData['uid'], $outData['actual_price'], $set['win_dml']);
            if (!(isset($set['win_dml']) && $set['win_dml'] == 1 && empty($outData['is_pass']))) {
                $time = $this->get_del_out_user($outData['uid']);
                $b    = $this->out_clear_auth($outData['uid'], '确认出款清除稽核', $time);
                if (!$b) {
                    $this->db->trans_rollback();
                    return false;
                }
            }
            /*写入现金流水*/
            /*$cashList['uid']       = $uid;
            $cashList['order_num'] = $outData['order_num'];
            $cashList['before_balance'] = $outData['balance'] - $outData['actual_price'];
            $cashList['type']      = 14;
            $cashList['amount']    = $outData['actual_price']*-1;
            $cashList['balance']   = $outData['balance'];
            $cashList['remark']    = '确认公司出款';
            $cashList['addtime']   = $_SERVER['REQUEST_TIME'];
            $b5 = $this->write('cash_list', $cashList);
            if (!$b5) {
                $this->db->trans_rollback();
                return false;
            }*/

            /*写入日结报表 实际出款金额*/
            $cashData['out_company_total'] = $outData['actual_price'];
            $cashData['out_company_num'] = 1;
            $cashData['agent_id'] = $outData['agent_id'];
            if ($outData['price'] - $outData['actual_price'] > 0) {
                $cashData['in_member_out_deduction'] = $outData['price'] - $outData['actual_price'];
                $cashData['in_member_out_num'] = 1;
            }
            $this->load->model('cash/Report_model');
            // edit by wuya 写入现金报表按照订单确认时间
            //$bool = $this->Report_model->collect_cash_report($outData['uid'], date('Y-m-d', $outData['addtime']), $cashData);
            $bool = $this->Report_model->collect_cash_report($outData['uid'], date('Y-m-d', $data['updated']), $cashData);
            if (!$bool) {
                $this->db->trans_rollback();
                return false;
            }

            $this->out_unlock($outData['uid']);
            $this->get_del_out_user($outData['uid'], false);

            //通过稽核  累计
            if ($outData['is_pass'] == 1) {
                $rkUserCounterNum = 'user_count:counter_num:'.$outData['uid'];
                $bool = $this->redis_setnx($rkUserCounterNum,1);
                if ($bool) {
                    $expire_time = strtotime(date('Y-m-d',strtotime('+1 day')))-time();
                    $this->redis_expire($rkUserCounterNum,$expire_time);
                }else{
                    $this->redis_INCRBY($rkUserCounterNum, 1);
                }
            }
            //累计用户的今日出款次数
            $outNum = 'user:out_num:_'.date('Y-m-d');
            $this->redis_HINCRBY($outNum,$uid,1);
            $this->redis_expire($outNum, 24*3600+120);
            wlog(APPPATH.'logs/cash_out_'.$this->sn.'_'.date('Ym').'.log', $this->push_str);
            $this->db->trans_commit();
            return true;
        } else {
            $this->db->trans_rollback();
            return false;
        }
    }

    /**
     * 出款订单号自动取消
    */
    public function auto_out_refuse()
    {
        die;
        $gcSet = $this->get_gcset(['incompany_timeout']);
        $time = $_SERVER['REQUEST_TIME'] - $gcSet['incompany_timeout']*60;
        $where  = [
            'addtime <' => $time,
            'status'  => 1,
        ];
        $fined  = 'order_num,uid,price';
        $data   = $this->get_all($fined, 'cash_out_manage', $where);
        if (empty($data)) {
            return true;
        }
        $this->db->trans_begin();
        $bool = $this->db->update('cash_out_manage', ['status' => 5 , 'updated' => $_SERVER['REQUEST_TIME'] ], $where);
        if (!$bool) {
            $this->db->trans_rollback();
            return false;
        }
        foreach ($data as $key => $value) {
            $sql="UPDATE gc_user SET balance = balance+{$value['price']}
                      WHERE id = {$value['uid']}";
            $bool = $this->db->query($sql);
            if (!$bool) {
                $this->db->trans_rollback();
                return false;
            }
        }
        return $this->db->trans_commit();
    }
    /**
     * 根据订单id 获取订单信息和用户信息
     * 获取到未出款的订单信息
     *
    */
    public function get_detail($id)
    {
        $a = 'gc_cash_out_manage';
        $where =array(
            $a.'.id'=>$id,
        );
        $where['wherein'] = array( "$a.status"=>array(OUT_NO,OUT_PREPARE));
        $condition['join'] = array(
            array('table'=>'user as b','on'=>"$a.uid = b.id")
        );

        $str =  "$a.order_num,$a.updated,$a.admin_fee,$a.hand_fee,$a.admin_id,$a.price,b.balance,b.id,b.username";
        return $this->get_one($str, 'cash_out_manage ', $where, $condition);
    }
    /**
     * 出款清除稽核
     * @param int   会员ID
     * @return bool
    */
    public function out_clear_auth($uid=0, $remark='', $starttime='')
    {
        $where['uid'] = $uid;
        $where['start_time <'] =$starttime;

        $b = $this->db->where($where)->delete('auth');
        if (!$b) {
            return false;
        }

        $data['uid'] = $uid;
        $data['content'] = $remark;
        $data['addtime'] = $_SERVER['REQUEST_TIME'];
        $b1 = $this->write('auth_log', $data);
        if ($b1) {
            $this->redis_select(REDIS_LONG);
            $rkUserAuthDml = 'user:dml';
            $this->redis_hdel($rkUserAuthDml, $uid);
            $this->redis_select(REDIS_DB);
            return true;
        } else {
            return false;
        }
    }

    /**
     * 清除用户赢钱打码量
     * @param int $uid
     * @param float $money
     * @param bool $is_w_dml
     */
    public function clear_w_dml($uid = 0, $money, $is_w_dml)
    {
        $this->redis_select(REDIS_LONG);
        $w_dml = $this->redis_hget('user:win_dml', $uid);
        $money = (float)$w_dml - $money;
        if ($money > 0 && $is_w_dml == 1) {
            $this->redis_hset('user:win_dml', $uid, $money);
        } else {
            $this->redis_hdel('user:win_dml', $uid);
        }
        $this->redis_select(REDIS_DB);
    }

    /**
     * 添加更改订单锁
    */
    public function set_chang_lock($id='', $bool=1)
    {
        $keys = 'temp:cash:chang_out';
        if ($bool) {
            $bool = $this->redis_hsetnx($keys, $id, time());
            if ($bool) {
                return true;
            } else {
                $set_time = $this->redis_hget($keys, $id);
                if (time()-10 > $set_time) {
                    $this->redis_del($keys, $id);
                    return true;
                }
                return false;
            }
        } else {
            $set_time = $this->redis_hget($keys, $id);
            if (time()-10 > $set_time) {
                $this->redis_del($keys, $id);
                return true;
            } else {
                if (empty($set_time)) {
                    return true;
                }
                return false;
            }
        }
    }
    /**
     * 删除订单锁
    */
    public function del_chang_lock($id='')
    {
        $keys = 'temp:cash:chang_out';
        return $this->redis_del($keys, $id);
    }


    /**
     * 出款解锁 出款次数统计
     * @param int   会员ID
     * @return bool
    */
    public function out_unlock($uid=0)
    {
        $rkOutUnlock = 'user:out_lock:'.$uid;

        $this->redis_del($rkOutUnlock);
    }
    /********************************************/




    /******************私有方法*******************/
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
     * 删除会员的出款时间
    */
    public function get_del_out_user($id, $bool =true)
    {
        $keys =  "temp:out_time:user_$id";
        if ($bool) {
            $time = $this->redis_get($keys);
            if ($time) {
                return $time;
            } else {
                return time();
            }
        } else {
            return $this->redis_del($keys);
        }
    }

    public function get_w_dml($id)
    {
        $keys = "temp:out_user_w_dml:user_$id";
        $is_w_dml = $this->redis_get($keys);
        $this->redis_del($keys);
        return $is_w_dml ? $is_w_dml : 0;
    }

    /**
     * 将levelid和adminid转换为name
     */
    private function _id_to_name($data)
    {
        if (!$data) {
            return $data;
        }
        // //.增加一个自动出款的值
        // $this->select_db('public');
        // $out_arr = $this->get_list('id, out_online_name as out_name','out_online');
        // $this->select_db('private');
        // 初始化0的值
        $cache['user_id'][0] = ['username'=>'-'];
        $cache['leve_id'][0] = '-';
        $cache['o_id'][0] = '-';
        foreach ($data as $k => $v) {
            $data[$k]['addtime'] = date("Y-m-d H:i:s",$v['addtime']);
            $data[$k]['updated'] = date("Y-m-d H:i:s",$v['updated']);
            $uid = $v['uid'];
            $out_id = isset($v['o_id'])?$v['o_id']:'-1';
            $agent_id = $v['agent_id'];

            if (empty($cache['user_id'][$uid])) {
                $user = $this->user_cache($uid);
                $cache['user_id'][$uid] = $user;
            }
            $leve_id = $cache['user_id'][$uid]['level_id'];
            
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

            // //.获取自动出款第三方名称
            // if (!empty($out_arr[$out_id]['out_name']) && 
            //     !empty($out_arr[$out_id]['id'])) {
            //     $v['out_name'] = $out_arr[$out_id]['out_name'].'(ID:'.$out_arr[$out_id]['id'].')';
            // } else {
            //     $v['out_name'] = '-';
            // }

            $v['leve_name'] = $cache['leve_id'][$leve_id];
            $v['user_name'] = $cache['user_id'][$uid]['username'];
            if ($cache['user_id'][$agent_id]) {
                $v['agent_name'] = $cache['user_id'][$agent_id]['username'];
            } else {
                $v['agent_name'] = '-';
            }
            
            $data[$k] = $v;
        }
        return $data;
    }

    /**
     * 格式化出款数据
     *
     * @access private
     * @param Array $arr 出款数据数组
     * @return Array
     */
    private function _format_data($arr=[])
    {
        if (empty($arr)) {
            return $arr;
        }

        foreach ($arr['rows'] as $k => $v) {
            $arr['rows'][$k]['price'] = floatval($v['price']);
            $arr['rows'][$k]['hand_fee'] = floatval($v['hand_fee']);
            $arr['rows'][$k]['admin_fee'] = floatval($v['admin_fee']);
            $arr['rows'][$k]['balance'] = floatval($v['balance']);
            $arr['rows'][$k]['addtime'] =
                        date('Y-m-d H:i:s', $v['addtime']);
            if ($v['updated'] != 0) {
                $arr['rows'][$k]['updated'] =
                        date('Y-m-d H:i:s', $v['updated']);
            } else {
                $arr['rows'][$k]['updated'] = '-';
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

        $key_arr = ['price'=>0,'hand_fee'=>0,'admin_fee'=>0,'actual_price'=>0];
        $refuse_price = 0;
        foreach ($arr['rows'] as $k1 => $v1) {
            foreach ($key_arr as $k2 => $v2) {
                if (!empty($v1[$k2])) {
                    $key_arr[$k2] += $v1[$k2];
                }
            }
            if ($v1['status'] == 3) {
                $refuse_price += $v1['actual_price'];
            }
        }
        $key_arr['remark'] = '会员被扣金额：'.$key_arr['hand_fee'].'+'.$key_arr['admin_fee'].'+'.$refuse_price.'='.($key_arr['hand_fee']+$key_arr['admin_fee']+$refuse_price);
        $key_arr['agent_name'] = '笔数:'.$arr['total'];
        $key_arr['status'] = '出款笔数:'.$arr['total'];
        $arr['footer'] = [$key_arr];
        return $arr;
    }
    /********************************************/
}
