<?php

/**
 * 嘉亿代付回调接口调用
 * Created by PhpStorm.
 * User: Tailand
 * Date: 2018/11/14
 * Time: 15:02
 */
defined('BASEPATH') or exit('No Such Script access');
//调用公共文件
include_once __DIR__.'/Agentpay.php';
class Jiayi extends Agentpay
{
    protected $o_id = 3;
    protected $o_name = '嘉亿';
    /* 代付接口返回字段 */
    protected $field = [
        'orderNum',// 商户订单号
        'amount',// 交易金额
        'merNo',//商户号
        'remitDate', //格式：yyyyMMddHHmmss
        'remitResult',//代付状态：00-成功 01-处理中 02-失败 03-签名错误 04-其他错误 06-初始（未扣款）50-网络异常 1000-已退款
        'sign'//签名
    ];
    /**
     * @var RSA
     */
    public $rsa;
    protected function parse()
    {
        //post 表单格式返回数据
        $data = $_POST;
        if (!empty($data)) {
            $this->call_data = $data;
        } else {
            $this->error = '第三方回调数据为空';
            return false;
        }
        $output = $this->call_data;
        if (strpos($output['data'],'%') !== false) {
            $output['data'] = urldecode($output['data']);
        }
        $this->load->library('RSA',['privateKey'=>$this->pay_set_config['out_private_key'],'publicKey'=>$this->pay_set_config['out_server_key']],'rsa');
        if ($this->rsa->error) {
            $this->error = $this->rsa->error;
            return false;
        }
        $this->call_format_data = $this->rsa->decryptByPrivateKey($output['data']);
        if (empty($this->call_format_data)) {
            $this->error = '代付回调接口返回解密失败,请检查公私钥';
            return false;
        }
        $this->call_format_data = json_decode(trim($this->call_format_data),true);
        if ($this->call_format_data['remitResult'] === '00' ) {
            $this->order_num = $this->call_format_data['orderNum'];
            $this->ret_data = 0;
            return true;
        } else {
            $this->order_num = $this->call_format_data['orderNum'];
            $this->error = $this->call_format_data['remitResult'];
            $this->ret_data = 0;
            return false;
        }

    }

}