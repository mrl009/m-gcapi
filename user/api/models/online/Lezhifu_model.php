<?php

/**
 * 乐智付支付接口调用
 * Created by PhpStorm.
 * User: Tailand
 * Date: 2018/11/20
 * Time: 14:25
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';
class Lezhifu_model extends Publicpay_model
{
    protected $c_name = 'lezhifu';
    private $p_name = 'LEZHIFU';//商品名称
    private $method = 'D'; //返回签名大小写 D 大写 X 小写
    private $key_string = '&'; //参与签名组成
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
       if(in_array($this->code,$this->scan_code)){
           return $this->buidImage($data);
         }else{
           return $this->buildWap($data);
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
        $k =$this->key_string. $this->key;
        $string = ToUrlParams($data) . $k;
        //拼接字符串进行MD5小写加密
        $data[$f] = strtolower(md5($string));
        $data['ip']      = get_ip();
        if($this->code == 7){
            $data['type'] = $this->bank_type;
        }
        return $data;
    }


    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {
        $data['account'] = $this->merId;
        $data['callback'] = $this->returnUrl;
        $data['money'] = (string)$this->money;
        $data['notify'] = $this->callback;
        $data['order'] = $this->orderNum;
        $data['paytype'] = $this->getPayType();
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
                return 'pay.wechat.qrcode';//微信扫码
                break;
            case 2:
                return 'pay.wechat.app';//微信wap
                break;
            case 4:
                return 'zfbyssmd0';//支付宝扫码
                break;
            case 5:
                return 'zfbyswapd0';//支付宝wap
                break;

            default:
                return 'zfbyssmd0';
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
        $headers=[ "api_key:".$this->key];
        $data = $this-> post_data($this->url,$pay_data);
        if (empty($data)) $this->retMsg('接口返回信息错误！');
        //接收参数为JSON格式 转化为数组
        $data = json_decode($data,true);
        if (empty($data)) $this->retMsg('接口返回信息格式错误！');
        //判断是否下单成功
        if (empty($data['payurl']))
        {
            $msg = isset($data['msg']) ? $data['msg'] : '返回参数错误';
            $this->retMsg("下单失败：{$msg}");
        }
        //扫码支付 wap支付返回支付 实际地址
        return $data['payurl'];
    }
    public function  post_data($url,$param){
        $headers=['api-key:'.$this->key];
        $curlPost = $param;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);            //设置访问的url地址
        curl_setopt($ch,CURLOPT_HEADER,0);            //是否显示头部信息
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);           //设置超时
        curl_setopt($ch,CURLOPT_FOLLOWLOCATION,1);      //跟踪301
        curl_setopt($ch, CURLOPT_POST, 1);//post提交方式
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);        //返回结果
        curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $r = curl_exec($ch);
        curl_close($ch);
        return $r;
    }
}