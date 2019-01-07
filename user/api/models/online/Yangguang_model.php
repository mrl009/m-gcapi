<?php
/**
 * 阳光支付接口调用
 * User: lqh
 * Date: 2018/07/30
 * Time: 10:20
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';

class Yangguang_model extends Publicpay_model
{
    protected $c_name = 'yangguang';
    private $p_name = 'YANGGUANG';//商品名称
    //支付接口签名参数 

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
        $params = $this->getBaseData();
        //构造签名参数
        $string = ToUrlParams($params);
        $params['ordername'] = $this->p_name;
        $params['orderinfo'] = $this->p_name;
        $params['paymoney'] = $this->money;
        $params['orderuid'] = $this->user['id'];
        $params['paytype'] = $this->getPayType();
        $params['notifyurl'] = $this->callback;
        $params['returnurl'] = $this->returnUrl;
        $params['payCodeType'] = 'URL';
        $params['sign'] = md5($string);
        $params['isSign'] = 'Y';
        $params['signType'] = 'MD5';
        //构造支付参数
        $data['appid'] = $this->merId;
        $data['params'] = data_to_string($params);
        $data['isEncryption'] = 'N';
        return $data;
    }
    
    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {
        $data['appid'] = $this->merId;
        $data['orderid'] = $this->orderNum;
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
            case 4:
            case 5:
                return '11';//支付宝扫码
                break; 
            default:
                return '11';//支付宝扫码
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
        if (empty($data['data']['payCode']))
        {
            $msg = '返回信息错误';
            if (isset($data['status']) && (500 == $data['status']))
            {
                $msg = '接口获取数据超时';
            }
            if (isset($data['msg'])) $msg = $data['msg'];
            $this->retMsg("下单失败：{$msg}");
        }
        return $data['data']['payCode'];
    }
}
