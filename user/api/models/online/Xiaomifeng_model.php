<?php
/**
 * 小蜜蜂支付接口调用
 * User: lqh
 * Date: 2018/05/28
 * Time: 10:02
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';

class Xiaomifeng_model extends Publicpay_model
{
    protected $c_name = 'xiaomifeng';
    private $p_name = 'XMF';//商品名称

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
        $string = implode('', array_values($data));
        $string .= $this->key;
        $data['eddesc'] = $this->p_name;
        $data['edbackurl'] = $this->returnUrl;
        $data['edpay'] = $this->getPayType();
        $data['edip'] = get_ip();
        $data['edsign'] = md5($string);
        return $data;
    }
    
    /**
     * 构造支付基本参数(业务数据)
     * @return array
     */
    private function getBaseData()
    {
        $data['edid'] = $this->merId;
        $data['edddh'] = $this->orderNum;
        $data['edfee'] = $this->money;
        $data['ednotifyurl'] = $this->callback;
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
                return 'wxsm';//微信扫码
                break;
            case 2:
                return 'wxwap';//微信H5
                break;   
            case 4:
                return 'zfbsm';//支付宝扫码
                break;
            case 5:
                return 'zfbwap';//支付宝h5
                break;
            default:
                return 'zfbsm';//支付宝扫码
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
        $data = json_decode($data,true);
        if (empty($data)) $this->retMsg('接口返回信息格式错误！');
        //确认下单是否成功
        if (empty($data['payurl']))
        {
            $msg = isset($data['error']) ? $data['error'] : '返回信息错误';
            $this->retMsg("下单失败：{$msg}");
        }
        return $data['payurl'];
    }
}
