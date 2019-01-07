<?php
/**
 * 真好付支付接口调用
 * User: lqh
 * Date: 2018/08/07
 * Time: 09:03
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';

class Yunzhifu_model extends Publicpay_model
{
    protected $c_name = 'yunzhifu';
    private $p_name = 'YUNZHIFU';//商品名称
    //支付接口签名参数 
    private $method = 'D'; //返回签名大小写 D 大写 X 小写
    private $key_string = '&key='; //参与签名组成
    private $field = 'sign'; //签名参数名

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
        $data['merchantId'] = $this->merId;//商户号
        $data['outTradeNo'] = $this->merId.$this->orderNum;
        $data['body'] = $this->p_name;
        if ($this->money < 10 || $this->money>10000){
            $this->retMsg('支付金额不小于10元或不大于10000元');
        }
        $data['totalFee'] = yuan_to_fen($this->money);
        $data['payType'] = $this->getPayType();
        $data['payMode'] = 'perCode';
        $data['attach'] = $this->p_name;
        $data['notifyUrl'] = $this->callback;
        $data['nonceStr'] = create_guid();
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
                return 'WX';//微信扫码
                break;
            case 4:
            case 5:
                return 'ALI';//支付宝扫码
                break;
            default:
                return 'ALI';
                break;
        }
    }
    protected function getPayResult($pay_data)
    {
        //传递的参数json数据
        $json_data = json_encode($pay_data,320);
        $length = strlen($json_data);
        $header = [
            'Content-Type: application/json; charset=utf-8',
            'Content-Length:' . $length
            ];
        $data = post_pay_data($this->url,$json_data,$header);
        if (empty($data)) $this->retMsg('接口返回信息错误！');
        //接收参数为JSON格式(非纯json数据) 转化为数组
        $data = json_decode($data,true);
        if (empty($data)) $this->retMsg('接口返回信息格式错误！');
        //判断是否下单成功
        if (empty($data['payUrl']))
        {
            $msg = isset($data['retMsg']) ? $data['retMsg'] : '返回参数错误';
            $this->retMsg("下单失败：{$msg}");
        }
        //扫码支付 wap支付返回支付 实际地址
        return $data['payUrl'];
    }
}
