<?php
if (! defined('BASEPATH')) exit('No direct script access allowed');

class Base_pay_model extends MY_Model
{
    //设置 各数据表
    private $utb = 'user'; //用户信息表
    private $ltb = 'level'; //层级信息表
    private $stb = 'pay_set'; //支付设定表
    private $btb = 'bank_card'; //银行入款表
    private $ftb = 'bank_fast_pay'; //直通车入款表
    private $otb = 'bank_online_pay'; //在线支付入款表
    private $bltb = 'level_bank'; //银行入款层级表
    private $fltb = 'level_bank_fast'; //直通车层级表
    private $oltb = 'level_bank_online'; //在线支付层级表
    private $ocotb = 'cash_in_online';//線上入款數據表
    private $occtb = 'cash_in_company';//公司入款數據表

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @根据当前登录用户ID 获取用户信息
     * @param uid 用户ID 
     * @return info 用户信息
     */
    public function get_user_info($uid)
    {
        //获取用户信息,
        $field = 'level_id,max_income_price';
        $where['id'] = $uid;
        $info = $this->get_one($field,$this->utb,$where);
        if (empty($info))
        {
            return ['code' => E_ARGS,'msg'=>'登錄失效,請重新登錄'];
        }
        //赋值当前用户的信息
        $this->level_id = $info['level_id']; //当前用户层级
        $this->first = !empty($info['max_income_price']) ? 0 : 1; //是否首冲
        return $info;
    }

    /**
     * @根据根据支付ID、层级ID获取层级支付信息
     * @param pay_id 支付ID
     * @return info 当前支付方式的支付信息
     */
    public function get_fast_info($pay_id)
    {
        $field = 'a.*';
        $where['a.status'] = 1;
        $where['a.id'] = $pay_id;
        $where['b.level_id'] = $this->level_id;
        $where2['join'] = array(
            array('table' => $this->fltb .' AS b', 'on' => 'a.id = b.fast_id')
        );
        $info = $this->get_all($field,$this->ftb,$where,$where2);
        if (!empty($info[0])) $info = $info[0];
        //赋值支付数据
        $this->pay_id  = $pay_id;  //方式支付方式ID(当前入款商户ID)
        $this->block_key = 'online'; //存款限额对应的rediskey
        $this->spk = 'ol'; //支付設定表中 標識前綴 线上入款为ol 公式入款为line
        return $info;
    }


    /**
     * @把订单信息写入对应的订单表
     * @param pay_id 支付ID
     * @param type 支付类型 公司入款 线上支付等
     */
    public function insert_order($order_data)
    {
        //赋值金额
        $this->money = $order_data['price'];
        //優惠金額
        $dm = $this->get_discount_amount();
        $is_discount = !empty($dm) ? 1 : 0;
        $total_price = ($this->money + $dm);
        //訂單參數重置
        $order_data['total_price'] = $total_price;
        $order_data['discount_price'] = $dm;
        $order_data['is_first'] = $this->first;
        $order_data['is_discount'] = $is_discount;
        //寫入數據表
        $inset_id = $this->write($this->ocotb,$order_data,[]);
        if (empty($inset_id))
        {
            return ['code' => E_OK ,'data'=>'订单写入失败'];
        }
    }

    /**
     * 根据订单号获取用支付的信息 (快速直通车)
     * cash_in_online
     */
    public function order_detail_fast($merch,$order_num)
    {
        //设置查询的字段名称
        $af = 'a.id AS pay_id,a.platform_name AS name,a.merch,a.pay_key,a.pay_private_key';
        $af .= ',a.pay_server_key,a.validate_ip';
        $bf = 'b.uid,b.order_num,b.price,b.total_price';
        $bf .= ',b.discount_price,b.status,b.agent_id,b.online_id';
        $field = "{$af},{$bf}";
        //设置查询条件
        $where['a.merch'] = $merch;
        $where['b.order_num'] = $order_num;
        $where['b.pay_serve_type'] = 'fast';
        $where2['join'] = array(
            array('table' => $this->ocotb .' AS b', 'on' => 'a.id = b.online_id')
        );
        $info = $this->get_all($field,$this->ftb,$where,$where2);
        if (!empty($info[0])) $info = $info[0];
        $this->block_key = 'online';
        return $info;
    }

