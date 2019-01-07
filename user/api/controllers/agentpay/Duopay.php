<?php

/**多付代付回调
 * Created by PhpStorm.
 * User: Tailand
 * Date: 2018/12/21
 * Time: 11:54
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once __DIR__.'/Agentpay.php';
class Duopay extends Agentpay {

    protected $o_id = 4;
    protected $o_name = '多付';
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
        'merchantId'    => 'out_id',
        'merOrderNo'    => 'order_num',
        'orderAmount'   => 'actual_price',
        'accountName'   => 'bank_name',
        'accountCardNo' => 'bank_num',
    ];
    /*
     * 子类继承需要重写的方法 解析代付接口返回的数据
     */
    protected function parse()
    {
        //post 格式返回数据
        if(!empty(file_get_contents("php://input")))
        {
            $this->call_data = file_get_contents("php://input");
        }else{
            $this->error = '第三方回调数据为空';
            return false;
        }
        if (strpos($this->call_data,'%') !== false) {
            $this->call_data = urldecode($this->call_data);
        }
        parse_str($this->call_data, $output);
        $this->call_format_data = $output;
        $sign = $this->call_format_data['sign'];
        //验证签名
        //去掉不参与签名参数
        unset($this->call_format_data['sign']);
        ksort($this->call_format_data);
        $vsign = md5($this->arr_tovalue($this->call_format_data) .'&key='. $this->pay_set_config['out_key']);
        if (strtoupper($vsign) <> strtoupper($sign)) {
            $this->error = $this->o_name.'接口返回签名验证失败';
            $this->return_json(E_OP_FAIL,$this->error);
        }
        if ($this->call_format_data['transferStatus'] === 'SUCCESS') {
            $this->order_num = $this->call_format_data['merOrderNo'];
            $this->ret_data = 'SUCCESS';
            return true;
        } else {
            $this->order_num = $this->call_format_data['merOrderNo'];
            if($this->call_format_data['transferStatus']=='FAILURE'){
                $this->error = '交易失败'.$this->call_format_data['transferStatus'];
            }else if($this->call_format_data['transferStatus']=='FAILURE')
            {
                $this->error = '处理中'.$this->call_format_data['transferStatus'];
            }
            $this->ret_data = 0;
            return false;
        }
    }
    public function arr_tovalue($data,$lk='=',$lv='&'){
        $string = '';
        if (is_array($data))
        {
            foreach($data as $key => $val)
            {
                if (!is_array($val) && ('sign' <> $key)
                    && ("" <> $val) && (null <> $val)
                    && ("null" <> $val))
                {
                    $string .= "{$key}{$lk}{$val}{$lv}";
                }
            }
            $string = trim($string, $lv);
            return $string;
        }
    }
}