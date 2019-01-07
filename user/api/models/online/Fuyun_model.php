<?php
defined('BASEPATH') or exit('No direct script access allowed');
/**
 * 富云支付接口调用
 * Created by PhpStorm.
 * User: 57207
 * Date: 2018/10/16
 * Time: 14:43
 */
include_once __DIR__.'/Publicpay_model.php';

class Fuyun_model extends Publicpay_model
{
    protected $c_name = 'fuyun';
    protected $p_name = 'FUYUN';
    //支付接口签名参数
    private $method = 'X'; //返回签名大小写 D 大写 X 小写
    private $key_string = '&key='; //参与签名组成
    private $field = 'sign'; //签名参数名

    public function __construct()
    {
        parent::__construct();
    }
   //提交方式
    protected function returnApiData($data)
    {
        return $this->buildWap($data);

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
        $data['mchid'] = $this->merId;//商户号
        $data['mchno'] = $this->orderNum;
        $data['tradetype'] = $this->getPayType();
        $data['totalfee'] = $this->money;
        $data['descrip'] = $this->p_name;
        $data['clientip'] = get_ip();
        $data['returnurl'] = $this->returnUrl;
        $data['notifyurl'] = $this->callback;
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
                return 'weixin';//微信扫码
                break;
            case 2:
                return 'weixinh5';//微信WAP
                break;
            case 4:
                return 'alipay';//支付宝扫码
                break;
            case 5:
                return 'alipayh5';//支付宝h5
                break;
            default:
                return 'alipay';//支付宝扫码
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
        $data = json_decode($data,true);
        if (empty($data)) $this->retMsg('接口返回信息格式错误！');
        //判断是否下单成功
        if ($data['result_code']!='1')
        {
            $msg = isset($data['retMsg']) ? $data['retMsg'] : '返回参数错误';
            $this->retMsg("下单失败：{$msg}");
        }
        //扫码支付返回支付二维码连接地址
        return $data['code_url'];
    }
}