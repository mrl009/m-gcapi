<?php
/**
 * @模块   假用户
 * @版本   Version 1.0.0
 * @日期   2017-04-05
 * shensiming
 */

defined('BASEPATH') OR exit('No direct script access allowed');


class Fuser extends GC_Controller
{
    private $china = true;
	public function __construct()
    {
        parent::__construct();
        $this->load->model('MY_Model', 'core');
    }


    public function get_user_data()
    {
        $this->core->select_db('private');
        $where = ['title'=>'会员中心开关'];
        $d = $this->core->get_one('*', 'gc_member_center');
        $d = json_decode($d['img_json'], true);
        $data['rows2'][] = $d['0'];
        $data['rows2'][] = $d['1'];

        $data['rows'][0][] = $d['2'];
        $data['rows'][0][] = $d['3'];
        $data['rows'][0][] = $d['4'];
        $data['rows'][0][] = $d['5'];
        
        $data['rows'][1][] = $d['6'];
        $data['rows'][1][] = $d['7'];
        $data['rows'][1][] = $d['8'];
        $data['rows'][1][] = $d['9'];

        $data['rows'][2][] = $d['10'];
        $data['rows'][2][] = $d['11'];

        foreach ($data['rows'] as $k1 => $v1) {
            foreach ($v1 as $k2 => $v2) {
                if($v2['status'] == 0)
                    unset($v1[$k2]);
            }
            if(empty($v1))
                unset($data['rows'][$k1]);
            else
                $data['rows'][$k1] = array_values($v1);
        }
        $data['rows'] = array_values($data['rows']);

        foreach ($data['rows2'] as $key => $value) {
            if($value['status'] == 0)
                unset($data['rows2'][$key]);
        }
        $this->return_json(OK, $data);
    }
}
