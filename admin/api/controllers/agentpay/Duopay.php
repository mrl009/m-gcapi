<?php

/**多付代付接口
 * Created by PhpStorm.
 * User: Tailand
 * Date: 2018/12/19
 * Time: 14:20
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once __DIR__.'/Agentpay.php';
class Duopay extends Agentpay
{
    protected  $m_name = 'duopay';
    protected  $o_id   = '4';
    protected  $o_name = '多付';
    protected $data_type = '';//数据格式
    /* 代付接口需要的字段 */
    protected $field = [
        'version',//版本号：V1.0.5
        'serviceName',//服务编码 openTransferPay
        'reqTime',//请求时间
        'merchantId',//商户号
        'busType',//PRV- 对私，PUB - 对公
        'merOrderNo',//订单号
        'orderAmount',//金额单位元2位小数
        'bankCode',//银行代码
        'clientReqIP',//ip
        'notifyUrl',//异步通知
        'signType',//签名方式 MD5
        'accountName',//收款人姓名
        'accountCardNo',//银行卡号
        'sign',//签名
    ];
    /* 字段对应数据库字段映射 */
    protected $field_map = [
        'merchantId'      => 'out_id',
        'merOrderNo'  => 'order_num',
        'orderAmount'  => 'actual_price',
        'bankCode'     => 'bank_type',
        'notifyUrl' => 'out_domain',
        'accountName'=> 'bank_name',
        'accountCardNo' => 'bank_num',
    ];
    /*
     * 构建代付支付接口的数据
     */
    protected function build_apay_data()
    {
        //构造签名参数
        $data = $this -> getBasedata();
        ksort($data);
        $data['sign'] = strtoupper(md5(arr_string($data) .'&key='. $this->pay_set_config['out_key']));
        $this->init_data = $data;
        $this->data = $data;
        if (empty($this->data)) {
            $this->error = '提交的数据不能为空';
            $this->return_json(E_OP_FAIL,$this->error);
        }
        //传递参数为STRING格式
        $this->data = http_build_query($this->data);
    }

    /**构造签名参数 注意：提交参数统一使用字符串类型
     * @return mixed
     */
    private  function getBasedata(){
        $data['version']    = 'V1.0.5';
        $data['serviceName']= 'openTransferPay';
        $data['reqTime']    = (string)date('Y-m-d H:i:s',time());
        $data['busType']    = 'PRV';
        $data['merchantId'] = (string)$this->pay_set_config[$this->field_map['merchantId']];
        $data['merOrderNo'] = (string)$this->pay_set_config[$this->field_map['merOrderNo']];
        $data['orderAmount']= (string)sprintf('%.2f',$this->pay_set_config[$this->field_map['orderAmount']]);
        $data['bankCode']   = (string)$this->pay_set_config[$this->field_map['bankCode']];//银行代码
        $data['clientReqIP']= (string)get_ip();
        $data['notifyUrl']  = (string)$this->pay_set_config[$this->field_map['notifyUrl']];
        $data['signType']   = (string)'MD5';
        $data['accountName']= (string)$this->pay_set_config[$this->field_map['accountName']];
        $data['accountCardNo'] = (string)$this->pay_set_config[$this->field_map['accountCardNo']];
        return $data;
    }

    /*
     * 构建代付查询接口的数据
     */
    protected function build_query_data()
    {
        //请求参数
        $data['version']    = 'V1.0.5';
        $data['serviceName']= 'openTransferQuery';
        $data['reqTime']    = (string)date('Y-m-d H:i:s',time());
        $data['merchantId'] = (string)$this->pay_set_config[$this->field_map['merchantId']];
        $data['merOrderNo'] = (string)$this->pay_set_config[$this->field_map['merOrderNo']];
        $data['signType']   = (string)'MD5';
        ksort($data);
        $data['sign'] = strtoupper(md5(arr_string($data).'&key=' . $this->pay_set_config['out_key']));
        $this->init_data = $data;
        $this->data = $data;
        if (empty($this->data)) {
            $this->error = '提交的数据不能为空';
            $this->return_json(E_OP_FAIL,$this->error);
        }
        //传递参数为STRING格式
        $this->data = http_build_query($this->data);
    }

    /*
     * 子类继承需要重写的方法 解析代付接口返回的数据
     * @return 成功返回 true 失败返回 false 并写入失败信息到error
     */
    protected function apay_result()
    {
        $this->parse_response();
       if ($this->ret_format_data['respBody']['transferStatus'] === 'SUCCESS'||$this->ret_format_data['respBody']['transferStatus'] === 'PENDING') {
            //交易处理中
            $this->result = true;
            $this->ret_message = $this->ret_format_data['merOrderNo']  . '已提交多付成功';
            return true;
        } else {
            //交易失败
            $this->error = $this->order_num . $this->o_name . $this->ret_format_data['respDesc'];
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
        {"respBody":{"orderAmount":"2.00","orderTime":"2018-12-20 21:49:31","transferStatus":"SUCCESS","transferTime":"2018-12-20 21:49:39","orderSn":"T1812202149306578492","merchantId":"MD74902054","merOrderNo":"5501812201923369544","sign":"56BC31468DA3D82A26402D8473A4BD82","signType":"MD5"},"respCode":"SUCCESS","respDesc":"请求响应成功"}
         *******************************************/
        if ($this->ret_format_data['respBody']['transferStatus'] === 'SUCCESS'||$this->ret_format_data['respBody']['transferStatus'] === 'PENDING') {
            $this->ret_message = $this->ret_format_data['merOrderNo'] . $this->o_name . '交易成功';
            return true;
        }else {
            $this->ret_message = $this->order_num . $this->o_name . $this->ret_format_data['respDesc'];
            return false;
        }
    }

    protected function parse_response()
    {
        if (empty($this->ret_data)) {
            $this->error = $this->o_name.'接口无返回';
            $this->return_json(E_OP_FAIL,$this->error);
        }
        $this->ret_format_data = $this->ret_data;
        $this->ret_format_data = json_decode($this->ret_format_data,320);
        $sign = $this->ret_format_data['respBody']['sign'];
        if( $this->ret_format_data['respCode'] <> 'SUCCESS'){
            $this->error = $this->o_name . $this->ret_format_data['respDesc'];
            $this->return_json(E_OP_FAIL,$this->error);
        }
        //验证签名
        //去掉不参与签名参数
        unset($this->ret_format_data['respBody']['sign']);
        ksort($this->ret_format_data['respBody']);
        $vsign = md5(arr_string($this->ret_format_data['respBody']) .'&key='. $this->pay_set_config['out_key']);
        if (strtoupper($vsign) <> strtoupper($sign)) {
            $this->error = $this->o_name.'接口返回签名验证失败';
            $this->return_json(E_OP_FAIL,$this->error);
        }
    }
}