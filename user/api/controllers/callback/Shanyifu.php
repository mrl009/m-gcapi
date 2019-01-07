<?php
/**
 * Created by PhpStorm.
 * User: mrl
 * Date: 2018/3/29
 * Time: 下午5:31
 */
defined('BASEPATH') or exit('No direct script access allowed');

class Shanyifu extends GC_Controller
{
    private $key;
    private $privateKey;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('pay/Online_model');
        $this->load->helper('common_helper');
    }

    public $success = "0";

    public function callbackurl()
    {
        $post = $this->P('data');
        if (empty($post)) {
            exit('参数错误');
        }
        $this->setKey();
        $data = $this->eDecode($post);
        $order_num = $data['orderNum'];
        $sign = $data['sign'];
        $status = $data['payResult'];
        $money = $data['amount'] / 100;
        if (empty($order_num) || empty($sign) || empty($money)) {
            $this->Online_model->online_erro('ST', '参数错误:' . json_encode($data));
            $this->Online_model->online_erro('SYF', '参数错误:' . $post);
            exit('参数错误');
        }
        if ($status != '00') {
            $this->Online_model->online_erro('SYF', '订单失败:' . $post);
            exit('订单失败');
        }

        $bool = $this->Online_model->fbs_lock('temp:new_order_num' . $order_num);
        if (!$bool) {
            exit('请稍后');
        }
        // 获取配置信息
        $pay = $this->Online_model->order_detail($order_num);
        if (empty($pay)) {
            $this->Online_model->online_erro('SYF', '无效的订单号:' . $post);
            exit('无效的订单号');
        }
        // 签名
        $bool = $this->verify($data, $this->key);
        if (!$bool) {
            $this->Online_model->online_erro($pay['id'], '验证失败:' . $post);
            exit('验证失败');
        }
        if ($pay['price'] != $money) {
            $this->Online_model->online_erro($pay['id'], '金额错误:' . $post);
            exit('金额错误');
        }
        //已充值的状态
        if ($pay['status'] == 2) {
            exit($this->success);
        }
        //更新订单
        $bool = $this->Online_model->update_order($pay);
        if ($bool) {
            exit($this->success);
        }
        $this->Online_model->online_erro($pay['id'], '加钱失败:' . $post);
        exit('加钱失败');
    }

    private function setKey()
    {
        $pay = $this->Online_model->get_one('pay_key,pay_private_key', 'bank_online_pay', ['bank_o_id' => 87]);
        if (empty($pay)) {
            $this->Online_model->online_erro('SYF', '无效的支付:' . $this->P('data'));
            exit('无效的支付');
        }
        $this->key = $pay['pay_key'];
        $this->privateKey = $pay['pay_private_key'];
    }

    private function eDecode($data)
    {
        $pr_key = openssl_get_privatekey($this->privateKey);
        if ($pr_key == false) {
            echo "打开密钥出错";
            die;
        }
        $data = base64_decode($data);
        $crypto = '';
        foreach (str_split($data, 128) as $chunk) {
            openssl_private_decrypt($chunk, $decryptData, $pr_key);
            $crypto .= $decryptData;
        }
        return json_decode($crypto, true);
    }

    private function verify($data, $signKey)
    {
        $r_sign = $data['sign'];
        $arr = [];
        foreach ($data as $key => $v) {
            if ($key !== 'sign') {
                $arr[$key] = (string)$v;
            }
        }
        ksort($arr);
        $sign = strtoupper(md5(my_json_encode($arr) . $signKey));
        return $sign == $r_sign ? true : false;
    }

}