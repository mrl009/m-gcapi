<?php
/**
 * 中富支付接口调用
 * User: lqh
 * Date: 2018/07/05
 * Time: 10:01
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';

class Zhongfu_model extends Publicpay_model
{
    protected $c_name = 'zhongfu';
    private $p_name = 'ZHONGFU';//商品名称
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
    {
        //所有支付都是以返回支付地址
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
        $data['order_id'] = $this->orderNum;
        $data['app_id'] = $this->merId;//商户号
        $data['user_id'] = $this->user['id']; //用户ID
        $data['price'] = yuan_to_fen($this->money);
        $data['ts'] = time();
        $data['client_ip'] = get_ip();
        $data['pay_type'] = $this->getPayType();
        $data['rand'] = sprintf("%06d", mt_rand(1, 999999));
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
            case 2:
                return '2';//微信扫码、WAP
                break;   
            case 5:
                return '1';//支付宝扫码、WAP
                break;
            case 12:
                return '3';//QQ扫码
                break;
            case 25:
                return '4';//快捷
                break;
            default:
                return '1';
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
        if (empty($data['data']['h5_pay_url']))
        {
            $msg = isset($data['data']['msg']) ? $data['data']['msg'] : '返回参数错误';
            $this->retMsg("下单失败：{$msg}");
        }
        //扫码支付返回支付二维码连接地址
        return $data['data']['h5_pay_url'];
    }
}
