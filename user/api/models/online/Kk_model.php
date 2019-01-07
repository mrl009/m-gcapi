<?php
/**
 * kk支付接口调用
 * User: lqh
 * Date: 2018/07/17
 * Time: 10:51
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';

class Kk_model extends Publicpay_model
{
    protected $c_name = 'kk';
    private $p_name = 'KK';//商品名称
    //支付接口签名参数 
    private $method = 'D'; //返回签名大小写 D 大写 X 小写
    private $key_string = '&key='; //参与签名组成
    private $field = 'signData'; //签名参数名

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
        //扫码支付返回支付二维码 其他直接跳转支付地址
        if (in_array($this->code,$this->scan_code))
        {
            return $this->buildScan($data);
        } else if(in_array($this->code,[5,34,37])){
            return $this-> buildWap($data);
        } else{
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
        $f = $this->field;
        $m = $this->method;
        $k = $this->key_string . $this->key;
        $data = get_pay_sign($data,$k,$f,$m);
        return $data;
    }
    
    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {   
        $data['versionId'] = '1.1';
        $data['orderAmount'] = yuan_to_fen($this->money);
        $data['orderDate'] = date('YmdHis');
        $data['currency'] = 'RMB';
        $data['transType'] = '008';
        $data['asynNotifyUrl'] = $this->callback;
        $data['synNotifyUrl'] = $this->returnUrl;
        $data['signType'] = 'MD5';
        $data['merId'] = $this->merId;//商户号
        $data['prdOrdNo'] = $this->orderNum;
        //网银支付参数
        if (7 == $this->code)
        {
            $data['tranChannel'] = $this->bank_type;
        }
        $data['payMode'] = $this->getPayType();
        $data['receivableType'] = 'D00';
        $data['prdAmt'] = yuan_to_fen($this->money);
        $data['prdName'] = $this->p_name;
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
                return '00022';//微信扫码
                break;   
            case 2:
                return '00016';//微信WAP
                break;
            case 4:
                return '00021';//支付宝扫码
                break;
            case 5:
                return '00028';//支付宝WAP
                break; 
            case 7:
                return '00020';//网银支付
                break;
            case 8:
                return '00024';//QQ扫码
                break;
            case 25:
                return '00019';//快捷
                break;
            case 34:
                return '00016';//微信h5wap
                break;
            case 37:
                return '00028';//支付宝h5wap
                break;
            default:
                return '00021';//微信扫码
                break;
        }
    }

    /**
     * 获取支付网关地址 部分接口地址不唯一
     * @param array $pay 支付参数
     * @return array
     */
    protected function getPayUrl($pay)
    {
        //设置支付域名
        $pay_url = '';
        if (!empty($pay['pay_url']))
        {
            $pay_url = trim($pay['pay_url']);
        }
        if (in_array($this->code,$this->scan_code))
        {
            $pay_url .= 'ScanPayApply.do';
        } elseif (in_array($this->code,[5,34,37])){
            $pay_url .= 'PayUnApply.do';
        }else {
            $pay_url .= 'PayApply.do';
        }
        return $pay_url;
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
        if(in_array($this->code,[5,34,37])){
            if(empty($data['htmlText'])){
                $msg = isset($data['retMsg']) ? $data['retMsg'] : '返回参数错误';
                $this->retMsg("下单失败：{$msg}");
            }
            $pattern="/href=\'([^(\}>)]+)\'/";
            preg_match($pattern,$data['htmlText'],$match);
            return $match[1];
        }else{
            if (empty($data['qrcode']))
            {
                $msg = isset($data['retMsg']) ? $data['retMsg'] : '返回参数错误';
                $this->retMsg("下单失败：{$msg}");
            }
            //扫码支付返回支付二维码连接地址
            return $data['qrcode'];
        }

    }
}
