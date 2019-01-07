<?php
/**
 * 信誉付支付接口调用
 * User: lqh
 * Date: 2018/07/22
 * Time: 17:22
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';

class Xinyufu_model extends Publicpay_model
{
    protected $c_name = 'xinyufu';
    private $p_name = 'XINYUFU';
    //支付接口签名参数 
    private $key_string = '|'; //参与签名组成
    
    public function __construct()
    {
        parent::__construct();
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
        $string = implode('|',array_values($data));
        $string .= $this->key_string . $this->key;
        $data['sign'] = strtoupper(md5($string));
        return $data;
    }

    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {
        $data['V'] = 'V4.0';
        $data['UserNo'] = $this->merId;//商户号
        $data['ordNo'] = $this->orderNum;// 订单号
        $data['ordTime'] = date('YmdHis');
        $data['amount'] = yuan_to_fen($this->money);//金额 单位分
        $data['pid'] = $this->getPayType();
        $data['notifyUrl'] = $this->callback;//异步返回地址
        $data['frontUrl'] = $this->returnUrl;
        $data['ip'] = get_ip();//终端IP
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
                return 'wxzf';//微信扫码
                break;
            case 2:
                return 'wxh5zf';//微信WAP
                break;
            case 4:
                return 'apzf';//支付宝扫码
                break;
            case 7:
                return 'cxkzf';//网关支付
                break;
            case 8:
                return 'qqzf';//QQ扫码
                break; 
            case 9:
                return 'jdzf';//京东扫码
                break;    
            case 17:
                return 'ylzf';//银联扫码
                break;
            default:
                return 'wxzf';//微信扫码
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
        //接收参数为JSON格式 转化为数组
        $data = json_decode($data,true);
        if (empty($data)) $this->retMsg('接口返回信息格式错误！');
        //判断是否下单成功
        if (empty($data['Payurl']))
        {
            $msg = "返回参数错误";
            if (isset($data['resMsg'])) $msg = $data['resMsg'];
            $this->retMsg("下单失败：{$msg}");
        }
        //返回支付二维码连接地址或WAP支付地址
        return $data['Payurl'];
    }    
}
