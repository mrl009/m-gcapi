<?php
/**
 *
 * 支付接口调用 的  新秒付
 * User: shenshilin
 * Date: 2017/4/10
 * Time: 15:02
 */
class Xinmiaofu_model extends MY_Model
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
        $paymoney    = "$money";
        $partner     = trim($pay_data['pay_id']); //商户ID
        $ordernumber = trim($order_num); //商户订单号
        $tokenKey    = $pay_data['pay_key']; // 密钥
        $callbackurl = $pay_data['pay_domain'].'/index.php/callback/Xinmiaofu/callbackurl';
        $hrefbackurl = $pay_data['pay_domain'].'/index.php/callback/Xinmiaofu/hrefbackurl';
        $banktype    = $this->return_code($pay_data['code'], $pay_data['bank_type']);
        $random      = (string) rand(1000, 9999);

        $payData = [
            'input_charset' => 'UTF-8',
            'notify_url'    => $callbackurl,
            'return_url'    => $hrefbackurl,
            'pay_type'      => 1,
            'bank_code'     => $banktype,
            'merchant_code' => $partner,
            'order_no'      => $order_num,
            'order_amount'  => $paymoney,
            'order_time'    => date('Y-m-d H:i:s'),
        ];
        ksort($payData); #排列数组 将数组已a-z排序
        $str = "";
        foreach ($payData as $k=>$v) {
            if ($v) {
                $str .= "$k=$v&";
            }
        }
        $str.="key=".$tokenKey;
        $sign                = md5(($str));

        $payData['sign']        = $sign;
        if ($pay_data['code'] ==5 ||$pay_data['code'] ==4) {

            //掉掉接口 提交地址提交数据
            $temp['url']    = $pay_data['pay_url'];
            $temp['method'] = 'post';
            $temp['data']   = $payData;
            /**跳转第三方**/
            $data['jump'] = 1; //设置支付方式的返回格式
            if ($pay_data['code'] == 2 || $pay_data['code'] == 5) {
                $data['jump'] = 5;
            }
            $url = "{$pay_data['pay_domain']}/index.php/pay/pay_test/pay_sest";//表单提交的地址
            $data['url']    = $url;//提交的地址
            $data['url']    = $url.'/'.$order_num;
            $data['json']  = json_encode($temp, JSON_UNESCAPED_UNICODE);
            return $data;
        }
        $post = http_build_query($payData);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $pay_data['pay_url']);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $tmpInfo = curl_exec($ch);
        if (curl_errno($ch)) {
            $this->error_echo(curl_error($ch));
        }


        $p = xml_parser_create();
        xml_parse_into_struct($p, $tmpInfo, $vals, $index);
        xml_parser_free($p);

        $arr = [];
        foreach ($vals as $v) {
            if ($v['tag'] == 'ERR') {
                $this->error_echo($v['value']);
            }
            $arr[$v['tag']] =$v['value'];
        }

        $url = substr($tmpInfo, 5, -6);
        if ($pay_data['code'] == 7) {
            $data['jump']      = 1; //设置支付方式的返回格式
            $data['url']       = $url;//二维码的地址
            $data['money']     = $money; //支付的钱
            $data['order_num'] = $order_num;//订单号
            return $data;
        }
        /**返回时二维码**/
        //$data 为返回的数据
        $data['jump']      = 3; //设置支付方式的返回格式
        $data['img']       = $url;//二维码的地址
        $data['money']     = $money; //支付的钱
        $data['order_num'] = $order_num;//订单号
        return $data;
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
