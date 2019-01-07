<?php
/**
 * 会员model
 * Created by PhpStorm.
 * User: shenshilin
 * Date: 2017/3/25
 * Time: 18:45
 */
class User_model extends MY_Model
{

    public $member   ;//会员数据
    /**
     * 构造函数
     */
    public function __construct()
    {
        parent::__construct();
    }
    /**
     * 层级判断
     * 根据用户注册的时间去判断用户所属的层级
     *
    */
    public function judge_level()
    {
        $where  = [
            'join_start_time <' =>time(),
            'join_end_time >'   =>time()
        ];
        $where2 = [];
        $arr = $this->get_one('id', 'level', $where, $where2);
        if (empty($arr)) {
            $where = [];
            $where['is_default'] = 1;
            $arr = $this->get_one('id', 'level', $where, $where2);
        }
        return $arr['id'];
    }

    /**
     * 添加会员
    */
    public function user_add($data, $addip)
    {
        $this->db->trans_start();
        $bank_name = isset($data['bank_name']) ? $data['bank_name'] : '';
        unset($data['bank_name']);
        $this->write('user', $data);
        $uid  = $this->db->insert_id();
        if (!empty($data['agent_id'])) {
            $where = [
                'id' => $data['agent_id'],
                'type' => 2,
            ];
            $bool = $this->db->set('off_sum', 'off_sum+1', false)->update('user', [], $where);
            if (!$bool) {
                return $this->db->trans_rollback();
            }
        }
        $bool = $this->db->set('user_num', "user_num+1", false)->where(['id'=>$data['level_id']])->update('level');
        if (!$bool) {
            return $this->db->trans_rollback();
        }
        $data = [
            'uid'   => $uid,
            'addip' => $addip,
            'bank_name' => $bank_name
        ];
        $bool = $this->write('user_detail', $data);

        if ($bool) {
            $this->db->trans_complete();
            return $uid;
        } else {
            return $this->db->trans_rollback();
        }
    }

    /**
     * 查询启用的银行
    */
    public function bank_list()
    {
        $where['is_qcode'] = "'0'";
        $where['status']   = 1;
        $this->User_model->select_db('public');
        $bank = $this->User_model->get_all('id,bank_name,img,is_qcode', 'bank', $where);
        $this->User_model->select_db('');
        return $bank;
        /*$bank = array_combine(array_column($bank,'id'),array_column($bank,'bank_name'));
        print_r($bank);

        $where  = [
            'b.level_id'=>$level_id,
            'a.status'  => 1
        ];
        $where2 = [
            'join' => 'level_bank',
            'on'   =>'a.id=b.card_id',
        ];
        $str = 'a.bank_o_id';
        $this->get_all($str,'bank_card',$where,$where2);*/
    }

    /**
     * 注册优惠
     * @param $uid int 会员id
     * @param $username str 会员名
     * @param $userOrBank bool fasle 为注册送优惠  true 为绑定银行卡送优惠
     * 6 为绑定银行卡送优惠  7 为支付宝  8 为微信
    */
    public function zhuceyouohui($uid, $username, $userOrBank,$agent_id=null)
    {
        $ci    = get_instance();
        $gcSet = $this->get_gcset();
        if (!empty($gcSet['register_discout']) && !empty($gcSet['register_discount_from_way'])) {
            $discountFrom = explode(',', $gcSet['register_discount_from_way']);
            if ($userOrBank) {
                $bool =   in_array($userOrBank, $discountFrom);
            } else {
                if (empty(array_intersect($discountFrom,[6,7,8]))) {
                    $bool = true;
                }else{
                    $bool = false;
                }
            }

            if (in_array($ci->from_way, $discountFrom) && $bool) {
                $money = $gcSet['register_discout'];
                $this->load->model('Comm_model', 'comm');
                $order  = order_num(1, 99);
                $where  = [
                    'uid'  => $uid,
                    'type' => 11
                ];
                $data = $this->get_one('','cash_list',$where);
                if (!empty($data)) {
                    return true;
                }
                //写入现金记录开始
                $this->comm->db->trans_begin();//开启事务
                //加钱
                switch ($userOrBank) {
                    case 6:
                        $str = "绑定银行卡送优惠";
                        break;
                    case 7:
                        $str = "绑定支付宝送优惠";
                        break;
                    case 8:
                        $str = "绑定微信送优惠";
                        break;
                    default:
                        $str = '注册送优惠';
                }

                $bool = $this->comm->update_banlace($uid, $money, $order, 11, $str);
                if ($bool == false) {
                    $this->comm->db->trans_rollback();
                    $this->status(E_ARGS, '操作失败1');
                }
                //查询会员稽核
                $bool = $this->comm->check_and_set_auth($uid);
                if ($bool == false) {
                    $this->comm->db->trans_rollback();
                    $this->status(E_ARGS, '操作失败2');
                }
                //写入会员
                $auth = [];
                $auth['total_price']    = $money;
                $auth['price']          = 0;//$money;
                $auth['discount_price'] = $money;
                $bool = $this->comm->set_user_auth($uid, $auth, 1);
                if ($bool == false) {
                    $this->comm->db->trans_rollback();
                    $this->status(E_ARGS, '操作失败3');
                }
                //写入报表
                $cash_data =  [
                    'in_register_discount' => $money,
                    'uid' => $uid,
                    'agent_id' => $agent_id,
                ];
                $bool = $this->comm->collect_cash_report($uid, date('Y-m-d'),$cash_data);

                if ($bool == false) {
                    $this->comm->db->trans_rollback();
                } else {
                    wlog(APPPATH.'logs/zuceyouhui_'.$this->sn.'_'.date('Ym').'.log', $username."$str $money 元");
                    $this->comm->db->trans_commit();
                    $ci->push(MQ_COMPANY_RECHARGE, "会员{$username}{$str}'.$money.'已到账",$order);
                }
            }
        }
    }


