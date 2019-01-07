<?php
defined('BASEPATH') or exit('No direct script access allowed');
/**
 * 双汇接口调用
 * Created by PhpStorm.
 * User: 57207
 * Date: 2018/8/23
 * Time: 13:52
 */
include_once __DIR__.'/Publicpay_model.php';
class Shuanghui_model extends Publicpay_model
{
    protected $c_name = 'shuanghui';
    private $p_name = 'SHUANGHUI';//商品名称
    //支付接口签名参数


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
        ksort($data);
        $string =ToUrlParams($data)."#".$this->key ;
        $data['sign'] = md5($string);
        return $data;
    }

    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {
        $data['amount'] =  sprintf('%.2f',$this->money);
        $data['currentTime'] = date('YmdHis');
        $data['merchant'] = $this->merId;
        $data['notifyUrl'] = $this->callback;
        $data['orderNo'] = $this->orderNum;
        $data['payType'] = $this->getPayType();
        $data['remark']  = $this->p_name;
        $data['returnUrl'] = $this->returnUrl;
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
            case 2:
                return 'wxpay';//微信
                break;
            case 4:
            case 5:
                return 'alipay';//支付宝
                break;
            case 8:
            case 12:
                return 'qqpay';//QQ扫码
                break;
            default:
                return 'wxpay';//支付宝扫码
                break;
        }
    }
}