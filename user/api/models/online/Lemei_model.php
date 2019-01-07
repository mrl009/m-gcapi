
<?php

/**乐美支付文件
 * Created by PhpStorm.
 * User: Tailand
 * Date: 2018/11/27
 * Time: 16:47
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';
class Lemei_model extends Publicpay_model
{
    protected $c_name = 'lemei';
    protected $p_name = 'LEMEI';//商品名称
    protected $field  = 'sign';
    private $ks = '&key=';

    public function __construct(){
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
    protected function getPayData(){
        //构造基本参数
        $data = $this->getBaseData();
        //构造签名参数
        ksort($data);
        $string = data_to_string($data).$this->ks.$this->key;
        $data[$this->field] = md5($string);
        return $data;
    }

    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData(){
        $data['UserID']    = $this->merId;//商户号
        $data['OrderID']   = $this->orderNum;//订单号
        $data['ChannelID'] = $this->getPayType();//交易类型
        $data['FaceValue'] =  $this->money;//订单金额
        $data['TimeStamp'] =  time();//订单金额
        $data['Version']   =  'V2.0';//版本
        $data['IP']        =  get_ip();//版本
        $data['ResultType']=  0;//版本
        $data['NotifyUrl'] = $this->callback;//回调地址
        $data['ResultUrl'] = $this->returnUrl;
        return $data;
    }

    private function getPayType(){
        switch ($this->code)
        {
            case 1:
                return '2100';//微信扫码
                break;
            case 2:
                return '2000';//微信Wap/h5
                break;
            case 4:
                 return '1100';//支付宝扫码
                break;
            case 5:
                return  '1000';//支付宝WAP
                break;
            case 7:
                return '5000';//网银支付
                break;
            default:
                return '1100';//支付宝扫码
                break;
        }

    }
    /**
     * 获取支付结果
     * @param $data 支付参数
     * @return return 二维码内容
     */
    protected function getPayResult($pay_data){
        $pay_data = http_build_query($pay_data);
        $data = post_pay_data($this->url,$pay_data);
        if (empty($data)) $this->retMsg('接口无信息返回！');
        //接收参数为JSON格式的对象 转化为数组
        $data = json_decode($data,true);
        //判断是否下单成功
        if (empty($data['params_info']) || $data['return_code'] <> 0){
            $msg = isset($data['return_msg']) ? $data['return_msg'] : '返回参数错误';
            $this->retMsg("下单失败：{$msg}");
        }
        $pay_url = $data['params_info']['pay_info'];
        return $pay_url;
    }
}