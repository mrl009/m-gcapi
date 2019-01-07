<?php

/**
 * Created by PhpStorm.
 * User: mrl
 * Date: 2018/3/12
 * Time: 下午1:52
 */

class Baifutong extends GC_Controller
{
    /**
     * 错误响应
     * @var string
     */
    public $error = "ERROR";

    /**
     * 成功响应
     * @var string
     */
    public $success = "000000";

    public function __construct()
    {
        parent::__construct();
        $this->load->model('pay/Online_model');
        $this->load->helper('common_helper');
    }

    /**
     * 异步回调接口
     */
    public function callbackurl()
    {
        $data = json_decode($_POST['paramData'], true);
        if (empty($data['sign']) || empty($data['resultCode']) || empty($data['orderNum']) || empty($data['payAmount'])) {
            $this->Online_model->online_erro('BF', '参数错误:' . json_encode($data));
            exit('参数错误');
        }
        // 加锁
        $bool = $this->Online_model->fbs_lock('temp:new_order_num' . $data['orderNum']);
        if (!$bool) {
            exit('请稍后');
        }
        // 根据订单号获取配置信息
        $pay = $this->Online_model->order_detail($data['orderNum']);
        if (empty($pay)) {
            $this->Online_model->online_erro('BF', '无效的订单号:' . json_encode($data));
            exit('无效的订单号');
        }
        // 验证返回状态
        if ($data['resultCode'] != '00') {
            $this->Online_model->online_erro($pay['id'], '交易不成功:' . json_encode($data));
            exit($this->error);
        }
        // 验证签名
        $flag = $this->sign($data, $pay['pay_key']);
        if (!$flag) {
            $this->Online_model->online_erro($pay['id'], '签名验证失败:' . json_encode($data));
            exit($this->error);
        }

        //已经确认
        if ($pay['status'] == 2) {
            exit($this->success);
        }
        $bool = $this->Online_model->update_order($pay);
        if ($bool) {
            exit($this->success);
        }
        $this->Online_model->online_erro($pay['id'], '写入现金记录失败:' . json_encode($_POST));
        exit('加钱失败');
    }

    /**
     * 验证签名
     * @param array $data 回调参数数组
     * @param string $signKey 秘钥
     * @return boolean $flag
     */
    private function sign($data, $signKey)
    {
        $r_sign = $data['sign']; #保留签名数据
        $arr = array();
        foreach ($data as $key => $v) {
            if ($key !== 'sign') { #删除签名
                $arr[$key] = $v;
            }
        }
        ksort($arr);
        $sign = strtoupper(md5($this->enData($arr) . $signKey)); #生成签名
        return $sign == $r_sign ? true : false;
    }

    private function enData($data)
    {
        if (is_string($data)) {
            $text = $data;
            $text = str_replace('\\', '\\\\', $text);
            $text = str_replace(
                array("\r", "\n", "\t", "\""),
                array('\r', '\n', '\t', '\\"'),
                $text);
            $text = str_replace("\\/", "/", $text);
            return '"' . $text . '"';
        } else if (is_array($data) || is_object($data)) {
            $arr = array();
            $is_obj = is_object($data) || (array_keys($data) !== range(0, count($data) - 1));
            foreach ($data as $k => $v) {
                if ($is_obj) {
                    $arr[] = $this->enData($k) . ':' . $this->enData($v);
                } else {
                    $arr[] = $this->enData($v);
                }
            }
            if ($is_obj) {
                $arr = str_replace("\\/", "/", $arr);
                return '{' . join(',', $arr) . '}';
            } else {
                $arr = str_replace("\\/", "/", $arr);
                return '[' . join(',', $arr) . ']';
            }
        } else {
            $data = str_replace("\\/", "/", $data);
            return $data . '';
        }
    }
}