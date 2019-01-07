<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * 弘宝支付
 * @version     v1.0 2017/1/22
 */
class Hongbao extends GC_Controller
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

    /**
     * 弘宝支付异步回调接口
     * @step 验证参数
     * @step 验证配置
     * @step 验证签名
     * @step 验证订单金额
     * @step 验证订单状态
     * @step 更新订单
     */
    public function callbackurl()
    {
        $data = $_REQUEST;
        // 验证参数
        if (empty($data['tradeNo']) || empty($data['amount']) || empty($data['sign']) || empty($data['status'])) {
            exit('参数错误');
        }
        // 加锁
        $bool = $this->Online_model->fbs_lock('temp:new_order_num' . $data['tradeNo']);
        if (!$bool) {
            exit('请稍后');
        }
        // 获取配置
        $pay = $this->Online_model->order_detail($data['tradeNo']);
        if (empty($pay)) {
            $this->Online_model->online_erro('HB', '无效的订单号:' . json_encode($_REQUEST));
            exit('无效的订单号');
        }
        // 验证签名
        $verify = $this->sign($data, $pay['pay_key']);
        if (!$verify) {
            $this->Online_model->online_erro($pay['id'], '签名验证失败:' . json_encode($_REQUEST));
            exit('签名验证失败');
        }
        // 验证订单金额
        if ($pay['price'] != $data['amount']) {
            $this->Online_model->online_erro($pay['id'], '订单金额验证失败:' . json_encode($_REQUEST));
            exit('订单金额验证失败');
        }
        // 已经确认
        if ($pay['status'] == 2) {
            exit('订单已经确认');
        }
        // 更新订单
        $bool = $this->Online_model->update_order($pay);
        if ($bool) {
            exit($this->success);
        }
        $this->Online_model->online_erro($pay['id'], '写入现金记录失败:' . json_encode($_REQUEST));
        exit('加钱失败');
    }

    /**
     * 验证签名
     * @param $data
     * @param $key
     * @return bool
     */
    private function sign($data, $key)
    {
        $sourceSign = $data['sign'];
        unset($data['sign']);
        ksort($data);
        $str = '';
        foreach ($data as $k => $v) {
            $str .= $k . '=' . $v . '&';
        }
        $str .= 'key=' . $key;
        return $sourceSign === md5($str);
    }
}