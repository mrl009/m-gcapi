<?php
/**
 * G米付T1支付模块
 * date:2018-05-12
 */

class Gmifut1_model extends MY_Model
{



    const URL_WANGGUAN = "https://gateway.gimi321.com/b2cPay/initPay"; //.g米付网关支付
    const URL_SAOMA = "https://gateway.gimi321.com/scanPay/initPay"; //.g米付其他支付
    private $key;
    private $merId;
    private $orderNum;
    private $money;
    private $url;
    private $callback;
    private $hrefback;
    private $domain;
    private $pr_key;//.私钥
    private $public_key;
   

    public function call_interface($order_num, $money, $pay_data)
    {
        $this->orderNum = $order_num;//订单号
        $this->money = $money;//支付金额,以分为单位
        $this->merId = isset($pay_data['pay_id'])?trim($pay_data['pay_id']) :'';//商户ID
        $this->key = $pay_data['pay_key'];  //商户私钥
        $this->domain = isset($pay_data['pay_domain']) ? $pay_data['pay_domain'] : '';
        $this->callback = $this->domain . '/index.php/callback/gmifu/callbackurl';//回调地址
        //.网关支付
        if($pay_data['code'] == 7){
            $this->url = self::URL_WANGGUAN;
        }else{
            $this->url = self::URL_SAOMA;
        }
        $data = $this->payData($pay_data);
        if($pay_data['code'] == 7){
            return $this->buildForm($data);
        }
        $return_data = $this->request($data);
        $return_data = json_decode($return_data,true);
        // //.判断是否支付成功
        if($return_data['resultCode'] !='0000'){
            echo json_encode(array('code' => E_OP_FAIL, 'msg' => $return_data['errMsg']));
            exit;
        }

        if(in_array($pay_data['code'], [1,4,8,9,10,17,22,26])){
            $res = [
                'jump' => 3,
                'img' =>$return_data['payMessage'],
                'money' => $money,
                'order_num' => $order_num,
            ];
        }else{
            $res = [
                'url' => $return_data['payMessage'],
                'jump' => 5
            ];
        }
        return $res;      
    }

    /**
      * @desc scan接口数据组装
      ****/
    private function payData($pay_data)
    {    
        $data['payKey']=$this->merId;//.商户号
        $data['orderPrice']=$this->money;//.金额  单位：元
        $data['outTradeNo']=$this->orderNum;//.订单号
        $data['productType']=$this->getType($pay_data['code']);//。支付方式
        $data['orderTime']=date("Ymdhis",time());//。下单时间
        $data['productName']='G米付';//。产品名称
        $data['orderIp']=get_ip();//。下单ip
        if($pay_data['code'] == 7){
            $data['bankCode']=$pay_data['bank_type'];//。银行编码
            $data['bankAccountType']='PRIVATE_DEBIT_ACCOUNT';//。对私借记卡
        }
        $data['returnUrl']=$_SERVER['HTTP_REFERER']?$_SERVER['HTTP_REFERER']:$pay_data['pay_return_url'];//。通知地址
        $data['notifyUrl']=$this->callback;//。异步通知地址
        $payStr = $this->sign($data,$pay_data['code']);//。拼装传的数据
        if($pay_data['code'] == 7){
            $data['sign'] = $payStr;
            return $data;
        }
        return   $payStr;
    }




    //.支付方式做映射/ T0结算方式   实时结算
    private function getType($code)
    {
         switch ($code) {
            case 1:
                return '10000101';//微信扫码
                break;
            case 2:
                return '10000201';//wap
                break;
            case 40:
                return '10000501';//qq扫码
                break;
            case 4:
                return '20000301';//支付宝扫码
                break;
            case 5:
                return '20000201';//wap
                break;
            case 41:
                return '20000501';//支付宝wap
                break;
            case 25:
                return '40000101';//快捷支付
                break;
            case 17:
                return '60000101';//银联扫码
                break;
            case 18:
                return '60000201';//银联wap
                break;
            case 8:
                return '70000101';//qq扫码
                break;
            case 9:
                return '80000101';//京东扫码
                break;
             case 7:
                return '50000101';//网关支付
                break;

        }
    }



     /**
     * 获取支付签名
     * @param string $data 支付参数
     * @return string $sign签名值 rsa_s加密
     */
    private function sign($data,$code)
    {
        ksort($data);
        $buff='';
        foreach ($data as $k => $v)
        {
            if($v != null && $v != ''&&$k !='sign'){
                $buff=$buff.$k.'='.$v.'&';           
            }
        }
        $str = $buff;
        $buff =$buff.'paySecret='.$this->key;
        $sign = strtoupper(md5($buff));
        $str = $str.'&sign='.$sign;
        if($code == 7){
            return $sign;
        }
        return $str;
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
        $ch=curl_init();
        curl_setopt($ch,CURLOPT_URL,$this->url);
        curl_setopt($ch,CURLOPT_HEADER,0);
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch,CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch,CURLOPT_POST,1);
        curl_setopt($ch,CURLOPT_POSTFIELDS,$data);
        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }



    public function online_erro($id, $str)
    {
        $reidsKey = "online:erro:";
        $this->redis_select(4);
        $this->redis_setex($reidsKey.$id, 90000, $str);
        $this->redis_select(5);
    }

}