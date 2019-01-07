<?php
defined('BASEPATH')or exit('No direct script access allowed');
/**
 * 金蚁付接口文件调用
 */
include_once  __DIR__.'/Publicpay_model.php';

class Jinyifu_model extends Publicpay_model
{
    protected $c_name = 'jinyifu';
    protected $p_name = 'JINYIFU';
    //支付接口签名参数
    private $method = 'D'; //返回签名大小写 D 大写 X 小写
    private $key_string = '&key='; //参与签名组成
    private $field = 'pay_md5sign'; //签名参数名

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
        return $this->useForm($data);
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
        $data['pay_productname'] = $this->p_name;//.商品名称
        $data['pay_tongdao'] = $this->getPayType();//.通道选择
        return $data;
    }

    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {
        $data['pay_memberid'] = $this->merId;//商户号
        $data['pay_orderid'] = $this->orderNum;//。订单号
        $data['pay_amount'] = $this->money;//.金额
        $data['pay_applydate'] = date('Y-m-d H:i:s',$_SERVER['REQUEST_TIME']);//.订单提交时间
        $data['pay_bankcode'] = 'alipay';//.现在只有支付通道可用 bankcode加通道2个字段确定一条通道
        $data['pay_notifyurl'] = $this->callback;//.服务端返回地址
        $data['pay_callbackurl'] = $this->returnUrl;//.页面返回地址
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
            case 4:
                return 'Jinyih5';//支付宝扫码
                break;
            case 5:
                return 'Jinyih5';//支付宝WAP
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
        // return $pay_data;
        // //传递参数
        // $pay_data = http_build_query($pay_data);
        // // var_dump($pay_data);exit;
        // $data = post_pay_data($this->url,$pay_data);
        // var_dump($data);exit;
        // if (empty($data)) $this->retMsg('接口返回信息错误！');
        // //接收参数为JSON格式 转化为数组
        // $data = json_decode($data,true);
        // if (empty($data)) $this->retMsg('接口返回信息格式错误！');
        // //判断是否下单成功
        // if (empty($data['payUrl']))
        // {
        //     $msg = isset($data['retMsg']) ? $data['retMsg'] : '返回参数错误';
        //     $this->retMsg("下单失败：{$msg}");
        // }
        // //扫码支付返回支付二维码连接地址
        // return $data['payUrl'];
    }

    protected function useForm($data)
    {
        $temp = [
            'method' => 'post',
            'data' => $data,
            'url' => $this->url
        ];
        $rs['jump'] = 5;
        $rs['url'] = $this->domain . '/index.php/pay/pay_test/pay_sest/' . $this->orderNum;
        $rs['json'] = json_encode($temp, JSON_UNESCAPED_UNICODE);
        return $rs;
    }
}