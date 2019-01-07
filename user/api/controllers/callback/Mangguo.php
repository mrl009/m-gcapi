<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * 芒果支付
 * @version     v1.0 2017/1/19
 */
class Mangguo extends GC_Controller
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
        $data = $this->getData();
        if ($data['status'] != 2) {
            $this->Online_model->online_erro('MG', '交易不成功:' . json_encode($_POST));
            exit($this->error);
        }
        $bool = $this->Online_model->fbs_lock('temp:new_order_num' . $data['down_sn']);
        if (!$bool) {
            exit('请稍后');
        }
        // 根据订单号获取配置信息
        $pay = $this->Online_model->order_detail($data['down_sn']);
        if (empty($pay)) {
            $this->Online_model->online_erro('MG', '无效的订单号:' . json_encode($_POST));
            exit($this->error);
        }
        // 验证金额
        if ($pay['price'] != $data['amount']) {
            $this->Online_model->online_erro($pay['id'], '订单金额验证失败:' . json_encode($_POST));
            exit($this->error);
        }
        // 验签
        $verify = $this->checkSign($data, $pay['pay_key']);
        if (!$verify) {
            $this->Online_model->online_erro($pay['id'], '签名验证失败:' . json_encode($_POST));
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
            'code' => isset($_POST["code"]) ? $_POST["code"] : '',// 应答码
            'msg' => isset($_POST["msg"]) ? $_POST["msg"] : '',// 应答消息
            'sign' => isset($_POST["sign"]) ? $_POST["sign"] : '',// 签名
            'order_sn' => isset($_POST["order_sn"]) ? $_POST["order_sn"] : '',// 系统订单号
            'down_sn' => isset($_POST["down_sn"]) ? $_POST["down_sn"] : '',// 商户订单号
            'status' => isset($_POST["status"]) ? $_POST["status"] : '',// 支付状态
            'amount' => isset($_POST["amount"]) ? $_POST["amount"] : '',// 支付金额
            'fee' => isset($_POST["fee"]) ? $_POST["fee"] : '',// 支付手续费
            'trans_time' => isset($_POST["trans_time"]) ? $_POST["trans_time"] : '',// 支付时间
        ];
        return $data;
    }

    /**
     * 验签
     * @param $data
     * @param $secretKey
     * @return bool
     */
    private function checkSign($data, $secretKey)
    {
        ksort($data);
        $str = '';
        foreach ($data as $key => $val) {
            if (!in_array($key, ['sign', 'code', 'msg']) && (!empty($val) || $val === 0 || $val === '0')) {
                $str .= $key . '=' . $val . '&';
            }
        }
        $str .= 'key=' . $secretKey;
        return $data['sign'] == strtolower(md5($str));
    }

}
