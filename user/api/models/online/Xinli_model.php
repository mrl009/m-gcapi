<?php
defined('BASEPATH')or die('No direct script access allowed');
/**
 * Created by PhpStorm.
 * User: 57207
 * Date: 2018/8/17
 * Time: 18:21
 */
include_once __DIR__.'/Publicpay_model.php';
class Xinli_model extends  Publicpay_model
{
    protected $c_name = 'Xinli';
    private $p_name = 'XINLI';//商品名称
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
        if($this->code==7){
            $data['pay_bankCode'] = $this->bank_type;
            $data['pay_paytype'] = '1';

        }
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
        $data['pay_bankcode'] = 'ICBC';
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
                return 'WechatSM';//微信扫码1,4,5,7,8,9,12,17,18,25
                break;
            case 2:
                return 'WechatH5';//微信H5
                break;
            case 4:
                return 'AlipaySM';//支付宝扫码
                break;
            case 5:
                return 'AlipayH5';//支付宝WAP
                break;
            case 7:
                return 'YLWY';//网银支付
                break;
            case 8:
                return 'QQSM';//qq扫码
                break;
            case 9:
                return 'JDSM';//京东扫码
                break;
            case 12:
                return 'QQH5';//qqWap
                break;
            case 17:
                return 'YLSM';//银联扫码
                break;
            case 18:
                return 'YLKJH5';//银联快捷 Wap
                break;
            case 25:
                return 'YLKJ';//快捷支付
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