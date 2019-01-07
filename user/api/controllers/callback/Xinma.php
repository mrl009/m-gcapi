<?php

defined('BASEPATH') or exit('No direct script access allowed');

include_once __DIR__.'/Basepay.php';
/**
 * 新码回调控制器
 *
 * @author      ssm
 * @package     controllers/callback/Xinma
 * @version     v1.0 2017/08/30
 * @copyright
 * @link
 */
class Xinma extends Basepay
{
    /**
     * 支付成功返回的参数
     * @var String
     */
    public $echo_str = '{"resDesc":"SUCCESS","resCode":"00"}';


    /**
     * 异步回调方法
     *
     * @access public
     * @return String $echo_str|或者错误信息
     */
    public function callbackurl()
    {
        // $GLOBALS['HTTP_RAW_POST_DATA'] = '{\"createTime\":\"20170923140812\",\"status\":\"02\",\"nonceStr\":\"uwHdtvQmCf4hLhqc2mMheIDfReTVdKb1\",\"resultDesc\":\"成功\",\"outTradeNo\":\"3471709231408349063\",\"sign\":\"8A16B896F22C74AA57620436057D8E03\",\"productDesc\":\"xinma\",\"orderNo\":\"p2017092314081300322734\",\"branchId\":\"170900142281\",\"resultCode\":\"00\",\"resCode\":\"00\",\"payType\":\"10\",\"resDesc\":\"成功\",\"orderAmt\":100}';

        $result = $this->_action(['callmethod'=>self::CALLBACKURL,'method'=>'globals','globals_name'=>'HTTP_RAW_POST_DATA','call_order_key'=>'outTradeNo','call_status_key'=>'resultCode','call_status_pass'=>'00','price_unit'=>100, 'call_price_key'=>'orderAmt']);
    }

    /**
     * 同步回调方法
     *
     * @access public
     * @return String
     */
    public function hrefbackurl()
    {
        // $result = $this->_action(['callmethod'=>self::HREFBACKURL,'method'=>'post','call_order_key'=>'out_trade_no','call_status_key'=>'order_status','call_status_pass'=>'3','price_unit'=>100]);
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
        if($data['resCode'] != '00') {
            return false;
        }
        $tokenKey = $payconf['pay_key'];
        $sign1 = $data['sign'];
        unset($data['sign']);
        ksort($data);
        $result = array();
        foreach ($data as $key => $value) {
            if($value == null) {
                continue;
            }
            $result[$key] = $value;
        }
        $k = urldecode(http_build_query($result));
        $sign2 = strtoupper(md5($k."&key=".$tokenKey));
        if ($sign2 === $sign1) {
            return true;
        }
        return false;
    }

}
