<?php
/**
 * 佰付通支付
 * Created by PhpStorm.
 * User: shenshilin
 * Date: 2017/5/3
 * Time: 19:38
 */
defined('BASEPATH') or exit('No direct script access allowed');

class Baitong extends GC_Controller
{
    public function __construct()
    {
        parent::__construct();
        //$this->load->model('pay/Pay_model');
        $this->load->model('pay/Online_model');
    }

    public $echo_str = "SUCCESS";  //回调地址层共返回
    public function index()
    {
    }
    public function callbackurl()
    {
        $ordernumber    = $this->input->post('orderNo');  //商户订单号
        $orderstatus    = $this->input->post('orderStatus'); //订单结果
        $paymoney       = $this->input->post('tradeAmt'); //订单金额
        $sign           = $this->input->post('signMsg'); // MD5签名值

        $bool = $this->Online_model->fbs_lock('temp:new_order_num'.$ordernumber);
        if (!$bool) {
            die('请稍后');
        }
        if (empty($sign)) {
            die('参数错误');
        }

        $payconf     = $this->Online_model->order_detail($ordernumber);
        if (empty($payconf)) {
            $erroStr = '无效的订单号:'.json_encode($_POST, JSON_UNESCAPED_UNICODE);
            $this->Online_model->online_erro($payconf['id'], $erroStr);
            die('无效的订单号');
        }

        $data = [
            'apiName' => $this->input->post('apiName'),
            'notifyTime' => $this->input->post('notifyTime'),
            'tradeAmt' => $this->input->post('tradeAmt'),
            'merchNo' => $this->input->post('merchNo'),
            'merchParam' => $this->input->post('merchParam'),
            'orderNo' => $this->input->post('orderNo'),
            'tradeDate' => $this->input->post('tradeDate'),
            'accNo' => $this->input->post('accNo'),
            'accDate' => $this->input->post('accDate'),
            'orderStatus' => $this->input->post('orderStatus'),
            'notifyType' => $this->input->post('notifyType'),
        ];
        $tokenKey = $payconf['pay_key'];
        $result = sprintf(
            "apiName=%s&notifyTime=%s&tradeAmt=%s&merchNo=%s&merchParam=%s&orderNo=%s&tradeDate=%s&accNo=%s&accDate=%s&orderStatus=%s",
            $data['apiName'], $data['notifyTime'], number_format($data['tradeAmt'],2), $data['merchNo'], $data['merchParam'], $data['orderNo'], $data['tradeDate'], $data['accNo'], $data['accDate'], $data['orderStatus']
        ); // 签名
        if ($sign != strtoupper(MD5($result.$tokenKey))) {
            $erroStr = '签名验证失败:'.json_encode($_POST, JSON_UNESCAPED_UNICODE);
            $this->Online_model->online_erro($payconf['id'], $erroStr);
            die('验证失败');
        }

        if ($orderstatus != 1) {
            $erroStr = '订单状态未成功:'.json_encode($_POST, JSON_UNESCAPED_UNICODE);
            $this->Online_model->online_erro($payconf['id'], $erroStr);
            die('订单失败');
        }

        if ($payconf['price'] != $paymoney) {
            $erroStr = '订单金额验证失败:'.json_encode($_POST, JSON_UNESCAPED_UNICODE);
            $this->Online_model->online_erro($payconf['id'], $erroStr);
            die('金额错误');
        }
        /***数据验证通过开始执行订单更新**/
        if ($data['notifyType'] == 0) {
            $data = [
                'ordernumber'=>$ordernumber,
                'money' =>$paymoney,
                'type'  =>code_pay($payconf['pay_code']),
                'jsstr'  =>$this->Online_model->return_jsStr($payconf['from_way'], $payconf['pay_return_url'])
            ];
            $this->load->view('online_pay/success.html', $data);
            die;
        }

        $bool = $this->Online_model->update_order($payconf);
        if ($bool) {
            echo $this->echo_str;
            die;
        }
        $erroStr = '写入现金记录失败:'.json_encode($_POST, JSON_UNESCAPED_UNICODE);
        $this->Online_model->online_erro($payconf['id'], $erroStr);
        echo "加钱失败";
    }

    // 同步通知地址
    public function hrefbackurl()
    {
        $ordernumber    = $this->input->get('ordernumber');  //商户订单号
        $partner        = $this->input->get('partner');  //商户订单号
        $orderstatus    = $this->input->get('orderstatus'); //订单结果
        $paymoney       = $this->input->get('paymoney'); //订单金额
        $sign           = $this->input->get('sign'); // MD5签名值
        if (empty($sign)) {
            die('参数错误');
        }
        // 根据订单号获取配置信息
        $payconf     = $this->Online_model->order_detail($ordernumber);
        $tokenKey = $payconf['pay_key'];
        // 签名规则
        $str      = "partner={$partner}&ordernumber={$ordernumber}&orderstatus={$orderstatus}&paymoney={$paymoney}$tokenKey";

        $data = [
            'ordernumber'=>$ordernumber,
            'money' =>$paymoney,
            'type'  =>code_pay($payconf['pay_code']),
            'jsstr'  =>$this->Online_model->return_jsStr($payconf['from_way'], $payconf['pay_return_url'])
        ];

        if ($sign == strtolower(MD5($str))) {
            $data['ordernumber'] = $ordernumber;
            $this->load->view('online_pay/success.html', $data);
        } else {
            $data = [
                'msg' =>'支付回调验证失败'
            ];
            $this->load->view('online_pay/error.html', $data);
        }
    }
}
