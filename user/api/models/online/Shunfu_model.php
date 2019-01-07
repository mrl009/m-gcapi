<?php
/**
 *
 * 支付接口调用 的demo
 * User: shenshilin
 * Date: 2017/4/10
 * Time: 15:02
 * update 2017/12/18 Marks
 */
class Shunfu_model extends MY_Model
{

    /**
     * 构造函数
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
        //$data 为返回的数据

        $partner     = (string)trim($pay_data['pay_id']); //商户ID
        $ordernumber = (string)trim($order_num); //商户订单号
        $tokenKey    = $pay_data['pay_key']; // 密钥
        $callbackurl = $pay_data['pay_domain'].'/index.php/callback/shunfu/callbackurl';
        $hrefbackurl = $pay_data['pay_domain'].'/index.php/callback/shunfu/hrefbackurl';
        $banktype    = $this->return_code($pay_data['code'], $pay_data['bank_type']);

        $x = $money*100;
        $arrData = [
            'merNo' => $partner,
            'payNetway' => $banktype,
            'random' => (string)rand(1000,9999),
            'orderNo' => $ordernumber,
            'amount' => (string)$x,
            'goodsInfo' => '111',
            'callBackUrl' => $callbackurl,
            'callBackViewUrl' => $hrefbackurl,
            'clientIP' => get_ip(),
        ];
        ksort($arrData);
        $sign = md5(Util::json_encode($arrData) .$tokenKey); #生成签名

        $arrData['sign'] = strtoupper($sign); #设置签名
        $arrData = Util::json_encode($arrData); #将数组转换为JSON格式
        $post = array('data'=>$arrData);
        $this->load->helper('common_helper');

        $temp['method'] = 'post';
        $temp['data']   = $post;

        $data['jump']   = 3; //设置支付方式的返回格式
        $res = pay_curl($pay_data['pay_url'],$post,'post');
        $ci = get_instance();

        $res = json_decode($res,true);

        if (!is_array($res)) {
            $ci->return_json(E_ARGS, "第三方异常");
        }
        if ($res['resultCode'] != 00 ) {
            $ci->return_json(E_ARGS,'第三方：'.$res['resultMsg']);
        }
        if ($pay_data['code'] == 2 || $pay_data['code'] == 5 || $pay_data['code'] == 7  || $pay_data['code'] == 12 || $pay_data['code'] == 13) {
            $data['jump'] = 5;
            $data['url']  =  $temp['url'] = $res['qrcodeInfo'];
            $data['json'] = json_encode($temp, JSON_UNESCAPED_UNICODE);
            $data['order_num']  = $order_num;
            $data['img'] = "http://www.baidu.com";
            return $data;
        }
        $data['img']    = $res['qrcodeInfo'];//二维码的地址
        $data['money']  = $money; //支付的钱
        $data['order_num']  = $ordernumber;//订单号
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
                return 'WX';//微信
            case 2:
                return 'WX_WAP';//微信app
            case 4:
                return 'ZFB';//支付宝
            case 5:
                return 'ZFB_WAP';//支付宝app
            case 8:
                return 'QQ';//qq钱包
            case 9:
                return 'JDQB'; //京东钱包
            case 10:
                return 'BAIDU';//百度钱包
            case 12:
                return 'QQ_WAP';
            case 13:
                return 'JDQB_WAP'; //京东
            case 17:
                return 'YL'; //银联
            case 20:
                return 'BAIDU_WAP';//百度钱包
            case 38:
                return 'SUNING';//苏宁钱包
            case 39:
                return 'SUNING_WAP';//苏宁WAP
            default:
                return 'WX';//微信
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
class Util
{
    static function json_encode($input){
        if(is_string($input)){
            $text = $input;
            $text = str_replace('\\', '\\\\', $text);
            $text = str_replace(
                array("\r", "\n", "\t", "\""),
                array('\r', '\n', '\t', '\\"'),
                $text);
            $text = str_replace("\\/", "/", $text);
            return '"' . $text . '"';
        }else if(is_array($input) || is_object($input)){
            $arr = array();
            $is_obj = is_object($input) || (array_keys($input) !== range(0, count($input) - 1));
            foreach($input as $k=>$v){
                if($is_obj){
                    $arr[] = self::json_encode($k) . ':' . self::json_encode($v);
                }else{
                    $arr[] = self::json_encode($v);
                }
            }
            if($is_obj){
                $arr = str_replace("\\/", "/", $arr);
                return '{' . join(',', $arr) . '}';
            }else{
                $arr = str_replace("\\/", "/", $arr);
                return '[' . join(',', $arr) . ']';
            }
        }else{
            $input = str_replace("\\/", "/", $input);
            return $input . '';
        }
    }
}
