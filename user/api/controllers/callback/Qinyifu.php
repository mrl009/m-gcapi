<?php
/**
 * 支付回调模板
 * Created by PhpStorm.
 * User: shenshilin
 * Date: 2017/5/3
 * Time: 19:38
 */
defined('BASEPATH') or exit('No direct script access allowed');

class Qinyifu extends GC_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('pay/Online_model');
    }

    public $echo_str = "0";  //回调地址层
    public function index()
    {
        $data = [
            'money' =>10,
            'type'  =>'微信'
        ];
        $this->load->view('online_pay/success.html', $data);
    }
    public function callbackurl()
    {
        $this->load->helper('common_helper');
        $data = $this->P('data');
        if (empty($data)) {
            die('参数错误');
        }
        $arr = json_decode($data, true);
        $ordernumber  = $arr['orderNum'];
        $sign        = $arr['sign'];
        $orderstatus = $arr['payResult'];
        $paymoney    = $arr['amount']/100;

        $bool = $this->Online_model->fbs_lock('temp:new_order_num'.$ordernumber);
        if (!$bool) {
            die('请稍后');
        }

        if (empty($sign)) {
            die('参数错误');
        }
        // 根据订单号获取配置信息
        $payconf     = $this->Online_model->order_detail($ordernumber);
        if (empty($payconf)) {
            $erroStr = '无效的订单号:'.$this->P('data');
            ;
            $this->Online_model->online_erro($payconf['id'], $erroStr);
            die('无效的订单号');
        }
        // 根据订单号 获取入款注单数据
        $tokenKey = $payconf['pay_key'];
        // 签名
        $bool = $this->is_sign($arr, $tokenKey);
        if (!$bool) {
            $erroStr = '签名验证失败:'.$this->P('data');
            ;
            $this->Online_model->online_erro($payconf['id'], $erroStr);
            die('验证失败');
        }
        if ($payconf['price'] != $paymoney) {
            $erroStr = '订单金额验证失败:'.$this->P('data');
            ;
            $this->Online_model->online_erro($payconf['id'], $erroStr);
            die('金额错误');
        }
        //已充值的状态
        if ($payconf['status'] == 2) {
            echo $this->echo_str;
            die;
        }
        if ($orderstatus !="00") {
            $erroStr = '订单状态未成功:'.$this->P('data');
            ;
            $this->Online_model->online_erro($payconf['id'], $erroStr);
            die('订单失败');
        }

        $bool = $this->Online_model->update_order($payconf);
        if ($bool) {
            echo $this->echo_str;
            die;
        }
        $erroStr = '写入现金记录失败:'.$this->P('data');
        ;
        $this->Online_model->online_erro($payconf['id'], $erroStr);
        echo "加钱失败";
    }


    public function callbacknewurl()
    {
        //暂未完成
        $this->load->helper('common_helper');
        $data = $this->P('data');
        if (empty($data)) {
            die('参数错误');
        }

        $data = urldecode($data);

        //此处需要查询出对应的私钥
        $private_key = '';
        $private_key_select = '';
        $private_key = "-----BEGIN RSA PRIVATE KEY-----\r\n";
        foreach (str_split($private_key_select,64) as $str){
            $private_key = $private_key . $str . "\r\n";
        }
        $private_key = $private_key . "-----END RSA PRIVATE KEY-----";


        $data = $this->decode($data,$private_key);
        $rows = $this->callback_to_array($data,$this->key);
       /* log_write("收到支付回调通知");
        log_write(PS($rows));*/

        $arr = json_decode($data, true);
        $ordernumber  = $arr['orderNum'];
        $sign        = $arr['sign'];
        $orderstatus = $arr['payResult'];
        $paymoney    = $arr['amount']/100;

        $bool = $this->Online_model->fbs_lock('temp:new_order_num'.$ordernumber);
        if (!$bool) {
            die('请稍后');
        }

        if (empty($sign)) {
            die('参数错误');
        }
        // 根据订单号获取配置信息
        $payconf     = $this->Online_model->order_detail($ordernumber);
        if (empty($payconf)) {
            $erroStr = '无效的订单号:'.$this->P('data');
            ;
            $this->Online_model->online_erro($payconf['id'], $erroStr);
            die('无效的订单号');
        }
        // 根据订单号 获取入款注单数据
        $tokenKey = $payconf['pay_key'];
        // 签名
        $bool = $this->is_sign($arr, $tokenKey);
        if (!$bool) {
            $erroStr = '签名验证失败:'.$this->P('data');
            ;
            $this->Online_model->online_erro($payconf['id'], $erroStr);
            die('验证失败');
        }
        if ($payconf['price'] != $paymoney) {
            $erroStr = '订单金额验证失败:'.$this->P('data');
            ;
            $this->Online_model->online_erro($payconf['id'], $erroStr);
            die('金额错误');
        }
        //已充值的状态
        if ($payconf['status'] == 2) {
            echo $this->echo_str;
            die;
        }
        if ($orderstatus !="00") {
            $erroStr = '订单状态未成功:'.$this->P('data');
            ;
            $this->Online_model->online_erro($payconf['id'], $erroStr);
            die('订单失败');
        }

        $bool = $this->Online_model->update_order($payconf);
        if ($bool) {
            echo $this->echo_str;
            die;
        }
        $erroStr = '写入现金记录失败:'.$this->P('data');
        ;
        $this->Online_model->online_erro($payconf['id'], $erroStr);
        echo "加钱失败";
    }


    private function is_sign($row, $signKey)
    { #效验服务器返回数据
        $r_sign = $row['sign']; #保留签名数据
        $arr = array();
        foreach ($row as $key=>$v) {
            if ($key !== 'sign') { #删除签名
                $arr[$key] = (string)$v;
            }
        }
        ksort($arr);
        $sign = strtoupper(md5(my_json_encode($arr) . $signKey)); #生成签名

        if ($sign == $r_sign) {
            return true;
        } else {
            return false;
        }
    }

    private function json_encode_ex($value){
        if (version_compare(PHP_VERSION,'5.4.0','<')){
            $str = json_encode($value);
            $str = preg_replace_callback("#\\\u([0-9a-f]{4})#i","replace_unicode_escape_sequence",$str);
            $str = stripslashes($str);
            return $str;
        }else{
            return json_encode($value,320);
        }
    }


    private function callback_to_array($json,$key){
        $array = json_decode($json,true);
        $sign_string = $array['sign'];
        ksort($array);
        $sign_array = array();
        foreach ($array as $k => $v) {
            if ($k !== 'sign'){
                $sign_array[$k] = $v;
            }
        }

        $md5 =  strtoupper(md5($this->json_encode_ex($sign_array) . $key));
        if ($md5 == $sign_string){
            return $sign_array;
        }else{
            $result = array();
            $result['payResult'] = '99';
            $result['msg'] = '返回签名验证失败';
            return $result;
        }

    }

    private function decode($data,$private_key){
        $pr_key = openssl_get_privatekey($private_key);
        if ($pr_key == false){
            echo "打开密钥出错";
            die;
        }
        $data = base64_decode($data);
        $crypto = '';
        foreach (str_split($data, 128) as $chunk) {
            openssl_private_decrypt($chunk, $decryptData, $pr_key);
            $crypto .= $decryptData;
        }
        return $crypto;
    }


}







