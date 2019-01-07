<?php
/**
 * @模块   会员中心／奖金详情
 * @版本   Version 1.0.0
 * @日期   2017-03-30
 * shensiming
 */

defined('BASEPATH') OR exit('No direct script access allowed');

class Bonus_detailed extends MY_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->model('user/Detailed_set_model', 'core');
    }

    /******************公共方法*******************/
    /**
     * 获取列表数据
     */
    public function get_list()
    {
        $type = $this->G('type');
        $type = strtolower(trim($type));
        if(empty($type) || strlen($type) > 15) {
            $this->return_json(OK, array('rows'=>array(),'user_rebate'=>array()));
        }
        $data = $this->core->get_rate_list($type);
        if($data) {
            if ('s_' === substr($type,0,2)) {
                $type = substr($type,2);
            }
            $data['user_rebate'] = $this->type_to_gid($type);
            $this->return_json(OK,$data);
        } else {
            $this->return_json(OK, array('rows'=>array(),'user_rebate'=>array()));
        }
    }

    private function type_to_gid($type)
    {
        $types = array_unique(AGENT_GAMES);
        $types = array_flip($types);
        $gid = isset($types[$type])?$types[$type]:0;
        if ($gid  && isset($this->user['id'])) {
            $this->load->model('games_model');
            $user_rebate = $this->games_model->user_rebate($this->user['id'],$gid);
        } else {
            $user_rebate = ['uid'=>$this->user['id'], 'gid'=>$gid, 'rebate'=>0, 'user_rebate'=>0];
        }
        return $user_rebate;
    }
    
    /*******************************************/
}