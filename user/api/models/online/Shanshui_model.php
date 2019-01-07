<?php
defined('BASEPATH') or exit('No direct script access allowed');
/**
 * Created by PhpStorm.
 * User: 57207
 * Date: 2018/7/23
 * Time: 18:02
 */
include_once __DIR__.'/Publicpay_model.php';
class Shanshui_model extends Publicpay_model
{
    protected $c_name = 'shanshui';
    private $p_name = 'SHANSHUI';//商品名称
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
        if (in_array($this->code,[2,5]))
        {
            return $this->buildWap($data);
        } else {
            return $this->buildScan($data);
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
        return $data;
    }

    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {
        $data['merId'] = $this->merId;//商户号
        $data['appId'] = $this->s_num;//应用ID
        $data['orderNo'] = $this->orderNum;
        $data['totalFee'] = $this->money;
        $data['channeltype'] = $this->getPayType();
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
                return '';//微信扫码
                break;
            case 2:
                return '';//微信WAP
                break;
            case 4:
                return 'TSALI_WEB';//支付宝扫码
                break;
            case 5:
                return 'TSALI_WEB';//支付宝WAP
                break;
            default:
                return 'TSALI_WEB';//微信扫码
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
        if (empty($data['retCode']) && 100<>$data['retCode']&&
            empty($data['qrcode']))
        {
            $msg = isset($data['retMsg']) ? $data['retMsg'] : '返回参数错误';
            $this->retMsg("下单失败：{$msg}");
        }
        //扫码支付 wap支付返回支付 实际地址
        if(in_array($this->code,[2,5]))
        {
            $string = explode(':',$data['qrcode']);
            $pay_url = strtolower($string[0]).':'.$string[1];
        }else{
            $pay_url = $data['qrcode'];
        }
        return $pay_url;
    }
    /**
     * 获取支付结果
     */

}