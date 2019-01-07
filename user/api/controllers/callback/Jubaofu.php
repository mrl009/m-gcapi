<?php

class Jubaofu extends GC_Controller
{
    private $key;
    private $private_key = null;
    private $public_key = null;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('pay/Online_model');
    }

    public function callbackurl()
    {
        $data = $_POST;
        $this->get_public_key();
        // 判断支付是否成功
        if ($data['respType']!='S') {
            $this->Online_model->online_erro('JBF', '支付失败:' . json_encode($_POST));            
            exit('支付失败');
        }
        // 验证参数
        if (empty($data['outOrderId'])||empty($data['sign'])) {
            $this->Online_model->online_erro('JBF', '参数错误:' . json_encode($_POST));            
            exit('参数错误');
        }
        $sign_str = $data['sign'];
        $sign_str = base64_decode($sign_str);
        unset ($data['sign']);
        //  // 根据订单号获取配置信息
        $pay = $this->Online_model->order_detail($data['outOrderId']);
        $this->key = $pay['pay_key'];
        $qmStr = $this->signStr($data);
        $flag = openssl_verify($qmStr,$sign_str,$this->public_key, OPENSSL_ALGO_SHA1);
        if(!$flag){
            $this->Online_model->online_erro('JBF', '签名验证失败:' . json_encode($_POST).'public:'.$this->public_key.'key:'.$this->key.'qmstr:'.$qmStr);
            exit('签名验证失败');
        }
        //.加锁
        $bool = $this->Online_model->fbs_lock('temp:new_order_num' . $data['outOrderId']);
        if (!$bool) {
            exit('请稍后');
        }
       
        if (empty($pay)) {
            $this->Online_model->online_erro('JBF', '无效的订单号:' . json_encode($_POST));
            exit('无效的订单号');
        }
         //金额不同
        if ($pay['price'] != $data['transAmt']) {
            $pay['total_price'] = $data['transAmt'];
            $pay['price'] = $data['transAmt'];
            $this->Online_model->write('cash_in_online', array('total_price' => $data['transAmt'], 'price' => $data['transAmt']), array('order_num' => $data['outOrderId']));
        }
        $bool = $this->Online_model->update_order($pay);
        if ($bool) {
            exit('success');
        }
        $this->Online_model->online_erro($pay['id'], '写入现金记录失败:' . json_encode($_REQUEST));
        exit('加钱失败');
    }



     // 同步通知地址
    public function hrefbackurl()
    {
        $data = $_POST;
        if($data['respCode'] != '00'){
                $return_data = [
                    'msg' =>$data['respMsg']
                ];
                $this->load->view('online_pay/error.html', $return_data);
        }
        // 签名规则
        $this->get_public_key();
        // 验证参数
        if (empty($data['outOrderId'])||empty($data['sign'])) {
            $return_data = [
                    'msg' =>'参数错误'
                ];
                $this->load->view('online_pay/error.html', $return_data);
        }
        $sign_str = $data['sign'];
        $sign_str = base64_decode($sign_str);
        unset ($data['sign']);
        //  // 根据订单号获取配置信息
        $pay = $this->Online_model->order_detail($data['outOrderId']);
        $this->key = $pay['pay_key'];
        $qmStr = $this->signStr($data);
        $flag = openssl_verify($qmStr,$sign_str,$this->public_key, OPENSSL_ALGO_SHA1);
        if($flag){
            $return_data = [
                'ordernumber'=>$data['outOrderId'],
                'money' =>$data['transAmt'],
                'jsstr'  =>$this->Online_model->return_jsStr($pay['from_way'],  $pay['pay_return_url']),
                'type'  =>code_pay($pay['pay_code'])
            ];
            $this->load->view('online_pay/success.html', $return_data);
           
        }else{
             $return_data = [
                'msg' =>'支付回调验证失败'
            ];
            $this->load->view('online_pay/error.html', $return_data);
        }
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
                $buff .= $k . "=" . $v . "&";           
            }
        }
        $reqPar;
        if (strlen($buff) > 0) 
        {
            $reqPar = substr($buff, 0, strlen($buff)-1);
        }
        
        return $reqPar;
    }
    

    // 获取公钥和私钥
    public function get_public_key()
    {
        $this->Online_model->select_db('public');
        $pay_id = $this->Online_model->get_one('id', 'bank_online', ['model_name'=>'Jubaofu']);
        $pay_id = $pay_id['id'];
        $this->Online_model->select_db('private');
        $public_key = $this->Online_model->get_one('*', 'bank_online_pay', ['bank_o_id'=>$pay_id]);
        $this->public_key =$public_key['pay_public_key'];
        $this->public_key=openssl_get_publickey($this->public_key);
    }
}