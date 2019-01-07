<?php

class Yifu extends GC_Controller
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
        // 验证参数
        if (empty($data['trade_no'])||empty($data['sign'])) {
            $this->Online_model->online_erro('YF', '参数错误:' . json_encode($_REQUEST));            
            exit('参数错误');
        }
        //验证签名
        $sign = $_REQUEST['sign'];
        $data=$this->dekong($data);
        unset($data['sign']);
         // 根据订单号获取配置信息
        $pay = $this->Online_model->order_detail($data['out_trade_no']);
        $this->key = $pay['pay_key'];
        ksort($data);
        $local_sign = $this->sign($data);
        if ($sign != $local_sign) {
            $this->Online_model->online_erro('YF', '签名验证失败:' . json_encode($_REQUEST));
            exit('签名验证失败');
        }
        // 加锁
        $bool = $this->Online_model->fbs_lock('temp:new_order_num' . $data['out_trade_no']);
        if (!$bool) {
            exit('请稍后');
        }
       
        if (empty($pay)) {
            $this->Online_model->online_erro('YF', '无效的订单号:' . json_encode($_REQUEST));
            exit('无效的订单号');
        }
        //金额不同
        if ($pay['price'] != $data['total_fee']) {
            $pay['total_price'] = $data['total_fee'];
            $pay['price'] = $data['total_fee'];
            $this->Online_model->write('cash_in_online', array('total_price' => $data['total_fee'], 'price' => $data['total_fee']), array('order_num' => $data['out_trade_no']));
        }

        $bool = $this->Online_model->update_order($pay);
        if ($bool) {
            exit('SUCCESS');
        }
        $this->Online_model->online_erro($pay['id'], '写入现金记录失败:' . json_encode($_REQUEST));
        exit('加钱失败');
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
            $md5str.=$key.'='.$val.'&';
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