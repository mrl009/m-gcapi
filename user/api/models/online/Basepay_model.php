<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * 支付父model
 *
 * @author      ssm
 * @package     model/online/basepay
 * @version     v1.0 2017/09/02
 * @copyright
 * @link
 */
abstract class Basepay_model extends MY_Model
{

    /**
     * 抽象方法：签名验证
     *
     * @access protected
     * @param Array $data 回调参数
     * @param Array $pay_data 支付信息
     * @return String $sign签名值
     */
    protected abstract function _get_sign($data, $pay_data);

    /**
     * 抽象方法：获取支付参数
     *
     * @access protected
     * @param String $order_num 订单号
     * @param Float $money 金额
     * @param String $pay_data 第三方支付信息
     * @return Array
     */
    protected abstract function _get_data($order_num, $money, $pay_data);

    /**
     * 抽象方法：获取支付类型编码
     *【支付类型@1微信#2微信app#3微信扫码#
     * 4支付宝#5支付宝APP#6支付宝扫码
     * #7网银#8QQ钱包#9京东钱包#10百度钱包#11点卡
     *
     * @access private
     * @param Integer $code 支付编号
     * @param String $bank 银行编号
     * @return String
     */
    protected abstract function _get_code($code, $bank);

    /**
     * 获取支付结果
     *
     * @access protected
     * @param String $order_num 订单号
     * @param Array $data 支付参数
     * @param Array $pay_data 支付信息
     * @return Array
     */
    protected abstract function _get_result($order_num, $ZFdata, $pay_data);

    /**
     * 第三方支付主方法
     *
     * @access public
     * @param String $order_num 订单号
     * @param Float $money 金额
     * @param String $pay_data 第三方支付信息
     * @return Array
     */
    public function call_interface($order_num, $money, $pay_data)
    {
        /*** 1.获取支付数据 ***/
        $data = $this->_get_data($order_num, $money, $pay_data);
        /*** 2.获取支付签名 ***/
        $data['sign'] = $this->_get_sign($data, $pay_data);
        /*** 3.获取支付结果 ***/
        $result = $this->_get_result($order_num, $data, $pay_data);
        // dump($result);exit;
        return $result;
    }

    /**
     * 获取支付结果
     *
     * @access protected
     * @param String $method 提交方法post|get
     * @param String $order_num 订单号
     * @param Array $data 支付参数
     * @param Array $pay_data 支付信息
     * @return Array
     */
    protected function _get_result2($method, $order_num, $data, $pay_data)
    {
        //掉掉接口 提交地址提交数据
        $temp['url']    = $pay_data['pay_url'];
        $temp['method'] = $method;
        $temp['data']   = $data;
        $url = "{$pay_data['pay_domain']}/index.php/pay/pay_test/pay_sest";
        /**跳转第三方**/
        $wap_code = [2,5,11,13,18,20,23];
        if (in_array($pay_data['code'], $wap_code)) {
            $resu['jump'] = 5;
        } else {
            $resu['jump'] = 5;
        }
        $resu['url']    = $url.'/'.$order_num;
        $resu['json']  = json_encode($temp, JSON_UNESCAPED_UNICODE);
        return $resu;
    }

    /**
     * 发送post请求
     *
     * @access protected
     * @param String $pay_url 支付网关
     * @param String $pay_domain 支付域名
     * @param Array $data 数据
     * @return String
     */
    protected function pay_curl($pay_url, $pay_domain, $data)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $pay_url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_REFERER, $pay_domain);
        curl_setopt($ch, CURLOPT_TIMEOUT,(int)7);
        $res=curl_exec($ch);
        curl_close($ch);
        return $res;
    }

    /**
     * 接口信息错误
     *
     * @access protected
     * @return void
     */
    protected function _error_echo($str)
    {
        $data['code'] = E_ARGS;
        $data['msg']  = $str;
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        die;
    }
}