    /**
     * 获取用户的绑定银行卡的数据
    */
    public function member_card()
    {
        $this->int_member();
        $bank = $this->base_bank_online('bank',$this->member['bank_id']);
        $data = [];

        $trim_num=function($num){
            switch (strlen($num)) {
                case strlen($num) < 8;
                    return substr_replace($num,str_repeat('*',strlen($num)-5),3,-2);
                case strlen($num) < 14:
                    return substr_replace($num,str_repeat('*',strlen($num)-6),3,-3);
                default:
                    return substr_replace($num,str_repeat('*',strlen($num)-8),4,-4);
            }
        };


        if (!empty($this->member['bank_num'])) {
            $temp = [
                'num' => $trim_num($this->member['bank_num']),
                'img' => isset($bank['img']) ? $bank['img'] : '',
                'name' => isset($bank['bank_name']) ? $bank['bank_name'] : '',
                'bank_id' => $this->member['bank_id'],
                'qrcode' => ''
            ];
            array_push($data,$temp);
        }

        if (!empty($this->member['wechat'])) {
            $temp = [
                'num' => $trim_num($this->member['wechat']),
                'img' => WX_IMG_PNG,
                'name' => "微信",
                'bank_id' => "52",
                'qrcode' => $this->member['wechat_qrcode']
            ];
            array_push($data,$temp);
        }
        if (!empty($this->member['alipay'])) {
            $temp = [
                'num' => $trim_num($this->member['alipay']),
                'img' => ZFB_IMG_PNG,
                'name' => "支付宝",
                'bank_id' => "51",
                'qrcode' => $this->member['alipay_qrcode']

            ];
            array_push($data,$temp);
        }
        return $data;
    }

    /**
     * 获取用户绑定银行卡数据（新版）
     */
    public function new_member_card()
    {
        $this->int_member();
        $member = $this->member;
        $bank = $this->base_bank_online('bank',$member['bank_id']);
        $data = [];
        $trim_num=function($num){
            switch (strlen($num)) {
                case strlen($num) < 8;
                    return substr_replace($num,str_repeat('*',strlen($num)-5),3,-2);
                case strlen($num) < 14:
                    return substr_replace($num,str_repeat('*',strlen($num)-6),3,-3);
                default:
                    return substr_replace($num,str_repeat('*',strlen($num)-8),4,-4);
            }
        };
        // 站点配置
        $set = $this->get_gcset(array('is_open_alipay','is_open_wechat'));

        // 支付宝
        $alipay = [
            'num' => $member['alipay'] ? $trim_num($member['alipay']) : '',
            'img' => ZFB_IMG_PNG,
            'name' => "支付宝",
            'bank_id' => "51",
            'qrcode' => $member['alipay_qrcode'],
            'is_bind' => $member['alipay'] ? true : false,
            'is_open' => isset($set['is_open_alipay']) ? intval($set['is_open_alipay']) : 0
        ];
        array_push($data,$alipay);
        // 微信
        $wechat = [
            'num' => $member['wechat'] ? $trim_num($member['wechat']) : '',
            'img' => WX_IMG_PNG,
            'name' => "微信",
            'bank_id' => "52",
            'qrcode' => $member['wechat_qrcode'],
            'is_bind' => $member['wechat'] ? true : false,
            'is_open' => isset($set['is_open_wechat']) ? intval($set['is_open_wechat']) : 0
        ];
        array_push($data,$wechat);
        // 银行卡
        if (isset($bank['img'])) {
            $binkImg = $bank['img'];
        } else {
            foreach ($bank as $v) {
                $binkImg = $v['img'];
                break;
            }
        }
        $bankNum = [
            'num' => $member['bank_num'] ? $trim_num($member['bank_num']) : '',
            'img' => $binkImg,
            'name' => isset($bank['bank_name']) ? $bank['bank_name'] : '',
            'bank_id' => $member['bank_id'],
            'qrcode' => '',
            'is_bind' => $member['bank_num'] ? true : false,
            'is_open' => 1
        ];
        array_push($data,$bankNum);

        return $data;
    }

    /**
     * 初始化用户的详细信息user_detail
     */
    private function int_member()
    {
        if (empty($this->member)) {
            $ci = get_instance();
            if (!empty($ci->user['id'])) {
                $findeStr = "*";
                $this->member = $this->get_one($findeStr,'user_detail',['uid' => $ci->user['id'] ]);;
            }
        }
    }

    /**
     * 根据bank_id 返回对应的name
     * @param $bank_id int 银行ID
     * @return string
    */

    public static function bank_id_name($bank_id)
    {
        switch ($bank_id) {
            case $bank_id < 51:
                return '银行卡号';
            case 51:
                return '支付宝账号';
            case 52:
                return  '微信账号';
        }
    }

    /**
     * 停用用户
     * @param $uid int 会员id
     * @return  bool
    */
    public  function stop_user($uid)
    {
        return $this->write('user',['status'=>2],['id' => $uid]);
    }


}
