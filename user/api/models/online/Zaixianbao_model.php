<?php
/**
 * 在线宝支付模块
 * date:2018-04-14
 */

class Zaixianbao_model extends MY_Model
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
        $this->money = $money;//支付金额
        $this->merId = isset($pay_data['pay_id']) ? trim($pay_data['pay_id']) : '';//商户ID
        $this->key = isset($pay_data['pay_key']) ? $pay_data['pay_key'] : '';//密钥   
        if(in_array($pay_data['code'], [1,4,8,9,10,33,36])){
            //在线宝扫码
            $this->url='http://p.1wpay.com/a/passivePay';
        }elseif(in_array($pay_data['code'], [2,5,12,13,20])){
            //。在线宝wap跳转
            $this->url='http://p.1wpay.com/a/wapPay';
        }
        $this->domain = isset($pay_data['pay_domain']) ? $pay_data['pay_domain'] : '';
        $this->callback = $this->domain . '/index.php/callback/zaixianbao/callbackurl';//回调地址
        // 组装数据
        $data = $this->fzData($pay_data);
        $return_data=$this->request($data);
        $return_data = iconv('GBK', 'UTF-8',$return_data);
        $return_data=json_decode($return_data,true);
        if ($return_data['respCode'] != "00" ) {
            echo json_encode(array('code' => E_OP_FAIL, 'msg' => $return_data['message']));
            exit;
        }
        if (in_array($pay_data['code'], [1, 4, 8, 9,10,17,19,38])) {
            $res = [
                'jump' => 3,
                'img' => $return_data['barCode'],
                'money' => $money,
                'order_num' => $order_num,
            ];
        } elseif (in_array($pay_data['code'], [2, 5,12,13,20,33,36])) {
            $res = [
                'url' => urldecode($return_data['barCode']),
                'jump' => 5
            ];
        } 
        return $res;

    }
    
    //.组装数据
    private function fzData($pay_data)
    {
        $code = isset($pay_data['code']) ? $pay_data['code'] : 0;
        //.wap
        $data['merchno']=$this->merId;//.商户号
        $data['traceno']=$this->orderNum;//.订单号
        $data['amount']=$this->money;//.交易金额
        $data['payType']=$this->getType($code);//.支付方式
        $data['goodsName']='通扫支付模块';
        $data['notifyUrl']=$this->callback;
        $data['signature']=$this->sign($data);
        // var_dump($data);exit;
        return   $data;


    }


    //.支付方式做映射
    private function getType($code)
    {
         switch ($code) {
            case 1:
                return '2';//微信扫码
                break;
            case 2:
                return '2';//微信WAP
                break;
            case 4:
                return '1';//支付宝扫码
                break;
            case 8:
                return '4';//QQ扫码
                break;
            case 9:
                return '5';//京东扫码
                break;
            case 10:
                return '3';//百度扫码
                break;
            case 20:
                return '3';//百度WAP
                break;
            case 33:
                return '2';//微信h5(扫码)
                break;
            case 36:
                return '1';//.支付宝h5
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
        ksort($data);
        $md5str = '';
        foreach ($data as $key => $val) {
            $md5str.=$key.'='.$val.'&';
        }
        $signStr = trim($md5str,' ').$this->key;
        return strtoupper(md5($signStr));
    }



    /**
    *去数组空值
    *@param 数组
    */
    private function dekong($data)
    {
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
            'method' => 'post',
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

}