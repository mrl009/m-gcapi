<?php
/**
 * 广州银商支付接口调用
 * User: lqh
 * Date: 2018/08/25
 * Time: 10:20
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';

class Yinshang_model extends Publicpay_model
{
    protected $c_name = 'yinshang';
    private $p_name = 'YINSHANG';//商品名称
    //支付接口签名参数 
    private $key_string = '&'; //参与签名组成

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
        //第三方银联支付扫码 返回json数据 其他跳转收银台
        if (17 == $this->code)
        {
            return $this->buildScan($data);
        } else {
            return $this->buildForm($data);
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
        $k = $this->key_string . $this->key;
        $string = ToUrlParams($data) . $k;
        $data['sign'] = md5($string);
        if (7 == $this->code) $data['bankId'] = $this->bank_type;
        return $data;
    }
    
    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {
        $data['shid'] = $this->merId;
        $data['bb'] = '1.0';
        $data['zftd'] = $this->getPayType();
        $data['ddh'] = $this->orderNum;
        $data['je'] = number_format($this->money,2);//金额必须是小数
        $data['ddmc'] = $this->p_name;
        $data['ddbz'] = $this->p_name;
        $data['ybtz'] = $this->callback;
        $data['tbtz'] = $this->returnUrl;
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
                return 'wxapi';//微信
                break;
            case 4:
            case 5:
                return 'alapi';//支付宝
                break;
            case 7:
                return 'shwg';//网银支付
                break;
            case 17:
                return 'yishi';//银联扫码
                break;
            case 25:
                return 'shkj';//银联钱包WAP
                break;
            default:
                return 'wxapi';//支付宝
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
        //接收参数为JSON格式(非纯json数据) 转化为数组
        $data = strstr($data,'{');
        $data = json_decode($data,true);
        if (empty($data)) $this->retMsg('接口返回信息格式错误！');
        //判断是否下单成功
        if (empty($data['qrCodeURL']))
        {
            $msg = isset($data['msg']) ? $data['msg'] : '返回参数错误';
            $this->retMsg("下单失败：{$msg}");
        }
        //扫码支付 wap支付返回支付 实际地址
        return $data['qrCodeURL'];
    }
}
