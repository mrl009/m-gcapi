<?php
/**
 * Created by PhpStorm.
 * User: mrl
 * Date: 2017/7/12
 * Time: 上午8:48
 */

defined('BASEPATH') or exit('No direct script access allowed');

class Content extends GC_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('MY_Model', 'core');
    }

    /**
     * 获取文章列表
     */
    public function getContentList()
    {
        // 接收数据
        $category_id = $this->G('category_id');
        $parent_id = $this->G('parent_id');
        $page = $this->G('page') != 0 ? (int)$this->G('page') : 1;

        // 搜索条件
        $basic = array();
        $senior = array(
            'orderby' => array('orderby' => 'desc', 'id' => 'desc')
        );

        $pages = array(
            'page' => $page > 0 ? $page : 1,
            'rows' => $page == -1 ? 1000 : 16,
        );
        if ($parent_id) {
            $cate_ids = $this->core->get_list('id', 'article_category',array('parent_id' => $parent_id));
            if (!empty($cate_ids)) {
                $senior['wherein'] = array('category_id' => array_column($cate_ids, 'id'));
            }
        } elseif ($category_id) {
            $basic['category_id'] = $category_id;
        }
        $rs = $this->core->get_list('id,title,category_id,add_time,content', 'article', $basic, $senior, $pages);
        if (!empty($rs['rows'])) {
            $cateNames = $this->core->get_list('id,name', 'article_category', [], ['wherein' => array('id' => array_column($rs['rows'], 'category_id'))]);
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

    /**
     * 获取文章详情
     */
    public function getContentDetail()
    {
        $id = (int)$this->G('id');
        $rs = $this->core->get_one('title,remark,content', 'article', array('id' => $id));
        $this->return_json(OK, $rs);
    }
	/**
     * 获取图片列表(视讯)
     * http://www.guocaitupian.com/ag/AV01.jpg
     */
    public function getImageList()
    {
        $topid  = $this->input->get_post('topid');
        $name   = $this->input->get_post('name') ? : null;
        $type   = $this->input->get_post('type') ? : 'mg'; //类型
        $page   = $this->input->get_post('page') ? : 1; //第几页
        $rows   = $this->input->get_post('rows') ? : 10; //多少条
        $where  = [];
        if($topid) $where[]  = ['topid'=>$topid]; //array_merge($where,['gameid'=>$gid]);
        if($name) $where[] = "name like '%$name%'";// array_merge($where,['name','like',"'$name'"]);
        if($type) $where[] = ['type'=>$type];//array_merge($where,['type'=>$type]);

        $this->load->database('shixun');
        $ret = $this->set_page_x('sx_game',$page,$rows,$where,null);
        //echo $this->db->last_query();
        $img_url = parse_url( UPLOAD_URL );
        $img_url = isset($img_url['port']) ? '//'. $img_url['host'] .$img_url['port'] : '//' . $img_url['host'] ;
        foreach ($ret['sx_game'] as $k => &$v) {
            $v->image = $img_url . '/' . $type . '/' . $v->image ;
        }

        $this->return_json(OK,$ret);

    }
    protected function set_page_x($table=NULL, $page_num=1, $limit=1, $where, $column='id', $desc='desc', $distcol='')
    {
        if($table == NULL){
            return FALSE;
        }
        $page_num < 1 ? $page_num=1 : $page_num;
        $page_num = floor($page_num);
        if(count($where) > 0){
            foreach ($where as $k=>$v) {
                    $this->db->where($v);
            }
        }
        if($distcol != ''){
            $result = $this->db->query("select count(distinct(".$distcol.")) as n from ".$table." where ".$where)->row();
            if(empty($result)){
                $page_count = 0;
            }else{
                $page_count = ceil($result->n/$limit);
            }
        }else{
            $page_count = ceil($this->db->count_all_results($table)/$limit);
        }

        $page_num > $page_count ? $page_num=$page_count : $page_num;
        $offset = $limit*($page_num-1);
        $offset < 1 ? $offset=0 : $offset;

        if($distcol != ''){
            $this->db->group_by($distcol);
        }
        if(count($where) < 1 && $column == NULL){#无条件 + 无排序
            $table_data = $this->db->get($table, $limit, $offset);
        }elseif(count($where) > 0 && $column == NULL){#有条件 + 无排序
            foreach ($where as $k=>$v) {
                    $this->db->where($v);
            }
            //$this->db->where($where);
            $table_data = $this->db->get($table, $limit, $offset);
        }elseif(count($where) > 0 && $column != NULL){#有条件 + 有排序
            $this->db->order_by($column, $desc);
            foreach ($where as $k=>$v) {
                    $this->db->where($v);
            }
            $table_data = $this->db->get($table, $limit, $offset);
        }elseif(count($where) < 1 && $column != NULL){#无条件 + 有排序
            $this->db->order_by($column, $desc);
            $table_data = $this->db->get($table, $limit, $offset);
        }

        $data = array(
            'page_num'=>$page_num,
            'page_count'=>$page_count,
            $table=>$table_data->result(),
        );

        $table_data->free_result();
        return $data;
    }
}
