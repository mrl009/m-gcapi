<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * 智通宝
 *
 * @file        Zhidebao.php
 * @package     user/controllers/online/
 * @author      marks
 * @version     v1.0 2017/12/26
 * @created     2017/12/26
 */
class Zhidebao extends GC_Controller
{



    //错误响应
    public $echo_error = "error";
    //成功响应
    public $echo_str = "SUCCESS";


    public function __construct()
    {
        parent::__construct();
        $this->load->model('pay/Online_model');
    }


    /**
     * 智付异步回调接口
     *
     * @access public
     * @return success|error
     */
    public function callbackurl()
    {

        $post = $this->_get_param();               // 获取回调参数
        if (empty($post['zhihpaySign'])) {
            die('参数错误');
        }
        $ordernumber = $post['order_no'];   // 订单号

        $bool = $this->Online_model->fbs_lock('temp:new_order_num'.$ordernumber);
        if (!$bool) {
            die('请稍后');
        }

        // 根据订单号获取配置信息
        $payconf = $this->Online_model->order_detail($ordernumber);
        if (empty($payconf)) {
            $erroStr = '无效的订单号:'.json_encode($_POST, JSON_UNESCAPED_UNICODE);
            $this->Online_model->online_erro('zhidebao', $erroStr);
            die($this->echo_error);
        }

        $dinpay_public_key = $payconf['pay_server_key'];
        // 根据回调参数和公钥验证签名
        $flag = $this->_sign($post, $dinpay_public_key);

        if ($post['trade_status'] != 'SUCCESS') {
            $erroStr = '交易不成功:'.json_encode($_POST, JSON_UNESCAPED_UNICODE);
            $this->Online_model->online_erro($payconf['id'], $erroStr);
            die($this->echo_error);
        }

        if (!$flag) {
            $erroStr = '验证签名失败:'.json_encode($_POST, JSON_UNESCAPED_UNICODE);
            $this->Online_model->online_erro($payconf['id'], $erroStr);
            die($this->echo_error);
        }

        $paymoney = $post['order_amount'];
        if ($payconf['price'] != $paymoney) {
            $erroStr = '订单金额验证失败:'.json_encode($_REQUEST, JSON_UNESCAPED_UNICODE);
            $this->Online_model->online_erro($payconf['id'], $erroStr);
            die('金额错误');
        }
        //已经确认
        if ($payconf['status'] == 2) {
            echo $this->echo_str;
            die;
        }
        $bool = $this->Online_model->update_order($payconf);
        if ($bool) {
            echo $this->echo_str;
            die;
        }
        $erroStr = '写入现金记录失败:'.json_encode($_GET, JSON_UNESCAPED_UNICODE);
        $this->Online_model->online_erro($payconf['id'], $erroStr);
        echo "加钱失败";
    }




    /**
     * 获取异步通知参数
     *
     * @access private
     * @return Array $post  回调参数数组
     */
    private function _get_param()
    {
        $post['merchant_code']  = $this->P("merchant_code");
        $post['notify_type'] = $this->P("notify_type");
        $post['notify_id'] = $this->P("notify_id");
        $post['interface_version'] = $this->P("interface_version");
        $post['sign_type'] = $this->P("sign_type");
        $post['zhihpaySign'] = $this->P("sign");
        $post['order_no'] = $this->P("order_no");
        $post['order_time'] = $this->P("order_time");
        $post['order_amount'] = $this->P("order_amount");
        $post['trade_no'] = $this->P("trade_no");
        $post['trade_time'] = $this->P("trade_time");
        $post['trade_status'] = $this->P("trade_status");
        $post['bank_seq_no'] = $this->P("bank_seq_no");
        if (!empty($post['zhihpaySign'])) {
            $post['zhihpaySign'] = base64_decode($post['zhihpaySign']);
        }
        return $post;
    }

    /**
     * 验证签名
     *
     * @access private
     * @param Array $post   回调参数数组
     * @param String $dinpay_public_key 公钥
     * @return boolean $flag
     */
    private function _sign($post, $dinpay_public_key)
    {
        extract($post);
        /////////////////////////////   参数组装  /////////////////////////////////
/**
除了sign_type dinpaySign参数，其他非空参数都要参与组装，组装顺序是按照a~z的顺序，下划线"_"优先于字母
*/
        $signStr = "";
    
        if ($bank_seq_no != "") {
            $signStr = $signStr."bank_seq_no=".$bank_seq_no."&";
        }

        $signStr = $signStr."interface_version=".$interface_version."&";

        $signStr = $signStr."merchant_code=".$merchant_code."&";

        $signStr = $signStr."notify_id=".$notify_id."&";

        $signStr = $signStr."notify_type=".$notify_type."&";

        $signStr = $signStr."order_amount=".$order_amount."&";

        $signStr = $signStr."order_no=".$order_no."&";

        $signStr = $signStr."order_time=".$order_time."&";

        $signStr = $signStr."trade_no=".$trade_no."&";

    
        $signStr = $signStr."trade_status=".$trade_status."&";
        
        $signStr = $signStr."trade_time=".$trade_time;
    
/////////////////////////////   RSA-S验签  /////////////////////////////////

    $dinpay_public_key = openssl_get_publickey($dinpay_public_key);   
    
    $flag = openssl_verify($signStr,$zhihpaySign,$dinpay_public_key,OPENSSL_ALGO_MD5); 
    
//////////////////////   异步通知必须响应“SUCCESS” /////////////////////////
/**
如果验签返回ture就响应SUCCESS,并处理业务逻辑，如果返回false，则终止业务逻辑。
*/
return $flag;
    }
}
