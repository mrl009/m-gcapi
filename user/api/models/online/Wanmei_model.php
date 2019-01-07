<?php
defined('BASEPATH')or exit('No such ');
/**
 * Created by PhpStorm.
 * User: 57207
 * Date: 2018/9/18
 * Time: 20:04
 */
include_once __DIR__.'/Publicpay_model.php';
class Wanmei_model extends Publicpay_model
{
    protected $c_name = 'wanmei';
    protected $p_name = 'WANMEI';
    //构造签名参数
    protected $method = 'D'; //返回签名大小写 D 大写 X 小写
    protected $field = 'sign'; //签名参数名

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
        $f = $this->field;
        $m = $this->method;
        $k = $this->key;
        $string = $this->key . $this->merId . $this->orderNum . $data['amount'];
        $sign = hash('sha256', $string);
        $data[$this->field] =$sign;
        return $data;
    }

    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {
        $data['mchId'] = $this->merId;//商户号
        $data['type'] ='1';//类型
        $data['amount'] = yuan_to_fen($this->money);
        $data['order'] = $this->orderNum;
        $data['channelId'] = $this->getPayType();
        $data['notifyUrl'] = $this->callback;
        $data['successUrl'] = $this->returnUrl;
        $data['errorUrl'] = $this->returnUrl;
        $data['extra'] = $this->p_name;
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
                return 'wechat';//微信扫码
                break;
            case 2:
                return 'wechat';//微信WAP
                break;
            case 4:
                return 'alipay';//支付宝扫码
                break;
            case 5:
                return 'alipay';//支付宝扫码
                break;
            default:
                return 'alipay';//微信扫码
                break;
        }
    }

}