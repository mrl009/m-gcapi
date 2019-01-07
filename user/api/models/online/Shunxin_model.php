<?php
defined('BASEPATH') or exit('No direct script access allowed');
/**
 * Created by PhpStorm.
 * User: 57207
 * Date: 2018/8/6
 * Time: 9:34
 */
include_once __DIR__.'/Publicpay_model.php';
class Shunxin_model extends Publicpay_model
{

    protected $c_name = 'shunxin';
    private $p_name = 'SHUNXIN';//商品名称
    //支付接口签名参数
    private $method = 'D'; //返回签名大小写 D 大写 X 小写
    private $field = 'signMsg'; //签名参数名*/
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
        if( in_array($this->code,[1,8])){
            return $this->buildScan($data);
        }else{
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
        //构造签名参数
        $f = $this->field;
        $m = $this->method;
        $k =  $this->key;
        $string = data_to_string($data) . $this->key;
        $data['signMsg'] =strtoupper(md5($string));
        if($this->code==7)$data['bankCode'] = $this->bank_type;
        return $data;
    }

    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {
        $data['apiName'] = $this->getPayType();; //接口名称
        $data['apiVersion'] = '1.0.0.0'; //接口版本
        $data['platformID'] = $this->merId; //商户(合作伙伴)ID
        $data['merchNo'] = $this->merId;//商户号
        $data['orderNo'] = $this->orderNum;
        $data['tradeDate'] = date('Ymd');
        $data['amt'] = $this->money;
        $data['merchUrl'] = $this->callback;
        $data['merchParam'] = urlencode($this->p_name);
        $data['tradeSummary'] = $this->p_name;
        if($this->code !='7')$data['customerIP'] = get_ip();
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
                return 'WECHAT_PAY';//微信
                break;
            case 7:
                return 'WEB_PAY_B2C';//网关支付
                break;
            case 8:
                return 'QQ_PAY';//QQ钱包扫码
                break;
            default :
                return 'WECHAT_PAY';
                break;

        }
    }

    /**
     * @param $data 支付参数
     * @return return  二维码内容
     */
    protected function getPayResult($pay_data)
    {

        $pay_data = http_build_query($pay_data);
        $data = post_pay_data($this->url,$pay_data);
        if (empty($data)) $this->retMsg('接口返回信息错误！');
        //接收参数为XML格式 转化为数组
        $data = FromXml($data);
        if (empty($data)) $this->retMsg('接口返回信息格式错误！');
        //判断下单是否成功
        if (!isset($data['respCode']) || ('00' <> $data['respCode'])
            || (empty($data['codeUrl']) && empty($data['codeUrl'])))
        {
            $msg = isset($data['respDesc']) ? $data['respDesc'] : '返回参数错误';
            $this->retMsg("下单失败: {$msg}");
        }
        //扫码支付返回 二维码地址
            $pay_url = $data['respCode']; //二维码地址

        return $pay_url;
    }
}