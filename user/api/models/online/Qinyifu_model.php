<?php
/**
 *
 * 支付接口调用 的demo
 * User: shenshilin
 * Date: 2017/4/10
 * Time: 15:02
 */
class Qinyifu_model extends MY_Model
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
        /**返回时二维码**/
        $this->load->helper('common_helper');
        $partner     = (string)trim($pay_data['pay_id']); //商户ID
        $ordernumber = (string)trim($order_num); //商户订单号
        $tokenKey    = $pay_data['pay_key']; // 密钥
        $callbackurl = $pay_data['pay_domain'].'/index.php/callback/Qinyifu/callbackurl';
        $banktype    = $this->return_code($pay_data['code'], $pay_data['bank_type']);
        $pay = array();
        $q = $money*100;
        $pay['version']         = "V2.0.0.0"; #版本号
        $pay['merNo']           = $partner; #商户号
        $pay['netway']          = $banktype;  #WX 或者 ZFB
        $pay['random']          = (string) rand(1000, 9999);
        ;  #4位随机数    必须是文本型
        $pay['orderNum']        = $ordernumber;  #商户订单号
        $pay['amount']          =  "$q"; #默认分为单位 转换需要 * 100   必须是文本型
        $pay['goodsName']       = 'onlie';  #商品名称
        $pay['callBackUrl']     = $callbackurl;
        $pay['charset']         = "UTF-8";
        $pay['callBackViewUrl'] = get_auth_headers('Origin');
        ksort($pay); #排列数组 将数组已a-z排序
        //检查错误信息
        $str  = my_json_encode($pay) . $tokenKey;
        $str  = str_replace("\\", '', $str);
        $sign = md5($str); #生成签名

        $pay['sign'] = strtoupper($sign); #设置签名

        //网关更换 占时需要
        $pay_data['pay_url']= $this->return_pay_url($pay_data['code']);
        $postdata['data'] = my_json_encode($pay);
        $res = pay_curl($pay_data['pay_url'], $postdata, 'post', $pay_data['pay_domain']);

        $res = json_decode($res, true); #将返回json数据转换为数组

        if ($res['stateCode'] != '00') {
            $str = "错误码 : {$res['stateCode']} 错误信息: 第三方支付通道正在维护！";
            $this->error_echo($str);
        }
        if (in_array($pay_data['code'], [2, 5, 12, 13, 33])) {
            return [
                'url' => $res['qrcodeUrl'],
                'jump' => 5
            ];
        }
        //$data 为返回的数据
        $data['jump']   = 3; //设置支付方式的返回格式
        if(in_array($pay_data['code'],$this->pay_model_1)){
            /*if ($pay_data['code'] == 17) {
                $data['is_img'] = 1;
            }*/
            $data['img']    = $res['qrcodeUrl'];//二维码的地址
            $data['money']  = $money; //支付的钱
            $data['order_num']  = $order_num;//订单号
            return $data;
        }
        $data['jump']   = 5;
        $url = "{$pay_data['pay_domain']}/index.php/pay/pay_test/pay_sest";//表单提交的地址
        $temp['url']    = $res['qrcodeUrl'];
        $temp['method'] = 'get';
        $temp['data']   = [];
        $data['url']    = $url.'/'.$order_num;
        $data['json']  = json_encode($temp, JSON_UNESCAPED_UNICODE);
        return $data;
    }

    /**
    *轻易付:微信(1,2,3),支付宝(4,5),QQ钱包(8,12),京东钱包(9,13),百度钱包(10),银联钱包(17)
    */
    private function return_code($code, $bank)
    {
        switch ($code) {
            case 1:
                return 'WX';//微信
            case 2:
                return 'WX_WAP';//微信app
            case 33:
                return 'WX_H5';//微信扫码
            case 4:
                return 'ZFB';//支付宝
            case 5:
                return 'ZFB_WAP';//支付宝app
            case 8:
                return 'QQ';//qq钱包
            case 12:
                return 'QQ_WAP';//qqwap
            case 9:
                return 'JD'; //京东钱包
            case 13:
                return 'JD_WAP'; //京东钱包
            case 10:
                return 'BAIDU'; //百度钱包
            case 17:
                return 'UNION_WALLET';//银联钱包
            //case 26:
            //    return 'MBANK';//手机银行
            default:
                return 'WX';
        }
    }

    /**
     * 特殊了支付网关不唯一
    */
    private function return_pay_url($code)
    {
        switch ($code) {
            case 1:
                return 'http://wx.qyfpay.com:90/api/pay.action';//微信
            case 2:
                return 'http://wxwap.qyfpay.com:90/api/pay.action';//微信wap
            case 33:
                return 'http://wx.qyfpay.com:90/api/pay.action';//微信h5     
            case 4:
                return 'http://zfb.qyfpay.com:90/api/pay.action';//支付宝
            case 5:
                return 'http://zfbwap.qyfpay.com:90/api/pay.action';//支付宝app
            case 8:
                return 'http://qq.qyfpay.com:90/api/pay.action';//qq钱包
            case 12:
                return 'http://qqwap.qyfpay.com:90/api/pay.action';//qqwap
            case 9:
                return 'http://jd.qyfpay.com:90/api/pay.action'; //京东钱包
            case 13:
                return 'http://jd.qyfpay.com:90/api/pay.action'; //京东wap
            case 10:
                return 'http://baidu.qyfpay.com:90/api/pay.action'; //百度钱包
            case 17:
                return 'http://unionpay.qyfpay.com:90/api/pay.action';//银联钱包
            //case 26:
            //    return 'http://mbank.qyfpay.com:90/api/pay.action';//手机银行
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
