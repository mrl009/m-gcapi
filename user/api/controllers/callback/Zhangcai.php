<?php

class Zhangcai extends GC_Controller
{
    private $key;
    private $success = "success";

    public function __construct()
    {
        parent::__construct();
        $this->load->model('pay/Online_model');
    }

    public function callbackurl()
    {
    	// header('Content-Type:text/html;charset=GB2312');
        $data=$_REQUEST;
        // 验证参数
        if (empty($data['orderid'])||empty($data['sign'])||empty($data['ovalue'])) {
            $this->Online_model->online_erro('ZC', '参数错误:' . json_encode($_REQUEST));            
            exit('参数错误');
        }
        //验证签名
        $sign = $_REQUEST['sign'];
         // 根据订单号获取配置信息
        $pay = $this->Online_model->order_detail($data['orderid']);
        $this->key = $pay['pay_key'];
        $local_sign=md5("orderid={$data['orderid']}&opstate={$data['opstate']}&ovalue={$data['ovalue']}{$this->key}");
        if ($sign != $local_sign) {
            $this->Online_model->online_erro('ZC', '签名验证失败:' . json_encode($_REQUEST).'key:'.$this->key);
            exit('签名验证失败');
        }
        // 加锁
        $bool = $this->Online_model->fbs_lock('temp:new_order_num' . $data['orderid']);
        if (!$bool) {
            exit('请稍后');
        }
       
        if (empty($pay)) {
            $this->Online_model->online_erro('ZC', '无效的订单号:' . json_encode($_REQUEST));
            exit('无效的订单号');
        }
        $bool = $this->Online_model->update_order($pay);
        if ($bool) {
            die("opstate=0");
        }
        $this->Online_model->online_erro($pay['id'], '写入现金记录失败:' . json_encode($_REQUEST));
        exit('加钱失败');
    }
}