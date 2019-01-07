<?php

defined('BASEPATH') or exit('No direct script access allowed');

include_once __DIR__.'/Basepay.php';
/**
 * 回调控制器
 *
 * @author
 * @package     controllers/callback/Ruyifu
 * @version
 * @copyright
 * @link
 */
class Ruyifu extends Basepay
{
    /**
     * 支付成功返回的参数
     * @var String
     */
    public $echo_str = "ErrCode=0";


    /**
     * 异步回调方法
     *
     * @access public
     * @return String $echo_str|或者错误信息
     */
    public function callbackurl()
    {
        $result = $this->_action(['callmethod'=>self::CALLBACKURL,'method'=>'get','call_order_key'=>'P_OrderId','call_status_key'=>'P_ErrCode','call_status_pass'=>'0','price_unit'=>1, 'call_price_key'=>'P_PayMoney']);

    }

    /**
     * 同步回调方法
     *
     * @access public
     * @return String
     */
    public function hrefbackurl()
    {
        $result = $this->_action(['callmethod'=>self::HREFBACKURL,'method'=>'get','call_order_key'=>'P_OrderId','call_status_key'=>'P_ErrCode','call_status_pass'=>'0','price_unit'=>1, 'call_price_key'=>'P_PayMoney']);
    }

    /**
     * 实现签名验证
     *
     * @access protected
     * @param Array $data 回调参数
     * @param Array $payconf 支付信息
     * @return Boolean
     */
    protected function _is_sign_succeed($data, $payconf)
    {
        $tokenKey = $payconf['pay_key'];
        $sign1 = $data['P_PostKey'];
        /*$sign2 = md5(sprintf('partner=%s&ordernumber=%s&orderstatus=%s&paymoney=%s%s',
            $data['partner'], $data['ordernumber'], $data['orderstatus'],$data['paymoney'], $tokenKey));*/
        $sign2 =md5($data['P_UserId'].'|'.$data['P_OrderId'].'|'.$data['P_CardId'].'|'.$data['P_CardPass'].'|'.$data['P_FaceValue'].'|'.
                $data['P_ChannelId'].'|'.$data['P_PayMoney'].'|'.$data['P_ErrCode'].'|'.$tokenKey);
        if ($sign2 === $sign1) {
            return true;
        }
        return false;
    }
}
