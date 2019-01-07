<?php
defined('BASEPATH')or die('No direct script access allowed');
/**
 * Created by PhpStorm.
 * User: 57207
 * Date: 2018/8/3
 * Time: 15:43
 */
include_once __DIR__.'/Publicpay_model.php';

class Yunhao_model extends Publicpay_model
{
    protected $c_name = 'yunhao';
    private $p_name = 'YUNHAO';//商品名称
    //支付接口签名参数
    private $method = 'D'; //返回签名大小写 D 大写 X 小写
    private $key_string = '&key='; //参与签名组成
    private $field = 'pay_md5sign'; //签名参数名

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
        $k = $this->key_string . $this->key;
        ksort($data);
        //把数组参数以key=value形式拼接最后加上$key_string值
        $sign_string = $this->Params($data).$k;
        $data[$this->field] =strtoupper(md5($sign_string));
        $data['tongdao'] = $this->getPayType();
        return $data;
    }

    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {
        $data['pay_memberid'] = $this->merId;
        $data['pay_orderid'] = $this->orderNum;
        $data['pay_amount'] = $this->money;
        $data['pay_applydate'] = date("Y-m-d H:i:s");
        if($this->code==7)$data['pay_bankcode'] = $this->bank_type;
        $data['pay_notifyurl'] = $this->callback;
        $data['pay_callbackurl'] = $this->returnUrl;
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
                return 'WX';//微信扫码
                break;
            case 4:
                return 'ZFB';//支付宝扫码
                break;
            case 5:
                return 'ZFB';//支付宝WAP
                break;
            case 7:
                return 'WY';//网银支付
                break;
            case 25:
                return 'KJ';//快捷支付
                break;
            default:
                return 'WX';//支付宝扫码
                break;
        }
    }
    /**
     * 将数组的键与值用符号隔开
     * @param $data array 待签名的数据
     * @return  $str string
     */
   protected function Params($data)
    {
        $buff = "";
        foreach ($data as $k => $v)
        {
            if($k != $this->field && $v != "" && !is_array($v)){
                $buff .= $k . "=>" . $v . "&";
            }
        }
        $buff = trim($buff, "&");
        return $buff;
    }
}