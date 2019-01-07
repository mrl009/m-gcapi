<?php
/**
 * 同银支付接口调用
 * User: lqh
 * Date: 2018/05/28
 * Time: 10:02
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';

class Tongyin_model extends Publicpay_model
{
    protected $c_name = 'tongyin';
    private $p_name = 'TONGYIN';//商品名称

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
        if (in_array($this->code,$this->scan_code))
        {
            return $this->buildScan($data);
        } else {
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
        $data = $this->getRequestData();
        $pay_data = $this->getBaseData();
        //构造业务参数(json格式数据)
        $string = json_encode($pay_data,320);
        $data['businessData'] = $string;
        //构造签名参数
        ksort($data);
        $string = ToUrlParams($data) . $this->key;
        $data['signData'] = strtoupper(md5($string));
        return $data;
    }

    /**
     * 构造支付基本参数(请求数据)
     * @return array
     */
    private function getRequestData()
    {
        $data['requestId'] = $this->orderNum;
        $data['orgId'] = $this->s_num;
        $data['timestamp'] = date('YmdHis');
        if (7 == $this->code)
        {
            $data['productId'] = '0500';
        } else {
            $data['productId'] = '0100';
        }
        $data['dataSignType'] = '0';
        return $data;
    }
    
    /**
     * 构造支付基本参数(业务数据)
     * @return array
     */
    private function getBaseData()
    {
        $data['merno'] = $this->merId;
        $data['bus_no'] = $this->getPayType();
        $data['amount'] = yuan_to_fen($this->money);
        $data['goods_info'] = $this->p_name;
        $data['order_id'] = $this->orderNum;
        if (7 == $this->code)
        {
            $bank = explode('&',$this->bank_type);  
            $data['cardname'] = $bank[1];
            $data['bank_code'] = $bank[0];
            $data['card_type'] = 1;
            $data['channelid'] = 1;
        }
        $data['notify_url'] = $this->callback;
        $data['return_url'] = $this->returnUrl;
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
                return '0101';//微信扫码
                break;   
            case 2:
                return '0103';//微信WAP
                break;
            case 4:
                return '0201';//支付宝扫码
                break;
            case 5:
                return '0203';//支付宝WAP
                break;
            case 7:
                return '0499';//网银
                break;
            case 8:
                return '0501';//QQ扫码
                break;
            case 9:
                return '0601';//京东扫码
                break;
            case 12:
                return '0503';//QQWAP
                break;
            case 13:
                return '0603';//京东WAP
                break;    
            case 17:
                return '0701';//银联扫码
                break;
            case 25:
                return '0399';//快捷
                break;
            default:
                return '0201';//支付宝扫码
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
        $pay_data = http_build_query($pay_data);
        $data = post_pay_data($this->url,$pay_data);
        if (empty($data)) $this->retMsg('接口返回信息错误！');
        $data = json_decode($data,true);
        if (empty($data)) $this->retMsg('接口返回信息格式错误！');
        //确认下单是否成功
        if (!empty($data['result'])) 
        {
            //解析result参数 获取支付链接
            $data = json_decode($data['result'],true);
        }
        if (empty($data['url']))
        {
            $msg = '返回信息错误';
            if (isset($data['respMsg'])) $msg = $data['respMsg'];
            if (isset($data['msg'])) $msg = $data['msg'];
            $this->retMsg("下单失败：{$msg}");
        }
        return $data['url'];
    }
}
