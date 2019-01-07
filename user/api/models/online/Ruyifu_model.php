<?php

defined('BASEPATH') or exit('No direct script access allowed');

include_once __DIR__.'/Basepay_model.php';
/**
 * 如一支付模块
 *
 * @author      Marks
 * @package     model/online/Ruyifu
 * @version     v1.0 2017/12/25
 * @copyright
 * @link
 */
class Ruyifu_model extends Basepay_model
{

    private $scan = array(1,4,8,9,10,17,22);

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
        //目前只有 1，4，7，8，12可用
        switch ($code) {
            case 1:
                return '21';//微信扫码
            case 2:
                return '33';//微信WAP
            case 33:
                return '88';//微信公众号
            case 4:
                return '2';//支付宝扫码
            case 5:
                return '36';//支付宝wap
            case 36:
                return '93';//支付宝公众号
            case 7:
                return '1';//网银
            case 8:
                return '89';//QQ扫码
            case 9:
                return '91';//京东
            case 10:
                return '90';//百度
            case 12:
                return '92';//QQwap
            case 16:
                return '94';//QQ公众号
            case 17:
                return '95';//银联
            case 22:
                return '3';//财付通
            case 25:
                return '32';//快捷支付
            case 27:
                return '31';//网银WAP
            default:
                return '21';
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
        $money = sprintf('%.2f',$money);
        $data['P_UserID'] = (string)trim($pay_data['pay_id']);
        $data['P_OrderID'] = $order_num;
        $data['P_CardId'] = '';
        $data['P_CardPass'] = '';
        $data['P_FaceValue']  =  $money;
        $data['P_ChannelID']  = $this->_get_code($pay_data['code'], $pay_data['bank_type']);
        $data['P_Subject']  = '';
        $data['P_Price']  = $money;
        $data['P_Quantity']  = '';
        if($pay_data['code']==7 || $pay_data['code']==27){
            $data['P_Description']  =  $pay_data['bank_type'];
        }
        $data['P_Notic']  = '';
        $data['P_ISsmart']  = 0;
        $data['P_Result_URL'] = $pay_data['pay_domain'].'/index.php/callback/Ruyifu/callbackurl';
        $data['P_Notify_URL'] = $pay_data['pay_domain'].'/index.php/callback/Ruyifu/hrefbackurl';
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
        $str = $data['P_UserID'].'|'.$data['P_OrderID'].'|'.$data['P_CardId'].'|'.$data['P_CardPass']
            .'|'.$data['P_FaceValue'].'|'.$data['P_ChannelID'].'|'.$tokenKey;

        $post_key = md5($str);
        return $post_key;
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
        $ZFdata['P_PostKey'] = $ZFdata['sign'];
        unset($ZFdata['sign']);
        $data = parent::_get_result2('post', $order_num, $ZFdata, $pay_data);
        $data['money']  = $ZFdata['P_Price'];
        $data['order_num']  = $order_num;
        return $data;
    }

    /*private function run2($url, $data, $method)
    {
        $str = '<html>
        <head>
            <!--支付测试表单提交-->
            <title>跳转...</title>
            <meta http-equiv="content-Type" content="text/html; charset=utf-8" />
        </head>
        <body onload="document.form1.submit();">
        <p>页面跳转中...</p>';
        $str .= '<form id="form1" name="form1" method="'.$method.'" action="'.$url.'">';

        foreach ($data as $key => $value) {
            $str .= '<input type="hidden" name="'.$key.'" value="'.$value.'"/>';
        }
        $str .= '</form>';
        $str .=  '</body>';
        $str .= '</html>';
        echo $str;
    }*/

}
