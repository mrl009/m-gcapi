<?php

defined('BASEPATH') or exit('No direct script access allowed');

include_once __DIR__ . '/Basepay_model.php';

/**
 * 长城付支付模块
 *
 * @author      ssm
 * @package     model/online/Changcheng
 * @version     v1.0 2017/10/13
 * @copyright
 * @link
 */
class Changcheng_model extends Basepay_model
{

    /**
     *【支付类型@1微信#2微信app#3微信扫码#
     * 4支付宝#5支付宝APP#6支付宝扫码
     * #7网银#8QQ钱包#9京东钱包#10百度钱包#11点卡
     *
     * @access protected
     * @param Integer $code 支付编号
     * @param String $bank 银行编号
     * @return String
     */
    protected function _get_code($code, $bank)
    {
        switch ($code) {
            case 1:
                return '2';//微信扫码支付
                break;
            case 2:
                return '2';//微信APP
                break;
            case 4:
                return '1';//支付宝扫码支付
                break;
            case 5:
                return '1';//支付宝APP
                break;
            case 8:
                return '4';//QQ钱包
                break;
            case 9:
                return '5';//京东钱包
                break;
            case 10:
                return '3';//百度钱包
            case 12:
                return '4';//QQ钱包APP
                break;
            case 15:
                return '5';//京东二维码(公众二维码)
                break;
            case 16:
                return '4';//QQ二维码(公众二维码)
                break;
            case 21:
                return '3';//百度钱包二维码(公众二维码)
                break;
            case 33:
                return '2';//微信H5(H5扫码)
                break;
            case 36:
                return '1';
                break;
            default:
                return '2';
        }
    }

    /**
     * 获取支付地址
     * @param $code
     * @param $pay_url 'http://a.cc8pay.com/api/passivePay,http://a.cc8pay.com/api/openPay,http://a.cc8pay.com/api/wapPay'
     * @return mixed
     */
    private function _get_pay_url($code, $pay_url)
    {
        $url = empty($pay_url) ? '' : explode(',', $pay_url);
        if (empty($url) || count($url) != 3) {
            exit($this->returnJson(E_ARGS, '获取支付地址失败'));
        }
        $rs = $url[0];
        if (in_array($code, [1, 4, 8, 9, 10])) {
            $rs = $url[0];
        } elseif (in_array($code, [15, 16, 21, 33, 36])) {
            $rs = $url[1];
        } elseif (in_array($code, [2, 5, 12])) {
            $rs = $url[2];
        }
        return $rs;
    }

    /**
     * 获取jump类型
     * @param $code
     * @return int
     */
    private function _get_jump($code) {
        return in_array($code, [2, 5, 12,15, 16, 21, 33, 36]) ? 5 : 3;
    }

    /**
     * 获取支付参数
     * @param String $order_num 订单号
     * @param Float $money 金额
     * @param String $pay_data 第三方支付信息
     * @return array|mixed
     */
    protected function _get_data($order_num, $money, $pay_data)
    {
        $data['merchno'] = (string)trim($pay_data['pay_id']);
        $data['notifyUrl'] = $pay_data['pay_domain'] . '/index.php/callback/Changcheng/callbackurl';
        $data['amount'] = $money;
        $data['traceno'] = $order_num;
        $bank_code = $this->_get_code($pay_data['code'], $pay_data['bank_type']);
        $data['payType'] = $bank_code;
        return $data;
    }

    /**
     * 获取支付签名
     * @param array $data 支付参数
     * @param array $pay_data 支付信息
     * @return string $sign签名值
     */
    protected function _get_sign($data, $pay_data)
    {
        $tokenKey = $pay_data['pay_key'];
        ksort($data);
        $k = '';
        foreach ($data as $key => $value) {
            $k .= $key . '=' . $value . '&';
        }
        return (md5($k . $tokenKey));
    }

    /**
     * 获取支付结果
     * @param String $order_num 订单号
     * @param array $ZFdata 支付参数
     * @param array $pay_data 支付信息
     * @return array|mixed
     */
    protected function _get_result($order_num, $ZFdata, $pay_data)
    {
        $ZFdata['signature'] = $ZFdata['sign'];
        unset($ZFdata['sign']);
        $pay_url = $this->_get_pay_url($pay_data['code'], $pay_data['pay_url']);
        $res = $this->pay_curl($pay_url, $pay_data['pay_domain'], $ZFdata);
        $res = iconv('GB2312', 'UTF-8', $res);
        $res = json_decode($res, true);
        if ($res['respCode'] != '00') {
            if (empty($res['message'])) {
                $res['message'] = '请求超时，请联系客服！';
            }
            $str = "错误信息: {$res['message']}";
            $this->_error_echo($str);
        }
        $data['jump'] = $this->_get_jump($pay_data['code']); //设置支付方式的返回格式
        $data['img'] = $data['jump'] == 3 ? $res['barCode'] : '';//二维码的
        $data['url'] = $data['jump'] == 5 ? $res['barCode'] : '';//跳转地址
        $data['money'] = $ZFdata['amount']; //支付的钱
        $data['order_num'] = $ZFdata['traceno'];//订单号
        return $data;
    }
}
