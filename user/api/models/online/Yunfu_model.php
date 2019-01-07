<?php
defined('BASEPATH')or die('No direct script access allowed');
/**
 * Created by PhpStorm.
 * User: 57207
 * Date: 2018/8/14
 * Time: 14:06
 */
include_once __DIR__.'/Publicpay_model.php';
class Yunfu_model extends Publicpay_model
{
    protected $c_name = 'yunfu';
    private $p_name = 'YUNFU';//商品名称
    //支付接口签名参数
    private $method = 'D'; //返回签名大小写 D 大写 X 小写
    private $key_string = '&paySecret='; //参与签名组成
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
        $k =$this->key_string. $this->key;
        $data = get_pay_sign($data,$k,$f,$m);
        return $data;
    }

    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {
        $data['transId'] = $this->getPayType();
        $data['serialNo'] = time();//唯一流水号
        $data['merNo'] = $this->merId;
        $data['merKey'] = $this->s_num;//第三方提供商品编号
        $data['merIp'] = get_ip();//ip地址
        $data['orderNo'] = $this->orderNum;
        if(in_array($this->money,[50,100,200,300,500,800,1000,2000,3000,5000,10000])){
        }else{
            $this->retMsg("下单失败请支付以下整数:50,100,200,300,500,800,1000,2000,3000,5000,10000");
        }
        $data['transAmt'] = $this->money;
        if($this->code==7)$data['bankCode'] = $this->bank_type;
        $data['orderDesc'] = $this->c_name;
        $data['transDate'] = date("Ymd");
        $data['transTime'] =date("YmdHis");
        if($this->code==7)$data['extraInfo'] ='id=SMARTCLOUD_ALIPAY_H5_PAY';
        $data['notifyUrl'] = $this->callback;
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
                return 'WEIXIN_SCAN_PAY';//微信扫码
                break;
            case 2:
                return 'WEIXIN_H5_PAY';//微信h5
                break;
            case 4:
                return 'SMARTCLOUD_ALIPAY_H5_PAY';//支付宝扫码
                break;
            case 5:
                return 'SMARTCLOUD_ALIPAY_H5_PAY';//支付宝WAP
                break;
            case 7:
                return 'EBANK_PC_PAY';//网银支付
                break;
            case 8:
                return 'QQ_SCAN_PAY';//qq扫码
                break;
            case 9:
                return 'JD_NATIVE_PAY';//京东扫码
                break;
            case 17:
                return 'UNIONPAY_NATIVE_PAY';//银联扫码
                break;
            case 28:
                return 'ALIPAY_H5_PAY';//支付宝H5(H5WAP)
                break;
            default:
                return 'SMARTCLOUD_ALIPAY_H5_PAY';//支付宝扫码
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
        if (!isset($data['respCode'])
            || empty($data['authCode']))
        {
            $msg = isset($data['respCode']) ? $data['respCode'].$data['respDesc'] : '返回参数错误';
            $this->retMsg("下单失败：{$msg}");
        }
        $string = explode(':',$data['authCode']);
        $pay_url = strtolower($string[0]).':'.$string[1];//wap支付地址或者二维码地址
        return $pay_url;
    }

}