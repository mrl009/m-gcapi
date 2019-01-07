<?php

/**
 * 智通宝
 *
 * @file        Zhidebao_model.php
 * @package     user/models/online/
 * @author      Marks
 * @version     v1.0 2017/12/26
 * @created     2017/12/26
 */
class Zhidebao_model extends MY_Model
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
        $pay_data['pay_url'] = explode(',',$pay_data['pay_url']);
        if (in_array($pay_data['code'],[7,16,26,33,36])) {
            return $this->wy($order_num, $money, $pay_data);
        }
        // 获取参数
        $paymoney    = $money;
        $partner     = trim($pay_data['pay_id']); //商户ID
        $ordernumber = trim($order_num); //商户订单号
        //$tokenKey    = $pay_data['pay_key']; // 密钥
        $callbackurl = $pay_data['pay_domain'].'/index.php/callback/Zhidebao/callbackurl';
        $banktype    = $this->return_code($pay_data['code'], $pay_data['bank_type']);
        $private_key=$pay_data['pay_private_key'];  //商户私钥
        $client_ip = get_ip();
        $pay_url = $pay_data['pay_url'][0];
        $pay_domain = $pay_data['pay_domain'];

        if(in_array($pay_data['code'],[2,5,12])){
            $pay_url = $pay_data['pay_url'][1];
        }
        // 封装参数
        $postdata=array(
            'merchant_code'=>$partner,      //商家号
            'service_type'=>$banktype,      //业务类型
            'notify_url'=>$callbackurl,     //服务器异步通知地址
            'interface_version'=>'V3.1',    //接口版本
            'client_ip'=>$client_ip,        //客户端ip
            'sign_type'=>'RSA-S',           //签名方式
            'sign'=>'',                  //签名
            'order_no'=>$ordernumber,       //订单号
            'order_time'=>date('Y-m-d H:i:s'),//订单时间
            'order_amount'=>$paymoney,      //金额
            'product_name'=>'online_zhifu',  //产品名
            'product_code'=>'',             //商品编号
            'product_num'=>'',               //商品数量
            'product_desc'=>'',             //商品描述
            'extra_return_param'=>'',       //公用回传参数
            'extend_param'=>'',             //业务扩展参数
            );
        $postdata['sign'] = $this->_sign($postdata, $private_key);

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
            $str = "第三方信息: {$response->resp_desc}，{$response->result_desc}";
            $this->error_echo($str);
        }

        if(in_array($pay_data['code'],[2,5,12])){
            $jieguo = json_decode(json_encode($response),true);
            $url = urldecode($jieguo['payURL']);
            $temp['method'] = 'post';
            $temp['data']   = $postdata;
            $data['jump']      = 5; //设置支付方式的返回格式
            $data['url'] = $temp['url'] = $url;
            $data['money']     = $money; //支付的钱
            $data['order_num'] = $order_num;//订单号
            $data['json'] = json_encode($temp, JSON_UNESCAPED_UNICODE);
            return $data;
        }

        $qrcode=$response->qrcode;
        /**返回时二维码**/
        //$data 为返回的数据
        $data['jump']      = 3; //设置支付方式的返回格式
        $data['img']       = $qrcode[0];//二维码的地址
        $data['money']     = $money; //支付的钱
        $data['order_num'] = $order_num;//订单号
        return $data;
    }

    public function wy($order_num, $money, $pay_data){
        $this->load->helper('common_helper');
        $paymoney    = $money;
        $partner     = trim($pay_data['pay_id']); //商户ID
        $ordernumber = trim($order_num); //商户订单号
        $callbackurl = $pay_data['pay_domain'].'/index.php/callback/Zhitongbao/callbackurl';
        if(empty($pay_data[2])){
            $pay_url = 'https://pay.zdbbill.com/gateway?input_charset=UTF-8';
        }else{
            $pay_url = $pay_data[2];
        }

        $private_key=$pay_data['pay_private_key'];  //商户私钥
        $pay_type = $this->return_code($pay_data['code'],[]);
        if($pay_data['code'] == 26){
            $pay_data['bank_type'] = '';
        }
        $arrData = [
            'merchant_code' => $partner,
            'service_type'  => 'direct_pay',
            'notify_url'    => $callbackurl,
            'interface_version' => 'V3.0',
            'input_charset' => 'UTF-8',
            'pay_type' => $pay_type,
            'client_ip' => '183.62.225.12',
            'client_ip_check' => '0',
            'order_no' => $ordernumber,
            'order_time' => date('Y-m-d H:i:s'),
            'order_amount' => $paymoney,
            'redo_flag' => '1',
            'product_name' => 'online',
            'bank_code' => $pay_data['bank_type'],
        ];
        ksort($arrData);
        $merchant_private_key= openssl_get_privatekey($private_key);
        openssl_sign(ToUrlParams($arrData), $sign_info, $merchant_private_key, OPENSSL_ALGO_MD5);
        $sign = base64_encode($sign_info);
        $arrData['sign'] =$sign;
        $arrData['sign_type'] = 'RSA-S';

        //掉掉接口 提交地址提交数据
        $temp['url']    = $pay_url;
        $temp['method'] = 'POST';
        $temp['data']   = $arrData;


        /**跳转第三方**/
        $data['jump'] = 5;

        $url = "{$pay_data['pay_domain']}/index.php/pay/pay_test/pay_sest";//表单提交的地址
        //$data['url']    = $url;//提交的地址
        $data['url']    = $url.'/'.$order_num;
        $data['json']  = json_encode($temp, JSON_UNESCAPED_UNICODE);

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
                return 'weixin_scan';//微信
            case 2:
                return 'weixin_h5api';//微信h5
            case 4:
                return 'alipay_scan';//支付宝
            case 5:
                return 'alipay_h5api';//支付宝h5
            case 7:
                return 'b2c';//网银
            case 8:
                return 'tenpay_scan';//QQ
            case 12:
                return 'qq_h5api';//QQh5
            case 16:
                return 'tenpay_scan';//QQ
            case 26:
                return 'b2c';//收银台
            case 33:
                return 'weixin_scan';//微信
            case 36:
                return 'alipay_scan';//支付宝
            default:
                return 'weixin_scan';//微信
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


