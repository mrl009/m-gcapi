<?php
/**
 * E支付模块
 * date:2018-05-09
 */

class Ehuifu_model extends MY_Model
{
    private $key;
    private $merId;
    private $orderNum;
    private $money;
    private $url;
    private $callback;
    private $domain;
    private $pr_key;//.私钥
    private $public_key;
   

    public function call_interface($order_num, $money, $pay_data)
    {
        $this->orderNum = $order_num;//订单号
        $this->money =  $money;//支付金额,以分为单位
        if($pay_data['code'] == 5){
            $this->money = strval($money).'.00';
        }
        $this->merId = isset($pay_data['pay_id'])?trim($pay_data['pay_id']) :'';//商户ID
        $this->url   =$pay_data['pay_url'];
        $this->key   =$pay_data['pay_key'];
        $this->pr_key=$pay_data['pay_private_key'];  //商户私钥
        // $this->pr_key=openssl_get_privatekey($this->pr_key);
        $this->public_key = $pay_data['pay_public_key'];  //服务器公钥
        $this->domain = isset($pay_data['pay_domain']) ? $pay_data['pay_domain'] : '';
        $this->callback = $this->domain . '/index.php/callback/ehuifu/callbackurl';//回调地址
        // //.组装数据
        $data = $this->fzData($pay_data);
        return $this->buildForm($data);

    }
    
    //.组装数据
    private function fzData($pay_data)
    {
        $code = isset($pay_data['code']) ? $pay_data['code'] : 0;
        //.wap
        $data['Version'] = "1.0";//.版本

        $data['MchId'] = $this->merId;//.商户号
        $data['MchOrderNo'] = $this->orderNum;//.商户号
        $data['PayType'] = $this->getType($code);//.支付方式
        if(in_array($pay_data['code'], [7, 9, 13, 17,25,27])){
            $data['BankCode'] = $this->getBankCode($code,$pay_data['bank_type']);//.银行编码
        }
        $data['Amount'] = $this->money;//。支付金额
        $data['OrderTime'] = date('YmdHis');//。时间
        $data['ClientIp'] = get_ip();//。客户端ip
        $data['NotifyUrl'] = $this->callback;//。异步通知地址
        $data['sign'] = $this->sign($data);
        return   $data;


    }


    //.支付方式做映射
    private function getType($code)
    {
         switch ($code) {
            case 1:
                return '10';//微信扫码
                break;
            case 2:
                return '20';//微信ap
                break;
            case 4:
                return '30';//支付宝扫码
                break;
            case 5:
                return '40';//支付宝wap
                break;
            case 7:
                return '50';//网关支付
                break;
            case 17:
                return '50';//银联扫码
                break;
            case 9:
                return '50';//京东扫码
                break;
            case 25:
                return '80';//网银快捷
                break;
            case 27:
                return '90';//网银wap
                break;
            case 13:
                return '51';//京东H5
                break;   
            case 8:
                return '60';//qq钱包
                break;
            case 12:
                return '70';//qqwap
                break;             
        }
    }
    

    //.支付方式做映射
    private function getBankCode($code,$bankType)
    {
         switch ($code) {
            case 7:
                return $bankType;//微信扫码
                break;
            case 17:
                return 'UNIONPAY';//银联扫码
                break;
            case 9:
                return 'JDPAY';//京东扫码
                break;
            case 27:
                return 'BANKWAP';//银联wap
                break;
        }
    }


     /**
     * 获取支付签名
     * @param string $data 支付参数
     * @return string $sign签名值 rsa_s加密
     */
    private function sign($data)
    {
        $str = "";
        foreach($data as $k=>$v){

            if($k == 'sign' || $v =='' || $k =='BankCode'){
                continue;
            }
            $str .= $v."|";
        }

        $str .= $this->key;
        $sign_falg = openssl_sign( $str, $sign, $this->pr_key, 'sha256' );
        if ($sign_falg) {
            $sign_base64 = base64_encode ( $sign );
        }
        return $sign_base64;
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




     /**
     * 创建表单
     * @param array $data 表单内容
     * @return array
     */
    private function buildForm($data)
    {
        $temp = [
            'method' => 'post',
            'data' => $data,
            'url' => $this->url
        ];
        $rs['jump'] = 5;
        $rs['url'] = $this->domain . '/index.php/pay/pay_test/pay_sest/' . $this->orderNum;
        $rs['json'] = json_encode($temp, JSON_UNESCAPED_UNICODE);
        return $rs;
    }



    /**
     * 请求接口
     * @param $data
     * @return mixed
     */
    private function request($data)
    {
        $ch = curl_init();  
        curl_setopt($ch, CURLOPT_POST, 1);  
        curl_setopt($ch, CURLOPT_URL, $this->url);  
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);  
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(  
            'Content-Type: application/json; charset=utf-8',  
            'Content-Length: ' . strlen($data))  
        );  
        ob_start();  
        curl_exec($ch);  
        $return_content = ob_get_contents();  
        ob_end_clean();  

        $return_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);  
        return $return_content;  
    }



    public function online_erro($id, $str)
    {
        $reidsKey = "online:erro:";
        $this->redis_select(4);
        $this->redis_setex($reidsKey.$id, 90000, $str);
        $this->redis_select(5);
    }

}