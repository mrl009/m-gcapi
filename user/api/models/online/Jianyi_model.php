<?php
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';

class Jianyi_model extends Publicpay_model
{
    protected $c_name = 'Jianyi';
    private $p_name = 'JIANYIFU';//商品名称A
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
        $data['pay_applydate'] = date("YmdHis");
        $data['pay_bankcode'] = $this->getPayType();
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
            case 2:
                return 'WXPAY';//微信Wap/h5
                break;
            case 4:
            case 5:
                return 'ALIPAY';//支付宝WAP
                break;
            default:
                return 'ALIPAY';//支付宝扫码
                break;
        }
    }

    /**
     * 获取支付结果
     * @param $data 支付参数
     * @return return 二维码内容
     */
    protected function getPayResult($pay_data){
        $pay_data = http_build_query($pay_data);
        $data = post_pay_data($this->url,$pay_data);
        if (empty($data)) $this->retMsg('接口无信息返回！');
        //接收参数为JSON格式 转化为数组
        $data = json_decode($data,true);
        //判断是否下单成功
        if (empty($data['payurl']) || $data['retCode'] <> 10000){
            $msg = isset($data['retMsg']) ? $data['retMsg'] : '返回参数错误';
            $this->retMsg("下单失败：{$msg}");
        }
        $pay_url = $data['payurl'];
        return $pay_url;
    }
}
