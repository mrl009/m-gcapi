<?php
/**
 * Created by Sublime Text.(全能付支付)
 * User: mrl
 * Date: 2018/4/10
 * Time: 下午14:25
 */

class Quannengfu_model extends MY_Model
{
    //private $merchantNo  = '7EC887686D677A19'; //商户机构号
    private $pay_ewm     = 'No'; //固定值
    private $tranType    = '2'; //固定值
    private $cardType    = 1; //银行卡类型
    private $userType    = 1; //用户类型
    private $pay_Channel = 1;

    public function call_interface($order_num, $money, $pay_data)
    {
        //构造基本参数参数
        $this->money  = $money; //支付金额
        $this->orderNum = $order_num; //订单号
        $this->merId = isset($pay_data['pay_id']) ? trim($pay_data['pay_id']) : ''; //商户ID
        $this->payChannel = (5 == $pay_data['from_way']) ? 1 : 2; //支付设备
        $this->bank_type  = isset($pay_data['bank_type']) ? $pay_data['bank_type'] : '';
        $this->code = isset($pay_data['code']) ? $pay_data['code'] : 0;
        $this->key = isset($pay_data['pay_key']) ? $pay_data['pay_key'] : ''; //密钥
        $this->url = isset($pay_data['pay_url']) ? $pay_data['pay_url'] : ''; //请求地址
        $this->domain = isset($pay_data['pay_domain']) ? $pay_data['pay_domain'] : '';
        $this->callback = $this->domain . '/index.php/callback/quannengfu/callbackurl';//回调地址
        //获取支付参数
        $data = $this->getPayData();
        return $this->getData($data);
    }

    /**
     * 获取前端返回数据
     * @param array
     * @return array
     */
    private function getData($data)
    {
        // 网银支付
        if (7 == $this->code) {
            return $this->buildForm($data);
        }
        $qrcode = $this->getPayResult($data);
        $res    = [
            'jump'      => 3,
            'img'       => $qrcode,
            'money'     => $this->money,
            'order_num' => $this->orderNum,
        ];
        return $res;
    }

    /**
     * 构造参与支付的参数
     * @param array
     * @return array
     */
    private function getPayData()
    {
        $sign                   = $this->getSign();
        $data['pay_fs']         = $this->getPayType();
        $data['pay_MerchantNo'] = $this->merId; //商户号
        $data['pay_orderNo']    = $this->orderNum;
        $data['pay_Amount']     = $this->money;
        $data['pay_NotifyUrl']  = $this->callback;
        $data['pay_ewm']        = $this->pay_ewm; //固定值
        $data['tranType']       = $this->tranType; //固定值
        $data['pay_ip']         = get_ip(); //获取IP
        //网银支付增加一下参数
        if (7 == $this->code) {
            //加载银行配置信息
            $this->config->load('bank_set');
            $bank_config = $this->config->item('bank');
            if (!empty($bank_config['Quannengfu']) && !empty($bank_type)) 
            {
                $bank_name = $bank_config['Quannengfu'][$bank_type];
            } else {
                $bank_name = '';
            }
            $data['pay_bankName']      = $bank_name;
            $data['pay_returnUrl']     = $this->callback;
            $data['pay_bankCard_Type'] = $this->cardType;
            $data['pay_user_Type']     = $this->userType;
            $data['pay_Channel']       = $this->payChannel;
        }
        $data['sign'] = $sign;
        return $data;
    }

    /**
     * 获取支付签名
     * @param array
     * @return array
     */
    private function getSign()
    {
        //构造参与签名的参数 (按照签名顺序) 获取签名
        $data[] = $this->getPayType();
        $data[] = $this->s_num; //平台机构号 只参与签名 不参与提交
        $data[] = $this->orderNum;
        $data[] = $this->money;
        $data[] = $this->callback;
        $data[] = $this->pay_ewm;
        $data[] = $this->key;
        //按照签名顺序转化成字符串
        $sign_string = implode("", $data);
        return md5($sign_string);
    }

    /**
     * @param $code
     * @return string
     */
    private function getPayType()
    {
        $code = $this->code;
        switch ($code) {
            case 1:
                return 'weixin'; //微信扫码
                break;
            case 4:
                return 'alipay'; //支付宝扫码
                break;
            case 8:
                return 'qq'; //QQ钱包扫码
                break;
            case 9:
                return 'jd'; //京东扫码
                break;
            case 16:
                return 'qq_h5'; //QQH5WAP
                break;
            case 33:
                return 'weixin_h5'; //微信H5扫码
                break;
            case 38:
                return 'sl'; //苏宁扫码
                break;
            case 7:
            case 27:
                return 'b2c'; //网银支付
                break;
            case 36:
                return 'alipay_h5'; //支付宝H5扫码
                break;
            default:
                return 'weixin';
                break;
        }
    }

    /**
     * @param $data 支付参数
     * @return return pay_Code 二维码内容
     */
    private function getPayResult($pay_data)
    {   
        $data = $this->postPay($pay_data);
        if (empty($data)) {
            $err = array('code' => E_OP_FAIL, 'msg' => "错误信息: 接口服务错误！");
            echo json_encode($err,JSON_UNESCAPED_UNICODE);
            exit;
        }
        $data = json_decode($data, true);
        //判断是否下单成功
        if (empty($data['pay_Status'])) {
            $err = array('code' => E_OP_FAIL, 'msg' => "错误信息: 通道正在维护！");
            echo json_encode($err,JSON_UNESCAPED_UNICODE);
            exit;
        }
        if (('100' <> $data['pay_Status']) || (empty($data['pay_Code']))) {
            $msg = "下单失败：{$data['pay_CodeMsg']}";
            $err = array('code' => E_OP_FAIL, 'msg' => $msg);
            echo json_encode($err,JSON_UNESCAPED_UNICODE);
            exit;
        }
        return $data['pay_Code'];
    }

    /**
     * @param $data 支付参数
     * @return $result 响应结果
     */
    private function postPay($data)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
        //curl_setopt($ch, CURLOPT_HTTPHEADER, array('Expect:')); 
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $rs = curl_exec($ch);
        curl_close($ch);
        return $rs;
    }

    /**
     * 创建表单
     * @param array $data 表单内容
     * @return array
     */
    private function buildForm($data)
    {
        $temp = [
            'method' => 'post',
            'data'   => $data,
            'url'    => $this->url,
        ];
        $rs['jump'] = 5;
        $rs['url']  = $this->domain . '/index.php/pay/pay_test/pay_sest/' . $this->orderNum;
        $rs['json'] = json_encode($temp, JSON_UNESCAPED_UNICODE);
        return $rs;
    }
}
