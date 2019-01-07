<?php
/**
 *
 * 聚源支付模型层
 * User: shenshilin
 * Date: 2017/4/10
 * Time: 15:02
 */
class Juyuan_model extends MY_Model
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
        $id = $pay_data['bank_o_id'];
        $data['jump'] = 1;
        if ($pay_data['code'] == 2 || $pay_data['code'] == 5) {
            $data['jump'] = 5;
        }
        $url = "{$pay_data['pay_domain']}/index.php/pay/pay_test/pay_sest";
        $data['url']    = $url;//提交的地址
//        $data['method'] = "get";//提交的方式
        $data['img']    = "";//图片地址  支付宝微信转账
//        $data['qrcode'] = "http://www.baidu.com";//二维码  base_64
        $data['confirm']= "";//确认存款码
        $data['money']  = $money;
        $data['order_num']  = $order_num;

        //签名数据开始
        // 相关参数
        $paymoney    = sprintf('%.2f', $money);
        $partner     = $pay_data['pay_id']; //商户ID
        $ordernumber = $order_num; //商户订单号
        $tokenKey        = $pay_data['pay_key']; // 密钥
        // 下行异步通知地址
        $callbackurl = $pay_data['pay_domain'].'/index.php/pay/juyuan/callbackurl';
        // 下行同步通知地址
        $hrefbackurl = $pay_data['pay_domain'].'/index.php/pay/juyuan/hrefbackurl';
        // 支付编码

        $banktype    = $this->return_code($pay_data['code'], $pay_data['bank_type']);

        // 签名规则
        $str         = 'partner=' . $partner . '&banktype=' . $banktype . '&paymoney=' . $paymoney . '&ordernumber=' . $ordernumber . '&callbackurl=' . $callbackurl . $tokenKey;

        $sign                = md5($str);
        $arrData['partner']     = $partner;
        $arrData['banktype']    = $banktype;
        $arrData['paymoney']    = $paymoney;
        $arrData['ordernumber'] = $ordernumber;
        $arrData['callbackurl'] = $callbackurl;
        $arrData['hrefbackurl'] = $hrefbackurl;
        $arrData['sign']        = $sign;

        $temp['url']    = $pay_data['pay_url'];
        $temp['method'] = 'get';
        $temp['data']   = $arrData;
        $data['url']    = $url.'/'.$order_num;
        $data['json']   = json_encode($temp, JSON_UNESCAPED_UNICODE);


        //数据签名完成
        /*switch($data['jump']){
            case 1:
                $data['data']   = [
                    'name' => "test",
                    'sign' => "324f15sw6fasfasjkfit6ewfvbdjkszy7sdgmnbshjfgdsfvkjdshfgsdgvbkj"
                ];
                break;
            case 3:
                $data['qrcode'] = "http:www.baidu.com";
                break;
        }*/
        return $data;
    }
    private function Jump_mode($id)
    {
        switch ($id) {
            case 15:
                return 3;
            case 16:
                return 3;
            default:
                return  1;
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
                return 'ALIPAYSCAN';//支付宝
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
}
