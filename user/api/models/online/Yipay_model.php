<?php
/**
 * 易支付新接口调用
 * Created by PhpStorm.
 * User: Tailand
 * Date: 2018/12/24
 * Time: 16:13
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';
class Yipay_model extends Publicpay_model
{
    protected $c_name = 'yipay';
    private $p_name = 'YIPAY';//商品名称

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
        if (in_array($this->code,[36])) {
            return $this->buildWap($data);
            //扫码支付
        }else if (in_array($this->code,[41])) {
            return $this->buildScan($data);
            //网银支付快捷支付和收银台 (部分接口不通用)
        } else{
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
        ksort($data);
        $string = data_to_string($data) . $this->key;
        $data['sign_type'] = 'MD5';
        $data['sign'] = md5($string);

        return $data;
    }

    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {
        $data['pid'] = $this->merId;
        $data['type'] = $this->getPayType();
        $data['out_trade_no'] = $this->orderNum;
        $data['money'] = $this->money;
        $data['name'] = $this->p_name;
        $data['notify_url'] = $this->callback;
        $data['return_url'] = $this->returnUrl;
        return $data;
    }

    /**
     * 根据code值获取支付方式
     * @param string code
     * @return string 聚合付支付方式 参数
     */
    private function getPayType()
    {
        switch ($this->code)
        {
            case 1:
            case 2:
                return 'wechat2';//微信
                break;
            case 4:
            case 5:
                return 'alipay2';//支付宝
                break;
            case 36:
            case 41:
                return 'alipay2qr';//支付宝
                break;
            default:
                return 'alipay2';//支付宝
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
        //传递参数为STRING格式
        $pay_data = http_build_query($pay_data);
        $data = post_pay_data($this->url,$pay_data);
        if (empty($data)) $this->retMsg('接口返回信息错误！');
        //转化为json数据
        $data = json_decode($data,true);
        if (empty($data)) $this->retMsg('接口返回信息格式错误！');
        //判断下单是否成功
        if (empty($data['payurl']))
        {
            $msg = isset($data['msg']) ? $data['msg'] : '返回参数错误';
            $this->retMsg("下单失败：{$msg}");
        }
        return $data['payurl'];
    }
}