<?php
/**
 * 第三方支付回调：讯宝商务
 *
 * @author      ssm
 * @version     v1.0 2017/07/17
 * @created     2017/07/17
 */
defined('BASEPATH') or exit('No direct script access allowed');

class Gaotong extends GC_Controller
{
    /**
     * 回调地址层共返回
     * @var String
     */
    public $echo_str = "ok";
    public function __construct()
    {
        parent::__construct();
        $this->load->model('pay/Online_model');
    }


    // 异步通知地址
    public function callbackurl()
    {
        $data = $this->_get_param();
        $ordernumber    = $data['ordernumber'];  //商户订单号
        $payconf     = $this->Online_model->order_detail($ordernumber);
        $bool = $this->Online_model->fbs_lock('temp:new_order_num'.$ordernumber);
        if (!$bool) {
            die('请稍后');
        }
        // 判断订单是否正确
        if (empty($payconf)) {
            $erroStr = '无效的订单号:'.json_encode($_GET, JSON_UNESCAPED_UNICODE);
            $this->Online_model->online_erro('Xunbao', $erroStr);
            die('无效的订单号');
        }

        //判断支付状态
        if ($data['orderstatus'] != 1) {
            $erroStr = '订单状态未成功:'.json_encode($_GET, JSON_UNESCAPED_UNICODE);
            $this->Online_model->online_erro($payconf['id'], $erroStr);
            die('订单失败');
        }

        //判断签名是否正确
        $flag = $this->_decrypt($data, $payconf['pay_key']);
        if (!$flag) {
            $erroStr = '订单状态未成功:'.json_encode($_GET, JSON_UNESCAPED_UNICODE);
            $this->Online_model->online_erro($payconf['id'], $erroStr);
            die('签名错误');
        }

        // 判断金额是否正确
        if ($payconf['price'] != $data['paymoney']) {
            $erroStr = '订单金额验证失败:'.json_encode($_GET, JSON_UNESCAPED_UNICODE);
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

    // 同步通知地址
    public function hrefbackurl()
    {
        $data = $this->_get_param();
        $ordernumber    = $data['ordernumber'];  //商户订单号
        $payconf     = $this->Online_model->order_detail($ordernumber);
        $flag = $this->_decrypt($data, $payconf['pay_key']);
        // 判断订单是否正确
        if (empty($payconf)) {
            $data = ['msg' =>'订单错误'];
            $this->load->view('online_pay/error.html', $data);
        }
        //判断支付状态
        elseif ($data['orderstatus'] != 1) {
            $data = ['msg' =>'支付不成功'];
            $this->load->view('online_pay/error.html', $data);
        }
        //判断签名是否正确
        elseif (!$flag) {
            $data = ['msg' =>'签名不正确'];
            $this->load->view('online_pay/error.html', $data);
        }
        // 判断金额是否正确
        elseif ($payconf['price'] != $data['paymoney']) {
            $data = ['msg' =>'金额不正确'];
            $this->load->view('online_pay/error.html', $data);
        }
        // 交易成功
        else {
            $data = [
                'ordernumber'=>$ordernumber,
                'money' =>$payconf['price'],
                'jsstr'  =>$this->Online_model->return_jsStr($payconf['from_way'] ,$payconf['pay_return_url']),
                'type'  =>code_pay($payconf['pay_code'])
            ];
            $this->load->view('online_pay/success.html', $data);
        }
    }

    /**
     * 签名是否正确
     *
     * @param Array $data 回调数据
     * @param String $key 商户密钥
     * @return boolean
     */
    private function _decrypt($data,$paykey)
    {
        $dec_arr['partner'] = $data['partner'];
        $dec_arr['ordernumber'] = $data['ordernumber'];
        $dec_arr['orderstatus'] = $data['orderstatus'];
        $dec_arr['paymoney'] = $data['paymoney'];
        $str = '';
        foreach ($dec_arr as $key => $value) {
            $str .= $key.'='.$value.'&';
        }
        $str = rtrim($str, '&').$paykey;
        $key = md5($str);
        if ($key == $data['sign']) {
            return true;
        }
        return false;
    }

    /**
     * 获取回调参数
     *
     * @return Array
     */
    private function _get_param()
    {
        $data = [
            'ordernumber' => $this->input->get('ordernumber'),
            'orderstatus' => $this->input->get('orderstatus'),
            'paymoney' => $this->input->get('paymoney'),
            'sign' => $this->input->get('sign'),
            'partner' => $this->input->get('partner')
        ];
        return $data;
    }
}
