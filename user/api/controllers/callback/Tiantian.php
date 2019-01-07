<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * 天天惠支付模块
 * @version     v1.0 2017/1/17
 */
class Tiantian extends GC_Controller
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
    }

    /**
     * 异步回调接口
     */
    public function callbackurl()
    {
        $data = file_get_contents('php://input');
        $data = json_decode($data, true);
        if ($data['code'] != '000000' && $data['message'] != 'SUCCESS') {
            $this->Online_model->online_erro('TTH', '交易不成功:' . json_encode($data));
            exit($this->error);
        }
        $bool = $this->Online_model->fbs_lock('temp:new_order_num' . $data['orderNo']);
        if (!$bool) {
            exit('请稍后');
        }
        // 根据订单号获取配置信息
        $pay = $this->Online_model->order_detail($data['orderNo']);
        if (empty($pay)) {
            $this->Online_model->online_erro('TTH', '无效的订单号:' . json_encode($data));
            exit($this->error);
        }

        // 验证金额
        if ($pay['price'] != $data['amt']) {
            $this->Online_model->online_erro($pay['id'], '订单金额验证失败:' . json_encode($data));
            exit($this->error);
        }
        // 已经确认
        if ($pay['status'] == 2) {
            exit($this->error);
        }
        // 加钱
        $bool = $this->Online_model->update_order($pay);
        if ($bool) {
            exit($this->success);
        }
        $this->Online_model->online_erro('TTH', '写入现金记录失败:' . json_encode($data));
        exit('加钱失败');
    }
}
