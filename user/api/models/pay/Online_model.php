<?php
/**
 * 线上支付 公共model
 * Created by PhpStorm.
 * User: shenshilin
 * Date: 2017/4/11
 * Time: 14:19
 */
class Online_model extends MY_Model
{
    public function __construct()
    {

        $this->load->model('user/Grade_mechanism_model');
        parent::__construct();
    }

    private $cash_key = "cash:count:online";//入款成功后需要添加redis 记录 hash结构 加上pay_id
    private $payset_key = 'pay:set:pay_Id:';//注意层级拼接支付设定的id
    private $frist      = 0               ;//判断用户是否是首存


    /**
     * 根据订单号获取用支付的信息
     * cash_in_online
     */
    public function order_detail($order_num)
    {
        $this->db->set_dbprefix('');
        $data = $this->db->select('a.*,b.pay_id,b.pay_server_num,c.level_id,c.level_id,c.max_income_price,max_out_price,b.pay_key,b.pay_public_key,b.pay_private_key,b.pay_server_key,b.pay_return_url')
            ->join('gc_bank_online_pay as b', 'b.id = a.online_id', 'left')
            ->join('gc_user as c', 'a.uid = c.id', 'left')
            ->where(['a.order_num'=>$order_num])
            ->get('gc_cash_in_online as a')->row_array();
        $this->db->set_dbprefix('gc_');
        return $data;
    }

    /**
     * 根据订单号去获得支付和用户的信息
    */

    /**
     * 写入线上支付表
     * @param  $order_num string 订单号
     * @param  $money     float  订单金额
     * @param  $uid       int     用户id
     * @param  $from_way  int     来源
     * @param $pay         int     支付数组相关
      * @return bool    写入是否成功
    */
    public function insert_order($order_num, $money, $uid, $from_way, $pay)
    {
        $this->select_db('private');
        $user      = $this->get_user($uid);
        $pay_set   = $this->get_pay_set($user['level_id'], false);
        if ($pay_set['code'] != OK) {
            return $pay_set;
        }

        $first          = 0;
        $pay_set        = $pay_set['data']['data'];
        //计算优惠
        $discount_price = $this->discount($money, $pay_set);
        empty($discount_price)? $is_discount = 0 : $is_discount = 1;

        $insert  = [
            'order_num'        => $order_num,           //订单号
            'uid'              => $uid,                 //用户id
            'price'            => $money,               // 存入金额
            'total_price'      => $discount_price+$money,//总金额
            'discount_price'   => $discount_price,        //优惠
            'pay_id'           => $pay['bank_o_id'],                  //支付id
            'status'           => 1 ,                    //支付状态 1：未确认，2：确认，3：取消
            'is_first'         => $first ,                    //是否首存
            'addtime'          => time() ,               //入款时间
            'is_discount'      => $is_discount,          // 优惠0：没有，1：有
            'from_way'         => $from_way,             //来源 1：ios，2：android，3：PC
            'agent_id'         => $pay['agent_id'],     //代理
            'pay_code'         => $pay['code'],
            'remark'           => $user['username'],
            'online_id'        => $pay['id']
        ];
        $data = $this->write('cash_in_online', $insert, []);
        if ($data) {
            return ['code' => OK ,'data'=>'订单写入成功'];
        } else {
            return ['code' => E_OK ,'data'=>'订单写入失败'];
        }
    }


    /**
     * 判断用户首存  并获取用户信息
    */
    public function get_user($id, $find='*')
    {
        $data = $this->get_one('*', 'user', ['id' => $id]);
        if (isset($data['max_income_price'])) {
            if ($data['max_income_price'] > 0) {
                $this->frist = 0;
            } else {
                $this->frist = 1;
            }
        }
        return $data;
    }

    /**
     * 判断用户首存  并获取用户信息
     */
    public function get_user_new($uid,$select_user)
    {
        $data = $this->db
            ->select($select_user)
            ->from('user as a')
            ->join('user_detail as b','a.id = b.uid')
            ->where(['a.id' => $uid])
            ->get()->row_array();
        if (isset($data['max_income_price'])) {
            if ($data['max_income_price'] > 0) {
                $this->frist = 0;
            } else {
                $this->frist = 1;
            }
        }
        return $data;
    }
    /**
     * 获取该层级下面的支付设定
     * $type = false 是id 为层级id true 是为会员id
     * @param id int   $id
     * @param type bool $type
     * @return  array
    */
    public function get_pay_set($id, $type=false)
    {
        if ($type) {
            //根据会员id 去获取支付设定
            $data = $this->get_one('*', 'user', ['id' => $id]);
            if (empty($data)) {
                return ['code' => E_ARGS,'msg'=>'会员id错误'];
            }
            $id = $data['level_id'];
        }
        $pay_id = $this->get_one('pay_id', 'level', ['id' => $id]);
        $pay_id = $pay_id['pay_id'];

        $redis_key = $this->payset_key.$pay_id;
        $pay_set = $this->redis_get($redis_key);
        if (empty($pay_set)) {
            $where  = [
                'a.id' => $id,
            ];
            $where2 = [
                'join'=>'pay_set',
                'on'=>'a.pay_id=b.id',
            ];
            $str  = "b.pay_set_content data,b.id";
            $temp =  $this->get_all($str, 'level', $where, $where2);

            if (empty($temp)) {
                return ['code' => E_ARGS,'msg'=>'未支付设定'];
            }
            $data         = [];
            $data['data'] = json_decode($temp[0]['data'], true);
            $data['id']   = $temp[0]['id'];
            $jsonData     = json_encode($data, JSON_UNESCAPED_UNICODE);
            $this->redis_set($redis_key, $jsonData);
            $this->redis_select(REDIS_DB);

            return ['code'=>OK ,'data' => $data ];
        }
        $this->redis_select(REDIS_DB);

        return ['code'=>OK ,'data' => json_decode($pay_set, true) ];
    }

