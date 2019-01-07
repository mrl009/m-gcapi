<?php

defined('BASEPATH') or exit('No direct script access allowed');

include_once __DIR__.'/Basepay_model.php';
/**
 * 万里通支付模块
 * @如果一段时间没有使用，全部支付将不能使用，需要找客服重新开通
 *
 * @author      ssm
 * @package     model/online/Wanlitong
 * @version     v1.0 2017/09/01
 * @copyright
 * @link
 */
class Wanlitong_model extends Basepay_model
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
                return '2001';
            case 1:
                return '2005';
            case 4:
                return '2003';
            case 5:
                return '2007';
            case 7:
                return $bank;
            case 8:
                return '2008';
            default:
                return '2001';
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
        $data['userid'] = (string)trim($pay_data['pay_id']);
        $data['url'] = $pay_data['pay_domain'].'/index.php/callback/Wanlitong/callbackurl';
        $data['aurl'] = $pay_data['pay_domain'].'/index.php/callback/Wanlitong/hrefbackurl';
        $data['money']  =  $money;
        $data['orderid'] = $order_num;
        $bank_code = $this->_get_code($pay_data['code'], $pay_data['bank_type']);
        $data['bankid']  = $bank_code;

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
        extract($data);
        return strtolower(md5("userid={$userid}&orderid={$orderid}&bankid={$bankid}&money={$money}&keyvalue={$tokenKey}"));
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
        $data = parent::_get_result2('get', $order_num, $ZFdata, $pay_data);
        return $data;
    }
}
