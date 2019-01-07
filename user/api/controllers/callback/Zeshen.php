<?php
/**
 * 泽圣的同步地址回调地址
 * Created by PhpStorm.
 * User: shenshilin
 * Date: 2017/5/3
 * Time: 19:38
 */
defined('BASEPATH') or exit('No direct script access allowed');

class Zeshen extends GC_Controller
{
    public function __construct()
    {
        parent::__construct();
        //$this->load->model('pay/Pay_model');
        $this->load->helper('common_helper');
        $this->load->model('pay/Online_model');
    }

    public $echo_str = '{"code": "00"}';  //回调地址层共返回

    public function callbackurl()
    {
        $data = [
            'merchantCode'  => $this->P('merchantCode'),
            'instructCode' => $this->P('instructCode'),
            'transType'  => $this->P('transType'),
            'outOrderId' => $this->P('outOrderId'),
            'transTime'  => $this->P('transTime'),
            'totalAmount'  => $this->P('totalAmount'),
        ];
        ksort($data);
        $ordernumber     = $this->P('outOrderId');

        $bool = $this->Online_model->fbs_lock('temp:new_order_num'.$ordernumber);
        if (!$bool) {
            die('请稍后');
        }

        $payconf     = $this->Online_model->order_detail($ordernumber);
        if (empty($payconf)) {
            $erroStr = '无效的订单号:'.json_encode($_POST, JSON_UNESCAPED_UNICODE);
            $this->Online_model->online_erro($payconf['id'], $erroStr);
            die('无效的订单号');
        }
        $tokenKey = $payconf['pay_key'];
        $signStr  = '';
        foreach($data as $k => $v){
            $signStr.= $k.'='.$v.'&';
        }
        $signStr .= 'KEY='.$tokenKey;
        // 签名
        if (strtoupper($this->P('sign')) != strtoupper(MD5($signStr))) {
            $erroStr = '签名验证失败:'.json_encode($_POST, JSON_UNESCAPED_UNICODE);
            $this->Online_model->online_erro($payconf['id'], $erroStr);
            die('验证失败');
        }

        if ($payconf['price'] != $data['totalAmount']/100) {
            $erroStr = '订单金额验证失败:'.json_encode($_POST, JSON_UNESCAPED_UNICODE);
            $this->Online_model->online_erro($payconf['id'], $erroStr);
            die('金额错误');
        }
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
        $erroStr = '写入现金记录失败:'.json_encode($_POST, JSON_UNESCAPED_UNICODE);
        $this->Online_model->online_erro($payconf['id'], $erroStr);
        echo "加钱失败";
    }

    // 同步通知地址
    public function hrefbackurl()
    {
        $data = [
            'merchantCode'  => $this->P('merchantCode'),
            'instructCode' => $this->P('instructCode'),
            'transType'  => $this->P('transType'),
            'outOrderId' => $this->P('outOrderId'),
            'transTime'  => $this->P('transTime'),
            'totalAmount'  => $this->P('totalAmount'),
        ];
        ksort($data);
        $ordernumber     = $this->P('outOrderId');
        $payconf     = $this->Online_model->order_detail($ordernumber);
        $tokenKey = $payconf['pay_key'];
        $signStr      = '';
        foreach($data as $k=>$v){
            $signStr.= $k.'='.$v.'&';
        }
        $signStr .= 'KEY='.$tokenKey;
        // 签名
        if (strtoupper($this->P('sign')) != strtoupper(MD5($signStr))) {
            $data['ordernumber'] = $ordernumber;
            $data = [
                'ordernumber'=>$ordernumber,
                'money' =>$data['totalAmount']/100,
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
