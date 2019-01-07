<?php
defined('BASEPATH') or exit('No direct script access allowed');
/**
 * @file Manager.php
 * @brief 站点设置
 *
 * @package controllers
 * @author bigChao <bigChao> 2017/03/23
 */
class Weihu extends GC_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('MY_Model', 'core');
    }



    /**********************游戏管理***********************/
    /**
     * @模块   设置管理／游戏管理
     * @版本   Version 2.0.0
     * @日期   2017-05-23
     * @重构   super
     */
    /* 游戏状态修改接口 是否维护 */
    public function set_games_status()
    {
        $gid    = (int)$this->G('gid');
        $status = (int)$this->G('status');
        /* 维护状态:0-正常,1-维护,2-永久下架 */
        $status_arr = array(0, 1, 2);
        if (!in_array($status, $status_arr) || $gid < 0) {
            $this->return_json(E_ARGS, '参数错误');
        }
        $status = ($status == 0) ? "'0'" : $status;
        $this->core->select_db('public');
        $this->core->db->where('id', $gid);
        $this->core->db->set('status', $status);
        $this->core->db->set('wh_content', '');
        $status = $this->core->db->update('games');
        if ($status) {
            $this->core->del_redis();
            $this->return_json(OK);
        }
        $this->return_json(E_ARGS, '修改失败');
    }

    /* 游戏热门修改接口 */
    public function set_games_hot()
    {
        $gid = (int)$this->G('gid');
        $hot = (int)$this->G('hot');
        /* 维护状态:0-正常,1-热门 */
        $hot_arr = array(0, 1);
        if (!in_array($hot, $hot_arr) || $gid < 0) {
            $this->return_json(E_ARGS, '参数错误');
        }
        $hot = ($hot == 0) ? "'0'" : $hot;
        $this->core->select_db('public');
        $status =  $this->core->db->where('id', $gid)->set('hot', $hot)->update('games');
        if ($status) {
            if ($hot==1) {
                if ($this->core->redisP_zrank('hot', $gid)===false) {
                    $this->core->db->select('id,sort');
                    $arr = $this->core->db->get_where('games', array('id'=>$gid))->row_array();
                    $this->core->redisP_zadd('hot', $arr['sort'], $arr['id']);
                }
            } else {
                $this->core->redisP_zrem('hot', $gid);
            }
            $this->return_json(OK);
        }
        $this->return_json(E_ARGS, '修改失败');
    }


    /**********************END游戏管理********************/
}
