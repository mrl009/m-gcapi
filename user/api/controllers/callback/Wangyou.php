<?php

defined('BASEPATH') or exit('No direct script access allowed');


/**
 * 网游回调控制器
 *
 * @author      ma
 * @package     controllers/callback/Wangyou
 * @version     v1.0 2017/11/23
 * @copyright
 * @link
 */
class Wangyou extends GC_Controller
{
    /**
     * 异步回调方法
     *
     * @access public
     * @return String $echo_str|或者错误信息
     */
    public function callbackurl()
    {

        //$result = $this->_action(['callmethod'=>self::CALLBACKURL,'method'=>'get','call_order_key'=>'out_trade_no','call_status_key'=>'respCode','call_status_pass'=>'00000','price_unit'=>1,'call_price_key'=>'money']);
        $backdata = file_get_contents('php://input');
        $backdata = str_replace("\\", '',$backdata);
        $rjo = json_decode($backdata);
        /*if(!$rjo){
            $rjo = $_POST;
            $rjo = json_decode($rjo);
        }*/
        $this->load->model('pay/Online_model');
        if(empty($rjo)){
            $this->Online_model->online_erro('json_deocde_error', $backdata);
            echo '{"status":false}';exit;
        }
        $param = array(
            'out_trade_no'=> $rjo->out_trade_no,
            'out_channel_no'=> $rjo->out_channel_no,
        );

        $payconf = $this->Online_model->order_detail($param['out_trade_no']);
        if(empty($payconf)){
            $erroStr = '无效的订单号:'.$backdata;
            $this->Online_model->online_erro($param['out_trade_no'], $erroStr);
            echo '{"status":false}';exit;
        }
        if($rjo->respCode != '00000') {
            $erroStr = '订单状态未成功:'.$backdata;
            $this->Online_model->online_erro($payconf['id'], $erroStr);
            echo '{"status":false}';exit;
        }
        //回调成功 一定要返回{"status":true}
        if($rjo->sign != $this->makeSignature($param, $payconf['pay_key'])){
            $erroStr = '签名验证失败:'.$backdata;
            $this->Online_model->online_erro($payconf['id'], $erroStr);
            echo '{"status":false}';exit;
        }
        $bool = $this->Online_model->update_order($payconf);
        if ($bool) {
            echo '{"status":true}';exit;
        }else{
            $erroStr = '写入现金记录失败:'.$backdata;
            $this->Online_model->online_erro($payconf['id'], $erroStr);
            echo '{"status":false}';exit;
        }
    }

    /*
    * 生成签名，$args为请求参数，$key为私钥
    */
    private function makeSignature($args, $key){
        if(isset($args['sign'])) {
            $oldSign = $args['sign'];
            unset($args['sign']);
        } else {
            $oldSign = '';
        }
        ksort($args);
        $requestString = '';
        foreach($args as $k => $v) {
            $requestString .= $k . '='.($v);
            $requestString .= '&';
        }
        $requestString = substr($requestString,0,strlen($requestString)-1);
        $newSign = md5( $requestString."&key=".$key);
        return $newSign;
    }

    /**
     * 同步回调方法
     *
     * @access public
     * @return String
     */
    /*public function hrefbackurl()
    {
        $result = $this->_action(['callmethod'=>self::HREFBACKURL,'method'=>'get','call_order_key'=>'out_trade_no','call_status_key'=>'respCode','call_status_pass'=>'00000','price_unit'=>1,'call_price_key'=>'money']);
    }*/

    /**
     * 实现签名验证
     *
     * @access protected
     * @param Array $data 回调参数
     * @param Array $payconf 支付信息
     * @return Boolean
     */
   /* protected function _is_sign_succeed($data, $payconf)
    {
        //$tokenKey = $payconf['pay_key'];
        extract($data);
        $s_sign = md5("out_trade_no={$out_trade_no}&out_channel_no={$out_channel_no}");

        if ($s_sign != $sign) {
            return false;
        }
        return true;
    }*/


}
