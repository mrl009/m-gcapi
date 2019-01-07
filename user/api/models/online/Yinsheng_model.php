<?php

defined('BASEPATH') or exit('No direct script access allowed');

include_once __DIR__.'/Basepay_model.php';
/**
 * 支付模块
 *
 * @author      superma
 * @package     model/online/Jinyang
 * @version     v1.0 2017/11/08
 * @copyright
 * @link
 */
class Yinsheng_model extends Basepay_model
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
                return 'WEIXIN';
            case 2:
                return 'WEIXINWAP';
            case 4:
                return 'ALIPAY';
            case 5:
                return 'ALIPAYWAP';
            case 7:
                return $bank;
            case 8:
                return 'QQPAY';
            case 9:
                return 'JDPAY';
            default:
                return 'WEIXIN';
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
        $data['p1_mchtid'] = (string)trim($pay_data['pay_id']);
        $data['p2_paytype']  = $this->_get_code($pay_data['code'], $pay_data['bank_type']);
        $data['p3_paymoney']  =  sprintf('%.2f',$money);
        $data['p4_orderno'] = $order_num;
        $data['p5_callbackurl'] = $pay_data['pay_domain'].'/index.php/callback/Zaixianbao/callbackurl';
        $data['p6_notifyurl'] = $pay_data['pay_domain'].'/index.php/callback/Zaixianbao/hrefbackurl';
        $data['p7_version'] = 'v2.8';
        $data['p8_signtype'] = 1;
        $data['p9_attach'] = '';
        $data['p10_appname'] = '';
        $data['p11_isshow'] = 0;
        $data['p12_orderip'] = '';
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
        $a = sprintf("p1_mchtid=%d&p2_paytype=%s&p3_paymoney=%.2f&p4_orderno=%s&p5_callbackurl=%s&p6_notifyurl=%s&p7_version=%s&p8_signtype=%d&p9_attach=%s&p10_appname=%s&p11_isshow=%d&p12_orderip=%s%s",
            $data['p1_mchtid'], $data['p2_paytype'], $data['p3_paymoney'], $data['p4_orderno'], $data['p5_callbackurl'], $data['p6_notifyurl'],
            $data['p7_version'], $data['p8_signtype'], $data['p9_attach'], $data['p10_appname'],$data['p11_isshow'], $data['p12_orderip'],$tokenKey);
        return md5($a);
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

        $data = parent::_get_result2('post', $order_num, $ZFdata, $pay_data);
        $new_data = json_decode($data['json'],true);
        $ci = get_instance();
        if (empty($new_data['url'])){
            $ci->return_json(E_ARGS, '获取二维码信息失败请更换支付方式1');
        }
        $this->load->model('pay/Online_model');
        $this->Online_model->set_get_detailo($order_num, $data['json']);
        $img_info = pay_curl($new_data['url'],http_build_query($new_data['data']),'post',$pay_data['pay_domain']);
        //$img_info  = $this->run2($new_data['url'],$new_data['data'],'post');
        $img_info = json_decode($img_info,true);
        if(!empty($img_info['data']['r6_qrcode'])){
            if($order_num != trim($img_info['data']['r3_orderno'])){
                $ci->return_json(E_ARGS, '第三方返回信息错误！请更换支付方式');
            }
            $data['img']  = $img_info['data']['r6_qrcode'];
            $data['jump'] = 3;
            $data['is_img'] = 1;//是否直接给图片地址，如果为0，则需要根据返回的地址自己生成图片
        }else{
            $ci->return_json(E_ARGS, '获取二维码信息失败请更换支付方式2');
        }
        //echo '<img src="'.$data['img'].'">';exit;
        return $data;
    }

    private function run2($url, $data, $method)
    {
        $str = '<html>
        <head>
            <!--支付测试表单提交-->
            <title>跳转...</title>
            <meta http-equiv="content-Type" content="text/html; charset=utf-8" />
        </head>
        <body onload="document.form1.submit();">
        <p>页面跳转中...</p>';
        $str .= '<form id="form1" name="form1" method="'.$method.'" action="'.$url.'">';

        foreach ($data as $key => $value) {
            $str .= '<input type="hidden" name="'.$key.'" value="'.$value.'"/>';
        }
        $str .= '</form>';
        $str .=  '</body>';
        $str .= '</html>';
        echo $str;
    }

    private function run($url, $data, $method)
    {
        $headers = array(
            'Referer:http://www.jb51.net;'
        );
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_REFERER, "http://www.jb51.net");
        curl_setopt($ch, CURLOPT_TIMEOUT,(int)7);
        $res=curl_exec($ch);
        $httpCode = curl_getinfo($ch,CURLINFO_HTTP_CODE);
        curl_close($ch);
        return [$httpCode, $res];
    }
}
