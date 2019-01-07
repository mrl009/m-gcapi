<?php

defined('BASEPATH') or exit('No direct script access allowed');

include_once __DIR__.'/Basepay_model.php';
/**
 * 新码支付模块
 *
 * @author      ssm
 * @package     model/online/Xinma
 * @version     v1.0 2017/09/22
 * @copyright
 * @link
 */
class Xinma_model extends Basepay_model
{

    /**
     *【支付类型@1微信#2微信app#3微信扫码#
     * 4支付宝#5支付宝APP#6支付宝扫码
     * #7网银#8QQ钱包#9京东钱包#10百度钱包#11点卡
     *
     * @access protected
     * @param Integer $code 支付编号
     * @param String $bank 银行编号
     * @return String
     */
    protected function _get_code($code, $bank)
    {
        switch ($code) {
            case 1:
                return 10;//微信
            case 4:
                return 20;//支付宝
            case 7:
                return $bank;//支付宝
            case 8:
                return 50;//qq钱包
            default:
                return 10;//百度钱包
        }
    }

    /**
     * 获取支付参数
     *
     * @access protected
     * @param String $order_num 订单号
     * @param Float $money 金额
     * @param String $pay_data 第三方支付信息
     * @return Array
     */
    protected function _get_data($order_num, $money, $pay_data)
    {
        $data = array(
            'messageid'         => '200001',
            'out_trade_no'      => $order_num,
            'back_notify_url'   => $pay_data['pay_domain'].'/index.php/callback/Xinma/callbackurl',
            'branch_id'         => (string)trim($pay_data['pay_id']),
            'prod_name'         => 'xinma',
            'prod_desc'         => 'xinma',
            'pay_type'          => $this->_get_code($pay_data['code'], $pay_data['bank_type']),
            'total_fee'         => $money*100,
            'nonce_str'         => $this->_createNoncestr(32)
        );

        return $data;
    }

    /**
     * 获取支付签名
     *
     * @access protected
     * @param Array $data 支付参数
     * @param Array $pay_data 支付信息
     * @return String $sign签名值
     */
    protected function _get_sign($data, $pay_data)
    {
        $tokenKey = $pay_data['pay_key'];
        ksort($data);
        $result = array();
        foreach ($data as $key => $value) {
            if($value == null) {
                continue;
            }
            $result[$key] = $value;
        }
        $k = urldecode(http_build_query($result));
        return strtoupper(md5($k."&key=".$tokenKey));
    }

    /**
     * 获取支付结果
     *
     * @access protected
     * @param String $order_num 订单号
     * @param Array $data 支付参数
     * @param Array $pay_data 支付信息
     * @return Array
     */
    protected function _get_result($order_num, $ZFdata, $pay_data)
    {
        $res = $this->httpPost($pay_data['pay_url'], $ZFdata, $pay_data['shopurl']);
        $res = json_decode($res, true);

        if ($res['resultCode'] == '00' && $res['resCode'] == '00') {
            $resultToSign = array();
            foreach ($res as $key => $value) {
                if ($key != 'sign') {
                    $resultToSign[$key] = $value;
                }
            }
            $sign = $this->_get_sign($resultToSign, $pay_data);
            if ($sign != $res['sign']) {
                $str = "签名错误，请联系客服";
                $this->_error_echo($str);
            }

            $data['jump']   = 3; //设置支付方式的返回格式
            $data['img']    = $res['payUrl'];//二维码的
            $data['money']  = $ZFdata['total_fee']/100; //支付的钱
            $data['order_num']  = $ZFdata['out_trade_no'];//订单号
            return $data;
        }
        $str = "错误信息: {$res['resDesc']}";
        $this->_error_echo($str);
    }


    protected function _createNoncestr($length) {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $res = '';
        for ($i = 0; $i < $length; $i++) {
            $random = mt_rand(0, strlen($chars)-1);
            $res .= $chars{$random};
        }
        return $res;
    }
    public function urlencode_array($array) {
        foreach($array as $k => $v) {
            if(is_array($v)) {
                $array[$k] = $this->urlencode_array($v);
            } else {
                $array[$k] = urlencode($v);
            }
        }
        return $array;
    }
    public function zh_json_encode($array) {
        $array = $this->urlencode_array($array);
        return urldecode(json_encode($array));
    }
    public function httpPost($url, $post_data,$pay_domain) {

        $data_string = $this->zh_json_encode($post_data);
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($curl, CURLOPT_POSTFIELDS,$data_string);
        curl_setopt($curl, CURLOPT_TIMEOUT, 30); // 设置超时限制防止死循环
        curl_setopt($curl, CURLOPT_RETURNTRANSFER,true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_REFERER, $pay_domain);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false); // 从证书中检查SSL加密算法是否存在
        curl_setopt($curl, CURLOPT_URL, $url); // 要访问的地址
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1); // 使用自动跳转
        curl_setopt($curl, CURLOPT_AUTOREFERER, 1); // 自动设置Referer

        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($data_string))
        );
        $result = curl_exec($curl);
        if (curl_errno($curl)) {
            echo 'Errno'.curl_error($curl);//捕抓异常
        }
        curl_close($curl);
        return $result;

    }
}
