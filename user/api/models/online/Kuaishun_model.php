<?php
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';

class Kuaishun_model extends Publicpay_model
{
    protected $c_name = 'Kuaishun';
    private $p_name = 'KUAISHUN';//商品名称A
    //支付接口签名参数 
    private $method = 'X'; //返回签名大小写 D 大写 X 小写
    private $key_string = '&'; //参与签名组成
    private $field = 'sign'; //签名参数名

    public function __construct()
    {
        parent::__construct();
    }

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
        $data['version'] = '1.1';
        $data['customerid'] = $this->merId;
        $data['sdorderno'] = $this->orderNum;
        $data['total_fee'] = $this->money;
        $data['paytype'] = $this->getPayType();
        $data['notifyurl'] = $this->callback;
        $data['returnurl'] = $this->returnUrl;
        $data['remark'] = $this->p_name;
        $data['rettype'] = 'form';
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
            case 4:
            case 5:
                return 'alipay';//支付宝WAP
                break;
            default:
                return 'alipay';//支付宝扫码
                break;
        }
    }
}