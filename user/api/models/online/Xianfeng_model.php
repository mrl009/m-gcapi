<?php
/**
 * 先疯支付接口调用
 * User: lqh
 * Date: 2018/07/30
 * Time: 10:20
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';

class Xianfeng_model extends Publicpay_model
{
    protected $c_name = 'xianfeng';
    private $p_name = 'XIANFENG';//商品名称
    //支付接口签名参数 
    private $ks = '&key='; //连接秘钥字符
    private $method = 'X'; //返回签名大小写 D 大写 X 小写
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
        return $this->buildWap($data);
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
        $k = $this->ks . $this->key;
        $data = get_pay_sign($data,$k,$f,$m);
        return $data;
    }
    
    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {
        $data['merchant'] = $this->merId;
        $data['m_orderNo'] = $this->orderNum;
        $data['tranAmt'] = $this->money;
        $data['pname'] = $this->p_name;
        $data['notifyUrl'] = $this->callback;
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
                return 'weixin';//微信扫码
                break;
            case 2:
                return 'weixinh5';//微信Wap/h5
                break;
            case 4:
                return 'alipay';//支付宝扫码
                break; 
            case 5:
                return 'alipayh5';//支付宝WAP
                break;
            case 7:
                return 'gateway';//网银支付
                break;
            case 8:
                return 'tenpay';//QQ扫码
                break;
            case 9:
                return 'jdpay';//京东扫码
                break;
            case 12:
                return 'tenpayh5';//QQwap
                break;
            case 13:
                return 'jdh5';//京东wap
                break;
            case 17:
                return 'union';//银联钱包
                break;
            case 18:
                return 'unionh5';//银联wap
                break;
            case 25:
                return 'shortcut';//快捷
                break;
            default:
                return 'alipay';//支付宝扫码
                break;
        }
    }

    /**
     * 获取支付网关地址 部分接口地址不唯一
     * @param array $pay 支付参数
     * @return array
     */
    protected function getPayUrl($pay)
    {
        $pay_url = '';
        if (!empty($pay['pay_url']))
        {
            $pay_url = trim($pay['pay_url']);
        }
        $method = $this->getPayType();
        $pay_url .= "?method={$method}";
        return $pay_url;
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
        if (empty($data['retMsg']['paymentInfo']))
        {
            $msg = isset($data['retMsg']) ? $data['retMsg'] : '返回信息错误';
            $this->retMsg("下单失败：{$msg}");
        }
        return $data['retMsg']['paymentInfo'];
    }
}
