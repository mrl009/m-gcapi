<?php

class Mingjiefu extends GC_Controller
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
        //.获取私钥
        $this->get_key();
        //.密文数据
        $jmData = $_POST['data'];
        if (empty($jmData)) {
            $this->Online_model->online_erro('MJF', '参数错误:');
            exit('参数错误');
        }
        //.解密数据
        $data = $this->decode($jmData);
        $data = json_decode($data,true);
        // 判断支付是否成功
        if ($data['payStateCode']!='00') {
            $this->Online_model->online_erro('MJF', '支付失败:' . json_encode($data));            
            exit('支付失败');
        }
        // 验证参数
        if (empty($data['orderNum'])||empty($data['sign'])) {
            $this->Online_model->online_erro('MJF', '参数错误:' . json_encode($data));            
            exit('参数错误');
        }
        $sign_str = $data['sign'];
        //  // 根据订单号获取配置信息
        $pay = $this->Online_model->order_detail($data['orderNum']);
        $this->key = $pay['pay_key'];
        $qmStr = $this->signStr($data);
        if($sign_str!=$qmStr){
            $this->Online_model->online_erro('MJF', '签名验证失败:' . json_encode($data));
            exit('签名验证失败');
        }
        //.加锁
        $bool = $this->Online_model->fbs_lock('temp:new_order_num' . $data['orderNum']);
        if (!$bool) {
            exit('请稍后');
        }
       
        if (empty($pay)) {
            $this->Online_model->online_erro('MJF', '无效的订单号:' . json_encode($data));
            exit('无效的订单号');
        }

        //.判断金额是不是一致
        if ($pay['price'] !=$data['amount']/100) {
            $this->Online_model->online_erro('1MJF', '支付金额异常:' . json_encode($data));
            exit('支付金额异常');
        }
        //已经确认
        if ($pay['status'] == 2) {
            exit('SUCCESS');
        }
        $bool = $this->Online_model->update_order($pay);
        if ($bool) {
            exit('SUCCESS');
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
        ksort($data);
        $sign_array = array();
        foreach ($data as $k => $v) {
            if ($k !== 'sign'){
                $sign_array[$k] = $v;
            }
        }
        $md5 =  strtoupper(md5($this->json_encode_ex($sign_array) . $this->key));
        return $md5;
    }
    

    // 获取公钥和私钥
    public function get_key()
    {
        $this->Online_model->select_db('public');
        $pay_id = $this->Online_model->get_one('id', 'bank_online', ['model_name'=>'Mingjiefu']);
        $pay_id = $pay_id['id'];
        $this->Online_model->select_db('private');
        $public_key = $this->Online_model->get_one('*', 'bank_online_pay', ['bank_o_id'=>$pay_id]);
        $this->public_key = $public_key['pay_public_key'];
        $this->private_key = $public_key['pay_private_key'];
    }

    //.数据解密
    public function decode($data){
        $pr_key = openssl_get_privatekey($this->private_key);
        if ($pr_key == false){
            $this->Online_model->online_erro('MJF', '打开密钥失败:' . $this->private_key);
            exit('打开密钥失败');
        }
        $data = base64_decode($data);
        $crypto = '';
        foreach (str_split($data, 128) as $chunk) {
            openssl_private_decrypt($chunk, $decryptData, $pr_key);
            $crypto .= $decryptData;
        }
        return $crypto;
    }


    //.json数据处理
    private function json_encode_ex($value)
    {
         if (version_compare(PHP_VERSION,'5.4.0','<')){
            $str = json_encode($value);
            $str = preg_replace_callback("#\\\u([0-9a-f]{4})#i","replace_unicode_escape_sequence",$str);
            $str = stripslashes($str);
            return $str;
        }else{
            return json_encode($value,320);
        }
    }
}