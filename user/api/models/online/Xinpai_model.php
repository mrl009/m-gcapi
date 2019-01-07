<?php
defined('BASEPATH') or exit('No direct script access allowed');
/**
 * Created by PhpStorm.
 * User: 57207
 * Date: 2018/7/4
 * Time: 10:50
 */
include_once __DIR__.'/Publicpay_model.php';

class Xinpai_model extends Publicpay_model

{
    protected $c_name = 'xinpai';
    private $p_name = 'XINPAI';//商品名称
    //支付接口签名参数
    private $method = 'D'; //返回签名大小写 D 大写 X 小写
    private $key_string = '&paySecret='; //参与签名组成
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
        $pay_data = post_pay_data($this->url,$data);
        //返回url或者html
        if ($data['payMessageType']==1) {
            return $this->buildScan($pay_data);
        } else {
            return $this->buildForm($data);
        }
    }

    /**
     * 构造支付参数+sign值
     * @return array
     */
    protected function getPayData()
    {
        //构造基本参数
        $data = $this->getBaseData();
        ksort($data);
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

        $data['payKey'] = $this->merId;//商户号
        $data['orderPrice'] = $this->money;
        $data['outTradeNo'] = $this->orderNum;
        $data['orderIp']   = get_ip();
        $data['productName']= "XIPay";
        $data['orderTime'] = date("YmdHis");
        $data['remark']    = '41zhifu';
        $data['notifyUrl'] = $this->callback;
        $data['returnUrl'] = $this->returnUrl;
        $data['productType'] = $this->getPayType();
        //网银支付
        if (7 == $this->code)
        {
            $data['bankAccountType'] = $this->getAcountType();
            $data['bankCode']   = $this->bank_type;

        //快捷支付没有该参数 且银行参数bankcode不传
        } else if(25== $this->code) {
            $data['secretContent'] = $this->secretContent();//AES加密暂时不开发
        }
        return $data;
    }

    /**
     * 获取支付网关地址 部分接口地址不唯一
     * @param array $pay 支付参数
     *
     * @return array
     */
    protected function getPayUrl($pay)
    {
        $url = '';
        if (!empty($pay['pay_url']))
        {
            $url = trim($pay['pay_url']);
        }
        //网银支付
        if (7 == $this->code)
        {
            $url .= 'b2cPay/initPay';
        //快捷支付
        } elseif(25 == $this->code) {
            $url .= 'quickPay/initPay';
        //扫码支付
        } else {
            $url .= 'cnpPay/initPay';
        }
        return $url;
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
                return '10000203';//微信扫码
                break;
            case 4:
                return '20000203';//支付宝扫码
                break;
            case 7:
                return '50000103';//网关支付
                break;
            case 8:
                return '70000103';//QQ钱包扫码
                break;
            case 9:
                return '80000103';//京东扫码
                break;
            case 17:
                return '60000103';//银联钱包
                break;
            case 25:
                return '40000103';//快捷支付
                break;
            default:
                return '10000203';//微信扫码
                break;
        }
    }

    /**
     * 银行账户类型
     * 银行类型 PRIVATE_DEBIT_ACCOUNT： 对私借记卡 PRIVATE_CREDIT_ACCOUNT：对私贷记卡
     */
    private function getAcountType()
    {
        switch ($this->cardType)
        {
            case '1':
                return "PRIVATE_DEBIT_ACCOUNT";//对私借记卡
                break;
            case '2':
                return "PRIVATE_CREDIT_ACCOUNT";//对私贷记卡
                break;
            default:
                return 'PRIVATE_CREDIT_ACCOUNT';//对私借记卡
                break;
        }

    }

    /**
     * 银行卡秘文
     * bankAccountName=XXX&bankAccountNo=5555541234567891234&bankAccountType=PRIVATE_DEBIT_ACCOUNT&bankCode=ICBC&certNo=220621199007052222&certType=IDENTITY&cvn2=826&expDate=2011&phoneNo=13514420581
     */
    private function secretContent()
    {
        $scret_data['bankAccountType'] = $this->getAcountType();//银行账户类型
        $scret_data['phoneNo'] = $this->P('money');

    }
    /**
     * 获取支付结果
     * @param $data 支付参数
     * @return return 二维码内容
     */
    protected function getPayResult($data)
    {
        //$data = post_pay_data($this->url,$pay_data);
        $data ='{"resultCode":"0000","errMsg":"","sign":"15D9B4FBFA010CDCDF8A4AC3B05215F2","payMessageType":"0","payMessage":"https://myun.tenpay.com/mqq/pay/qrcode.html?_wv=1027&_bid=2183&t=6Ve8b0b7c5c7fd5ca42cac0c4c97fa00"}';
        $data = json_decode($data);
        if (empty($data)) $this->retMsg('接口返回信息错误！');
        //接收参数为JSON格式 转化为数组
        $data = json_decode($data,true);
        if (empty($data)) $this->retMsg('接口返回信息格式错误！');
        //判断是否下单成功
        if (empty($data['payMessage']))
        {
            $msg = isset($data['retMsg']) ? $data['retMsg'] : '返回参数错误';
            $this->retMsg("下单失败：{$msg}");
        }
        //扫码支付返回支付二维码连接地址
        return $data['payMessage'];
    }
}