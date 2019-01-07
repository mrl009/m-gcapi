<?php
defined('BASEPATH') or exit('No direct script access allowed');
/**
 * Created by PhpStorm.
 * User: 57207
 * Date: 2018/7/20
 * Time: 15:50
 */
include_once __DIR__.'/Publicpay_model.php';
class Yixin_model extends Publicpay_model
{
    protected $c_name = 'yixin';
    private $p_name = 'YIXIN';//商品名称
    //支付接口签名参数
    private $method = 'X'; //返回签名大小写 D 大写 X 小写
    private $key_string = '&key='; //参与签名组成
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
       return $this->buildForm($data,"get");
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
        $k = $this->key;
        $data = $this->get_pay_sign($data,$k,$f,$m);
        $data['hrefbackurl'] = $this->returnUrl;
        $data['payerIp'] = get_ip();//客户ip
        $data['attach'] = date('Y-m-d H:i:s',time());//自定发起时间
        //print_r(json_encode($data));die;
        return $data;
    }

    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {
        $data['parter'] = $this->merId;//商户号
        $data['type'] = $this->getPayType();//银行的类型
        $data['value'] = $this->money;//金额
        $data['orderid'] = $this->orderNum;//订单号 唯一
        $data['callbackurl'] = $this->callback;
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
                return '1004';//微信扫码
                break;
            case 2:
                return '2099';//微信WAP
                break;
            case 4:
                return '992';//支付宝扫码
                break;
            case 5:
                return '2098';//支付宝WAP
                break;
            case 7:
                return $this->bank_type;//网关支付
                break;
            case 23:
                return '993';//财富通wap
                break;
            case 27:
                return '2097';//网银WAP
                break;
            case 8:
                return '2100';//qq扫码
                break;
            default:
                return '1004';//微信扫码
                break;
        }
    }

    /**
     * 获取支付签名 md5加密方式
     * @param array $data 参与签名的参数
     * @param string $field 代表签名的数组字段 默认sign
     * @param string $method 加密方式 默认D 大写 X 小写
     * @param string $key_string 由key值组成的加密字符串 默认'&key='.$key
     * @param string 备注：$key_string 默认'&key='.$key 部分接口 '&'.$key
     * @return array : 含有签名参数的 data 数组
     */
   public function get_pay_sign($data,$key_string,$field='sign',$method='D')
    {
        if (!empty($data) && is_array($data) && !empty($key_string))
        {

            //把数组参数以key=value形式拼接最后加上$key_string值
            $sign_string = ToUrlParams($data).$key_string;
            //拼接字符串进行MD5大写加密
            $sign = md5($sign_string);
            $sign = ('D' == $method) ? strtoupper($sign) : $sign;
            $data[$field] = $sign;
        }
        return $data;
    }


}
