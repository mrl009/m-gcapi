<?php

/**
 * Created by PhpStorm.
 * User: mrl
 * Date: 2018/3/9
 * Time: 下午5:48
 */
class Wechat_model extends MY_Model
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
        //调用微信接口生成支付二维码
        $param = [
            'body' => '微信快速充值，订单：' . $order_num,
            'attach' => $username,
            'out_trade_no' => $order_num,
            'total_fee' => $money * 100,
            'spbill_create_ip' => get_ip(),
            'notify_url' => $pay['pay_domain'] . '/index.php/callback/wechat/notify',
            'trade_type' => 'NATIVE',
        ];
        $rs = $weChatPay->unifiedOrder($param);
        if (isset($rs["code_url"]) && !empty($rs["code_url"])) {
            return [
                'jump' => 3,
                'img' => $rs["code_url"],
                'money' => $money,
                'order_num' => $order_num
            ];
        } else {
            $CI->return_json(E_OP_FAIL, $rs['return_code'] . ':' . $rs['return_msg']);
        }
    }
}