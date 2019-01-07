<?php
/**
 * 易付支付模块
 * date:2018-04-09
 */

class Yifu_model extends MY_Model
{
    public $key;
    public $merId;
    public $sn;
    public $orderNum;
    public $money;
    public $url;
    public $callback;
    public $domain;

    public function call_interface($order_num, $money, $pay_data)
    {
        $this->orderNum = $order_num;//订单号
        $this->money = $money*100;//支付金额
        $this->merId = isset($pay_data['pay_id']) ? trim($pay_data['pay_id']) : '';//商户ID
        $this->sn = isset($pay_data['pay_server_num']) ? trim($pay_data['pay_server_num']) : '';//终端序列号
        $this->key = isset($pay_data['pay_key']) ? $pay_data['pay_key'] : '';//密钥
        $this->url = isset($pay_data['pay_url']) ? $pay_data['pay_url'] : '';//请求地址
        $this->domain = isset($pay_data['pay_domain']) ? $pay_data['pay_domain'] : '';
        $this->callback = $this->domain . '/index.php/callback/yifu/callbackurl';//回调地址
        // 组装数据
        $data = $this->fzData($pay_data);
        $return_data=$this->request($data);
        $return_data=json_decode($return_data,true);
        if ($return_data['respcd'] != "0000" ) {
            echo json_encode(array('code' => E_OP_FAIL, 'msg' => $return_data['respmsg']));
            exit;
        }
        if (in_array($pay_data['code'], [1, 4, 8, 9,10,17,19,38])) {
            $res = [
                'jump' => 3,
                'img' => $return_data['data']['pay_params'],
                'money' => $money,
                'order_num' => $order_num,
            ];
        } elseif (in_array($pay_data['code'], [2, 5,12])) {
            $res = [
                'url' => urldecode($return_data['data']['pay_params']),
                'jump' => 5
            ];
        } elseif (in_array($pay_data['code'],[7,15,16,19,21,26,33,36])) {
             $res = [
                'url' => urldecode($return_data['data']['pay_params']),
                'jump' => 5
            ];
        }
        return $res;

    }
    
    //.组装数据
    private function fzData($pay_data)
    {
        $code = isset($pay_data['code']) ? $pay_data['code'] : 0;
        $data['src_code']=$this->sn;//.商户唯一标识 序列号 
        $data['out_trade_no']=$this->orderNum;//。订单号
        $data['total_fee']=$this->money;//。订单总金额
        $data['time_start']=date('YmdHis');//。发起时间
        $data['goods_name']="我中了头奖";//.商品名称
        $data['trade_type']=$this->getType($code);//.交易类型
        $data['finish_url']=$this->callback;
        $data['mchid']=$this->merId;
        if($pay_data['code'] == 7){
            $data['extend'] ="{'bankName':".$pay_data['bank_type'].",'cardType':'借记卡'}";
        }
        $data['sign']=$this->sign($data);
        return   $data;


    }


    //.支付方式做映射
    private function getType($code)
    {
         switch ($code) {            
            case 1:
                return '50104';//微信扫码
                break;
            case 2:
                return '50107';//微信wap
                break;
            case 4:
                return '60104';//支付宝扫码
                break;
            case 7:
                return '80103';//网关支付
                break;
            case 8:
                return '40104';//qq钱包扫码
                break;
            case 9:
                return '92104';//京东扫码
                break;
            case 10:
                return '93104';//百度扫码
                break;
            case 12:
                return '40107';//qq钱包wap
                break;
            case 19:
                return '30104';//银联二维码
                break;
            case 26:
                return '80101';//收银台
                break;
            case 33:
                return '50103';//微信公众号
                break; 
            case 38:
                return '94104';//苏宁扫码
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
            if($key!='sign'&&$val!=''){
                $md5str.=$key.'='.$val.'&';
            }
        }
        $signStr = trim($md5str,' ').'key='. $this->key;
        // var_dump($signStr);exit;
        return strtoupper(md5($signStr));
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