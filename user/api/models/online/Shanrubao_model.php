<?php
defined('BASEPATH') or exit('No direct script access allowed');

include_once __DIR__.'/Publicpay_model.php';

class Shanrubao_model extends Publicpay_model
{
    protected $c_name = 'shanrubao';
    private $p_name = 'SHANRUBAO';//商品名称
    private $sign = 'key';

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
       return $this->buildForm($data);
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
        $data = $this->Versign($data);
        unset($data['token']);
        return $data;
    }

    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {
        $data['uid'] = $this->merId;
        //$data['price'] = sprintf("%.2f",$this->money);
        $data['price'] = $this->money;
        $data['istype'] = $this->getPayType();
        $data['notify_url'] = $this->callback;
        $data['return_url'] = $this->returnUrl;
        $data['orderid'] = $this->orderNum;
        $data['orderuid'] = $this->user['id'];
        $data['goodsname'] = $this->p_name;
        $data['version'] = '2';
        $data['token'] = $this->key;
        return $data;
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
            case 2:
                return '2';//微信扫码、WAP
                break;
            case 4:
            case 5:
                return '1';//支付宝扫码、WAP
                break;
            case 8:
            case 12:
                return '3';//QQ扫码、WAP
                break;
            default:
                return '2';//微信扫码
                break;
        }
    }

    /**签名验证
     * @param $data
     */
   public function Versign($data)
   {
       ksort($data);
       //把数组参数以key=value形式拼接
       $string = ToUrlParams($data);
       //转换成小写md5加密
       $data[$this->sign] = strtolower(md5($string));
       return $data;
   }


    /**
     * 获取支付结果
     * @param $data 支付参数
     * @return return 二维码内容
     */
    protected function getPayResult($pay_data)
    {
        //传递参数为STRING格式 将数组转化成STRING格式
        //$pay_data = http_build_query($pay_data);
        $data = post_pay_data($this->url,$pay_data);
        $data = json_decode($data,true);
        if (empty($data)) $this->retMsg('接口返回信息错误！');
        //判断下单是否成功
        if (empty($data) || empty($data['code'])|| 1<> $data['code'])
        {
            $this->retMsg('错误信息: 接口服务错误！');
        }
        if (!isset($data['code']) || (1 <> $data['code'])
            ||empty($data['data']['qrcode']))
        {
            if (!empty($data['code'])) $msg = $data['code'];
            if (!empty($data['msg'])) $msg = $data['msg'];
            $msg = isset($msg) ? $msg : '返回参数错误';
            $this->retMsg("下单失败：{$msg}");
        }
        $string = explode(':',$data['data']['qrcode']);
        $pay_url = strtolower($string[0]).':'.$string[1];//wap支付地址或者二维码地址
        return $pay_url;

    }
}