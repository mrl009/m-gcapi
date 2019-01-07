<?php
/**
 * 通达付
 * date:2018-05-31
 */

class Tongdafu_model extends MY_Model
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
        $this->callback = $this->domain . '/index.php/callback/tongdafu/callbackurl';//回调地址
        $data = $this->fzData($pay_data);
        return $this->buildForm($data);
        // $return = $this->request($data);
        // var_dump($return);exit;
    }
    
    //.组装数据
    private function fzData($pay_data)
    {
        $code = isset($pay_data['code']) ? $pay_data['code'] : 0;
        //.wap
        $data['p0_Cmd'] = 'Buy';//.业务类型
        $data['p1_MerId'] = $this->merId;//.商户编号
        $data['p2_Order'] =  $this->orderNum;//.商户订单号
        $data['p3_Amt'] = $this->money;//.支付金额
        $data['p4_Cur'] =  'CNY';//。交易币种
        $data['p5_Pid'] = 'TDZF';//。商品名称
        $data['p6_Pcat'] = 'TDZF';//。商品种类
        $data['p7_Pdesc'] = 'TDZF';//.商品描述
        $data['p8_Url'] = $this->callback;//.异步回掉地址
        $data['pa_MP'] =  $this->merId;//.支付通道编码
        $data['pd_FrpId'] = $this->getType($code,$pay_data['bank_type']);//.订单号
        $data['pr_NeedResponse'] =  '1';//。应答机制
        $data['open_id'] = '';//。微信open_id
        $data['is_phone'] = '';//。手机端
        $data['bank_type'] =  $code==7? 1:'';//。银行卡类型
        $data['hmac'] =$this ->sign($data);//。支付金额
        return   $data;
    }


    //.支付方式做映射
    private function getType($code,$bankType)
    {
         switch ($code) {
            case 1:
                return 'weixin';//微信扫码
                break;
            case 2:
                return 'wxwap';//微信wap
                break;
            case 22:
                return 'tenpay';//财付通扫码支付
                break;
            case 8:
                return 'qqcode';//qq扫码
                break;
            case 7:
                return $bankType;//网银支付
                break;
            case 12:
                return 'qqwap';//qqwap
                break;
            case 4:
                return 'alipay';//支付宝扫码
                break;
            case 5:
                return 'alipaywap';//支付宝wap
                break;
            case 9:
                return 'jdcode';//京东扫码
                break;
            case 17:
                return 'ylcode';//银联扫码
                break;
            case 18:
                return 'bankwap';//银联wap
                break;
            case 25:
                return 'Ylquick';//快捷
                break;
        }
    }


     /**
     * 签名
     * @param array $data 表单内容
     * @return array
     */
    private function sign($data)
    {
        $key = iconv("GB2312","UTF-8",$this->key);
        $str ="{$data['p0_Cmd']}{$data['p1_MerId']}{$data['p2_Order']}{$data['p3_Amt']}{$data['p4_Cur']}{$data['p5_Pid']}{$data['p6_Pcat']}{$data['p7_Pdesc']}{$data['p8_Url']}{$data['pa_MP']}{$data['pd_FrpId']}{$data['pr_NeedResponse']}";
        $str  = iconv("GB2312","UTF-8",$str);
        $b = 64; // byte length for md5
        if (strlen($key) > $b) {
            $key = pack("H*",md5($key));
        }
        $key = str_pad($key, $b, chr(0x00));
        $ipad = str_pad('', $b, chr(0x36));
        $opad = str_pad('', $b, chr(0x5c));
        $k_ipad = $key ^ $ipad ;
        $k_opad = $key ^ $opad;
        return md5($k_opad . pack("H*",md5($k_ipad . $str)));
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
    

    public function online_erro($id, $str)
    {
        $reidsKey = "online:erro:";
        $this->redis_select(4);
        $this->redis_setex($reidsKey.$id, 90000, $str);
        $this->redis_select(5);
    }

}