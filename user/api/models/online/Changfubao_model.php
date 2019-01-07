<?php
defined('BASEPATH') or exit('No direct script access allowed');
/**
 * Created by PhpStorm.
 * User: 57207
 * Date: 2018/7/16
 * Time: 9:59
 */
include_once __DIR__.'/Publicpay_model.php';

class Changfubao_model extends Publicpay_model
{
    protected $c_name = 'changfubao';
    private $p_name = 'CHANGFUBAO';//商品名称
    private $ks = '&key=';

    public function __construct(){
        parent::__construct();
    }

    /**
     * 构造支付参数+sign值
     * @return array
     */
    protected function getPayData(){
        //构造基本参数
        $data = $this->getBaseData();
        //构造签名参数
        $sign = get_pay_sign($data,$this->ks.$this->key,'sign','X');
        return $sign;
    }

    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData(){
        $data['mch_id'] = $this->merId;//商户号
        $data['body'] = $this->p_name;//商品名称
        $data['trade_type'] = $this->getPayType();//交易类型
        $data['total_fee'] =  yuan_to_fen($this->money);//订单金额,单位为分
        $data['out_trade_no'] = $this->orderNum;//订单号
        $data['notify_url'] = $this->callback;//回调地址
        return $data;
    }

    private function getPayType(){
        switch ($this->code){
            case 1:
                return 'WX.NATIVE';//微信扫码,无法使用
                break;
            case 4:
                return 'ALIPAY.NATIVE';//支付宝扫码
                break;
            case 8:
                return 'QQ.NATIVE';//QQ扫码,无法使用
                break;
            case 2:
                return 'WX.WEB';//微信H5,无法使用
                break;
            case 5:
                return 'ALIPAY.FTF';//支付宝H5
                break;
            case 12:
                return 'QQ.WEB';//QQ H5,无法使用
                break;
            default:
                return 'WECHAT';
                break;
        }

    }
    /**
     * 获取支付结果
     * @param $data 支付参数
     * @return return 二维码内容
     */
    protected function getPayResult($pay_data){
        $pay_data = http_build_query($pay_data);
        $url = $this->url.'/dopay/unifiedorder';
        $data = post_pay_data($url,$pay_data);
        if (empty($data)) $this->retMsg('接口无信息返回！');
        //接收参数为JSON格式的对象 转化为数组
        $data = json_decode($data,true);
        //判断是否下单成功
        if (empty($data['params_info']) || $data['return_code'] <> 0){
            $msg = isset($data['return_msg']) ? $data['return_msg'] : '返回参数错误';
            $this->retMsg("下单失败：{$msg}");
        }
        $pay_url = $data['params_info']['pay_info'];
        return $pay_url;
    }
}