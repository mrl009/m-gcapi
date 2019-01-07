<?php
/**
 * 千禧翼达支付接口调用
 * Created by PhpStorm.
 * User: Tailand
 * Date: 2018/11/24
 * Time: 13:51
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';
class Qianxiyida_model extends Publicpay_model
{
    protected $c_name = 'qianxiyida';
    private $p_name = 'QXYD';
    //支付接口签名参数
    private $method = 'D'; //返回签名大小写 D 大写 X 小写
    private $key_string = '&key='; //参与签名组成
    private $field = 'sign'; //签名参数名

    public function __construct()
    {
        parent::__construct();
    }
    /**
     * 获取前端返回数据 部分第三方支付不一样
     * @param array
     * @return array
     */
    protected function returnApiData($data)
    {
        if (in_array($this->code,$this->scan_code)) {
            return $this->buildScan($data);
            //网银支付快捷支付和收银台 (部分接口不通用)
        } else {
            return $this->buildWap($data);
        }
    }
    /**
     * 构造支付参数+sign值
     * @return array
     */
    protected function getPayData()
    {
        //构造基本参数
        $data = $this->getBaseData();
        //构造签名参数
        $f = $this->field;
        $m = $this->method;
        $k = $this->key_string . $this->key;
        ksort($data);
        $string = ToUrlParams($data);
        $data['sign'] = $this -> get_sign($string, $this->p_key);
        return $data;
    }

    //组装参数
    protected function getBaseData(){
        //.wap
        $data['seller_id']=$this->merId;//.商户号
        $data['order_type'] = $this->getPayType();//支付类型
        $data['pay_body']   = $this->p_name ;//商品描述
        $data['out_trade_no']= $this->orderNum;//订单号
        $data['total_fee']   = yuan_to_fen($this->money);//支付金额单位为分
        $data['notify_url']  = $this->callback;//回调地址
        $data['return_url']=$this->returnUrl;
        $data['spbill_create_ip']= get_ip();//客户端ip
        $data['spbill_times'] = $_SERVER['REQUEST_TIME'];//。系统时间戳
        $data['noncestr'] = create_guid();//随机数
        $data['remark']   = $this->c_name;
        return   $data;
    }

    //根据编码映射第三方对应的编码通道
    private function getPayType()
    {
        switch ($this->code) {
            case 1:
                return '2701';//微信扫码
                break;
            case 4:
                return '2702';//支付宝二维码
                break;
            case 7:
                return '2704';//网银网关
                break;
            case 8:
                return '2705';//QQ钱包
                break;
            case 12:
                return '2707';//qqh5
                break;
            case 15:
                return '2709';//京东钱包
                break;
            case 17:
                return '2711';//银联扫码
                break;
            case 20:
                return '2710';//百度WAP
                break;
            case 25:
                return '2703';//银联快捷
                break;
            case 33:
                return '2706';//微信h5
                break;
            case 36:
                return '2708';//支付宝h5
                break;
            default:
                return '2702';
                break;
        }
    }
    protected function  get_sign($data, $privateKey,$code = 'base64'){
        $privateKey = openssl_get_privatekey($privateKey);

        if (openssl_sign($data, $ret, $privateKey,OPENSSL_ALGO_MD5)){
            $ret =  base64_encode(''.$ret);
        }
        openssl_free_key($privateKey);
        return $ret;
    }


    /**
     * 获取支付结果
     * @param $data 支付参数
     * @return return 二维码内容
     */
    protected function getPayResult($pay_data)
    {
        $str = base64_encode(json_encode($pay_data,JSON_UNESCAPED_SLASHES));
        $t = array(
            'Content-Type: application/json; charset=utf-8',
            'Content-Length: ' . strlen($str)
        );
        $data = post_pay_data($this->url,$str,$t);
        if (empty($data)) $this->retMsg('接口返回信息错误！');
        //接收参数为JSON格式 转化为数组
        $data = json_decode($data,true);
        if (empty($data)) $this->retMsg('接口返回信息格式错误！');
        //判断是否下单成功
        if (empty($data['pay_url'])|| '00'<>$data['state'])
        {
            $msg = isset($data['return_msg']) ? $data['return_msg'] : '返回参数错误';
            $this->retMsg("下单失败：{$msg}");
        }
        return $data['pay_url'];
    }
}