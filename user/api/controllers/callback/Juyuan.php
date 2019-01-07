<?php
/**
 * 聚源支付的同步地址回调地址
 * Created by PhpStorm.
 * User: shenshilin
 * Date: 2017/5/3
 * Time: 19:38
 */
defined('BASEPATH') or exit('No direct script access allowed');

class Juyuan extends GC_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('pay/Online_model');
    }

    public $echo_str = "ok";  //回调地址层
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
        $partner     = trim($this->G('partner'));
        $ordernumber = trim($this->G('ordernumber'));
        $orderstatus = trim($this->G('orderstatus'));
        $paymoney    = trim($this->G('paymoney'));
        $sysnumber   = trim($this->G('sysnumber'));
        $attach      = trim($this->G('attach'));
        $sign        = trim($this->G('sign'));
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
            $erroStr = '无效的订单号:'.json_encode($_GET, JSON_UNESCAPED_UNICODE);
            $this->Online_model->online_erro($payconf['id'], $erroStr);
            die('无效的订单号');
        }
        // 根据订单号 获取入款注单数据
        $tokenKey = $payconf['pay_key'];
        // 签名
        $str = 'partner=' . $partner . '&ordernumber=' . $ordernumber . '&orderstatus=' . $orderstatus . '&paymoney=' . $paymoney . $tokenKey;
        $signValue = md5($str);
        if ($sign !== $signValue) {
            $erroStr = '签名验证失败:'.json_encode($_GET, JSON_UNESCAPED_UNICODE);
            $this->Online_model->online_erro($payconf['id'], $erroStr);
            die('验证失败');
        }
        if ($payconf['price'] != $paymoney) {
            $erroStr = '订单金额验证失败:'.json_encode($_GET, JSON_UNESCAPED_UNICODE);
            $this->Online_model->online_erro($payconf['id'], $erroStr);
            die('金额错误');
        }
        //已充值的状态
        if ($payconf['status'] == 2) {
            echo $this->echo_str;
            die;
        }
        if ($orderstatus !=1) {
            $erroStr = '订单状态未成功:'.json_encode($_GET, JSON_UNESCAPED_UNICODE);
            $this->Online_model->online_erro($payconf['id'], $erroStr);
            die('订单失败');
        }
        
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
        $partner     = $this->input->get('partner');
        $ordernumber = $this->input->get('ordernumber'); //商户订单号
        $orderstatus = $this->input->get('orderstatus'); //订单结果
        $paymoney    = $this->input->get('paymoney'); //充值金额
        $sign        = $this->input->get('sign'); // MD5签名值

        // 根据订单号获取配置信息
        $payconf     = $this->Online_model->order_detail($ordernumber);
        $tokenKey = $payconf['pay_key'];

        // 签名规则
        $signText = 'partner=' . $partner . '&ordernumber=' . $ordernumber . '&orderstatus=' . $orderstatus . '&paymoney=' . $paymoney . $tokenKey; //MD5加密签名

        if ($sign == strtolower(MD5($signText))) {
            $data['ordernumber'] = $ordernumber;
            $data = [
                'ordernumber'=>$ordernumber,
                'money' =>$paymoney,
                'type'  =>code_pay($payconf['pay_code']),
                'jsstr'  =>$this->Online_model->return_jsStr($payconf['from_way'], $payconf['pay_return_url'])
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
