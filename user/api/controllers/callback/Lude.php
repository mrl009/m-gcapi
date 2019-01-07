<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * 路德支付
 * @package     user/controllers/online/
 * @version     v1.0 2017/12/19
 */
class Lude extends GC_Controller
{
    //成功响应
    public $echo_str = "SUCCESS";

    public function __construct()
    {
        parent::__construct();
        $this->load->model('pay/Online_model');
        $this->load->model('online/Lude_model');
    }

    /**
     * 路德支付异步回调接口
     */
    public function callbackurl()
    {
        $data = $this->getData();
        if (empty($data['tradeNo']) || empty($data['amount']) || empty($data['status']) || empty($data['notifyType']) || empty($data['sign']) || empty($data['extra'])) {
            die('参数错误');
        }
        // 根据订单号获取配置信息
        $payconf = $this->Online_model->order_detail($data['tradeNo']);
        if (empty($payconf)) {
            $erroStr = '无效的订单号:' . json_encode($_POST, JSON_UNESCAPED_UNICODE);
            $this->Online_model->online_erro('zhifu', $erroStr);
            die($this->echo_error);
        }
        // 准备准备验签数据
        $str_to_sign = $this->Lude_model->prepareSign($data);
        // 验证签名
        $resultVerify = $this->Lude_model->verify($str_to_sign, $data['sign'], $data['extra']);
        if ($resultVerify) {
            //开始处理
            $bool = $this->Online_model->fbs_lock('temp:new_order_num' . $data['tradeNo']);
            if (!$bool) {
                die('请稍后');
            }
            //验证订单金额
            if ($payconf['price'] != $data['amount']) {
                $erroStr = '订单金额验证失败:' . json_encode($data, JSON_UNESCAPED_UNICODE);
                $this->Online_model->online_erro($payconf['id'], $erroStr);
                die('金额错误');
            }
            $bool = $this->Online_model->update_order($payconf);
            if ($bool && '1' == $data["notifyType"]) {
                echo $this->echo_str;
                exit;
            }
            $erroStr = '写入现金记录失败:' . json_encode($data, JSON_UNESCAPED_UNICODE);
            $this->Online_model->online_erro($payconf['id'], $erroStr);
            echo "加钱失败";
        } else {
            $erroStr = '验证签名失败:' . json_encode($data, JSON_UNESCAPED_UNICODE);
            $this->Online_model->online_erro($payconf['id'], $erroStr);
            echo "签名无效，视为无效数据!";
        }
    }

    private function getData()
    {
        $data = [
            'service' => isset($_REQUEST["service"]) ? $_REQUEST["service"] : '',// 接口名字
            'merId' => isset($_REQUEST["merId"]) ? $_REQUEST["merId"] : '',// 商户在支付平台的的平台号
            'tradeNo' => isset($_REQUEST["tradeNo"]) ? $_REQUEST["tradeNo"] : '',//商户订单号
            'tradeDate' => isset($_REQUEST["tradeDate"]) ? $_REQUEST["tradeDate"] : '',// 商户订单日期
            'opeNo' => isset($_REQUEST["opeNo"]) ? $_REQUEST["opeNo"] : '',// 支付平台订单号
            'opeDate' => isset($_REQUEST["opeDate"]) ? $_REQUEST["opeDate"] : '',// 支付平台订单日期
            'amount' => isset($_REQUEST["amount"]) ? $_REQUEST["amount"] : '',// 支付金额
            'status' => isset($_REQUEST["status"]) ? $_REQUEST["status"] : '',// 固定值：1 成功
            'extra' => isset($_REQUEST["extra"]) ? $_REQUEST["extra"] : '',// 商户参数
            'payTime' => isset($_REQUEST["payTime"]) ? $_REQUEST["payTime"] : '',// 支付时间
            'sign' => isset($_REQUEST["sign"]) ? $_REQUEST["sign"] : '',// 签名数据
            'notifyType' => isset($_REQUEST["notifyType"]) ? $_REQUEST["notifyType"] : '',// 通知类型
        ];
        return $data;
    }
}
