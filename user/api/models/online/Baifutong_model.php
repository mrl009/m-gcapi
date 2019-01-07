<?php
/**
 * Created by PhpStorm.
 * User: mrl
 * Date: 2018/3/12
 * Time: 下午1:51
 */

class Baifutong_model extends GC_Model
{
    public $key;
    public $merId;
    public $orderNum;
    public $money;
    public $url;
    public $callback;
    public $domain;

    public function call_interface($order_num, $money, $pay_data)
    {
        $this->orderNum = $order_num;//订单号
        $this->money = $money * 100;//支付金额
        $this->merId = isset($pay_data['pay_id']) ? trim($pay_data['pay_id']) : '';//商户ID
        $this->key = isset($pay_data['pay_key']) ? $pay_data['pay_key'] : '';//商户私钥
        $this->url = isset($pay_data['pay_url']) ? $pay_data['pay_url'] : '';//请求地址
        $this->domain = isset($pay_data['pay_domain']) ? $pay_data['pay_domain'] : '';
        $this->callback = $this->domain . '/index.php/callback/Baifutong/callbackurl';//回调地址
        // 组装数据
        $data = $this->getData($pay_data);
        $rs = json_decode($this->request($data), true);
        if ($rs['resultCode'] != '00') {
            echo json_encode(array('code' => E_OP_FAIL, 'msg' => "错误号: {$rs['resultCode']}, 错误信息：{$rs['resultMsg']}！"));
            exit;
        }
        if (in_array($pay_data['code'], [1, 4, 8, 9, 17])) {
            $res = [
                'jump' => 3,
                'img' => $rs['CodeUrl'],
                'money' => $money,
                'order_num' => $order_num,
            ];
        } else {
            $res = [
                'url' => $rs['CodeUrl'],
                'jump' => 5
            ];
        }
        return $res;
    }

    /**
     * 获取请求参数
     * @param $pay_data
     * @return array
     */
    private function getData($pay_data)
    {
        $code = isset($pay_data['code']) ? $pay_data['code'] : 0;
        // 请求数据赋值
        $data['merchantNo'] = $this->merId;// 商户在支付平台的的平台号
        $data['netwayCode'] = $this->getService($code);// 商户在支付平台的的平台号
        $data['randomNum'] = (string)rand(1000, 9999);  //4位随机数
        $data['orderNum'] = $this->orderNum;  //订单号
        $data['payAmount'] = (string)$this->money;// 金额
        $data['goodsName'] = '线上支付';// 商品名称
        $data['callBackUrl'] = $this->callback;// 商户通知地址
        $data['frontBackUrl'] = get_auth_headers('Origin');// 页面返回地址
        $data['requestIP'] = get_ip();// IP
        //$data['requestIP'] = '202.106.196.115';// IP
        $data['sign'] = $this->sign($data, $this->key);// 签名
        return ['paramData' => $this->enData($data)];
    }

    /**
     * @param $code
     * @return int
     */
    private function getService($code)
    {
        switch ($code) {
            case 1:
                return 'WX';//微信扫码
                break;
            case 2:
                return 'WX_WAP';//微信H5
                break;
            case 4:
                return 'ZFB';//支付宝扫码
                break;
            case 5:
                return 'ZFB_WAP';//支付宝H5
                break;
            case 8:
                return 'QQ';//QQ钱包
                break;
            case 9:
                return 'JDQB';//京东钱包
                break;
            case 12:
                return 'QQ_WAP';//QQ钱包H5
                break;
            case 13:
                return 'JDQB_WAP';//京东钱包H5
                break;
            case 17:
                return 'YL';//银联
                break;
            case 25:
                return 'KJ';
                break;
            default:
                return 'WX';
        }
    }

    /**
     * 获取支付签名
     * @param array $data 支付参数
     * @param string $key 秘钥
     * @return string $sign签名值
     */
    private function sign($data, $key)
    {
        ksort($data);
        $sign = md5($this->enData($data) . $key);
        return strtoupper($sign);
    }

    /**
     * 格式化数据
     * @param $data
     * @return string
     */
    private function enData($data)
    {
        if (is_string($data)) {
            $text = $data;
            $text = str_replace('\\', '\\\\', $text);
            $text = str_replace(
                array("\r", "\n", "\t", "\""),
                array('\r', '\n', '\t', '\\"'),
                $text);
            $text = str_replace("\\/", "/", $text);
            return '"' . $text . '"';
        } else if (is_array($data) || is_object($data)) {
            $arr = array();
            $is_obj = is_object($data) || (array_keys($data) !== range(0, count($data) - 1));
            foreach ($data as $k => $v) {
                if ($is_obj) {
                    $arr[] = $this->enData($k) . ':' . $this->enData($v);
                } else {
                    $arr[] = $this->enData($v);
                }
            }
            if ($is_obj) {
                $arr = str_replace("\\/", "/", $arr);
                return '{' . join(',', $arr) . '}';
            } else {
                $arr = str_replace("\\/", "/", $arr);
                return '[' . join(',', $arr) . ']';
            }
        } else {
            $data = str_replace("\\/", "/", $data);
            return $data . '';
        }
    }

    /**
     * 请求接口
     * @param $data
     * @return mixed
     */
    private function request($data)
    {
        $ch = curl_init();
        curl_setoPt($ch, CURLOPT_URL, $this->url);
        curl_setoPt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setoPt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setoPt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setoPt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (comPatible; MSIE 5.01; Windows NT 5.0)');
        curl_setoPt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setoPt($ch, CURLOPT_AUTOREFERER, 1);
        curl_setoPt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setoPt($ch, CURLOPT_RETURNTRANSFER, true);
        $rs = curl_exec($ch);
        if (curl_errno($ch)) {
            return curl_error($ch);
        }
        return $rs;
    }
}