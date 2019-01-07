<?php
/**
 * 通扫支付模块
 * date:2018-04-14
 */

class Zhonglian_model extends MY_Model
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
        $this->money = $money*100;//支付金额,以分为单位
        $this->merId = isset($pay_data['pay_id'])?trim($pay_data['pay_id']) :'';//商户ID
        $this->url   =$pay_data['pay_url'];
        $this->pr_key=$pay_data['pay_private_key'];  //商户私钥
        // $this->pr_key=openssl_get_privatekey($this->pr_key);
        $this->public_key = $pay_data['pay_public_key'];  //服务器公钥
        $this->domain = isset($pay_data['pay_domain']) ? $pay_data['pay_domain'] : '';
        $this->callback = $this->domain . '/index.php/callback/zhonglian/callbackurl';//回调地址
        // //.组装数据
        $data = $this->fzData($pay_data);
        $data =json_encode($data);
        $return_data=$this->request($data);
        $return_data=json_decode($return_data,true);
        if ($return_data['state'] != "00" ) {
            $this->online_erro('ZL', '签名验证失败:' . json_encode($data).'onlineKeyStart:'.$this->pr_key.json_encode($return_data));
            echo json_encode(array('code' => E_OP_FAIL, 'msg' => $return_data['return_msg']));
            exit;
        }
        if (in_array($pay_data['code'], [1, 4, 8, 9,10,15,17,19,38])) {
            $res = [
                'jump' => 3,
                'img' => $return_data['pay_url'],
                'money' => $money,
                'order_num' => $order_num,
            ];
        } elseif (in_array($pay_data['code'], [2, 5,12,13,20,25,33,36])) {
            $res = [
                'url' => urldecode($return_data['pay_url']),
                'jump' => 5
            ];
        } 
        return $res;

    }
    
    //.组装数据
    private function fzData($pay_data)
    {
        $code = isset($pay_data['code']) ? $pay_data['code'] : 0;
        //.wap
        $data['seller_id']=$this->merId;//.商户号

        $data['order_type']=$this->getType($code);//.支付类型
        $data['pay_body']='众联支付';//.商品描述
        $data['out_trade_no']=$this->orderNum;//.订单号
        $data['total_fee']=$this->money;//。支付金额
        $data['notify_url']=$this->callback;//。回调地址
        $data['spbill_create_ip']='59.174.235.48';//。客户端ip
        $data['spbill_times']=time();//。系统时间戳
        $data['noncestr']='cnl'.time();//。随机数
        $data['sign']=$this->sign($data);
        return   $data;


    }


    //.支付方式做映射
    private function getType($code)
    {
         switch ($code) {
            case 1:
                return '2701';//微信扫码
                break;
            case 4:
                return '2702';//支付宝二维码
                break;
            case 7:
                return '2704';//网银网关
                break;
            case 8:
                return '2705';//QQ钱包
                break;
            case 12:
                return '2707';//qqh5
                break;
            case 15:
                return '2709';//京东钱包
                break;
            case 17:
                return '2711';//银联扫码
                break;
            case 20:
                return '2710';//百度WAP
                break;
            case 25:
                return '2703';//银联快捷
                break;
            case 33:
                return '2706';//微信h5
                break;            
            case 36:
                return '2708';//支付宝h5
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
            if($v != null && $v != ''){
                $buff .= $k . "=" . $v . "&";           
            }
        }
        $reqPar;
        if (strlen($buff) > 0) 
        {
            $reqPar = substr($buff, 0, strlen($buff)-1);
        }
        $this->pr_key=openssl_get_privatekey($this->pr_key);
        if(!empty($reqPar)){
             $this->online_erro('ZL', '签名字符串:'.$reqPar);
        }
        openssl_sign($reqPar,$sign_info,$this->pr_key,OPENSSL_ALGO_MD5);
        openssl_free_key($this->pr_key);
        $sign = base64_encode($sign_info);
        return $sign;
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