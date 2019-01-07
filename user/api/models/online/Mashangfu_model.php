
<?php

/**
 * 码上付支付接口调用
 * Created by PhpStorm.
 * User: Tailand
 * Date: 2018/10/31
 * Time: 19:01
 */
defined('BASEPATH')or exit('No direct script access allowed');
//调用公共文件
include_once  __DIR__.'/Publicpay_model.php';
class Mashangfu_model extends Publicpay_model
{
    protected  $c_name ='mashangfu';
    protected  $p_name = 'MASHANGFU';
    //支付接口签名参数
    private $method = 'X'; //返回签名大小写 D 大写 X 小写
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
        //所有支付都是以返回支付地址
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
        $k =  $this->key;
        $string =data_value($data);
        $data[$f] = md5($string.$k);
        $data['return_url'] = $this->returnUrl;
        $data['prod_desc']  = $this->p_name;
        return $data;
    }

    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {
        $data['appid']      = $this->merId;//商户号
        $data['name']       = $this->c_name;
        $data['pay_type']   = $this->getPayType();
        $data['price']      = $this->money;
        $data['order_id']   = $this->orderNum;
        $data['extend']     = $this->user['id'];
        $data['notify_url'] = $this->callback;
        $data['order_time'] = date('Y-m-d H:i:s', time());
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
            case 1:
            case 2:
                return 'wechat';//微信扫码
                break;
            case 4:
            case 5:
                return 'f2f';//支付宝扫码
                break;
            default:
                return 'f2f';
                break;
        }
    }

}