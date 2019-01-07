<?php

/**
 * Created by PhpStorm.
 * User: mrl
 * Date: 2018/3/9
 * Time: 下午5:48
 */
class Wechath5_model extends MY_Model
{
    /**
     * 构造函数
     */
    public function __construct()
    {
        parent::__construct();
    }

    public function call_interface($order_num, $money, $pay)
    {
        $CI = get_instance();
        $username = $CI->user['username'];
        $domain = isset($pay_data['pay_domain']) ? $pay_data['pay_domain'] : '';
        if (empty($username) || $money <= 0) {
            $CI->return_json(E_ARGS, '参数错误');
        }
        $config = [
            'mch_id' => $pay['pay_id'],
            'appid' => $pay['pay_key'],
            'apikey' => $pay['pay_server_key'],
        ];
        include_once APPPATH . 'libraries/Wechatpay.php';
        $weChatPay = new Wechatpay($config);
        $scene_info ='{"h5_info": {"type":"Wap","wap_url": "'. $domain.'","wap_name": "微信充值"}} ';
        //调用微信接口生成微信链接
        $param = [
            'body' => '微信快速充值，订单：' . $order_num,
            'attach' => $username,
            'out_trade_no' => $order_num,
            'total_fee' => $money * 100,
            'spbill_create_ip' => get_ip(),
            'notify_url' => $pay['pay_domain'] . '/index.php/callback/wechat/notify',
            'trade_type' => 'MWEB',
            'scene_info' => $scene_info,
        ];
        $rs = $weChatPay->unifiedOrderH5($param);
        if (isset($rs["mweb_url"]) && !empty($rs["mweb_url"])) {
            return  [
                'url' => $rs["mweb_url"],
                'jump' => 5
            ];
        } else {
            $this->online_erro('微信H5', json_encode($rs));
            $CI->return_json(E_OP_FAIL, $rs['return_code'] . ':' . $rs['return_msg']);
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

}