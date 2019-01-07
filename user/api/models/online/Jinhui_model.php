<?php
/**
 * 金汇支付接口调用
 * User: lqh
 * Date: 2018/05/07
 * Time: 18:32
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';

class Jinhui_model extends Publicpay_model
{
    protected $c_name = 'jinhui';
    private $p_name = 'JINHUI';//交易备注
    //支付接口签名参数 
    private $method = 'X'; //返回签名大小写 D 大写 X 小写
    private $key_string = '&keyvalue='; //参与签名组成
    private $field = 'sign'; //签名参数名
    
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 获取前端返回数据 
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
        //构造签名参数 sign
        $key_string = $this->key_string . $this->key;
        $sign_data = $this->getSignData();
        $sign_string = data_to_string($sign_data);
        $sign_string = $sign_string . $key_string;
        $sign = md5(strtolower($sign_string));
        //构造签名参数 sign2
        $sign2_data = $this->getSign2Data();
        $sign2_string = data_to_string($sign2_data);
        $sign2_string = $sign2_string . $key_string;
        $sign2 = md5(strtolower($sign2_string));
        $data['sign'] = $sign; 
        //$data['ext'] = $this->p_name; 
        $data['sign2'] = $sign2; 
        return $data;
    }

    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {
        $data['userid'] = $this->merId;
        $data['orderid'] = $this->orderNum;
        $data['money'] = $this->money;
        $data['url'] = $this->callback;
        $data['bankid'] = $this->getPayType();
        return $data;
    }

    /**
     * 构造参与签名参数 sign 
     * @return array
     */
    private function getSignData()
    {
        $data['userid'] = $this->merId;
        $data['orderid'] = $this->orderNum;
        $data['bankid'] = $this->getPayType();
        return $data;
    }

    /**
     * 构造参与签名参数 sign2 (非必传参数,本次加上)
     * @return array
     */
    private function getSign2Data()
    {
        $data['money'] = $this->money;
        $data['userid'] = $this->merId;
        $data['orderid'] = $this->orderNum;
        $data['bankid'] = $this->getPayType();
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
                return '2001';//微信扫码
                break;
            case 2:
                return '2005';//微信wap
                break;
            case 4:
                return '2003';//支付宝扫码
                break;
            case 5:
                return '2007';//支付宝wap
                break;
            case 7:
                return $this->bank_type;//网银
                break;
            case 8:
                return '2008';//QQ钱包扫码
                break;
            case 9:
                return '2010';//京东扫码
                break;
            case 12:
                return '2009';//QQ钱包wap
                break;
            case 13:
                return '2011';//京东钱包wap
                break;
            case 17:
                return '2012';//银联扫码
                break;
            default:
                return '2001';
                break;
        }
    }

    /**
     * 创建表单提交数据
     * @param array $data 表单内容
     * @return array
     */
    protected function buildForm($data)
    {
        $temp = [
            'method' => 'get',
            'data'   => $data,
            'url'    => $this->url,
        ];
        $rs['jump'] = 5;
        $rs['url']  = $this->domain . '/index.php/pay/pay_test/pay_sest/' . $this->orderNum;
        $rs['json'] = json_encode($temp, JSON_UNESCAPED_UNICODE);
        return $rs;
    }   
}
