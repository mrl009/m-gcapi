
<?php

/**
 * Created by PhpStorm.
 * User: Tailand
 * Date: 2018/10/22
 * Time: 18:00
 */
defined('BASEPATH') or exit('No direct script access allowed');
include_once __DIR__.'/Publicpay_model.php';
class Lintong_model extends Publicpay_model
{
    protected $c_name = 'lintong';
    private $p_name = 'LINTONG';//商品名称
    //支付接口签名参数
    private $key_string = '&'; //参与签名组成
    private $fasnt = [10,20,30,40,50,60,70,80,90,100,200,300,400,500,600,700,800,900,1000,1500,2000,3000] ;

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
        if(!in_array(intval($this->money),$this->fasnt)){
            $this->retMsg('请支付10,20,30,40,50,60,70,80,90,100,200,300,400,500,600,700,800,900,1000,1500,2000,3000');
        }
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
                return 'PXX_H5_WX';//微信扫码、WAP、WAP扫码
                break;
            default:
                return 'PXX_H5_WX';//微信扫码
                break;
        }
    }
}