<?php

defined('BASEPATH') or exit('No direct script access allowed');

include_once __DIR__.'/Basepay.php';
/**
 * 回调控制器
 *
 * @author
 * @package     controllers/callback/Jinyang
 * @version
 * @copyright
 * @link
 */
class Jinyang extends Basepay
{
    /**
     * 支付成功返回的参数
     * @var String
     */
    public $echo_str = "ok";


    /**
     * 异步回调方法
     *
     * @access public
     * @return String $echo_str|或者错误信息
     */
    public function callbackurl()
    {
        $result = $this->_action(['callmethod'=>self::CALLBACKURL,'method'=>'get','call_order_key'=>'ordernumber','call_status_key'=>'orderstatus','call_status_pass'=>'1','price_unit'=>1, 'call_price_key'=>'paymoney']);

    }

    /**
     * 同步回调方法
     *
     * @access public
     * @return String
     */
    public function hrefbackurl()
    {
        $result = $this->_action(['callmethod'=>self::HREFBACKURL,'method'=>'get','call_order_key'=>'ordernumber','call_status_key'=>'orderstatus','call_status_pass'=>'1','price_unit'=>1, 'call_price_key'=>'paymoney']);
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
        $sign1 = $data['sign'];
        $sign2 = md5(sprintf('partner=%s&ordernumber=%s&orderstatus=%s&paymoney=%s%s',
            $data['partner'], $data['ordernumber'], $data['orderstatus'],$data['paymoney'], $tokenKey));
        if ($sign2 === $sign1) {
            return true;
        }
        return false;
    }
}
