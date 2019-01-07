<?php
/**
 * 第三方代付 回调
 */
defined('BASEPATH') or exit('No direct script access allowed');

abstract class Agentpay extends GC_Controller
{
    protected $o_id = '';//代付通道id 子类继承时必须规定o_id
    protected $o_name = '';//代付通道简称 子类继承时必须规定o_name
    protected $order_num = '';//订单号
    protected $pay_set_config = [];//支付设定信息
    protected $call_data = null;//服务端回调发送的数据
    protected $call_format_data = null;//服务端回调发送格式化后的数据
    protected $db_data = null;//要入库的数据 新的代付数据
    protected $error = null;
    protected $ret_data = '';
    protected $result = false;
    protected $wlog_always = true;//开启后记录每一笔日志，否则只记录出错日志
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
        $logstr = '[callback]['.$this->o_id . '|' .$this->o_name.']['.$this->order_num.']';
        if ($this->error) {
            $logstr .= '[error:'.$this->error.']';
        }
        if ($this->db_data) {
            $logstr .= '[db_data:' . json_encode($this->db_data,JSON_UNESCAPED_UNICODE) .']';
        }

        if ($this->call_data) {
            if (!is_string($this->call_data)) {
                $logstr .= '[call_data:' . json_encode($this->call_data,JSON_UNESCAPED_UNICODE) . ']';
            } else {
                $logstr .= '[call_data:' . $this->call_data . ']';
            }
        }
        if ($this->call_format_data) {
            if (!is_string($this->call_format_data)) {
                $logstr .= '[call_format_data:' . json_encode($this->call_format_data,JSON_UNESCAPED_UNICODE) . ']';
            } else {
                $logstr .= '[call_format_data:' . $this->call_format_data . ']';
            }
        }
        if (!is_string($log)) {
            $log = json_encode($log,JSON_UNESCAPED_UNICODE);
        }
        $logstr .= $log;
        if ($this->error) {
            @wlog(APPPATH . "logs/agentpay/{$this->ap->sn}_apay_error_" . date('Ym') . '.log', $logstr);
        } else {
            @wlog(APPPATH . "logs/agentpay/{$this->ap->sn}_apay_record_" . date('Ym') . '.log', $logstr);
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

    protected function apay_ok()
    {
        $rt = $this->ap->update_apay($this->order_num,$this->o_id,APAY_OK);
        if (!$rt) {
            $this->error = $this->ap->get_error();
        }
        return $rt;
    }

    protected function apay_fail()
    {
        $rt = $this->ap->update_apay($this->order_num,$this->o_id,APAY_FAIL,$this->error);
        if (!$rt) {
            $this->error = $this->ap->get_error();
        }
        return $rt;
    }

    public function callback()
    {
        $this->result = $this->parse();
        if ($this->result) {
            $flag = $this->apay_ok();
            $this->push(MQ_PAY_OK, "{$this->o_name} 代付 {$this->order_num} 成功");
            if ($flag) {
                echo $this->ret_data;
            }
        } else {
            if ($this->order_num) {
                $flag = $this->apay_fail();
                $this->push(MQ_PAY_OK, "{$this->o_name} 代付 {$this->order_num} 失败");
                if ($flag) {
                    echo $this->ret_data;
                }
            }
        }
    }

    /*
     * 子类继承需要重写的方法 解析回调接口的数据
     * @return bool true on success or false on fail
     */
    abstract protected function parse();

}
