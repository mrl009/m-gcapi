<?php
/**
 * 乐淘支付接口调用
 * User: lqh
 * Date: 2018/05/11
 * Time: 09:28
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';

class Letao_model extends Publicpay_model
{
    protected $c_name = 'letao';
    private $p_name = 'LETAO';//商品名称
    //支付接口签名参数 
    private $method = 'X'; //返回签名大小写 D 大写 X 小写
    private $field = 'Signature'; //签名参数名*/

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
        $f = $this->field;
        $m = $this->method;
        $k = $this->key;
        $data = get_pay_sign($data,$k,$f,$m);
        $data['SignMethod'] = 'MD5';
        return $data;
    }
    
    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {
        
        $data['Version'] = '1.0';
        $data['TxCode'] = $this->getPayCode();
        $data['MerNo'] = $this->merId;
        $data['ProductId'] = $this->getPayType();
        $data['TxSN'] = $this->orderNum;
        $data['Amount'] = yuan_to_fen($this->money);
        $data['PdtName'] = $this->p_name;
        $data['NotifyUrl'] = $this->callback;
        //网关支付 特殊参数
        if (7 == $this->code)
        {
            $data['BankId'] = $this->bank_type;
        }
        $data['ReqTime'] = date('YmdHis');
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
                return '0601';//微信扫码
                break;
            case 2:
                return '0621';//微信WAP
                break;   
            case 4:
                return '0602';//支付宝扫码
                break; 
            case 5:
                return '0622';//支付宝WAP
                break; 
            case 7:
                return '0611';//网关支付
                break;
            case 8:
                return '0604';//QQ钱包扫码
                break;
            case 17:
                return '0603';//银联钱包扫码
                break;            
            default:
                return '0601';//微信扫码
                break;
        }
    }

    /**
     * 根据code值获取交易编码
     * @param string code 
     * @return string 交易编码 参数
     */
    private function getPayCode()
    {
        $TxCode = '';
        //扫码支付
        if (in_array($this->code, [1,4,8,17]))
        {
            $TxCode = '210110';
        //WAP或H5支付
        } elseif(in_array($this->code, [2,5])) {
            $TxCode = '210105';
        } elseif(7 == $this->code) {
            $TxCode = '210111';
        } 
        return $TxCode;
    }
    

    /**
     * 获取支付结果
     * @param $data 支付参数
     * @return return 二维码内容
     */
    protected function getPayResult($pay_data)
    {
        //传递参数为STRING格式 将数组转化成STRING格式
        $pay_data = http_build_query($pay_data);
        $data = post_pay_data($this->url,$pay_data);
        if (empty($data)) $this->retMsg('接口返回信息错误！');
        //接受参数为STRING格式 转化为数组
        parse_str(urldecode($data),$data);
        if (empty($data)) $this->retMsg('接口返回信息格式错误！');
        //判断是否下单成功
        if (!isset($data['Status']) || (1 <> $data['Status']) 
            || (empty($data['CodeUrl']) && empty($data['PayUrl'])))
        {
            $msg = isset($data['RspMsg']) ? $data['RspMsg'] : '返回参数错误';
            $this->retMsg("下单失败：{$msg}");
        }
        //扫码支付返回 二维码地址 wap支付返回支付地址
        if (!empty($data['CodeUrl']))
        {
            $pay_url = $data['CodeUrl']; //二维码地址
        } else { 
            $pay_url = $data['PayUrl']; //wap支付地址
        }
        return base64_decode($pay_url); //地址需要base64解码
    }
}
