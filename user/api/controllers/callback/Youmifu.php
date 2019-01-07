<?php
/**
 * Created by PhpStorm.
 * User: mr.l
 * Date: 2018/4/10
 * Time: 上午10:37
 */

class Youmifu extends GC_Controller
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
        if ($data['orderStatus'] != 1) {
            $this->Online_model->online_erro('YMF', '支付未成功:' . json_encode($_REQUEST));
            exit('支付未成功');
        }
        if (empty($data['signMsg']) || empty($data['orderNo']) || empty($data['tradeAmt'])) {
            $this->Online_model->online_erro('YMF', '参数错误:' . json_encode($_REQUEST));
            exit('参数错误');
        }
        // 加锁
        $bool = $this->Online_model->fbs_lock('temp:new_order_num' . $data['orderNo']);
        if (!$bool) {
            exit('请稍后');
        }

        // 根据订单号获取配置信息
        $pay = $this->Online_model->order_detail($data['orderNo']);
        if (empty($pay)) {
            $this->Online_model->online_erro('YMF', '无效的订单号:' . json_encode($_REQUEST));
            exit('无效的订单号');
        }
        // 验证签名
        $flag = $this->verify($data, $pay['pay_key']);
        if (!$flag) {
            $this->Online_model->online_erro($pay['id'], '签名验证失败:' . json_encode($_REQUEST));
            exit('签名验证失败');
        }

        if ($pay['price'] != $data['tradeAmt']) {
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
            'apiName' => isset($_REQUEST['apiName']) ? $_REQUEST['apiName'] : '',
            'notifyTime' => isset($_REQUEST['notifyTime']) ? $_REQUEST['notifyTime'] : '',
            'tradeAmt' => isset($_REQUEST['tradeAmt']) ? $_REQUEST['tradeAmt'] : '',
            'merchNo' => isset($_REQUEST['merchNo']) ? $_REQUEST['merchNo'] : '',
            'merchParam' => isset($_REQUEST['merchParam']) ? $_REQUEST['merchParam'] : '',
            'orderNo' => isset($_REQUEST['orderNo']) ? $_REQUEST['orderNo'] : '',
            'tradeDate' => isset($_REQUEST['tradeDate']) ? $_REQUEST['tradeDate'] : '',
            'accNo' => isset($_REQUEST['accNo']) ? $_REQUEST['accNo'] : '',
            'accDate' => isset($_REQUEST['accDate']) ? $_REQUEST['accDate'] : '',
            'orderStatus' => isset($_REQUEST['orderStatus']) ? $_REQUEST['orderStatus'] : '',
            'signMsg' => isset($_REQUEST['signMsg']) ? $_REQUEST['signMsg'] : ''
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
            "apiName=%s&notifyTime=%s&tradeAmt=%s&merchNo=%s&merchParam=%s&orderNo=%s&tradeDate=%s&accNo=%s&accDate=%s&orderStatus=%s",
            $data['apiName'], $data['notifyTime'], $data['tradeAmt'], $data['merchNo'], $data['merchParam'], $data['orderNo'], $data['tradeDate'], $data['accNo'], $data['accDate'], $data['orderStatus']
        );
        $sign = md5($str . $key);
        return strcasecmp($sign, $data['signMsg']) == 0 ? true : false;
    }
}