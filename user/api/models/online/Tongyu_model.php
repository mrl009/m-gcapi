<?php
/**
 * 通宇支付接口调用
 * User: lqh
 * Date: 2018/07/05
 * Time: 15:05 
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';

class Tongyu_model extends Publicpay_model
{
    protected $c_name = 'tongyu';
    private $p_name = 'TONGYU';//商品名称

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
        ksort($data);
        $string = json_encode($data);
        $string = str_replace('\/','/',$string);
        $string = str_replace('\/\/','//',$string);
        //获取加密字符串
        $sign_string = "biz_content={$string}&key=" . $this->key;
        $sign = strtoupper(md5($sign_string));
        //构造传递参数
        $return_data['sign_type'] = 'MD5';
        $return_data['signature'] = $sign;
        $return_data['biz_content'] = $string;
        unset($data,$string,$sign);
        return $return_data;
    }
    
    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {   
        $data['version'] = '1.0'; //接口版本号
        $data['mch_id'] = $this->merId;//商户号
        $data['out_order_no'] = $this->orderNum;
        $data['pay_platform'] = $this->getPayType();
        $data['pay_type'] = $this->getType();
        $data['payment_fee'] = yuan_to_fen($this->money);
        $data['cur_type'] = 'CNY';
        $data['body'] = $this->p_name;
        $data['notify_url'] = $this->callback;
        $data['bill_create_ip'] = get_ip();
        return $data;
    }

    /**
     * 根据code值获取支付方式
     * @param string code 
     * @return string 交易类型 参数
     */
    private function getType()
    {
        if (in_array($this->code,$this->wap_code))
        {
            return 'MWEB';
        } elseif (in_array($this->code,$this->scan_code)) {
            return 'NATIVE';
        }
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
            case 2:
                return 'WXPAY';//微信
                break;
            case 4:
            case 5:
                return 'ALIPAY';//支付宝
                break;
            case 8:
            case 12:
                return 'SQPAY';//支付宝
                break;
            default :
                return 'ALIPAY';
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
        if (empty($data) || empty($data['biz_content']))
        {
            $this->retMsg('接口返回信息格式错误！');
        } 
        //判断是否下单成功
        if (empty($data['biz_content']['mweb_url']))
        {
            $msg = isset($data['ret_msg']) ? $data['ret_msg'] : '返回参数错误';
            $this->retMsg("下单失败：{$msg}");
        }
        //返回二维码连接地址
        return $data['biz_content']['mweb_url'];
    }
}