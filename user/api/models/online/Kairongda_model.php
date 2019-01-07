<?php
/**
 * 支付模块
 * date:2018-05-12
 */

class Kairongda_model extends MY_Model
{
    private $key;
    private $merId;
    private $orderNum;
    private $money;
    private $url;
    private $callback;
    private $hrefback;
    private $domain;
    private $pr_key;//.私钥
    private $public_key;
   

    public function call_interface($order_num, $money, $pay_data)
    {
        $this->orderNum = $order_num;//订单号
        $this->money = $money;//支付金额,以分为单位
        $this->merId = isset($pay_data['pay_id'])?trim($pay_data['pay_id']) :'';//商户ID
        $this->key = $pay_data['pay_key'];  //商户私钥
        $this->domain = isset($pay_data['pay_domain']) ? $pay_data['pay_domain'] : '';
        $this->callback = $this->domain . '/index.php/callback/kairongda/callbackurl';//回调地址
        $this->url = $pay_data['pay_url'];
        $data =$this->payData($pay_data);
        return  $this->buildForm($data);
    }

    /**
      * @desc scan接口数据组装
      ****/
    private function payData($pay_data)
    {    
        $data['GuestNo']=$this->merId;//.商户号
        $data['krdKey']=$this->key;//.安全码
        $data['orderNo']=$this->orderNum;//.订单号
        $data['totalFee']=$this->money;//。金额  元
        $data['notifyUrl']=$this->callback;//。下单时间
        $data['returnUrl']= isset($_SERVER['HTTP_REFERER'])?$_SERVER['HTTP_REFERER']:$pay_data['pay_return_url'];//。产品名称
        $data['defaultbank']=$this->getType($pay_data['code'],$pay_data['bank_type']);//。支付方式
        // $data['sign'] =$this->sign($data);
        return   $data;
    }

    //.支付方式做映射/ T0结算方式   实时结算
    private function getType($code,$bank_type)
    {
         switch ($code) {
            case 1:
                return 'wxpay';//微信扫码
                break;
            case 2:
                return 'wxh5';//wap
                break;
            case 4:
                return 'ALIPAY';//支付宝扫码
                break;
            case 5:
                return 'ALIPAYh5';//wap
                break;
            case 7:
                return $bank_type;//wap
                break;
            case 25:
                return 'QUICKPAY';//wap
                break;
        }
    }

    /**
     * 创建表单
     * @param array $data 表单内容
     * @return array
     */
    private function buildForm($data)
    {
        $temp = [
            'method' => 'post',
            'data' => $data,
            'url' => $this->url
        ];
        $rs['jump'] = 5;
        $rs['url'] = $this->domain . '/index.php/pay/pay_test/pay_sest/' . $this->orderNum;
        $rs['json'] = json_encode($temp, JSON_UNESCAPED_UNICODE);
        return $rs;
    }

}