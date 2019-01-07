<?php

/**
 * 微支付接口调用
 * Created by PhpStorm.
 * User: Tailand
 * Date: 2018/11/16
 * Time: 15:05
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once __DIR__.'/Publicpay_model.php';
class Weizhifu_model extends Publicpay_model
{
    protected $p_name = 'WEIZHIFU';
    protected $c_name = 'weizhifu';
    //支付接口签名参数
    private $method = 'D'; //返回签名大小写 D 大写 X 小写
    private $key_string = '&appkey='; //参与签名组成
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
        //wap支付
        if (in_array($this->code,[1,2]))
        {
            return $this->buildScan($data);
            //扫码支付
        } else {
            $vdata['data'] = json_encode($data);
            //第三方支付返回 支付地址
            $url = $this->url;
            $url .="?".http_build_query($vdata);
            $res = [
                'method'=>'get',
                'jump' => 5,
                'url' => $url
            ];
            return $res;
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
        $k = $this->key;
        $data[$f] = strtoupper(md5(data_value($data).$k));
        $data['remark'] = $this -> c_name ;//充值方式
        $data['frontUrl'] = $this->returnUrl;
        return $data;
    }
    protected function getBaseData()
    {
        $data['appid']=$this->merId;
        $data['orderno']=$this->orderNum;
        $data['amount']=$this->money;
        $data['paytype']=$this->getPayType();//充值方式
        $data['returnurl'] = $this->callback;
        return $data;
    }
    protected function getPayType()
    {
        switch ($this->code)
        {
            case 1:
            case 2:
                return '1';//微信支付
                break;
            case 4:
            case 5:
                return '2';//支付宝支付
                break;
            default:
                return 1;//支付宝支付
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
        //传递参数为json格式
        $vdata['data'] = json_encode($pay_data,JSON_UNESCAPED_SLASHES);
        $data = pay_curl($this->url,$vdata,'get');
        //接收参数为JSON格式 转化为数组
        $data = json_decode($data,true);
        if (empty($data)) $this->retMsg('接口返回信息格式错误！');
        //判断是否下单成功
        if (empty($data['payUrl']) || $data['statue'] <> '1')
        {
            $msg = isset($data['msg']) ? $data['msg'] : '返回参数错误';
            $this->retMsg("下单失败：{$msg}");
        }
        return $data['payUrl'];
    }
}