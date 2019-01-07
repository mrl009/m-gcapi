<?php
defined('BASEPATH') or exit('No direct script access allowed');
/**
 * 代付model基类
 */
class Agentpay_model extends MY_Model
{
    protected $allagentpay_cache_key = 'allagentpay';
    protected $error_msg = null;

    public function __construct()
    {
        parent::__construct();
    }

    public function get_pay_set($o_id)
    {
        $pset = $this->redisP_hget($this->allagentpay_cache_key,$o_id);
        if (empty($pset)) {
            $this->cache_all_agent_pay();
            $pset = $this->redisP_hget($this->allagentpay_cache_key,$o_id);
        }
        $pset = json_decode($pset,true);
        $s_pset = $this->db->select('out_domain,out_id,out_key,out_secret,out_private_key,out_public_key,out_server_key,out_server_num,min_amount,max_amount,total_amount')
            ->from('out_online_set')
            ->where('status=1')
            ->where('o_id',$pset['id'])
            ->limit(1)
            ->get()
            ->row_array();
        $pset = array_merge($pset,$s_pset);
        $callback_url = $pset['out_domain'];
        if (false === strpos($callback_url,'callback')) {
            $api = '/agentpay/' . strtolower($pset['model_name']) . '/callback';
            $callback_url .= $api ;
            $pset['out_domain'] = $callback_url;
        }
        return $pset;
    }

    public function get_apay_channels()
    {
        $pset_exists = $this->redisP_exists($this->allagentpay_cache_key);
        if (!$pset_exists) {
            $this->cache_all_agent_pay();
        }
        $s_pset = $this->db->select('o_id,min_amount,max_amount,total_amount')
            ->from('out_online_set')
            ->where('status=1')
            ->order_by('diy_order','desc')
            ->get()
            ->result_array();
        if (empty($s_pset)) {
            return [];
        }

        foreach ($s_pset as $k => &$v) {
            $pset = $this->redisP_hget($this->allagentpay_cache_key,$v['o_id']);
            if (empty($pset)) {
                unset($s_pset[$k]);
                continue;
            }
            $pset = json_decode($pset,true);
            $v = array_merge($v,$pset);
            $v['doapay_api'] = '/agentpay/' . $pset['model_name'] . '/doapay';
            $v['doquery_api'] = '/agentpay/' . $pset['model_name'] . '/doquery';
        }
        return $s_pset;

    }

    /*
     * 更新或写入代付设定
     */
    public function update_pay_set($pset)
    {
        $sql = $this->db->insert_string('out_online_set',$pset);
        $str = '';
        unset($pset['o_id']);
        foreach ($pset as $k => $v) {
            $str .= "`$k`='$v',";
        }
        $str = substr($str,0,-1);
        $sql .= " ON DUPLICATE KEY UPDATE " . $str;
        $flag = $this->db->query($sql);
        if (!$flag) {
            $db_error = $this->db->error();
            isset($db_error['message'])?'':$db_error['message'] = '';
            $log_str = '更新代付设定失败: ' . json_encode($pset,JSON_UNESCAPED_UNICODE) . "\t" . $db_error['message'];
            $this->error_msg = $log_str;
            return false;
        }
        return true;
    }

    /*
     * 查询订单信息
     * @param 订单号 订单状态
     */
    public function query_order($order_num,$status = null)
    {
        $this->db->select('a.uid,a.order_num,a.actual_price,a.status,b.bank_id,b.bank_name,b.bank_num,a.o_id,a.admin_id')
            ->from('cash_out_manage as a')
            ->join('user_detail as b','a.uid=b.uid','inner');
        if ($status) {
            $this->db->where('a.status',$status);
        }
        $order_info = $this->db->where('a.order_num',$order_num)
            ->limit(1)
            ->get()
            ->row_array();
        if (empty($order_info)) {
            return [];
        }
        if ($order_info['o_id'] > 0) {
            $apay_info = $this->db->select('o_id,status as o_status,addtime,update_time,remark')
                ->from('cash_out_online')
                ->where('order_num',$order_info['order_num'])
                ->where('o_id',$order_info['o_id'])
                ->limit(1)
                ->get()
                ->row_array();
            $order_info = array_merge($order_info,$apay_info);
        }
        return $order_info;
    }

    /*
     * 查询代付订单信息
     * @param 订单号 代付状态
     */
    public function query_apay_order($order_num,$status = null)
    {
        $this->db->select('a.uid,a.order_num,a.actual_price,a.admin_id,b.bank_name,b.bank_num,a.o_id,c.status as o_status.c.addtime,c.update_time,c.remark')
            ->from('cash_out_manage as a')
            ->join('user_detail as b','a.uid=b.uid','inner')
            ->join('cash_out_online c','a.order_num=c.order_num and a.o_id=c.o_id')
            ->where('a.order_num',$order_num);
        if ($status) {
            $this->db->where('c.status',$status);
        }
        $apay_order = $this->db->limit(1)
            ->get()
            ->row_array();
        return $apay_order;

    }


