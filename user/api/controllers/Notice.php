<?php
/**
 * Created by PhpStorm.
 * User: mrl
 * Date: 2017/5/26
 * Time: 下午2:42
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class Notice extends MY_Controller
{
    private $userKey = 'user:notice:uid';
    private $levelKey = 'user:notice:level_id';

    public function __construct()
    {
        parent::__construct();
        $this->load->model('MY_Model', 'core');
    }

    /**
     * 设置公告读取时间
     */
    public function setReadTime()
    {
        $id = $this->user['id'];
        $this->core->redis_hset($this->userKey, $id, time());
        $this->return_json(OK, '执行成功');
    }

    /**
     * 是否有未读公告
     */
    public function isNewNotice()
    {
        $id = $this->user['id'];
        $level_id = $this->core->get_one('level_id', 'user', array('id' => $id));
        $userTime = $this->core->redis_hget($this->userKey, $id);
        $noticeTime = $this->core->redis_hget($this->levelKey, $level_id['level_id']);
        $rs = $userTime > $noticeTime ? false : true;
        $this->return_json(OK, ['is_new_notice' => $rs]);
    }

    /**
     * 获取会员公告
     * @comment $type 公告类型0：最新公告，1：弹出（PC）2： 会员公告
     */
    public function getNotice()
    {
        $type = $this->G('type') ? intval($this->G('type')) : 0;
        $level_id = $this->core->get_one('level_id', 'user', array('id' => $this->user['id']));
        $show_location = $this->getShowLocation($this->from_way);
        $rs = $this->getUserNotice($type, $show_location,$level_id['level_id']);
        $this->return_json(OK, $rs);
    }

    /**
     * 获取显示位置
     * @param $from_way
     * @return int
     */
    private function getShowLocation($from_way)
    {
        $show_location = 0;
        if ($from_way == 1) {
            $show_location = 2;
        } else if ($from_way == 2) {
            $show_location = 1;
        } else if ($from_way == 3) {
            $show_location = 4;
        } else if ($from_way == 4) {
            $show_location = 3;
        }
        return $show_location;
    }

    /**
     * 获取会员公告
     * @param int $type 公告类型0：最新公告，1：弹出（PC）2： 会员公告
     * @param int $show_location 显示位置，0：全部，1：安卓，2：苹果 ,3：H5,4：PC
     * @param int $level_id 层级ID
     * @return mixed
     */
    private function getUserNotice($type, $show_location,$level_id)
    {
        $this->core->db->select('content,addtime,title,show_location,status,level_id');
        if ($show_location!=0){
            $this->core->db->where_in('show_location', [0, $show_location]);
        }
        $this->core->db->where_in('level_id', [-1, $level_id]);
        $this->core->db->order_by('addtime','desc');
        $res = $this->core->db->get_where('log_user_notice', array('status' => 1, 'notice_type' => $type), 5)->result_array();
        foreach ($res as &$v) {
            $v['time'] = date('Y-m-d H:i:s', $v['addtime']);
        }
        return $res;
    }
}