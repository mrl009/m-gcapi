<?php

defined('BASEPATH') or exit('No direct script access allowed');

include_once __DIR__.'/Basepay_model.php';
/**
 * 591支付模块
 *
 * @author      Marks
 * @package     model/online/Wujiuyi
 * @version     v1.0 2017/12/22
 * @copyright
 * @link
 */
class Wujiuyi_model extends Basepay_model
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
    protected function _get_code($code, $bank=null)
    {
        switch ($code) {
            case 1:
                return '1004';//微信掃碼
            case 2:
                return '1007';//微信wap
            case 4:
                return '992';//支付寶掃碼
            case 5:
                return '1003';//支付寶wap
            case 8:
                return '1008';//QQ掃碼
            case 9:
                return '1010';//京東掃碼
            case 10:
                return '1012';//百度掃碼
            case 12:
                return '1009';//QQwap
            case 13:
                return '1011';//京東wap
            case 20:
                return '1013';//百度wap
            default:
                return '1004';
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
        $data['parter'] = (string)trim($pay_data['pay_id']);
        $data['callbackurl'] = $pay_data['pay_domain'].'/index.php/callback/Wujiuyi/callbackurl';
        $data['hrefbackurl'] = $pay_data['pay_domain'].'/index.php/callback/Wujiuyi/hrefbackurl';
        $data['value']  =  $money;
        $data['orderid'] = $order_num;
        $bank_code = $this->_get_code($pay_data['code'], $pay_data['bank_type']);
        $data['type']  = $bank_code;

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

        return md5(sprintf("parter=%s&type=%s&value=%s&orderid=%s&callbackurl=%s%s", 
            $data['parter'],
            $data['type'],
            $data['value'],
            $data['orderid'],
            $data['callbackurl'],
            $tokenKey));
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
        $data['money']     = $ZFdata['value']; //支付的钱
        $data['order_num'] = $order_num;//订单号
        return $data;
    }
}
