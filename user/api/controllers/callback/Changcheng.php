<?php

defined('BASEPATH') or exit('No direct script access allowed');

include_once __DIR__.'/Basepay.php';
/**
 * 长城回调控制器
 *
 * @author      ssm
 * @package     controllers/callback/Changcheng
 * @version     v1.0 2017/10/13
 * @copyright
 * @link
 */
class Changcheng extends Basepay
{
    /**
     * 支付成功返回的参数
     * @var String
     */
    public $echo_str = "success";


    /**
     * 异步回调方法
     *
     * @access public
     * @return String $echo_str|或者错误信息
     */
    public function callbackurl()
    {
        $result = $this->_action(['callmethod'=>self::CALLBACKURL,'method'=>'post','call_order_key'=>'traceno','call_status_key'=>'status','call_status_pass'=>'1','price_unit'=>1, 'call_price_key'=>'amount']);
    }

    /**
     * 同步回调方法
     *
     * @access public
     * @return String
     */
    public function hrefbackurl()
    {
        // $result = $this->_action(['callmethod'=>self::HREFBACKURL,'method'=>'post','call_order_key'=>'traceno','call_status_key'=>'status','call_status_pass'=>'1','price_unit'=>1, 'call_price_key'=>'amount']);
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
        return true;
        $tokenKey = $payconf['pay_key'];
        $sign1 = $data['signature'];
        unset($data['sign']);
        ksort($data);
        $k = '';
        foreach ($data as $key => $value) {
            if (!empty($value)) {
                $k .= $key.'='.$value.'&';
            }
        }
        $sign2 = (md5($k.$tokenKey));
        if ($sign2 === $sign1) {
            return true;
        }
        return false;
    }

}
