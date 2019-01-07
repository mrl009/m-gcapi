<?php
/**
 * 全银支付接口调用
 * User: lqh
 * Date: 2018/05/08
 * Time: 10:02
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';

class Quanyin_model extends Publicpay_model
{
    protected $c_name = 'quanyin';
    private $p_name = 'QYF';//商品名称
   
    //支付接口签名参数 
    private $method = 'D'; //返回签名大小写 D 大写 X 小写
    private $key_string = '&paySecret='; //参与签名组成
    private $field = 'sign'; //签名参数名*/

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 获取前端返回数据 
     * @param array
     * @return array
     */
    protected function returnApiData($data)
    {
       //网银支付 返回的是网银支付地址
       if (7 == $this->code) 
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
        $data['payKey'] = $this->merId;
        $data['productName'] = $this->p_name;
        $data['orderNo'] = $this->orderNum;
        $data['orderPrice'] = $this->money;
        $data['payWayCode'] = 'ZITOPAY';//支付方式编码 固定值
        $data['payTypeCode'] = $this->getPayType();
        $data['orderDate'] = date('Ymd');
        $data['orderTime'] = date('YmdHis');
        $data['notifyUrl'] = $this->callback;
        $data['orderPeriod'] = 10;
        //网银支付参数
        if (7 == $this->code)
        {
           $data['field5'] = $this->bank_type; 
        }
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
                return 'ZITOPAY_WX_SCAN';//微信扫码
                break;   
            case 4:
                return 'ZITOPAY_ALI_SCAN';//支付宝扫码
                break;     
            case 7:
                return 'ZITOPAY_BANK_SCAN';//网关支付
                break;
            case 8:
                return 'ZITOPAY_QQ_SCAN';//QQ钱包扫码
                break;
            case 9:
                return 'JPAY_JDPAY';//京东扫码
                break; 
            case 19:
                return 'MOBPAY_UNION_SCAN';//银联钱包公众号二维码
                break;
            default:
                return 'ZITOPAY_WX_SCAN';//支付宝扫码
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
        //传递参数为STRING格式 将数组转化成STRING格式
        $pay_data = http_build_query($pay_data);
        $data = post_pay_data($this->url,$pay_data);
        if (empty($data)) $this->retMsg('接口返回信息错误！');
        //接收参数为JSON格式 转化为数组
        $data = json_decode($data,true);
        if (empty($data)) $this->retMsg('接口返回信息格式错误！');
        //判断是否下单成功
        if (!isset($data['result']) || ('success' <> $data['result']) 
            || empty($data['code_url']))
        {
            $msg = isset($data['msg']) ? $data['msg'] : '返回参数错误';
            $this->retMsg("下单失败：{$msg}");
        }
        if (7 == $this->code)
        {
            return urldecode($data['code_url']); //地址进行解析
        } else {
            return $data['code_url'];
        }
    }
}
