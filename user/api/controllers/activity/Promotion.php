<?php

/**
 * Created by PhpStorm.
 * User: dragon
 * Date: 2017/4/21
 * Time: 15:18
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class Promotion extends GC_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('MY_Model', 'core');
    }

    public function get_activity_list()
    {
        $page = (int)$this->G('page') OR $page = 1;
        $rows = (int)$this->G('rows') OR $rows = 20;

        $show_way = $this->from_way;

        if ($page < 0) {
            $page = 1;
        }

        if ($rows < 0) {
            $rows = 20;
        }

        $where = [
            'status' => 1,
            'expiration_time >' => time(),
            'show_way like' => '%' . $show_way . '%',
        ];

        $senior = array(); //高级搜索
        $page = array(
            'page' => $page,
            'rows' => $rows,
            'order' => 'asc',//$this->G('order'),
            'sort' => 'sort',//$this->G('sort'),
            'total' => -1,
        );

        $arr = $this->core->get_list('id, img_base64, title, extra_title, type, addtime, start_time, expiration_time, content', 'set_activity', $where, $senior, $page);
        $arr = array_values($arr['rows']);
        foreach ($arr as $key => $value) {
            $arr[$key]['addtime'] = date("Y-m-d", $value['addtime']);
            $arr[$key]['start_time'] = date("Y-m-d", $value['start_time']);
            $arr[$key]['expiration_time'] = date("Y-m-d", $value['expiration_time']);
        }

        $rs = array('total' => count($arr), 'rows' => $arr);
        $this->return_json(OK, $rs);
    }

    public function h5_promotions_show()
    {
        $result['test'] = 'promotions';
        $result['sn'] = $this->_sn;
        $result['from_way'] = $this->from_way;
        $this->load->view('h5/promotions_show', $result);
    }


    public function show($id = 0)
    {
        $rs = $this->core->get_one('content', 'set_activity', ['id'=>$id, 'status' => 1]);
        $this->load->view('h5/show', $rs);
    }

    public function h5_show()
    {
        $id = (int)$this->G('id');
        if (empty($id)) {
            $this->return_json(E_ARGS, '参数错误');
        }
        $rs = $this->core->get_one('content', 'set_activity', ['id'=>$id, 'status' => 1]);
        $content = isset($rs['content']) ? $rs['content'] : '';
        $this->return_json(OK, ['content' => $content]);
    }

    public function h5_serivces_show()
    {
        $result['test'] = 'serivces';
        $result['sn'] = $this->_sn;
        $this->load->view('h5/services_show', $result);
    }

    /**
     * 获取优惠活动等级晋级图片（第一张图片）
     */
    public function getGradeImg()
    {
        $this->return_json(OK, array('img' => GRADE_IMG));
    }

    /**
     * 晋级活动等级列表
     */
    public function getGradeList()
    {
        $rs = $this->core->get_list('*', 'grade_mechanism', array('status' => 1), array('orderby' => array('id' => 'asc')));
        $this->return_json(OK, $rs);
    }

    /**
     * 晋级活动等级列表
     */
    public function getRewardList()
    {
        $rs = $this->core->get_list('*', 'reward_day', array('status' => 1), array('orderby' => array('id' => 'asc')));
        $this->return_json(OK, $rs);
    }

    // /**
    //   * @desc  获取晋级奖励和每日嘉奖数据
    //   * @param $id 活动id  id=1001 是晋级奖励  id =1002 是每日嘉奖 采用get传参
    //   * @$url http://www.gc360.com/activity/promotion/get_sperceil_active
    //   */
    // public  function get_sperceil_active()
    // {
    //     $arr = $this->core->get_all('*', 'set_activity', array(),['wherein' =>['id'=>[1001,1002]]]);
    //     $this->return_json(OK, $arr);
    // }
}
