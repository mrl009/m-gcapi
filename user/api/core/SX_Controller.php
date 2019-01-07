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
        $this->sxuser = json_decode($this->P('data'), true) ? json_decode($this->P('data'), true) : [];
        $this->sxuser = array_merge($this->user, $this->sxuser, $_GET);
        $this->sxuser['sn'] = $this->get_sn();
        $this->sxuser['merge_username'] = $this->sxuser['sn'] . $this->user['username'];
    }

    /**
     * 获取sn
     * @return string
     */
    public function get_sn()
    {
        $this->M->redisP_select(REDIS_PUBLIC);
        $snInfo = $this->M->redisP_hget('dsn', $this->_sn);
        if (empty($snInfo)) {
            $this->return_json(E_ARGS, '请求头出错');
        }
        $snInfo = json_decode($snInfo, true);
        return isset($snInfo['sn']) ? $snInfo['sn'] : '';
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
