<?php
/**
 * .
 * 支付接口调用model 末班
 * User: shenshilin
 * Date: 2017/4/10
 * Time: 15:02
 */
class Card_model extends MY_Model
{
    private $tbName = "card_";//表明前缀
    private $pwde   = "5";   //密码错误次数
    //private $rkeys  = "card:";//redis键
    private $userIp = "";
    /**
     * 构造函数
     *
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 创建点卡数据
    */
    public function creat()
    {
        ini_set('max_execution_time', '0');
        ini_set('memory_limit', '3000M');
        $this->db->query('use gc_card');
        $this->db->query("SET AUTOCOMMIT=0");
        $o= 0;
        $order = '17040001';
        for ($i=0;$i<=200;$i++) {
            $sql = "INSERT INTO `gc_card_1704` (`pwd`, `price`, `is_used`) VALUES ({$order}, 20, '2')";
            $order ++;
            $this->db->query($sql);
            $o++;
            if ($o > 10000) {
                $o=0;

            }
        }
        $this->db->query("commit");
    }


    /**
     * 点卡充值
     * 表名  tbName . 卡号前4 位
     *fbs_lock()redis分布锁
     * fbs_unlock() 解锁
     *
     *
    */
    public function card_doing($data, $uid, $from_way)
    {
        $user_ip = get_ip();
        $this->userIp = $user_ip;
        $redis_key = "card:user:$uid";
        $lock = "lock:card:".$data['card_pwd'];
        $site    = $this->get_gcset();
        if (empty($site)) {
            return $this->status(E_ARGS, "获取站点信息失败");
        }
        //验证点卡是否开启
        $x = $this->check($site, $uid);
        if ($x['status'] != OK) {
            return $x;
        }

        $this->select_db("private");
        $userData = $this->get_one('is_card,username,id,level_id,max_income_price', 'user', ['id'=>$uid]);
        if ($userData['is_card'] != 1) {
            return $this->status(E_ARGS, "彩豆充值被锁定请联系客服");
        }
        $tbname  = $this->tbName.substr($data['card_pwd'], 0, 4);

        $this->select_db('card');
        $xx= $this->db->query("show tables like 'gc_{$tbname}'")->row();
        if (!$xx) {
            return ['status'=>E_ARGS,'msg'=>'错误的卡密'];
        }

        $where = [
            'pwd' => $data['card_pwd']
        ];
        $cardData = $this->get_one("", $tbname, $where);

        if (empty($cardData)) {
            $this->redis_HINCRBY($redis_key, 'erro', 1);
            return $this->status(E_ARGS, '卡号错误');
        }
        if ($cardData['is_used'] == 1) {
            return $this->status(E_ARGS, '卡号已经使用');
        }
        $ci = get_instance();

        //验证通过开始执行入库 点卡type   15
        $bool = $this->fbs_lock($lock); //加锁
        if (!$bool) {
            return $this->status(E_ARGS, '系统繁忙1');
        }


        //开启事务
        $this->db->trans_begin();
        $this->select_db('card');
        $updata = [
            'is_used'   => 1,
            'order_num' => $data['order_num'],
            'uid'       => $uid,
            'agent_id'  => $ci->user['agent_id'],
            'username'  => $userData['username'],
            'use_time'  => time(),
            'from_way'  => $from_way
        ];
        $bool = $this->db->update($tbname, $updata, ['pwd'=> $data['card_pwd'], 'is_used' =>2]);
        if (!$bool) {
            return $this->status(E_ARGS, '充值失败');
        }
        $this->redis_HINCRBY($redis_key, 'user', 1);
        $ipData = $this->get_one('', 'black_list_ip', [ 'ip'=>$this->userIp , 'uid' => $uid]);

        if ($ipData) {
            $sql = "update gc_black_list_ip set ip_count = ip_count+1  , time =".time()." where ip = ".$this->userIp;
            $bool = $this->db->query($sql);
        } else {
            $inset = [
                'ip'        => $this->userIp,
                'ip_status' => 1,
                'ip_count'  => 1,
                'uid'       => $uid,
                'time'      => time(),
            ];
            $bool = $this->db->insert('black_list_ip', $inset);
        }
        if (!$bool) {
            $this->db->trans_rollback();
            return $this->status(E_ARGS, '充值失败');
        } else {
            $this->db->trans_commit();

        }

        $this->load->model('Comm_model', 'comm');
        $this->select_db('private');
        $this->comm->db->trans_begin();//开启事务
        //加钱
        $bool = $this->comm->update_banlace($uid, $cardData['price'], $data['order_num'], 15, '彩豆充值');
        if (!$bool) {
            $this->comm->db->trans_rollback();
            $this->status(E_ARGS, '操作失败1');
        }
        //查询会员稽核
        $bool = $this->comm->check_and_set_auth($uid);
        if (!$bool) {
            $this->comm->db->trans_rollback();
            $this->status(E_ARGS, '操作失败2');
        }


        //写入会员
        $auth =[];
        $auth['total_price']    = $cardData['price'];
        $auth['price']          = 0;
        $auth['discount_price'] = $cardData['price'];

        $bool = $this->comm->set_user_auth($uid, $auth, 1);
        if ($bool['status'] == false) {
            $this->comm->db->trans_rollback();
            $this->status(E_ARGS, '操作失败3');
        }

        //写入报表
        $cashData = [
            'in_card_total' => $cardData['price'],
            'in_card_num'   => 1,
            'agent_id'      => $ci->user['agent_id']
        ];
        if ($userData['max_income_price'] <= 0) {
            //$cashData['is_one_pay'] =1;
        }
        $bool = $this->comm->collect_cash_report($uid, date('Y-m-d'), $cashData);
        if (!$bool) {
            $this->comm->db->trans_rollback();
            $this->status(E_ARGS, '操作失败4');
        }
       /* $bool = $this->comm->incre_level_use($cardData['price'],$userData['level_id']);
        if(!$bool){
            $this->comm->db->trans_rollback();
            $this->status(E_ARGS,'操作失败5');
        }*/
        $this->comm->db->trans_commit();
        return  $this->status(OK, $cardData['price']);

    }

    /**
     * 检查会员是否有优惠卡充值
    */
    public function check($data, $uid)
    {
        $key = "card:user:$uid";
        $usernum  =  $this->redis_hget($key, "user");
        $usererro =  $this->redis_hget($key, "erro");

        if ($usernum > $data['user_card_cishu']-1) {
            return $this->status(E_ARGS, '彩豆充值次数上限');
        }
        if ($data['card_status'] != 1) {
            return $this->status(E_ARGS, '站点为开启优彩豆充值');
        }

        //如果充值卡密码错误次数达到上限 这停用改用户的点卡充值
        if ($usererro >= $this->pwde) {
            $this->select_db('private');
            $bool = $this->db->update('user', ['is_card'=>2], ['id'=>$uid]);
            if ($bool) {
                $this->redis_del($key, "erro");
                return $this->status(E_ARGS, "卡号错误次数太多已经被锁定");
            } else {
                return $this->status(E_ARGS, '请重试');
            }
        }
        $this->select_db('card');
        $where = [
            'ip'  => $this->userIp,
            //'uid' => $uid
        ];
        $ipObj  = $this->db->where($where)->get('black_list_ip')->row();
        if (!empty($ipObj)) {
            if ($ipObj->ip_status == 2) {
                return $this->status(E_ARGS, 'ip被锁定');
            }
            if ($ipObj->ip_count >= $data['ip_cishu']) {
                return $this->status(E_ARGS, 'ip充值上限');
            }
        }

        return $this->status(OK, '验证通过');
    }

}
