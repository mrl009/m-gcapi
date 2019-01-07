<?php
/**
 * 支付回调模板
 * Created by PhpStorm.
 * User: shenshilin
 * Date: 2017/5/3
 * Time: 19:38
 */
defined('BASEPATH') or exit('No direct script access allowed');

class Xunjietong extends GC_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('pay/Online_model');
    }

    public $echo_str = "success";  //回调地址层
    public function index()
    {
        $data = [
            'money' =>10,
            'type'  =>'微信',
            'jsstr'  =>$this->Online_model->return_jsStr($this->from_way),
            'msg'   =>'测试',

        ];
        $this->load->view('online_pay/success.html', $data);
    }
    public function callbackurl()
    {
        $post_data = array(
            "transDate"    =>$this->P('transDate'),
            "transTime"    =>$this->P('transTime'),
            "merchno"      =>$this->P('merchno'),
            "merchName"      =>$this->P('merchName'),
            "openId"       =>$this->P('openId'),
            "amount"       =>$this->P('amount'),
            "traceno"      =>$this->P('traceno'),
            "payType"        =>$this->P('payType'),
            "orderno"        =>$this->P('orderno'),
            "channelOrderno" => $this->P('channelOrderno'),
            "channelTraceno" =>$this->P('channelTraceno'),
            "status"         =>$this->P('status'),
        );
        $sign = $this->P('signature');
        $paymoney    = $post_data['amount'];
        $orderstatus = $post_data['status'];
        $ordernumber = $post_data['traceno'];

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
        ksort($post_data);
        $a='';
        foreach ($post_data as $x=>$x_value) {
            if ($x_value) {
                $a=$a.$x."=".$x_value."&";
            }
        }
        $a = $a.$tokenKey;
        $a = iconv("UTF-8", "GB2312//IGNORE", $a);
        if ($sign != strtoupper(MD5($a))) {
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
}
