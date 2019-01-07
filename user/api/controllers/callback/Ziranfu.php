<?php

class Ziranfu extends GC_Controller
{
    private $key;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('pay/Online_model');
    }

    public function callbackurl()
    {
    	// header('Content-Type:text/html;charset=GB2312');
        $data=$_POST;
        if(empty($data['status']) ||$data['status'] !=1){
            $this->Online_model->online_erro('ZRF', '支付失败:' . json_encode($_POST));            
            exit('支付失败');
        }
        // 验证参数
        if (empty($data['sdorderno'])||empty($data['sign'])) {
            $this->Online_model->online_erro('ZRF', '参数错误:' . json_encode($_POST));            
            exit('参数错误');
        }
        //验证签名
        $sign = $data['sign'];
         // 根据订单号获取配置信息
        $pay = $this->Online_model->order_detail($data['sdorderno']);
        if (empty($pay)) {
            $this->Online_model->online_erro('ZRF', '无效的订单号:' . json_encode($_POST));
            exit('无效的订单号');
        }
        $this->key = $pay['pay_key'];
        $local_sign=md5("customerid={$data['customerid']}&status={$data['status']}&sdpayno={$data['sdpayno']}&sdorderno={$data['sdorderno']}&total_fee={$data['total_fee']}&paytype={$data['paytype']}&{$this->key}");
        if ($sign != $local_sign) {
            $this->Online_model->online_erro('ZRF', '签名验证失败:' . json_encode($_POST).'key:'.$this->key);
            exit('签名验证失败');
        }
        // 加锁
        $bool = $this->Online_model->fbs_lock('temp:new_order_num' . $data['sdorderno']);
        if (!$bool) {
            exit('请稍后');
        }

        //.执行支付成功后的操作
         //已经确认
        if ($pay['status'] == 2) {
            exit('success');
        }
        $bool = $this->Online_model->update_order($pay);
        if ($bool) {
            die("success");
        }
        $this->Online_model->online_erro($pay['id'], '写入现金记录失败:' . json_encode($_POST));
        exit('加钱失败');
    }
}