    /*
     * 发起代付  写入代付流水表 并 更新出款表o_id和o_status、remark字段
     * @param 代付信息 ['o_id','order_num','uid','price','status','addtime','admin_id']
     * @return bool
     */
    public function insert_apay($apay_info)
    {
        if (empty($apay_info['o_id']) || empty($apay_info['order_num']) || empty($apay_info['uid'])
            || empty($apay_info['price']) || empty($apay_info['status']) || empty($apay_info['addtime']) || empty($apay_info['admin_id'])) {
            $this->error_msg = '代付信息缺少参数';
            return false;
        }
        if ($apay_info['status'] == APAY_OK) {
            $apay_info['update_time'] = $apay_info['addtime'];
        }
        $this->db->trans_start();
        $sql = $this->db->insert_string('cash_out_online',$apay_info);
        $now = time();
        $sql .= " ON DUPLICATE KEY UPDATE status={$apay_info['status']},update_time=$now";
        $flag = $this->db->query($sql);
        if (!$flag) {
            $db_error = $this->db->error();
            isset($db_error['message'])?'':$db_error['message'] = '';
            $log_str = '写入代付记录失败: ' . json_encode($apay_info,JSON_UNESCAPED_UNICODE) . "\t" . $db_error['message'];
            $this->error_msg = $log_str;
            $this->db->trans_rollback();
            return false;
        }
        // 发起代付 更新出款表代付状态
        if ($apay_info['status'] == APAY_OK) {
            // 代付成功 更新出款表记录 status 状态为 已确认
            return $this->out_do($apay_info['order_num'],$apay_info['o_id']);
        } else {
            $record = [
                'updated' => $apay_info['addtime'],
                'o_id' => $apay_info['o_id'],
                'o_status' => $apay_info['status'],
                'people_remark' => '代付出款中'
            ];
        }
        $where = [
            'order_num' => $apay_info['order_num'],
            'uid' => $apay_info['uid'],
        ];
        $flag = $this->db->update('cash_out_manage',$record,$where);
        if (!$flag) {
            $db_error = $this->db->error();
            isset($db_error['message'])?'':$db_error['message'] = '';
            $log_str = '更新出款表代付状态失败: ' . $this->db->last_query() . "\t" . $db_error['message'];
            $this->error_msg = $log_str;
            $this->db->trans_rollback();
            return false;
        }
        $this->db->trans_complete();
        return true;
    }


    /*
     * 更新代付状态 同步更新 出款表 status 和 remark
     * @param 订单号 渠道id 状态 备注
     * @return bool
     */
    public function update_apay($order_num,$o_id,$status,$remark = '')
    {
        $where = [
            'o_id' => $o_id,
            'order_num' => $order_num,
        ];
        $data = [
            'status' => $status,
            'update_time' => $_SERVER['REQUEST_TIME'],
            'remark' => $remark
        ];
        $this->db->trans_start();
        $flag = $this->db->update('cash_out_online',$data,$where);
        if (!$flag) {
            $db_error = $this->db->error();
            isset($db_error['message'])?'':$db_error['message'] = '';
            $log_str = '更新代付记录表失败: ' . $this->db->last_query() . "\t" . $db_error['message'];
            $this->error_msg = $log_str;
            $this->db->trans_rollback();
            return false;
        }
        if ($status == APAY_FAIL) {
            // 代付失败 只更新出款表记录 remark 状态仍为正在出款 并置空admin_id 解除自动出款锁定
            $record = [
                'o_status' => APAY_FAIL,
                'remark' => '代付出款失败:' . $remark,
                'admin_id' => 0
            ];
            $flag = $this->db->update('cash_out_manage',$record,$where);
            if (!$flag) {
                $db_error = $this->db->error();
                isset($db_error['message'])?'':$db_error['message'] = '';
                $log_str = '更新代付记录失败: ' . $this->db->last_query() . "\t" . $db_error['message'];
                $this->error_msg = $log_str;
                $this->db->trans_rollback();
                return false;
            }
            $this->db->trans_complete();
            return true;
        } elseif ($status == APAY_OK) {
            // 代付成功 更新出款表记录 status 状态为 已确认
            $this->out_do($order_num);
        }


    }

