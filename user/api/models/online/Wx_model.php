<?php
/**
 *
 * 微信公众号的微信支付接口
 * User: shenshilin
 * Date: 2017/4/10
 * Time: 15:02
 */
class Wx_model extends MY_Model
{

    /**
     * 构造函数
     *
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     *
     *接口调用 paydata 参数
     *   'bank_o_id'   支付平台的id号
     *   'pay_domain'      异步回调的地址
     *   'pay_return_url'  同步回调的地址
     *   'pay_id'          商户号
     *   'pay_key'         商户密钥
     *   'pay_private_key' 商户私钥
     *   'pay_public_key'  商户公钥
     *   'pay_server_key'  服务端公钥
     *   'pay_server_num'  终端号
     *   'shopurl'         商城域名
     *   'code'            状态值
     *   'bank_type'       网银支付是网银的type
     *   返回二维码的参照     迅捷通
     */
    public function call_interface($order_num, $money, $pay_data)
    {
        $this->load->helper('common_helper');

        $callbankurl ="{$pay_data['pay_domain']}/index.php/callback/weixin/callbackurl";
        $arrData= [
            'appid'  => $pay_data['pay_server_num'],
            'mch_id' => $pay_data['pay_id'],
            'nonce_str'   => uniqid('wx', true),
            'body'        => "线上支付",
            'out_trade_no'=> $order_num,
            'total_fee'   => $money*100,
            'product_id'  => rand(100000000,999999999),
            'spbill_create_ip'  => "123.12.12.123",
            'notify_url'  => $callbankurl,
            'trade_type'  => "NATIVE",
        ];

        /*$arrData= [
            'appid'  => $pay_data['pay_server_num'],
            'mch_id' => $pay_data['pay_id'],
            'nonce_str'   => uniqid('wx', true),
            'body'        => "线上支付",
            'out_trade_no'=> $order_num,
            'total_fee'   => $money*100,
            'product_id'  => rand(100000000,999999999),
            'spbill_create_ip'  => "103.244.251.59",
            //'notify_url'  => "http://paysdk.weixin.qq.com/example/notify.php",
            'notify_url'  => $callbankurl,
            'trade_type'  => "MWEB",
            'scene_info'  => '{"h5_info": {"type":"Wap","wap_url": "www.across6.cn","wap_name": "腾讯充值"}}'
        ];*/

        ksort($arrData);

        $str = ToUrlParams($arrData);
        $str.="&key=".$pay_data['pay_key'];
        $arrData['sign'] = strtoupper(md5($str));
        $xml = ToXml($arrData);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_URL, $pay_data['pay_url']);
        //curl_setopt($ch, CURLOPT_URL, "https://api.mch.weixin.qq.com/pay/unifiedorder");
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);//严格校验
        //curl_setopt($ch, CURLOPT_REFERER, "http://www.across6.cn/pay/pay/pay_do");
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        $res = curl_exec($ch);
        if ($res) {
            curl_close($ch);
        } else {
            $error = curl_errno($ch);
            curl_close($ch);
            $errorArr = [
                'code'=>E_ARGS,
                'msg'=>"curl出错，错误码:$error",
            ];
            echo json_encode($errorArr);
            die();
        }


        $resData = FromXml($res);
        if ($resData['return_code'] == 'SUCCESS') {
            $sign = $resData['sign'];
            unset($resData['sing']);
            ksort($resData);
            $str = ToUrlParams($resData);
            $str.="&key=".$pay_data['pay_key'];
            if ($sign == strtoupper(md5($str))) {
                $data['jump']   = 3; //设置支付方式的返回格式
                $data['img']    = $resData['code_url'];//二维码的
                $data['money']  = $money; //支付的钱
                $data['order_num']  = $order_num;//订单号
                return $data;
            } else {
                $errorArr = [
                    'code'=>E_ARGS,
                    'msg'=>"二维码返回签名验证失败",
                ];
                echo json_encode($errorArr);
                die();
            }
        } else {
            $errorArr = [
                'code'=>E_ARGS,
                'msg'=>$res['return_msg'],
            ];
            echo json_encode($errorArr);
            die();
        }
    }

    /**
     *【支付类型@1微信#2微信app#3微信扫码#
     * 4支付宝#5支付宝APP#6支付宝扫码
     * #7网银#8QQ钱包#9京东钱包#10百度钱包#11点卡
    */
    private function return_code($code, $bank)
    {
        switch ($code) {
            case 1:
                return 'WEIXIN';//微信
            case 2:
                return 'WEIXINWAP';//微信app
            case 4:
                return 'ALIPAY';//支付宝
            case 5:
                return 'ALIPAYWAP';//支付宝app
            case 7:
                return $bank;//网银
            case 8:
                return '';//qq钱包
            case 9:
                return ''; //京东钱包
            case 10:
                return '';//百度钱包
        }
    }

    /**
     * 接口信息错误
    */
    public function error_echo($str)
    {
        $data['code'] = E_ARGS;
        $data['msg']  = $str;
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        die;
    }
}
