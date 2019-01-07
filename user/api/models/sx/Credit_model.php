<?php
//session_start();
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

include_once FCPATH.'api/core/SX_Model.php';

class Credit_model extends SX_Model
{
    public function __construct()
    {

    }
    /*视讯credit修改*/
    public function update_credit($sx_total_limit,$price,$platform = 'ag',$sn)
    {
        $this->select_db('shixun_w');
        /*写入额度转换记录表*/
        $data['billno']=$platform.'_'.date('Y-m-d H:i:s',time());
        $data['sn']=$sn;
        $data['platform']=$platform;
        $data['user']=$this->user['username'];
        $data['type']=$price>0?1:2;
        $data['credit']=$price;
        $data['after_credit']=$sx_total_limit+$price;
        $data['creater']=$this->user['username'];
        $data['remark']='用户调整';
        $data['time']=date('Y-m-d H:i:s',time());
        $rs=$this->write('credit_record',$data);
        return $rs;
    }
}