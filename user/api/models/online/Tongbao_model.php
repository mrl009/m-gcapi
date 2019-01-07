<?php
/**
 * 通宝支付接口调用
 * User: lqh
 * Date: 2018/07/05
 * Time: 15:05 
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';

class Tongbao_model extends Publicpay_model
{
    protected $c_name = 'tongbao';
    protected $p_name = 'TONGBAO';
    //支付接口签名参数 
    private $ks = '&'; //参与签名组成

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
     * 构造基本参数
     */
    protected function getPayData()
    {
        $data = $this->getDataBase();
        //构造签名参数
        $k = $this->ks . $this->key;
        $string = ToUrlParams($data) . $k;
        $data['sign'] = md5($string);
        if (7 == $this->code) $data['bankcode'] = $this->bank_type;
        return $data;
    }

    /*
     * 构造签名的参数
     */
    protected function getDataBase()
    {
        $data['shid'] = $this->merId;
        $data['bb'] = '1.0';
        $data['zftd']=$this->getPayType();//充值方式
        $data['ddh'] = $this->orderNum;
        $data['je'] = $this->money;
        $data['ddmc'] = $this->p_name;
        $data['ddbz'] = $this->p_name;
        $data['ybtz'] = $this->callback;
        $data['tbtz'] = $this->returnUrl;
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
                 return 'weixin';//微信
                break;
            case 2:
                return 'weixinh5';//微信
                break;
            case 4:
                return 'alipay';//支付宝
                break;
            case 5:
                return 'alipayh5';//支付宝
                break;
            case 7:
                return 'bank';//网银
                break;
            case 8:
                return 'qqpay';//QQ
                break;
            case 17:
                return 'ylscan';//银联扫码
                break;
            case 18:
                return 'bankwap';//银联
                break;
            default:
                return 'alipay';
        }
    }
}