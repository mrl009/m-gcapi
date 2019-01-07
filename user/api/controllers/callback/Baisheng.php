<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * 百盛支付
 * @package     user/controllers/online/
 * @version     v1.0 2018/3/22
 */
class Baisheng extends GC_Controller
{
    /**
     * 成功响应
     * @var string
     */
    public $success = "SUCCESS";

    public function __construct()
    {
        parent::__construct();
        $this->load->model('pay/Online_model');
    }

    public function callbackurl()
    {
        $data = $this->getData();
        // 验证参数
        if (empty($data['MerchantId']) || empty($data['Code']) || empty($data['OutPaymentNo']) || empty($data['PaymentAmount'])) {
            $this->Online_model->online_erro('BS', '参数错误:' . json_encode($data));
            exit('参数错误');
        }
        if ($data['Code'] != 200) {
            $this->Online_model->online_erro('BS', '没有支付成功:' . json_encode($data));
            exit('没有支付成功');
        }
        // 加锁
        $bool = $this->Online_model->fbs_lock('temp:new_order_num' . $data['OutPaymentNo']);
        if (!$bool) {
            exit('请稍后');
        }
        // 根据订单号获取配置信息
        $pay = $this->Online_model->order_detail($data['OutPaymentNo']);
        if (empty($pay)) {
            $this->Online_model->online_erro('BS', '无效的订单号:' . json_encode($data));
            exit('无效的订单号');
        }
        //验证签名
        $validate = $this->sign($data, $pay['pay_key']);
        if (!$validate) {
            $this->Online_model->online_erro($pay['id'], '签名验证失败:' . json_encode($data));
            exit('签名验证失败');
        }
        //已经确认
        if ($pay['status'] == 2) {
            exit($this->success);
        }
        $bool = $this->Online_model->update_order($pay);
        if ($bool) {
            exit($this->success);
        }
        $this->Online_model->online_erro($pay['id'], '写入现金记录失败:' . json_encode($data));
        exit('加钱失败');
    }

    /**
     * 获取数据
     * @return array
     */
    private function getData()
    {
        $data = [
            'MerchantId' => isset($_REQUEST["MerchantId"]) ? $_REQUEST["MerchantId"] : '',
            'Code' => isset($_REQUEST["Code"]) ? $_REQUEST["Code"] : '',
            'PaymentNo' => isset($_REQUEST["PaymentNo"]) ? $_REQUEST["PaymentNo"] : '',
            'OutPaymentNo' => isset($_REQUEST["OutPaymentNo"]) ? $_REQUEST["OutPaymentNo"] : '',
            'PaymentAmount' => isset($_REQUEST["PaymentAmount"]) ? $_REQUEST["PaymentAmount"] : '',
            'PaymentFee' => isset($_REQUEST["PaymentFee"]) ? $_REQUEST["PaymentFee"] : '',
            'PaymentState' => isset($_REQUEST["PaymentState"]) ? $_REQUEST["PaymentState"] : '',
            'PassbackParams' => isset($_REQUEST["PassbackParams"]) ? $_REQUEST["PassbackParams"] : '',
            'Sign' => isset($_REQUEST["Sign"]) ? $_REQUEST["Sign"] : '',
        ];
        return $data;
    }

    //签名验证
    private function sign($data, $key)
    {
        ksort($data);
        $arg = "";
        foreach ($data as $k => $v) {
            if ($k == 'Sign' || $k == 'SignType' || $v == '') {
                continue;
            }
            $arg .= $k . "=" . $v . "&";
        }
        $arg = substr($arg, 0, count($arg) - 2);
        if (get_magic_quotes_gpc()) {
            $arg = stripslashes($arg);
        }
        return md5($arg . $key) == strtolower($data['Sign']);
    }
}
