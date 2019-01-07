<?php

defined('BASEPATH') or exit('No direct script access allowed');

include_once __DIR__.'/Basepay.php';
/**
 * 艾米森回调控制器
 *
 * @author      ssm
 * @package     controllers/callback/Yuebao
 * @version     v1.0 2017/08/30
 * @copyright
 * @link
 */
class Yuebao extends Basepay
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
        $result = $this->_action(['callmethod'=>self::HREFBACKURL,'method'=>'get','call_order_key'=>'ordernumber','call_status_key'=>'orderstatus','call_status_pass'=>'1','price_unit'=>1, 'call_price_key'=>'paymoney']);
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
        extract($data);

        $signSource = sprintf("partner=%s&ordernumber=%s&orderstatus=%s&paymoney=%s%s", $partner, $ordernumber, $orderstatus, $paymoney, $tokenKey);

        $signValue = md5($signSource);

        if ($sign === $signValue) {
            return true;
        }
        return false;
    }

}