    /**
     * 优惠计算
     * @param  $money   float 存入金额
     * @param  $pay_set array 支付设定
     * @param  $key     string 线上入款为ol 公式入款为line
     * @return  float   返回优惠金额
    */
    public function discount($money, $pay_set, $key='ol')
    {
        $youhui = 0;

        if ($pay_set["{$key}_is_give_up"]) {
            return $youhui;
        }
        //判断首存
        if ($pay_set["{$key}_deposit"] == 2) {
            if ($this->frist == 0) {
                return $youhui;
            }
        } elseif ($money < $pay_set[$key.'_discount_num']) {//判断是否达到优惠标准

            return $youhui;
        }

        $youhui = $money * $pay_set[$key.'_discount_per']/100;

        if ($youhui > $pay_set[$key.'_discount_max'] && $pay_set[$key.'_discount_max'] >0) {
            $youhui = $pay_set[$key.'_discount_max'];
        }

        return $youhui;
    }

    /**
     * 更订单信息
     * 写现金记录
     * @param  $pay_data array 用户和订单的数据
     * @return  bool
    */
    public function update_order($pay_data)
    {
        $this->load->model('Comm_model', 'comm');
        $ordernumber = $pay_data['order_num'];
        $ci =get_instance();
        if ($pay_data['status'] != 1) {
            echo $ci->echo_str;
            die;
        }
        $lock        = "temp:online:".$ordernumber;//加锁
        $bool = $this->fbs_lock($lock);
        if (!$bool) {
            return false;
        }
        $strpay      = code_pay($pay_data['pay_code']).'支付';
        ($pay_data['discount_price'] > 0)?$type=5:$type=7;

        $pay_set  = $this->get_pay_set($pay_data['level_id'], false);
        $pay_set  = $pay_set['data']['data'];
        if (empty($pay_set['online_risk'])) {
            $fengkong = 3000;
        } else {
            $fengkong = $pay_set['online_risk'];
        }

        $this->select_db('public');
            $onlineName = $this->get_one('online_bank_name', 'bank_online', ['id'=>$pay_data['pay_id']]);
        $this->select_db('private');
        
        //自动入款金额超出风控金额这停止自动入款
        if ($fengkong < $pay_data['total_price']) {

            
            $this->select_db('private');
            $this->db->update('cash_in_online', ['status'=>4], ['id'=>$pay_data['id']]);
            $ci->push(MQ_ONLINE_IN, "{$onlineName['online_bank_name']},有会员大额订单入款,请管理员审核:订单号:{$pay_data['order_num']}");
            echo $ci->echo_str;
            $this->fbs_unlock($lock);
            die;
        }



        $this->comm->db->trans_start();

        $pay_data['max_income_price']>0?$first=0:$first=1;
        $updata = [
            'price' => $pay_data['price'],
            'total_price' => $pay_data['total_price'],
            'status'   => 2,
            'is_first' => $first,
            'update_time'   => $_SERVER['REQUEST_TIME']
        ];
        $where =array(
            'order_num' => $ordernumber,
            'status'   => 1,
        );
        $bool  = $this->comm->db->update('cash_in_online', $updata, $where);
        if (!$bool) {
            $this->fbs_unlock($lock);
            $this->comm->db->trans_rollback();
            return false;
        }
        //加钱前先算好等级和积分，增加存款用户积分及晋级等级信息
        $set = $this->get_gcset(['sys_activity']);
        if (in_array(1, explode(',', $set['sys_activity']))) {
            $CI = &get_instance();
            $gradeInfo = $CI->Grade_mechanism_model->grade_doing($pay_data['uid'], $pay_data['total_price']);
            if (empty($gradeInfo['integral']) && empty($gradeInfo['vip_id'])) {
                $this->fbs_unlock($lock);
                $this->comm->db->trans_rollback();
                return false;
            }
        } else {
            $gradeInfo = ['integral' => 0, 'vip_id' => 0];
        }

        /*edit_by wuya 20180705 start*/
        if ($pay_data['discount_price'] > 0) {
            //线上入款含优惠 此时写入流水表两条记录，一条存款金额，一条优惠金额
            $type = 5;
            // 写充值金额
            $bool = $this->comm->update_banlace($pay_data['uid'], $pay_data['price'], $pay_data['order_num'], $type, $strpay, $pay_data['price'], $gradeInfo['integral'], $gradeInfo['vip_id']);
            $type1 = 11;//优惠活动
            $remark = '线上入款-存款优惠';
            // 写优惠金额
            $bool1 = $this->comm->update_banlace($pay_data['uid'], $pay_data['discount_price'], $pay_data['order_num'], $type1, $remark);
            $bool = $bool && $bool1;
        } else {
            $type = 7;
            $bool = $this->comm->update_banlace($pay_data['uid'], $pay_data['price'], $pay_data['order_num'], $type, $strpay, $pay_data['price'], $gradeInfo['integral'], $gradeInfo['vip_id']);
        }
        //开始写入现金
//        $bool = $this->comm->update_banlace($pay_data['uid'], $pay_data['total_price'], $pay_data['order_num'], $type, $strpay, $pay_data['price'], $gradeInfo['integral'], $gradeInfo['vip_id']);
        /*edit_by wuya 20180705 end*/
        if (!$bool) {
            $this->fbs_unlock($lock);
            $this->comm->db->trans_rollback();
            return false;
        }
        //查询稽核
        $bool = $this->comm->check_and_set_auth($pay_data['uid']);
        if (!$bool) {
            $this->fbs_unlock($lock);
            $this->comm->db->trans_rollback();
            return false;
        }
        //写入稽核
        $bool = $this->comm->set_user_auth($pay_data['uid'], $pay_data, 2);
        if (!$bool) {
            $this->fbs_unlock($lock);
            $this->comm->db->trans_rollback();
            return false;
        }
        $cashData['in_online_total']    = $pay_data['price'];
        $cashData['in_online_discount'] = $pay_data['discount_price'];
        if ($pay_data['discount_price'] > 0) {
            $cashData['in_online_discount_num'] = 1;
        }
        $cashData['in_online_num'] = 1;
        $cashData['agent_id'] = $pay_data['agent_id'];
        if ($first) {
            $cashData['is_one_pay'] = 1;
        }
        $report_date = date('Y-m-d', $_SERVER['REQUEST_TIME']);
        $bool = $this->comm->collect_cash_report($pay_data['uid'], $report_date, $cashData);
        if (!$bool) {
            $this->fbs_unlock($lock);
            $this->comm->db->trans_rollback();
            return false;
        }

        //层级累计金额增加
        $bool = $this->comm->incre_level_use($pay_data['price'], $pay_data['level_id']);
        if (!$bool) {
            $this->fbs_unlock($lock);
            $this->comm->db->trans_rollback();
            return false;
        }
        $str = json_encode($_REQUEST, JSON_UNESCAPED_UNICODE);
        wlog(APPPATH.'logs/online_in_'.$this->sn.'_'.date('Ym').'.log', "线上入款存入金额{$pay_data['price']},优惠{$pay_data['discount_price']},支付id{$pay_data['pay_id']} 使用参数$str");
        $ci->push(MQ_ONLINE_IN, "第三方支付:{$onlineName['online_bank_name']},已确认入款,订单号:{$pay_data['order_num']}");
        $this->comm->db->trans_complete();
        $this->comm->redis_HINCRBYFLOAT($this->cash_key, $pay_data['online_id'], $pay_data['price']);
        $this->fbs_unlock($lock);
        return true;
    }

