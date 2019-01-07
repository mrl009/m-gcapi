<?php
/**
 * 11付模块
 * 
 */

class Elevenfu_model extends MY_Model
{
    private $key;
    private $merId;
    private $orderNum;
    private $money;
    private $url;
    private $callback;
    private $domain;
    private $pr_key;//.私钥
    private $public_key;
   

    public function call_interface($order_num, $money, $pay_data)
    {
        $this->orderNum = $order_num;//订单号
        $this->money = $money;//支付金额
        $this->merId = isset($pay_data['pay_id']) ? trim($pay_data['pay_id']) : '';//商户ID
        $this->key = isset($pay_data['pay_key']) ? $pay_data['pay_key'] : '';//密钥
        $this->url = isset($pay_data['pay_url']) ? $pay_data['pay_url'] : '';//请求地址
        $this->domain = isset($pay_data['pay_domain']) ? $pay_data['pay_domain'] : '';
        $this->callback = $this->domain . '/index.php/callback/elevenfu/callbackurl';//回调地址
        $data = $this->fzData($pay_data);
        return  $this->buildForm($data);

    }
    
    //.组装数据
    private function fzData($pay_data)
    {
        $code = isset($pay_data['code']) ? $pay_data['code'] : 0;
        //.wap
        $data['version']='01';//.版本
        $data['ordercode']=$this->orderNum;//.订单编号
        $data['merNo']= $this->merId;//.商户号
        $data['amount']= $this->money;//.支付金额
        $data['goodsId']=$this->getType($code,$pay_data['bank_type']);//.支付方式
        $data['statedate']= date('Ymd', time());//。支付日期
        $data['callbackurl']=$this->callback;//。异步回调地址
        $data['notifyurl']=$_SERVER['HTTP_REFERER']?$_SERVER['HTTP_REFERER']:$pay_data['pay_return_url'];//。同步返回地址
        if($pay_data['code']==7){
            $data['bankname']=$pay_data['bank_type'];//。一行名称
        }
        $data['ip'] = get_ip();
        $data['sign']=$this->sign($data);//。支付金额
        return   $data;
    }


    //.支付方式做映射
    private function getType($code,$bankType)
    {
         switch ($code) {
            case 1:
                return 'Wxpay';//微信扫码
                break;
            case 4:
                return 'Zfbpay';//支付宝
                break;
            case 5:
                return 'Zfbh5';//支付宝wap
                break;
            case 7:
                return 'Wypay';//网银支付
                break;
            case 8:
                return 'Qqpay';//qq钱包
                break;
            case 9:
                return 'Jdpay';//京东扫码
                break;
                break;
            case 17:
                return 'Ylpay';//银联扫码
                break;
            case 25:
                return 'Wappay';//网银快捷支付
                break;    
        }
    }


     /**
     * 获取支付签名
     * @param string $data 支付参数
     * @return string $sign签名值
     */
    private function sign($data)
    {
       $amount = $data['amount']*100;
       $qmStr = sprintf(
                    "%s%s%s%s",
                    $data['ordercode'],
                    $amount,
                    $data['goodsId'],
                    $this->key  
            );
       return   md5($qmStr);
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


    public function online_erro($id, $str)
    {
        $reidsKey = "online:erro:";
        $this->redis_select(4);
        $this->redis_setex($reidsKey.$id, 90000, $str);
        $this->redis_select(5);
    }

}