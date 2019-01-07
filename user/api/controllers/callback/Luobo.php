<?php

defined('BASEPATH') or exit('No direct script access allowed');

include_once __DIR__.'/Basepay.php';
/**
 * 萝卜回调控制器
 *
 * @author      ssm
 * @package     controllers/callback/Luobo
 * @version     v1.0 2017/08/30
 * @copyright
 * @link
 */
class Luobo extends Basepay
{
    /**
     * 支付成功返回的参数
     * @var String
     */
    public $echo_str = "opstate=0";


    /**
     * 异步回调方法
     *
     * @access public
     * @return String $echo_str|或者错误信息
     */
    public function callbackurl()
    {
        $result = $this->_action(['callmethod'=>self::CALLBACKURL,'method'=>'get','call_order_key'=>'orderid','call_status_key'=>'opstate','call_status_pass'=>'0','price_unit'=>1, 'call_price_key'=>'ovalue']);
    }

    /**
     * 同步回调方法
     *
     * @access public
     * @return String
     */
    public function hrefbackurl()
    {
        $result = $this->_action(['callmethod'=>self::HREFBACKURL,'method'=>'get','call_order_key'=>'orderid','call_status_key'=>'opstate','call_status_pass'=>'0','price_unit'=>1, 'call_price_key'=>'ovalue']);
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
        $sign2 = md5(sprintf('orderid=%s&opstate=%s&ovalue=%s%s', 
                $data['orderid'],
                $data['opstate'],
                $data['ovalue'],
                $tokenKey));

        if ($sign2 === $sign1) {
            return true;
        }
        return false;
    }

}
