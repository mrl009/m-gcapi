<?php

class Ehuifu extends GC_Controller
{
    private $key;
    private $success = "success";
    private $private_key = null;
    private $public_key = null;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('pay/Online_model');
    }

    public function callbackurl()
    {
        $data = $_REQUEST;
        $this->get_public_key();
        // 验证参数
        if (empty($data['MchOrderNo'])||empty($data['Sign'])) {
            $this->Online_model->online_erro('EHF', '参数错误:' . json_encode($data));            
            exit('参数错误');
        }
        $sign_str = $data['Sign'];
        $sign_str = base64_decode($sign_str);
        unset ($data['Sign']);
        if(isset($data['Remark'])){
            unset($data['Remark']);
        }
        //  // 根据订单号获取配置信息
        $pay = $this->Online_model->order_detail($data['MchOrderNo']);
        $this->key = $pay['pay_key'];
        $qmStr = $this->signStr($data);
        $flag = openssl_verify($qmStr,$sign_str,$this->public_key, 'sha256');
        if(!$flag){
            $this->Online_model->online_erro('EHF', '签名验证失败:' . json_encode($_REQUEST).'public:'.$this->public_key.'key:'.$this->key.'qmstr:'.$qmStr);
            exit('签名验证失败');
        }
        //.加锁
        $bool = $this->Online_model->fbs_lock('temp:new_order_num' . $data['MchOrderNo']);
        if (!$bool) {
            exit('请稍后');
        }
       
        if (empty($pay)) {
            $this->Online_model->online_erro('EHF', '无效的订单号:' . json_encode($data));
            exit('无效的订单号');
        }
        //金额不同
        if ($pay['price'] != $data['PayAmount']) {
            $pay['total_price'] = $data['PayAmount'];
            $pay['price'] = $data['PayAmount'];
            $this->Online_model->write('cash_in_online', array('total_price' => $data['PayAmount'], 'price' => $data['PayAmount']), array('order_num' => $data['MchOrderNo']));
        }
        $bool = $this->Online_model->update_order($pay);
        if ($bool) {
            exit('ok');
        }
        $this->Online_model->online_erro($pay['id'], '写入现金记录失败:' . json_encode($data));
        exit('加钱失败');
    }

    

    /**
     * 获取支付签名
     * @param string $data 支付参数
     * @return string $sign签名值 rsa_s加密
     */
    private function signStr($data)
    {
        $str = "";
        foreach($data as $k=>$v){

            if($k == 'sign' || $v ==''){
                continue;
            }
            $str .= $v."|";
        }

        $str .= $this->key;
        
        return $str;
    }
    

    // 获取公钥和私钥
    public function get_public_key()
    {
        $this->Online_model->select_db('public');
        $pay_id = $this->Online_model->get_one('id', 'bank_online', ['model_name'=>'Ehuifu']);
        $pay_id = $pay_id['id'];
        $this->Online_model->select_db('private');
        $public_key = $this->Online_model->get_one('*', 'bank_online_pay', ['bank_o_id'=>$pay_id]);
        $this->public_key =$public_key['pay_public_key'];
    }
}