<?php
/**
 * @模块   会员中心／投注记录model
 * @版本   Version 1.0.0
 * @日期   2017-03-30
 * shensiming
 */

if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

class Game_rule_model extends MY_Model
{
	public function __construct()
    {
        parent::__construct();
    }

    /**
     * 获取游戏规则
     */
    public function get_game_rules_list($type)
    {
        $data = [];
        $select = '*';
        $this->select_db('public');
        $game_rules = $this->get_list($select, 'game_rule', array('type'=>$type));

        if($game_rules == null){
            $data = null;
        }else{
            foreach ($game_rules as $value){

                if($value['type'] == $type){
                    $data[] = $value;
                }
            }

            $data = $this->sequence_sort($data,'id','SORT_ASC');
        }
        return $data;
    }

    /**
     * 获取玩法提示
     */
    public function get_game_tips_list($id)
    {
        $select = 'paly_intro';
        $this->select_db('public');
        $result = $this->get_list($select, 'games_types', array('id'=>$id));
        return $result;
    }

    /**
     * 获取文章内容
     */
    public function get_game_article_list($id)
    {
        $select = 'content,title';
        $this->select_db('private');
        $result = $this->get_list($select, 'set_article', array('id'=>$id));
        return $result;
    }


    protected  function sequence_sort($arrayList, $field, $type) {
        $arrSort = array();
        foreach ($arrayList as $uniqid => $row) {
            foreach ($row as $key => $value) {
                $arrSort[$key][$uniqid] = $value;
            }
        }
        array_multisort($arrSort[$field], constant($type), $arrayList);
        return $arrayList;

    }


}




