<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * 路德支付模块
 * @version     v1.0 2017/12/19
 */
class Lude_model extends MY_Model
{
    public $key;
    public $merId;
    public $orderNum;
    public $money;
    public $url;
    public $callback;
    public $domain;
    public $step;
    const API_VERSION = "1.0.0.0";              //支付版本的api的版本号
    const API_NAME_PAY = "TRADE.B2C";           //网银支付
    const API_NAME_SCANPAY = "TRADE.SCANPAY";   //扫码支付
    const API_NAME_H5PAY = "TRADE.H5PAY";       //H5支付
    const API_NAME_QUERY = "TRADE.QUERY";       //支付订单查询
    const API_NAME_REFUND = "TRADE.REFUND";     //退款申请
    const API_NAME_SETTLE = "TRADE.SETTLE";     //单笔委托结算
    const API_NAME_NOTIFY = "TRADE.NOTIFY";     //支付通知
    const API_NAME_SETTLE_QUERY = "TRADE.SETTLE.QUERY";//单笔委托结算查询
    const API_NAME_QUICKPAY_APPLY = "TRADE.QUICKPAY.APPLY";//快捷支付
    const API_NAME_QUICKPAY_CONFIRM = "TRADE.QUICKPAY.CONFIRM";//快捷确认

    public function call_interface($order_num, $money, $pay_data)
    {
        $this->orderNum = $order_num;//订单号
        $this->money = $money;//支付金额
        $this->merId = isset($pay_data['pay_id']) ? trim($pay_data['pay_id']) : '';//商户ID
        $this->key = isset($pay_data['pay_key']) ? $pay_data['pay_key'] : '';//密钥
        $this->url = isset($pay_data['pay_url']) ? $pay_data['pay_url'] : '';//请求地址
        $this->step = isset($pay_data['step']) ? $pay_data['step'] : 0;//快捷支付步骤
        $this->domain = isset($pay_data['pay_domain']) ? $pay_data['pay_domain'] : '';
        $this->callback = $this->domain . '/index.php/callback/Lude/callbackurl';//回调地址
        // 组装数据
        $data = $this->getData($pay_data);
        // 准备待签名数据
        $strToSign = $this->prepareSign($data);
        // 数据签名
        $signMsg = $this->sign($strToSign);
        $data['sign'] = $signMsg;
        // 准备请求数据
        $toRequset = $this->prepareRequest($strToSign, $signMsg);
        //请求数据
        $rsData = $this->request($toRequset);
        // 响应信息
        preg_match('{<desc>(.*?)</desc>}', $rsData, $match);
        $rsDesc = isset($match[1]) ? $match[1] : '';
        if ($rsDesc != '' && $rsDesc != '交易完成') {
            echo json_encode(array('code' => E_OP_FAIL, 'msg' => $rsDesc));
            exit;
        }
        // 响应码
        preg_match('{<code>(.*?)</code>}', $rsData, $match);
        $rsCode = isset($match[1]) ? $match[1] : '';
        if ($this->step == 1) {
            if ($rsCode != '00') {
                return array('code' => E_OP_FAIL, 'data' => '操作失败');
            }
            preg_match('{<opeNo>(.*?)</opeNo>}', $rsData, $match);
            $opeNo = isset($match[1]) ? $match[1] : '';
            preg_match('{<opeDate>(.*?)</opeDate>}', $rsData, $match);
            $opeDate = isset($match[1]) ? $match[1] : '';
            preg_match('{<sessionID>(.*?)</sessionID>}', $rsData, $match);
            $sessionID = isset($match[1]) ? $match[1] : '';
            $data = [
                'opeNo' => $opeNo,
                'opeDate' => $opeDate,
                'sessionID' => $sessionID,
                'order_num' => $order_num
            ];
            return array('code' => OK, 'data' => $data);
        } elseif ($this->step == 2) {
            if ($rsCode != '00') {
                return array('code' => E_OP_FAIL, 'data' => '操作失败');
            }
            return [
                'url' => 'http://'. $_SERVER['HTTP_HOST'],
                'jump' => 5
            ];
        }
        if (in_array($pay_data['code'], [2, 5, 7, 12, 26])) {//WAP 收银台
            return $this->buildForm($data);
        } elseif ($data['service'] == 'TRADE.SCANPAY' && $rsCode != '00') {
            return array('code' => E_OP_FAIL, 'msg' => $rsDesc);
        }
        preg_match('{<qrCode>(.*?)</qrCode>}', $rsData, $match);
        $img = isset($match[1]) ? $match[1] : '';
        $data = [
            'is_img' => 0,
            'jump' => 3,
            'money' => $money,
            'order_num' => $order_num,
            'img' => base64_decode($img)
        ];
        return $data;
    }

