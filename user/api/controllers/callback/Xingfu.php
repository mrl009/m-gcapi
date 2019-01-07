<?php

defined('BASEPATH') or exit('No direct script access allowed');

include_once __DIR__.'/Basepay.php';
/**
 * 星付回调控制器
 *
 * @author      ssm
 * @package     controllers/callback/Xingfu
 * @version     v1.0 2017/08/30
 * @copyright
 * @link
 */
class Xingfu extends Basepay
{
    /**
     * 支付成功返回的参数
     * @var String
     */
    public $echo_str = "SUCCESS";


    /**
     * 异步回调方法
     *
     * @access public
     * @return String $echo_str|或者错误信息
     */
    public function callbackurl()
    {
        $result = $this->_action(['callmethod'=>self::CALLBACKURL,'method'=>'request','call_order_key'=>'tradeNo','call_status_key'=>'status','call_status_pass'=>'1','price_unit'=>1, 'call_price_key'=>'amount']);
    }

    /**
     * 同步回调方法
     *
     * @access public
     * @return String
     */
    public function hrefbackurl()
    {
        // $_REQUEST = json_decode('{"service":"TRADE.NOTIFY","merId":"2017100212013740","tradeNo":"59d4af901e801","tradeDate":"20171004","opeNo":"7643003","opeDate":"20171004","amount":"0.10","status":"1","extra":"","payTime":"20171004180450","sign":"397AE63AD34DD6AC34CA6E8E0ECCB0A2","notifyType":"1"}', true);

        $result = $this->_action(['callmethod'=>self::HREFBACKURL,'method'=>'request','call_order_key'=>'tradeNo','call_status_key'=>'status','call_status_pass'=>'1','price_unit'=>1, 'call_price_key'=>'amount']);
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
        $result = sprintf(
                    "service=%s&merId=%s&tradeNo=%s&tradeDate=%s&opeNo=%s&opeDate=%s&amount=%s&status=%s&extra=%s&payTime=%s",
                    $data['service'],
                    $data['merId'],
                    $data['tradeNo'],
                    $data['tradeDate'],
                    $data['opeNo'],
                    $data['opeDate'],
                    $data['amount'],
                    $data['status'],
                    $data['extra'],
                    $data['payTime']
            );
        $sign1 = $data['sign'];
        $sign2 = strtoupper(md5($result.$tokenKey));
        if ($sign2 === $sign1) {
            return true;
        }
        return false;
    }

}
