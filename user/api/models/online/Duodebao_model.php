<?php
/**
 *
 * W付
 * 支付接口调用
 * User: shenshilin
 * Date: 2017/4/10
 * Time: 15:02
 * update 2017/12/18 Marks
 */
class Duodebao_model extends MY_Model
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
        // 获取参数
        if ($pay_data['code'] == 7) {
            return $this->wy($order_num, $money, $pay_data);
        }
        $paymoney    = $money;
        $partner     = trim($pay_data['pay_id']); //商户ID
        $ordernumber = trim($order_num); //商户订单号
        $tokenKey    = $pay_data['pay_key']; // 密钥
        $callbackurl = $pay_data['pay_domain'].'/index.php/callback/duodebao/callbackurl';
        $banktype    = $this->return_code($pay_data['code'], $pay_data['bank_type']);
        $private_key=$pay_data['pay_private_key'];  //商户私钥
        $client_ip = get_ip();
        $pay_url = $pay_data['pay_url'];
        $pay_domain = $pay_data['pay_domain'];

        if(in_array($pay_data['code'],[2,5])){
            $pay_url = 'https://api.ddbill.com/gateway/api/h5apipay';
        }

        // 封装参数
        $postdata=array(
            'extend_param'=>'',             //业务扩展参数
            'extra_return_param'=>'',       //公用回传参数
            'product_code'=>'',             //商品编号
            'product_desc'=>'',             //商品描述
            'product_num'=>'',               //商品数量
            'merchant_code'=>$partner,      //商家号
            'service_type'=>$banktype,      //业务类型
            'notify_url'=>$callbackurl,     //服务器异步通知地址
            'interface_version'=>'V3.3',    //接口版本
            'sign_type'=>'RSA-S',           //签名方式
            'order_no'=>$ordernumber,       //订单号
            'client_ip'=>$client_ip,        //客户端ip
            'sign'=>'',                  //签名
            'order_time'=>date('Y-m-d H:i:s'),//订单时间
            'order_amount'=>$paymoney,      //金额
            'product_name'=>'online_zhifu'  //产品名
            );
        $postdata['sign'] = $this->_sign($postdata, $private_key);

        // 发送连接获取二维码
        // $response = pay_curl($pay_url,$postdata,'post',$pay_domain);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $pay_url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postdata));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_REFERER, $pay_domain);
        $response=curl_exec($ch);
        curl_close($ch);


        // 接收参数处理
        $response = new SimpleXMLElement($response);

        $response=$response->response;

        if ($response->resp_code!="SUCCESS" || $response->result_code ==1) {
            $str = "第三方信息: {$response->resp_desc} | {$response->result_desc}";
            $this->error_echo($str);
        }
        if(in_array($pay_data['code'],[2,5])){
            //$data 为返回的数据
            $jieguo = json_decode(json_encode($response),true);
            $url = parse_url($this->parseurl($jieguo['payURL']));//四重解码
            parse_str($url['query'],$url_arr);//url参数转数组
            $url_arr['timestamp'] = $url_arr['amp;timestamp'];
            $url_arr['nonce_str'] = $url_arr['amp;nonce_str'];
            $url_arr['notify_url'] = $url_arr['amp;notify_url'];
            if(!json_decode($url_arr['meta_option'],true)){
                $url_arr['meta_option'] = trim($url_arr['meta_option'],'"');
            }
            unset($url_arr['amp;timestamp'],$url_arr['amp;notify_url'],$url_arr['amp;nonce_str']);
            ksort($url_arr);
            $temp['method'] = 'post';
            $temp['data']   = $url_arr;
            $data['jump']      = 5; //设置支付方式的返回格式
            $temp['url'] =   $url['scheme'].'://'.$url['host'].$url['path'];
            $data['url'] ="{$pay_data['pay_domain']}/index.php/pay/pay_test/pay_sest".'/'.$order_num;
            $data['money']     = $money; //支付的钱
            $data['order_num'] = $order_num;//订单号
            $data['json'] = json_encode($temp, JSON_UNESCAPED_UNICODE);
            return $data;
        }
        $qrcode=$response->qrcode;
        /**返回时二维码**/
        //$data 为返回的数据
        $data['jump']      = 3; //设置支付方式的返回格式
        $data['img']       = $qrcode;//二维码的地址
        $data['money']     = $money; //支付的钱
        $data['order_num'] = $order_num;//订单号
        return $data;
    }

    public function wy($order_num, $money, $pay_data){
        $paymoney    = $money;
        $partner     = trim($pay_data['pay_id']); //商户ID
        $ordernumber = trim($order_num); //商户订单号
        $callbackurl = $pay_data['pay_domain'].'/index.php/callback/Duodebao/callbackurl';
        $return_url  = $pay_data['pay_domain'].'/index.php/callback/Duodebao/return_url';
        $banktype    = $this->return_code($pay_data['code'], $pay_data['bank_type']);
        $private_key =$pay_data['pay_private_key'];  //商户私钥
        $client_ip  = get_ip();
        $pay_url    = 'https://pay.ddbill.com/gateway?input_charset=UTF-8';
        $data = [
            'merchant_code' => $partner,
            'service_type'  => "direct_pay",
            'notify_url'    => $callbackurl,
            'interface_version' => 'V3.3',
            'input_charset'  => 'UTF-8',
            'pay_type'      => 'b2c',
            'client_ip'      => '127.0.0.1',
            'client_ip_check'      => '0',
            'return_url'     => $return_url,
            'order_no'       => $ordernumber,
            'order_time'     => date('Y-m-d H:i:s'),
            'order_amount'   => (string)$money,
            'bank_code'      => $banktype ,
            'product_name'   => 'online' ,
        ];
        $this->load->helper('common_helper');
        ksort($data);
        $sign = rtrim(ToUrlParams($data),'&');
        $merchant_private_key= openssl_get_privatekey($private_key);
        openssl_sign($sign,$sign_info,$merchant_private_key,OPENSSL_ALGO_MD5);
        $sign = base64_encode($sign_info);
        $data['sign'] = $sign;
        $data['sign_type']      = 'RSA-S';

        $temp['url']    = $pay_url;
        $temp['method'] = 'POST';
        $temp['data']   = $data;
        $returnData = [];
        $url = "{$pay_data['pay_domain']}/index.php/pay/pay_test/pay_sest";//表单提交的地址
        $returnData['url']   = $url;//提交的地址
        $returnData['jump']  = 5;//提交的地址
        $returnData['url']   = $url.'/'.$order_num;
        $returnData['json']  = json_encode($temp, JSON_UNESCAPED_UNICODE);
        return $returnData;


    }


    //四重解码，先替换掉%转成的码，然后再双重urldecode，然后再替换特殊字符
    private function parseurl($url="")
    {
        $url = str_replace("%2525", "%", $url);
        $url = urldecode(urldecode($url));
        $url = str_replace(["&times","&no"],["&amp;times","&amp;no"],$url);
        return $url;
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
                return 'weixin_scan';//微信
            case 2:
                return 'weixin_h5api';//微信
            case 4:
                return 'alipay_scan';
            case 5:
                return 'alipay_h5api';
            case 8:
                return 'qq_scan';
            case 9:
                return 'jdpay_scan';
            case 17:
                return 'ylpay_scan';
        }
    }

    /**
     * 智付签名
     *
     * @access private
     * @param Array $postdata   提交参数
     * @param String $merchant_private_key  商户私钥
     * @return String $sign     签名
     */
    private function _sign($postdata, $merchant_private_key)
    {
        /////////////////////////////   参数组装  /////////////////////////////////
        /**
        除了sign_type dinpaySign参数，其他非空参数都要参与组装，组装顺序是按照a~z的顺序，下划线"_"优先于字母
        */

        $merchant_code = $postdata["merchant_code"];
        $service_type = $postdata["service_type"];
        $notify_url = $postdata["notify_url"];
        $interface_version =$postdata["interface_version"];
        $client_ip = $postdata["client_ip"];
        $sign_type = $postdata["sign_type"];
        $order_no = $postdata["order_no"];
        $order_time = $postdata["order_time"];
        $order_amount =$postdata["order_amount"];
        $product_name =$postdata["product_name"];
        $product_code = $postdata["product_code"];
        $product_num = $postdata["product_num"];
        $product_desc = $postdata["product_desc"];
        $extra_return_param =$postdata["extra_return_param"];
        $extend_param = $postdata["extend_param"];


        $signStr = "";
        $signStr = $signStr."client_ip=".$client_ip."&";
        if ($extend_param != "") {
            $signStr = $signStr."extend_param=".$extend_param."&";
        }
        if ($extra_return_param != "") {
            $signStr = $signStr."extra_return_param=".$extra_return_param."&";
        }
        $signStr = $signStr."interface_version=".$interface_version."&";
        $signStr = $signStr."merchant_code=".$merchant_code."&";
        $signStr = $signStr."notify_url=".$notify_url."&";
        $signStr = $signStr."order_amount=".$order_amount."&";
        $signStr = $signStr."order_no=".$order_no."&";
        $signStr = $signStr."order_time=".$order_time."&";
        if ($product_code != "") {
            $signStr = $signStr."product_code=".$product_code."&";
        }
        if ($product_desc != "") {
            $signStr = $signStr."product_desc=".$product_desc."&";
        }
        $signStr = $signStr."product_name=".$product_name."&";
        if ($product_num != "") {
            $signStr = $signStr."product_num=".$product_num."&";
        }
        $signStr = $signStr."service_type=".$service_type;
    /////////////////////////////   RSA-S签名  /////////////////////////////////



    /////////////////////////////////初始化商户私钥//////////////////////////////////////

        $merchant_private_key= openssl_get_privatekey($merchant_private_key);
        openssl_sign($signStr, $sign_info, $merchant_private_key, OPENSSL_ALGO_MD5);
        $sign = base64_encode($sign_info);
    /////////////////////////  提交参数到智汇付网关  ////////////////////////
        return $sign;
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
