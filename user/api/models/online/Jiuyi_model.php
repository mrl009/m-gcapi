<?php
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';

class Jiuyi_model extends Publicpay_model
{
    protected $c_name = 'jiuyi';
    private $p_name = 'JIUYI';//商品名称A
    //支付接口签名参数 
    private $method = 'X'; //返回签名大小写 D 大写 X 小写
    private $field = 'sign'; //签名参数名

    public function __construct()
    {
        parent::__construct();
    }

    protected function returnApiData($data)
    {
        return $this->Redirect($data);
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
        $signStr = implode('', array_values($data)) . $this->key;
        $data['sign'] = md5($signStr);
        $data['body'] = $this->p_name;
        $data['mch_create_ip'] = get_ip();
        return $data;
    }

    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {
        $data['mch_id'] = $this->merId;
        $data['out_trade_no'] = $this->orderNum;
        $data['callback_url'] = $this->returnUrl;
        $data['notify_url'] = $this->callback;
        $data['total_fee'] = $this->money;
        $payType = $this->getPayType();
        $data['service'] = $payType[0];
        $data['way'] = $payType[1];
        $data['format'] = 'xml';
        return $data;
    }

    /**
     * 根据code值获取支付方式
     *
     * @param string code
     *
     * @return string 聚合付支付方式 参数
     */
    private function getPayType()
    {
        switch ($this->code) {
            case 1:
                $data = ['wx', 'pay'];
                break;
            case 2:
                $data = ['wx', 'wap'];
                break;
            case 4:
                $data = ['al', 'pay'];
                break;
            case 5:
                $data = ['al', 'wap'];
                break;
            case 8:
                $data = ['qq', 'pay'];
                break;
            case 12:
                $data = ['qq', 'wap'];
                break;
            case 9:
                $data = ['jd', 'pay'];
                break;
            case 13:
                $data = ['jd', 'wap'];
                break;
            default:
                $data = ['al', 'pay'];
                break;
        }
        return $data;
    }

    protected function Redirect($data)
    {
        //把参数按照地址的形式拼接出来
        $parameter = http_build_query($data);
        $pay_url = $this->url . '?' . $parameter;
        $res = [
            'jump' => 5,
            'url' => $pay_url
        ];
        return $res;
    }
}