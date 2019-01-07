<?php
/**
 * 通宝(付)支付接口调用
 * User: lqh
 * Date: 2018/07/05
 * Time: 15:05 
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';

class Tongbaofu_model extends Publicpay_model
{
    protected $c_name = 'tongbaofu';
    protected $p_name = 'TONGBAO';
    //支付接口签名参数 
    private $ks = '&key='; //参与签名组成

    public function __construct()
    {
        parent::__construct();
    }
    
    /**
     * 构造基本参数
     */
    protected function getPayData()
    {
        $data = $this->getDataBase();
        //构造签名参数
        ksort($data);
        $k = $this->ks . $this->key;
        $string = ToUrlParams($data) . $k;
        $data['sign'] = md5($string);
        return $data;
    }

    /*
     * 构造签名的参数
     */
    protected function getDataBase()
    {
        $data['pay_notifyurl'] = $this->callback;
        $data['pay_backurl'] = $this->returnUrl;
        $data['pay_biztype']= $this->getPayType();//充值方式
        $data['pay_merberid'] = $this->merId;
        $data['pay_orderno'] = $this->orderNum;
        $data['pay_amount'] = $this->money;
        $data['pay_ordertime'] = date('Y-m-d H:i:s');
        if (7 == $this->code) $data['pay_bankcode'] = $this->bank_type;
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
            case 2:
                return '2';//微信
                break;
            case 4:
            case 5:
                return '3';//支付宝
                break;
            case 7:
                return '1';//网银
                break;
            case 8:
            case 12:
                return '5';//QQ钱包
                break;
            case 9:
            case 13:
                return '6';//QQ钱包
                break;
            case 17:
            case 18:
                return '7';//银联
                break;
            default:
                return '3';
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
        if (empty($data['pay_qrcode']))
        {
            $msg = "返回信息错误";
            if (isset($data['result_msg'])) $msg = $data['result_msg'];
            $this->retMsg("下单失败：{$msg}");
        }
        //返回支付连接或二维码地址
        return $data['pay_qrcode'];
    }
}