<?php
/**
 * 安安支付接口调用
 * User: lqh
 * Date: 2018/07/30
 * Time: 10:20
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';

class Anan_model extends Publicpay_model
{
    protected $c_name = 'anan';
    private $p_name = 'ANAN';//商品名称
    //支付接口签名参数 
    private $ks = '&key='; //连接秘钥字符
    private $method = 'D'; //返回签名大小写 D 大写 X 小写
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
        return $this->buildForm($data);
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
        $data['version'] = 'V1.0';
        $data['mer_id'] = $this->merId;
        $data['money'] = $this->money;
        $data['mer_orderid'] = $this->orderNum;
        $data['attach'] = $this->p_name;
        $data['goods_desc'] = $this->p_name;
        $data['spbill_create_ip'] = get_ip();
        $data['type'] = $this->getPayType();
        $data['notify_url'] = $this->callback;
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
                return 'WEIXINH5';//微信Wap/h5
                break;
            case 4: 
            case 5:
                return 'ZFBH5';//支付宝WAP
                break;
            case 7:
                return 'WANGYIN';//网银支付
                break;
            case 9:
            case 13:
                return 'JDH5';//京东wap
                break;
            default:
                return 'ZFBH5';//支付宝扫码
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
        if (empty($data['code_url']))
        {
            $msg = '返回信息错误';
            if (isset($data['Error_msg'])) $msg = $data['Error_msg'];
            $this->retMsg("下单失败：{$msg}");
        }
        return $data['code_url'];
    }
}
