<?php
/**
 * 全球付支付接口调用
 * User: lqh
 * Date: 2018/05/15
 * Time: 15:25
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';

class Quanqiufu_model extends Publicpay_model
{
    protected $c_name = 'quanqiufu';
    private $p_name = 'QUANQIUFU';//商品名称
    //支付接口签名参数 
    private $method = 'D'; //返回签名大小写 D 大写 X 小写
    private $key_string = '&key='; //参与签名组成
    private $field = 'sign'; //签名参数名*/

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
        $f = $this->field;
        $m = $this->method;
        $k = $this->key_string . $this->key;
        $data = get_pay_sign($data,$k,$f,$m);
        $data['callback_url'] = $this->callback;
        $data['client_ip'] = get_ip();
        return $data;
    }
    
    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {
        $data['subject'] = $this->p_name;
        $data['total_fee'] = yuan_to_fen($this->money);
        $data['pay_type'] = $this->getPayType();
        $data['mchNo'] = $this->merId;
        $data['body'] = $this->p_name;
        $data['version'] = '2.0';
        $data['mchorderid'] = $this->orderNum;
        $data['showurl'] = $this->returnUrl;
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
                return 'pay_weixin_scan';//微信扫码
                break;
            case 2:
                return 'pay_weixin_wap';//微信WAP
                break;   
            case 4:
                return 'pay_alipay_scan';//支付宝扫码
                break; 
            case 5:
                return 'pay_alipay_wap';//支付宝WAP
                break; 
            case 7:
                return 'pay_note_wap';//网关支付
                break;
            case 8:
                return 'pay_qqpay_scan';//QQ钱包扫码
                break;
            case 12:
                return 'pay_qqpay_wap';//QQ钱包WAP
                break;
            case 17:
                return 'pay_ylpay_scan';//银联钱包扫码
                break;            
            default:
                return 'pay_weixin_scan';//微信扫码
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
        //传递参数为JSON格式 将数组转化成JSON格式
        $pay_data = json_encode($pay_data);
        //对方.net语言无法接受“\/\/”这种转义字符，需要替换成正常的数据
        $pay_data = str_replace('\/\/','//',$pay_data);
        $pay_data = str_replace('\/','/',$pay_data);
        $data = post_pay_data($this->url,$pay_data);
        if (empty($data)) $this->retMsg('接口返回信息错误！');
        //接收参数为JSON格式 转化为数组
        $data = json_decode($data,true);
        if (empty($data)) $this->retMsg('接口返回信息格式错误！');
        //判断是否下单成功
        if (!isset($data['errorcode']) || ('0' <> $data['errorcode']) 
            || (empty($data['code_url'])))
        {
            $msg = isset($data['errormsg']) ? $data['errormsg'] : '返回参数错误';
            $this->retMsg("下单失败：{$msg}");
        }
        //扫码支付 wap支付返回支付 实际地址
        return $data['code_img_url'];
    }
}
