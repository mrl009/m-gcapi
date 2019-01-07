<?php
/**
 * 支付回调模板
 * Created by PhpStorm.
 * User: shenshilin
 * Date: 2017/5/3
 * Time: 19:38
 */
defined('BASEPATH') or exit('No direct script access allowed');

class Yafu extends GC_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('pay/Online_model');
    }

    public $echo_str = "SUCCESS";  //回调地址层

    public function callbackurl()
    {
        $ordernumber = trim($this->P('merOrderNo'));
        $orderstatus = trim($this->P('orderStatus'));
        $paymoney    = trim($this->P('transAmt'));
        $sign        = trim($this->P('sign'));
        $erroStr      = "";
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
            $erroStr = '无效的订单号:'.json_encode($_POST, JSON_UNESCAPED_UNICODE);
            $this->Online_model->online_erro($payconf['id'], $erroStr);
            die('无效的订单号');
        }
        // 根据订单号 获取入款注单数据
        $tokenKey = $payconf['pay_key'];
        // 签名
        $data = $this->input->post();
        ksort($data);
        $signStr = '';
        foreach ($data as $k => $value) {
            if ($value && $k !='sign') {
                $signStr .= $k . '=' . $value . '&';
            }
        }
        if ( strtoupper($sign) != strtoupper(MD5($signStr.'key='.$tokenKey)) ) {
            $erroStr = '签名验证失败:'.json_encode($_POST, JSON_UNESCAPED_UNICODE);
            $this->Online_model->online_erro($payconf['id'], $erroStr);
            die('验证失败');
        }
        if ($payconf['price'] != $paymoney) {
            $erroStr = '订单金额验证失败:'.json_encode($_POST, JSON_UNESCAPED_UNICODE);
            $this->Online_model->online_erro($payconf['id'], $erroStr);
            die('金额错误');
        }
        //已充值的状态
        if ($payconf['status'] == 2) {
            echo $this->echo_str;
            die;
        }
        if ($orderstatus !=1) {
            $erroStr = '订单状态未成功:'.json_encode($_POST, JSON_UNESCAPED_UNICODE);
            $this->Online_model->online_erro($payconf['id'], $erroStr);
            die('订单失败');
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
        $ordernumber = trim($this->P('merOrderNo'));
        $orderstatus = trim($this->P('orderStatus'));
        $paymoney    = trim($this->P('transAmt'));
        $sign        = trim($this->P('sign'));

        // 根据订单号获取配置信息
        $payconf     = $this->Online_model->order_detail($ordernumber);
        $tokenKey = $payconf['pay_key'];

        // 签名规则
        $data = $this->input->post();
        ksort($data);
        $signStr = '';
        foreach ($data as $k => $value) {
            if ($value && $k !='sign') {
                $signStr .= $k . '=' . $value . '&';
            }
        }
        
        if (  strtoupper($sign) != strtoupper(MD5($signStr.'key='.$tokenKey)) ) {
            $data['ordernumber'] = $ordernumber;
            $data = [
                'ordernumber'=>$ordernumber,
                'money' =>$paymoney,
                'jsstr'  =>$this->Online_model->return_jsStr($payconf['from_way'], $payconf['pay_return_url']),
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
