<?php
defined('BASEPATH')or exit('No direct script access allowed');
/**
 * Created by PhpStorm.
 * User: 57207
 * Date: 2018/7/26
 * Time: 15:49
 */
include_once __DIR__.'/Publicpay_model.php';

class Rongfu_model extends Publicpay_model
{
    protected $c_name = 'rongfu';
    private $p_name = 'RONGFU';//商品名称

    public function __construct()
    {
        parent::__construct();
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
        $sign_data = json_encode($data);
        $sign_data = str_replace('\/\/','//',$sign_data);
        $sign_data = str_replace('\/','/',$sign_data);
        $string = $sign_data . $this->key;
        //条码支付特殊参数
        if (in_array($this->code,[40,41]))
        {
            $data['scanType'] = 'Page';
        }
        $data['sign'] = strtoupper(md5($string));
        return $data;
    }

    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {
        $money = yuan_to_fen($this->money);
        $data['merNo'] = $this->merId;//商户号
        $data['aptNo'] = $this->s_num;//资质编号
        $data['payNetway'] = $this->getPayType();
        $data['random'] = sprintf("%04d",mt_rand(1,9999));
        $data['orderNum'] = $this->orderNum;
        $data['amount'] = (string)$money;
        $data['stuffName'] = $this->p_name;
        $data['callBackUrl'] = $this->callback;
        $data['callBackViewUrl'] = $this->returnUrl;
        $data['ip'] = get_ip();
        return $data;
    }

    /**根据code值获取支付方式
     * @return string
     */
    private function  getPayType()
    {
        switch ($this->code)
        {
            case 1:
                return 'WX';
                break;
            case 4:
                return 'ZFB';
                break;
            default:
                return 'WX';
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
        //传递参数JSON格式数据 (paramData的参数内容为json数据)
        $pay_data = json_encode($pay_data);
        $pay_data = str_replace('\/\/','//',$pay_data);
        $pay_data = str_replace('\/','/',$pay_data);
        $post['data'] = $pay_data;
        $data = post_pay_data($this->url,$post);
        if (empty($data)) $this->retMsg('接口返回信息错误！');
        //接收参数为JSON格式 转化为数组
        $data = json_decode($data,true);
        if (empty($data)) $this->retMsg('接口返回信息格式错误！');
        //判断是否下单成功
        if (empty($data['payUrl']))
        {
            $msg = "返回参数错误";
            if (isset($data['msg'])) $msg = $data['msg'];
            if (isset($data['status'])) $msg = $data['status'];
            $this->retMsg("下单失败：{$msg}");
        }
        //返回WAP支付地址
        return $data['payUrl'];
    }

}