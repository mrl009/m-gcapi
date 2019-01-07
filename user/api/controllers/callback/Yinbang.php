<?php
/**
 * 支付回调demo
 * Created by PhpStorm.
 * User: shenshilin
 * Date: 2017/5/3
 * Time: 19:38
 */
defined('BASEPATH') or exit('No direct script access allowed');

class Yinbang extends GC_Controller
{
    private $private_key = null;
    private $public_key = null;
    public $echo_str = "SUCCESS";  //回调地址层共返回
    public function __construct()
    {
        parent::__construct();
        $this->load->model('pay/Online_model');
        $this->_get_public_key();
    }

    // 异步通知地址
    public function callbackurl()
    {
        $sign=$_REQUEST['sign'];//数字签名
        $merId=$_REQUEST['merId'];//商户号
        $version=$_REQUEST['version'];//版本号
        $encParam=$_REQUEST['encParam'];//业务参数

        //验证签名与业务参数是否通过
        if (openssl_verify(base64_decode($encParam), base64_decode($sign), $this->public_key)) {
            $res=json_decode($this->_decrypt($encParam), true);
        }
        if (empty($res)) {
            echo 'error';
        }

        $ordernumber    = $res['orderId'];  //商户订单号
        $partner        = $res['order_state'];  //
        $paymoney       = $res['money']/100; //订单金额


        $bool = $this->Online_model->fbs_lock('temp:new_order_num'.$ordernumber);
        if (!$bool) {
            die('请稍后');
        }

        $payconf     = $this->Online_model->order_detail($ordernumber);

        if (empty($payconf)) {
            $erroStr = '无效的订单号:'.json_encode($_REQUEST, JSON_UNESCAPED_UNICODE);
            $this->Online_model->online_erro('yingbang', $erroStr);
            die('无效的订单号');
        }
        $tokenKey = $payconf['pay_key'];

        //判断支付状态
        if ($partner != '1003') {
            $erroStr = '订单状态未成功:'.json_encode($_REQUEST, JSON_UNESCAPED_UNICODE);
            $this->Online_model->online_erro($payconf['id'], $erroStr);
            die('订单失败');
        }

        if ($payconf['price'] != $paymoney) {
            $erroStr = '订单金额验证失败:'.json_encode($_REQUEST, JSON_UNESCAPED_UNICODE);
            $this->Online_model->online_erro($payconf['id'], $erroStr);
            die('金额错误');
        }
        //已经确认
        if ($payconf['status'] == 2) {
            echo $this->echo_str;
            die;
        }

        $bool = $this->Online_model->update_order($payconf);
        if ($bool) {
            $data = [
                'ordernumber'=>$ordernumber,
                'money' =>$paymoney,
                'jsstr'  =>$this->Online_model->return_jsStr($payconf['from_way'],  $payconf['pay_return_url']),
                'type'  =>code_pay($payconf['pay_code'])
            ];
            $this->load->view('online_pay/success.html', $data);
        } else {
            $data = [
                'msg' =>'支付回调验证失败'
            ];
            $this->load->view('online_pay/error.html', $data);
        }
    }

    // 同步通知地址
    public function hrefbackurl()
    {
        $sign=$_REQUEST['sign'];//数字签名
        $merId=$_REQUEST['merId'];//商户号
        $version=$_REQUEST['version'];//版本号
        $encParam=$_REQUEST['encParam'];//业务参数
        $this->_get_public_key();

        //验证签名与业务参数是否通过
        if (openssl_verify(base64_decode($encParam), base64_decode($sign), $this->public_key)) {
            $res=json_decode($this->_decrypt($encParam), true);
        }

        if (empty($res)) {
            $data = [
                'msg' =>'支付回调验证失败'
            ];
            $this->load->view('online_pay/error.html', $data);
        }

        $ordernumber    = $res['orderId'];  //商户订单号
        $partner        = $res['order_state'];  //
        $paymoney       = $res['money']/100; //订单金额
        $payconf     = $this->Online_model->order_detail($ordernumber);


        // 根据订单号获取配置信息
        $payconf     = $this->Online_model->order_detail($ordernumber);
        $tokenKey = $payconf['pay_key'];


        if ($partner == '1003') {
            $data['ordernumber'] = $ordernumber;
            $data = [
                'ordernumber'=>$ordernumber,
                'money' =>$paymoney,
                'jsstr'  =>$this->Online_model->return_jsStr($payconf['from_way'],  $payconf['pay_return_url']),
                'type'  =>code_pay($payconf['pay_code'])
            ];
            $this->load->view('online_pay/success.html', $data);
        } else {
            $data = [
                'msg' =>'支付回调验证失败'
            ];
            $this->load->view('online_pay/error.html', $data);
        }
    }

    //rsa解密
    private function _decrypt($data)
    {
        $priKey= openssl_get_privatekey($this->private_key);
        $data=base64_decode($data);
        $Split = str_split($data, 128);
        $back='';
        foreach ($Split as $k=>$v) {
            openssl_private_decrypt($v, $decrypted, $priKey);
            $back.= $decrypted;
        }

        return $back;
    }

    // 获取服务器公钥
    public function _get_public_key()
    {
        $this->Online_model->select_db('public');
        $pay_id = $this->Online_model->get_one('id', 'bank_online', ['online_bank_name'=>'银邦']);
        $pay_id = $pay_id['id'];
        $this->Online_model->select_db('private');
        $public_key = $this->Online_model->get_one('pay_private_key,pay_server_key', 'bank_online_pay', ['bank_o_id'=>$pay_id]);
        $this->public_key =$public_key['pay_server_key'];
        $this->private_key=$public_key['pay_private_key'];
    }
}
