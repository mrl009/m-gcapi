<?php
/**
 * 易付支付模块
 * date:2018-04-09
 */

class Huiyin_model extends MY_Model
{
    public $key;
    public $merId;
    public $orderNum;
    public $money;
    public $url;
    public $callback;
    public $domain;

    public function call_interface($order_num, $money, $pay_data)
    {
        $this->orderNum = $order_num;//订单号
        $this->money = $money;//支付金额
        $this->merId = isset($pay_data['pay_id']) ? trim($pay_data['pay_id']) : '';//商户ID
        $this->key = isset($pay_data['pay_key']) ? $pay_data['pay_key'] : '';//密钥
        $this->url = isset($pay_data['pay_url']) ? $pay_data['pay_url'] : '';//请求地址
        $this->domain = isset($pay_data['pay_domain']) ? $pay_data['pay_domain'] : '';
        $this->callback = $this->domain . '/index.php/callback/huiyin/callbackurl';//回调地址
        // 组装数据
        $data = $this->fzData($pay_data);
        return  $this->buildForm($data);

    }
    
    //.组装数据
    private function fzData($pay_data)
    {
        $code = isset($pay_data['code']) ? $pay_data['code'] : 0;
        $data['merchantcode']=$this->merId;//.商户id
        $data['type']=$this->getType($code);//.通道类型
        $data['amount']=$this->money;//。支付金额
        $data['orderid']=$this->orderNum;//.订单号jhj
        $data['notifyurl']=$this->callback;//。异步通知地址
        $data['sign']=$this->sign($data);
        return   $data;


    }


    //.支付方式做映射
    private function getType($code)
    {
         switch ($code) { 
            case 1:
                return 'WEIXIN';//微信支付
                break;           
            case 4:
                return 'ALIPAY';//支付宝
                break;
            case 8:
                return 'QQ';//qq扫码
                break;
            case 9:
                return 'JD';//京东支付
                break;
            case 10:
                return 'BAIDU';//百度钱包
                break;
             case 15:
                return 'JDH5';//京东h5
                break;
            case 16:
                return 'QQH5';//qqh5
                break;
             case 17:
                return 'YINLIAN';//银联钱包
                break; 
            case 21:
                return 'BAIDUH5';//百度钱包h5
                break;
            case 25:
                return 'KUAIJIE';//银联快捷
                break;
            case 26:
                return 'SHOUYINTAI';//收银台
                break;
            case 33:
                return 'WEIXINH5';//微信h5
                break;
            case 36:
                return 'ALIPAYH5';//支付宝h5
                break;            
        }
    }



     /**
     * 获取支付签名
     * @param string $data 支付参数
     * @return string $sign签名值
     */
    private function sign($data)
    {
        ksort($data);
        $md5str = '';
        foreach ($data as $key => $val) {
            $md5str.=$key.'='.$val.'&';
        }
        $signStr = trim($md5str,' ').'key='. $this->key;
        return strtoupper(md5($signStr));
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
        // $str="";
        // $str.="<body onload='document.form1.submit();'>";
        // $str.="<form id='form' method='post' action='".$this->url."' style='display:none'>";
        // foreach ($$data as $k => $v) {
        //     $str.="<input type='text' name='".$k."'  value='".$v."'>";
        // }
        // $str.="</form>";
        // $str.="</body>";
        // echo $str;


        // $ch = curl_init();
        // curl_setopt($ch, CURLOPT_URL, $this->url);
        // curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        // curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        // curl_setopt($ch, CURLOPT_POST, true);
        // curl_setopt($ch, CURLOPT_HEADER, false);
        // curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        // curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // $rs = curl_exec($ch);
        // curl_close($ch);
        // return $rs;
        // $ch = curl_init();  
        // curl_setopt($ch, CURLOPT_POST, 1);  
        // curl_setopt($ch, CURLOPT_URL, $this->url);  
        // curl_setopt($ch, CURLOPT_POSTFIELDS, $data);  
        // curl_setopt($ch, CURLOPT_HTTPHEADER, array(  
        //     'Content-Type: application/json; charset=utf-8',  
        //     'Content-Length: ' . strlen($data))  
        // );  
        // ob_start();  
        // curl_exec($ch);  
        // $return_content = ob_get_contents();  
        // ob_end_clean(); 
        // // $return_content=curl_exec($ch); 

        // $return_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);  
        // return $return_content;  



        // $data_string = json_encode($data);
        // $curl = curl_init($url);
        // curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
        // curl_setopt($curl, CURLOPT_POSTFIELDS,$data_string);
        // curl_setopt($curl, CURLOPT_TIMEOUT, 30); // 设置超时限制防止死循环
        // curl_setopt($curl, CURLOPT_RETURNTRANSFER,true);
        // curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        // curl_setopt($curl, CURLOPT_REFERER, $pay_domain);
        // curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false); // 从证书中检查SSL加密算法是否存在
        // curl_setopt($curl, CURLOPT_URL, $this->url); // 要访问的地址
        // curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1); // 使用自动跳转
        // curl_setopt($curl, CURLOPT_AUTOREFERER, 1); // 自动设置Referer

        // curl_setopt($curl, CURLOPT_HTTPHEADER, array(
        //         'Content-Type: application/json',
        //         'Content-Length: ' . strlen($data_string))
        // );
        // $result = curl_exec($curl);
        // if (curl_errno($curl)) {
        //     echo 'Errno'.curl_error($curl);//捕抓异常
        // }
        // curl_close($curl);
        // return $result;
    }

}