    /**
     * 获取支付参数
     * @param array $pay_data
     * @return array|mixed
     * 網銀：service version merId tradeNo tradeDate amount notifyUrl extra summary expireTime clientIp bankId sign
     * 快捷：
     *   1：service version merId tradeNo tradeDate amount notifyUrl extra summary expireTime clientIp cardType cardNo cardName idCardNo mobile cvn2 validDate sign
     *   2：service version merId opeNo opeDate sessionID dymPwd sign
     * 掃碼：service version merId typeId tradeNo tradeDate amount notifyUrl extra summary expireTime clientIp sign
     * WAP：service version merId typeId tradeNo tradeDate amount notifyUrl extra summary expireTime clientIp sign
     * 收銀台：service version merId tradeNo tradeDate amount notifyUrl extra summary expireTime clientIp bankId sign
     */
    private function getData($pay_data)
    {
        $code = isset($pay_data['code']) ? $pay_data['code'] : 0;
        // 请求数据赋值
        $data['service'] = $this->getService($code);// 接口名字
        $data['version'] = self::API_VERSION;// 商户API版本
        $data['merId'] = $this->merId;// 商户在支付平台的的平台号
        $data['typeId'] = $this->getTypeId($code);
        $data['tradeNo'] = $this->orderNum;//商户订单号
        $data['tradeDate'] = date('Ymd', time());// 商户订单日期
        $data['amount'] = $this->money;// 商户交易金额
        $data['notifyUrl'] = $this->callback;// 商户通知地址
        $data['summary'] = "lude";// 商户交易摘要
        $data['extra'] = $pay_data['pay_key'];// 商户扩展字段
        $data['expireTime'] = 60 * 5;// 超时时间
        $data['clientIp'] = get_ip();//客户端ip
        $data['bankId'] = $this->getBankId($pay_data);
        $data = $this->zwToUtf8($data);// 对含有中文的参数进行UTF-8编码
        if ($code == 25 && $this->step == 1) {
            $data['cardType'] = 1;//银行卡类型
            $data['cardNo'] = isset($pay_data['cardNo']) ? $pay_data['cardNo'] : '';//银行卡卡号
            $data['cardName'] = isset($pay_data['cardName']) ? $pay_data['cardName'] : '';;//开户姓名
            $data['idCardNo'] = isset($pay_data['idCardNo']) ? $pay_data['idCardNo'] : '';//身份证号
            $data['mobile'] = isset($pay_data['mobile']) ? $pay_data['mobile'] : '';//预留手机号
            $data['cvn2'] = isset($pay_data['cvn2']) ? $pay_data['cvn2'] : '';//信用卡安全码，银行卡类型为信用卡时必填
            $data['validDate'] = isset($pay_data['validDate']) ? $pay_data['validDate'] : '';//信用卡有效期，银行卡类型为信用卡时必填，格式为YYMM
        } elseif ($code == 25 && $this->step == 2) {
            $data['opeNo'] = isset($pay_data['opeNo']) ? $pay_data['opeNo'] : '';//支付订单号
            $data['opeDate'] = isset($pay_data['opeDate']) ? $pay_data['opeDate'] : '';;//订单日期
            $data['sessionID'] = isset($pay_data['sessionID']) ? $pay_data['sessionID'] : '';//交易标识
            $data['dymPwd'] = isset($pay_data['dymPwd']) ? $pay_data['dymPwd'] : '';//动态口令
        }
        return $data;
    }

