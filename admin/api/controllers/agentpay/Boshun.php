<?php
/**
 * 博顺代付
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once __DIR__.'/Agentpay.php';

class Boshun extends Agentpay {

    protected $o_id = 1;
    protected $o_name = '博顺';
    /* 代付接口需要的字段 */
    protected $field = [
        'payKey',// 支付key
        'cardNo',// 银行卡号
        'cardName',// 银行卡账户名
        'noticeUrl',// 回调通知的url
        'orderNo',// 商户订单号
        'tranAmt',// 交易金额 博顺按照分为单位 从表里面取出来 需要*100
        'tranTime'// => date('YmdHis')
    ];
    protected $commit_field = [
        'encryptData',//加密后的数据
        'signData',//加密签名
        'merId'//商户id
    ];
    /* 字段对应数据库字段映射 */
    protected $field_map = [
        'payKey' => 'out_key',
        'merId'  => 'out_id',
        'cardNo'  => 'bank_num',
        'cardName'  => 'bank_name',
        'noticeUrl'  => 'out_domain',
        'orderNo'  => 'order_num',
        'tranAmt'  => 'actual_price',
        'tranTime'  => 'addtime',
        'secret'  => 'out_secret',
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

        $data = [
                'payKey' => $this->pay_set_config[$this->field_map['payKey']],
                'cardNo' => $this->pay_set_config[$this->field_map['cardNo']],
                'cardName' => $this->pay_set_config[$this->field_map['cardName']],
                'noticeUrl' => $this->pay_set_config[$this->field_map['noticeUrl']],
                'orderNo' => $this->order_num,
                'tranTime' => date('YmdHis',$this->pay_set_config[$this->field_map['tranTime']]),
                'tranAmt' => $this->format_tranamt($this->pay_set_config[$this->field_map['tranAmt']]),
        ];
        ksort($data);
        $this->init_data = $data;
        $data_json = json_encode($data);
        $this->data = [];
        $this->data['encryptData'] = $this->rsa->encryptByPublicKey($data_json);
        $this->data['signData'] = $this->getSign($data,$this->pay_set_config[$this->field_map['secret']]);
        $this->data['merId'] = md5($this->pay_set_config[$this->field_map['merId']]);
        if (empty($this->data['encryptData'])) {
            $this->error = '生成加密数据失败';
            $this->return_json(E_OP_FAIL,$this->error);
        }
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
            'payKey' => $this->pay_set_config[$this->field_map['payKey']],
            'orderNo' => $this->order_num,
        ];
        ksort($data);
        $this->init_data = $data;
        $data_json = json_encode($data);
        $this->data = [];
        $this->data['encryptData'] = $this->rsa->encryptByPublicKey($data_json);
        $this->data['signData'] = $this->getSign($data,$this->pay_set_config[$this->field_map['secret']]);
        $this->data['merId'] = md5($this->pay_set_config[$this->field_map['merId']]);
        if (empty($this->data['encryptData'])) {
            $this->error = '生成加密数据失败';
            $this->return_json(E_OP_FAIL,$this->error);
        }
    }

    protected function getSign($data,$secret)
    {
        ksort($data);
        $str = '';
        foreach ($data as $k => $v) {
            if (empty($v)) {
                continue;
            }
            $_str = $k . '=' . $v .'&';
            $str .= $_str;
        }
        $str .= 'paySecret=';
        $str .= $secret;
        return strtoupper(md5($str));
    }

    /*
     * 格式化金额
     * 精确到分，不足12位在前面补0到12位，如4.55元应传000000000455
     */
    protected function format_tranamt($tranAmt)
    {
        $tranAmt = 100 * $tranAmt;
        $tranAmt = (int)$tranAmt;
        $tranAmt = (string)$tranAmt;
        $tranAmt = str_pad($tranAmt, 12, "0", STR_PAD_LEFT);
        return $tranAmt;
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
        if ($this->ret_format_data['respCode'] === '0000' && $this->ret_format_data['resultFlag'] === '2') {
            //交易处理中
            $this->result = true;
            $this->ret_message = $this->ret_format_data['orderNo'] . $this->o_name . $this->ret_format_data['respDesc'];
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
        if ($this->ret_format_data['respCode'] === '0000' && $this->ret_format_data['resultFlag'] === '0') {
            $this->ret_message = $this->ret_format_data['orderNo'] . $this->o_name . '交易成功';
            return true;
        }
        $this->ret_message = $this->order_num . $this->o_name . $this->ret_format_data['respDesc'];
        return false;

    }

    protected function parse_response()
    {
        if (empty($this->ret_data)) {
            $this->error = '博顺接口无返回';
            $this->return_json(E_OP_FAIL,$this->error);
        }
        $this->ret_format_data = $this->rsa->decryptByPrivateKey($this->ret_data);
        if (empty($this->ret_format_data)) {
            $this->error = '博顺接口返回解密失败,请检查公私钥';
            $this->return_json(E_OP_FAIL,$this->error);
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