<?php

defined('BASEPATH') OR exit('No direct script access allowed');
include_once FCPATH . 'api/core/SX_Controller.php';

class User extends SX_Controller
{
    protected $ag_api;
    protected $dg_api;
    protected $ky_api;
    public function __construct()
    {
        parent::__construct();
        $this->load->library('BaseApi');
        $this->load->helper('common_helper');
    }

    /**
     * 额度转换页面列表
     */
    public function transfer_list()
    {
        $this->load->model('sx/dg/user_model', 'dg_user');
        $rs = [
            'sx' => [],
            'user' => 0,
            'platform' => 0
        ];
        $username = $this->sxuser['merge_username'];
        //系统额度
        $userBalance = $this->M->get_one('balance', 'user', array('username' => substr($username, 3)));
        $rs['user'] = isset($userBalance['balance']) ? $userBalance['balance'] : 0;
        //获取站点设置
        $set = $this->M->get_gcset();
        $set = explode(',', $set['cp']);
        //ag额度
        if (in_array(1001, $set)) {
            $agUser = $this->dg_user->user_info($username, 'balance,actype,g_password', 'ag');
            if (!empty($agUser)) {
                $this->ag_api = BaseApi::getinstance('ag', 'user', $this->sxuser['sn']);
                $agBalance = $this->ag_api->get_balance($username, $agUser['actype'], $agUser['g_password']);
            } else {
                $agBalance = 0;
            }
            $rs['sx']['ag'] = isset($agBalance['info']) ? $agBalance['info'] : 0;
        }
        //dg额度
        if (in_array(1002, $set)) {
            $dgUser = $this->dg_user->user_info($username, 'balance', 'dg');
            if (!empty($dgUser)) {
                $this->dg_api = BaseApi::getinstance('dg', 'dgUser', $this->sxuser['sn']);
                $dgBalance = $this->dg_api->updateBalance($username, 'dg');
            } else {
                $dgBalance = 0;
            }
            $rs['sx']['dg'] = isset($dgBalance['member']['balance']) ? $dgBalance['member']['balance'] : 0;
        }
        //lebo额度
        if (in_array(1003, $set)) {
            $lbUser = $this->dg_user->user_info($username, 'balance', 'lebo');
            $rs['sx']['lebo'] = isset($lbUser['balance']) ? $lbUser['balance'] : 0;
        }
        //pt额度
        if (in_array(1004, $set)) {
            $ptUser = $this->dg_user->user_info($username, 'balance', 'pt');
            $rs['sx']['pt'] = isset($ptUser['balance']) ? $ptUser['balance'] : 0;
        }
        if (in_array(1006, $set)){
            //include_once(FCPATH.'api/libraries/ky/UserApi.php') ;
            $this->load->library('ky/KyuserApi','','KyUser');
            //$this->load->library('ky/UserApi','','userapi');
            $data = [];
            $data['s'] = 1;
            $data['account'] = $username;
            $res = $this->KyUser->get_api_data($data,1,$this->get_sn());
            if(isset($res['d']['code'])&&$res['d']['code']==0){
                /*同步ky_user信息*/
                //$data = $this->update_balance($username,$rs['d']['money'],'ky');
                $this->dg_user->update_balance($username, $res['d']['money'], 'ky');
                $rs['sx']['ky'] =$res['d']['money']?$res['d']['money']:0;
            }else{
                $kyUser = $this->dg_user->user_info($username, 'balance', 'ky');
                $rs['sx']['ky'] = isset($kyUser['balance']) ? $kyUser['balance'] : 0;
            }
            //$kyUser = $this->dg_user->user_info($username, 'balance', 'ky');
            //$rs['sx']['ky'] = isset($kyUser['balance']) ? $kyUser['balance'] : 0;
        }
        //mg额度
        if (in_array(1005, $set)) {
            $rs['sx']['mg'] = 0;
        }
        if (!empty($rs['sx'])) {
            foreach ($rs['sx'] as $k => $v) {
                $rs['platform'] = isset($rs['platform']) ? $rs['platform'] + $v : $v;
            }
            $rs['platform']=sprintf("%.2f", $rs['platform']);
        }
        $this->return_json(OK, $rs);
    }
}
