<?php
/**
 * 大白支付接口调用
 * User: lqh
 * Date: 2018/08/29
 * Time: 09:20
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';

class Dabai_model extends Publicpay_model
{
    protected $c_name = 'dabai';
    private $p_name = 'DABAI';//商品名称

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
        $string = ToUrlParams($data);
        $data['pay_returntype'] = 'HTML';
        $data['pay_create_ip'] = get_ip();
        $data['pay_mahname'] = $this->p_name;
        if (in_array($this->code,$this->wap_code))
        {
            $temp['h5_info'] = array(
                'type' => 'WAP',
                'wap_name' => $this->p_name,
                'wap_url' => $this->returnUrl
            );
            $data['pay_scene_info'] = json_encode($temp);
        }
        $data['pay_sign'] = md5(md5($string));
        unset($data['pay_key']);
        //传递参数进行包装
        $return['body'] = base64_encode(json_encode($data));
        return $return;
    }
    
    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {
        $data['pay_version'] = '2.0';
        $data['pay_amount'] = $this->money;
        $data['pay_bankcode'] = $this->getPayType();
        $data['pay_scene'] = $this->getScene();
        $data['pay_memberid'] = $this->merId;
        $data['pay_orderid'] = $this->orderNum;
        $data['pay_notifyurl'] = $this->callback;
        $data['pay_callbackurl'] = $this->returnUrl;
        $data['pay_rand'] = create_guid();
        $data['pay_key'] = $this->key;
        return $data;
    }

    /**
     * 根据code值获取支付方式
     * @param string code 
     * @return string 支付场景 参数
     */
    private function getScene()
    {
        if (in_array($this->code,$this->wap_code))
        {
            return 'wap';
        } else {
            return 'sm';
        }
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
            case 2:
                return 'wxpay';//微信宝
                break; 
            case 4:
            case 5:
                return 'alipay';//支付宝
                break;
            case 7:
                return $this->bank_type;//网银支付
                break;
            case 8:
            case 12:
                return 'qqpay';//QQ
                break;
            case 9:
            case 13:
                return 'jdpay';//京东
                break;
            case 17:
            case 18:
                return 'unionpay';//银联钱包
                break;
            case 25:
                return 'kjpay';//快捷
                break;
            default:
                return 'alipay';//支付宝扫码
                break;
        }
    }
}
