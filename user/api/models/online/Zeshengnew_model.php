<?php
/**
 * 通扫支付模块
 * date:2018-04-14
 */

class Zeshengnew_model extends MY_Model
{
    //.扫码支付网关
    const URL_SAOMA = "http://gateway.clpayment.com/scan/entrance.do"; 
    //.网银支付网关
    const URL_WANGGUAN = "http://gateway.clpayment.com/ebank/pay.do"; 
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
        $this->money = strval($money*100);//支付金额
        $this->merId = isset($pay_data['pay_id']) ? trim($pay_data['pay_id']) : '';//商户ID
        $this->key = isset($pay_data['pay_key']) ? $pay_data['pay_key'] : '';//密钥
        $this->url = isset($pay_data['pay_url']) ? $pay_data['pay_url'] : '';//请求地址
        $this->domain = isset($pay_data['pay_domain']) ? $pay_data['pay_domain'] : '';
        $this->callback = $this->domain . '/index.php/callback/zeshengnew/callbackurl';//回调地址
        if($pay_data['code'] == 7) {
            $data = $this->BankData($pay_data);
            $this->url =self::URL_WANGGUAN;
            return $this ->buildForm($data);
        } 
        if(in_array($pay_data['code'],[1,4,8,9,17])){
            $data = $this->H5Data($pay_data);
            // var_dump($data);exit;
            $this->url =self::URL_SAOMA;
            $return_data = $this->request($data);
            if($return['code'] != '00'){
                echo json_encode(array('code' => E_OP_FAIL, 'msg' => $return['msg']));
                exit;
            }
            $res = [
                'jump' => 3,
                'img' => $return['url'],
                'money' => $data['amount'],
                'order_num' => $order_num,
            ];
            return  $res;
        }

    }
    
    //.组装扫码数据
    private function H5Data($pay_data)
    {
        $code = isset($pay_data['code']) ? $pay_data['code'] : 0;
        //.wap
        $data['model']='QR_CODE';//.模块
        $data['merchantCode']=$this->merId;//.商户号
        $data['outOrderId']=$this->orderNum;//.订单号
        $data['amount']=$this->money;//.支付金额
        $data['orderCreateTime']=date('YmdHis');//.订单时间
        $data['noticeUrl']=$this->callback;//。异步回掉
        $data['isSupportCredit']='1';//。是否支持信用卡
        $data['payChannel']=$this->getType($code,$pay_data['bankType']);//。支付金额
        $data['ip']=get_ip();//。支付金额
        $data['sign']=$this->sign($data,$code);//。支付金额
        return   $data;
    }


    //.组装网银数据
    private function BankData($pay_data)
    {
        $code = isset($pay_data['code']) ? $pay_data['code'] : 0;
        //.wap
        $data['merchantCode']=$this->merId;//.商户号
        $data['outOrderId']=$this->orderNum;//.订单号
        $data['totalAmount']=$this->money;//.支付金额
        $data['orderCreateTime']=date('YmdHis');//.订单时间
        $data['merUrl']=$this->domain;//.商户取货url
        $data['noticeUrl']=$this->callback;//。异步回掉
        $data['bankCode']=$pay_data['bank_type'];//。异步回掉
        $data['bankCardType']='01';//。是否支持信用
        $data['sign']=$this->sign($data,$code);//。支付金额
        return   $data;
    }



    //.支付方式做映射
    private function getType($code,$bankType)
    {
         switch ($code) {
            case 1:
                return '21';//微信扫码
                break;
            case 4:
                return '30';//支付宝扫码
                break;
            case 8:
                return '31';//qq钱包扫码
                break;
            case 9:
                return '39';//京东扫码
                break;
            case 7:
                return $bankType;//网银支付
                break;
            case 17:
                return '38';//银联扫码
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
            'method' =>'post',
            'data' => $data,
            'url' => $this->url
        ];
        $rs['jump'] = 5;
        $rs['url'] = $this->domain . '/index.php/pay/pay_test/pay_sest/' . $this->orderNum;
        $rs['json'] = json_encode($temp, JSON_UNESCAPED_UNICODE);
        return $rs;
    }

    /* 构建签名原文 */
    private function sign($data,$code)
    {
        if($code == 7){
            $str="lastPayTime=&merchantCode={$data['merchantCode']}&orderCreateTime={$data['orderCreateTime']}&outOrderId={$data['outOrderId']}&totalAmount={$data['totalAmount']}&KEY={$this->key}"; 
        }
        if(in_array($code, [1,4,8,9,17])) {
            // 参与签名字段
            $sign_fields1 = [
                "merchantCode",
                "outOrderId",
                "amount",
                "orderCreateTime",
                "noticeUrl",
                "isSupportCredit"
            ];
            sort($sign_fields1);
            $str = "";
            foreach ($sign_fields1 as $field) {
                $str .= $field . "=" . $data[$field] . "&";
            }
            $str .= "KEY=" . $this->key;
        }
        $sign = strtoupper(md5($str));
        return $sign;
    }



    /**
     * 请求接口
     * @param $data
     * @return mixed
     */
    private function request($data)
    {
        $data = http_build_query($data);
        $options = array(
                'http' => array(
                'method' => 'POST',
                'header' => 'Content-type:application/x-www-form-urlencoded',
                'content' => $data,
                'timeout' => 15 * 60
            ) // 超时时间（单位:s）

        );
        $context = stream_context_create($options);
        $result = file_get_contents($this->url, false, $context);
        return $result;
    }



    public function online_erro($id, $str)
    {
        $reidsKey = "online:erro:";
        $this->redis_select(4);
        $this->redis_setex($reidsKey.$id, 90000, $str);
        $this->redis_select(5);
    }

}