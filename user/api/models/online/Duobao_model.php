<?php
/**
 * 多宝支付接口调用
 * Created by PhpStorm.
 * User: Tailand
 * Date: 2018/10/23
 * Time: 14:21
 */
defined('BASEPATH') or exit('No direct script access allowed');
//调用公共文件
include_once __DIR__.'/Publicpay_model.php';
class Duobao_model extends Publicpay_model
{
    //redis 错误记录
    protected $c_name = 'duobao';
    private $p_name = 'DUOBAO';//商品名称
    //支付接口签名参数
    private $method = 'X'; //返回签名大小写 D 大写 X 小写
    private $field = 'sign'; //签名参数名
    private $amount = [20,30,50,100,200,300,500]; //微信wap固定金额


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
        $f = $this->field;
        $m = $this->method;
        $k = $this->key;
        strtolower($data);
        $string = ToUrlParams($data).$k;
        $data['sign'] = md5($string);
        $data['hrefbackurl'] = $this->returnUrl;
        $data['attach'] = date('Y-m-d H:i:s',time());//自定发起时间
        return $data;
    }

    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {
        $data['parter'] = $this->merId;//商户号
        $data['type'] = $this->getPayType();//银行的类型
        //从手机端进入
        if(in_array($this->from_way,[1,2])){
            if($this->code==1) $data['type'] ='1004';//微信扫码手机端
            if($this->code==4) $data['type'] ='1006';//支付宝扫码手机端
        }else{
            if($this->code==1) $data['type'] ='1007';//微信扫码pc端
            if($this->code==4) $data['type'] ='992';//支付宝扫码pc端
        }
        $data['value'] = $this->money;//金额
        //微信wap通道
        if($this->code==2&& !in_array($this->money,$this->amount)){
            $this->retMsg('请支付金额20,30,50,100,200,300,500');
        }
        $data['orderid'] = $this->orderNum;//订单号 唯一
        $data['callbackurl'] = $this->callback;
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
            case 2:
                return '1100';//微信WAP
                break;
            case 5:
                return '1101';//支付宝WAP
                break;
            case 7:
                return $this->bank_type;//网关支付
                break;
            case 8:
                return '993';//qq扫码
                break;
            case 9:
                return '1002';//京东扫码
                break;
            case 12:
                return '1102';//qqwap
                break;
            case 14:
                return '1009';//一码付
                break;
            case 17:
                return '1001';//银联扫码
                break;
            case 21:
                return '1003';//百度扫码
                break;
            case 25:
                return '1962';//银联在线快捷
                break;
            case 27:
                return '1005';//网银WAP
                break;
            case 40:
                return '1010';//微信条码
                break;
            default:
                return '1101';
                break;
        }
    }
}