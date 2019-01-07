<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * 五福支付
 * @package     user/controllers/online/
 * @version     v1.0 2017/12/27
 */
class Wufu extends GC_Controller
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
        $this->load->model('online/Wufu_model');
    }

    /**
     * 五福支付异步回调接口
     */
    public function callbackurl()
    {
        $data = $this->getData();
        // 验证参数
        if (empty($data['merchOrderId']) || empty($data['amt']) || empty($data['merData'])) {
            exit('参数错误');
        }
        // 加锁
        $bool = $this->Online_model->fbs_lock('temp:new_order_num' . $data['merchOrderId']);
        if (!$bool) {
            exit('请稍后');
        }
        // 根据订单号获取配置信息
        $pay = $this->Online_model->order_detail($data['merchOrderId']);
        if (empty($pay)) {
            $this->Online_model->online_erro('WUF', '无效的订单号:' . json_encode($_POST));
            exit('无效的订单号');
        }
        //验证签名
        $md5Str = $this->Wufu_model->sign($data, $pay['pay_key']);
        if ($_POST["md5value"] != $md5Str) {
            $this->Online_model->online_erro($pay['id'], '签名验证失败:' . json_encode($_POST));
            exit('签名验证失败');
        }
        //验证订单金额
        $price = $pay['price'] * 100;
        if (intval($price) != intval($data['amt'])) {
            $this->Online_model->online_erro($pay['id'], '订单金额验证失败:' . json_encode($_POST));
            exit('订单金额验证失败');
        }
        //已经确认
        if ($pay['status'] == 2) {
            exit('订单已经确认');
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
        $data = array();
        foreach ($_POST as $key => $value) {
            if ($key == "md5value") {
                continue;
            } else if ($value === "") {
                continue;
            } else {
                $data[$key] = $value;
            }
        }
        return $data;
    }
}
