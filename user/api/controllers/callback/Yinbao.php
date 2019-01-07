<?php
/**
 * 银宝支付回调同步地址
 * Created by PhpStorm.
 * User: shenshilin
 * Date: 2017/5/3
 * Time: 19:38
 */
defined('BASEPATH') or exit('No direct script access allowed');

class Yinbao extends GC_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->model('pay/Online_model');
    }

    public $echo_str = "ok";  //回调地址层共返回

    public function callbackurl()
    {
        $ordernumber    = $this->G('ordernumber');  //商户订单号
        $partner        = $this->G('partner');  //
        $orderstatus    = $this->G('orderstatus'); //订单结果
        $paymoney       = $this->G('paymoney'); //订单金额
        $sign           = $this->G('sign'); // MD5签名值


        $bool = $this->Online_model->fbs_lock('temp:new_order_num'.$ordernumber);
        if (!$bool) {
            die('请稍后');
        }

        if (empty($sign)) {
            die('参数错误');
        }
        $payconf     = $this->Online_model->order_detail($ordernumber);

        if (empty($payconf)) {
            $erroStr = '无效的订单号:'.json_encode($_GET, JSON_UNESCAPED_UNICODE);
            $this->Online_model->online_erro($payconf['id'], $erroStr);
            die('无效的订单号');
        }
        $tokenKey = $payconf['pay_key'];
        // 签名
        $str      = "partner={$partner}&ordernumber={$ordernumber}&orderstatus={$orderstatus}&paymoney={$paymoney}$tokenKey";
        $signValue = md5($str);
        if ($sign !== $signValue) {
            $erroStr = '签名验证失败:'.json_encode($_GET, JSON_UNESCAPED_UNICODE);
            $this->Online_model->online_erro($payconf['id'], $erroStr);
            die('验证失败');
        }
        //判断支付状态
        if ($orderstatus != 1) {
            $erroStr = '订单状态未成功:'.json_encode($_GET, JSON_UNESCAPED_UNICODE);
            $this->Online_model->online_erro($payconf['id'], $erroStr);
            die('订单失败');
        }

        if ($payconf['price'] != $paymoney) {
            $erroStr = '订单金额验证失败:'.json_encode($_GET, JSON_UNESCAPED_UNICODE);
            $this->Online_model->online_erro($payconf['id'], $erroStr);
            die('金额错误');
        }
        //已经确认
        if ($payconf['status'] == 2) {
            echo $this->echo_str;
            die;
        }

        /***数据验证通过开始执行订单更新**/

        $bool = $this->Online_model->update_order($payconf);
        if ($bool) {
            echo $this->echo_str;
            die;
        }
        $erroStr = '写入现金记录失败:'.json_encode($_GET, JSON_UNESCAPED_UNICODE);
        $this->Online_model->online_erro($payconf['id'], $erroStr);
        echo "加钱失败";
    }

    // 同步通知地址
    public function hrefbackurl()
    {
        $ordernumber    = $this->G('ordernumber');  //商户订单号
        $partner        = $this->G('partner');  //商户订单号
        $orderstatus    = $this->G('orderstatus'); //订单结果
        $paymoney       = $this->G('paymoney'); //订单金额
        $sign           = $this->G('sign'); // MD5签名值


        // 根据订单号获取配置信息
        $payconf  = $this->Online_model->order_detail($ordernumber);
        $tokenKey = $payconf['pay_key'];

        // 签名规则
        $str      = "partner={$partner}&ordernumber={$ordernumber}&orderstatus={$orderstatus}&paymoney={$paymoney}$tokenKey";

        if ($sign == MD5($str)) {
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
}
