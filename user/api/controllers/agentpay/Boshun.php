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
        'tranAmt',// 交易金额
        'tranTime'// => date('YmdHis')
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
    /**
     * @var RSA
     */
    public $rsa;


    /*
     * 子类继承需要重写的方法 解析代付接口返回的数据
     */
    protected function parse()
    {
        if (!empty(file_get_contents("php://input"))) {
            $this->call_data = file_get_contents("php://input");
        } else {
            $this->error = '第三方回调数据为空';
            return false;
        }
        parse_str($this->call_data, $output);
        if (strpos($output['transData'],'%') !== false) {
            $output['transData'] = urldecode($output['transData']);
        }
        $this->load->library('RSA',['privateKey'=>$this->pay_set_config['out_private_key'],'publicKey'=>$this->pay_set_config['out_server_key']],'rsa');
        if ($this->rsa->error) {
            $this->error = $this->rsa->error;
            return false;
        }

        $this->call_format_data = $this->rsa->decryptByPrivateKey($output['transData']);
        if (empty($this->call_format_data)) {
            $this->error = '代付回调接口返回解密失败,请检查公私钥';
            return false;
        }
        $this->call_format_data = json_decode(trim($this->call_format_data),true);
        if ($this->call_format_data['respCode'] === '0000' && $this->call_format_data['resultFlag'] === '0') {
            $this->order_num = $this->call_format_data['orderNo'];
            $this->ret_data = 0;
            return true;
        } else {
            $this->order_num = $this->call_format_data['orderNo'];
            $this->error = $this->call_format_data['respDesc'];
            $this->ret_data = 0;
            return false;
        }

    }
}