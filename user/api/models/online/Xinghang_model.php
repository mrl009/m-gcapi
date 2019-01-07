<?php
/**
 * 星航支付接口调用 修改版
 * User: lqh
 * Date: 2018/07/05
 * Time: 15:05 
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';

class Xinghang_model extends Publicpay_model
{
    protected $c_name = 'xinghang';
    private $p_name = 'XINGHANG';//商品名称

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
            return $this->buildForm($data,'get');
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
        $string = ToUrlParams($data) . $this->key;
        $data['attach'] = $this->p_name;
        $data['isshow'] = 0;
        $data['sign'] = md5($string);
        return $data;
    }
    
    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {   
        $data['version'] = '3.0'; //接口版本号
        $data['method'] = 'XingHang.online.interface';//接口名称
        $data['partner'] = $this->merId;//商户号
        $data['banktype'] = $this->getPayType();
        $data['paymoney'] = $this->money;
        $data['ordernumber'] = $this->orderNum;
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
            case 1:
                return 'WEIXIN';//微信
                break;
            case 2:
                return 'WEIXINWAP';//微信app
                break;
            case 4:
                return 'ALIPAY';//支付宝
                break;
            case 5:
                return 'ALIPAYWAP';//支付宝app
                break;
            case 7:
                return $this->bank_type;//网银
                break;
            case 8:
                return 'QQ';//qq钱包
                break;
            case 9:
                return 'JD'; //京东钱包
                break;
            case 12 :
                return 'QQWAP';
                break;
            case 13 :
                return 'JDWAP';
                break;
            case 17 :
                return 'UNIONPAY';//财付通
                break;
            case 18 :
                return 'UNIONPAYWAP';
                break;
            case 22 :
                return 'TENPAY';//财付通
                break;
            case 23 :
                return 'TENPAYWAP';
                break;
            case 25 :
                return 'SHORTCUT';
                break;
            case 40 :
                return 'WEIXINCODE';//微信条码
                break;
            default :
                return 'WEIXIN';
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
        $pay_data = http_build_query($pay_data);
        $data = post_pay_data($this->url,$pay_data);
        if (empty($data)) $this->retMsg('接口返回信息错误！');
        //接收参数为JSON格式 转化为数组
        $data = json_decode($data,true);
        if (empty($data)) $this->retMsg('接口返回信息格式错误！');
        //判断是否下单成功
        if (empty($data['qrurl']))
        {
            $msg = isset($data['message']) ? $data['message'] : '返回参数错误';
            $this->retMsg("下单失败：{$msg}");
        }
        //返回二维码连接地址
        return $data['qrurl'];
    }
}