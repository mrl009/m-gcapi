<?php
/**
 * 捷付通支付接口调用
 * Created by PhpStorm.
 * User: 57207
 * Date: 2018/9/5
 * Time: 19:42
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';

class Jiefutong_model extends Publicpay_model
{
    protected $c_name = 'jiefutong';
    private $p_name = 'JIEFUTONG';//商品名称
    //支付接口签名参数
    private $field = 'fxsign'; //签名参数名

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
        }  elseif (in_array($this->code,$this->scan_code)) {
            return $this->buidImage($data);
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
        $data= $this->createsign($data);
        $data['fxaction'] ='orderpay';
        $data['fxdesc'] ='Jiefu';
        $data['fxbackurl'] = $this->returnUrl;
        $data['fxpay'] = $this->getPayType();
        $data['fxip'] = get_ip();
        if($this->code==7) $data['fxbankcode'] = $this->bank_type;
        return $data;
    }

    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {
        $data['fxid'] = $this->merId;//商户号
        $data['fxddh'] = $this->orderNum;
        $data['fxfee'] = $this->money;
        $data['fxnotifyurl'] = $this->callback;
        return $data;
    }
    /**
     * 获取支付网关地址 部分接口地址不唯一
     * @param array $pay 支付参数
     * @return array
     */
    protected function getPayUrl($pay)
    {
        //支付网关根据每个站点配置 取私库shopurl
        $url = '';
        if (!empty($pay['shopurl']))
        {
            $url = trim($pay['shopurl']);
        }
        return $url;
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
            case 4:
                return 'alipay';//支付宝扫码
                break;
            case 5:
                return 'alipay';//支付宝WAP
                break;
            default:
                return 'alipay';//支付宝扫码
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
        $data = pay_curl($this->url,$pay_data,'get');
        if (empty($data)) $this->retMsg('接口返回信息错误！');
        //接收参数为JSON格式 转化为数组
        $data = json_decode($data,true);
        if (empty($data)) $this->retMsg('接口返回信息格式错误！');
        //判断是否下单成功
        if ($data['status']<>1)
        {
            $msg = isset($data['error']) ? $data['error'] : '返回参数错误';
            $this->retMsg("下单失败：{$msg}");
        }
        //扫码支付返回二维码
        if (in_array($this->code,$this->scan_code))
        {
            //扫码支付返回支付二维码连接地址
            return $data['payimg'];
        } else {
            return $data['payurl'];
        }

    }
    /**
     * sign待签名数据
     */
    protected function  createsign($data)
    {
        $buff = "";
        foreach ($data as $k => $v)
        {
            if($k <>$this->field && $v <> ""
                && !is_array($v)&& $v <>null ){
                $buff .= $v;
            }
        }
        $data [$this->field] = md5($buff.$this->key);
        return $data;
    }
}