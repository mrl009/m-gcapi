<?php
/**
 * Created by PhpStorm.
 * User: mrl
 * Date: 2018/4/8
 * Time: 上午10:43
 */

class Gefu_model extends MY_Model
{
    public $key;
    public $merId;
    public $orderNum;
    public $money;
    public $url;
    public $callback;
    public $domain;

    public function __construct()
    {
        parent::__construct();
    }

    public function call_interface($order_num, $money, $pay_data)
    {
        $this->orderNum = $order_num;//订单号
        $this->money = $money;//支付金额
        $this->merId = isset($pay_data['pay_id']) ? trim($pay_data['pay_id']) : '';//商户ID
        $this->key = isset($pay_data['pay_key']) ? $pay_data['pay_key'] : '';//密钥
        $this->url = isset($pay_data['pay_url']) ? $pay_data['pay_url'] : '';//请求地址
        $this->domain = isset($pay_data['pay_domain']) ? $pay_data['pay_domain'] : '';
        $this->callback = $this->domain . '/index.php/callback/gefu/callbackurl';//回调地址
        // 组装数据
        $data = $this->getData($pay_data);
        return $this->buildForm($data);
    }

    /**
     * 获取支付参数
     * @param array $pay_data
     * @return array
     */
    private function getData($pay_data)
    {
        $code = isset($pay_data['code']) ? $pay_data['code'] : 0;
        // 请求数据赋值
        $data['parter'] = $this->merId;// 商户在支付平台的的平台号
        $data['value'] = $this->money;//订单金额
        $data['type'] = $this->getType($code);//交易类型
        $data['orderid'] = $this->orderNum;//商户订单号
        $data['notifyurl'] = $this->callback;// 商户通知地址
        if ($pay_data['from_way'] == 3 || $pay_data['from_way'] == 4) {
            $data['callbackurl'] = get_auth_headers('Origin');// 页面返回地址
        } else {
            $site = $this->get_one('domain', 'set_domain', ['type' => 3, 'is_main' => 1, 'is_binding' => 1]);
            $data['callbackurl'] = isset($site['domain']) ? $site['domain'] : $this->url;
        }
        $data['sign'] = $this->sign($data);// 签名
        return $data;
    }

    /**
     * @param $code
     * @return string
     */
    private function getType($code)
    {
        switch ($code) {
            case 1:
                return 'wx';//微信扫码
                break;
            case 2:
                return 'wxwap';//微信H5
                break;
            case 4:
                return 'ali';//支付宝扫码
                break;
            case 5:
                return 'aliwap';//支付宝H5
                break;
            case 8:
                return 'qq';//QQ钱包
                break;
            case 12:
                return 'qqwap';//QQ钱包APP
                break;
            default:
                return 'wx';
        }
    }

    /**
     * 获取支付签名
     * @param string $data 支付参数
     * @return string $sign签名值
     */
    public function sign($data)
    {
        ksort($data);
        return md5(urldecode(http_build_query($data) . '&key=' . $this->key));
    }

    /**
     * 创建表单
     * @param array $data 表单内容
     * @return array
     */
    private function buildForm($data)
    {
        $temp = [
            'method' => 'get',
            'data' => $data,
            'url' => $this->url
        ];
        $rs['jump'] = 5;
        $rs['url'] = $this->domain . '/index.php/pay/pay_test/pay_sest/' . $this->orderNum;
        $rs['json'] = json_encode($temp, JSON_UNESCAPED_UNICODE);
        return $rs;
    }
}