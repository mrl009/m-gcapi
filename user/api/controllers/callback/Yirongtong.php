<?php

class Yirongtong extends GC_Controller
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
        $data=$_GET;
        if(empty($data['orderstatus']) ||$data['orderstatus'] !=1){
            $this->Online_model->online_erro('YRT', '支付失败:' . json_encode($_GET));            
            exit('支付失败');
        }
        // 验证参数
        if (empty($data['ordernumber'])||empty($data['sign'])) {
            $this->Online_model->online_erro('YRT', '参数错误:' . json_encode($_GET));            
            exit('参数错误');
        }
        //验证签名
        $sign = $data['sign'];
         // 根据订单号获取配置信息
        $pay = $this->Online_model->order_detail($data['ordernumber']);
        $this->key = $pay['pay_key'];
        $local_sign=md5("partner={$data['partner']}&ordernumber={$data['ordernumber']}&orderstatus={$data['orderstatus']}&paymoney={$data['paymoney']}{$this->key}");
        if ($sign != $local_sign) {
            $this->Online_model->online_erro('YRT', '签名验证失败:' . json_encode($_GET).'key:'.$this->key);
            exit('签名验证失败');
        }
        // 加锁
        $bool = $this->Online_model->fbs_lock('temp:new_order_num' . $data['ordernumber']);
        if (!$bool) {
            exit('请稍后');
        }
       
        if (empty($pay)) {
            $this->Online_model->online_erro('YRT', '无效的订单号:' . json_encode($_GET));
            exit('无效的订单号');
        }

        //.执行支付成功后的操作
         //已经确认
        if ($pay['status'] == 2) {
            exit('ok');
        }
        $bool = $this->Online_model->update_order($pay);
        if ($bool) {
            die("ok");
        }
        $this->Online_model->online_erro($pay['id'], '写入现金记录失败:' . json_encode($_GET));
        exit('加钱失败');
    }
}