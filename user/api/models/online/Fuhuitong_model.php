<?php
defined('BASEPATH') or exit('No direct script access allowed');
/**
 * 福汇通支付接口调用
 * User: lqh
 * Date: 2018/08/29
 * Time: 09:20
 */
include_once __DIR__.'/Publicpay_model.php';

class Fuhuitong_model extends Publicpay_model
{
    protected $c_name = 'fuhuitong';
    private $p_name = 'FUHUITONG';//商品名称

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
        $string = ToUrlParams($data) . $this->key;
        $data['sign'] = md5($string);
        return $data;
    }
    
    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {
        $data['merCode'] = $this->merId;
        $data['tranNo'] = $this->orderNum;
        $data['tranType'] = $this->getTranType();
        $data['tranAmt'] = yuan_to_fen($this->money);
        $data['collectWay'] = $this->getPayType();
        $data['tranTime'] = date('YmdHis');
        $data['noticeUrl'] = $this->callback;
        $data['orderDesc'] = $this->p_name;
        $data['userIP'] = get_ip();
        return $data;
    }

    /**
     * 根据code值获取支付方式
     * @param string code 
     * @return string 交易类型 参数
     */
    private function getTranType()
    {
        if (7 == $this->code)
        {
            return '01';
        } elseif (25 == $this->code) {
            return '02';
        } elseif (in_array($this->code,$this->wap_code)) {
            return '03';
        } else {
            return '00';
        }
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
                return 'WXZF';//微信
                break;
            case 2:
                return 'WXH5';//微信WAP
                break;
            case 4: 
                return 'ZFBZF';//支付宝
                break;
            case 5:
                return 'ZFBH5';//支付宝WAP
                break; 
            case 7: 
                return 'WEB';//网银
                break; 
            case 8: 
                return 'QQZF';//QQ钱包
                break; 
            case 9: 
                return 'JDZF';//京东钱包
                break;
            case 12:
                return 'QQZF5';//QQ钱包WAP
                break; 
            case 13:
                return 'JDH5';//京东H5
                break; 
            case 17:
                return 'UPZF';//银联
                break;
            case 18:
                return 'UPH5';//银联WAP
                break;
            case 25:
                return 'QUICK';//网银快捷
                break;
            default:
                return 'ZFBZF';//支付宝
                break;
        }
    }
}
