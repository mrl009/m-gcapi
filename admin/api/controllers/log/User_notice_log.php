<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Created by PhpStorm.
 * User: mrl
 * Date: 2017/4/14
 * Time: 下午3:08
 */
class User_notice_log extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('MY_Model', 'core');
        $this->load->model('log/User_notice_log_model');
    }

    /**
     * 获取公告类型和显示位置
     */
    public function getNoticeTypeAndShowLocation()
    {
        $rs['notice_type'] = $this->User_notice_log_model->getNoticeType();
        $rs['show_location'] = $this->User_notice_log_model->getShowLocation();
        $this->return_json(OK, $rs);
    }

    /**
     * 获取消息列表
     */
    public function getNoticeLogList()
    {
        $this->core->open('log_user_notice');//打开表
        // 获取搜索条件
        $condition = [
            'from_time' => $this->G('from_time'),
            'to_time' => $this->G('to_time'),
            'status' => $this->G('status'),
            'content' => $this->G('content'),
        ];
        $searchInfo = $this->User_notice_log_model->getBasicAndSenior($condition);
        $page = array(
            'page' => $this->G('page'),
            'rows' => $this->G('rows'),
            'order' => $this->G('order'),
            'sort' => $this->G('sort'),
            'total' => -1,
        );
        $arr = $this->core->get_list('*', 'log_user_notice', $searchInfo['basic'], $searchInfo['senior'], $page);
        $arr = $this->User_notice_log_model->formatData($arr);
        $rs = array('total' => $arr['total'], 'rows' => $arr['rows']);
        $this->return_json(OK, $rs);
    }

    /**
     * 获取公告明细
     */
    public function getInfo()
    {
        $id = $this->G('id');
        if (empty($id)) {
            $this->return_json(E_DATA_EMPTY);
        }
        $arr = $this->core->get_one('*', 'log_user_notice', array('id' => $id));
        $this->return_json(OK, $arr);
    }

    /**
     * 修改添加公告
     */
    public function saveNotice()
    {
        $id = $this->P('id');
        $admin_id = $this->P('admin_id');
        $content = $this->P('content');
        $notice_type = $this->P('notice_type');
        $level_id = $this->P('level_id');
        $show_location = $this->P('show_location');
        $title = $this->P('title');

        if (empty($admin_id) || empty($content) || empty($title) || empty($level_id)) {
            $this->return_json(E_ARGS, 'Parameter is null');
        }
        $data = array(
            'admin_id' => $admin_id,
            'content' => $content,
            'title' => $title,
            'notice_type' => $notice_type,
            'level_id' => $level_id,
            'show_location' => $show_location,
            'status' => 1
        );
        $where = array();
        if (!empty($id)) {
            $where['id'] = $id;
        } else {
            $data['addtime'] = time();
        }
        $this->core->write('log_user_notice', $data, $where);
        // 设置更新时间公告
        if ($notice_type == 2) {
            $this->setNoticeTime($level_id);
        }
        // 记录操作日志
        $pre = !empty($id) ? '修改' : '新增';
        $this->load->model('log/Log_model');
        $this->Log_model->record($this->admin['id'], array('content' => "{$pre}会员公告,标题为:{$title}"));
        $this->return_json(OK, '执行成功');
    }

    public function delete()
    {
        $id = $this->P('id');
        if (empty($id)) {
            $this->return_json(E_ARGS, 'Parameter is null');
        }
        $this->core->delete('log_user_notice', explode(',', $id));
        // 记录操作日志
        $this->load->model('log/Log_model');
        $this->Log_model->record($this->admin['id'], array('content' => '删除了会员公告ID为:' . $id));
        $this->return_json(OK, '执行成功');
    }

    /**
     * 更新状态
     */
    public function updateStatus()
    {
        $id = $this->P('id');
        $status = $this->P('status');
        if (empty($id)) {
            $this->return_json(E_ARGS, 'Parameter is null');
        }
        $status = $status == 1 ? 0 : 1;
        $pre = $status == 1 ? '启用' : '停用';
        $this->core->write('log_user_notice', array('status' => $status), array('id' => $id));
        // 记录操作日志
        $this->load->model('log/Log_model');
        $this->Log_model->record($this->admin['id'], array('content' => "{$pre}了会员公告ID为:{$id}"));
        $this->return_json(OK, '执行成功');
    }

    /**
     * 设置更新时间公告
     * @param $level_id
     */
    private function setNoticeTime($level_id)
    {
        $key = 'user:notice:level_id';
        $this->core->redis_hset($key, $level_id, time());
    }
}
