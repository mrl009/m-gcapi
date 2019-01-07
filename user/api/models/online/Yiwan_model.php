
<?php
defined('BASEPATH') or exit('No direct script access allowed');
/**
 * Created by PhpStorm.
 * User: 57207
 * Date: 2018/9/12
 * Time: 16:08
 */
include_once __DIR__.'/Publicpay_model.php';

class Yiwan_model extends Publicpay_model
{
    protected $c_name = 'yiwan';
    private $p_name = 'YIWAN';//商品名称
    //支付接口签名参数
    private $method = 'X'; //返回签名大小写 D 大写 X 小写
    private $key_string = '&key='; //参与签名组成
    private $field = 'sign'; //签名参数名
    private $amount = [1,50,100,200,300,500,800,1000,2000,3000,5000];//支付宝支付方式指定金额

    public function __construct()
    {
        parent::__construct();
    }

    public function returnApiData($data){
        if(in_array($this->code,[7,25])){
            $res = [
                'jump' => 5,
                'url' => 'https://www.qqfu.me/api/create?'.http_build_query($data)
            ];
            return $res;
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
        $k = $this->key;
        $data = $this-> md5sign($data,$k,$f,$m);
        if (in_array($this->code,[7,25])) $data['bankCode'] = $this->bank_type;
        $data['format'] = 'html';
        return $data;
    }

    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {
        $data['mchid'] = $this->merId;
        $data['out_order_id'] = $this->orderNum;
        $data['type'] = $this->getPayType();
        if(in_array($this->code,[4,5])){
           if( in_array($this->money,$this->amount)){
               $data['price'] =sprintf("%.2f",$this->money);
           }else{
               $this->retMsg('请支付金额:50,100,200,300,500,800,1000,2000,3000,5000');
           }
        }
        $data['price'] = sprintf("%.2f",$this->money);
        $data['notifyurl'] = $this->callback;

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
            case 7:
                return 'ebank';//网银支付
                break;
            case 25:
                return 'quickpay_h5';//快捷
                break;
            default:
                return 'alipay';//支付宝扫码
                break;
        }
    }

    public function md5sign($data,$k,$f,$m){
        ksort($data);
        $string ='';
        foreach($data as $key => $val)
        {
            if (!is_array($val) && ('sign' <> $key)
                && ("" <> $val) && (null <> $val)
                && ("null" <> $val))
            {
                $string .= $val;
            }
        }
        $string = md5($string). $k;
        //拼接字符串进行MD5大写加密
        $sign =strtolower(md5($string)) ;
        $data[$this->field] = $sign;
        return $data;

    }
    /**
     * 获取支付结果
     * @param $data 支付参数
     * @return return 二维码内容
     */
    protected function getPayResult($pay_data)
    {
        //傳遞參數為json數據
        //$data=curl_get('https://www.qqfu.me/api/create?'.$pay_data);
        $data = post_pay_data($this->url,$pay_data);
        if (empty($data)) $this->retMsg('接口返回信息错误！');
        //接收参数为JSON格式 转化为数组
        $data = json_decode($data,true);
        if (empty($data)) $this->retMsg('接口返回信息格式错误！');
        if (empty($data['payUrl']) && empty($data['qrUrl']))
        {
            $msg = isset($data['msg']) ? $data['msg'] : '返回参数错误';
            $this->retMsg("下单失败：{$msg}");
        }
        $payUrl = !empty($data['qrUrl']) ? $data['qrUrl'] : $data['payUrl'];
        return $payUrl;
    }
}