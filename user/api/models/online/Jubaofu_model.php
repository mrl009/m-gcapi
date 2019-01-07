<?php
/**
 * 聚宝支付模块
 * date:2018-05-12
 */

class Jubaofu_model extends MY_Model
{



    const URL_WANGGUAN = "https://payment.fujubaopay.com/sfpay/payServlet"; //.聚付宝网关支付
    const URL_SAOMA = "https://payment.fujubaopay.com/sfpay/scanCodePayServlet"; //.聚付宝扫码支付
    const URL_WAP = "https://payment.fujubaopay.com/sfpay/h5PayServlet"; //.聚付宝h5支付
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
        $this->pr_key=$pay_data['pay_private_key'];  //商户私钥
        $this->public_key = $pay_data['pay_public_key'];  //服务器公钥
        $this->domain = isset($pay_data['pay_domain']) ? $pay_data['pay_domain'] : '';
        $this->callback = $this->domain . '/index.php/callback/jubaofu/callbackurl';//回调地址
        $this->hrefback = $this->domain . '/index.php/callback/jubaofu/hrefbackurl';//回调地址
        //.网关支付
        if($pay_data['code'] == 7){
            $this->url = self::URL_WANGGUAN;
            //.封装数据
            $data = $this->WangGuanData($pay_data);
            return $this->buildForm($data);
        }

        if(in_array($pay_data['code'],[1,4,8,17])){
            $this->url = self::URL_SAOMA;
            //.扫码支付接
            $data = $this->SaomaH5Data($pay_data);
            $return =  $this->request($data);
            if($return['respCode'] != '00' && $return['respCode'] != '99'){
                echo json_encode(array('code' => E_OP_FAIL, 'msg' => $return['respMsg']));
                exit;
            }
             $res = [
                'jump' => 3,
                'img' => $return['payCode'],
                'money' => $return['transAmt'],
                'order_num' => $order_num,
            ];
            return $res;
        }

        if(in_array($pay_data['code'], [2,5,12])){
            $this->url = self::URL_WAP;
            //.封装数据
            $data = $this->SaomaH5Data($pay_data);
            return $this->buildForm($data);
        }
        
    }

    /**
      * @desc 封装网关支付数据
      ****/
    private function WangGuanData($pay_data)
    {
        $data['merchantId']=$this->merId;//.商户号
        $data['notifyUrl']=$this->callback;//.异步返回地址
        $data['returnUrl']=$_SERVER['HTTP_REFERER']?$_SERVER['HTTP_REFERER']:$pay_data['pay_return_url'];//.同步返回地址
        $data['outOrderId']=$this->orderNum;//.订单号
        $data['subject']='聚付宝支付';//。订单名称
        $data['body']='聚付宝支付';//。订单描述
        $data['transAmt']=$this->money;//。金额
        $data['defaultBank']=$pay_data['bank_type'];//。银行编码
        $data['channel']='B2C';//。银行渠道
        $data['cardAttr']=1;//。卡类型
        $data['sign']=$this->sign($data);//。卡类型
        return   $data;
    }


    /**
      * @desc 封装扫码支付数据
      ****/
    private function SaomaH5Data($pay_data)
    {    
        $data['merchantId']=$this->merId;//.商户号
        $data['notifyUrl']=$this->callback;//.异步返回地址
        $data['outOrderId']=$this->orderNum;//.订单号
        $data['subject']='聚付宝支付';//。订单名称
        $data['transAmt']=$this->money;//。金额
        $data['scanType']=$this->getType($pay_data['code']);//。支付方式
        $data['sign']=$this->sign($data);//。卡类型sss
        return   $data;
    }

    //.支付方式做映射
    private function getType($code)
    {
         switch ($code) {
            case 1:
                return '20000002';//微信扫码
                break;
            case 4:
                return '10000001';//支付宝二维码
                break;
            case 8:
                return '30000003';//qq扫码
                break;
            case 17:
                return '80000008';//银联扫码
                break;
            case 2:
                return '20000003';//微信wap
                break;
            case 5:
                return '10000002';//支付宝wap
                break;
            case 12:
                return '30000004';//qq钱包
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
        $this->pr_key=openssl_get_privatekey($this->pr_key);
        openssl_sign($reqPar,$sign_info,$this->pr_key,OPENSSL_ALGO_SHA1);
        openssl_free_key($this->pr_key);
        $sign = base64_encode($sign_info);
        return $sign;
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
        curl_setopt($ch, CURLOPT_URL, $this->url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response=curl_exec($ch);
        curl_close($ch);
        $response = json_decode($response,true);
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