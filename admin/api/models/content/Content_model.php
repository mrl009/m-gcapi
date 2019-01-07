<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Content_model extends MY_Model
{
    /**
     * 获取搜索条件
     * @param $condition
     * @return array
     */
    public function getBasicAndSenior($condition)
    {

    }

    //当前分类下子分类
    public function getCategory($pid, &$ids)
    {
        $arr = array_pop($this->pub_search(array('parent_id' => $pid)));
        if ($arr == array()) {
            return false;
        } else {
            foreach ($arr as $v) {
                $ids[] = $v['id'];
                $this->getCategory($v['id'], $ids);
            }
        }
    }

    //自由查询文章
    public function pub_search($tj=array(),$tj2=array()){
        $arr = $this->get_all('*',$tj,'article_category',$tj2);
        return array('status'=>true,'msg'=>$arr);
    }
}