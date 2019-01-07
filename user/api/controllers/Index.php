<?php
/**
 * Index
 *
 * @file        user/api/controllers/index
 * @package     controllers
 * @author      ssm
 * @version     v1.0 2017/07/09
 * @created 	2017/07/09
 */
class Index extends GC_Controller
{
    /**
     * 构造函数
     *
     */
    public function __construct()
    {
        parent::__construct();
        $this->load->model('GC_Model', 'G');
        $this->G->select_db('public');
    }

    /**
     * 获取H5地址
     *
     * @access public
     * @return Array ['content'=>'', 'version'=>'', 'hot'=>'']
     */
    public function version()
    {
        $where['sn'] = $this->G->sn;
        $select = 'url content,`type`,version,hot';
        $version = $this->G->get_one($select, 'ios_version',$where);
        if (empty($version)) {
            //$join = ['orderby'=>['id'=>'asc']];
            //$version = $this->G->get_one($select, 'ios_version',[],$join);
            $version = ['content'=>'', 'type'=>0, 'version'=>'1.1.1', 'hot'=>0];
        }
        $this->return_json(OK, $version);
    }

    public function download()
    {
        $where = [];

        $appType = $this->G('apptype');
        if ($appType == 'ios') {
            $where['app_type'] = '1';
        } elseif ($appType == 'android') {
            $where['app_type'] = '2';
        }else{
            $this->return_json(E_ARGS,'apptype错误');
        }
        $this->G->select_db('private');
        $version = $this->G->get_one ('url', 'version', $where);
        $this->return_json(OK,$version);
    }
}
