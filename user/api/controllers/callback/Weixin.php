<?php
/**
 * 支付回调模板
 * Created by PhpStorm.
 * User: shenshilin
 * Date: 2017/5/3
 * Time: 19:38
 */
defined('BASEPATH') or exit('No direct script access allowed');

class Weixin extends GC_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('pay/Online_model');
    }

    public $echo_str = "SUCCESS";  //回调地址层
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
        $xml = $GLOBALS['HTTP_RAW_POST_DATA'];
        if (empty($xml)) {
            $xml = file_get_contents('php://input');
            ;
        }
        $data = FromXml($xml);

        $ordernumber    = $data['out_trade_no'];

        $bool = $this->Online_model->fbs_lock('temp:new_order_num'.$ordernumber);
        if (!$bool) {
            die('请稍后');
        }
        // 根据订单号获取配置信息
        $payconf     = $this->Online_model->order_detail($ordernumber);
        if (empty($payconf)) {
            $erroStr = '获取数据方式'.' .无效的订单号:'.$xml;
            $this->Online_model->online_erro($payconf['id'], $erroStr);
            die('无效的订单号');
        }
        // 根据订单号 获取入款注单数据
        $tokenKey = $payconf['pay_key'];
        // 签名
        $sign           = $data['sign'];
        $paymoney       = $data['total_fee']/100;
        unset($data['sign']);
        ksort($data);
        $str = ToUrlParams($data);
        $str.="&key=".$payconf['pay_key'];

        if ($sign != strtoupper(md5($str))) {
            $erroStr = '签名验证失败:'.$xml;
            $this->Online_model->online_erro($payconf['id'], $erroStr);
            die('验证失败');
        }
        if ($payconf['price'] != $paymoney) {
            $erroStr = '订单金额验证失败:'.$xml;
            $this->Online_model->online_erro($payconf['id'], $erroStr);
            die('金额错误');
        }
        //已充值的状态
        if ($payconf['status'] == 2) {
            echo $this->echo_str;
            die;
        }
        if ($data['return_code'] != 'SUCCESS') {
            $erroStr = '订单状态未成功:'.$xml;
            $this->Online_model->online_erro($payconf['id'], $erroStr);
            die('订单失败');
        }

        $bool = $this->Online_model->update_order($payconf);
        if ($bool) {
            echo $this->echo_str;
            die;
        }
        $erroStr = '写入现金记录失败:'.$xml;
        $this->Online_model->online_erro($payconf['id'], $erroStr);
    }
}
