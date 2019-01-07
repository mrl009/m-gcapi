<?php
/**
 *
 * 迅捷通
 * 支付接口调用
 * User: shenshilin
 * Date: 2017/4/10
 * Time: 15:02
 * update 2017/12/18 Marks
 */
class Xunjietong_model extends MY_Model
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
        $callbackurl = $pay_data['pay_domain'].'/index.php/callback/Xunjietong/callbackurl';
        $banktype    = $this->return_code($pay_data['code'], $pay_data['bank_type']);
        $random      = (string) rand(1000, 9999);
        $pay = array();
        $pay['merchno']           = $partner; #商户号
        $pay['amount']            = $paymoney; #金額
        $pay['goodsName']            = 'online'; #
        $pay['traceno']           = $ordernumber; #
        $pay['payType']           = $banktype; #
        $pay['notifyUrl']         = $callbackurl; #
        ksort($pay); #排列数组 将数组已a-z排序
        $str = "";
        foreach ($pay as $k=>$v) {
            $str.= $k.'='.$v.'&';
        }
        $str  .= $tokenKey;

        $sign = md5($str); #生成签名
        $pay['signature'] = $sign; #设置签名
        $temp['method'] = 'post';
        $temp['data']   = $pay;
        $post = http_build_query($pay);

        if ($pay_data['code'] == 2) {
            $pay_data['pay_url'] = 'http://a.bldpay.com:8209/payapi/wapPay';
        }elseif(in_array($pay_data['code'],[33,36,16,15,21])){
            $pay_data['pay_url'] = 'http://a.bldpay.com:8209/payapi/openPay';
        }
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
        $tmpInfo = iconv("gb2312//IGNORE", "utf-8", $tmpInfo);
        $row = json_decode($tmpInfo, true); #将返回json数据转换为数组
        if ($row['respCode'] != '00') {
            $str = "错误码 : {$row['respCode']}错误信息: {$row['message']}";
            $this->error_echo($str);
        }

        if (in_array($pay_data['code'],[2,5])) {
            $data['jump'] = 5;
            $data['img']       = '';
            $data['money']     = $money; //支付的钱
            $data['order_num'] = $order_num;//订单号
            $data['url'] = $temp['url'] = $row['barCode'];
            $data['json']       = json_encode($temp, JSON_UNESCAPED_UNICODE);
            return $data;
            /*$urlStr = parse_url($row['barCode']);
            $urlStr = $urlStr['query'];
            $url = explode('=',$urlStr);
            if (isset($url[1])) {
                $data['url'] = $url[1];
                return $data;
            }else{
                $this->error_echo("第三方返回错误");
            }*/
        }
        /**返回时二维码**/
        //$data 为返回的数据
        $data['jump']      = 3; //设置支付方式的返回格式
        $data['img']       = $row['barCode'];//二维码的地址
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
                return '2';//微信
            case 2:
                return '2';//微信app/wap
            case 33:
                return '2';//微信公衆號掃碼
            case 4:
                return '1';//支付宝
            case 36:
                return '1';//支付宝公衆號掃碼
            /*case 7:
                return $bank;//网银*/
            case 8:
                return '4';//qq钱包
            case 16:
                return '4';//qq钱包公衆號掃碼
            case 9:
                return '5'; //京东钱包
            case 15:
                return '5';//京东公衆號掃碼
            case 10:
                return '3';//百度钱包
            case 21:
                return '3';//百度钱包

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
