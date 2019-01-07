<?php
/**
 * Created by PhpStorm.
 * User: Tailand
 * Date: 2018/11/7
 * Time: 15:49
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once __DIR__.'/Agentpay.php';
class Jiayi extends Agentpay
{
    protected  $m_name = 'jiayi';
    protected  $o_id   = '3';
    protected  $o_name = '嘉亿';
    protected $data_type = 'form_urlencoded';//数据格式
    /* 代付接口需要的字段 */
    protected $field = [
        'amount',//金额单位(分)
        'bankAccountName',//开户名
        'bankAccountNo',//银行卡号
        'bankCode',//银行代码
        'callBackUrl',//回调通知地址
        'charset',//编码格式
        'merNo',//商户号
        'orderNum',//订单号
        'sign',//签名
        'version'//版本固定值 V3.1.0.0
    ];
    protected $commit_field = [
        'data',//加密后的数据
        'merchNo',//商户号
        'version'//版本号
    ];
    /* 字段对应数据库字段映射 */
    protected $field_map = [
        'amount'  => 'actual_price',
        'bankAccountName'=> 'bank_name',
        'bankAccountNo' => 'bank_num',
        'callBackUrl' => 'out_domain',
        'merNo'      => 'out_id',
        'orderNum'  => 'order_num',
        'bank_Code' => 'bank_type'
    ];
    /*
     * 构建代付支付接口的数据
     */
    protected function build_apay_data()
    {
        $this->load->library('RSA',['privateKey'=>$this->pay_set_config['out_private_key'],'publicKey'=>$this->pay_set_config['out_server_key']],'rsa');
        if ($this->rsa->error) {
            $this->error = $this->rsa->error;
            $this->return_json(E_OP_FAIL,$this->error);
        }
        //构造签名参数
        $data = $this -> getBasedata();
        ksort($data);
        $data['sign'] = strtoupper(md5(json_encode($data,320) . $this->pay_set_config['out_key']));
        $this->init_data = $data;
        $data_json = json_encode($data);
        $this->data = [];
        $this->data['data'] = $this->rsa->encryptByPublicKey($data_json);
        $this->data['merchNo'] = $this->pay_set_config[$this->field_map['merNo']];
        $this->data['version'] = 'V3.1.0.0';
        if (empty($this->data['data'])) {
            $this->error = '生成加密数据失败';
            $this->return_json(E_OP_FAIL,$this->error);
        }
        //传递参数为STRING格式
        $this->data = http_build_query($this->data);
    }

    /**构造签名参数 注意：提交参数统一使用字符串类型
     * @return mixed
     */
    private  function getBasedata(){
        $data['amount']     = (string)dyuan_to_fen($this->pay_set_config[$this->field_map['amount']]);
        $data['bankAccountName'] = (string)$this->pay_set_config[$this->field_map['bankAccountName']];
        $data['bankAccountNo'] = (string)$this->pay_set_config[$this->field_map['bankAccountNo']];
        $data['bankCode']   = (string)$this->pay_set_config[$this->field_map['bank_Code']];//银行代码
        $data['callBackUrl']= (string)$this->pay_set_config[$this->field_map['callBackUrl']];
        $data['charset']    = 'UTF-8';
        $data['merNo']      = (string)$this->pay_set_config[$this->field_map['merNo']];
        $data['orderNum']   = (string)$this->pay_set_config[$this->field_map['orderNum']];
        $data['version']    = 'V3.1.0.0';
        return $data;
    }

    /*
     * 构建代付查询接口的数据
     */
    protected function build_query_data()
    {
        $this->load->library('RSA',['privateKey'=>$this->pay_set_config['out_private_key'],'publicKey'=>$this->pay_set_config['out_server_key']],'rsa');
        if ($this->rsa->error) {
            $this->error = $this->rsa->error;
            $this->return_json(E_OP_FAIL,$this->error);
        }
        $data = [
            'amount'    => (string)dyuan_to_fen($this->pay_set_config[$this->field_map['amount']]),
            'merNo'     => (string)$this->pay_set_config[$this->field_map['merNo']],
            'orderNum'  => (string)$this->order_num,
            'remitDate' => (string)date('Y-m-d',$this->pay_set_config['addtime']),
        ];
        ksort($data);
        $data['sign'] = strtoupper(md5(json_encode($data,320) . $this->pay_set_config['out_key']));
        $this->init_data = $data;
        $data_json = json_encode($data);
        $this->data = [];
        $this->data['data'] = $this->rsa->encryptByPublicKey($data_json);
        $this->data['merchNo'] = $this->pay_set_config[$this->field_map['merNo']];
        $this->data['version']    = 'V3.1.0.0';
        if (empty($this->data['data'])) {
            $this->error = '生成加密数据失败';
            $this->return_json(E_OP_FAIL,$this->error);
        }
        //传递参数为STRING格式/*
        $this->data = http_build_query($this->data);
    }

    /*
     * 子类继承需要重写的方法 解析代付接口返回的数据
     * @return 成功返回 true 失败返回 false 并写入失败信息到error
     */
    protected function apay_result()
    {
        $this->parse_response();
        //todo 解析代付支付返回结果
        /*******************************************
        {"amount":"100","merNo":"60000680","msg":"提交成功","orderNum":"5501811142050133321","sign":"EAC59967B4EEB6C2876289DB8A9C0E59","stateCode":"00"}
         *******************************************/
        $this->ret_format_data = json_decode(trim($this->ret_format_data),true);

        if ($this->ret_format_data['stateCode'] === '00') {
            //交易处理中
            $this->result = true;
            $this->ret_message = $this->ret_format_data['orderNum'] . $this->o_name . $this->ret_format_data['msg'];
            return true;
        } else {
            //交易失败
            $this->error = $this->order_num . $this->o_name . $this->ret_format_data['msg'];
            return false;
        }
    }

    /*
     * 子类继承需要重写的方法 解析查询接口的数据
     * @return array 返回查询结果信息
     */
    protected function query_result()
    {
        $this->parse_response();
        //todo 解析代付支付查询结果
        /*******************************************
        {"amount":"100","merNo":"60000680","msg":"查询成功","orderNum":"5501811142050133321","remitResult":"00","sign":"7D8BBD9F5784B6B58B0327E72236FAF0","stateCode":"00"}
         *******************************************/
        $this->ret_format_data = json_decode(trim($this->ret_format_data),true);
        if ($this->ret_format_data['stateCode'] === '00') {
            $this->ret_message = $this->ret_format_data['orderNum'] . $this->o_name . '交易成功';
            return true;
        }
        $this->ret_message = $this->order_num . $this->o_name . $this->ret_format_data['msg'];
        return false;

    }

    protected function parse_response()
    {
        if (empty($this->ret_data)) {
            $this->error = '嘉亿接口无返回';
            $this->return_json(E_OP_FAIL,$this->error);
        }
        $this->ret_format_data = $this->ret_data;
        $this->ret_data = json_decode($this->ret_data,320);
        $sign = $this->ret_data['sign'];
        //验证签名
        //去掉不参与签名参数
        unset($this->ret_data['sign']);
        ksort($this->ret_data);
        $vsign = md5(json_encode($this->ret_data,320).$this->pay_set_config['out_key']);
        if (strtoupper($vsign) <> strtoupper($sign)) {
            $this->error = '嘉亿接口返回签名验证失败';
            $this->return_json(E_OP_FAIL,$this->error);
        }
    }
}