    /**
     * 獲取service
     * @param int $code
     * @return string
     */
    private function getService($code)
    {
        $service = '';
        if ($this->step == 1) {
            $service = self::API_NAME_QUICKPAY_APPLY;
        } elseif ($this->step == 2) {
            $service = self::API_NAME_QUICKPAY_CONFIRM;
        } elseif (in_array($code, [1, 4, 8, 17])) {
            $service = self::API_NAME_SCANPAY;
        } elseif (in_array($code, [2, 5, 12])) {
            $service = self::API_NAME_H5PAY;
        } elseif ($code == 7 || $code == 26) {
            $service = self::API_NAME_PAY;
        }
        return $service;
    }

    /**
     * @param $code
     * @return int
     */
    private function getTypeId($code)
    {
        switch ($code) {
            case 1:
                return 2;//微信扫码
                break;
            case 2:
                return 2;//微信APP
                break;
            case 4:
                return 1;//支付宝扫码
                break;
            case 5:
                return 1;//支付宝APP扫码
                break;
            case 7:
                return 0;//网银
                break;
            case 8:
                return 3;//QQ钱包
                break;
            case 12:
                return 3;//QQ钱包APP
                break;
            case 17:
                return 4;//银联
                break;
            case 25:
                return 0;//快捷
                break;
            case 26:
                return 0;//收银台
                break;
            default:
                return 2;
        }
    }

    /**
     * 獲取bankId
     * @param array $pay_data
     * @return string
     */
    private function getBankId($pay_data)
    {
        $code = isset($pay_data['code']) ? $pay_data['code'] : 0;
        return $code == 7 ? $pay_data['bank_type'] : '';
    }

    /**
     * 对含有中文的参数进行UTF-8编码
     * @param $data
     * @return mixed
     */
    private function zwToUtf8($data)
    {
        // 将中文转换为UTF-8
        if (!preg_match("/[\xe0-\xef][\x80-\xbf]{2}/", $data['notifyUrl'])) {
            $data['notifyUrl'] = iconv("GBK", "UTF-8", $data['notifyUrl']);
        }
        if (!preg_match("/[\xe0-\xef][\x80-\xbf]{2}/", $data['extra'])) {
            $data['extra'] = iconv("GBK", "UTF-8", $data['extra']);
        }
        if (!preg_match("/[\xe0-\xef][\x80-\xbf]{2}/", $data['summary'])) {
            $data['summary'] = iconv("GBK", "UTF-8", $data['summary']);
        }
        return $data;
    }

