<?php
/**
 * @模块   会员中心／我的收藏
 * @版本   Version 1.0.0
 * @日期   2017-04-21
 * super
 */

defined('BASEPATH') or exit('No direct script access allowed');

class Favorite extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('MY_Model', 'M');
    }

    //获取收藏列表
    public function get_favorite()
    {
        $where['uid'] =$this->user['id'];
        if (empty($where['uid'])) {
            $this->return_json(E_ARGS, '参数错误!');
        }
        $one_fav = $this->M->get_one('favorite', 'user_detail', $where, array());
        if (empty($one_fav['favorite'])) {
            $this->return_json(OK);
        }
        $content['status <>'] = 2;
        $condition['wherein'] = array('id'=>explode(',', $one_fav['favorite']));
        $this->M->select_db('public');
        $result= $this->M->get_list('id,name,type,img,status,wh_content,tmp', 'games', $content, $condition);
        foreach ($result as $k => $v){
            $prefix = explode('_',$v['tmp']);
            if (strtolower($prefix[0])=='s'){
                $result[$k]['ctg'] = 'sc';
            }else{
                $result[$k]['ctg'] = 'gc';
            }

        }
        empty($result)?$this->return_json(OK):$this->return_json(OK, $result);
    }

    /*
     * 判断是否用户收藏的游戏
     */
    public function is_favor()
    {
        $fav_key = 'user:favorite:uid';
        $where['uid'] =$this->user['id'];
        $gid = $this->P('gid');
        if (empty($where['uid']) || empty($gid)) {
            $this->return_json(E_ARGS, '参数错误!');
        }
        $my_favor = $this->M->redis_hexists($fav_key,$this->user['id']);
        if ($my_favor === false) {
            $my_favor = $this->M->get_one('favorite', 'user_detail', $where, array());
            if (empty($my_favor['favorite'])) {
                $this->M->redis_hset($fav_key,$this->user['id'],'');
                $this->return_json(OK,['flag'=>false,'favor'=>[]]);
            } else {
                $this->M->redis_hset($fav_key,$this->user['id'],$my_favor['favorite']);
                $my_favor = explode(',',trim($my_favor['favorite']));
                if (in_array($gid,$my_favor)) {
                    $flag = true;
                } else {
                    $flag = false;
                }
                $this->return_json(OK,['flag'=>$flag,'favor'=>$my_favor]);
            }
        } else {
            $my_favor = $this->M->redis_hget($fav_key,$this->user['id']);
            $my_favor = explode(',',trim($my_favor));
            if (in_array($gid,$my_favor)) {
                $flag = true;
            } else {
                $flag = false;
            }
            $this->return_json(OK,['flag'=>$flag]);
        }
    }


    //加入或取消收藏
    public function set_favorite()
    {
        $where['uid'] =$this->user['id'];
        $arr['gid'] = $this->P('gid');
        $status = $this->P('status');
        if (empty($where['uid']) || empty($arr['gid'])) {
            $this->return_json(E_ARGS, '参数错误!');
        }
        $one_fav = $this->M->get_one('favorite', 'user_detail', $where, array());
        $update = array();
        if (empty($status)) {//添加收藏
            if (empty($one_fav['favorite'])) {
                $update['favorite'] = $arr['gid'];
            } else {
                $update['favorite'] = $one_fav['favorite'].','.$arr['gid'];
            }
        } elseif ($status == 1) {//取消收藏
            if (strlen($one_fav['favorite'])<=2) {
                trim($one_fav['favorite'], ',')==trim($arr['gid'], ',')?
                    $update['favorite'] = '':$this->return_json(E_OP_FAIL, '操作失败！没有添加此收藏！');
            } else {
                $fav_arr = explode(',', $one_fav['favorite']);
                $gid = explode(',', $arr['gid']);
                $update['favorite']  = implode(',', array_flip(array_flip(array_merge(array_diff($fav_arr, $gid)))));
                //对比两个数组返回差集并去除差集数组的重复部分
            }
        } else {
            $this->return_json(E_ARGS, '参数错误!');
        }
        $is = $this->M->write('user_detail', $update, $where);
        $fav_key = 'user:favorite:uid';
        $this->M->redis_hset($fav_key,$this->user['id'],$update['favorite']);
        $is?$this->return_json(OK):$this->return_json(E_OP_FAIL, '操作失败！');
    }
}
