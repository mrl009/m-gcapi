<?php

class Zhonglian extends GC_Controller
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
        $data=file_get_contents("php://input");
        $data=json_decode($data,true);
        $this->get_public_key();
        // 验证参数
        if (empty($data['out_trade_no'])||empty($data['sign'])) {
            $this->Online_model->online_erro('ZL', '参数错误:' . json_encode($data));            
            exit('参数错误');
        }
        // //验证签名
        $sign=$data['sign'];
        $flag = openssl_verify($this->dekong($data),base64_decode($sign),$this->public_key,OPENSSL_ALGO_MD5);
        if(!$flag){
            $this->Online_model->online_erro('ZL', '签名验证失败:' . json_encode($data).'public:'.$this->public_key.'sign:'.$sign);
            exit('签名验证失败');
        }
        //  // 根据订单号获取配置信息
        $pay = $this->Online_model->order_detail($data['out_trade_no']);
        //.加锁
        $bool = $this->Online_model->fbs_lock('temp:new_order_num' . $data['out_trade_no']);
        if (!$bool) {
            exit('请稍后');
        }
       
        if (empty($pay)) {
            $this->Online_model->online_erro('ZL', '无效的订单号:' . json_encode($data));
            exit('无效的订单号');
        }
        $bool = $this->Online_model->update_order($pay);
        if ($bool) {
            exit($this->success);
        }
        $this->Online_model->online_erro($pay['id'], '写入现金记录失败:' . json_encode($data));
        exit('加钱失败');
    }

     /**
     * 获取支付签名
     * @param string $data 支付参数
     * @return string $sign签名值
     */
    private function formatBizQueryParaMap($paraMap)
    {
        $buff = "";
        ksort($paraMap);
        foreach ($paraMap as $k => $v)
        {
            if($v != null && $v != ''){
                $buff .= $k . "=" . $v . "&";           
            }
        }
        $reqPar;
        if (strlen($buff) > 0) 
        {
            $reqPar = substr($buff, 0, strlen($buff)-1);
        }
        return urlencode($reqPar);
    }

    /**
    *去数组空值
    *@param 数组
    */
    private function dekong($data){
        foreach ($data as $k => $v)
        {
            if($v != '' && $k != 'sign'){
                $Parameters[$k] = $v;
            }
        }
        ksort($Parameters);
        return urldecode($this->formatBizQueryParaMap($Parameters));
    }

    

    // 获取公钥和私钥
    public function get_public_key()
    {
        $this->Online_model->select_db('public');
        $pay_id = $this->Online_model->get_one('id', 'bank_online', ['model_name'=>'Zhonglian']);
        $pay_id = $pay_id['id'];
        $this->Online_model->select_db('private');
        $public_key = $this->Online_model->get_one('*', 'bank_online_pay', ['bank_o_id'=>$pay_id]);
        $this->public_key =$public_key['pay_server_key'];
        $this->public_key=openssl_get_publickey($this->public_key);
    }
}