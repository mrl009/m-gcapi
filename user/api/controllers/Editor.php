<?php

/**
 * Created by PhpStorm.
 * User: dragon
 * Date: 2017/4/17
 * Time: 20:28
 */
class Editor extends GC_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Editor_model', 'core');
    }

    public function index()
    {
        $result['test'] = 'test';
        $this->load->view('editor/editor', $result);
    }

    public function get_gid_list()
    {
        $this->core->get_gid_list();
    }

    public function get_user_data()
    {
        $this->core->get_game_rules_list();

    }

    public function create_game_data()
    {
        $type = $_POST['type'];
        $title = $_POST['title'];
        $content = $_POST['content'];

        $this->core->create_game_data($type, $title, $content);
    }

    public function update_game_data()
    {

        $id = $_REQUEST['id'];
        $type = $_REQUEST['type'];
        $title = $_REQUEST['title'];
        $content = $_REQUEST['content'];

        $this->core->update_game_data($id, $type, $title, $content);
    }

    public function delete_game_data()
    {

        $id = $_POST['id'];
        $this->core->delete_game_data($id);
    }

    public function opt_game_tips()
    {
        $result['test'] = 'test';
        $this->load->view('editor/game_tips_editor', $result);
    }

    public function get_game_tips_id_list()
    {
        $this->core->get_game_tips_id_list();
    }

    protected function getPlayTipsByGid($gid)
    {
        $data = null;
        $glist = $this->getplayall();
        if($glist != null){
            $data = [];
            foreach ($glist as $game) {
                if ($game['gid'] == $gid) {
                    $data[] = $game;
                }
            }
        }
        return $data;
    }

    public function get_game_tips_search_data()
    {
        try {

            if (isset($_REQUEST['gid']) && $_REQUEST['gid'] != null) {
                $gid = $_REQUEST['gid'];
                $data = $this->getPlayTipsByGid($gid);
            } else {
                $data = $this->getplayall();
            }

            echo json_encode($data, JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            echo json_encode($e->getMessage(), JSON_UNESCAPED_UNICODE);
            exit();
        }
    }

//玩法提示 中奖说明 范例
    public function update_game_tips_data()
    {

            $id = $_REQUEST['id'];
            $game_tips = $_REQUEST['game_tips'];
            $win_tips = $_REQUEST['win_tips'];
            $content1 = $_REQUEST['example1'];
            $content2 = $_REQUEST['example2'];

            $tips_data[0] = $game_tips;
            $tips_data[1] = $win_tips;
            $tips_data[2] = $content1;
            $tips_data[3] = $content2;


            $data = array(
                'paly_intro' => json_encode($tips_data, JSON_UNESCAPED_UNICODE)
            );

            $this->core->update_game_tips_data($id,$data);
    }


    /**
     * @brief 组织全部游戏的玩法或菜单列表为 json
     *      NOTE: 此函数有写文件缓存，
     *          如果后台其他地方有修改 gc_games_types 表，请删除此缓存文件:
     *      Cache: APPPATH.'cache/games_play.json'
     * @access protected
     * @return 所有游戏的所有玩法和菜单
     */
    public function getplayall() /* {{{ */
    {
       return $this->core->getplayall();
    } /* }}} */


}