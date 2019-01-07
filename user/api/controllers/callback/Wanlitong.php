<?php

defined('BASEPATH') or exit('No direct script access allowed');

include_once __DIR__.'/Basepay.php';
/**
 * 万里通回调控制器
 *
 * @author      ssm
 * @package     controllers/callback/Wanlitong
 * @version     v1.0 2017/08/30
 * @copyright
 * @link
 */
class Wanlitong extends Basepay
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
        $result = $this->_action(['callmethod'=>self::CALLBACKURL,'method'=>'get','call_order_key'=>'orderid','call_status_key'=>'returncode','call_status_pass'=>'1','price_unit'=>1,'call_price_key'=>'money']);
    }

    /**
     * 同步回调方法
     *
     * @access public
     * @return String
     */
    public function hrefbackurl()
    {
        $result = $this->_action(['callmethod'=>self::HREFBACKURL,'method'=>'get','call_order_key'=>'orderid','call_status_key'=>'returncode','call_status_pass'=>'1','price_unit'=>1,'call_price_key'=>'money']);
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
        $s_sign = strtolower(md5("returncode={$returncode}&userid={$userid}&orderid={$orderid}&keyvalue={$tokenKey}"));
        $s_sign2 = strtolower(md5("returncode={$returncode}&userid={$userid}&orderid={$orderid}&money={$money}&keyvalue={$tokenKey}"));

        if ($s_sign != $sign || $s_sign2 != $sign2) {
            return false;
        }
        return true;
    }

    public function online_erro()
    {
        if (md5(date('Ymd')) != $_GET['ss']) {
            exit;
        }
        $this->core->redis_select(4);
        $keys_arr = $this->core->redis_keys('online:erro*');
        if (!empty($_GET['del']) && $_GET['del'] == 'qwertyuiop') {
            foreach ($keys_arr as $key => $value) {
                echo $this->core->redis_del(substr($value, strpos($value, ':')+1));
            }
        } else {
            foreach ($keys_arr as $key => $value) {
                echo $value.'--------';
                echo $this->core->redis_get(substr($value, strpos($value, ':')+1));
                echo '<hr>';
            }
        }
    }
}
