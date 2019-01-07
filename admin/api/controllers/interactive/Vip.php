<?php
/**
 * vip权限设置 接口
 * User: lqh6249
 * Date: 1970/01/01
 * Time: 00:01
 */
defined('BASEPATH') or exit('No direct script access allowed');

class Vip extends MY_Controller
{
    static $wsAct = '';  
    public function __construct()
    {
        parent::__construct();
        $this->load->helper('interactive');
        $this->load->model('interactive/Vip_model','VM');
        self::$wsAct = new WsAct($this->VM->sn);
    }

    //获取vip发言权限信息数据
    public function get_access_list()
    {
        $data = $this->VM->get_list('*','ws_vip_access');
        $this->return_json(OK, $data);
    }

    //获取vip权限设置信息
    public function get_access_info()
    {
        $id = input("param.id",'','intval');
        if (empty($id)) $this->return_json(E_ARGS,'Parameter is null');
        //构造条件查询语句
        $where['id'] = $id;
        $info = $this->VM->get_one('*','ws_vip_access',$where);
        $this->return_json(OK,$info);
    }

    //保存Vip权限
    public function access_save()
    {
        $id = input("param.id",0,'intval');
        $is_speak = input("param.is_speak"); 
        $is_share = input("param.is_share"); 
        $record_num = input("param.record_num",0,'intval');
        $red_grab_num = input("param.red_grab_num",0,'intval');
        $red_send_num = input("param.red_send_num",0,'intval');
        if (empty($id)) $this->return_json(E_ARGS,'invalid parameter');
        /*当设置当前VIP发言权限为是时，高该等级的VIP默认有发言权限
        * @php 1、更新当前用户vip权限及发言条数
        * @php 2、更新更高等级vip权限不包括发言
         */
        if (1 == $is_speak)
        {
            //开启事物
            $this->VM->db->trans_start();
            // 1、高等级vip等级权限数据
            $save_data['is_speak'] = 1;
            $save_data['is_share'] = $is_share;
            $where['vip_id >'] = $id;
            $result = $this->VM->write('ws_vip_access',$save_data,$where);
            // 2、当前vip等级权限数据
            unset($where);
            $where['vip_id'] = $id;
            $save_data['record_num'] = $record_num;
            $save_data['red_grab_num'] = $red_grab_num;
            $save_data['red_send_num'] = $red_send_num;
            $result = $this->VM->write('ws_vip_access',$save_data,$where);
            //事物结束
            $this->VM->db->trans_complete();
        //当设置当前VIP发言权限为否时，低于该等级的VIP默认不具有发言权限
        } else {
            $save_data['is_speak'] = 0;
            $save_data['is_share'] = 0;
            $save_data['record_num'] = 0;
            $where['vip_id <='] = $id;
            $result = $this->VM->write('ws_vip_access',$save_data,$where);
        }
        unset($parms);
        if ($result) 
        {
            //数据库保存执行结果以后 redis数据同步更新
            $sdata = [];
            $key = 'wshddt:vip_access';
            $data = $this->VM->get_list('*','ws_vip_access'); 
            foreach($data as $k => $v)
            {
                $sdata[$v['vip_id']] = $data[$k];
            }
            unset($data);
            $sdata = json_encode($sdata,true);
            self::$wsAct->set($key, $sdata);
            //返回执行结果
            $this->return_json(OK,'执行成功');
        } else {
            $this->return_json(E_ARGS,'没有数据被修改');
        }
    }
}
