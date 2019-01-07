<?php

/*小熊猫支付接口调用
 * Created by PhpStorm.
 * User: Tailand
 * Date: 2018/12/22
 * Time: 11:30
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once __DIR__.'/Publicpay_model.php';
class Xiaoxiongmao_model extends Publicpay_model
{
    protected $c_name = 'xiaoxiongmao';
    private $p_name = 'XXM';//商品名称

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
        ksort($data);
        $string = floatval($data['money']).trim($data['orderid']). $this->key;
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
        $data['paytype'] = $this->getPayType();
        $data['orderid'] = $this->orderNum;
        $data['money'] = $this->money;//两位小数
        $data['name'] = $this->p_name;
        $data['notify_url'] = $this->callback;
        $data['refer'] = $this->returnUrl;
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
                return '2';//微信
                break;
            case 4:
            case 5:
                return '1';//支付宝
                break;
            case 8:
            case 12:
                return '3';//qq
                break;
            case 36:
            case 41:
                return '1';//支付宝
                break;
            default:
                return '1';//支付宝
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
        $sdata = json_decode($data,true);
        if (empty($data)) $this->retMsg('接口返回信息格式错误！');
        //判断下单是否成功
        if (empty($data['image']))
        {
            $msg = isset($data['msg']) ? $data['msg'] : '返回参数错误';
            $this->retMsg("下单失败：{$msg}");
        }
        return $data['image'];
    }
}