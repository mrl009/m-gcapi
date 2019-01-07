<?php
/**
 * 新艾米森WAP支付接口调用
 * User: lqh
 * Date: 2018/05/04
 * Time: 10:02
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';

class Amisw_model extends Publicpay_model
{
    protected $c_name = 'aimisen';
    private $p_name = 'AMSWAP';//商品名称
    //支付接口签名参数 
    private $method = 'D'; //返回签名大小写 D 大写 X 小写
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
        //扫码支付
        if (in_array($this->code,$this->scan_code))
        {
            return $this->buildScan($data);
        } else {
            return $this->buildWap($data);
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
        $data['src_code'] = $this->s_num;//商户唯一标识号
        $data['out_trade_no'] = $this->orderNum;
        $data['total_fee'] = yuan_to_fen($this->money);//单位分
        $data['time_start'] = date('YmdHis');
        $data['goods_name'] = $this->p_name;
        $data['trade_type'] = $this->getPayType();
        $data['finish_url'] = $this->returnUrl;
        $data['mchid'] = $this->merId;//商户号
        //网关支付 特殊参数
        if (7 == $this->code)
        {
            //加载银行配置信息
            $this->config->load('bank_set');
            $bank = $this->config->item('bank');
            $card_name = '借记卡';
            $bank_name = '中国工商银行';
            if (isset($bank['Amisw_model']) && isset($this->bank_type) 
                && isset($bank['Amisw_model'][$this->bank_type]))
            {
                $type = $this->bank_type;
                $bank_name = $bank['Amisw_model'][$type];
            } 
            if (1 <> $this->cardType) 
            {
                 $card_name = '信用卡';
            } 
            $temp['bankName'] = $bank_name;
            $temp['cardType'] = $card_name;
            $data['extend'] = json_encode($temp,JSON_UNESCAPED_UNICODE);
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
            case 2:
                return '50107';//微信WAP
                break;
            case 5:
                return '60107';//支付宝WAP
                break;
            case 7:
                return '80103';//网关支付
                break;
            case 12:
                return '40107';//QQ钱包WAP
                break;
            case 26:
                return '80101';//收银台
                break;
            case 33:
                return '50204';//微信WAP扫码
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
        $data = post_pay_data($this->url,$pay_data);
        if (empty($data)) $this->retMsg('接口返回信息错误！');
        //接收参数为JSON格式 转化为数组
        $data = string_decoding($data);
        if (empty($data)) $this->retMsg('接口返回信息格式错误！');
        //判断是否下单成功
        if (empty($data)) $this->retMsg('接口返回信息格式错误！');
        //判断下单是否成功
        if (!isset($data['respcd']) || ('0000' <> $data['respcd'])
          || empty($data['data']) || empty($data['data']['pay_params']))
        {
            $msg = isset($data['respmsg']) ? $data['respmsg'] : '返回参数错误';
            $this->retMsg("下单失败：{$msg}");
        }
        return $data['data']['pay_params'];
    }
}
