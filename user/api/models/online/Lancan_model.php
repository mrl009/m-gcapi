<?php
defined('BASEPATH')or exit('No direct script access allowed');
/**
 * Created by PhpStorm.
 * User: 57207
 * Date: 2018/9/20
 * Time: 18:36
 */
include_once  __DIR__.'/Publicpay_model.php';
class Lancan_model extends Publicpay_model
{
    protected $c_name = 'lancan';
    private $p_name = 'LANCAN';//商品名称
    //支付接口签名参数
    private $method = 'X'; //返回签名大小写 D 大写 X 小写
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
    {       //wap支付
        if (in_array($this->code,[5]))
        {
            return $this->buildWap($data);
        }else{
            return $this->useForm($data);
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
        $data = get_pay_sign($data,$k,$f,$m);
        $data['goodsName'] = $this->p_name;
        if (7 == $this->code) $data['bankId'] = $this->bank_type;
        return $data;
    }

    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {
        $data['userId'] = $this->merId;//商户号
        $data['orderNo'] = $this->orderNum;
        $data['tradeType'] = $this->getPayType();
        $data['payAmt'] = $this->money;
        $data['returnUrl'] = $this->returnUrl;
        $data['notifyUrl'] = $this->callback;
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
                return '01';//微信扫码
                break;
            case 2:
                return '02';//微信WAP
                break;
            case 4:
                return '11';//支付宝扫码
                break;
            case 5:
                return '12';//支付宝WAP
                break;
            case 7:
                return '41';//网关支付
                break;
            case 8:
                return '21';//QQ钱包扫码
                break;
            case 9:
                return '31';//京东扫码
                break;
            case 17:
                return '71';//银联钱包
                break;
            case 18:
                return '51';//银联WAP
                break;
            case 25:
                return '61';//快捷(银联PC)
                break;
            default:
                return '01';//微信扫码
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
        //传递参数
        $pay_data = http_build_query($pay_data);
        $data = post_pay_data($this->url,$pay_data);
        if (empty($data)) $this->retMsg('接口返回信息错误！');
        //接收参数为JSON格式 转化为数组
        $data = json_decode($data,true);
        if (empty($data)) $this->retMsg('接口返回信息格式错误！');
        //判断是否下单成功
        if (empty($data['payUrl']))
        {
            $msg = isset($data['retMsg']) ? $data['retMsg'] : '返回参数错误';
            $this->retMsg("下单失败：{$msg}");
        }
        //扫码支付返回支付二维码连接地址
        return $data['payUrl'];
    }
}