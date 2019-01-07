<?php

class Huiyin extends GC_Controller
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
        $data=$_REQUEST;
        $pre_data=$this->fzData($data);
        // 验证参数
        if (empty($data['orderid'])||empty($data['sign'])) {
            $this->Online_model->online_erro('HY', '参数错误:' . json_encode($_REQUEST));            
            exit('参数错误');
        }
        //验证签名
        $sign = $_REQUEST['sign'];
         // 根据订单号获取配置信息
        $pay = $this->Online_model->order_detail($data['orderid']);
        $this->key = $pay['pay_key'];
        ksort($pre_data);
        $local_sign = $this->sign($pre_data);
        if ($sign != $local_sign) {
            $this->Online_model->online_erro('HY', '签名验证失败:' . json_encode($_REQUEST));
            exit('签名验证失败');
        }
        // 加锁
        $bool = $this->Online_model->fbs_lock('temp:new_order_num' . $data['orderid']);
        if (!$bool) {
            exit('请稍后');
        }
       
        if (empty($pay)) {
            $this->Online_model->online_erro('HY', '无效的订单号:' . json_encode($_REQUEST));
            exit('无效的订单号');
        }
        //金额不同
        if ($pay['price'] != $data['amount']) {
            $pay['total_price'] = $data['amount'];
            $pay['price'] = $data['amount'];
            $this->Online_model->write('cash_in_online', array('total_price' => $data['amount'], 'price' => $data['amount']), array('order_num' => $data['orderid']));
        }

        $bool = $this->Online_model->update_order($pay);
        if ($bool) {
            exit('success');
        }
        $this->Online_model->online_erro($pay['id'], '写入现金记录失败:' . json_encode($_REQUEST));
        exit('加钱失败');
    }



     //.组装数据
    private function fzData($data)
    {
        $pre_data=[];
        $pre_data['amount']=$data['amount'];
        $pre_data['merchantcode']=$data['merchantcode'];
        $pre_data['orderid']=$data['orderid'];
        $pre_data['paytime']=$data['paytime'];
        $pre_data['status']=$data['status'];
        return   $pre_data;


    }

     /**
     * 获取支付签名
     * @param string $data 支付参数
     * @return string $sign签名值
     */
    private function sign($data)
    {
        ksort($data);
        $md5str = '';
        foreach ($data as $key => $val) {
            $md5str.=$key.'='.$val;
        }
        $signStr = trim($md5str,' ').'key='. $this->key;
        return strtoupper(md5($signStr));
    }

    /**
    *去数组空值
    *@param 数组
    */
    private function dekong($data){
        foreach($data as $k=>$v){
            if(empty($v)){
                unset($data[$k]);
            }
        }
        return $data;
    }
}