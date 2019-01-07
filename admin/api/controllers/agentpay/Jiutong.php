<?php

/**
 * 久通代付接口
 * Created by PhpStorm.
 * User: Tailand
 * Date: 2018/11/6
 * Time: 16:13
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once __DIR__.'/Agentpay.php';
class Jiutong extends Agentpay
{
    protected  $m_name = 'jiutong';
    protected  $o_id   = '2';
    protected  $o_name = '久通';
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
        'merNo'  => 'out_id',
        'orderNum'  => 'order_num',
        'bank_Code' => 'bank_code'
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
        $this->data['data'] = $this->rsa->encryptByPublicKey($data_json);
        $this->data['merchNo'] = md5($this->pay_set_config[$this->field_map['merchNo']]);
        $this->data['version'] = 'V3.1.0.0';
        if (empty($this->data['data'])) {
            $this->error = '生成加密数据失败';
            $this->return_json(E_OP_FAIL,$this->error);
        }
    }

    /**构造签名参数
     * @return mixed
     */
    private  function getBasedata(){
        $data['amount']     = (string)yuan_to_fen($this->pay_set_config[$this->field_map['amount']]);
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
            'amount'    => $this->pay_set_config[$this->field_map['amount']],
            'merNo'     => $this ->pay_set_config['merNo'],
            'orderNum'  => $this->order_num,
            'remitDate' => date('Y-m-d',$this->pay_set_config['addtime']),
            'remitResult' => $this-> getRestaus(),//提交状态
        ];
        ksort($data);
        $data['sign'] = strtoupper(md5(json_encode($data,320) . $this->pay_set_config['out_key']));
        $this->init_data = $data;
        $data_json = json_encode($data);
        $this->data = [];
        $this->data['data'] = $this->rsa->encryptByPublicKey($data_json);
        $this->data['merchNo'] = md5($this->pay_set_config[$this->field_map['merchNo']]);
        $data['version']    = 'V3.1.0.0';
        if (empty($this->data['encryptData'])) {
            $this->error = '生成加密数据失败';
            $this->return_json(E_OP_FAIL,$this->error);
        }
    }

    /*
     * 子类继承需要重写的方法 解析代付接口返回的数据
     * @return 成功返回 true 失败返回 false 并写入失败信息到error
     */
    protected function apay_result()
    {
        $this->parse_response();
        //todo 解析代付支付返回结果
        // parse ret_format_data
        // var_dump($this->ret_format_data);
        /*******************************************
        e.g: {"tranTime":"20180920104611","respCode":"0000","bsSerial":"153741157208966408","orderNo":"5501809201045200404","tranFee":"100","resultFlag":"2","respDesc":"成功"}
        1.respCode是对发起代付请求后服务端返回给客户端的响应码，0000代表服务端已接收到代付请求且没有任何异常，respCode不等于0000时代表请求参数异常、服务端数据处理异常或其他未知异常
        2.resultFlag 为固定值2。
        3.对于代付结果服务端将统一通过异步通知（订单回调）的方式进行回调
        4.客户端在接收到服务端响应后也可以使用订单查询的方式查询订单状态
        5.除respCode、respDesc外，其他响应内容只有当respCode等于0000才会出现
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
        e.g {"respCode":"3003","resultFlag":"1","respDesc":"原订单不存在"}
        1.respCode是对发起订单请求后服务端返回给客户端的响应码，0000代表服务端已接收到查询请求且没有任何异常，respCode不等于0000时代表请求参数异常、服务端数据处理异常或其他未知异常
        2.原交易成功: respCode等于0000且resultFlag等于0
        3.原交易失败：respCode等于0000且resultFlag等于1
        4.原交易处理中：respCode等于0000且resultFlag等于2
        5.respCode不等于0000时订单状态是未知的
        6.除respCode、respDesc外，其他响应内容只有当respCode等于0000才会出现
        针对未收到应答或者处理状态不明确的订单，可通过该接口发起订单查询；单笔订单的查询频率建议5分钟一次，如果查询到结果成功，则不需要再查询；查询5次以上仍获取不到明确状态的交易，后续可以间隔更长的时间发起查询，最终结果以对账单为准；
        另外，博顺有商户交易查询系统(页面版)
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
            $this->error = '久通接口无返回';
            $this->return_json(E_OP_FAIL,$this->error);
        }
        $this->ret_format_data = $this->rsa->decryptByPrivateKey($this->ret_data);
        if (empty($this->ret_format_data)) {
            $this->error = '久通接口返回解密失败,请检查公私钥';
            $this->return_json(E_OP_FAIL,$this->error);
        }
    }

    /**
     * 获取提交的订单的状态
     */
    protected function getRestaus(){
        if($this->pay_set_config['status']==2){
            return '00';
        }else{
            return '99';
        }
    }

    public function decode()
    {
        $data = file_get_contents("php://input");
        var_dump($data);
        $this->load->library('RSA',['privateKey'=>$this->pay_set_config['out_private_key'],'publicKey'=>$this->pay_set_config['out_server_key']],'rsa');
        $ret = $this->rsa->decryptByPrivateKey($data);
        var_dump($ret);
    }
}