<?php
defined('BASEPATH')or exit('No direct script access allowed ');
/**
 * Created by PhpStorm.
 * User: 57207
 * Date: 2018/8/24
 * Time: 18:23
 */
include_once  __DIR__.'/Publicpay_model.php';

class Esinpay_model extends Publicpay_model
{
    protected $c_name = 'esinpay';
    protected $p_name = 'ESINPAY';
//支付接口签名参数
    private $method = 'D'; //返回签名大小写 D 大写 X 小写
    private $key_string ='&key=';
    private $field = 'P_PostKey'; //签名参数名

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
        $string = $this-> ToParams($data);
        $data[$this->field] = strtolower(md5($string));
        $data['P_Price'] = '21';//商品售价
        $data['P_Result_URL'] = $this->callback;
        $data['P_Notify_URL'] = $this->returnUrl;
        if($this->code==7)$data['P_Description'] = $this->bank_type;
        return $data;
    }

    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {
        $data['P_UserID'] = $this->merId;//商户号
        $data['P_OrderID'] = $this->orderNum;
        $data['P_CardID'] = $this->c_name;
        $data['P_CardPass'] = $this->p_name;
        $data['P_FaceValue'] = $this->money;
        $data['P_ChannelID'] = $this->getPayType();
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
                return '21';//微信扫码
                break;
            case 2:
                return '33';//微信WAP 1,2,12,17,25,40
                break;
            case 4:
                return '2';//支付宝扫码
                break;
            case 5:
                return '36';//支付宝WAP
                break;
            case 7:
                return '1';//网关支付
                break;
            case 8:
                return '89';//QQ钱包扫码
                break;
            case 9:
                return '91';//京东扫码
                break;
            case 20:
                return '90';//百度钱包
                break;
            case 12:
                return '92';//QQWAP
                break;
            case 13:
                return '98';//京东钱包wap
                break;
            case 17:
                return '95';//银联钱包扫码
                break;
            case 18:
                return '31';//银联钱包wap
                break;
            case 22:
                return '3';//财付通
                break;
            case 25:
                return '32';//银联快捷
                break;
            case 33:
                return '88';//微信公众号
                break;
            case 40:
                return '121';//微信条码
                break;
            default:
                return '21';//微信扫码
                break;
        }
    }

    /**
     * 获取支付结果
     * @param $data 支付参数
     * @return return 二维码内容
     */
    protected function getPayResult($pay_data)
    {
        //传递参数
        //$pay_data = http_build_query($pay_data);
        $data = post_pay_data($this->url,$pay_data);
        if (empty($data)) $this->retMsg('接口返回信息错误！');
        //接收参数为JSON格式 转化为数组
        $data = json_decode($data,true);
        if (empty($data)) $this->retMsg('接口返回信息格式错误！');
        //判断是否下单成功
        if (empty($data['payUrl']) && $data['status']<>'T')
        {
            $msg = isset($data['errMsg']) ? $data['errCode'].$data['errMsg'] : '返回参数错误';
            $this->retMsg("下单失败：{$msg}");
        }
            //扫码支付返回支付二维码连接地址
            return $data['payUrl'];


    }
    /**
     * 将数组的键与值用&符号隔开
     * @param $data array 待签名的数据
     * @return  $str string
     */
  protected  function ToParams($data)
    {
        $buff = "";
        foreach ($data as $k => $v)
        {
            if (!is_array($v) && ('sign' <> $k)
                && ("" <> $v) && (null <> $v)
                && ("null" <> $v))
            {
                $buff .= $v . "|";
            }
        }
        $buff = $buff.$this->key;
        return $buff;
    }
}