<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * 新宝回调控制器
 *
 * @author      ssm
 * @package     controllers/callback/xinbao
 * @version     v1.0 2017/08/30
 * @copyright
 * @link
 */
class Xinbao extends GC_Controller
{
    /**
     * 构造方法
     *
     */
    public function __construct()
    {
        parent::__construct();
        $this->load->model('pay/Online_model', 'core');
    }

    public $echo_str = "ok";  //回调地址层

    /**
     * 异步回调方法
     *
     * @access public
     */
    public function callbackurl()
    {
        /*** 1.获取回调参数 ***/
        $calldata = $this->_get_calldata('post');
        if (!$calldata) {
            $this->_get_fail('callbackurl', -1, $calldata, 'POST参数为空');
            return;
        }

        /*** 2.获取支付信息 ***/
        if (empty($calldata['order_no'])) {
            $this->_get_fail('callbackurl', -1, $calldata, '订单号为空');
            return;
        }
        $payconf = $this->core->order_detail($calldata['order_no']);
        if (empty($payconf)) {
            $this->_get_fail('callbackurl', -1, $calldata, '订单号错误');
            return;
        }

        /*** 3.最后的验证 ***/
        if (!$this->_verify($calldata, $payconf)) {
            $this->_get_fail('callbackurl', $payconf['id'], $calldata);
            return;
        }

        /*** 4.添加数据 ***/
        $bool = $this->core->update_order($payconf);
        if ($bool) {
            $this->_get_succeed('callbackurl', $calldata, $payconf);
            return;
        }
        return $this->_get_fail('callbackurl', $payconf['id'], $calldata);
    }

    /**
     * 同步回调方法
     *
     * @access public
     */
    public function hrefbackurl()
    {
        /*** 1.获取回调参数 ***/
        $calldata = $this->_get_calldata('get');
        if (!$calldata) {
            $this->_get_fail('hrefbackurl', -1, $calldata, 'POST参数为空');
            return;
        }

        /*** 2.获取支付信息 ***/
        if (empty($calldata['order_no'])) {
            $this->_get_fail('hrefbackurl', -1, $calldata, '订单号为空');
            return;
        }
        $payconf = $this->core->order_detail($calldata['order_no']);
        if (empty($payconf)) {
            $this->_get_fail('hrefbackurl', -1, $calldata, '订单号错误');
            return;
        }

        /*** 3.最后的验证 ***/
        if (!$this->_verify($calldata, $payconf)) {
            $this->_get_fail('hrefbackurl', $payconf['id'], $calldata);
            return;
        }

        $this->_get_succeed('hrefbackurl', $calldata, $payconf);
        return;
    }




    /**
     * 获取回调参数
     *
     * @access private
     * @return Boolean|Array
     */
    private function _get_calldata($method='post')
    {
        switch ($method) {
            case 'post':
                return $_POST;
                break;

            case 'get':
                return $_GET;
                break;

            default:
                return $_POST;
                break;
        }
        // return json_decode('{"code":"00","message":"Pay Success","partner_id":"108674","order_no":"59a6aae72548a","trade_no":"1152948005494105841","amount":"1.0000","attach":"","sign":"9669a97db95205e8b75b334e41e370ac"}', true);
    }

    /**
     * 最后验证数据
     *
     * @access private
     * @param Array $data 回调参数
     * @param Array $payconf 支付信息
     * @return Boolean
     */
    private function _verify($data, $payconf)
    {
        /*** 1.验证交易状态是否成功 ***/
        if ($data['code'] != '00') {
            return false;
        }

        /*** 2.验证签名是否成功 ***/
        if (!$this->_is_sign_succeed($data, $payconf)) {
            return false;
        }

        /*** 3.验证金额是否正确 ***/
        if ($payconf['price'] != $data['amount']) {
            return false;
        }

        /*** 4.已充值的状态 ***/
        if ($payconf['status'] == 2) {
            return true;
        }
        return true;
    }

    /**
     * 是否签名成功
     *
     * @access private
     * @param Array $data 回调参数
     * @param Array $payconf 支付信息
     * @return Boolean
     */
    private function _is_sign_succeed($data, $payconf)
    {
        $tokenKey = $payconf['pay_key'];
        $sign1 = $data['sign'];
        unset($data['sign']);
        ksort($data);
        $k = '';
        foreach ($data as $key => $value) {
            if (!empty($value)) {
                $k .= $key.'='.$value.'&';
            }
        }
        $sign2 = strtolower(md5($k.$tokenKey));
        if ($sign2 === $sign1) {
            return true;
        }
        return false;
    }

    /**
     * 获取成功字符串
     *
     * @access private
     * @return String
     */
    private function _get_succeed($str='', $data='',$payconf='')
    {
        if ($str == 'hrefbackurl') {
            $data = [
                'ordernumber'=>$data['order_no'],
                'money'     =>$data['amount'],
                'jsstr'  =>$this->core->return_jsStr($payconf['from_way'], $payconf['pay_return_url']),
                'type'  =>code_pay($payconf['pay_code'])
            ];
            $this->load->view('online_pay/success.html', $data);
        } else {
            echo 'ok';
            exit;
        }
    }

    /**
     * 获取失败字符串
     *
     * @access private
     * @param Integer $id 支付IDas
     * @param Array $data 支付回调参数
     * @param String $message 输出信息
     * @return void
     */
    private function _get_fail($str='',$id='',$data='',$message='fail')
    {
        if ($str == 'hrefbackurl') {
            $data = [
                'msg' =>$message
            ];
            $this->load->view('online_pay/error.html', $data);
        } else {
            $erroStr = '错误信息:'.json_encode($data, JSON_UNESCAPED_UNICODE);
            $this->core->online_erro($id.':'.$message, $erroStr);
            echo $message;
            exit;
        }
    }
}
