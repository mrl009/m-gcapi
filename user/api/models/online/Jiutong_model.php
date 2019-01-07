<?php
/**
 * 久通付接口调用
 * User: lqh
 * Date: 2018/08/16
 * Time: 16:20
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';

class Jiutong_model extends Publicpay_model
{
    protected $c_name = 'jiutong';
    private $p_name = 'JIUTONG';//商品名称

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
        return $this->buildWap($data);
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
        ksort($data);
        $string = json_encode($data,320) . $this->key;
        $data['sign'] = strtoupper(md5($string));
        //构造传递参数
        $data = json_encode($data,320);
        $data = $this->encodePay($data);
        $string = urlencode($data);
        $data = array(
            'data' => $string,
            'merchNo' => $this->merId,
            'version' => 'V3.1.0.0'
        );
        $data = ToUrlParams($data);
        return $data;
    }

    /*
     * 秘钥加密方式
     */
    private function encodePay($data)
    {
        $str = '';
        $encryptData = '';
        $bk = openssl_pkey_get_public($this->b_key);
        foreach (str_split($data, 117) as $chunk) 
        {
            openssl_public_encrypt($chunk, $encryptData, $bk);
            $str = $str . $encryptData;
        }
        return base64_encode($str);
    }
    
    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {
        $random = rand(1000,9999);
        $money = yuan_to_fen($this->money);
        $data['amount'] = (string)$money;
        $data['callBackUrl'] = $this->callback;
        $data['callBackViewUrl'] = $this->returnUrl;
        $data['charset'] = 'UTF-8';
        $data['goodsName'] = $this->p_name;
        $data['merNo'] = $this->merId;
        $data['netway'] = $this->bank_type;
        $data['orderNum'] = $this->orderNum;
        $data['random'] = (string)$random;
        $data['version'] = 'V3.1.0.0';
        return $data;
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
        if (empty($data['qrcodeUrl']))
        {
            $msg = isset($data['msg']) ? $data['msg'] : '返回参数错误';
            $this->retMsg("下单失败：{$msg}");
        }
        //扫码支付 wap支付返回支付 实际地址
        return $data['qrcodeUrl'];
    }
}