    /**
     * 设置和获取订单提交信息
    */
    public function set_get_detailo($order, $data=[])
    {
        $kes = "temp:order_detail:$order";

        if (empty($data)) {
            return $this->redis_GET($kes);
        } else {
            return $this->redis_SETEX($kes, 3600, $data);
        }
    }
    /**
     * 产生一个唯一的确认码
    */
    public function creat_confirm()
    {
        $kes = 'temp:creat_confirm:';
        while (1) {
            $confirm = date('d').rand(0000, 9999);
            $bool = $this->redis_setnx($kes.$confirm, time());
            if ($bool) {
                //过期时间
                $gcSet = $this->get_gcset();
                $this->redis_EXPIRE($kes.$confirm, $gcSet['incompany_timeout']*60+1200);
                return (string)$confirm;
            }
        }
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

    /**
     * @param $from_way  int 来源
     * @param $pay_id    int 支付id
     * @return jsstr
     * 返回对应来源的js完成跳转
    */
    public function return_jsStr($from_way,$returnUrl=null)
    {
        $jsStr = "";
        switch ($from_way) {
            case 1:
                $jsStr = " JavaScript:window.location.href='http://www.baidu.com';";
                break;
            case 2:
                $jsStr ='if (typeof android === "object" && typeof  android.close() !== \'undefined\') {
                android.close();
            }else {
                JavaScript:window.location.href="'.$returnUrl.'";
            }';
                break;
            default:
                if (!empty($returnUrl)) {
                    $jsStr = "JavaScript:window.location.href='".$returnUrl."'";
                }else{
                    $jsStr = "window.close();";
                }
        }
        return $jsStr;
    }
}
