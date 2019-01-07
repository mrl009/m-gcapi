<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * 悠悠支付模块
 * @version     v1.0 2017/12/28
 */
class Uu extends GC_Controller
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
     * @access public
     */
    public function callbackurl()
    {
        $data = $this->getData();
        // 验证参数
        if (empty($data['sign']) || empty($data['outTradeNo']) || empty($data['amount']) || empty($data['status'])) {
            exit('参数错误');
        }
        // 加锁
        $bool = $this->Online_model->fbs_lock('temp:new_order_num' . $data['outTradeNo']);
        if (!$bool) {
            exit('请稍后');
        }
        // 根据订单号获取配置信息
        $pay = $this->Online_model->order_detail($data['outTradeNo']);
        if (empty($pay)) {
            $this->Online_model->online_erro('UU', '无效的订单号:' . json_encode($_POST));
            exit($this->error);
        }
        // 验证返回状态
        if ($data['status'] != 'PAYED') {
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
        if (intval($price) != intval($data['amount'])) {
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
            'payType' => isset($_REQUEST["payType"]) ? $_REQUEST["payType"] : '',// 支付方式
            'tradeNo' => isset($_REQUEST["tradeNo"]) ? $_REQUEST["tradeNo"] : '',// 平台交易号
            'outTradeNo' => isset($_REQUEST["outTradeNo"]) ? $_REQUEST["outTradeNo"] : '',//商户交易号
            'outContext' => isset($_REQUEST["outContext"]) ? $_REQUEST["outContext"] : '',// 创建交易时的 outContext 参数
            'merchantNo' => isset($_REQUEST["merchantNo"]) ? $_REQUEST["merchantNo"] : '',// 商户号
            'currency' => isset($_REQUEST["currency"]) ? $_REQUEST["currency"] : '',// 货币类型 CNY
            'amount' => isset($_REQUEST["amount"]) ? $_REQUEST["amount"] : '',// 交易金额
            'payedAmount' => isset($_REQUEST["payedAmount"]) ? $_REQUEST["payedAmount"] : '',// 用户支付金额
            'status' => isset($_REQUEST["status"]) ? $_REQUEST["status"] : '',// 交易状态
            'sign' => isset($_REQUEST["sign"]) ? $_REQUEST["sign"] : '',// 签名
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
            $verify = openssl_verify($str, $sign, $keyId, OPENSSL_ALGO_SHA1);
        }
        return $verify == 1 ? true : false;
    }
}
