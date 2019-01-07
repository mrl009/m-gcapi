<?php
/**
 * 广付通支付接口调用
 * User: lqh
 * Date: 2018/08/29
 * Time: 09:20
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';

class Guangfutong_model extends Publicpay_model
{
    protected $c_name = 'guangfutong';
    private $p_name = 'GFT';//商品名称
    //支付接口签名参数 
    private $method = 'X'; //返回签名大小写 D 大写 X 小写
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
        $data['mchId'] = $this->merId;
        $data['payType'] = $this->getPayType();
        $data['mchOrderNo'] = $this->orderNum;
        $data['amount'] = yuan_to_fen($this->money);
        $data['version'] = 'V001';
        $data['commodityName'] = $this->p_name;
        $data['backNotifyUrl'] = $this->callback;
        $data['frontNotifyUrl'] = $this->returnUrl;
        $data['timestamp'] = time();
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
                return '008';//微信
                break;
            case 4: 
            case 5:
                return '005';//支付宝
                break;   
            default:
                return '005';//支付宝
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
        $pay_data = json_encode($pay_data);
        $data = post_pay_data($this->url,$pay_data,'json');
        if (empty($data)) $this->retMsg('接口返回信息错误！');
        //接收参数为JSON格式 转化为数组
        $data = json_decode($data,true);
        if (empty($data)) $this->retMsg('接口返回信息格式错误！');
        //判断是否下单成功
        if (empty($data['data']['qr_code']) && empty($data['data']['html']))
        {
            $msg = isset($data['msg']) ? $data['msg'] : '返回信息错误';
            $this->retMsg("下单失败：{$msg}");
        }
        //返回支付链接
        if (!empty($data['data']['qr_code'])) 
        {
            $pay_url = $data['data']['qr_code'];
        } else {
            $pay_url = $data['data']['html'];
        } 
        return $pay_url;
    }
}
