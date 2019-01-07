<?php
/**
 * Created by PhpStorm.
 * User: mr.xiaolin
 * Date: 2018/4/12
 * Time: 上午11:10
 */

class Shangfuyun extends GC_Controller
{
    /**
     * 成功响应
     * @var string
     */
    public $success = "SUCCESS";

    public function __construct()
    {
        parent::__construct();
        $this->load->model('pay/Online_model');
    }

    /**
     * 异步回调接口
     */
    public function callbackurl()
    {
        $data = $this->getData();
        if ($data['status'] != 1) {
            $this->Online_model->online_erro('SFY', '支付未成功:' . json_encode($_REQUEST));
            exit('支付未成功');
        }
        if (empty($data['sign']) || empty($data['tradeNo']) || empty($data['amount'])) {
            $this->Online_model->online_erro('SFY', '参数错误:' . json_encode($_REQUEST));
            exit('参数错误');
        }
        // 加锁
        $bool = $this->Online_model->fbs_lock('temp:new_order_num' . $data['tradeNo']);
        if (!$bool) {
            exit('请稍后');
        }

        // 根据订单号获取配置信息
        $pay = $this->Online_model->order_detail($data['tradeNo']);
        if (empty($pay)) {
            $this->Online_model->online_erro('SFY', '无效的订单号:' . json_encode($_REQUEST));
            exit('无效的订单号');
        }
        // 验证签名
        $flag = $this->verify($data, $pay['pay_key']);
        if (!$flag) {
            $this->Online_model->online_erro($pay['id'], '签名验证失败:' . json_encode($_REQUEST));
            exit('签名验证失败');
        }

        if ($pay['price'] != $data['amount']) {
            $this->Online_model->online_erro($pay['id'], '订单金额验证失败:' . json_encode($_REQUEST));
            exit('订单金额验证失败');
        }
        //已经确认
        if ($pay['status'] == 2) {
            exit($this->success);
        }
        $bool = $this->Online_model->update_order($pay);
        if ($bool) {
            exit($this->success);
        }
        $this->Online_model->online_erro($pay['id'], '写入现金记录失败:' . json_encode($_REQUEST));
        exit('加钱失败');
    }


    /**
     * 获取数据
     * @return array
     */
    private function getData()
    {
        $data = [
            'service' => isset($_REQUEST['service']) ? $_REQUEST['service'] : '',
            'merId' => isset($_REQUEST['merId']) ? $_REQUEST['merId'] : '',
            'tradeNo' => isset($_REQUEST['tradeNo']) ? $_REQUEST['tradeNo'] : '',
            'tradeDate' => isset($_REQUEST['tradeDate']) ? $_REQUEST['tradeDate'] : '',
            'opeNo' => isset($_REQUEST['opeNo']) ? $_REQUEST['opeNo'] : '',
            'opeDate' => isset($_REQUEST['opeDate']) ? $_REQUEST['opeDate'] : '',
            'amount' => isset($_REQUEST['amount']) ? $_REQUEST['amount'] : '',
            'status' => isset($_REQUEST['status']) ? $_REQUEST['status'] : '',
            'extra' => isset($_REQUEST['extra']) ? $_REQUEST['extra'] : '',
            'payTime' => isset($_REQUEST['payTime']) ? $_REQUEST['payTime'] : '',
            'sign' => isset($_REQUEST['sign']) ? $_REQUEST['sign'] : '',
            'notifyType' => isset($_REQUEST['notifyType']) ? $_REQUEST['notifyType'] : '',
        ];
        return $data;
    }

    /**
     * 验证签名
     * @param array $data 回调参数数组
     * @param string $key
     * @return boolean $flag
     */
    private function verify($data, $key)
    {
        $str = sprintf(
            "service=%s&merId=%s&tradeNo=%s&tradeDate=%s&opeNo=%s&opeDate=%s&amount=%s&status=%s&extra=%s&payTime=%s",
            $data['service'],
            $data['merId'],
            $data['tradeNo'],
            $data['tradeDate'],
            $data['opeNo'],
            $data['opeDate'],
            $data['amount'],
            $data['status'],
            $data['extra'],
            $data['payTime']
        );
        $sign = md5($str . $key);
        return strcasecmp($sign, $data['sign']) == 0 ? true : false;
    }
}