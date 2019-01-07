<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Play extends MY_Controller
{
    private $type_gid_map = [];

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Games_model');
        $this->type_gid_map = TMP_TO_GID;
    }

    /*
     * 获取游戏玩法的赔率
     */
    public function bet_rate($type='s_k3')
    {
        if ( !array_key_exists($type,$this->type_gid_map) ) {
            $this->return_json(E_ARGS,'参数有误');
        }
        $this->load->model('Play_model');

        if (method_exists($this->Play_model,'get_'.$type.'_play_data')) {
            $retes = $this->Play_model->{'get_'.$type.'_play_data'}($this->type_gid_map[$type]);
        } else {
            $this->return_json(E_ARGS,'参数有误');
        }

        $res = $this->Games_model->getplayall();

        $user_rebate = $this->Games_model->user_rebate($this->user['id'],$this->type_gid_map[$type]);
        $data = [];
        $tids = [];
        if ($user_rebate['rebate'] === 0) {
            foreach ($retes as $item) {
                if (false !== $index = array_search($item['tid'],$tids)) {
                    $data[$index][] = $item['rate'];
                } else {
                    $tids[] = $item['tid'];
                    $index = array_search($item['tid'],$tids);
                    $data[$index][] = $item['rate'];
                }
            }
        } else {
            foreach ($retes as $item) {
                if (false !== $index = array_search($item['tid'],$tids)) {
                    $data[$index][] = sprintf('%.3f',round($item['rate']- $user_rebate['user_rebate']*($item['rate'] - $item['rate_min'])/$user_rebate['rebate'],3));
                } else {
                    $tids[] = $item['tid'];
                    $index = array_search($item['tid'],$tids);
                    $data[$index][] = sprintf('%.3f',round($item['rate']- $user_rebate['user_rebate']*($item['rate'] - $item['rate_min'])/$user_rebate['rebate'],3));
                }
            }
        }
        foreach ($data as &$v) {
            $v = implode(',',$v);
        }
        $this->return_json(OK,['rate'=>$data]);
    }

    public function products($type='s_k3')
    {
        if (!array_key_exists($type,$this->type_gid_map)) {
            $this->return_json(E_ARGS,'参数有误');
        }
        $rows = [];
        /* 加入站点cp判断 */
        $ids = $this->Games_model->get_gcset(['cp']);
        $ids = explode(',', $ids['cp']);
        foreach ($this->type_gid_map[$type] as $gid) {
            if (!in_array($gid, $ids)) {
                continue;
            }
            $ret = $this->Games_model->getproducts($gid);
            $rows[$gid]['balls_rate'] = $ret;
            $rows[$gid]['user_rebate'] = $this->Games_model->user_rebate($this->user['id'], $gid);
        }
        $this->return_json(OK, $rows);
    }

    public function play($type='s_k3'){
        if (!array_key_exists($type,$this->type_gid_map)) {
            $this->return_json(E_ARGS,'参数有误');
        }
        $rows = [];
        /* 加入站点cp判断 */
        $ids = $this->Games_model->get_gcset(['cp']);
        $ids = explode(',', $ids['cp']);
        foreach ($this->type_gid_map[$type] as $gid){
            if (!in_array($gid, $ids)) {
                continue;
            }
            $rows[$gid] = $this->Games_model->getplay($gid);
            /* 加入国私关联检测 */
            $rows[$gid] = ['aid' => 0] + $rows[$gid];
            $aid = ($gid < 50) ? $gid + 50 : (($gid < 100) ? $gid - 50 : 0);
            if ($aid && in_array($aid, $ids)) {
                $aid_info = $this->Games_model->info($aid);
                if ($aid_info && $aid_info['type'] == $rows[$gid]['type'] && $aid_info['ctg'] != $rows[$gid]['ctg']) {
                    $rows[$gid]['aid'] = $aid;
                }
            }
        }
        $this->return_json(OK, $rows);
    }

    /*
     * 购彩页接口 购彩页彩种数据
     */
    public function all_cp()
    {
        $gcset = $this->Games_model->get_gcset(['cp','lottery_auth']);
        $cp = array_unique(explode(',',$gcset['cp']));
        $lottery_auth = array_unique(explode(',',$gcset['lottery_auth']));
        if (! $this->Games_model->redisP_exists('games')) {
            $this->load->model('Comm_model','comm');
            $this->comm->cache_all_games();
        }
        $games = $this->Games_model->redisP_hmget('games',$cp);
        array_walk($games,function (&$item){
            $item = json_decode($item,true);
        });
        $tab = [1=>explode(',',GC),2=>explode(',',SC),4=>explode(',',SX)];
        $types = [1=>'gc',2=>'sc',4=>'sx'];
        $games = array_values($games);
        $data = [];
        foreach ($lottery_auth as $v) {
            foreach ($games as $k => $vv) {
                if (in_array($vv['id'],$tab[$v])) {
                    $vv['gid'] = $vv['id'];
                    if ($this->from_way == 3) {
                        // 来源为PC时,加上开奖信息
                    }
                    if ($vv['id'] > 1000) {
                        $data[$types[$v]][] = $vv;
                    } else {
                        if (!isset($data[$types[$v]][$vv['tmp']])) {
                            $data[$types[$v]][$vv['tmp']] = [];
                        }
                        $data[$types[$v]][$vv['tmp']][] = $vv;
                    }
                    if(!in_array($vv['id'],[3,4,24,25])){
                        unset($games[$k]);
                    }
                }
            }
        }
        $this->return_json(OK, $data);
    }
}
