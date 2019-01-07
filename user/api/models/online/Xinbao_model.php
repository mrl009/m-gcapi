<?php

defined('BASEPATH') or exit('No direct script access allowed');

include_once __DIR__.'/Basepay_model.php';
/**
 * 新宝支付模块
 * @异步回调是post，同步回调是get
 *
 * @author      ssm
 * @package     model/online/xinbao
 * @version     v1.0 2017/08/30
 * @copyright
 * @link
 */
class Xinbao_model extends Basepay_model
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
                return '0002';//微信
            case 2:
                return '0004';//微信
            case 4:
                return '0003';//支付宝
            case 5:
                return '0005';//支付宝app
            case 7:
                return $bank;//网银
            case 8:
                return '0006';//qq钱包
            default:
                return '0002';//百度钱包
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
        $data['amount']     =  sprintf('%.2f', $money);
        $data['version']    = "V1.0";
        $data['order_no']   = $order_num;
        $data['partner_id'] = (string)trim($pay_data['pay_id']);
        $data['notify_url'] = $pay_data['pay_domain'].'/index.php/callback/xinbao/callbackurl';
        $data['return_url'] = $pay_data['pay_domain'].'/index.php/callback/xinbao/hrefbackurl';
        $bank_code = $this->_get_code($pay_data['code'], $pay_data['bank_type']);
        if ($pay_data['code'] == 7) {
            $data['pay_type']  = '0001';
            $data['bank_code'] = $bank_code;
        } else {
            $data['pay_type']  = $bank_code;
        }
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
        $k = '';
        foreach ($data as $key => $value) {
            $k .= $key.'='.$value.'&';
        }
        return strtolower(md5($k.$tokenKey));
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
        $data = parent::_get_result2('post', $order_num, $ZFdata, $pay_data);
        return $data;
    }
}
