<?php
/**
 * Created by PhpStorm.
 * User: mrl
 * Date: 2018/3/21
 * Time: 下午6:36
 */

class Sufuwechat_model extends MY_Model
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
        $this->money =(float) $money;//支付金额
        $this->merId = isset($pay_data['pay_id']) ? trim($pay_data['pay_id']) : '';//商户ID
        $this->key = isset($pay_data['pay_key']) ? $pay_data['pay_key'] : '';//密钥
        $this->url = isset($pay_data['pay_url']) ? $pay_data['pay_url'] : '';//请求地址
        $this->domain = isset($pay_data['pay_domain']) ? $pay_data['pay_domain'] : '';
        $this->callback = $this->domain . '/index.php/callback/sufu/callbackurl';//回调地址
        // 组装数据
        $data = $this->getData($pay_data);
        // var_dump($data);exit;
        return $this->buildForm($data);
    }

    /**
     * 获取支付参数
     * @param array $pay_data
     * @return array
     */
    private function getData($pay_data)
    {
        $code = isset($pay_data['code']) ? $pay_data['code'] : 0;
        $bankType = isset($pay_data['bank_type']) ? $pay_data['bank_type'] : 0;
        // 请求数据赋值
        $data['notify_url'] =$this->callback;// 回调
        $data['pay_type'] = $this->getType($code);//支付方式
        $data['bank_code'] = $pay_data['bank_type'];//银行编码
        $data['merchant_code'] = $this->merId;//商户订单号
        $data['order_no'] =  $this->orderNum;//订单号
        $data['order_amount'] = (string)$this->money;// 页面返回地址
        $data['order_time'] = date('Y-m-d H:i:s');// 商户通知地址
        $data['req_referer'] = $this->domain;
        $data['customer_ip'] = get_ip();
        $data['sign'] = $this->sign($data);
        return $data;
    }

    private function getType($code)
    {
        switch ($code) {
            case 7:
                return '1';//网银支付
                break;
            case 1:
                return '2';//微信支付扫码
                break;
            case 2:
                return '2';//微信支付wap
                break;
            case 33:
                return '2';//微信支付h5
                break;
             case 4:
                 return '3';//支付宝扫码
                 break;
             case 5:
                 return '3';//支付宝wap
                 break;
             case 36:
                 return '3';//支付宝h5
                 break;
             case 8:
                 return '5';//qq 扫码
                 break;
             case 12:
                 return '5';//qq钱包
                 break;
             case 16:
                 return '5';//qq
                 break;
            /* case 9:
                 return '6';//京东扫码
                 break;
             case 13:
                 return '6';//京东wap
                 break;
             case 15:
                 return '6';//京东二维码
                 break;*/
            case 40:
                  return '6';//微信条码
                  break;
             /*case 17:
                 return '7';//银联
                 break;*/
            default:
                return '2';
            
        }
    }

    /**
     * 获取支付签名
     * @param string $data 支付参数
     * @return string $sign签名值
     */
    public function sign($data)
    {
        ksort($data);
        $arg = "";
        foreach ($data as $k => $v) {
            if ($k == 'sign' || $k == 'signType' || $v == '') {
                continue;
            }
            $arg .= $k . "=" . $v . "&";
        }
        if (get_magic_quotes_gpc()) {
            $arg = stripslashes($arg);
        }
        return md5($arg .'key='.$this->key);
    }

    /**
     * 发送请求
     * @param $data
     * @return mixed
     */
    private function request($data)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $rs = curl_exec($ch);
        curl_close($ch);
        return $rs;
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