<?php

defined('BASEPATH') or exit('No direct script access allowed');

include_once __DIR__.'/Basepay_model.php';
/**
 * 网游支付模块
 *
 * @author      ma
 * @package     model/online/Wangyou
 * @version     v1.0 2017/11/23
 * @copyright
 * @link
 */
class Wangyou_model extends Basepay_model
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
                return 'Z0';
            case 4:
                return 'A0';
            default:
                return 'Z0';
        }
    }

    /**
     * 获取支付参数
     *
     * @access protected
     * @param String $order_num 订单号
     * @param Float $money 金额
     * @param String $pay_data 第三方支付信息
     * @return Array
     */
    protected function _get_data($order_num, $money, $pay_data)
    {
        $data['amount']  =  floor($money*100);//金额
        $data['mch_id'] = (string)trim($pay_data['pay_id']);//商户id
        $data['notify_url'] = $pay_data['pay_domain'].'/index.php/callback/Wangyou/callbackurl';//回调地址
        $data['out_trade_no'] = $order_num;//订单号
        $data['mch_create_ip'] = '66.212.31.50';//生成订单号的机器ip（fake）
        //$data['mch_create_ip'] = $_SERVER['SERVER_ADDR'];
        $data['time_start'] = date('YmdHis');//提交时间
        $data['body'] = '00';//商品描述
        $data['attach'] = '00';//详细备注
        $data['nonce_str'] = mt_rand(10000000,99999999);//随机字符串
        $data['trade_type'] = $this->_get_code($pay_data['code'], $pay_data['bank_type']);
        return $data;
    }

    /**
     * 获取支付签名
     *
     * @access protected
     * @param Array $data 支付参数
     * @param Array $pay_data 支付信息
     * @return String $sign签名值
     */
    protected function _get_sign($data, $pay_data)
    {
        $tokenKey = $pay_data['pay_key'];
        $args = $data;
        ksort($args);
        $requestString = '';
        foreach($args as $k => $v) {
            $requestString .= $k . '='.($v);
            $requestString .= '&';
        }
        $requestString = substr($requestString,0,strlen($requestString)-1);
        $newSign = md5( $requestString."&key=".$tokenKey);
        return $newSign;
    }



    /**
     * 获取支付结果
     *
     * @access protected
     * @param String $order_num 订单号
     * @param Array $data 支付参数
     * @param Array $pay_data 支付信息
     * @return Array
     */
    protected function _get_result($order_num, $ZFdata, $pay_data)
    {
        $data = parent::_get_result2('get', $order_num, $ZFdata, $pay_data);
        $new_data = json_decode($data['json'],true);
        $ci = get_instance();
        if (empty($new_data['url'])){
            $ci->return_json(E_ARGS, '获取二维码信息失败请更换更换支付方式1');
        }
        $this->load->model('pay/Online_model');
        $this->Online_model->set_get_detailo($order_num, $data['json']);
        unset($data['json']);
        $img_info = pay_curl($new_data['url'],$new_data['data'],'get',$pay_data['pay_domain']);
        $img_info = json_decode($img_info,true);
        if(!empty($img_info['payUrl'])){
            if($order_num != trim($img_info['out_trade_no'])){
                $ci->return_json(E_ARGS, '第三方返回信息错误！请更换支付方式');
            }
            $data['img']  = rtrim($img_info['payUrl'], "\"");
            $data['jump'] = 3;
            $data['is_img'] = 0;
        }else{
            $ci->return_json(E_ARGS, '获取二维码信息失败请更换更换支付方式2');
        }
        return $data;
    }
}
