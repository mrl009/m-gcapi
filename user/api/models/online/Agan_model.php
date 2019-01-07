<?php
/**
 * 阿甘AG支付接口调用
 * User: lqh
 * Date: 2018/07/30
 * Time: 10:20
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once __DIR__.'/Publicpay_model.php';

class Agan_model extends Publicpay_model
{
    protected $c_name = 'agan';
    private $p_name = 'AGAN';//商品名称
    //支付接口签名参数 
    private $method = 'D'; //返回签名大小写 D 大写 X 小写
    private $key_string = '&key='; //参与签名组成
    private $field = 'pay_md5sign'; //签名参数名

    public function __construct()
    {
        parent::__construct();
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
        $k = $this->key_string . $this->key;
        $data = get_pay_sign($data,$k,$f,$m);
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
        $data['pay_service'] = $this->getPayType();
        $data['pay_notifyurl'] = $this->callback;
        $data['pay_callbackurl'] = $this->returnUrl;
        $data['pay_applydate'] = date("Y-m-d H:i:s");
        //网银支付参数
        if (7 == $this->code)
        {
            $data['pay_bankcode'] = $this->bank_type;
        }
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
            case 4:
                return '903';//支付宝扫码
                break; 
            case 5:
                return '904';//支付宝WAP
                break;
            case 7:
                return '907';//网银支付
                break;
            case 8:
                return '908';//QQ扫码
                break;
            case 12:
                return '905';//QQWAP
                break;
            case 25:
                return '911';//快捷支付
                break;
            default:
                return '903';//支付宝扫码
                break;
        }
    }

    /**
     * 获取支付结果
     * @param $data 支付参数
     * @return return 二维码内容
     */
    protected function getPayResult($pay_data)
    {
        //传递参数
        $data = post_pay_data($this->url,$pay_data,-1);
        if (empty($data)) $this->retMsg('接口返回信息错误！');
        //接收参数为JSON格式 转化为数组
        $data = json_decode($data,true);
        if (empty($data)) $this->retMsg('接口返回信息格式错误！');
        //判断是否下单成功
        if (empty($data['data']))
        {
            $msg = "返回参数错误";
            if (isset($data['msg'])) $msg = $data['msg'];
            $this->retMsg("下单失败：{$msg}");
        }
        //返回支付二维码连接地址或WAP支付地址
        return $data['data'];
    }
}