    /**
     * 更订单信息
     * 写现金记录
     * @param  $pay array 用户和订单的数据
     * @return  bool
    */
    public function update_order($pay)
    {
        $ci = get_instance();
        $this->load->model('Comm_model', 'CM');
        $order_num = $pay['order_num'];
        //订单加锁
        $lock = "temp:online:{$order_num}";//加锁
        $bool = $this->fbs_lock($lock);
        if (!$bool) return false;
        $this->get_user_info($pay['uid']);
        //设置 更新 cash_in_online 订单表的条件
        $where = array(
           'order_num' => $order_num,
           'status' => 1
        );
        //获取层级支付设置信息 (风控金额)
        $pay_set = $this->get_pay_set();
        if (!empty($pay_set['online_risk']))
        {
            $risk_money = $pay_set['online_risk'];
        } else {
            $risk_money = 3000;
        }
        //超过风控金额
        if ($risk_money < $pay['total_price']) 
        {
            $update['status'] = 4;
            $this->db->update($this->ocotb, $update, $where);
            $msg = "快速支付通道：{$pay['name']},有会员大额订单入款";
            $msg .= ",请管理员审核:订单号:{$order_num}";
            $ci->push(MQ_ONLINE_IN, $msg);
            $this->fbs_unlock($lock);
            exit('订单金额超过风控金额请手动入款');
        }
        //开启事物  执行用户账户等操作
        $this->CM->db->trans_start();
        //更新订单表
        $updata = [
            'price' => $pay['price'],
            'total_price' => $pay['total_price'],
            'status' => 2,
            'pay_code' => $pay['pay_code'],
            'update_time' => $_SERVER['REQUEST_TIME']
        ];
        $bool  = $this->CM->db->update($this->ocotb, $updata, $where);
        if (!$bool)
        {
            $this->fbs_unlock($lock);
            $this->CM->db->trans_rollback();
            return false;
        }
        /**
         * 更新用户余额 积分等
         * @更新前先算好等级和积分，增加存款用户积分及晋级等级信息
         * type 现金流水类型 5 線上入款有优惠  7 線上入款不含優惠
         * strpay 支付方式备注说明
         */
        $type = (0 < $pay['discount_price']) ? 5 : 7; //现金流水类型
        $strpay = code_pay($pay['pay_code']) . '支付'; //现金流水备注说明
        //获取用户积分详情
        $set = $this->get_gcset(['sys_activity']);
        if (in_array(1, explode(',', $set['sys_activity'])))
        {
            $CI = &get_instance();
            $gradeInfo = $CI->Grade_mechanism_model->grade_doing($pay['uid'], $pay['total_price']);
            if (empty($gradeInfo['integral']) && empty($gradeInfo['vip_id'])) 
            {
                $this->fbs_unlock($lock);
                $this->CM->db->trans_rollback();
                return false;
            }
        } else {
            $gradeInfo = ['integral' => 0, 'vip_id' => 0];
        }
        //更新用户余额等信息
        $bool = $this->CM->update_banlace($pay['uid'],$pay['total_price'],$pay['order_num'], $type, $strpay, $pay['price'], $gradeInfo['integral'], $gradeInfo['vip_id']);
        if (!$bool)
        {
            $this->fbs_unlock($lock);
            $this->CM->db->trans_rollback();
            return false;
        }
        //用户稽核操作
        $bool = $this->CM->set_user_auth($pay['uid'], $pay, 2);
        if (!$bool)
        {
            $this->fbs_unlock($lock);
            $this->CM->db->trans_rollback();
            return false;
        }
        //写入报表数据
        $cashData['in_online_total']    = $pay['price'];
        $cashData['in_online_discount'] = $pay['discount_price'];
        if (0 < $pay['discount_price']) 
        {
            $cashData['in_online_discount_num'] = 1;
        }
        if (!empty($this->first)) $cashData['is_one_pay'] = 1;
        $cashData['in_online_num'] = 1;
        $cashData['agent_id'] = $pay['agent_id'];
        $r_date = date('Y-m-d', $_SERVER['REQUEST_TIME']);
        $bool = $this->CM->collect_cash_report($pay['uid'], $r_date, $cashData);
        if (!$bool)
        {
            $this->fbs_unlock($lock);
            $this->CM->db->trans_rollback();
            return false;
        }
        //更新层级累计金额
        $bool = $this->CM->incre_level_use($pay['price'], $this->level_id);
        if (!$bool)
        {
            $this->fbs_unlock($lock);
            $this->CM->db->trans_rollback();
            return false;
        }
        //写入日志
        $str = json_encode($_REQUEST, JSON_UNESCAPED_UNICODE);
        $log = APPPATH.'logs/online_in_'.$this->sn.'_'.date('Ym').'.log';
        $msg = "线上入款存入金额{$pay['price']},优惠{$pay['discount_price']}";
        $msg .= ",支付id{$pay['pay_id']} 使用参数{$str}";
        wlog($log, $msg);
        $msg = "快速支付通道:{$pay['name']},已确认入款";
        $msg = ",订单号:{$pay['order_num']}";
        $ci->push(MQ_ONLINE_IN,$msg);
        //事物结束
        $this->CM->db->trans_complete();
        //redis保存的key值
        $key = $this->block_key;
        $key = "cash:count:{$key}";
        $this->comm->redis_HINCRBYFLOAT($key, $pay['online_id'], $pay['price']);
        $this->fbs_unlock($lock);
        return true;
    }

