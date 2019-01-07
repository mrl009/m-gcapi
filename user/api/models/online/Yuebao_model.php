<?php
/**
 *
 * 支付接口调用
 * User: super
 * Date: 2017/10/16
 * Time: 15:02
 */
class Yuebao_model extends MY_Model
{

    /**
     * 构造函数
     *
     */
    public function __construct()
    {
        parent::__construct();
        $this->load->helper('common_helper');
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
     *   返回二维码的参照
     */
    public function call_interface($order_num, $money, $pay_data)
    {
        $partner     = (string)trim($pay_data['pay_id']); //商户ID
        $ordernumber = (string)trim($order_num); //商户订单号
        $tokenKey    = $pay_data['pay_key']; // 密钥
        $callbackurl = $pay_data['pay_domain'].'/index.php/callback/yuebao/callbackurl';
        $hrefbackurl = $pay_data['pay_domain'].'/index.php/callback/yuebao/hrefbackurl';
        $banktype    = $this->return_code($pay_data['code'], $pay_data['bank_type']);//bank_type有值表示是网银
        $version     = "3.0";
        $method      = 'Yb.online.interface';
        $goodsname = '';
        $attach = '';
        $isshow = 0;
        //$str  = "version={$version}&method={$method}&partner={$partner}&banktype={$banktype}&paymoney={$money}&ordernumber={$ordernumber}&callbackurl={$callbackurl}{$pay_data['pay_key']}";

        $signSource = sprintf("version=%s&method=%s&partner=%s&banktype=%s&paymoney=%s&ordernumber=%s&callbackurl=%s%s", $version,$method,$partner, $banktype, $money, $ordernumber, $callbackurl, $tokenKey);
        $sign = md5($signSource);//32位小写MD5签名值，UTF-8编码

        $arrData = [
            'partner'     => $partner,
            'banktype'    => $banktype,
            'method'      => $method,
            'version'     => $version,
            'paymoney'    => $money,
            'ordernumber' => $ordernumber,
            'callbackurl' => $callbackurl,
            'hrefbackurl' => $hrefbackurl,
            'sign'        => $sign
        ];
        //掉掉接口 提交地址提交数据
        $temp['url']    = $pay_data['pay_url'];
        $temp['method'] = 'post';
        $temp['data']   = $arrData;


        /**跳转第三方**/
        $data['jump'] = 1; //设置支付方式的返回格式
        // if (in_array($pay_data['code'], [2,5,7,12,13])) {
            $data['jump'] = 5;//打开外部app
            $url = "{$pay_data['pay_domain']}/index.php/pay/pay_test/pay_sest";//表单提交的地址
            $data['url']    = $url;//提交的地址
            $data['url']    = $url.'/'.$order_num;
            $data['json']  = json_encode($temp, JSON_UNESCAPED_UNICODE);
            return $data;
        // }

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
                return 'WEIXIN';
            case 2:
                return 'WEIXINWAP';
            case 4:
                return 'ALIPAY';
            case 5:
                return 'ALIPAYWAP';
            case 7:
                return $bank;
            case 8:
                return 'QQ';
            case 12:
                return 'QQWAP';
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
