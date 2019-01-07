<?php
/**
 * 微数金服支付接口调用
 * User: lqh
 * Date: 2018/05/04
 * Time: 16:42
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';

class Weishu_model extends Publicpay_model
{
    protected $c_name = 'weishu';
    //支付接口返回签名参数 
    private $method = 'X'; //返回签名大小写 D 大写 X 小写
    private $field = 'sign'; //签名参数名
   
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 获取前端返回数据
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
        $f = $this->field;
        $m = $this->method;
        $k = $this->key;
        $data = get_pay_sign($data,$k,$f,$m);
        return $data;
    }
    
    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {
        $data['callbackurl'] = $this->callback;
        $data['orderid'] = $this->orderNum;
        $data['merchant'] = $this->merId;//商户号
        $data['type'] = $this->getPayType();//支付方式
        $data['value'] = $this->money;//订单金额
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
                return '310';//微信扫码
                break; 
            case 4:
                return '210';//支付宝扫码
                break;
            case 7:
                return $this->bank_type;//网银支付
                break;
            case 8:
                return '410';//QQ钱包扫码
                break; 
            case 9:
                return '510';//京东扫码
                break; 
            case 15:
                return '520';//京东扫码
                break;   
            case 12:
                return '421';//QQwap扫码
                break;
            case 19:
                return '610';//银联钱包公众号二维码
                break; 
            case 25:
                return '710';//快捷支付
                break; 
            case 33:
                return '330';//微信H5扫码
                break;
            case 36:
                return '220';//支付宝H5扫码
                break;   
            default:
                return '310';//微信扫码
                break;
        }
    }
}
