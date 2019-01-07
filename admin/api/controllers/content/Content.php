<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Content extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('MY_Model', 'core');
        $this->load->model('content/Content_model');
    }

    /**************************获取内容列表****************************/
    public function getArticleList()
    {
        $condition = [
            'time_start' => $this->G('time_start') ? strtotime($this->G('time_start') . ' 00:00:00') : 0,
            'time_end' => $this->G('time_end') ? strtotime($this->G('time_end') . ' 23:59:59') : 0,
            'keywords' => $this->G('keywords'),
            'cate_id' => $this->G('search_category_id'),
        ];
        $basic = array();
        $senior = array(
            'orderby' => array('orderby' => 'desc')
        );
        $page = array(
            'page' => $this->G('page'),
            'rows' => $this->G('rows'),
            'order' => $this->G('order'),
            'sort' => $this->G('sort'),
            'total' => -1,
        );
        if ($condition['time_start']) {
            $basic['add_time >='] = $condition['time_start'];
        }
        if ($condition['time_end']) {
            $basic['add_time <'] = $condition['time_end'];
        }
        if ($condition['keywords']) {
            $basic['title like'] = '%'.$condition['keywords'].'%';
        }
        if ($condition['cate_id']) {
            $basic['category_id'] = $condition['cate_id'];
        }
        $rs = $this->core->get_list('*', 'article', $basic, $senior, $page);
        if (!empty($rs['rows'])) {
            $cateNames = $this->core->get_list('id,name','article_category',[],['wherein' => array('id' => array_column($rs['rows'], 'category_id'))]);
            foreach ($rs['rows'] as &$v) {
                foreach ($cateNames as $item) {
                    if ($item['id'] == $v['category_id']) {
                        $v['category_name'] = $item['name'];
                    }
                }
                $v['add_time'] = date('Y-m-d H:i:s', $v['add_time']);
            }
        }
        $this->return_json(OK, $rs);
    }

    // 获取一条文章信息
    public function getOneArticle()
    {
        $id = (int)$this->G('id');
        if (!empty($id) && $id > 0) {
            $rs = $this->core->get_one('*', 'article', array('id' => $id));
            if (!empty($rs)) {
                $arr = $this->core->get_one('*', 'article_category', array('id' => $rs['category_id']));
                $rs['cateName'] = isset($arr['name'])?$arr['name']:'';
            }
        }
        $this->return_json(OK, $rs);
    }

    // 保存文章信息
    public function saveArticle()
    {
        $id = (int)$this->P('id');
        $title = $this->P('title');
        $remark = $this->P('remark');
        $category_id = (int)$this->P('category_id');
        $content = $this->P('content');
        $order_by = (int)$this->P('orderby');
        $no_edit = (int)$this->P('no_edit');

        if (empty($title)) {
            $this->return_json(E_ARGS, 'Parameter is error');
        }
        if (!empty($id) && $no_edit == 2) {
            $this->return_json(E_ARGS, '当前文章不能编辑');
        }

        $data = array(
            'title' => $title,
            'remark' => $remark,
            'category_id' => $category_id,
            'content' => $content,
            'orderby' => $order_by,
        );
        $where = array();
        if (!empty($id)) {
            $where['id'] = $id;
        } else {
            $data['add_time'] = time();
        }
        $this->core->write('article', $data, $where);
        // 记录操作日志
        $pre = !empty($id) ? '修改' : '新增';
        $this->load->model('log/Log_model');
        $this->Log_model->record($this->admin['id'], array('content' => "{$pre}内容列表,标题为:{$title}"));
        $this->return_json(OK, '执行成功');
    }

    // 删除文章
    public function deleteArticle()
    {
        $id = $this->P('id');
        if (empty($id)) {
            $this->return_json(E_ARGS, 'Parameter is null');
        }
        // 记录操作日志
        $this->load->model('log/Log_model');
        $this->Log_model->record($this->admin['id'], array('content' => '删除了内容列表ID为:' . $id));
        $this->core->delete('article', explode(',', $id));
    }

    /**************************END内容列表****************************/

    /**************************获取文章分类****************************/
    // 获取分类列表
    public function getCategoryList()
    {
        $pid = $this->G('pid');
        $self = $this->G('self');
        // 搜索条件
        $where['parent_id'] = $pid ? $pid : 0;
        if ($self) {
            $where['id !='] = $self;
        }
        $arr = $this->core->db->select('*')->get_where('article_category', $where)->result_array();

        $rs = array();
        foreach ($arr as $v) {
            $a = $this->core->get_list('*', 'article_category', array('parent_id' => $v['id']));
            $rs[] = array(
                'id' => $v['id'],
                'label' => $v['name'],
                'branch' => array(),
                'inode' => count($a) > 0 ? true : false,
                'no_del' => $v['no_del'],
                'no_edit' => $v['no_edit'],
            );
        }
        foreach ($rs as $k => $v) {
            $rs[$k]['label'] .= ' (' . $rs[$k]['id'] . ')';
            $rs[$k]['action'] = '<a href="javascript:editChannel(' . $v['id'] . ')" class="btn btn-default btn-sm btn-icon icon-left"'
                . ($v['no_edit'] == 1 ? '' : 'style="visibility:hidden"') . '><i class="entypo-pencil"></i>编辑</a> <a href="javascript:delChannel('
                . $v['id'] . ')" class="btn btn-danger btn-sm btn-icon icon-left" '
                . ($v['no_del'] == 1 ? '' : 'style="visibility:hidden"') . '><i class="entypo-cancel"></i>删除</a>';
            $rs[$k]['open'] = false;
        }
        $this->return_json(OK, $rs);
    }

    // 获取一条分类信息
    public function getOneCategory()
    {
        $id = (int)$this->G('id');
        $rs = $this->core->get_one('*', 'article_category', array('id' => $id));
        if ($rs['parent_id'] != 0) {
            $arr = $this->core->get_one('*', 'article_category', array('id' => $rs['parent_id']));
            $rs['cateName'] = $arr['name'];
        }
        $this->return_json(OK, $rs);
    }

    // 保存分类信息
    public function saveCategory()
    {
        $id = $this->P('id');
        $name = $this->P('name');
        $parent_id = $this->P('parent_id');
        $no_edit = $this->P('no_edit');
        if (empty($name)) {
            $this->return_json(E_ARGS, 'Parameter is error');
        }
        if (!empty($id) && $no_edit == 2) {
            $this->return_json(E_ARGS, '当前文章分类不能编辑');
        }

        $data = array(
            'name' => $name,
            'parent_id' => $parent_id,
        );
        $where = array();
        if (!empty($id)) {
            $where['id'] = $id;
        }
        $this->core->write('article_category', $data, $where);
        // 记录操作日志
        $pre = !empty($id) ? '修改' : '新增';
        $this->load->model('log/Log_model');
        $this->Log_model->record($this->admin['id'], array('content' => "{$pre}分类管理,标题为:{$name}"));
        $this->return_json(OK, '执行成功');
    }

    // 删除分类
    public function deleteCategory()
    {
        $id = $this->P('id');
        if (empty($id)) {
            $this->return_json(E_ARGS, 'Parameter is null');
        }
        $rs = $this->core->db->select('id')->get_where('article_category', array('parent_id' => $id))->result_array();
        if (!empty($rs)) {
            $ids = array_column($rs, 'id');
            array_push($ids, $id);
        } else {
            $ids = [$id];
        }
        // 记录操作日志
        $this->load->model('log/Log_model');
        $this->Log_model->record($this->admin['id'], array('content' => '删除了分类管理ID为:' . $id));
        $this->core->db->where_in('id', $ids)->delete('article_category');
    }
    /**************************END文章分类****************************/
}