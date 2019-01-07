<?php

class Elevenfu extends GC_Controller
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
        $data = json_decode($data,true);
        // 判断支付是否成功
        if ($data['state'] != '10Z') {
            $this->Online_model->online_erro('11F', '支付失败:' . json_encode($_REQUEST));            
            exit('支付失败');
        }
        // 验证参数
        if (empty($data['ordercode'])||empty($data['sign'])) {
            $this->Online_model->online_erro('11F', '参数错误:' . json_encode($_REQUEST));            
            exit('参数错误');
        }
        $sign_str = $data['sign'];
        //  // 根据订单号获取配置信息
        $pay = $this->Online_model->order_detail($data['ordercode']);
        $this->key = $pay['pay_key'];
        $qmSign = $this->signStr($data);
        if( $sign_str != $qmSign){
            $this->Online_model->online_erro('11F', '签名验证失败:' . json_encode($_REQUEST));
            exit('签名验证失败');
        }
        //.加锁
        $bool = $this->Online_model->fbs_lock('temp:new_order_num' . $data['ordercode']);
        if (!$bool) {
            exit('请稍后');
        }
       
        if (empty($pay)) {
            $this->Online_model->online_erro('11F', '无效的订单号:' . json_encode($_REQUEST));
            exit('无效的订单号');
        }
        if ($pay['price'] !=$data['amount']) {
            $this->Online_model->online_erro('11F', '支付金额异常:' . json_encode($_REQUEST));
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
       $amount = $data['amount']*100;       
       $pmstr = "{$data['ordercode']}{$amount}{$data['goodsId']}{$this->key}";
       return  md5(trim($pmstr,''));
    }
    
}