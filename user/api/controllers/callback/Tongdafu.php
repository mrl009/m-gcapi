<?php

class Tongdafu extends GC_Controller
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
        $data=$_REQUEST;
        if(empty($data['r1_Code']) ||$data['r1_Code'] !=1){
            $this->Online_model->online_erro('TDF', '支付失败:' . json_encode($_REQUEST));            
            exit('支付失败');
        }
        // 验证参数
        if (empty($data['r5_Order'])||empty($data['hmac'])) {
            $this->Online_model->online_erro('TDF', '参数错误:' . json_encode($_REQUEST));            
            exit('参数错误');
        }
        //验证签名
        $sign = $data['hmac'];
         // 根据订单号获取配置信息
        $pay = $this->Online_model->order_detail($data['r5_Order']);
        $this->key = $pay['pay_key'];
        $local_sign=$this ->sign($data);
        if ($sign != $local_sign) {
            $this->Online_model->online_erro('TDF', '签名验证失败:' . json_encode($_REQUEST).'key:'.$this->key);
            exit('签名验证失败');
        }
        // 加锁
        $bool = $this->Online_model->fbs_lock('temp:new_order_num' . $data['r5_Order']);
        if (!$bool) {
            exit('请稍后');
        }
       
        if (empty($pay)) {
            $this->Online_model->online_erro('TDF', '无效的订单号:' . json_encode($_REQUEST));
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
        $this->Online_model->online_erro($pay['id'], '写入现金记录失败:' . json_encode($_REQUEST));
        exit('加钱失败');
    }



     /**
     * 签名
     * @param array $data 表单内容
     * @return array
     */
    private function sign($data)
    {
        $key = iconv("GB2312","UTF-8",$this->key);
        $str ="{$data['p1_MerId']}{$data['r0_Cmd']}{$data['r1_Code']}{$data['r2_TrxId']}{$data['r3_Amt']}{$data['r4_Cur']}{$data['r5_Order']}{$data['r6_Type']}";
        $str  = iconv("GB2312","UTF-8",$str);
        $b = 64; // byte length for md5
        if (strlen($key) > $b) {
            $key = pack("H*",md5($key));
        }
        $key = str_pad($key, $b, chr(0x00));
        $ipad = str_pad('', $b, chr(0x36));
        $opad = str_pad('', $b, chr(0x5c));
        $k_ipad = $key ^ $ipad ;
        $k_opad = $key ^ $opad;
        return md5($k_opad . pack("H*",md5($k_ipad . $str)));
    }

}