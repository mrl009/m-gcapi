<?php
defined('BASEPATH')or exit('No direct script access allowed');
/**
 * Created by PhpStorm.
 * User: 57207
 * Date: 2018/8/7
 * Time: 17:50
 */
include_once  __DIR__.'/Publicpay_model.php';

class Ziranfu_model extends Publicpay_model
{
    protected $c_name = 'ziranfu';
    protected $p_name = 'ZIRANFU';
    //支付接口签名参数
    private $method = 'X'; //返回签名大小写 D 大写 X 小写
    private $key_string = '&'; //参与签名组成
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
        return $this->useForm($data);
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
        // $f = $this->field;
        // $m = $this->method;
        // $k = $this->key_string . $this->key;
        // $data = get_pay_sign($data,$k,$f,$m);
        $sign = md5('version='.$data['version'].'&customerid='.$data['customerid'].'&total_fee='.$data['total_fee'].'&sdorderno='.$data['sdorderno'].'&notifyurl='.$data['notifyurl'].'&returnurl='.$data['returnurl'].'&'.$this->key);
        $data['sign'] = $sign;
        $data['bankcode'] = $this->code == 7? $this->bank_type:'';
        return $data;
    }

    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {
        $data['version'] = '1.0';//商户号
        $data['customerid'] = $this->merId;
        $data['sdorderno'] = $this->orderNum;
        $data['total_fee'] = $this->money;
        $data['paytype'] = $this->getPayType();
        $data['notifyurl'] = $this->callback;
        $data['returnurl'] = $this->returnUrl;
        return $data;
    }

    /**
     * 根据code值获取支付方式
     * @param string code
     * @return string 支付方式 参数
     */
    private function getPayType()
    {
        switch ($this->code)
        {
            case 1:
                return 'weixin';//微信扫码
                break;
            case 2:
                return 'wxwap';//微信WAP  1,5,7,8,17,18,25
                break;
            case 34:
                return 'gzhpay';//微信公众号  5,7,8,17,18,25
                break;
            case 4:
                return 'alipay';//支付宝扫码
                break;
            case 5:
                return 'alipaywap';//支付宝WAP
                break;
            case 7:
                return 'bank';//网关支付
                break;
            case 8:
                return 'qq';//QQ钱包扫码
                break;
            case 12:
                return 'qqwap';//京东扫码
                break;    
        }
    }

    /**
     * 获取支付结果
     * @param $data 支付参数
     * @return return 二维码内容
     */
    protected function getPayResult($pay_data)
    {
        // return $pay_data;
        // //传递参数
        // $pay_data = http_build_query($pay_data);
        // // var_dump($pay_data);exit;
        // $data = post_pay_data($this->url,$pay_data);
        // var_dump($data);exit;
        // if (empty($data)) $this->retMsg('接口返回信息错误！');
        // //接收参数为JSON格式 转化为数组
        // $data = json_decode($data,true);
        // if (empty($data)) $this->retMsg('接口返回信息格式错误！');
        // //判断是否下单成功
        // if (empty($data['payUrl']))
        // {
        //     $msg = isset($data['retMsg']) ? $data['retMsg'] : '返回参数错误';
        //     $this->retMsg("下单失败：{$msg}");
        // }
        // //扫码支付返回支付二维码连接地址
        // return $data['payUrl'];
    }

    protected function useForm($data)
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