<?php
/**
 * 小熊宝支付接口调用
 * User: lqh
 * Date: 2018/07/12
 * Time: 14:45
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';

class Xiaoxiong_model extends Publicpay_model
{
    protected $c_name = 'xiaoxiong';
    private $p_name = 'XIAOXIONG';//商品名称
    //支付接口签名参数
    private $key_string = '&'; //参与签名组成

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
        $string = implode('&',array_values($data));
        $string .= $this->key_string . $this->key;
        $data['type'] = 'form';
        $data['goodsName'] = $this->p_name;
        $data['merchantUid'] = $this->user['id'];//用户ID
        $data['paytype'] = $this->getPayType();
        $data['sign'] = md5($string);
        return $data;
    }
    
    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {   
        $data['money'] = $this->money;
        $data['merchantId'] = $this->merId;//商户号
        $data['notifyURL'] = $this->callback;
        $data['returnURL'] = $this->returnUrl;
        $data['merchantOrderId'] = $this->orderNum;
        $data['timestamp'] = time();
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
            case 2:
                return 'WX';//微信扫码、WAP、WAP扫码
                break;   
            case 4:
            case 5:
                return 'ALIPAY';//支付宝扫码、WAP、WAP扫码
                break;
            case 8:
            case 12:
                return 'QQ';//QQ扫码
                break;
            case 9:
                return 'JD_QQ';
                break;
            case 15:
                return 'JD_WX';//京东微信
            case 36:
                return 'ALI_SOLID';//支付宝扫码固码
                break;
            case 37:
                return 'ALI_SOLID';//支付宝wap固码
                break;
            default:
                return 'WX';//微信扫码
                break;
        }
    }
}
