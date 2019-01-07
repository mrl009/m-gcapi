<?php
/**
 * @brief 长龙助手
 * Created by PhpStorm.
 * Date: 2018/11/11
 * Time: 上午9:37
 */

defined('BASEPATH') OR exit('No direct script access allowed');

class Dragon extends MY_Controller
{
    protected $dragon_name = [
        100 => '大', 101 => '小', 102 => '单', 103 => '双',
        196 => '大', 197 => '小', 198 => '单', 199 => '双',
        313 => '大', 314 => '小', 315 => '单', 316 => '双',
    ];
    protected $ball_name = [
        'd1' => '第一', 'd2' => '第二', 'd3' => '第三', 'd4' => '第四', 'd5' => '第五', 'd6' => '第六', 'd7' => '第七', 'd8' => '第八', 'd9' => '第九', 'd10' => '第十', 'zh' => '和值', '12he' => '冠亚和', 'hz' => '和值'
    ];
    protected $dragon_gids = [];
    protected $dragon_play = [];
    protected $dragon_data = [];
    protected $ret_data = [];
    /**
     * @var object| Dragon_model
     */
    public $dragon;
    /**
     * @var object | Games_model
     */
    public $Games_model;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Dragon_model','dragon');
        $this->load->model('Games_model');
    }

    /**
     * 设置长龙彩种数据
     */
    protected function set_gids()
    {
        $this->dragon_gids = $this->dragon->getDragonCP();
        $gid = $this->G('gid');
        if ($gid) {
            if (!in_array($gid,$this->dragon_gids)) {
                $this->return_json(E_ARGS,'无效的GID');
            }
            $this->dragon_gids = [$gid];
        }
    }

    /**
     * 设置长龙玩法
     */
    protected function set_play()
    {
        $plays = $this->dragon->redis_hgetall('dragon_play');
        if (empty($plays)) {
            $plays = $this->cache_dragon_plays();
        }
        foreach ($this->dragon_gids as $gid) {
            $this->dragon_play[$gid] = json_decode($plays[$gid],true);
            $this->dragon_play[$gid]['user_rebate'] = $this->Games_model->user_rebate($this->user['id'], $gid);
        }
    }

    /**
     * 缓存长龙玩法到redis hash key=dragon_play
     * @return array
     */
    protected function cache_dragon_plays()
    {
        $this->load->model('Games_model');
        $gids = $this->dragon->getDragonCP();
        $rows = [];
        foreach ($gids as $gid) {
            $play = $this->Games_model->getplay($gid);
            $this->format_play($play['play'],$play['tmp']);
            $rows[$gid] = json_encode($play);
        }
        $this->dragon->redis_hmset('dragon_play',$rows);
        return $rows;
    }

    /**
     * 简化化长龙的玩法和球号，方便渲染页面
     * @param $plays array 单个彩种玩法
     * @param $key string 游戏的tmp
     */
    protected function format_play(&$plays,$key)
    {
        // 目前只有2级菜单，所以只循环2次
        foreach ($plays as $k => &$v) {
            if (!in_array($v['sname'],array_keys($this->dragon->games[$key]))) {
                unset($plays[$k]);
            } else {
                foreach ($v['play'] as $kk => &$vv) {
                    if (!in_array($vv['sname'],$this->dragon->games[$key][$v['sname']])) {
                        unset($v['play'][$kk]);
                    } else {
                        // 加入balls
                        $balls = $this->Games_model->getproducts($v['gid'],$vv['id']);
                        $balls = array_slice($balls[$vv['id']]['balls'],0,4);
                        $balls = array_make_key($balls,'code');
                        $vv['balls']['dx'] = array_slice($balls,0,2,true);
                        $vv['balls']['ds'] = array_slice($balls,2,2,true);
                    }
                }
                $v['play'] = array_values($v['play']);
            }
        }
        $plays = array_values($plays);
        // 只取最内层玩法
        $plays = $plays[0]['play'];
        $plays = array_make_key($plays,'sname');
    }

    public function format_plays_ret()
    {
        $games = [];
        $plays = [];
        foreach ($this->dragon_play as $gid => $info) {
            foreach ($info['play'] as $ball_name => $ball_info) {
                $key = $gid . '_' . $ball_name . '_';
                $dx = array_values($ball_info['balls']['dx']);
                $ds = array_values($ball_info['balls']['ds']);
                $plays[$key . 'dx']['name'] = $plays[$key . 'ds']['name'] = $ball_info['name'];
                $plays[$key . 'dx']['sname'] = $plays[$key . 'ds']['sname'] = $ball_info['sname'];
                $plays[$key . 'dx']['balls'] = $dx;
                $plays[$key . 'ds']['balls'] = $ds;
            }
            unset($info['play']);
            $games[$gid] = $info;
        }
        $this->ret_data['games'] = $games;
        $this->ret_data['plays'] = $plays;
    }

    public function plays()
    {
        $this->set_gids();
        $this->set_play();
        $this->format_plays_ret();
        $this->return_json(OK,$this->ret_data);
    }

    public function data()
    {
        $this->set_gids();
        $data = $this->dragon->set_dragon($this->dragon_gids)->get_dragon();
        $this->format_ret_dragon($data);
        sortArrByField($this->ret_data,'times',true);
        $this->return_json(OK,$this->ret_data);
    }

    /**
     * 格式化接口返回数据
     * @param array $dragon_data
     */
    protected function format_ret_dragon($dragon_data)
    {
        foreach ($dragon_data as $gid => $data) {
            foreach ($data as $ball_name  => $dxds) {
                if ($ball_name == 'kj_issue') {
                    continue;
                }
                // ball_name : ['hz','d1','d2',...'d10','zh','12he']
                foreach ($dxds as $k => $v) {
                    // $k : ['dx','ds']
                    if ($v['times'] >= 3) {
                        $key = $gid . '_' . $ball_name . '_' . $k;//eg:60_d3_dx
                        $data = [
                            'gid'=>$gid,
                            'name'=>$this->dragon_name[$v['code']],
                            'times'=>$v['times'],
                            'kj_issue'=>$data['kj_issue']
                        ];
                        /********************
                        {"59_d5_dx_101": {
                        "gid": 59,
                        "name": 小,
                        "times": 7,
                        "kj_issue": "921610"
                        },
                        "60_d3_dx_100": {
                        "gid": 60,
                        "name": 大,
                        "times": 7,
                        "kj_issue": "1811190129"
                        }}
                         ******************/
                        $this->ret_data[$key] = $data;
                    }
                }
            }
        }
    }




}
