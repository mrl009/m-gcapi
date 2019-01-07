<?php
/**
 * @模块   会员中心／详细设定
 * @版本   Version 1.0.0
 * @日期   2017-03-30
 * shensiming
 */

defined('BASEPATH') or exit('No direct script access allowed');

class Detailed_set extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('user/Detailed_set_model',
                            'core');
    }

    /******************公共方法*******************/
    /**
     * 获取列表数据
     */
    public function get_list()
    {
        $type = $this->G('type');
        if (empty($type) || strlen($type) > 15) {
            $this->return_json(OK, array('rows'=>array()));
        }
        $data = $this->core->get_detailed_list($type);
        if ($data) {
            $this->return_json(OK, $data);
        } else {
            $this->return_json(OK, array('rows'=>array()));
        }
    }

    /*******************************************/
}
