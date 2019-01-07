<?php
/**
 * Created by PhpStorm.
 * User: Daxiniu
 * Date: 2018/11/21
 * Time: 16:29
 */
defined('BASEPATH') or exit('No direct script access allowed');

include_once __DIR__.'/Publicpay_model.php';

class Api_model extends Publicpay_model
{
    protected $c_name = 'api';
    private $p_name = 'API';//商品名称

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
        return $this->buildForm($data);
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
        $string = $this->ToParams($data);
        $data['hmac'] = HmacMd5($string,$this->key);
        return $data;
    }

    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {
        $data['p0_Cmd'] = 'Buy';
        $data['p1_MerId'] = $this->merId;
        $data['p2_Order'] = $this->orderNum;
        $data['p3_Amt'] = $this->money;
        $data['p4_Cur'] = 'CNY';
        $data['p5_Pid'] = $this->p_name;
        $data['p6_Pcat'] = 'APIpay';
        $data['p7_Pdesc'] = 'APIpay';
        $data['p8_Url'] = $this->callback;
        $data['p9_SAF'] = '0';
        $data['pa_MP'] = $this->c_name;
        $data['pd_FrpId'] = $this->getPayType();
        $data['pr_NeedResponse'] = "1";
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
                return 'wxcode';//微信
                break;
            case 2:
                return 'wxwap';//微信WAP 
                break;
            case 4:
                return 'alipay';//支付宝扫码
                break;
            case 5:
                return 'alipaywap';//支付宝WAP
                break;
            case 7:
                return $this->bank_type;//网银支付
                break;
            case 8:
                return 'qqpay';//QQ钱包扫码
                break;
            case 9:
                return 'jdpay';//京东扫码
                break;
            case 12:
                return 'qqpaywap';//QQWAP
                break;
            case 13:
                return 'jdwap';//QQWAP
                break;
            case 18:
                return 'unionpay';//银联钱包
                break;
            case 22:
                return 'tenpay';//财付通扫码
                break;
            case 25:
                return 'OnLineKJ';//快捷
                break;
            default :
                return 'alipay';
                break;

        }
    }


    /**
     * 将数组的键与值用+符号隔开
     * @param $data array 待签名的数据
     * @return  $str string
     */
    protected function ToParams($data)
    {
        $str = "";
        foreach ($data as $k => $v)
        {
            if($k != "sign" && $v != "" && !is_array($v)){
                $str .= "" . $v ;
            }
        }
        $params = trim($str, "");
        return $params;
    }

    protected function buildForm($data,$method="post")
    {
        $temp = [
            'method' => $method,
            'data'   => $data,
            'url'    => $this->url.'/GateWay/ReceiveBank.aspx',
        ];
        $rs['jump'] = 5;
        $rs['url']  = $this->domain . '/index.php/pay/pay_test/pay_sest/' . $this->orderNum;
        $rs['json'] = json_encode($temp, JSON_UNESCAPED_UNICODE);
        return $rs;
    }
}