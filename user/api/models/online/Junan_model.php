<?php

/**
 * 君安支付接口调用
 * Created by PhpStorm.
 * User: Tailand
 * Date: 2018/12/28
 * Time: 16:41
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';
class Junan_model extends Publicpay_model
{
    protected $c_name = 'junan';
    private $p_name = 'JUNAN';//商品名称
    //支付接口签名参数
    private $method = 'D'; //返回签名大小写 D 大写 X 小写
    private $key_string = '&key='; //参与签名组成
    private $field = 'pay_md5sign'; //签名参数名

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
        $k = $this->key_string . $this->key;
        ksort($data);
        //把数组参数以key=value形式拼接最后加上$key_string值
        $sign_string = $this->Params($data).$k;
        $data[$this->field] =strtoupper(md5($sign_string));
        if($this->code==7)$data['pay_card_no'] = $this->bank_type;
        $data['pay_requestIp'] = get_ip();
        $data['tongdao'] = 'XTFAH5D0';
        $data['return_type'] = 1;
        return $data;
    }

    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {
        $data['pay_memberid'] = $this->merId;
        $data['pay_orderid'] = $this->orderNum;
        $data['pay_bankcode'] = $this->getPayType();
        $data['pay_amount'] = $this->money;
        $data['pay_applydate'] = date("Y-m-d H:i:s");
        $data['pay_notifyurl'] = $this->callback;
        $data['pay_callbackurl'] = $this->returnUrl;
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
                return 'WXZF';//微信扫码
                break;
            case 4:
                return 'ALIPAY';//支付宝扫码
                break;
            case 5:
                return 'ALIPAY';//支付宝WAP
                break;
            case 7:
                return 'WY';//网银支付
                break;
            case 25:
                return 'KJ';//快捷支付
                break;
            default:
                return 'ALIPAY';//支付宝扫码
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
        if (empty($data['pay_url']) || $data['code'] <> 1){
            $msg = isset($data['result_msg']) ? $data['result_msg'] : '返回参数错误';
            $this->retMsg("下单失败：{$msg}");
        }
        $pay_url = $data['pay_url'];
        return $pay_url;
    }
    /**
     * 将数组的键与值用符号隔开
     * @param $data array 待签名的数据
     * @return  $str string
     */
    protected function Params($data)
    {
        $buff = "";
        foreach ($data as $k => $v)
        {
            if($k != $this->field && $v != "" && !is_array($v)){
                $buff .= $k . "=>" . $v . "&";
            }
        }
        $buff = trim($buff, "&");
        return $buff;
    }
}