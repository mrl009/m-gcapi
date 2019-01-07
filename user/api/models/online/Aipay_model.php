<?php
defined('BASEPATH') or exit('No direct script access allowed');
include_once __DIR__.'/Publicpay_model.php';

class Aipay_model extends Publicpay_model
{
    protected $c_name = 'Aipay';
    private $p_name = 'AIPAY';//商品名称
    private $method = 'D'; //返回签名大小写 D 大写 X 小写
    private $key_string = '&key='; //参与签名组成
    private $field = 'sign'; //签名参数名

    public function __construct(){
        parent::__construct();
    }

    /**
     * 构造支付参数+sign值
     * @return array
     */
    protected function getPayData(){
        //构造基本参数
        $data = $this->getBaseData();
        $f = $this->field;
        $m = $this->method;
        $k = $this->key_string . $this->key;
        $data = $this->get_pay_sign($data,$k,$f,$m);
        return $data;
    }

    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData(){
        $data['merch_id'] = (int)$this->merId;
        $data['version'] = 10;
        $data['signtype'] = 0;
        $data['timestamp'] = number_format(microtime(true),3,'','');
        $data['norce_str'] = md5(uniqid(microtime(true),true)); //随机生成字符串
        $data['detail'] = uniqid($this->p_name.'-');
        $data['out_trade_no'] = $this->merId.$this->orderNum;
        $data['money'] = yuan_to_fen($this->money);
        $data['channel'] =$this->getPayType() ;
        $data['callback_url'] = $this->callback;
        $data['callfront_url'] = $this->returnUrl;
        $data['ip'] = get_ip();
        return $data;
    }

    private function getPayType(){
        switch ($this->code){
            case 1:
            case 2:
                return 100002;//微信
                break;
            case 4:
            case 5:
                return 100001;//支付宝
                break;
            default:
                return 100001;
                break;
        }

    }
    /**
     * 获取支付结果
     * @param $data 支付参数
     * @return return 二维码内容
     */
    protected function getPayResult($pay_data){
        $pay_data = http_build_query($pay_data);
        $return_data = post_pay_data($this->url,$pay_data);
        if (empty($return_data)) $this->retMsg('接口无信息返回！');
        //接收参数为JSON格式 转化为数组
        $return_data_arr = json_decode($return_data,true);
        //判断是否下单成功
        if ($return_data_arr['result'] <> 'SUCCESS'){
            $msg = isset($return_data_arr['msg']) ? $return_data_arr['msg'] : '返回参数错误';
            $this->retMsg("下单失败：{$msg}");
        }
        $json_data = base64_decode($return_data_arr['body']);
        $array_data = json_decode($json_data,true);

        if (empty($array_data['payurl'])){
            $this->retMsg("支付链接不可用");
        }
        $pay_url = $array_data['payurl'];
        return $pay_url;
    }

    protected function get_pay_sign($data,$ks,$fd='sign',$md='D')
    {
        if (!empty($data) && is_array($data))
        {
            ksort($data);
            //把数组参数以key=value形式拼接最后加上$ks值
            $signStr = '';
            foreach ($data as $k => $v){
                if (!is_array($v) && ('sign' <> $k)){
                    $signStr .= "{$k}={$v}&";
                }
            }
            $signStr = trim($signStr, '&');
            $signStr = $signStr . $ks;
            //拼接字符串进行MD5大写加密
            $sign = md5($signStr);
            $sign = ('D' == $md) ? strtoupper($sign) : $sign;
            $data[$fd] = $sign;
        }
        return $data;
    }
}