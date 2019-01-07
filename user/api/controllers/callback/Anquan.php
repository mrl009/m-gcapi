<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * 安全付支付
 * @version     v1.0 2017/1/13
 */
class Anquan extends GC_Controller
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
        if (empty($data['sign']) || empty($data['out_trade_no']) || empty($data['total_amount']) || empty($data['trade_status'])) {
            exit('参数错误');
        }
        $bool = $this->Online_model->fbs_lock('temp:new_order_num' . $data['out_trade_no']);
        if (!$bool) {
            exit('请稍后');
        }
        // 根据订单号获取配置信息
        $pay = $this->Online_model->order_detail($data['out_trade_no']);
        if (empty($pay)) {
            $this->Online_model->online_erro('AQ', '无效的订单号:' . json_encode($_POST));
            exit($this->error);
        }
        // 验证返回状态
        if ($data['trade_status'] != 1) {
            $this->Online_model->online_erro($pay['id'], '交易不成功:' . json_encode($_POST));
            exit($this->error);
        }
        // 验证签名
        $flag = $this->sign($data, $pay['pay_server_key']);
        if (!$flag) {
            $this->Online_model->online_erro($pay['id'], '签名验证失败:' . json_encode($_POST));
            exit($this->error);
        }

        $price = $pay['price'] * 100;
        if (intval($price) != intval($data['total_amount'])) {
            $this->Online_model->online_erro($pay['id'], '订单金额验证失败:' . json_encode($_POST));
            exit($this->error);
        }
        //已经确认
        if ($pay['status'] == 2) {
            exit($this->error);
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
            'subject' => isset($_POST["subject"]) ? $_POST["subject"] : '',// 标题
            'body' => isset($_POST["body"]) ? $_POST["body"] : '',// 订单详情
            'trade_status' => isset($_POST["trade_status"]) ? $_POST["trade_status"] : '',// 交易状态
            'total_amount' => isset($_POST["total_amount"]) ? $_POST["total_amount"] : '',// 支付金额
            'sysd_time' => isset($_POST["sysd_time"]) ? $_POST["sysd_time"] : '',// 平台交易时间
            'trade_time' => isset($_POST["trade_time"]) ? $_POST["trade_time"] : '',// 订单时间
            'trade_no' => isset($_POST["trade_no"]) ? $_POST["trade_no"] : '',// 平台订单
            'out_trade_no' => isset($_POST["out_trade_no"]) ? $_POST["out_trade_no"] : '',// 订单号
            'notify_time' => isset($_POST["notify_time"]) ? $_POST["notify_time"] : '',// 通知时间
            'sign' => isset($_POST["sign"]) ? $_POST["sign"] : '',// 签名
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
            $verify = openssl_verify($str, $sign, $keyId, OPENSSL_ALGO_SHA256);
        }
        return $verify == 1 ? true : false;
    }
}