    /**
     * @层级ID获取 获取支付设定信息
     * @param level_id 当前用户层级ID
     * @return info 当前支付优惠设置信息信息
     */
    public function get_pay_set()
    {
        $field = 'b.*';
        $where['a.id'] = $this->level_id;
        $where2['join'] = array(
            array('table' => $this->stb .' AS b', 'on' => 'a.pay_id = b.id')
        );
        $info = $this->get_all($field,$this->ltb,$where,$where2);
        if (empty($info)) 
        {
           return ['code' => E_ARGS,'msg'=>'未設置支付設定,無法充值'];
        }
        if (!empty($info[0]['pay_set_content']))
        {
            $info = json_decode($info[0]['pay_set_content'],true);
        }
        return $info;
    }

    /**
     * @根据当前用户层级获取优惠金额信息
     * @param level_id 当前用户层级ID
     * @param money 充值金额
     */
    private function get_discount_amount()
    {
        $spk = $this->spk;
        $discount_money = 0;
        $pay_set = $this->get_pay_set();
        //返回優惠金額 跟據優惠政策
        /**
         * @以下幾種情況講無法獲取得優惠
         * 1、放棄優惠
         * 2、只有首充優惠 用戶并非首充
         * 3、充值金額未達到優惠標準
         */
        $gv = $pay_set["{$spk}_is_give_up"]; //是否放棄優惠
        $ft = $pay_set["{$spk}_deposit"]; //是否首存優惠
        $dm = $pay_set["{$spk}_discount_num"]; //優惠標準金額
        $dp = ($pay_set["{$spk}_discount_per"]/100); //優惠百分比(%)
        $dx =  $pay_set["{$spk}_discount_max"]; //優惠上限金額  
        //跟據優惠政策 返回最終優惠金額
        if (!empty($gv) || ($this->money < $dm) ||
           ((2 == $ft)  && (0 == $this->frist)))
        {
            return $discount_money;
        }
        $discount_money = (($this->money) * $dp);
        //判斷是否優惠已達到最高上限金額
        if ((0 < $dx) && ($discount_money > $dx))
        {
            return $dx;
        }
        return $discount_money; 
    }

    /**
     * @某已支付达到条件自动停用
     * @param pay_id 支付ID
     * @param type 支付类型 公司入款 线上支付等
     */
    public function stop_fast($pay_id)
    {
        $sdata['status'] = 2;
        $where['id'] = $this->pay_id;
        $this->db->update($this->ftb,$sdata,$where);
    }

    /**
     * 设置和获取订单提交信息
    */
    public function set_get_detailo($order, $data=[])
    {
        $kes = "temp:order_detail:{$order}";
        if (empty($data)) {
            return $this->redis_GET($kes);
        } else {
            return $this->redis_SETEX($kes, 3600, $data);
        }
    }

    /**
     * @获取redis中记录的当前支付的已使用的额度
     * @param pay_id 支付ID
     * @param type 支付类型 公司入款 线上支付等
     * @return used_amount 已使用的额度
     */
    public function get_block_amount()
    {
        $key = $this->block_key;
        $pay_id = $this->pay_id;
        //redis保存的key值
        $key = "cash:count:{$key}";
        $used_amount = $this->redis_hget($key,$pay_id);
        return $used_amount ? $used_amount : 0;
    }

    /**
     * @清空redis中已使用该支付方式的已使用的额度
     * @param pay_id 支付ID
     * @param type 支付类型 公司入款 线上支付等
     */
    public function delet_block_amount()
    {
        $key = $this->block_key;
        $pay_id = $this->pay_id;
        //redis保存的key值
        $key = "cash:count:{$key}";
        $this->redis_del($key,$pay_id);
    }

    /**
     * "online:erro:";//线上入款错误记录
     * @param $id 线上支付的id
     * @param $str 错误信息
     */
    public function online_erro($id, $str)
    {
        $reidsKey = "online:erro:";
        $this->redis_select(4);
        $this->redis_setex($reidsKey.$id, 90000, $str);
        $this->redis_select(5);
    }




}
