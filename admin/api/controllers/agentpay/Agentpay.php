<?php
/**
 * 第三方代付 回调
 */
defined('BASEPATH') or exit('No direct script access allowed');

abstract class Agentpay extends MY_Controller
{
    protected $o_id = '';//代付通道id 子类继承时必须规定o_id
    protected $o_name = '';//代付通道简称 子类继承时必须规定o_name
    protected $order_num = '';//订单号
    protected $pay_set_config = [];//支付设定信息
    protected $data_type = 'JSON';//数据格式
    protected $data_tran_timeout = 0;//代付接口数据传输最大超时时间
    protected $init_data = null;//传输前未加密的数据
    protected $data = null;//传输的数据
    protected $ret_data = null;//接口返回的数据
    protected $ret_format_data = null;//接口返回解析过后的数据
    protected $db_data = null;//要入库的数据 新的代付数据
    protected $error = null;
    protected $result = false;//代付接口返回的值解析结果
    protected $ret_message = '';//代付接口返回的值解析结果
    protected $wlog_always = true;//开启后记录每一笔日志，否则只记录出错日志
    protected $method = '';//接口请求的方法
    const ORDER_STATUS = ['UNSURE'=>OUT_NO,'OK'=>OUT_DO,'PRE'=>OUT_PREPARE];//订单在out_manager表的状态
    /* APAY_STATUS = 2 时，才会将out_manager表ORDER_STATUS修改为 2  */
    const APAY_STATUS = ['UNSURE'=>APAY_UNSURE,'OK'=>APAY_OK,'FAIL'=>APAY_FAIL,'LOCK'=>APAY_LOCK];//代付表out_online表的状态

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Agentpay_model','ap');
        $this->get_pay_set();
    }

    public function __destruct()
    {
        parent::__destruct();
        if ($this->wlog_always) {
            $this->wlog();
        } else {
            if ($this->error) {
                $this->wlog();
            }
        }
    }

    protected function wlog($log = '')
    {
        /* log：[dopay][o_id|o_name][order_num][error][db_data][data][ret_data][info] */
        $logstr = '['. $this->method .']['.$this->o_id . '|' .$this->o_name.']['.$this->order_num.']';
        if ($this->error) {
            $logstr .= '[error:'.$this->error.']';
        }
        if ($this->db_data) {
            $logstr .= '[db_data:' . json_encode($this->db_data,JSON_UNESCAPED_UNICODE) .']';
        }
        if ($this->init_data) {
            if (!is_string($this->init_data)) {
                $logstr .= '[init_data:' . json_encode($this->init_data,JSON_UNESCAPED_UNICODE) . ']';
            } else {
                $logstr .= '[init_data:' . $this->init_data . ']';
            }
        }
        if ($this->data) {
            if (!is_string($this->data)) {
                $logstr .= '[data:' . json_encode($this->data,JSON_UNESCAPED_UNICODE) . ']';
            } else {
                $logstr .= '[data:' . $this->data . ']';
            }
        }
        if ($this->ret_data) {
            if (!is_string($this->ret_data)) {
                $logstr .= '[ret_data:' . json_encode($this->ret_data,JSON_UNESCAPED_UNICODE) . ']';
            } else {
                $logstr .= '[ret_data:' . $this->ret_data . ']';
            }
        }
        if ($this->ret_format_data) {
            if (!is_string($this->ret_format_data)) {
                $logstr .= '[ret_format_data:' . json_encode($this->ret_format_data,JSON_UNESCAPED_UNICODE) . ']';
            } else {
                $logstr .= '[ret_format_data:' . $this->ret_format_data . ']';
            }
        }
        if (!is_string($log)) {
            $log = json_encode($log,JSON_UNESCAPED_UNICODE);
        }
        $logstr .= $log;
        if ($this->error) {
            @wlog(APPPATH . "logs/agentpay/{$this->ap->sn}_apay_error_" . date('Ym') . '.log', $logstr);
        } else {
            if ($logstr) {
                @wlog(APPPATH . "logs/agentpay/{$this->ap->sn}_apay_record_" . date('Ym') . '.log', $logstr);
            }
        }
    }

    /*
     * 获取代付支付设定
     */
    protected function get_pay_set()
    {
        $pay_set_config = $this->ap->get_pay_set($this->o_id);
        $this->pay_set_config = $pay_set_config;
        return $this->pay_set_config;
    }

    /*
     * 提交数据到代付接口
     */
    protected function submit()
    {
        $ch = curl_init();
        $data = $this->data;
        if (empty($data)) {
            $this->return_json(E_OP_FAIL,'代付数据错误');
        }
        $pay_url = $this->pay_set_config['pay_url'];
        if (empty($pay_url)) {
            $this->return_json(E_OP_FAIL,'代付支付接口域名错误');
        }
        $query_url = $this->pay_set_config['query_url'];
        if (empty($query_url)) {
            $this->return_json(E_OP_FAIL,'代付查询接口域名错误');
        }
        if (strtoupper($this->data_type) === 'JSON') {
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type:application/json;charset=UTF-8']);
            $data_string = json_encode($data);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        } else {
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type:application/x-www-form-urlencoded;charset=UTF-8']);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }

        if ($this->method === 'doapay') {
            curl_setopt($ch, CURLOPT_URL, $pay_url);
        } else {
            curl_setopt($ch, CURLOPT_URL, $query_url);
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        curl_setopt($ch, CURLOPT_TIMEOUT, $this->data_tran_timeout);
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 5.1; rv:23.0) Gecko/20100101 Firefox/23.0");
        $output = curl_exec($ch);
        if (false === $output) {
            $this->error = curl_error($ch);
        }
        curl_close($ch);
        if ($this->error) {
            $this->return_json(E_OP_FAIL,$this->error);
        }
        $this->ret_data = $output;
        return $output;
    }

    protected function apay_new()
    {
        $rt = $this->ap->insert_apay($this->db_data);
        if (!$rt) {
            $this->error = $this->ap->error_msg;
        }
        $ret_msg = explode(':',$this->error)[0];
        if ($ret_msg) {
            $this->return_json(E_OP_FAIL,$ret_msg);
        }
        $this->return_json(OK,$this->ret_message);
    }

    protected function apay_ok()
    {
        $rt = $this->ap->update_apay($this->order_num,$this->o_id,APAY_OK);
        if (!$rt) {
            $this->error = $this->ap->error_msg;
        }
        $ret_msg = explode(':',$this->error)[0];
        if ($ret_msg) {
            $this->return_json(E_OP_FAIL,$ret_msg);
        }
        $this->return_json(OK,$this->ret_message);
    }

    protected function check_lock()
    {
        $lock_key = 'auto_out_lock:' . $this->method . ':' . $this->order_num;
        $lock_time = $this->method === 'doapay' ? 60 : 10;
        $b = $this->ap->redis_setnx($lock_key, $_SERVER['REQUEST_TIME']);
        if ($b) {
            // 加锁成功
            $this->ap->redis_expire($lock_key, $lock_time);
            return true;
        } else {
            // 旧锁还在
            $str = $this->order_num .'同笔订单';
            $str .= $this->method === 'doapay' ? '1分钟只能发起1次代付' : '10秒只能发起1次查询';
            $this->wlog_always = false;
            $this->return_json(E_OP_FAIL,$str);
        }
    }


    /*
     * 后台代付接口
     */
    public function doapay($order_num)
    {
        if (empty($order_num)) {
            $this->return_json(E_ARGS,'缺少订单号');
        }
        $this->order_num = $order_num;
        $this->method = __FUNCTION__;
        $this->check_lock();
        $order = $this->ap->query_order($this->order_num,self::ORDER_STATUS['PRE']);
        if (empty($order)) {
            $this->return_json(E_DATA_INVALID,'无效的订单号');
        }
        if ($order['o_id']>0 && $order['o_status'] != self::APAY_STATUS['FAIL']) {
            $this->return_json(E_DATA_INVALID,'该订单已代付');
        }
        if ($order['actual_price'] > $this->pay_set_config['max_amount'] || $order['actual_price'] < $this->pay_set_config['min_amount']) {
            $this->return_json(E_DATA_INVALID,'订单金额不在该代付限额内');
        }
        $this->pay_set_config = array_merge($this->pay_set_config,$order);
        // 构建要入库的新的代付订单信息
        $this->init_db_data();
        // 调用子类方法，创建要传送的数据
        $this->build_apay_data();
        // 提交数据到代付接口
        $this->submit();
        // 处理返回
        $this->result = $this->apay_result();
        if ($this->result) {
            $this->apay_new();
        } else {
            $this->return_json(E_OP_FAIL,$this->error);
        }
    }

    public function doquery($order_num)
    {
        if (empty($order_num)) {
            $this->return_json(E_ARGS,'缺少订单号');
        }
        $this->order_num = $order_num;
        $this->method = __FUNCTION__;
        $this->check_lock();
        $order = $this->ap->query_order($this->order_num);
        if (empty($order)) {
            $this->return_json(E_DATA_INVALID,'无效的订单号');
        }
        if ($order['o_id'] == 0) {
            $this->return_json(E_DATA_INVALID,'无效的代付订单');
        }
        if ($order['status'] == self::ORDER_STATUS['OK'] && $order['o_status'] == self::APAY_STATUS['OK']) {
            $this->return_json(OK,'该订单已支付成功');
        }
        $this->pay_set_config = array_merge($this->pay_set_config,$order);
        $this->build_query_data();
        $this->submit();
        $this->result = $this->query_result();
        if ($this->result) {
            $this->apay_ok();
        } else {
            $this->return_json(E_OP_FAIL,$this->ret_message);
        }
    }

    protected function init_db_data()
    {
        /*获取代付通道银行编码*/
        $this->config->load('bank_set');
        $pay_bank = $this->config->config['bank'];
        $model_name = $this->pay_set_config['model_name'];
        if(!empty($pay_bank[$model_name])){
            $bank_id = $this->pay_set_config['bank_id'];
            $this->pay_set_config['bank_type'] = $pay_bank[$model_name][$bank_id] ;
        }
        $db_data = [
            'o_id' => $this->o_id,
            'order_num' => $this->order_num,
            'uid' => $this->pay_set_config['uid'],
            'price' => $this->pay_set_config['actual_price'],
            'status' => self::APAY_STATUS['UNSURE'],
            'addtime' => $_SERVER['REQUEST_TIME'],
            'update_time' => 0,
            'admin_id' => $this->pay_set_config['admin_id'],
            'remark' => ''
        ];
        $this->db_data = $db_data;
        $this->pay_set_config = array_merge($this->pay_set_config,$db_data);
        return $db_data;
    }

    /*
     * 子类继承需要重写的方法 构建代付接口的数据
     * @return null 构建 代付接口数据 $this->data
     */
    abstract protected function build_apay_data();

    /*
     * 子类继承需要重写的方法 构建代付接口的数据
     * @return null 构建 查询接口数据 $this->data
     */
    abstract protected function build_query_data();

    /*
     * 子类继承需要重写的方法 解析代付接口返回的数据
     * @return bool 原订单交易成功返回true,否则返回false
     */
    abstract protected function apay_result();

    /*
     * 子类继承需要重写的方法 解析代付接口返回的数据
     * @return bool 原订单交易成功返回true,否则返回false
     */
    abstract protected function query_result();

}
