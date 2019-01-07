<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class SX_Controller extends MY_Controller
{

    protected $sxuser = [];

    public function __construct()
    {
        parent::__construct();
        $this->get_sx_user();
    }

    /**
     * 获取所有登录信息及请求参数
     */
    private function get_sx_user()
    {
        //var_dump($this->P('uid'));exit();
        $this->load->model('MY_Model','core');
        $arr=$this->M->get_one('*','user',array('id'=>$this->P('uid')));
        $this->user=$arr;
        $this->sxuser = json_decode($this->P('data'), true) ? json_decode($this->P('data'), true) : [];
        $this->sxuser = array_merge($this->user, $this->sxuser, $_GET);
        $this->sxuser['sn'] = $this->get_sn();
        $this->sxuser['merge_username'] = $this->sxuser['sn'] . $this->user['username'];
    }

    /*视讯credit修改*/
    public function update_credit($sx_total_limit,$price,$platform = 'ag')
    {
        $result=$this->M->update_sx_set('credit',$sx_total_limit+$price,0);
        /*写入额度转换记录表*/
        $data['billno']=$platform.'_'.date('Y-m-d H:i:s',time());
        $data['sn']=$this->_sn;
        $data['platform']=$platform;
        $data['user']=$this->user['username'];
        $data['type']=$price>0?1:2;
        $data['credit']=abs($price);
        $data['after_credit']=$sx_total_limit+$price;
        $data['creater']=$this->user['username'];
        $data['remark']='用户调整';
        $rs=$this->M->write('credit_record',$data);
    }
}
