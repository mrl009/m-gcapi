<?php
/**
 *
 * 支付接口调用 的demo
 * User: shenshilin
 * Date: 2017/4/10
 * Time: 15:02
 */
class Yafu_model extends MY_Model
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
     *   返回二维码的参照     迅捷通
     */
    public function call_interface($order_num, $money, $pay_data)
    {
        /**返回时二维码**/
        //$data 为返回的数据

        $partner     = (string)trim($pay_data['pay_id']); //商户ID
        $ordernumber = (string)trim($order_num); //商户订单号
        $tokenKey    = $pay_data['pay_key']; // 密钥
        $callbackurl = $pay_data['pay_domain'].'/index.php/callback/yafu/callbackurl';
        $hrefbackurl = $pay_data['pay_domain'].'/index.php/callback/yafu/hrefbackurl';
        $banktype    = $this->return_code($pay_data['code'], $pay_data['bank_type']);

        $data = [
            'consumerNo'     => $partner,
            'payType'    => $banktype,
            'transAmt'    => (string)$money,
            'merOrderNo' => $ordernumber,
            'backUrl' => $callbackurl,
            'version'     => "3.0",
            'frontUrl'     => $hrefbackurl,
            'goodsName'      => 'online',
            'bankCode'      => $pay_data['bank_type'],
        ];
        if ($pay_data['code'] == 7) {
            $data['payType'] = '0601';
            $ci = get_instance();
            if ($ci->from_way == 3) {
                $data['payType'] = '0101';
            }
        }
        ksort($data);
        $str = '';
        foreach ($data as $keys => $value) {
            if (!empty($value)) {
                $str .= $keys.'='.$value.'&';
            }
        }
        $str .= 'key='.$tokenKey;
        $sign = md5($str);
        $data['sign'] = $sign;

        $returnData['jump']   = 1; //设置支付方式的返回格式
        if (in_array($pay_data['code'], [1,2,4,5,7,8])) {
            /*if (in_array($pay_data['code'], [2,5,7])) {
                $returnData['jump'] = 5;
                $pay_data['pay_url'] =  "http://p.1wpay.com/a/wapPay";
            }*/
            $temp['url']    = $pay_data['pay_url'];
            $temp['method'] = 'POST';
            $temp['data']   = $data;

            $url = "{$pay_data['pay_domain']}/index.php/pay/pay_test/pay_sest";//表单提交的地址
            $returnData['url']    = $url.'/'.$order_num;
            $returnData['json']  = json_encode($temp, JSON_UNESCAPED_UNICODE);
            return $returnData;

        }
        $res = pay_curl($pay_data['pay_url'],http_build_query($data),'POST');
        $res = json_decode($res,true);
        if ($res['code'] != '000000') {
            $this->error_echo("交易失败:".$res['msg']);
        }
        ksort($res);
        $str = ToUrlParams($res);
        $str .= '&key='.$tokenKey;

        if (strtoupper($res['sign']) != strtoupper( md5($str))) {
            $this->error_echo("签名验证失败数据可能被篡改");
        }
        $returnData['img']    = $res['busContent'];//二维码的地址
        $returnData['money']  = $money; //支付的钱
        $returnData['order_num']  = $order_num;//订单号
        return $returnData;

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
                return '0201';//微信
            case 2:
                return '0201';//微信app
            case 4:
                return '0301';//支付宝
            case 5:
                return '0301';//支付宝app
            case 7:
                return $bank;//网银
            case 8:
                return '0501';//qq钱包
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
