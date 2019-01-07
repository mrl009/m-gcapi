<?php
/**
 * 易付支付模块
 * date:2018-04-09
 */

class Gaotong_model extends MY_Model
{
    public $key;
    public $merId;
    public $orderNum;
    public $money;
    public $url;
    public $callback;
    public $domain;

    public function call_interface($order_num, $money, $pay_data)
    {
        $this->orderNum = $order_num;//订单号
        $this->money = (float)$money;//支付金额
        $this->merId = isset($pay_data['pay_id']) ? trim($pay_data['pay_id']) : '';//商户ID
        $this->key = isset($pay_data['pay_key']) ? $pay_data['pay_key'] : '';//密钥
        $this->url = isset($pay_data['pay_url']) ? $pay_data['pay_url'] : '';//请求地址
        $this->domain = isset($pay_data['pay_domain']) ? $pay_data['pay_domain'] : '';
        $this->callback = $this->domain . '/index.php/callback/gaotong/callbackurl';//回调地址
        $banktype=$this->getType($pay_data['code'],$pay_data['bank_type']);
        $sign = md5("partner={$this->merId}&banktype={$banktype}&paymoney={$this->money}&ordernumber={$order_num}&callbackurl={$this->callback}{$this->key}");
        $data = $this->fzData($pay_data);
        $data['sign']=$sign;
        return  $this->buildForm($data);

    }
    
    //.组装数据
    private function fzData($pay_data)
    {
        $code = isset($pay_data['code']) ? $pay_data['code'] : 0;
        $data['partner']=$this->merId;//.商户id
        $data['banktype']=$this->getType($code,$pay_data['bank_type']);//.通道类型
        $data['paymoney']=$this->money;//。支付金额
        $data['ordernumber']=$this->orderNum;//.订单号jhj
        $data['callbackurl']=$this->callback;//。异步通知地址
        return   $data;


    }


    //.支付方式做映射
    private function getType($code,$banktype)
    {
        switch ($code) {
            case 1:
                return 'WEIXIN';
                break;
            case 2:
                return 'WEIXINWAP';
                break;
            case 4:
                return 'ALIPAY';
                break;
            case 5:
                return 'ALIPAYWAP';
                break;
            case 7:
                return $banktype;
                break;
            case 8:
                return 'QQPAY';
                break;
            case 12:
                return 'QQPAYWAP';
            case 17:
                return 'UNIONPAY';
            case 22:
                return 'TENPAY';
                break;
            case 25:
                return 'UNIONWAPPAY';
                break;
            case 40:
                return 'WEIXINBARCODE';
                break;
            default:
                return 'WEIXIN';
                break;
        }
    }


    /**
    *去数组空值
    *@param 数组
    */
    private function dekong($data){
        foreach($data as $k=>$v){
            if(empty($v)){
                unset($data[$k]);
            }
        }
        return $data;
    }




     /**
     * 创建表单
     * @param array $data 表单内容
     * @return array
     */
    private function buildForm($data)
    {
        $temp = [
            'method' => 'get',
            'data' => $data,
            'url' => $this->url
        ];
        $rs['jump'] = 5;
        $rs['url'] = $this->domain . '/index.php/pay/pay_test/pay_sest/' . $this->orderNum;
        $rs['json'] = json_encode($temp, JSON_UNESCAPED_UNICODE);
        return $rs;
    }
}