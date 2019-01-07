<?php
/**
 * 瞬付的同步地址回调地址
 * Created by PhpStorm.
 * User: shenshilin
 * Date: 2017/5/3
 * Time: 19:38
 */
defined('BASEPATH') or exit('No direct script access allowed');

class Shunfu extends GC_Controller
{
    public function __construct()
    {
        parent::__construct();
//        $this->load->model('pay/Pay_model');
        $this->load->model('pay/Online_model');
    }

    public $echo_str = "000000";  //回调地址层共返回

    public function callbackurl()
    {
        $this->load->helper('common_helper');
        $data = $this->P('data');
        if (empty($data)) {
            die('参数错误');
        }
        $arr = json_decode($data, true);
        $ordernumber  = $arr['orderNo'];
        $sign        = $arr['sign'];
        $orderstatus = $arr['resultCode'];
        $paymoney    = $arr['amount']/100;


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
            $erroStr = '无效的订单号:'.$this->P('data');
            ;
            $this->Online_model->online_erro($payconf['id'], $erroStr);
            die('无效的订单号');
        }
        // 根据订单号 获取入款注单数据
        $tokenKey = $payconf['pay_key'];
        // 签名
        $bool = $this->is_sign($arr, $tokenKey);
        if (!$bool) {
            $erroStr = '签名验证失败:'.$this->P('data');
            ;
            $this->Online_model->online_erro($payconf['id'], $erroStr);
            die('验证失败');
        }
        if ($payconf['price'] != $paymoney) {
            $erroStr = '订单金额验证失败:'.$this->P('data');
            ;
            $this->Online_model->online_erro($payconf['id'], $erroStr);
            die('金额错误');
        }
        //已充值的状态
        if ($payconf['status'] == 2) {
            echo $this->echo_str;
            die;
        }
        if ($orderstatus !="00") {
            $erroStr = '订单状态未成功:'.$this->P('data');
            ;
            $this->Online_model->online_erro($payconf['id'], $erroStr);
            die('订单失败');
        }

        $bool = $this->Online_model->update_order($payconf);
        if ($bool) {
            echo $this->echo_str;
            die;
        }
        $erroStr = '写入现金记录失败:'.$this->P('data');
        ;
        $this->Online_model->online_erro($payconf['id'], $erroStr);
        echo "加钱失败";
    }
    private function is_sign($row, $signKey)
    { #效验服务器返回数据
        $r_sign = $row['sign']; #保留签名数据
        $arr = array();
        foreach ($row as $key=>$v) {
            if ($key !== 'sign') { #删除签名
                $arr[$key] = (string)$v;
            }
        }
        ksort($arr);
        $sign = strtoupper(md5(my_json_encode($arr) . $signKey)); #生成签名

        if ($sign == $r_sign) {
            return true;
        } else {
            return false;
        }
    }
}
