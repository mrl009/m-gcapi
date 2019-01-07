<?php
defined('BASEPATH') or exit('No direct script access allowed');
/**
 * Created by PhpStorm.
 * User: 57207
 * Date: 2018/7/16
 * Time: 9:59
 */
include_once __DIR__.'/Publicpay_model.php';

class Quanyuntong_model extends Publicpay_model
{
    protected $c_name = 'quanyuntong';
    private $p_name = 'QUANYUNTONG';//商品名称

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
        } else {
            return $this->buildForm($data);
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
        $string = ToUrlParams($data);
        $data['hmac'] = $this->HmacMd5($string,$this->key);
        return $data;
    }

    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {

        $data['p0_Cmd'] = 'Buy'; //接口版本号
        $data['p1_MerId'] = $this->merId;//商户号
        $data['p2_Order'] = $this->orderNum;
        $data['p3_Amt'] = $this->money;
        $data['p4_Cur'] = 'CNY';
        $data['p5_Pid'] = 'PayGou';
        $data['p6_Pcat'] = time();
        $data['p7_Pdesc'] = 'QYTPAY';
        $data['p8_Url'] = $this->callback;
        $data['pa_MP'] = $this->p_name;
        $data['pd_FrpId'] = $this->getPayType();
        $data['pr_NeedResponse'] = "1";
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
                return 'weixin';//微信
                break;
            case 2:
                return 'wxwap';//微信WAP
                break;
            case 4:
                return 'alipay';//支付宝扫码
                break;
            case 5:
                return 'alipaywap';//支付宝WAP
                break;
            case 7:
                return $this->bank_type;//网关支付
                break;
            case 8:
                return 'qqmobile';//QQ钱包扫码
                break;
            case 9:
                return 'jdpay';//京东扫码
                break;
            case 12:
                return 'tenpaywap';//QQWAP
                break;
            case 17:
                return 'bdpay';//银联钱包扫码
                break;
            case 25:
                return 'yinlian';//银联快捷
                break;
            case 33:
                return 'weixincode';//微信扫码
                break;
            case 36:
                return 'alipaycode';//支付宝h5扫码
                break;
            case 37:
                return 'alipaywap';//支付宝h5扫码
                break;
            default :
                return 'weixin';
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
        //传递参数为JSON格式 将数组转化成JSON格式
        $pay_data = json_encode($pay_data);
        $data = post_pay_data($this->url,$pay_data);
        if (empty($data)) $this->retMsg('接口无信息返回！');
        //接收参数为JSON格式 转化为数组
        $data = json_decode($data,true);
        if (empty($data)) $this->retMsg('接口返回信息格式错误！');
        //判断是否下单成功
        if (empty($data['payImg']) ||
            0 <> $data['status'])
        {
            $msg = isset($data['Msg']) ? $data['Msg'] : '返回参数错误';
            $this->retMsg("下单失败：{$msg}");
        }

            $pay_url = $data['payImg'];

        return $pay_url;
    }

    protected function HmacMd5($data,$key)
    {
// RFC 2104 HMAC implementation for php.
// Creates an md5 HMAC.
// Eliminates the need to install mhash to compute a HMAC
// Hacked by Lance Rushing(NOTE: Hacked means written)

//需要配置环境支持iconv，否则中文参数不能正常处理
        $key = iconv("GB2312","UTF-8",$key);
        $data = iconv("GB2312","UTF-8",$data);

        $b = 64; // byte length for md5
        if (strlen($key) > $b) {
            $key = pack("H*",md5($key));
        }
        $key = str_pad($key, $b, chr(0x00));
        $ipad = str_pad('', $b, chr(0x36));
        $opad = str_pad('', $b, chr(0x5c));
        $k_ipad = $key ^ $ipad ;
        $k_opad = $key ^ $opad;

        return md5($k_opad . pack("H*",md5($k_ipad . $data)));
    }

}