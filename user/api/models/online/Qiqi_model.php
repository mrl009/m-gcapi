<?php
/**
 * 柒柒支付接口调用
 * User: lqh
 * Date: 2018/07/30
 * Time: 10:20
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';

class Qiqi_model extends Publicpay_model
{
    protected $c_name = 'qiqi';
    private $p_name = 'QIQI';//商品名称
    //支付接口签名参数 
    private $ks = '&key='; //连接秘钥字符

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
        $k = $this->ks . $this->key;
        $string = ToUrlParams($data) . $k;
        $data['NotifyUrl'] = $this->callback;
        $data['sign'] = strtoupper(md5($string));
        return $data;
    }
    
    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {
        $data['Amount'] = $this->money;
        $data['Ip'] = get_ip();
        $data['Msg'] = $this->orderNum;
        $data['Sh_OrderNo'] = $this->orderNum;
        $data['Type'] = $this->getPayType();
        $data['UId'] = $this->merId;
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
                return '5';//微信
                break;
            case 4: 
            case 5:
                return '13';//支付宝
                break;
            case 8:
            case 12:
                return '14';//QQ
                break;
            case 25:
                return '15';//网银快捷
                break;
            default:
                return '13';//支付宝
                break;
        }
    }
}
