<?php
/**
 * 云天支付接口调用
 * User: lqh
 * Date: 2018/05/28
 * Time: 10:02
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';

class Yuntian_model extends Publicpay_model
{
    protected $c_name = 'yuntian';
    private $p_name = 'YUNTIAN';//商品名称
    private $ks = '&';

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
        $k = $this->ks . $this->key;
        $string = ToUrlParams($data) . $k;
        $data['ip'] = get_ip();
        $data['body'] = $this->p_name;
        if (7 == $this->code) $data['type'] = $this->bank_type;
        $data['sign'] = md5($string);
        return $data;
    }
    
    /**
     * 构造支付基本参数(业务数据)
     * @return array
     */
    private function getBaseData()
    {
        $data['account'] = $this->merId;
        $data['callback'] = $this->returnUrl;
        $data['money'] = $this->money;
        $data['notify'] = $this->callback;
        $data['order'] = $this->orderNum;
        $data['paytype'] = $this->getPayType();
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
                return 'wxsmd0';//微信扫码
                break;
            case 2:
                return 'wxh5d0';//微信H5
                break;   
            case 4:
                return 'zfbsmd0';//支付宝扫码
                break;
            case 5:
                return 'zfbwapd0';//支付宝h5
                break;
            case 7:
                return $this->bank_type;//网银
                break;
            default:
                return 'zfbsmd0';//支付宝扫码
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
        //设置自定义头部信息
        $key = $this->key;
        $header = array("api-key:{$key}");
        //传递参数
        $data = post_pay_data($this->url,$pay_data,$header);
        if (empty($data)) $this->retMsg('接口返回信息错误！');
        $data = json_decode($data,true);
        if (empty($data)) $this->retMsg('接口返回信息格式错误！');
        //确认下单是否成功
        if (empty($data['payurl']))
        {
            $msg = isset($data['msg']) ? $data['msg'] : '返回信息错误';
            $this->retMsg("下单失败：{$msg}");
        }
        return $data['payurl'];
    }
}
