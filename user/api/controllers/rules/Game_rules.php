<?php

/**
 * Created by PhpStorm.
 * User: dragon
 * Date: 2017/4/17
 * Time: 17:42
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class Game_rules extends GC_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('rules/Game_rule_model', 'core');
    }

    /**
     *获取游戏规则
     */
    public function get_games_rules_content() {
        $type = $this->G('type');
        $result = null;

        $data = $this->core->get_game_rules_list($type);
        if(empty($data)){
            $result['code'] = 101;
        }else{
            $result = $this->data_formate($data);
        }
        $this->return_json(OK,$result);

    }

    /**
     * 获取游戏玩法提示
     */
    public function get_game_tips_content()
    {
        $id = (int)$this->G('id');
        $data = $this->core->get_game_tips_list($id);
        if(!empty($data)){
            $rows = json_decode($data[0]['paly_intro'],true);
            if (empty($rows)) {
                $rows = [];
            }
            $result['rows'] = $rows;
            $this->return_json(OK,$result);
        } else {
            $this->return_json(OK,['rows'=>[]]);
        }
    }

    /**
     * 获取文章内容
     */
    public function get_game_article_content(){
        $id = (int)$this->G('id');
        $data = $this->core->get_game_article_list($id);
        //wlog('ajax_test.txt',json_encode($data,JSON_UNESCAPED_UNICODE));
        if(!empty($data)) {
            //$data['sn'] = $this->_sn;
            $this->return_json(OK,$data);
        }
    }

    /**
     * 获取文章内容H5页面
     */
    public function h5_game_article_content(){
        $id = (int)$this->G('id');
        $result['sn'] = $this->core->sn;
        $result['id'] = $id;
        $this->load->view('h5/artical_tips',$result);
    }

    /**
     * 游戏规则H5页面
     */
    public function h5_games_rules_content(){
        $type = $this->G('type');
        $result = null;

        $data = $this->core->get_game_rules_list($type);
        if(empty($data)){
            $result['code'] = 101;
        }else{
            $result = $this->data_formate($data);
        }
        $result['sn'] = $this->_sn;

        $this->load->view('h5/game_rule',$result);
    }

    private function data_formate($data){

        $content = [];
        $introduction = $data[0];

        array_splice($data,0,1);

        foreach ($data as $key => $value){
            $content[$value['title']][] = $value['content'];
        }

        $result['code'] = 200;
        $result['introduction'] = $introduction['content'];
        $result['rows'] = $content;

        return $result;
    }
}