    public function out_do($order_num,$o_id = null)
    {
        $a = 'cash_out_manage';
        $where[$a.'.order_num'] = $order_num;
        $where[$a.'.status'] = OUT_PREPARE;
        $outData = $this->db->select("$a.uid,$a.agent_id,$a.price,$a.actual_price,$a.is_pass,$a.addtime,$a.order_num,$a.remark, b.max_out_price,b.balance")
            ->join('user b', "b.id = $a.uid", 'inner')
            ->where($where)
            ->limit(1)
            ->get($a)
            ->row_array();
        if (empty($outData)) {
            $this->error_msg = '预备出款状态的订单号' . $order_num . '不存在';
            return false;
        }
        $uid = $outData['uid'];
        $this->push_str   = "代付出款成功,订单号{$order_num},提款额度{$outData['price']},实际出款金额{$outData['actual_price']}:会员id:$uid";
        $data['status']   = OUT_DO;
        if ($o_id) {
            $data['o_id']   = $o_id;
        }
        $data['o_status']   = APAY_OK;
        $data['updated']  = $_SERVER['REQUEST_TIME'];
        $data['remark'] = '银行卡转账成功';
        $data['people_remark'] = '代付出款成功';

        $b1 = $this->write('cash_out_manage', $data, $where);
        if ($b1) {
            /*更新用户最大出款数*/
            if ($outData['actual_price'] > $outData['max_out_price']) {
                $bool = $this->db->update('user', ['max_out_price'=>$outData['actual_price']], ['id'=>$uid]);
                if (!$bool) {
                    $this->db->trans_rollback();
                    $this->error_msg = '更新用户最大出款数失败';
                    return false;
                }
            }

            /*清除所有稽核*/
            $is_w_dml = $this->get_w_dml($outData['uid']);
            $this->clear_w_dml($outData['uid'], $outData['actual_price'], $is_w_dml);
            if (!$is_w_dml) {
                $time = $this->get_del_out_user($outData['uid']);
                $b    = $this->out_clear_auth($outData['uid'], '确认出款清除稽核', $time);
                if (!$b) {
                    $this->db->trans_rollback();
                    $this->error_msg = '确认出款清除稽核失败';
                    return false;
                }
            }

            /*写入日结报表 实际出款金额*/
            $cashData['out_company_total'] = $outData['actual_price'];
            $cashData['out_company_num'] = 1;
            $cashData['agent_id'] = $outData['agent_id'];
            $cashData['report_date'] = date('Y-m-d', $data['updated']);
            $cashData['uid'] = $outData['uid'];
            if ($outData['price'] - $outData['actual_price'] > 0) {
                $cashData['in_member_out_deduction'] = $outData['price'] - $outData['actual_price'];
                $cashData['in_member_out_num'] = 1;
            }
            $sql = $this->db->insert_string('cash_report', $cashData);
            $sql .= " ON DUPLICATE KEY UPDATE out_company_total=out_company_total+{$cashData['out_company_total']},out_company_num=out_company_num+1";
            if (isset($cashData['in_member_out_deduction'])) {
                $sql .= ",in_member_out_deduction=in_member_out_deduction+{$cashData['in_member_out_deduction']},in_member_out_num=in_member_out_num+1";
            }
            $bool = $this->db->query($sql);
            if (!$bool) {
                $this->db->trans_rollback();
                $this->error_msg = '写入现金报表失败';
                return false;
            }
            $this->out_unlock($outData['uid']);
            $this->get_del_out_user($outData['uid'], false);

            //通过稽核  累计
            if ($outData['is_pass'] == 1) {
                $rkUserCounterNum = 'user_count:counter_num:'.$outData['uid'];
                $bool = $this->redis_setnx($rkUserCounterNum,1);
                if ($bool) {
                    $this->redis_expire($rkUserCounterNum,OUT_BOUNODS_TIME);
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
            $this->error_msg = '更新出款记录表失败';
            return false;
        }
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
        if ($money > 0 && $is_w_dml) {
            $this->redis_hset('user:win_dml', $uid, $money);
        } else {
            $this->redis_hdel('user:win_dml', $uid);
        }
        $this->redis_select(REDIS_DB);
    }

    protected function cache_all_agent_pay()
    {
        $this->select_db('public');
        $pays = $this->db->select('*')
            ->from('out_online')
            ->where('status=1')
            ->get()
            ->result_array();
        $pays = array_make_key($pays,'id');
        array_walk($pays,function(&$v){$v=json_encode($v,JSON_UNESCAPED_UNICODE);});
        $this->redisP_del($this->allagentpay_cache_key);
        $this->redisP_hmset($this->allagentpay_cache_key,$pays);
        $this->select_db('private');
    }

    public function get_error()
    {
        return $this->error_msg;
    }

}