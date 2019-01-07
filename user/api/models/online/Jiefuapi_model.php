<?php

/**
 * 捷付(新)支付接口调用(与前面捷付不同的支付)
 * Created by PhpStorm.
 * User: Tailand
 * Date: 2018/10/27
 * Time: 20:03
 */
defined('BASEPATH') or exit('No direct script access allowed');
//调用公共文件
include_once __DIR__.'/Publicpay_model.php';
class Jiefuapi_model extends Publicpay_model
{
    //redis 错误记录
    protected $c_name = 'jiefuapi';
    private $p_name = 'JIEFUAPI';//商品名称
    //支付接口签名参数
    private $method = 'D'; //返回签名大小写 D 大写 X 小写
    private $key_string = '&key='; //参与签名组成
    private $field = 'sign'; //签名参数名

    public function __construct()
    {
        parent::__construct();
    }
    /**
     * 获取前端返回数据 部分第三方支付不一样
     * @param array
     * @return array
     */
    protected function returnApiData($data)
    {
        //wap支付
        if (in_array($this->code,$this->wap_code))
        {
            return $this->buildWap($data);
            //扫码支付
        } elseif (in_array($this->code,$this->scan_code)) {
            return $this->buidImage($data);
            //网银支付快捷支付和收银台 (部分接口不通用)
        } else {
            return $this->buildForm($data);
        }
    }
    /**
     * 构造支付参数+sign值
     * @return array
     */
    protected function getPayData()
    {
        //构造基本参数
        $data = $this->getBaseData();
        //构造签名参数
        $f = $this->field;
        $m = $this->method;
        $k = $this->key_string . $this->key;
        //json格式化后在去掉反斜线
        $string = stripslashes(json_encode($data));
        //使用支付后台公钥 加密json data
        $rsamsg = $this->encrypt($string);

        //制作簽名
        $tmp_sign = $this->sign($rsamsg);
        //返回传送的报文参数
        unset($data,$string);
        $param['merchant_code'] = $this->merId;
        $param['data'] = $rsamsg;
        $param['sign'] = $tmp_sign;
        return $param;
    }

    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {
        $data['amount'] = $this->money;
        $data['platform'] = 'PC';
        $data['note'] = date("Y-m-d H:i:s");
        if($this->code==7){
            $data['bank_code'] = $this->bank_type;
        }else{
            $data['bank_code'] = '';
        }
        $data['service_type'] = $this->getPayType();
        $data['merchant_user'] = $this->user['id'];//用户id
        $data['merchant_order_no'] = $this->orderNum;
        $data['risk_level'] = '1';
        $data['callback_url'] = $this->callback;
        return $data;
    }

