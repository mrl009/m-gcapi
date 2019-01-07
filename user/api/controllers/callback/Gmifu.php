<?php

class Gmifu extends GC_Controller
{
    private $key;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('pay/Online_model');
    }

    public function callbackurl()
    {
        $data = $_GET;
        // 判断支付是否成功
        if ($data['tradeStatus'] != 'SUCCESS') {
            $this->Online_model->online_erro('GMF', '支付失败:' . json_encode($_GET));            
            exit('支付失败');
        }
        // 验证参数
        if (empty($data['outTradeNo'])||empty($data['sign'])) {
            $this->Online_model->online_erro('GMF', '参数错误:' . json_encode($_GET));            
            exit('参数错误');
        }
        $sign_str = $data['sign'];
        //  // 根据订单号获取配置信息
        $pay = $this->Online_model->order_detail($data['outTradeNo']);
        $this->key = $pay['pay_key'];
        $qmSign = $this->signStr($data);
        if( $sign_str != $qmSign){
            $this->Online_model->online_erro('GMF', '签名验证失败:' . json_encode($_GET));
            exit('签名验证失败');
        }
        //.加锁
        $bool = $this->Online_model->fbs_lock('temp:new_order_num' . $data['outTradeNo']);
        if (!$bool) {
            exit('请稍后');
        }
       
        if (empty($pay)) {
            $this->Online_model->online_erro('GMF', '无效的订单号:' . json_encode($_GET));
            exit('无效的订单号');
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
        $this->Online_model->online_erro($pay['id'], '写入现金记录失败:' . json_encode($_GET));
        exit('加钱失败');
    }

    /**
     * 获取支付签名
     * @param string $data 支付参数
     * @return string $sign签名值 rsa_s加密
     */
    private function signStr($data)
    {
       ksort($data);
        $buff='';
        foreach ($data as $k => $v)
        {
            if($v != null && $v != ''&&$k !='sign'){
                $buff=$buff.$k.'='.$v.'&';           
            }
        }
        $buff =$buff.'paySecret='.$this->key;
        $sign = strtoupper(md5($buff));
        return $sign;
    }
    
}