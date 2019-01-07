<?php
defined('BASEPATH')or exit('No direct script access allowed');
include_once  __DIR__.'/Publicpay_model.php';
class Qianlaibao_model extends Publicpay_model
{
    protected $c_name = 'qianlaibao';
    private $p_name = 'QIANLAIBAO';//商品名称
    //支付接口签名参数
    private $method = 'X'; //返回签名大小写 D 大写 X 小写
    private $key_string = ''; //参与签名组成
    private $field = 'sign'; //签名参数名

    public function __construct()
    {
        parent::__construct();
    }


    public function returnApiData($data){
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
        $data['version'] = '1.0';
        $data['merchant_no'] = $this->merId;
        $data['out_trade_no'] = $this->orderNum;
        $data['payment_type'] = $this->getPayType();
        $data['notify_url'] = $this->callback;
        $data['page_url'] = $this->returnUrl;
        $data['total_fee'] = $this->money;
        $data['trade_time'] = date('YmdHis');
        $data['user_account'] = $this->user['username'];
        $data['body'] = $this->p_name;
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
                return 'alipc';//支付宝扫码
                break;
            case 5:
                return ' aliwap';//支付宝WAP
                break;
            default:
                return 'alipc';//支付宝扫码
                break;
        }
    }
}