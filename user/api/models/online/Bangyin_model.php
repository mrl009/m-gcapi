<?php
/**
 * 北京邦银支付接口调用
 * Created by PhpStorm.
 * User: 57207
 * Date: 2018/9/23
 * Time: 21:54
 */
defined('BASEPATH')or exit('No direct script access allowed');
include_once  __DIR__.'/Publicpay_model.php';
class Bangyin_model extends Publicpay_model
{
    protected $c_name = 'bangyin';
    private $p_name = 'BANGYIN';//商品名称
    //支付接口签名参数
    private $method = 'D'; //返回签名大小写 D 大写 X 小写
    private $ks = '&'; //参与签名组成
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
        //wap支付
        if (in_array($this->code,$this->wap_code))
        {
            return $this->buildWap($data);
        //扫码支付
        }  elseif (in_array($this->code,$this->scan_code)) {
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
        ksort($data);
        $string =  $this->ks . ToUrlParams($data) . $this->key;
        $data['sign'] = strtoupper(md5($string));
        if($this->code == 7) $data['bankCode'] = $this->bank_type;//网关支付
        return $data;
    }

    /**
     * 构造支付基本参数
     * @return array
     */

    private function getBaseData()
    {
        $data['merId'] = $this->merId;//商户号
        $data['merOrderId'] = $this->orderNum;
        $data['transAmt'] = yuan_to_fen($this->money);
        $data['paymentType'] = $this->getPayType();
        $data['backUrl'] = $this->callback;//异步回调地址
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
                return 'wx_qrcode';//微信扫码
                break;
            case 2:
                return 'wx_app';//微信H5
                break;
            case 4:
                return 'ali_qrcode';//支付宝扫码
                break;
            case 5:
                return 'ali_app';//支付宝wap
                break;
            case 7:
                return 'gate_web';//网关
                break;
            case 8:
                return 'qq_qrcode';//QQ扫码
                break;
            case 9:
                return 'jd_qrcode';//京东扫码
                break;
            case 12:
                return 'qq_app';//QQwap
                break;
            case 16:
                return 'qq_h5';//QQh5
                break;
            case 17:
                return '11';//银联钱包:跳出网页扫码支付
                break;
            case 18:
                return 'gate_qrcode';//银联wap
                break;
            case 25:
                return 'gate_h5';//网银快捷
                break;
            case 34:
                return 'wx_h5';//微信h5wap
                break;
            case 37:
                return 'ali_h5';//支付宝h5wap
                break;
            default:
                return 'ali_qrcode';//支付宝扫码
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
        $pay_data = http_build_query($pay_data);
        $data = post_pay_data($this->url,$pay_data);
        if (empty($data)) $this->retMsg('接口返回信息错误！');
        //接收参数为JSON格式 转化为数组
        $data = json_decode($data,true);
        if (empty($data)) $this->retMsg('接口返回信息格式错误！');
        //判断是否下单成功
        if (empty($data['imgUrl']))
        {
            $msg = isset($data['message']) ? $data['message'] : '返回参数错误';
            $this->retMsg("下单失败：{$msg}");
        }
        return $data['imgUrl'];
    }

}