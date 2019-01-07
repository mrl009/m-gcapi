<?php

class Pingguo extends GC_Controller
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
        if ($data['result'] != '0') {
            $this->Online_model->online_erro('PGF', '支付失败:' . json_encode($_GET));            
            exit('支付失败');
        }
        // 验证参数
        if (empty($data['out_trade_no'])||empty($data['sign'])) {
            $this->Online_model->online_erro('PGF', '参数错误:' . json_encode($_GET));            
            exit('参数错误');
        }
        $sign_str = $data['sign'];
        //  // 根据订单号获取配置信息
        $pay = $this->Online_model->order_detail($data['out_trade_no']);
        $this->key = $pay['pay_key'];
        $qmSign = $this->signStr($data);
        if( $sign_str != $qmSign){
            $this->Online_model->online_erro('PGF', '签名验证失败:' . json_encode($_GET));
            exit('签名验证失败');
        }
        //.加锁
        $bool = $this->Online_model->fbs_lock('temp:new_order_num' . $data['out_trade_no']);
        if (!$bool) {
            exit('请稍后');
        }
       
        if (empty($pay)) {
            $this->Online_model->online_erro('PGF', '无效的订单号:' . json_encode($_GET));
            exit('无效的订单号');
        }
         //.实际支付金额 和订单金额不同 则支付多少 上分多少
        if ($pay['price'] *100 != $data['real_fee']) {
            $pay['total_price'] = $data['real_fee']/100;
            $pay['price'] = $data['real_fee']/100;
            $this->Online_model->write('cash_in_online', array('total_price' => $pay['total_price'], 'price' => $pay['price']), array('order_num' => $data['out_trade_no']));
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
       $pmstr = "mer_id={$data['mer_id']}&out_trade_no={$data['out_trade_no']}&pay_type={$data['pay_type']}&real_fee={$data['real_fee']}&total_fee={$data['total_fee']}&key={$this->key}";
       return  md5(trim($pmstr,''));
    }
    
}