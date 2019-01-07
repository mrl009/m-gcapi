<?php
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';

class Guanbao_model extends Publicpay_model
{
    protected $c_name = 'guanbao';
    private $p_name = 'GUANBAO';//商品名称A
    //支付接口签名参数 
    private $method = 'D'; //返回签名大小写 D 大写 X 小写
    private $key_string = '&'; //参与签名组成
    private $field = 'sign'; //签名参数名

    public function __construct()
    {
        parent::__construct();
    }

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
        $f = $this->field;
        $m = $this->method;
        $k = $this->key_string . $this->key;
        $data = get_pay_sign($data,$k,$f,$m);
        $data['command'] = 'applyqr';
        return $data;
    }
    
    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {
        $data['cpId'] = $this->merId;
        $data['channel'] = $this->getPayType();
        $data['money'] = yuan_to_fen($this->money);
        $data['subject'] = $this->p_name;
        $data['description'] = $this->p_name;
        $data['orderIdCp'] = $this->orderNum;
        $data['notifyUrl'] = $this->callback;
        $data['timestamp'] = number_format(microtime(true),3,'','');
        $data['ip'] = get_ip();
        $data['version'] = '1';

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
                return 'wechat';//微信
                break;
            case 4:
            case 5:
                return 'alipay';//支付宝
                break;
            default:
                return 'alipay';//支付宝
                break;
        }
    }

    /**
     * 获取支付结果
     * @param $data 支付参数
     * @return return 二维码内容
     */
    protected function getPayResult($pay_data){
        $pay_data = http_build_query($pay_data);
        $data = post_pay_data($this->url,$pay_data);
        if (empty($data)) $this->retMsg('接口无信息返回！');
        //接收参数为JSON格式 转化为数组
        $data = json_decode($data,true);
        //判断是否下单成功
        if (empty($data['data']) || $data['status'] != 0){
            $msg = isset($data['message']) ? $data['message'] : '返回参数错误';
            $this->retMsg("下单失败：{$msg}");
        }
        $pay_url = $data['data'];
        return $pay_url;
    }
}
