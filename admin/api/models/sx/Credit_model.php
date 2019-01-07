<?php
/**
 * @模块   视讯
 * @版本   Version 1.0.0
 * @日期   2017-09-11
 * super
 */
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Credit_model extends GC_Model
{
    public function __construct()
    {
        parent::__construct();
        //$this->select_db('shixun');
    }
    public function add_credit_record($credit,$admin_name)
    {
        $this->select_db('shixun_w');
        $data['billno']='admin_'.date('Y-m-d H:i:s',time());
        $data['sn']=$this->_sn;
        $data['platform']='admin';
        $data['user']=$admin_name;
        $data['type']=3;
        $data['credit']=$credit;
        $data['after_credit']=$credit;
        $data['creater']=$admin_name;
        $data['remark']='超级后台修改';
        $data['time']=date('Y-m-d H:i:s',time());
        $rs=$this->write('credit_record',$data);
        return $rs;
    }
    public function update_credit($sx_total_limit,$price,$platform = 'ag',$sn)
    {
        //var_dump($this->admin);exit();
        $this->select_db('shixun_w');
        /*写入额度转换记录表*/
        $data['billno']=$platform.'_'.date('Y-m-d H:i:s',time());
        $data['sn']=$sn;
        $data['platform']=$platform;
        $data['user']=$this->user['username'];
        $data['type']=$price>0?1:2;
        $data['credit']=$price;
        $data['after_credit']=$sx_total_limit+$price;
        $data['creater']=$this->admin['username'];
        $data['remark']='后台管理员修改';
        $data['time']=date('Y-m-d H:i:s',time());
        $rs=$this->write('credit_record',$data);
        return $rs;
    }
}
