<?php
if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}
class Pay_set_model extends MY_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    public $is_confirm=0;
    public function index()
    {
    }
    public function show_data($id =null)
    {
        $str = 'id,pay_name';
        $where = [];
        if ($id) {
            $where['id'] = $id;
            $str = '*';
        }
        $page   = array(
            'page'  => $this->G('page'),
            'rows'  => $this->G('rows'),
            'order' => 'asc',
            'sort'  => 'id',
            'total' => -1,
        );

        $arr = $this->get_list($str, 'pay_set', $where, [], $page);
        if ($id) {
            $arr['rows'][0]['pay_set_content'] = json_decode($arr['rows'][0]['pay_set_content'], true);
        }
        $arrx['rows'] = $arr['rows'];
        $arrx['total'] = $arr['total'];
        return ['status'=>OK,'data'=>$arrx];
    }



    /**
     * @param $id int 公司入款id
     * @param $money int 存入金额
     * @param  $min_quota 最小入款
     * @return  array code  msg
    */
    public function check_bank_quota($id, $money, $min_quota)
    {
        $key = "cash:count:bank_card";
        $res  = [];
        $res['code'] = false;
        $res['msg']  = '获取平台限额失败';
        $data = $this->get_one('max_amount,is_confirm', 'bank_card', array('id'=>$id));
        if ($data) {
            $this->is_confirm = (int)$data['is_confirm'];
            $res['code'] = true;
            //获取已使用的额度
            $quota = $this->redis_HGET($key, $id);
            if ($quota+$money > $data['max_amount']) {
                $a = $data['max_amount'] - $quota;
                $res['code'] = false;
                $res['msg']  = "该支付方式剩余额度{$a}";
                //已用额度加上最小入款 大于限额则停用

                if ($quota+$min_quota > $data['max_amount']) {
                    $update = [
                        'status'=>2
                    ];
                    $where   = [
                        'id' => $id
                    ];
                    $this->db->update('bank_card', $update, $where);
                    $this->redis_del($key, $id);
                    $res['code'] = true;
                    $res['msg'] = '支付方式达到最大限额,请更换支付方式';
                }
            } elseif ($quota+$money == $data['max_amount']) {
                /*$update = [
                    'status'=>2
                ];
                $where   = [
                    'id' => $id
                ];
                $this->db->update('bank_card', $update, $where);*/
                $res['code'] = true;
            }
        }
        return $res;
    }


    /**
     * 根据用户的uid 去获银行信息
     */
    public function user_bank($uid)
    {
        $data     = $this->get_one('bank_num,bank_id,address,bank_pwd,bank_name name', 'user_detail', ['uid' => $uid]);
        $bankData = $this->base_bank_online('bank');
        if (empty($data['bank_id'])) {
            return false;
        }
        empty($bankData['bank_name'])?$bankname = $bankData[$data['bank_id']]['bank_name']:$bankname="";
        $data['bank_name'] = $bankname;
        $data['img']       = $bankData[$data['bank_id']]['img'];
        return $data;
    }
    /**
     * 删除支付设定
    */
//    public function pay_del($pay_id=null){
//
//        $data = $this->get_one('*','level',['pay_id'=>$pay_id]);
//        if ($data) {
//            return ['status'=>E_ARGS,'data'=>'该支付设定使用中'];
//        }
//        $bool = $this->db->delete('pay_set',['id'=>$pay_id]);
//        if ($bool){
//            return ['status'=>OK,'data'=>'操作成功'];
//        }
//        return ['status'=>E_OK,'data'=>'操作失败'];
//
//    }

    /**
     * 获取一个会员的支付设定信息
     * @param    $uid int   会员ID
     * @param  $field string 要查找的字段
     * return array
     */
    public function get_pay_set($uid=0, $field='*')
    {
        $where['user.id'] = $uid;
        $this->db->where($where);
        $condition['join'] =
            array(
                array('table'=>'level as l','on'=>'user.level_id = l.id'),
                array('table'=>'pay_set as ps','on'=>'ps.id = l.pay_id'),
            );
        $data = $this->get_one($field, 'user', $where, $condition);
        if (empty($data)) {
            return false;
        }
        $payData = json_decode($data['pay_set_content'], true);
        return array_merge($payData, $data);
    }

    


    /**
     * 出款 reids 加锁
     */
    public function set_out_lock($id, $order=null)
    {
        $kye = 'user:out_lock:'.$id;
        if (!empty($order)) {
            $bool =  $this->redis_setnx($kye, $order);
            if ($bool) {
                $gcSet = $this->get_gcset(['incompany_timeout']);
                $this->redis_EXPIRE($kye, $gcSet['incompany_timeout']*60);
                return true;
            } else {
                return false;
            }
        } else {
            return $this->redis_get($kye);
        }
    }
     /**
      * 解锁出款
     */

    public function del_out_lock($uid)
    {
        $kye = 'user:out_lock:'.$uid;
        return $this->redis_del($kye);
    }


    /**
     * 公司入款加锁
     */
    public function del_in_lock($id, $order_num)
    {
        $kye = 'user:in_company:'.$id;
        $arr = $this->redis_get($kye);
        $arr = json_decode($arr, true);
        if (empty($arr)) {
            return false;
        }
        foreach ($arr as $key => $value) {
            if ($order_num == $value['order_num']) {
                unset($arr[$key]);
                break;
            }
        }
        if (empty($arr) || count($arr) == 0) {
            return $this->redis_del($kye);
        } else {
            $gcSet = $this->get_gcset(['incompany_timeout']);
            $expire = $gcSet['incompany_timeout']*60;
            $this->redis_set($kye, json_encode($arr));
            $this->redis_EXPIRE($kye, $expire);
            return true;
        }
    }

    /**
     * 公司入款加锁
     */
    public function set_in_lock($id, $order=null)
    {
        $kye = 'user:in_company:'.$id;
        $lock = 'user:in_lock:'.$id;

        if (!empty($order)) {
            $bool = $this->redis_setnx($lock, $order);
            if ($bool) {
                $this->redis_EXPIRE($lock, CASH_REQUEST_TIME);
            } else {
                return false;
            }
        }
        $gcSet = $this->get_gcset();
        if (!empty($order)) {
            $arr = $this->redis_get($kye);
            if (!empty($arr)) {
                $arr = json_decode($arr, true);
                foreach ($arr as $key => $value) {
                    if ($value['expire'] <= time()) {
                        unset($arr[$key]);
                    }
                }
                if (count($arr) >= $gcSet['incompany_count']) {
                    return false;
                }
            }
            $expire = $gcSet['incompany_timeout']*60;
            $arr[] = ['order_num'=>$order, 'expire'=>time()+$expire];
            $bool = $this->redis_set($kye, json_encode($arr));
            $this->redis_EXPIRE($kye, $expire);
            return true;
        } else {
            $arr = $this->redis_get($kye);
            return json_decode($arr, true);
        }
    }
}
