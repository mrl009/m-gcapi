<?php

class Kairongda extends GC_Controller
{
    private $key;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('pay/Online_model');
    }

    public function callbackurl()
    {
        $data = $_REQUEST;
        // 判断支付是否成功
        if ($data['cgzt'] != 'success') {
            $this->Online_model->online_erro('KRD', '支付失败:' . json_encode($_REQUEST));            
            exit('支付失败');
        }
        // 验证参数
        if (empty($data['orderNo'])||empty($data['sign'])) {
            $this->Online_model->online_erro('KRD', '参数错误:' . json_encode($_REQUEST));            
            exit('参数错误');
        }
        $sign_str = $data['sign'];
        //  // 根据订单号获取配置信息
        $pay = $this->Online_model->order_detail($data['orderNo']);
        $this->key = $pay['pay_key'];
        $qmSign = $this->signStr($data);
        if( $sign_str != $qmSign){
            $this->Online_model->online_erro('KRD', '签名验证失败:' . json_encode($_REQUEST));
            exit('签名验证失败');
        }
        //.加锁
        $bool = $this->Online_model->fbs_lock('temp:new_order_num' . $data['orderNo']);
        if (!$bool) {
            exit('请稍后');
        }
       
        if (empty($pay)) {
            $this->Online_model->online_erro('KRD', '无效的订单号:' . json_encode($_REQUEST));
            exit('无效的订单号');
        }
        if ($pay['price'] !=$data['totalFee']) {
            $this->Online_model->online_erro('KRD', '支付金额异常:' . json_encode($_REQUEST));
            exit('支付金额异常');
        }

        //.执行支付成功后的操作
         //已经确认
        if ($pay['status'] == 2) {
            exit('success');
        }
        $bool = $this->Online_model->update_order($pay);
        if ($bool) {
            exit('success');
        }
        $this->Online_model->online_erro($pay['id'], '写入现金记录失败:' . json_encode($_REQUEST));
        exit('加钱失败');
    }

    /**
     * 获取支付签名
     * @param string $data 支付参数
     * @return string $sign签名值 rsa_s加密
     */
    private function signStr($data)
    {
       $pmstr = "uid={$data['uid']}&totalFee={$data['totalFee']}&orderNo={$data['orderNo']}{$this->key}";
       return  md5(trim($pmstr,''));
    }
    
}