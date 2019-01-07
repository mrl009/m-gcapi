<?php
/**
 * 易付支付模块
 * date:2018-04-09
 */

class Pingguo_model extends MY_Model
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
        $this->money = $money*100;//支付金额 分
        $this->merId = isset($pay_data['pay_id']) ? trim($pay_data['pay_id']) : '';//商户ID
        $this->key = isset($pay_data['pay_key']) ? $pay_data['pay_key'] : '';//密钥
        $this->url = isset($pay_data['pay_url']) ? $pay_data['pay_url'] : '';//请求地址
        $this->domain = isset($pay_data['pay_domain']) ? $pay_data['pay_domain'] : '';
        $this->callback = $this->domain . '/index.php/callback/pingguo/callbackurl';//回调地址
        $data = $this->fzData($pay_data);
        $return_data = $this->request($data);
        $return_data = json_decode($return_data,true);
        if ($return_data['status'] != '0') {
            echo json_encode(array('code' => E_OP_FAIL, 'msg' => $return_data['msg']));
            exit;
        }
        if(in_array($pay_data['code'], [1, 4, 8, 9,10,17,19,25,38])){
            $res = [
                'jump' => 3,
                'img' => $return_data['code_url'],
                'money' => $money,
                'order_num' => $order_num,
            ];
            // $res = [
            //     'url' => urldecode($return_data['code_img_url']),
            //     'jump' => 5
            // ];
        }
        if(in_array($pay_data['code'], [2,5,7,12,15,16,19,21,26,33,36])){
             $res = [
                'url' => urldecode($return_data['code_url']),
                'jump' => 5
            ];

        }
        return $res;

    }
    
    //.组装数据
    private function fzData($pay_data)
    {
        $code = isset($pay_data['code']) ? $pay_data['code'] : 0;
        $data['mer_id'] = $this->merId;//.商户号
        $data['out_trade_no'] = $this->orderNum;//.订单号
        $data['pay_type'] =$this->getType($pay_data['code'],$pay_data['bank_type']);//。支付方式
        $data['goods_name'] = 'SPMS';//.商品描述
        $data['total_fee']=$this->money;//。支付金额
        $data['callback_url']=$_SERVER['HTTP_REFERER']?$_SERVER['HTTP_REFERER']:$pay_data['pay_return_url'];//.异步通知地址
        $data['notify_url']=$this->callback;//.异步通知地址
        $data['nonce_str']=substr(str_shuffle("abcdefghijklmnopqrstuvwxyz0123456789"), 0, 32);//.异步回掉地址
        $data['sign']=$this->sign($data);//.异步回掉地址
        return   $data;



    }

    private function sign($data)
    {
        $str = "mer_id={$data['mer_id']}&nonce_str={$data['nonce_str']}&out_trade_no={$data['out_trade_no']}&total_fee={$data['total_fee']}&key={$this->key}";
        return  md5(trim($str,''));
    }





    //.支付方式做映射
    private function getType($code,$banktype)
    {
        switch ($code) {
            case 1:
                return '002';//.微信你扫码
                break;
            case 5:
                return '005';//。支付宝wap
                break;
            case 4:
                return '006';//.支付宝扫码
                break;
            case 8:
                return '011';//。qq扫码
                break;
            case 7:
                return '008';//.网关支付
                break;
        }
    }





    /**
     * 请求接口
     * @param $data
     * @return mixed
     */
    private function request($data)
    {

        $this->url .= '?';
        $this->url .= http_build_query ($data);
        // var_dump($this->url);exit;
        $ch = curl_init ();
        $user_agent = $_SERVER ['HTTP_USER_AGENT'];
        $header = array (
                "User-Agent: $user_agent" 
        );
        $ch = curl_init ();
        curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt ( $ch, CURLOPT_URL, $this->url );
        curl_setopt ( $ch, CURLOPT_HTTPHEADER, $header );
        $response = curl_exec ( $ch );
        if ($error = curl_error ( $ch )) {
            die ( $error );
        }
        curl_close ( $ch );
        return $response;
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