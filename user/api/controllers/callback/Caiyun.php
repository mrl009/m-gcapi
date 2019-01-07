<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * 财运支付模块
 * @version     v1.0 2017/3/22
 */
class Caiyun extends GC_Controller
{
    /**
     * 错误响应
     * @var string
     */
    public $error = "ERROR";

    /**
     * 成功响应
     * @var string
     */
    public $success = "SUCCESS";

    public function __construct()
    {
        parent::__construct();
        $this->load->model('pay/Online_model');
        $this->load->helper('common_helper');
    }

    /**
     * 异步回调接口
     */
    public function callbackurl()
    {
        $data = $this->getData();
        if (empty($data['sign']) || empty($data['order_no']) || empty($data['order_amount'])) {
            $this->Online_model->online_erro('CY', '参数错误:' . json_encode($_POST));
            exit('参数错误');
        }
        // 加锁
        $bool = $this->Online_model->fbs_lock('temp:new_order_num' . $data['order_no']);
        if (!$bool) {
            exit('请稍后');
        }
        // 根据订单号获取配置信息
        $pay = $this->Online_model->order_detail($data['order_no']);
        if (empty($pay)) {
            $this->Online_model->online_erro('CY', '无效的订单号:' . json_encode($_POST));
            exit('无效的订单号');
        }
        // 验证返回状态
        if ($data['trade_status'] != 'SUCCESS') {
            $this->Online_model->online_erro($pay['id'], '交易不成功:' . json_encode($_POST));
            exit($this->error);
        }
        // 验证签名
        $flag = $this->sign($data, $pay['pay_server_key']);
        if (!$flag) {
            $this->Online_model->online_erro($pay['id'], '签名验证失败:' . json_encode($_POST));
            exit($this->error);
        }

        if ($pay['price'] != $data['order_amount']) {
            $this->Online_model->online_erro($pay['id'], '订单金额验证失败:' . json_encode($_POST));
            exit($this->error);
        }
        //已经确认
        if ($pay['status'] == 2) {
            exit($this->success);
        }
        $bool = $this->Online_model->update_order($pay);
        if ($bool) {
            exit($this->success);
        }
        $this->Online_model->online_erro($pay['id'], '写入现金记录失败:' . json_encode($_POST));
        exit('加钱失败');
    }


    /**
     * 获取数据
     * @return array
     */
    private function getData()
    {
        $data = [
            'merchant_code' => isset($_POST["merchant_code"]) ? $_POST["merchant_code"] : '',// 商家号
            'interface_version' => isset($_POST["interface_version"]) ? $_POST["interface_version"] : '',// 版本号
            'sign' => isset($_POST["sign"]) ? $_POST["sign"] : '',// 签名
            'notify_type' => isset($_POST["notify_type"]) ? $_POST["notify_type"] : '',// 通知leix
            'notify_id' => isset($_POST["notify_id"]) ? $_POST["notify_id"] : '',// 通知id
            'order_no' => isset($_POST["order_no"]) ? $_POST["order_no"] : '',// 订单号
            'order_time' => isset($_POST["order_time"]) ? $_POST["order_time"] : '',// 订单时间
            'order_amount' => isset($_POST["order_amount"]) ? $_POST["order_amount"] : '',// 支付金额
            'trade_status' => isset($_POST["trade_status"]) ? $_POST["trade_status"] : '',// 支付状态
            'trade_time' => isset($_POST["trade_time"]) ? $_POST["trade_time"] : '',// 支付时间
            'trade_no' => isset($_POST["trade_no"]) ? $_POST["trade_no"] : '',// 平台订单
            'bank_seq_no' => isset($_POST["bank_seq_no"]) ? $_POST["bank_seq_no"] : '',// 银行交易流水号
            'extra_return_param' => isset($_POST["extra_return_param"]) ? $_POST["extra_return_param"] : '',
        ];
        return $data;
    }

    /**
     * 验证签名
     * @param array $data 回调参数数组
     * @param string $public_key 公钥
     * @return boolean $flag
     */
    private function sign($data, $public_key)
    {
        ksort($data);
        $verify = false;
        $str = ToUrlParams($data);
        $sign = isset($data['sign']) ? base64_decode($data['sign']) : '';
        $keyId = openssl_get_publickey($public_key);
        if ($keyId) {
            $verify = openssl_verify($str, $sign, $keyId, OPENSSL_ALGO_MD5);
        }
        return $verify == 1 ? true : false;
    }
}