    /**
     * 准备签名
     * @param $data
     * @return string
     */
    public function prepareSign($data)
    {
        if ($data['service'] == 'TRADE.B2C') {//1网银支付
            $result = sprintf(
                "service=%s&version=%s&merId=%s&tradeNo=%s&tradeDate=%s&amount=%s&notifyUrl=%s&extra=%s&summary=%s&expireTime=%s&clientIp=%s&bankId=%s",
                $data['service'],
                $data['version'],
                $data['merId'],
                $data['tradeNo'],
                $data['tradeDate'],
                $data['amount'],
                $data['notifyUrl'],
                $data['extra'],
                $data['summary'],
                $data['expireTime'],
                $data['clientIp'],
                $data['bankId']
            );
            return $result;
        } else if ($data['service'] == 'TRADE.SCANPAY') {//2扫码支付
            $result = sprintf(
                "service=%s&version=%s&merId=%s&typeId=%s&tradeNo=%s&tradeDate=%s&amount=%s&notifyUrl=%s&extra=%s&summary=%s&expireTime=%s&clientIp=%s",
                $data['service'],
                $data['version'],
                $data['merId'],
                $data['typeId'],
                $data['tradeNo'],
                $data['tradeDate'],
                $data['amount'],
                $data['notifyUrl'],
                $data['extra'],
                $data['summary'],
                $data['expireTime'],
                $data['clientIp']
            );
            return $result;
        } else if ($data['service'] == 'TRADE.NOTIFY') {//7回调
            $result = sprintf(
                "service=%s&merId=%s&tradeNo=%s&tradeDate=%s&opeNo=%s&opeDate=%s&amount=%s&status=%s&extra=%s&payTime=%s",
                $data['service'],
                $data['merId'],
                $data['tradeNo'],
                $data['tradeDate'],
                $data['opeNo'],
                $data['opeDate'],
                $data['amount'],
                $data['status'],
                $data['extra'],
                $data['payTime']
            );
            return $result;
        } else if ($data['service'] == 'TRADE.H5PAY') {//h5支付
            $result = sprintf(
                "service=%s&version=%s&merId=%s&typeId=%s&tradeNo=%s&tradeDate=%s&amount=%s&notifyUrl=%s&extra=%s&summary=%s&expireTime=%s&clientIp=%s",
                $data['service'],
                $data['version'],
                $data['merId'],
                $data['typeId'],
                $data['tradeNo'],
                $data['tradeDate'],
                $data['amount'],
                $data['notifyUrl'],
                $data['extra'],
                $data['summary'],
                $data['expireTime'],
                $data['clientIp']
            );
            return $result;
        } else if ($data['service'] == 'TRADE.QUICKPAY.APPLY') {//快捷支付
            $result = sprintf("service=%s&version=%s&merId=%s&tradeNo=%s&tradeDate=%s&amount=%s&notifyUrl=%s&extra=%s&summary=%s&expireTime=%s&clientIp=%s&cardType=%s&cardNo=%s&cardName=%s&idCardNo=%s&mobile=%s&cvn2=%s&validDate=%s",
                $data['service'],
                $data['version'],
                $data['merId'],
                $data['tradeNo'],
                $data['tradeDate'],
                $data['amount'],
                $data['notifyUrl'],
                $data['extra'],
                $data['summary'],
                $data['expireTime'],
                $data['clientIp'],
                $data['cardType'],
                $data['cardNo'],
                $data['cardName'],
                $data['idCardNo'],
                $data['mobile'],
                $data['cvn2'],
                $data['validDate']
            );
            return $result;
        } else if ($data['service'] == 'TRADE.QUICKPAY.CONFIRM') {
            $result = sprintf("service=%s&version=%s&merId=%s&opeNo=%s&opeDate=%s&sessionID=%s&dymPwd=%s",
                $data['service'],
                $data['version'],
                $data['merId'],
                $data['opeNo'],
                $data['opeDate'],
                $data['sessionID'],
                $data['dymPwd']
            );
            return $result;
        }
    }

    /**
     * 获取支付签名
     * @param string $data 支付参数
     * @return string $sign签名值
     */
    private function sign($data)
    {
        $signature = md5($data . $this->key);
        return $signature;
    }

    /**
     * 准备带有签名的request字符串
     * @param string $string request字符串
     * @param string $signature 签名数据
     * @return string
     */
    private function prepareRequest($string, $signature)
    {
        return $string . '&sign=' . $signature;
    }

    /**
     * 请求接口
     * @param $data
     * @return mixed
     */
    private function request($data)
    {
        $curl = curl_init();
        $curlData = array();
        $curlData[CURLOPT_POST] = true;
        $curlData[CURLOPT_URL] = $this->url;
        $curlData[CURLOPT_RETURNTRANSFER] = true;
        $curlData[CURLOPT_TIMEOUT] = 120;
        $curlData[CURLOPT_POSTFIELDS] = $data;
        curl_setopt_array($curl, $curlData);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        $result = curl_exec($curl);
        curl_close($curl);
        return $result;
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
            'data' => $data,
            'url' => $this->url
        ];
        $rs['jump'] = 5;
        $rs['url'] = $this->domain . '/index.php/pay/pay_test/pay_sest/' . $this->orderNum;
        $rs['json'] = json_encode($temp, JSON_UNESCAPED_UNICODE);
        return $rs;
    }

    /**
     * 验证签名
     * @param array    signData 签名数据
     * @param array    sourceData 原数据
     * @param sting    $extra   额外数据
     * @return bool
     */
    public function verify($data, $signature, $extra)
    {
        $this->key = $extra;
        $mySign = $this->sign($data);
        if (strcasecmp($mySign, $signature) == 0) {
            return true;
        } else {
            return false;
        }
    }
}