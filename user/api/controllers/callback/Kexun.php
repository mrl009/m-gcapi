<?php
/**
 * 聚源支付的同步地址回调地址
 * Created by PhpStorm.
 * User: shenshilin
 * Date: 2017/5/3
 * Time: 19:38
 */
defined('BASEPATH') or exit('No direct script access allowed');

class Kexun extends GC_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->model('pay/Online_model');
    }

    private $cash_key = "cash:count:online";//入款成功后需要添加redis 记录 hash结构 加上pay_id
    public $echo_str = "opstate=0";  //回调地址层共返回

    public function callbackurl()
    {
        $ordernumber    = $this->input->get('orderid');  //商户订单号
        $orderstatus    = $this->input->get('opstate'); //订单结果
        $paymoney       = $this->input->get('ovalue'); //订单金额
        $sign           = $this->input->get('sign'); // MD5签名值
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
        $str      = "orderid={$ordernumber}&opstate={$orderstatus}&ovalue={$paymoney}$tokenKey";
        $str  = iconv("utf-8", "gb2312//IGNORE", $str);
        if ($sign != MD5($str)) {
            $erroStr = '签名验证失败:'.json_encode($_GET, JSON_UNESCAPED_UNICODE);
            $this->Online_model->online_erro($payconf['id'], $erroStr);
            die('验证失败');
        }
        //判断支付状态
        if ($orderstatus != 0) {
            $erroStr = '订单状态未成功:'.json_encode($_GET, JSON_UNESCAPED_UNICODE);
            $this->Online_model->online_erro($payconf['id'], $erroStr);
            die('支付失败');
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
        $ordernumber    = $this->input->get('orderid');  //商户订单号
        $orderstatus    = $this->input->get('opstate'); //订单结果
        $paymoney    = $this->input->get('ovalue'); //订单金额
        $sign           = $this->input->get('sign'); // MD5签名值

        // 根据订单号获取配置信息
        $payconf     = $this->Online_model->order_detail($ordernumber);
        $tokenKey = $payconf['pay_key'];

        // 签名规则
        $str = "orderid={$ordernumber}&opstate={$orderstatus}&ovalue={$paymoney}$tokenKey";
        $str  = iconv("utf-8", "gb2312//IGNORE", $str);

        if ($sign == MD5($str)) {
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