    /**
     * 根据code值获取支付方式
     * @param string code
     * @return string 聚合付支付方式 参数
     */
    private function getPayType()
    {
        switch ($this->code)
        {
            case 1:
                return '2';//微信扫码
                break;
            case 2:
                return '8';//微信Wap/h5
                break;
            case 4:
                return '3';//支付宝扫码
                break;
            case 5:
                return '9';//支付宝WAP
                break;
            case 7:
                return '1';//网银支付
                break;
            case 8:
                return '4';//QQ扫码
                break;
            case 9:
                return '5';//京东扫码
                break;
            case 12:
                return '10';//QQWAP
                break;
            case 13:
                return '12';//jdWAP
                break;
            case 17:
                return '11';//银联钱包
                break;
            case 25:
                return '13';//银联快捷
                break;
            default:
                return '3';//支付宝扫码
                break;
        }
    }
    /**
     * 获取支付结果
     * @param $data 支付参数
     * @return return 二维码内容
     */
    protected function getPayResult($pay_data)
    {
        //传递参数
        $pay_data="merchant_code=".$this->merId."&data=".rawurlencode($pay_data['data'])."&sign=".rawurlencode($pay_data['sign']);
        $opts = array('http' =>
            array(
                'method'  => 'POST',
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                'content' => $pay_data
            )
        );

        //传送请求
        $context = stream_context_create($opts);
        //接收返回信息
        $result = file_get_contents($this->url, false, $context);
        //接收参数为JSON格式 转化为数组
        $J=json_decode($result);
        $tmp_str=$J->data;
        $tmp_error=$J->error_code;

        $decrypted = "";
        $decodeStr =($tmp_str);

        //解密信息
        $decrypted =$this->decrypt($decodeStr );
        //判断是否有错误,有错误输出错误代码，没错误输出充值连结

        if (empty($transaction_url)==1) {
            $J2=json_decode($decrypted);
            $result=$J2->transaction_url;
            if(in_array($this->code,$this->scan_code)){
                $result=$J2->qr_image_url;
                if(empty($result)){
                    $result=$J2->transaction_url;
                }
            }
        }
        if (!$tmp_error=="") {
            $msg = isset($tmp_error) ? $tmp_error : '返回信息错误';
            $this->retMsg("下单失败：{$msg}");
        }
        return  $result;
    }
    /**
     * 加密
     *
     * @param string 明文
     * @param string 密文編碼（base64/hex/bin）
     * @param int 填充方式(所以目前僅支持OPENSSL_PKCS1_PADDING)
     * @return string 密文
     */
    public function encrypt($data , $code = 'base64', $padding = OPENSSL_PKCS1_PADDING )
    {
        $ret = false;
        if (!$this->_checkPadding($padding, 'en')) $this->_error('padding error');
        $tmpCode="";
        //明文过长分段加密
        foreach (str_split($data, 117) as $chunk) {
            openssl_public_encrypt($chunk, $encryptData, $this->s_key, $padding);
            $tmpCode .=$encryptData ;
            $ret = base64_encode($tmpCode);

        }
        return $ret;
    }
    /**
     * 解密
     *
     * @param string 密文
     * @param string 密文編碼（base64/hex/bin）
     * @param int 填充方式（OPENSSL_PKCS1_PADDING / OPENSSL_NO_PADDING）
     * @param bool 是否翻轉明文（When passing Microsoft CryptoAPI-generated RSA cyphertext, revert the bytes in the block）
     * @return string 明文
     */
    public function decrypt($data, $code = 'base64', $padding = OPENSSL_PKCS1_PADDING, $rev = false)
    {
        $ret = false;
        //$data = $this->_decode($data, $code);
        $data = base64_decode($data);
        if (!$this->_checkPadding($padding, 'de')) $this->_error('padding error');
        if ($data !== false) {

            $enArray = str_split($data, 256);
            foreach ($enArray as $va) {
                openssl_private_decrypt($va,$decryptedTemp,$this->p_key);//私钥解密
                $ret .= $decryptedTemp;
            }
        }
        else
        {
            echo "<br>解密失敗<br>".$data;
        }
        return $ret;
    }

    /**
     * 生成签名
     * $data string 签名材料
     * $code string 签名编码（base64/hex/bin）
     * $ret 签名值
     */
    public function sign($data, $code = 'base64')
    {
        $ret = false;
        if (openssl_sign($data, $ret, $this->p_key ,OPENSSL_ALGO_SHA1 )) {
            $ret = base64_encode('' . $ret);
        }
        return $ret;
    }
    /**
     * 检查填充类型
     * 加密只支持PKCS1_PADDING
     * 解密支持PKCS1_PADDING和NO_PADDING
     *
     * $padding int 填充模式(OPENSSL_PKCS1_PADDING,OPENSSL_NO_PADDING ...etc.)
     * $type string 加密en/解密de
     * $ret bool
     */
    private function _checkPadding($padding, $type)
    {
        if ($type == 'en') {
            switch ($padding) {
                case OPENSSL_PKCS1_PADDING:
                    $ret = true;
                    break;
                default:
                    $ret = false;
            }
        } else {
            switch ($padding) {
                case OPENSSL_PKCS1_PADDING:
                case OPENSSL_NO_PADDING:
                    $ret = true;
                    break;
                default:
                    $ret = false;
            }
        }
        return $ret;
    }
}