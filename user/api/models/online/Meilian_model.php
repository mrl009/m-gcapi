<?php
defined('BASEPATH') or exit('No direct script access allowed');
/**
 * Created by PhpStorm.
 * User: daxiniu
 * Date: 2018.11.23
 * Time: 16:05
 */
include_once __DIR__.'/Publicpay_model.php';

class Meilian_model extends Publicpay_model
{
    protected $c_name = 'meilian';
    private $p_name = 'MEILIAN';//商品名称
    private $ks = '&key=';
    private $field = 'sign'; //签名参数名

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
        $data = get_pay_sign($data,$this->ks.$this->key,$this->field,'X');
        return $data;
    }

    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData(){
        $data['channel'] = $this->merId;//商户号
        $data['callback'] = $this->callback;//回调地址
        $data['orderid'] = $this->orderNum;//订单号
        $data['txnAmt'] = $this->money;//订单金额
        $data['paytype'] =$this->getPayType() ;
        $data['ip'] =$this->user['loginip'];

        return $data;
    }


    private function getPayType(){
        switch ($this->code){
            case 1:
                return 'weixin';//微信扫码
                break;
            case 4:
            case 5:
                return 'alipay';//支付宝扫码
                break;
            default:
                return 'weixin';
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
        //var_dump($data);die;
        if (empty($data)) $this->retMsg('接口无信息返回！');
        //接收参数为JSON格式 转化为数组
        $data = json_decode($data,true);
        //判断是否下单成功
        if (empty($data['codeUrl']) || $data['resultCode'] <> '0000'){
            $msg = isset($data['resultMsg']) ? $data['resultMsg'] : '返回参数错误';
            $this->retMsg("下单失败：{$msg}");
        }
        $pay_url = $data['codeUrl'];
        return $pay_url;
    }
}