<?php
/**
 * Maxpay 支付接口调用
 * Created by PhpStorm.
 * User: Tailand
 * Date: 2018/10/22
 * Time: 10:42
 */
defined('BASEPATH') or exit('No direct script access allowed');
//调用公共文件
include_once __DIR__.'/Publicpay_model.php';

class Maxpay_model extends Publicpay_model
{
    //redis 错误记录
    protected $p_name = 'MAXPAY';
    protected $c_name = 'maxpay';
    //签名参数
    protected $f_d  = 'sign';
    protected $m_d = 'X';
    protected $k_g = '';
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

   //构造支付参数
    protected function getPayData(){
        //构造签名参数
        $data = $this->getBaseData();
        //签名
        $string = $data['money'].$data['order_id'].$data['sdk'];
        $data['sign'] = md5(md5($string));
        return $data;
    }

    //签名参数 (修改)
    protected function getBaseData()
    {
        $data['id'] = $this->merId;  //商户id
        $data['sdk'] = $this->s_num; //第三方SDK(机构号)
        $data['order_id'] = $this->orderNum;
        $data['money']    = $this->money;
        $data['refer']    = $this->returnUrl;
        $data['notify_url'] = $this->callback;
        $data['Identification'] = '454a42d8dad186f50093270bd6bbccf1';//识别类型 固定值
        return $data;
    }

    /**
     * 根据编码获取支付通道
     */
    protected function getPayType(){

        switch ($this->code){
            case 1:
                return 'wechat';
                break;
            case 2:
                return 'wechat';
                break;
            case 4:
            case 5:
                return 'alipay';
                break;
            case 8:
            case 12:
                return 'qq';
                break;
            default:
                return 'alipay';
                break;
        }
    }
}