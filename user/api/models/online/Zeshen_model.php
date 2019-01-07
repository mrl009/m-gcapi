<?php
/**
 *
 * 泽圣
 * 支付接口调用
 * User: shenshilin
 * Date: 2017/4/10
 * Time: 15:02
 */
class Zeshen_model extends MY_Model
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
     */
    public function call_interface($order_num, $money, $pay_data)
    {

        if ($pay_data['code'] == 7) {
            return $this->wx_pay($order_num, $money, $pay_data);
        }
        // 获取参数
        if ($money > 3000) {
            $ci = get_instance();
            $ci->return_json(E_ARGS,'限额3000内');
        }
        $paymoney    = $money*100;
        $partner     = trim($pay_data['pay_id']); //商户ID
        $ordernumber = trim($order_num); //商户订单号
        $tokenKey    = $pay_data['pay_key']; // 密钥
        $callbackurl = $pay_data['pay_domain'].'/index.php/callback/zeshen/callbackurl';
        $banktype    = $this->return_code($pay_data['code'], $pay_data['bank_type']);
        $client_ip   = get_ip();
          $pay_url     = $pay_data['pay_url'];
        $pay_domain  = $pay_data['pay_domain'];

        // 封装参数
        $pay = array();
        $pay['merchantCode']      = $partner;
        $pay['amount']            = (string)$paymoney;
        $pay['orderCreateTime']   = date('YmdHis');
        $pay['lastPayTime']       = date('YmdHis');
        $pay['outOrderId']        = $ordernumber;
        $pay['payChannel']        = $banktype;
        $pay['noticeUrl']         = $callbackurl;
        $pay['isSupportCredit']   = 1;
        $pay['ip']                = '192.168.8.102';
        $pay['model'] = "QR_CODE";
        $sign_fields1 = Array(
            "merchantCode",
            "outOrderId",
            "amount",
            "orderCreateTime",
            "noticeUrl",
            "isSupportCredit"
        );
        ksort($pay);
        $str = "";
        foreach($pay as $k=>$v){
            if (in_array($k,$sign_fields1)) {
                $str.= $k.'='.$v.'&';
            }
        }
        $str  .= "KEY=".$tokenKey;
        $sign = md5($str); #生成签名
        $pay['sign'] = strtoupper($sign); #设置签名

        // 发送连接获取二维码
        $response = pay_curl($pay_url,$pay,'post',$pay_domain);


        // 接收参数处理
        $response = json_decode($response,true);
        if ($response['code'] != '00'){
            $this->error_echo($response['msg']);
        }
        $str = '';
        $row = $response['data'];
        foreach($row as $k=>$v){
            if ($k != 'sign') {
                $str.= $k.'='.$v.'&';
            }
        }
        if ($row['sign'] != strtoupper(md5($str.'KEY='.$tokenKey))) {
            $this->error_echo('数据篡改');
        }
        /**返回时二维码**/
        //$data 为返回的数据
        $data['jump']      = 3; //设置支付方式的返回格式
        $data['img']       = $row['url'];//二维码的地址
        $data['money']     = $money; //支付的钱
        $data['order_num'] = $order_num;//订单号
        return $data;
    }

    private function wx_pay($order_num, $money, $pay_data){

        $paymoney    = $money*100;
        $partner     = trim($pay_data['pay_id']); //商户ID
        $ordernumber = trim($order_num); //商户订单号
        $tokenKey    = $pay_data['pay_key']; // 密钥
        $callbackurl = $pay_data['pay_domain'].'/index.php/callback/zeshen/callbackurl';
        $herback     = $pay_data['pay_domain'].'/index.php/callback/zeshen/return_url';
        $client_ip   = get_ip();

        $data = [];
        $data = [
            'merchantCode'    => $partner,
            'outOrderId'      => $ordernumber,
            'totalAmount'     => $paymoney,
            'orderCreateTime' => date('YmdHis'),
            'lastPayTime'     => date('YmdHis'),
            'noticeUrl'       => $callbackurl,
            'merUrl'          => $herback,
            'bankCode'        => $pay_data['bank_type'],
            'bankCardType'    => '01',
        ];
        $sign_fields = Array(
            "merchantCode",
            "outOrderId",
            "totalAmount",
            "orderCreateTime",
            "lastPayTime"
        );
        ksort($data); #排列数组 将数组已a-z排序
        $str = "";
        foreach($data as $k=>$v){
            if (in_array($k,$sign_fields)) {
                $str.= $k.'='.$v.'&';
            }
        }
        $str  .= "KEY=".$tokenKey;
        $sign = md5($str); #生成签名
        $data['sign'] = strtoupper($sign); #设置签名
        $url            = 'http://payment.zsagepay.com/ebank/pay.do';

        $temp['url']    = $url;
        $temp['method'] = 'post';
        $temp['data']   = $data;
        $returnData = [];
        $url = "{$pay_data['pay_domain']}/index.php/pay/pay_test/pay_sest";//表单提交的地址
        $returnData['url']   = $url;//提交的地址
        $returnData['jump']  = 5;//提交的地址
        $returnData['url']   = $url.'/'.$order_num;
        $returnData['json']  = json_encode($temp, JSON_UNESCAPED_UNICODE);
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
                return '21';//微信
            case 4:
                return '30';
            case 8:
                return '31';
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
