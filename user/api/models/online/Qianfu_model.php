<?php
/**
 * 乾富支付接口调用
 * User: lqh
 * Date: 2018/06/27
 * Time: 14:12
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';

class Qianfu_model extends Publicpay_model
{
    protected $c_name = 'qianfu';
    private $p_name = 'QIANFU';//商品名称
    private $f_aount=[30,50,100];//微信wap支付金额固定

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
            return $this->buildWap($data);
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
        $sign_data = array_values($data);
        $string = implode('',$sign_data) . md5($this->key);
        $data['asyn_url'] = $this->callback;
        $data['jump_url'] = $this->returnUrl;
        //网银和快捷参数
        if ((7 == $this->code) || (25 == $this->code)) 
        {
            $data['bank_code'] = $this->bank_type;
        }
        $data['ip_add'] = get_ip();
        $data['sign_info'] = md5($string);
        return $data;
    }
    
    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {
        $data['order_sn'] = $this->orderNum;
        if($this->code==2)
        {
            if(in_array($this->money,$this->f_aount))
            {
                $this->money = $data['totle_amount'] = yuan_to_fen($this->money);
            }else{
                $this->retMsg('请充值金额30、50、100！');
            }
        }
        $data['totle_amount'] = yuan_to_fen($this->money); 
        $data['pay_type'] = $this->getPayType();
        $data['this_date'] = time();
        $data['mch_number'] = $this->merId;
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
                return 'wxqrcode';//微信扫码
                break; 
            case 2:
                return 'wxhtml';//微信WAP
                break;     
            case 4:
                return 'aliqrcode';//支付宝扫码
                break; 
            case 5:
                return 'aliwap';//支付宝WAP
                break; 
            case 7:
                return 'ylggp';//网银支付
                break;
            case 8:
                return 'qqqrcode';//QQ扫码
                break;
            case 9:
                return 'jdqrcode';//京东扫码
                break;     
            case 12:
                return 'qqweb';//QQWAP
                break;
            case 13:
                return ' jdwap';//京东WAP
                break;
            case 17:
                return 'ylqrcode';//银联扫码
                break;   
            case 25:
                return 'ylpay';//快捷支付
                break;    
            default:
                return 'wxqrcode';//微信扫码
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
        $data = post_pay_data($this->url,$pay_data);
        if (empty($data)) $this->retMsg('接口返回信息错误！');
        //接收参数为JSON格式 转化为数组
        $data = json_decode($data,true);
        if (empty($data)) $this->retMsg('接口返回信息格式错误！');
        //判断是否下单成功
        if (empty($data['data']['img']))
        {
            $msg = isset($data['msg']) ? $data['msg'] : '返回参数错误';
            $this->retMsg("下单失败：{$msg}");
        }
        //扫码支付 wap支付返回支付 实际地址
        return $data['data']['img'];
    }
}
