<?php
/**
 * 利盈支付接口调用
 * User: lqh
 * Date: 2018/07/10
 * Time: 17:45
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';

class Liying_model extends Publicpay_model
{
    protected $c_name = 'liying';
    private $p_name = 'LIYING';//商品名称
    //参与签名参数
    private $ks = '&key=';

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
        ksort($data);
        $k = $this->ks . $this->key;
        $string = data_to_string($data) . $k;
        $data['sign'] = strtoupper(md5($string));
        return $data;
    }

    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {
        $data['mch_id'] = $this->merId;//商户号
        $data['trade_type'] = $this->getPayType();
        $data['out_trade_no'] = $this->orderNum;
        $data['total_fee'] = yuan_to_fen($this->money);
        $data['notify_url'] = $this->callback;
        $data['time_start'] = date('YmdHis');
        $data['nonce_str'] = create_guid() ;//随机字符串
        $data['body'] = $this->p_name;
        $data['attach'] = $this->p_name;
        $data['return_url'] = $this->returnUrl;
        $data['bank_id'] = (7 == $this->code) ? $this->bank_type : '';
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
                return '01';//微信扫码
                break;
            case 2:
                return '08';//微信WAP
                break;
            case 4:
            case 5:
                return '02';//支付宝
                break;
            case 7:
                return '10';//网银
                break;
            case 8:
                return '05';//QQ扫码
                break;
            case 12:
                return '06';//QQWAP
                break;
            case 9:
                return '07';//京东扫码
                break;
            case 13:
                return '13';//京东WAP
                break;
            case 17:
                return '11';//银联扫码
                break;
            case 25:
                return '12';//网银快捷
                break;
            default:
                return '02';//支付宝
                break;
        }
    }
}
