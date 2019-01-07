<?php
defined('BASEPATH') or exit('No direct script access allowed');
/**
 * 隆发支付接口调用
 * Created by PhpStorm.
 * User: 57207
 * Date: 2018/9/26
 * Time: 9:55
 */
include_once __DIR__.'/Publicpay_model.php';
class Longfa_model extends Publicpay_model
{
    protected $c_name = 'longfa';
    protected $p_name = 'LONGFA';//商品名称
    //支付接口签名参数
    private $method = 'D'; //返回签名大小写 D 大写 X 小写
    private $key_string = ''; //参与签名组成
    private $field = 'sign'; //签名参数名

    public function __construct()
    {
        parent::__construct();
    }
    /**
     * 构造支付参数
     */
    protected function getPayData(){

        //构造基本参数
        $data = $this->getBaseData();
        $params= $this->getParam();
        //参数转化成json数据
        $string = json_encode($params,320);
        $string = str_replace('\/\/','//',$string);
        $string = str_replace('\/','/',$string);
        $data['data'] = urlencode($this->encode_pay($string));
        ksort($data);
        $colums = ToUrlParams($data);
        return $colums;
    }
    /**
     * 构造支付基本参数
     */
    protected  function getBaseData(){
        $data['merchNo'] = $this->merId;
        $data['version'] = 'V3.6.0.0';
        return $data;
    }
    /**
     * 构造支付基本参数
     */
    protected  function getParam(){
        $data['merchNo'] = $this->merId;
        $data['netwayType'] = $this->getPayType();
        $data['randomNo'] = (string)(time());
        $data['orderNo']  = (string)$this->orderNum;
        $data['amount'] = (string)yuan_to_fen($this->money);//单位分
        $data['goodsName'] = $this->p_name;
        $data['notifyUrl'] = $this->callback;
        $data['notifyViewUrl'] = $this->returnUrl;
        ksort($data);
        $string = json_encode($data,320);
        $data['sign'] = strtoupper(md5($string.$this->key));
        return $data;
    }
    /*
    * 秘钥加密方式
    */
    public function encode_pay($data){
        $pu_key = openssl_pkey_get_public($this->b_key);
        if ($pu_key == false){
            echo "打开密钥出错";
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
    /**
     * 根据code值获取支付方式
     * @param string code
     * @return string 支付方式 参数
     */
    private function getPayType()
    {
        switch ($this->code)
        {
            case 1:
                return 'WX';//微信扫码
                break;
            case 2:
                return 'WX_WAP';//微信WAP
                break;
            case 4:
                return 'ZFB';//支付宝扫码
                break;
            case 5:
                return 'ZFB_WAP';//支付宝WAP
                break;
            case 7:
                return 'MBANK';//网银
                break;
            case 8:
                return 'QQ';//QQ扫码
                break;
            case 9:
                return 'JD';//京东钱包
                break;
            case 12:
                return 'QQ_WAP';//QQWAP
                break;
            case 17:
                return 'UNION_WALLET';//银联扫码
                break;
            default:
                return 'ZFB';//支付宝扫码
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
        $data = post_pay_data($this->url,$pay_data);
        if (empty($data)) $this->retMsg('接口返回信息错误！');
        //接收参数为JSON格式 转化为数组
        $data = json_decode($data,true);
        if (empty($data)) $this->retMsg('接口返回信息格式错误！');
        //判断是否下单成功
        if (empty($data)||$data['stateCode']<>'00')
        {
            $msg = isset($data['msg']) ? $data['msg'] : '返回参数错误';
            $this->retMsg("下单失败：{$msg}");
        }
        //扫码支付 wap支付返回支付 实际地址
        return $data['qrcodeUrl'];
    }
}