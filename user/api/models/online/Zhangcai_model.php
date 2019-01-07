<?php
/**
 * 通扫支付模块
 * date:2018-04-14
 */

class Zhangcai_model extends MY_Model
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
        $this->money = (float)$money;//支付金额
        $this->merId = isset($pay_data['pay_id']) ? trim($pay_data['pay_id']) : '';//商户ID
        $this->key = isset($pay_data['pay_key']) ? $pay_data['pay_key'] : '';//密钥
        $this->url = isset($pay_data['pay_url']) ? $pay_data['pay_url'] : '';//请求地址
        $this->domain = isset($pay_data['pay_domain']) ? $pay_data['pay_domain'] : '';
        $this->callback = $this->domain . '/index.php/callback/zhangcai/callbackurl';//回调地址
        $banktype=$this->getType($pay_data['code'],$pay_data['bank_type']);
        $sign = md5("parter={$this->merId}&type={$banktype}&value={$this->money}&orderid={$order_num}&callbackurl={$this->callback}{$this->key}");
        $data = $this->fzData($pay_data);
        $data['sign'] =$sign;
        return  $this->buildForm($data);

    }
    
    //.组装数据
    private function fzData($pay_data)
    {
        $code = isset($pay_data['code']) ? $pay_data['code'] : 0;
        //.wap
        $data['parter']=$this->merId;//.商户号
        $data['type']=$this->getType($code,$pay_data['bank_type']);//.支付类型
        $data['value']=$this->money;//.支付金额
        $data['orderid']=$this->orderNum;//.订单号
        $data['callbackurl']=$this->callback;//。支付金额
        return   $data;
    }


    //.支付方式做映射
    private function getType($code,$bankType)
    {
         switch ($code) {
            case 1:
                return '1004';//微信扫码
                break;
            case 2:
                return '2099';//微信wap
                break;
            case 4:
                return '992';//支付宝
                break;
            case 5:
                return '2098';//支付宝wap
                break;
            case 7:
                return $bankType;//网银支付
                break;
            case 8:
                return '2100';//qq钱包
                break;
            case 12:
                return '2097';//qqwap
                break;
            case 17:
                return '996';//银联扫码
                break;
            case 22:
                return '993';//财付通
                break;
            case 25:
                return '2028';//网银快捷支付
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
            'method' => 'get',
            'data' => $data,
            'url' => $this->url
        ];
        $rs['jump'] = 5;
        $rs['url'] = $this->domain . '/index.php/pay/pay_test/pay_sest/' . $this->orderNum;
        $rs['json'] = json_encode($temp, JSON_UNESCAPED_UNICODE);
        return $rs;
    }



    /**
     * 请求接口
     * @param $data
     * @return mixed
     */
    private function request($data)
    {
        $ch = curl_init();  
        curl_setopt($ch, CURLOPT_POST, 1);  
        curl_setopt($ch, CURLOPT_URL, $this->url);  
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);  
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(  
            'Content-Type: application/json; charset=utf-8',  
            'Content-Length: ' . strlen($data))  
        );  
        ob_start();  
        curl_exec($ch);  
        $return_content = ob_get_contents();  
        ob_end_clean();  

        $return_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);  
        return $return_content;  
    }



    public function online_erro($id, $str)
    {
        $reidsKey = "online:erro:";
        $this->redis_select(4);
        $this->redis_setex($reidsKey.$id, 90000, $str);
        $this->redis_select(5);
    }

}