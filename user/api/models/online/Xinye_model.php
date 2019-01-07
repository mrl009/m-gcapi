<?php
/**
 *
 * 微信公众号的微信支付接口
 * User: shenshilin
 * Date: 2017/4/10
 * Time: 15:02
 */
class Xinye_model extends MY_Model
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
        $callbankurl ="{$pay_data['pay_domain']}/index.php/callback/xinye/callbackurl";
        $arrData= [
            'service'  => "pay.weixin.native",
            //'version'  => "2.0",
            'mch_id'  => $pay_data['pay_id'],
            'out_trade_no' => $order_num,
            'body' => "online",
            'total_fee' => $money*100,
            'mch_create_ip' => "192.168.8.1",
            'notify_url' => $callbankurl,
            'nonce_str' => uniqid('wx',true),
        ];

        ksort($arrData);

        $str = ToUrlParams($arrData);
        $str.="&key=".$pay_data['pay_key'];
        $arrData['sign'] = strtoupper(md5($str));
        $xml = ToXml($arrData);

        if ($pay_data['code'] == 4) {
            $pay_data['pay_url'] = "https://pay.swiftpass.cn/pay/gateway";
        }
        $res = pay_curl($pay_data['pay_url'],$xml,'POST');
        /*$ch = curl_init();
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_URL, $pay_data['pay_url']);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);//严格校验
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        $res = curl_exec($ch);*/

        $resData = FromXml($res);
        if (isset($resData['status']) && $resData['status'] == 400) {
            $errorArr = [
                    'code'=>E_ARGS,
                    'msg'=>$resData['message'],
                ];
                echo json_encode($errorArr);
                exit;
        }
        if ($resData['result_code'] == '0') {
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
