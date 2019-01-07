<?php
/**
 *
 * 用于提交表单和读取验证码
 * Created by PhpStorm.
 * User: shenshilin
 * Date: 2017/5/8
 * Time: 18:41
 */

defined('BASEPATH') or exit('No direct script access allowed');

class Pay_test extends GC_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('pay/Online_model');
        if ($this->user && $this->user['status'] == 4) {
            $this->return_json(E_DENY,'没有权限');
        }
    }

    public function qrcode($order)
    {
        Header("Content-type: image/png");
        $base  = $this->Online_model->redis_get("temp:qrcode:$order");
        echo base64_decode($base);
    }

    /**
     * 执行表单提交跳转
     */
    public function pay_sest($order)
    {
        $data = $this->Online_model->set_get_detailo($order);

        if (empty($data)) {
            $this->return_json(E_ARGS, '没有该订单号');
        };
        $data = json_decode($data, true);


        $this->load->view('pay.html', $data);
    }

    /**
     * 执行表单提交跳转 不需要生成表单
     */
    public function pay_uses($order)
    {
        $data = $this->Online_model->set_get_detailo($order);
        if (empty($data)) 
        {
            $this->return_json(E_ARGS, '没有该订单号');
        }
        $data = json_decode($data, true);
        $this->load->view('pay_form.html', $data);
    }

}
