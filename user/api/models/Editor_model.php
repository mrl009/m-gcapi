<?php

/**
 * Created by PhpStorm.
 * User: dragon
 * Date: 2017/5/22
 * Time: 14:54
 */
class Editor_model extends GC_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->select_db('public');
    }

    public function get_game_rules_list()
    {
        try {

            if (isset($_REQUEST['type'])) {
                $where = [
                    'type' => $_REQUEST['type'],
                ];
                $this->db_public->where($where);
            }


            $query = $this->db_public->get('gc_game_rule');
            $data = $query->result_array();

            echo json_encode($data, JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            echo json_encode($e->getMessage(), JSON_UNESCAPED_UNICODE);
            exit();
        }
    }

    public function get_gid_list()
    {
        try {

            $result = [];
            $this->db_public->select('type');
            $this->db_public->distinct();
//            $this->db->group_by("gid");
            $query = $this->db_public->get('gc_game_rule');
            $data = $query->result_array();

            foreach ($data as $key => $value) {
                $result[$key]['id'] = $value['type'];
                $result[$key]['value'] = $value['type'];
            }

            echo json_encode($result, JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            echo json_encode($e->getMessage(), JSON_UNESCAPED_UNICODE);
            exit();
        }
    }

    public function create_game_data($type,$title,$content){
        try {
            $data = [
                'type' => $type,
                'title' => $title,
                'content' => $content,
            ];

            if ($type == null || $title == null) {
                throw new Exception('gid or title = null', 101);
            }

            $this->db_public->insert('gc_game_rule', $data);

            $result = [
                'code' => 200,
                'msg' => 'ok',
            ];

            echo json_encode($result, JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {

            echo json_encode($e->getMessage(), JSON_UNESCAPED_UNICODE);
            exit();
        }
    }

    public function update_game_data($id,$type,$title,$content){
        try {

            $data = array(
                'title' => $title,
                'type' => $type,
                'content' => $content
            );

            $this->db_public->where('id', $id);
            $this->db_public->update('gc_game_rule', $data);

            $result = [
                'code' => 200,
                'msg' => 'ok',
            ];
            echo json_encode($result, JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            echo json_encode($e->getMessage(), JSON_UNESCAPED_UNICODE);
            exit();
        }
    }

    public function delete_game_data($id)
    {
        try {

            $where = [
                'id' => $id,
            ];

            if ($id == null) {
                throw new Exception('id is null', 101);
            }

            $this->db_public->where($where);
            $this->db_public->delete('gc_game_rule');

            $result = [
                'code' => 200,
                'msg' => 'ok'
            ];
            echo json_encode($result, JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            echo json_encode($e->getMessage(), JSON_UNESCAPED_UNICODE);
            exit();
        }
    }

    public function get_game_tips_id_list()
    {
        try {

            $result = [];
            $this->db_public->select('gid,game_name');
            $this->db_public->distinct();
            $query = $this->db_public->get('gc_games_types');
            $data = $query->result_array();

            foreach ($data as $key => $value) {
                $result[$key]['id'] = $value['gid'];
                $result[$key]['value'] = $value['game_name'];
            }

            echo json_encode($result, JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            echo json_encode($e->getMessage(), JSON_UNESCAPED_UNICODE);
            exit();
        }
    }

    public function getplayall() /* {{{ */
    {
//        $file = APPPATH.'cache/games_play.json';
//        if (file_exists($file)) {
//            return json_decode(file_get_contents($file), true);
//        }

        $glist = $this->db_public->get('gc_games', null, null)->result_array();
        $c = count($glist);

        /* 支持三级菜单 */
        for ($i = 0; $i < $c; $i++) {
            // 如果需要支持超过三级菜单，请打开下一行注释，并注释掉 for 里面其他代码
            // $glist[$i]['play'] = $this->getplay_n($glist[$i]['id']);
            $res = $this->db_public->select('id,name,sname,pid,is_type,gid,status,paly_intro')
                ->get_where('gc_games_types', array('gid' => $glist[$i]['id'], 'pid' => 0, 'status !=' => 2));
            $glist[$i]['play'] = $res->result_array();

            $cp = count($glist[$i]['play']);
            for ($j = 0; $j < $cp; $j++) {
                $res = $this->db_public->select('id,name,sname,pid,is_type,gid,status,paly_intro')
                    ->get_where('gc_games_types', array('pid' => $glist[$i]['play'][$j]['id'], 'status !=' => 2));
                $glist[$i]['play'][$j]['play'] = $res->result_array();

                $cp2 = count($glist[$i]['play'][$j]['play']);
                if ($cp2 == 0) {
                    unset($glist[$i]['play'][$j]['play']);
                }
                for ($k = 0; $k < $cp2; $k++) {
                    $res = $this->db_public->select('id,name,sname,pid,is_type,gid,status,paly_intro')
                        ->get_where('gc_games_types', array('pid' => $glist[$i]['play'][$j]['play'][$k]['id'], 'status !=' => 2));
                    $glist[$i]['play'][$j]['play'][$k]['play'] = $res->result_array();
                    $cp3 = count($glist[$i]['play'][$j]['play'][$k]['play']);
                    if ($cp3 == 0) {
                        unset($glist[$i]['play'][$j]['play'][$k]['play']);
                    }
                }
            }
        }

        $data = [];
        foreach ($glist as $k1 => $v1) {
            if (isset($v1['play'])) {
                foreach ($v1['play'] as $k2 => $v2) {
                    if (isset($v2['play'])) {
                        foreach ($v2['play'] as $k3 => $v3) {
                            if (isset($v3['play'])) {

                                foreach ($v3['play'] as $k4 => $v4) {
                                    $data[] = [
                                        'id' => $v4['id'],
                                        'gid' => $v2['gid'],
                                        'first' => $v1['name'],
                                        'second' => $v2['name'],
                                        'third' => $v3['name'],
                                        'forth' => $v4['name'],
                                        'paly_intro' => $v4['paly_intro'],
                                    ];
                                }


                            } else {
                                $data[] = [
                                    'id' => $v3['id'],
                                    'gid' => $v2['gid'],
                                    'first' => $v1['name'],
                                    'second' => $v2['name'],
                                    'third' => $v3['name'],
                                    'forth' => '-',
                                    'paly_intro' => $v3['paly_intro'],

                                ];
                            }


                        }
                    } else {
                        $data[] = [
                            'id' => $v2['id'],
                            'gid' => $v2['gid'],
                            'first' => $v1['name'],
                            'second' => $v2['name'],
                            'third' => '-',
                            'paly_intro' => $v2['paly_intro'],
                        ];
                    }

                }
            } else {
                $data[] = [
                    'id' => $v1['id'],
                    'gid' => $v1['id'],
                    'first' => $v1['name'],
                    'second' => '-',
                    'third' => '-',
                    'paly_intro' => $v1['paly_intro'],
                ];
            }
        }


//        @file_put_contents($file, json_encode($glist));
//        echo json_encode($glist, JSON_UNESCAPED_UNICODE);
//        echo json_encode($data, JSON_UNESCAPED_UNICODE);

        return $data;
    } /* }}} */

    public function update_game_tips_data($id,$data)
    {
        try {

            $this->db_public->where('id', $id);
            $this->db_public->update('gc_games_types', $data);

            $result = [
                'code' => 200,
                'msg' => 'ok',
            ];
            echo json_encode($result, JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            echo json_encode($e->getMessage(), JSON_UNESCAPED_UNICODE);
            exit();
        }
    }



}
