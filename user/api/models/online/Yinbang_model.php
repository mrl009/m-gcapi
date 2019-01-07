<?php
/**
 *
 * 银邦
 * User: shenshilin
 * Date: 2017/4/10
 * Time: 15:02
 */
class Yinbang_model extends MY_Model
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
     */
    public function call_interface($order_num, $money, $pay_data)
    {
        $ordernumber = (string)trim($order_num); //商户订单号
        $callbackurl = $pay_data['pay_domain'].'/index.php/callback/Yinbang/callbackurl';
        $hrefbackurl = $pay_data['pay_domain'].'/index.php/callback/Yinbang/hrefbackurl';
        $pay_id = (string)trim($pay_data['pay_id']);
        $pay_server_num = $pay_data['pay_server_num'];
        $private_key=$pay_data['pay_private_key'];  //商户私钥
        $public_key = $pay_data['pay_server_key'];  //服务器公钥
        $priKey= openssl_get_privatekey($private_key);
        $pubkey= openssl_pkey_get_public($public_key);
        $form_url=$pay_data['pay_url'];
        $payType = $this->_return_code($pay_data['code'], $pay_data['bank_type']);
        $appSence = $this->_format_way($pay_data['code']);
        $_encParam = array(
            'terId'=>$pay_server_num,
            'businessOrdid'=>$ordernumber,
            'orderName'=>'onlie',
            'merId'=>$pay_id,
            'tradeMoney'=>$money*100,
            'payType'=>$payType,
            'syncURL'=>$callbackurl,
            'asynURL'=>$hrefbackurl,
            'appSence'=>$appSence
        );
        $enc_json = json_encode($_encParam, JSON_UNESCAPED_UNICODE);
        $Split = str_split($enc_json, 64);
        $encParam_encrypted = '';
        foreach ($Split as $Part) {
            openssl_public_encrypt($Part, $PartialData, $pubkey);//服务器公钥加密
            $t = strlen($PartialData);
            $encParam_encrypted .= $PartialData;
        }
        $encParam = base64_encode(($encParam_encrypted));//加密的业务参数
        openssl_sign($encParam_encrypted, $sign_info, $priKey);
        $sign = base64_encode($sign_info);//加密业务参数的签名

        $arrData['sign'] = $sign;
        $arrData['merId'] = $pay_id;
        $arrData['version'] = '1.0.9';
        $arrData['encParam'] = $encParam;

        //掉掉接口 提交地址提交数据
        $temp['url']    = $pay_data['pay_url'];
        $temp['method'] = 'get';
        $temp['data']   = $arrData;

        /**不是网银直接返回时二维码**/
        // if ($pay_data['code'] != 7 && $pay_data['code'] != 12) {
        //     $fetch_dode = $this->_fetch_wxcode($arrData, $pay_data['pay_url'],
        //                     $pay_data['shopurl'], $payType, 'get');
        //     return ['jump'=>3, 'img'=>$fetch_dode, 'money'=>$money,
        //             'order_num'=>$ordernumber];
        // }

        /**跳转第三方**/
        $data['jump'] = 5; //设置支付方式的返回格式
        if ($pay_data['code'] == 7) {
            $data['jump'] = 5;
        }

        $url = "{$pay_data['pay_domain']}/index.php/pay/pay_test/pay_sest";//表单提交的地址
        $data['url']    = $url;//提交的地址
        $data['url']    = $url.'/'.$order_num;
        $data['json']  = json_encode($temp, JSON_UNESCAPED_UNICODE);
        return $data;
    }

    /**
     *【支付类型@1微信#2微信app#3微信扫码#
     * 4支付宝#5支付宝APP#6支付宝扫码
     * #7网银#8QQ钱包#9京东钱包#10百度钱包#11点卡
     * 银绑:微信(1,2,3),支付宝(4,5,6),网银(7),QQ钱包(8,12,16),京东钱包(9,13,15),百度钱包(10,20,21),银联钱包(17),快捷支付(25),收银台(26)
    */
    private function _return_code($code, $bank)
    {
        switch ($code) {
            case 1:
                return 1005;//微信
            case 2:
                return 1010;//微信WAP/H5
//            case 3:
//                return 1009;//微信公众号二维码
            case 4:
                return 1006;//支付宝
            case 5:
                return 1008;//支付宝wap/H5
//            case 6:
//                return ;//支付宝公众号二维码
            case 7:
                return 1003;//网银
            case 8:
                return 1013;//qq钱包
            case 9:
                return 1017; //京东钱包
            case 10:
                return 1019;//百度钱包
            case 12:
                return 1014;//qqWAP/H5
            case 13:
                return 1022; //京东wap/H5
//            case 15:
//                return ; //京东钱包公众号二维码
//            case 16:
//                return ;//qq钱包公众号二维码
            case 17:
                return 1016;//银联钱包
            case 20:
                return 1023;//百度钱包WAP/H5
//            case 21:
//                return ;//百度钱包
            case 25:
                return 1024;//快捷支付 
//            case 26:
//                return ;//收银台
            default:
                return 1005;
        }
    }

    /**
     * 从网站获取二维码链接
     *
     * @access private
     * @param Array $data 发送的数据
     * @param String $pay_url 支付网关
     * @param String $fake_url 伪造支付域名
     * @param String $fake_url 支付类型
     * @param String $method 支付方法
     * @return String 二维码地址
     */
    private function _fetch_wxcode($data, $pay_url, $fake_url, $pay_type, $method='post')
    {
        $respon = pay_curl($pay_url, $data, $method, $fake_url);
        preg_match_all('/id="ordersId" name="dollar" value="(.*)">/', $respon, $match);
        if (empty($match[1][0])) {
            $this->error_echo('获取二维码失败, 请联系客服');
        }
        $temp['ordId'] = $match[1][0];
        $temp['payType'] = $pay_type;
        $respon = pay_curl('https://www.yinbangpay.com/gateway/weixinPay', http_build_query($temp), 'post', $fake_url);
        $respon = json_decode($respon, true);
        if ($respon['code'] != 0) {
            $this->error_echo('获取二维码失败, 请联系客服2');
        }
        return $respon['data']['jump_url'];
    }

    /**
     * 来源转换相对的应用场景
     */
    private function _format_way($code)
    {
        switch ($this->from_way) {
            case 3:
                $appSence = 1001;   //pc
                break;
            default:
                $appSence = 1002;   //h5
                break;
        }
        return 1001;
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
