<?php

defined('BASEPATH') or exit('No direct script access allowed');

include_once __DIR__.'/Basepay_model.php';
/**
 * 支付模块
 *
 * @author      ssm
 * @package     model/online/Ankuai
 * @version     v1.0 2017/08/30
 * @copyright
 * @link
 */
class Ankuai_model extends Basepay_model
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
                return 'WEIXIN';//微信
            case 2:
                return 'WEIXINWAP';//微信wap
            case 4:
                return 'ALIPAY';//支付宝
            case 5:
                return 'ALIPAYWAP';//支付宝wap
            case 7:
                return $bank;//银行
            case 8:
                return 'QQ';//qq钱包
            case 9:
                return 'JD';//qq钱包
            case 12:
                return 'QQWAP';//qq钱包wap
            default:
                return 'WEIXIN';//微信
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
        $data['partner'] = trim($pay_data['pay_id']);
        $data['callbackurl'] = $pay_data['pay_domain'].'/index.php/callback/Ankuai/hrefbackurl';
        $data['hrefbackurl'] = $pay_data['pay_domain'].'/index.php/callback/Ankuai/hrefbackurl';
        $data['paymoney']  =  $money;
        $data['ordernumber'] = $order_num;
        $data['attach'] = 'ankuaizhifu';
        $bank_code = $this->_get_code($pay_data['code'], $pay_data['bank_type']);
        $data['banktype']  = $bank_code;

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
        extract($data);
        $tokenKey = $pay_data['pay_key'];
        $signSource = sprintf("partner=%s&banktype=%s&paymoney=%s&ordernumber=%s&callbackurl=%s%s", $partner, $banktype, $paymoney, $ordernumber, $callbackurl, $tokenKey);
        return md5($signSource);
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
