<?php
/**
 * 起点支付接口调用
 * User: lqh
 * Date: 2018/05/28
 * Time: 10:02
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';

class Qidian_model extends Publicpay_model
{
    protected $c_name = 'qidian';
    private $p_name = 'QIANDIAN';//商品名称
    private $ks = '&mchkey=';

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
        ksort($data);
        $k = $this->ks . $this->key;
        $string = ToUrlParams($data) . $k;
        $data['sign'] = md5($string);
        return $data;
    }
    
    /**
     * 构造支付基本参数(业务数据)
     * @return array
     */
    private function getBaseData()
    {
        $data['pay_type'] = $this->getPayType();
        $data['mchid'] = $this->merId;
        $data['order_id'] = $this->orderNum;
        $data['goodname'] = $this->p_name;
        $data['money'] = yuan_to_fen($this->money);
        $data['client_ip'] = get_ip();
        $data['notify_url'] = $this->callback;
        $data['return_url'] = $this->returnUrl;
        $data['nonce_str'] = create_guid();
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
                return 'wxewm';//微信扫码
                break;
            case 2:
                return 'wxwap';//微信H5
                break;   
            case 4:
            case 5:
                return 'zfbh5';//支付宝扫码
                break;
            default:
                return 'zfbh5';//支付宝扫码
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
        if (empty($data['payInfo']))
        {
            $msg = isset($data['msg']) ? $data['msg'] : '返回信息错误';
            $this->retMsg("下单失败：{$msg}");
        }
        return $data['payInfo'];
    }
}
