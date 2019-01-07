<?php
/**
 * 明捷支付模块
 * date:2018-05-12
 */

class Mingjiefu_model extends MY_Model
{
    const URL_WXSM = "http://wx.mjzfpay.com:90/api/pay"; //.微信扫码
    const URL_WXWAP = "http://wxwap.mjzfpay.com:90/api/pay"; //.微信wap
    const URL_WXWTXM = "http://wx.mjzfpay.com:90/api/pay"; //.微信条形码
    const URL_ZFBSM = "http://zfb.mjzfpay.com:90/api/pay"; //.支付宝扫码
    const URL_ZFBWAP = "http://zfbwap.mjzfpay.com:90/api/pay"; //.支付宝wap
    const URL_ZFBTXM = "http://zfb.mjzfpay.com:90/api/pay"; //.支付宝条形码
    const URL_QQ = "http://qq.mjzfpay.com:90/api/pay"; //.QQ
    const URL_QQWAP = "http://qqwap.mjzfpay.com:90/api/pay"; //.QQWAP
    const URL_JD = "http://jd.mjzfpay.com:90/api/pay"; //.JD
    const URL_JDWAP = "http://jdwap.mjzfpay.com:90/api/pay"; //.JDwap
    const URL_YLSM = "http://union.mjzfpay.com:90/api/pay"; //.银联扫码
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
        $this->money = strval($money*100);//支付金额,以分为单位
        $this->merId = isset($pay_data['pay_id'])?trim($pay_data['pay_id']) :'';//商户ID
        $this->key = isset($pay_data['pay_key']) ? $pay_data['pay_key'] : '';//密钥
        $this->pr_key=$pay_data['pay_private_key'];  //商户私钥
        $this->public_key = $pay_data['pay_public_key'];  //服务器公钥
        $this->domain = isset($pay_data['pay_domain']) ? $pay_data['pay_domain'] : '';
        $this->callback = $this->domain . '/index.php/callback/mingjiefu/callbackurl';//回调地址
        $this->url = $this->getUrl($pay_data['code']);
        //.获取数据
        $post_data = $this->getData($pay_data);
        $json_data = $this->json_encode_ex($post_data);
        $dataStr = $this->encode_pay($json_data);
        $url_data = 'data=' . urlencode($dataStr) . '&merchNo=' . $this->merId . '&version=V3.0.0.0';
        //.请求接口
        $result = $this->request($url_data);
        $result = json_decode($result,true);
        if($result['stateCode']!='00'){
            echo json_encode(array('code' => E_OP_FAIL, 'msg' => $result['msg']));
            exit;
        }

        if(in_array($pay_data['code'],[1,4,8,9,17])){
            $res = [
                'jump' => 3,
                'img' => $result['qrcodeUrl'],
                'money' => $money,
                'order_num' => $order_num,
            ];
        }else{
             $res = [
                'url' => $result['qrcodeUrl'],
                'jump' => 5
            ];
        }
        return $res;   
    }

    /**
      * @desc 封装网关支付数据
      ****/
    private function getData($pay_data)
    {
        $data['version'] = 'V3.0.0.0';//.版本
        $data['merchNo'] = $this->merId;//.版本
        $data['netwayCode'] = $this->getType($pay_data['code']);//.支付网关编码
        $data['randomNum'] = substr(str_shuffle("abcdefghijklmnopqrstuvwxyz0123456789"), 0, 8);//.随机数
        $data['orderNum'] = $this->orderNum;//。订单号
        $data['amount'] = $this->money;//。支付金额 分
        $data['goodsName'] = 'KJF';//。商品名称
        $data['callBackUrl'] = $this->callback;//。异步通知地址
        $data['callBackViewUrl'] = $_SERVER['HTTP_REFERER']?$_SERVER['HTTP_REFERER']:$pay_data['pay_return_url'];//。回显地址
        $data['charset'] = 'UTF-8';//。客户端系统编码格式
        $data['sign'] = $this->sign($data);//。签名
        return   $data;
    }


    //.支付方式做映射
    private function getType($code)
    {
         switch ($code) {
            case 1:
                return 'WX';//微信扫码
                break;
            case 2:
                return 'WX_WAP';//微信wap
                break;
            case 4:
                return 'ZFB';//支付宝扫码
                break;
            case 5:
                return 'ZFB_WAP';//支付宝wap
                break;
            case 8:
                return 'QQ';//qq扫码
                break;
            case 9:
                return 'JD';//jd扫码
                break;
            case 12:
                return 'QQ_WAP';//qqwap
                break;
            case 13:
                return 'JD_WAP';//jdwap
                break;
            case 17:
                return 'UNION_WALLET';//银联扫码
                break;
            case 40:
                return 'WX_AUTH_CODE';//微信条形码
                break;
            case 41:
                return 'ZFB_AUTH_CODE';//支付宝条形码
                break;

        }
    }
    

    //。获取支付地址
    private function getUrl($code)
    {
         switch ($code) {
            case 1:
                return self::URL_WXSM;//微信扫码
                break;
            case 2:
                return self::URL_WXWAP;//微信wap
                break;
            case 4:
                return self::URL_ZFBSM;;//支付宝扫码
                break;
            case 5:
                return self::URL_ZFBWAP;;//支付宝wap
                break;
            case 8:
                return self::URL_QQ;//qq扫码
                break;
            case 9:
                return self::URL_JD;//jd扫码
                break;
            case 12:
                return self::URL_QQWAP;//qqwap
                break;
            case 13:
                return self::URL_JDWAP;//jdwap
                break;
            case 17:
                return self::URL_YLSM;//银联扫码
                break;
            case 40:
                return self::URL_WXWTXM;//微信条形码
                break;
            case 41:
                return self::URL_ZFBTXM;//支付宝条形码
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
        $sign = strtoupper(md5($this->json_encode_ex($data) . $this->key));
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
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $tmpInfo = curl_exec($ch);
        if (curl_errno($ch)) {
            return curl_error($ch);
        }
        return $tmpInfo;
        
    }



    public function online_erro($id, $str)
    {
        $reidsKey = "online:erro:";
        $this->redis_select(4);
        $this->redis_setex($reidsKey.$id, 90000, $str);
        $this->redis_select(5);
    }



    //.json数据处理
    private function json_encode_ex($value){
         if (version_compare(PHP_VERSION,'5.4.0','<')){
            $str = json_encode($value);
            $str = preg_replace_callback("#\\\u([0-9a-f]{4})#i","replace_unicode_escape_sequence",$str);
            $str = stripslashes($str);
            return $str;
        }else{
            return json_encode($value,320);
        }
    }


    //。对传输的数据加密
    public function encode_pay($data){#加密
        $pu_key = openssl_pkey_get_public($this->public_key);
        if ($pu_key == false){
            echo json_encode(array('code' => E_OP_FAIL, 'msg' => '打开密钥错误'));
            die;
        }
        $encryptData = '';
        $crypto = '';
        foreach (str_split($data, 117) as $chunk) {
            openssl_public_encrypt($chunk, $encryptData, $pu_key);
            $crypto = $crypto . $encryptData;
        }

        $crypto = base64_encode($crypto);
        return $crypto;
    }


}