<?php
/**
 * 乐付无限支付接口调用
 * User: Tailand
 * Date: 2019/1/2
 * Time: 11:11
 */
defined('BASEPATH') or exit('No direct script access allowed');
//调用公共文件
include_once  __DIR__.'/Publicpay_model.php';
class Lewuxian_model extends Publicpay_model
{
    protected $c_name = 'lewuxian';
    private $p_name = 'LWX';//商品名称
    //支付接口签名参数
    private $method = 'D'; //返回签名大小写 D 大写 X 小写
    private $key_string = '&'; //参与签名组成
    private $field = 'sign'; //签名参数名

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
        //wap支付
        if (in_array($this->code,$this->wap_code))
        {
            return $this->buildWap($data);
            //扫码支付
        } elseif (in_array($this->code,$this->scan_code)) {
            return $this->buidImage($data);
            //网银支付快捷支付和收银台 (部分接口不通用)
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
        $f = $this->field;
        $m = $this->method;
        $k = $this->key_string . $this->key;
        $data = get_pay_sign($data,$k,$f,$m);
        if($this->code==7){
            $data['bankCode'] = $this->bank_type;
            $data['bankCardNo'] = substr(create_guid(),0,16);
        }
        if($this->code==25)$data['userIdentity'] = '13'.substr(create_guid(),0,8);
        $data['serviceType'] = '1';
        return $data;
    }

    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {
        $data['cpId'] = $this->merId;//商户号
        $data['serviceId'] = $this->s_num;//服务id
        $data['payType'] = $this->getPayType();
        $data['subject'] = $this->p_name;
        $data['fee'] = yuan_to_fen($this->money);
        $data['description'] = 'perLe';
        $data['orderIdCp'] = $this->orderNum;
        $data['notifyUrl'] = $this->callback;
        $data['callbackUrl'] = $this->returnUrl;
        $data['timestamp'] = getMillisecond();
        $data['ip'] = get_ip();
        $data['version'] = '1';
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
                return '1000';//微信扫码
                break;
            case 2:
                return '1001';
                break;
            case 4:
                return '2000';//支付宝扫码
                break;
            case 5:
                return '2000';//支付宝wap
                break;
            case 7:
                return '5000';
                break;
            case 18:
                return '5002';
                break;
            case 25:
                return '5001';
                break;
            default:
                return '2000';
                break;
        }
    }
    protected function getPayResult($pay_data)
    {
        //传递的参数json数据
        $json_data = json_encode($pay_data, 320);
        $header = [
            'Content-Type: application/json; charset=utf-8',
        ];
        $data = post_pay_data($this->url, $json_data, $header);
        if (empty($data)) $this->retMsg('接口返回信息错误！');
        $data = json_decode($data,true);
         if(isset($data['status']) &&  $data['status'] <> '0'){
             $msg = isset($data['status']) ? $data['status'] : '返回参数错误';
             $this->retMsg("下单失败：{$msg}");
         }
        //wap支付
        if (in_array($this->code,$this->wap_code))
        {
            return $data['payUrl'];
            //扫码支付
        } elseif (in_array($this->code,$this->scan_code)) {
            return $data['imageUrl'];
        }
    }
}