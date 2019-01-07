<?php

class Jk extends GC_Controller
{
    private $key;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('pay/Online_model');
    }

    public function callbackurl()
    {
        $data=$_POST;
        if(empty($data['status']) ||$data['status'] !='Success'){
            $this->Online_model->online_erro('JK', '支付失败:' . json_encode($_POST));            
            exit('支付失败');
        }
        // 验证参数
        if (empty($data['merchant_order_no'])||empty($data['sign'])) {
            $this->Online_model->online_erro('JK', '参数错误:' . json_encode($_POST));            
            exit('参数错误');
        }
        //验证签名
        $sign = $data['sign'];
         // 根据订单号获取配置信息
        $pay = $this->Online_model->order_detail($data['merchant_order_no']);
        $this->key = $pay['pay_key'];
        $local_sign=$this ->sign($data);
        if ($sign != $local_sign) {
            $this->Online_model->online_erro('JK', '签名验证失败:' . json_encode($_POST).'key:'.$this->key);
            exit('签名验证失败');
        }
        // 加锁
        $bool = $this->Online_model->fbs_lock('temp:new_order_num' . $data['merchant_order_no']);
        if (!$bool) {
            exit('请稍后');
        }
       
        if (empty($pay)) {
            $this->Online_model->online_erro('JK', '无效的订单号:' . json_encode($_POST));
            exit('无效的订单号');
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



     /**
     * 签名
     * @param array $data 表单内容
     * @return array
     */
    private function sign($data)
    {
        //构造签名参数
        $data = array_filter($data);
        unset($data['sign']);
        ksort($data);
        $string = json_encode($data,320);
        $string = str_replace('\/\/','//',$string);
        $string = str_replace('\/','/',$string);
        $string = $this->key . $string . $this->key;
        $sign = md5($string);
        return $sign;
    }

}