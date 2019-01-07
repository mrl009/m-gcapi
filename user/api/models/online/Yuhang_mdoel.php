<?php

/**(凯航)宇航支付接口调用
 * Created by PhpStorm.
 * User: Tailand
 * Date: 2018/12/27
 * Time: 20:45
 */
defined('BASEPATH') or exit('No direct script access allowed');
//调用共用文件
include_once __DIR__.'/Publicpay_model.php';
class Yuhang_mdoel extends Publicpay_model
{
    protected $c_name = 'yuhang';
    private $p_name = 'YUHANG';//商品名称
    private $key_string = '&key=';

    public function __construct(){
        parent::__construct();
    }

    protected function returnApiData($data){
        return $this->buildForm($data,'get');

    }
    /**
     * 构造支付参数+sign值
     * @return array
     */
    protected function getPayData(){
        //构造基本参数
        $data = $this->getBaseData();
        //构造签名参数
        ksort($data);
        //转化为json字符串
        $json = json_encode($data,JSON_UNESCAPED_SLASHES);
        //组装提交数据
        $data['req']  = base64_encode($json);
        $data['sign'] = md5($json.$this->key);
        return $data;
    }

    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData(){
        $data['action']  =$this->getPayType() ;//支付模式
        $data['merid']   = $this->merId;//商户号
        $data['orderid'] = $this->orderNum;//订单号
        $data['txnamt '] = (string)yuan_to_fen($this->money);//订单金额
        $data['backurl'] = $this->callback;//回调地址
        if($this->code==2){
            $data['ip'] = get_ip();
        }
        $data['fronturl']= $this->returnUrl;//可选

        return $data;
    }



    private function getPayType(){
        switch ($this->code){
            case 1:
                return 'WxSao';//微信扫码https://api.hbasechina.org/api/mpgateway
                break;
            case 2:
                return 'WxH5';//微信h5
                break;
            case 4:
                return 'AliCode';//支付宝扫码
                break;
            case 5:
                return 'AliWap';//支付宝H5
                break;
            case 8:
                return 'QQSao';//QQ扫码
                break;
            case 17:
                return 'USao';//银联扫码
                break;
            case 33:
                return 'WxJsApi';//微信公众号
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
        $data = post_pay_data($this->url,$pay_data);
        if (empty($data)) $this->retMsg('接口无信息返回！');
        //接收参数为JSON格式 转化为数组
        $data = json_decode($data,true);
        //判断是否下单成功

        if ($data['respcode'] <> '00'){
            $msg = isset($data['result_msg']) ? $data['result_msg'] : '返回参数错误';
            $this->retMsg("下单失败：{$msg}");
        }
        if(in_array($this->code,$this->scan_code)){
            $pay_url = $data['formaction'];
        }else if(in_array($this->code,$this->wap_code)){
            //$pay_url = $data['formaction'];
        }
        return $pay_url;
    }
}