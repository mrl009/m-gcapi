<?php

defined('BASEPATH') or exit('No direct script access allowed');

include_once __DIR__.'/Basepay.php';
/**
 * 闪付回调控制器
 *
 * @author      ssm
 * @package     controllers/callback/Shanfu
 * @version     v1.0 2017/08/30
 * @copyright
 * @link
 */
class Shanfu extends Basepay
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
        $result = $this->_action(['callmethod'=>self::CALLBACKURL,'method'=>'request','call_order_key'=>'TransID','call_status_key'=>'Result','call_status_pass'=>'1','price_unit'=>100, 'call_price_key'=>'FactMoney']);
    }

    /**
     * 同步回调方法
     *
     * @access public
     * @return String
     */
    public function hrefbackurl()
    {
        $result = $this->_action(['callmethod'=>self::HREFBACKURL,'method'=>'request','call_order_key'=>'TransID','call_status_key'=>'Result','call_status_pass'=>'1','price_unit'=>100, 'call_price_key'=>'FactMoney']);
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
        $MARK = "~|~";
        $Md5key = $payconf['pay_key'];
        extract($data);
        $sign2 = md5('MemberID='.$MemberID.$MARK.'TerminalID='.$TerminalID.$MARK.'TransID='.$TransID.$MARK.'Result='.$Result.$MARK.'ResultDesc='.$ResultDesc.$MARK.'FactMoney='.$FactMoney.$MARK.'AdditionalInfo='.$AdditionalInfo.$MARK.'SuccTime='.$SuccTime.$MARK.'Md5Sign='.$Md5key);
        $sign1 = $data['Md5Sign'];
        if ($sign2 == $sign1) {
            return true;
        }
        return false;
    }

}
