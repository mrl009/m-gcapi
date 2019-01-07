<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * 支付回调父控制器
 *
 * @author      ssm
 * @package     controllers/callback/Basepay
 * @version     v1.0 2017/08/30
 * @copyright
 * @link
 */
abstract class Basepay extends GC_Controller
{

	const CALLBACKURL = 1;
    const HREFBACKURL = 2;

	/**
     * 构造方法
     *
     */
    public function __construct()
    {
        parent::__construct();
        $this->load->model('pay/Online_model', 'core');
    }

    /**
     * 抽象方法：子类实现自己的签名验证
     *
     * @access protected
     * @param Array $data 回调参数
     * @param Array $payconf 支付信息
     * @return Boolean
     */
    protected abstract function _is_sign_succeed($data, $payconf);

    /**
     * 给子类调用的方法
     *
     * @access protected
     * @param Array $param
     * @return
     */
    protected function _action($param=[])
    {
    	/*** 1.验证数据 ***/
    	$callmethod = $param['callmethod'];
        $result = $this->_verify_data($param);

        switch ($result['status']) {
            case 1:
                $bool = false;
                break;
            case 2:
            	$bool = $this->core->update_order($result['data']['payconf']);
                break;
            case 3:
                $bool = true;
                break;
            default:
                $bool = true;
                break;
        }
        if ($result['status'] == 3) {
            $this->_get_succeed(self::CALLBACKURL, $result['data']);
            exit;
        }
        if ($bool) {
            $this->_get_succeed($callmethod, $result['data']);
        } else {
            $this->_get_fail($callmethod, $result['data']);
        }
    }

    /**
     * 验证方法
     *
     * @access private
     * @param Array $param ['method'=>'获取回调参数类型',
     						'call_order_key'=>'订单key',
                        	'call_status_key'=>'状态key', 'call_status_pass'=>'状态正确值',
                        	'price_unit'=>'金额进制']
     * @return Array status:1=失败，2=成功，3=以交易
     * [status=>'状态', ['id'=>'支付id', 'message'=>'错误信息',
                        'data'=>'回调数据','payconf'=>'系统订单数据(status=2才有)']]
     */
    private function _verify_data($param=[])
    {
    	$method = $param['method'];
    	$price_unit = $param['price_unit'];
    	$call_order_key = $param['call_order_key'];
    	$call_status_key = $param['call_status_key'];
        $call_price_key = $param['call_price_key'];
    	$call_status_pass = $param['call_status_pass'];
        $globals_name = empty($param['globals_name'])?'':$param['globals_name'];

        $calldata = $this->_get_calldata($method, $globals_name);

        /*** 验证数据的正确性 ***/
        if (!$calldata || empty($calldata[$call_order_key])) {
            return ['status'=>1,'data'=>['id' => -1,'data' => $calldata,
                        'message' => '回调数据错误']];
        }
        $payconf = $this->core->order_detail($calldata[$call_order_key]);
        if (empty($payconf)) {
            return ['status'=>1,'data'=>['id' => -1,'data' => $calldata,
                        'message' => '订单号错误']];
        }
        if ($calldata[$call_status_key] != $call_status_pass) {
            return ['status'=>1,'data'=>['id' => $payconf['id'],'message' => '交易错误',
                        'data' => $calldata]];
        }
        if (!$this->_is_sign_succeed($calldata, $payconf)) {
            return ['status'=>1,'data'=>['id' => $payconf['id'],'message' => '签名错误',
                        'data' => $calldata]];
        }
        if ((int)(float)($payconf['price']*$price_unit) !=
            (int)(float)$calldata[$call_price_key]) {
            return ['status'=>1,'data'=>['id' => $payconf['id'],'message' => '金额错误',
                        'data' => $calldata]];
        }
        if ($payconf['status'] == 2) {
            return ['status'=>3,'data'=>['id' => $payconf['id'],'message' => '订单以确认',
                        'data' => $calldata, 'payconf'=>$payconf]];
        }
        return ['status'=>2,'data'=>['id' => $payconf['id'],'message' => '订单正确回调',
                        'data' => $calldata, 'payconf'=>$payconf]];
    }

    /**
     * 获取回调参数
     *
     * @access private
     * @param String $method get|post|other
     * @return Array
     */
    private function _get_calldata($method='post', $param='')
    {
        switch ($method) {
        	case 'get':
        		$data = $_GET;
        		break;

        	case 'post':
        		$data = $_POST;
        		break;

            case 'globals':
                if (isset($GLOBALS[$param])) {
                    $data = $GLOBALS[$param];
                } else {
                    $data = file_get_contents('php://input');
                }
                $data = str_replace("\\", '',$data);
                $data = json_decode($data, true);
                break;

            case 'request':
                $data = $_REQUEST;
                break;

        	default:
        		$data = $_POST;
        		break;
        }
        return $data;
    }

    /**
     * 获取成功字符串
     *
     * @access private
     * @param Integer $type 类型
     * @param Array $data 数据['payconf'=>[], 'data'=>[]]
     * @return String
     */
    private function _get_succeed($type='', $data='')
    {
        switch ($type) {
            case 1:
                echo $this->echo_str;
                break;

            case 2:
                $payconf = $data['payconf'];
                $data = [
                    'money' => $payconf['price'],
                    'type'  => code_pay($payconf['pay_code']),
                    'jsstr' => $this->core->return_jsStr($payconf['from_way'], $payconf['pay_return_url']),
                    'ordernumber'=> $payconf['order_num']
                ];
                $this->load->view('online_pay/success.html', $data);
                break;

            default:
                echo $this->echo_str;
                break;
        }
    }

    /**
     * 获取失败字符串
     *
     * @access private
     * @param Integer $type 类型
     * @param Array $data 数据['id'=>int, 'message'=>string, 'data'=[]]
     * @return void
     */
    private function _get_fail($type='', $data2='')
    {

        if (is_array($data2)) {
            $id = empty($data2['id']) ? '-1' : $data2['id'];
            $data = empty($data2['data']) ? [] : $data2['data'];
            $message = empty($data2['message']) ? '错误' : $data2['message'];
        } else {
            $id = -1;
            $data = [];
            $message = '错误';
        }

        switch ($type) {
            case 1:
                $erroStr = '错误信息:'.json_encode($data, JSON_UNESCAPED_UNICODE);
                $this->core->online_erro($id.':'.$message, $erroStr);
                echo $message;
                // exit;
                break;

            case 2:
                $data = ['msg' =>$message];
                $this->load->view('online_pay/error.html', $data);
                break;

            default:
                $erroStr = '错误信息:'.json_encode($data, JSON_UNESCAPED_UNICODE);
                $this->core->online_erro($id.':'.$message, $erroStr);
                echo $message;
                // exit;
                break;
        }
    }
}
