<?php

defined('BASEPATH') or exit('No direct script access allowed');

include_once __DIR__.'/Basepay_model.php';
/**
 * 闪付支付模块
 *
 * @author      ssm
 * @package     model/online/Shanfu
 * @version     v1.0 2017/08/30
 * @copyright
 * @link
 */
class Shanfu_model extends Basepay_model
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
                return '57';//微信
            case 4:
                return '758';//支付宝
            case 7:
                return $bank;//支付宝
            case 8:
                return '77';//qq钱包
            default:
                return '57';//百度钱包
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
        $data['MemberID'] = trim($pay_data['pay_id']);
        $data['TerminalID'] = trim($pay_data['pay_server_num']);
        $data['KeyType'] = "1";
        $data['PayID'] = $this->_get_code($pay_data['code'], $pay_data['bank_type']);
        $data['TradeDate'] = date('Ymdhis');
        $data['TransID'] = trim($order_num);
        $data['OrderMoney'] = $money*100;
        $data['PageUrl'] = $pay_data['pay_domain'].'/index.php/callback/Shanfu/hrefbackurl';
        $data['ReturnUrl'] = $pay_data['pay_domain'].'/index.php/callback/Shanfu/callbackurl';
        $data['NoticeType'] = "1";
        $data['InterfaceVersion'] = "4.0";
        
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
        $MARK = "|";
        $Md5key = $pay_data['pay_key'];
        $Signature=md5($MemberID.$MARK.$PayID.$MARK.$TradeDate.$MARK.$TransID.$MARK.$OrderMoney.$MARK.$PageUrl.$MARK.$ReturnUrl.$MARK.$NoticeType.$MARK.$Md5key);
        return $Signature;
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
        $ZFdata['Signature'] = $ZFdata['sign'];unset($ZFdata['sign']);
        $data = parent::_get_result2('post', $order_num, $ZFdata, $pay_data);
        return $data;
    }
}
