<?php
/**
 * 天付支付接口调用
 * User: lqh
 * Date: 2018/07/30
 * Time: 10:20
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';

class Tianfu_model extends Publicpay_model
{
    protected $c_name = 'tianfu';
    private $p_name = 'TIANFU';//商品名称
    //支付接口签名参数 
    private $ks = '&key='; //连接秘钥字符
    private $method = 'D';
    private $field = 'sign'; //签名参数名

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
        $k = $this->ks . $this->key;
        $data = get_pay_sign($data,$k,$f,$m);
        return $data;
    }
    
    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {
        $data['src_code'] = $this->s_num;//商户唯一标识号
        $data['out_trade_no'] = $this->orderNum;
        $data['total_fee'] = yuan_to_fen($this->money);//单位分
        $data['time_start'] = date('YmdHis');
        $data['goods_name'] = $this->p_name;
        $data['trade_type'] = $this->getPayType();
        $data['finish_url'] = $this->returnUrl;
        $data['mchid'] = $this->merId;//商户号
        //网银支付特殊参数
        if (7 == $this->code)
        {
            $temp = array(
                'bankName' => $this->bank_type,
                'cardType' => '借记卡'
            );
            $data['extend'] = json_encode($temp,JSON_UNESCAPED_UNICODE);
        }
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
                return '50104';//微信扫码
                break;   
            case 2:
                return '50107';//微信WAP
                break;
            case 4:
                return '60104';//支付宝扫码
                break; 
            case 5:
                return '60107';//支付宝WAP
                break; 
            case 7:
                return '80103';//网关支付
                break;
            case 8:
                return '40104';//QQ钱包扫码
                break;
            case 9:
                return '92104';//京东扫码
                break; 
            case 10:
                return '93104';//百度扫码
                break; 
            case 12:
                return '40107';//QQ钱包WAP
                break;  
            case 17:
                return '30104';//银联钱包
                break;
            case 18:
                return '30107';//银联WAP
                break;
            case 25:
                return '70103';//快捷
                break;
            case 26:
                return '80101';//收银台
                break;
            case 33:
                return '50204';//微信WAP扫码
                break;
            case 38:
                return '94104';//苏宁扫码
                break;
            case 41:
                return '60101';//支付宝反扫
                break;
            default:
                return '60104';//支付宝
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
        $data = string_decoding($data);
        if (empty($data)) $this->retMsg('接口返回信息格式错误！');
        //判断是否下单成功
        if (empty($data['data']['pay_params']))
        {
            $msg = isset($data['respmsg']) ? $data['respmsg'] : '返回参数错误';
            $this->retMsg("下单失败：{$msg}");
        }
        return $data['data']['pay_params'];
    }
}
