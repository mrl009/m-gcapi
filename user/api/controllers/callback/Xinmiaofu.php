<?php
/**
 * 新秒付
 * Created by PhpStorm.
 * User: shenshilin
 * Date: 2017/5/3
 * Time: 19:38
 */
defined('BASEPATH') or exit('No direct script access allowed');

class Xinmiaofu extends GC_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->model('pay/Online_model');
    }

    public $echo_str = "ok";  //回调地址层共返回

    public function callbackurl()
    {
        $post_data = array(

            "merchant_code"         => $_GET['merchant_code'],
            "notify_type"           => $_GET['notify_type'],
            "order_no"              => $_GET['order_no'],
            "order_time"            => $_GET['order_time'],
            "trade_no"              => $_GET['trade_no'],
            "order_amount"          => $_GET['order_amount'],
            "order_time"            => $_GET['order_time'],
            "trade_time"            => $_GET['trade_time'],
            "trade_status"          => $_GET['trade_status'],
        );
        $ordernumber = $post_data['order_no'];
        $orderstatus =$post_data['trade_status'];
        $payconf     = $this->Online_model->order_detail($ordernumber);

        $bool = $this->Online_model->fbs_lock('temp:new_order_num'.$ordernumber);
        if (!$bool) {
            die('请稍后');
        }

        if (empty($payconf)) {
            $erroStr = '无效的订单号:'.json_encode($_GET, JSON_UNESCAPED_UNICODE);
            $this->Online_model->online_erro($payconf['id'], $erroStr);
            die('无效的订单号');
        }
        $tokenKey = $payconf['pay_key'];
        ksort($post_data);

        // 签名
        $a='';
        foreach ($post_data as $x=>$x_value) {
            if ($x_value) {
                $a=$a.$x."=".$x_value."&";
            }
        }
        $b=md5($a.'key='.$tokenKey);
        $c= $_GET['sign'];

        if ($c !== $b) {
            $erroStr = '签名验证失败:'.json_encode($_GET, JSON_UNESCAPED_UNICODE);
            $this->Online_model->online_erro($payconf['id'], $erroStr);
            die('验证失败');
        }
        //判断支付状态
        if ($orderstatus != 'success') {
            $erroStr = '订单状态未成功:'.json_encode($_GET, JSON_UNESCAPED_UNICODE);
            $this->Online_model->online_erro($payconf['id'], $erroStr);
            die('订单失败');
        }

        if ($payconf['price'] != $post_data['order_amount']) {
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
            $erroStr = 'ok:'.json_encode($_GET, JSON_UNESCAPED_UNICODE);
            $this->Online_model->online_erro($payconf['id'], $erroStr);
            echo $this->echo_str;
            die;
        }
        $erroStr = '写入现金记录失败:'.json_encode($_GET, JSON_UNESCAPED_UNICODE);
        $this->Online_model->online_erro($payconf['id'], $erroStr);
    }

    // 同步通知地址
    public function hrefbackurl()
    {
        $post_data = array(

            "merchant_code"         => $_GET['merchant_code'],
            "notify_type"           => $_GET['notify_type'],
            "order_no"              => $_GET['order_no'],
            "order_time"            => $_GET['order_time'],
            "trade_no"              => $_GET['trade_no'],
            "order_amount"          => $_GET['order_amount'],
            "order_time"            => $_GET['order_time'],
            "trade_time"            => $_GET['trade_time'],
            "trade_status"          => $_GET['trade_status'],
        );
        $ordernumber = $post_data['order_no'];
        $paymoney    = $post_data['order_amount'];
        $orderstatus =$post_data['trade_status'];

        // 根据订单号获取配置信息
        $payconf     = $this->Online_model->order_detail($ordernumber);
        $tokenKey = $payconf['pay_key'];

        // 签名规则
        $a='';
        foreach ($post_data as $x=>$x_value) {
            if ($x_value) {
                $a=$a.$x."=".$x_value."&";
            }
        }
        $b=md5($a.'key='.$tokenKey);
        $c= $_GET['sign'];


        if ($b == $c) {
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
            $this->load->view('online_pay/success.html', $data);
        }
    }
}
