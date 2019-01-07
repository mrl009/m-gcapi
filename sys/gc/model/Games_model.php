<?php
/**
 * @file models/Games_model.php
 * @brief 
 * 
 * Copyright (C) 2017 GC.COM
 * All rights reserved.
 * 
 * @package model
 * @author Langr <hua@langr.org> 2017/03/14 16:55
 * 
 * $Id$
 */

defined('BASEPATH') OR exit('No direct script access allowed');

class Games_model extends MY_Model 
{
    public function __construct() /* {{{ */
    {
        parent::__construct();
        // $this->db2 = $this->load->database('private', true);
        $this->select_db('public');
    } /* }}} */

    /**
     * @brief 取玩法的完整 sname 或中文名
     *      e.g. 
     *          sname(10)               sfssc
     *          sname(10, 0, true)      三分时时彩
     *          sname(10, 1388)         z3_z3zx_zx6
     *          sname(10, 1388, true)   中三_中三组选_组选6
     * @access  public
     * @param   int     $gid        game id
     * @param   int     $tid        玩法 id
     * @param   boolen  $show_time  显示名称/简称
     * @return  string
     */
    public function sname($gid = 0, $tid = 0, $show_name = false) /* {{{ */
    {
        if (!is_numeric($gid) || !is_numeric($tid)) {
            return false;
        }
        /* bet_name:: bet_sname:: */
        $key = $show_name ? 'b_n:'.$gid.':'.$tid : 'b_s:'.$gid.':'.$tid;
        $sname = $this->redis_get($key);
        if (!empty($sname)) {
            return $sname;
        }

        $sname = '';
        
        $this->select_db('public');
        $res = $this->db->select('name,sname')->get_where('games', array('id' => $gid));
        $ginfo = $res->row_array();

        if (empty($ginfo['sname'])) {
            return $sname;
        }
        if ($tid == 0 && !empty($ginfo['sname'])) {
            $sname = $show_name ? $ginfo['name'] : $ginfo['sname'];
            $this->redis_set($key, $sname);
            return $sname;
        }

        /* 向上找父级 */
        $res = $this->db->select('name,sname,pid')->get_where('games_types', array('id' => $tid, 'gid' => $gid));
        $pinfo = $res->row_array();
        if (isset($pinfo['pid']) && $pinfo['pid'] == 0) {
            $sname = $show_name ? $pinfo['name'] : $pinfo['sname'];
            $this->redis_set($key, $sname);
            return $sname;
        }
        $res = $this->db->select('name,sname,pid')->get_where('games_types', array('id' => $pinfo['pid'], 'gid' => $gid));
        $pinfo2 = $res->row_array();
        if (isset($pinfo2['pid']) && $pinfo2['pid'] == 0) {
            $sname = $show_name ? $pinfo2['name'].'_'.$pinfo['name'] : $pinfo2['sname'].'_'.$pinfo['sname'];
            $this->redis_set($key, $sname);
            return $sname;
        }
        $res = $this->db->select('name,sname,pid')->get_where('games_types', array('id' => $pinfo2['pid'], 'gid' => $gid));
        $pinfo3 = $res->row_array();
        if (isset($pinfo3['pid']) && $pinfo3['pid'] == 0) {
            $sname = $show_name ? $pinfo3['name'].'_'.$pinfo2['name'].'_'.$pinfo['name']
                : $pinfo3['sname'].'_'.$pinfo2['sname'].'_'.$pinfo['sname'];
            $this->redis_set($key, $sname);
            return $sname;
        }

        return $sname;
    } /* }}} */

    /**
     * @brief
     * @access  public 
     * @param   mixed   $gid    游戏id/游戏sname
     * @return  游戏信息
     */
    public function info($gid = 1) /* {{{ */
    {
        $where = array('id' => $gid, 'status !=' => 2);
        if (!empty($gid) && !is_numeric($gid)) {
            $where = array('sname' => $gid, 'status !=' => 2);
        }
        $this->select_db('public');
        $query = $this->db->select('id,name,sname,ctg,type,tname,max_money_play,max_money_stake,img,game_intro,hot,show,wh_content,status')
                ->get_where('games', $where);
        return $query->row_array();
    } /* }}} */

    /**
     * @brief 获取游戏列表
     *      NOTE: 此函数有写文件缓存，
     *          如果后台其他地方有修改 gc_games 表，请删除此缓存文件: 
     *      Cache: APPPATH.'cache/games_list.json'
     * @access public 
     * @return 游戏列表
     */
    public function getlist() /* {{{ */
    {
        $list_key = 'games:list';
        $ret = $this->redisP_get($list_key);
        if (!empty($ret)) {
            return json_decode($ret, true);
        }
        
        $this->select_db('public');
        $query = $this->db->order_by('sort desc')->get_where('games', array('status !=' => 2));
        $res = $query->result_array();

        $this->redisP_set($list_key, json_encode($res));
        return $res;
    } /* }}} */

    /**
     * @brief 取指定游戏的玩法或菜单列表
     *      不指定或者指定无效的游戏id，则返回全部游戏菜单和玩法
     * @param int   $gid    游戏id
     * @access public 
     * @return 指定游戏的所有玩法和菜单
     */
    public function getplay($gid = 0) /* {{{ */
    {
        $play_key = 'games_play';
        $ret = $this->redisP_hget($play_key, $gid);
        if (!empty($ret)) {
            return json_decode($ret, true);
        }

        $this->select_db('public');
        $where = array('id' => $gid, 'status !=' => 2);
        $ginfo = $this->db->get_where('games', $where)->row_array();

        /* 支持三级菜单 */
        //for ($i = 0; $i < $c; $i++) {
            // 如果需要支持超过三级菜单，请打开下一行注释，并注释掉 for 里面其他代码
            // $ginfo['play'] = $this->getplay_n($ginfo['id']);
            $res = $this->db->select('id,name,sname,pid,is_type,gid,status')
                ->get_where('games_types', array('gid' => $ginfo['id'], 'pid' => 0, 'status !=' => 2));
            $ginfo['play'] = $res->result_array();

            $cp = count($ginfo['play']);
            for ($j = 0; $j < $cp; $j++) {
                $res = $this->db->select('id,name,sname,pid,is_type,gid,status')
                    ->get_where('games_types', array('pid' => $ginfo['play'][$j]['id'], 'status !=' => 2));
                $ginfo['play'][$j]['play'] = $res->result_array();

                $cp2 = count($ginfo['play'][$j]['play']);
                if ($cp2 == 0 ) {
                    unset($ginfo['play'][$j]['play']);
                }
                for ($k = 0; $k < $cp2; $k++) {
                    $res = $this->db->select('id,name,sname,pid,is_type,gid,status')
                        ->get_where('games_types', array('pid' => $ginfo['play'][$j]['play'][$k]['id'], 'status !=' => 2));
                    $ginfo['play'][$j]['play'][$k]['play'] = $res->result_array();
                    $cp3 = count($ginfo['play'][$j]['play'][$k]['play']);
                    if ($cp3 == 0 ) {
                        unset($ginfo['play'][$j]['play'][$k]['play']);
                    }
                }
            }
        //}
        $this->redisP_hset($play_key, $gid, json_encode($ginfo));
        return $ginfo;
    } /* }}} */

    /**
     * @brief 取指定游戏的玩法可下单的球号和赔率
     *      不指定或者指定无效的游戏id，则返回全部游戏菜单和玩法。
     *      Cache: APPPATH.'cache/games_play_[gid].json'
     *      可以考虑放到 Redis.
     *      NOTE: 快3 和值玩法动态赔率设置有所不同。
     * @param int   $gid    游戏id
     * @param int   $tid    游戏玩法id
     *      不指定tid时，以玩法id为key, 返回此游戏各玩法的球号，
     * @access public 
     * @return 指定游戏的玩法可下单球号赔率
     *  "109": {
     *      "rate": "950.000",
     *      "rate_min": "850.000",
     *      "rebate": "10.000",
     *      "balls": [
     *          {id: "401", gid: "2", cid: "106", tid: "108", pid: "0", name: "百位", code: "999", rate: "0.000", rate_min: "0.000", "rebate": "0.000", child: {}},
     *          {}
     *      ]
     *  }
     */
    public function getproducts($gid = 1, $tid = 0) /* {{{ */
    {
        $tmp = array();
        $play_key = 'games_play';
        $ret = $this->redis_hget($play_key, $gid);
        if (!empty($ret)) {
            $tmp = json_decode($ret, true);
            if ($tid > 0 && !empty($tmp[$tid])) {
                return array($tid => $tmp[$tid]);
            }
            return $tmp;
        }

        $this->select_db('private');
        $query = $this->db->select('id,gid,cid,tid,pid,name,code,rate,rate_min,rebate')
                ->get_where('games_products', array('gid' => $gid));
        $products = $query->result_array();
        $tmp = array();

        foreach ($products as $p) {
            /* 处理普通的玩法动态赔率 */
            if ($p['pid'] == G_NO_PID && $p['code'] == G_MAX_RATE) {
                $tmp[$p['tid']]['id'] = $p['id'];
                $tmp[$p['tid']]['rate'] = $p['rate'];               /* [最高]赔率 */
                $tmp[$p['tid']]['rate_min'] = $p['rate_min'];       /* 最低赔率 */
                $tmp[$p['tid']]['rebate'] = $p['rebate'];           /* [低赔率]返利 */
            } else {
                /* (产品)球号归入上级菜单 */
                $tmp[$p['tid']][$p['pid']][] = $p;
            }
        }

        $c = count($tmp);
        foreach ($tmp as $k => $v) {
            if (empty($v[G_NO_PID])) {
                continue;
            }
            $tmp[$k]['balls'] = $tmp[$k][G_NO_PID];
            unset($tmp[$k][G_NO_PID]);
            foreach ($tmp[$k]['balls'] as $k1 => $v1) {
                if (empty($tmp[$k][$v1['id']])) {
                    continue;
                }
                $tmp[$k]['balls'][$k1]['child'] = $tmp[$k][$v1['id']];
                unset($tmp[$k][$v1['id']]);
            }
        }

        $this->redis_hset($play_key, $gid, json_encode($tmp));
        if ($tid > 0 && !empty($tmp[$tid])) {
            return array($tid => $tmp[$tid]);
        }
        return $tmp;
    } /* }}} */

    /**
     * @brief 组织全部游戏的玩法或菜单列表为 json
     *      NOTE: 此函数有写文件缓存，
     *          如果后台其他地方有修改 gc_games_types 表，请删除此缓存文件: 
     *      Cache: APPPATH.'cache/games_play.json'
     * @access protected
     * @return 所有游戏的所有玩法和菜单
     */
    protected function getplayall() /* {{{ */
    {
        $play_key = 'games:play';
        $ret = $this->redisP_get($play_key);
        if (!empty($ret)) {
            return json_decode($ret, true);
        }

        $this->select_db('public');
        $glist = $this->db->get('games', null, null)->result_array();
        $c = count($glist);

        /* 支持三级菜单 */
        for ($i = 0; $i < $c; $i++) {
            // 如果需要支持超过三级菜单，请打开下一行注释，并注释掉 for 里面其他代码
            // $glist[$i]['play'] = $this->getplay_n($glist[$i]['id']);
            $res = $this->db->select('id,name,sname,pid,is_type,gid,status')
                ->get_where('games_types', array('gid' => $glist[$i]['id'], 'pid' => 0, 'status !=' => 2));
            $glist[$i]['play'] = $res->result_array();

            $cp = count($glist[$i]['play']);
            for ($j = 0; $j < $cp; $j++) {
                $res = $this->db->select('id,name,sname,pid,is_type,gid,status')
                    ->get_where('games_types', array('pid' => $glist[$i]['play'][$j]['id'], 'status !=' => 2));
                $glist[$i]['play'][$j]['play'] = $res->result_array();

                $cp2 = count($glist[$i]['play'][$j]['play']);
                if ($cp2 == 0 ) {
                    unset($glist[$i]['play'][$j]['play']);
                }
                for ($k = 0; $k < $cp2; $k++) {
                    $res = $this->db->select('id,name,sname,pid,is_type,gid,status')
                        ->get_where('games_types', array('pid' => $glist[$i]['play'][$j]['play'][$k]['id'], 'status !=' => 2));
                    $glist[$i]['play'][$j]['play'][$k]['play'] = $res->result_array();
                    $cp3 = count($glist[$i]['play'][$j]['play'][$k]['play']);
                    if ($cp3 == 0 ) {
                        unset($glist[$i]['play'][$j]['play'][$k]['play']);
                    }
                }
            }
        }
        $this->redisP_set($play_key, json_encode($glist));
        return $glist;
    } /* }}} */

    /**
     * @brief 某个游戏的玩法或菜单列表为json
     * @access protected
     * @return 某个游戏的所有玩法和菜单
     */
    protected function getplay_n($gid = 1, $pid = 0) /* {{{ */
    {
        $_tmp = array();
        /* 多级菜单 */
        $this->select_db('public');
        $res = $this->db->select('id,name,sname,pid,is_type,gid')
            ->get_where('games_types', array('gid' => $gid, 'pid' => $pid, 'status !=' => 2));
        $_tmp = $res->result_array();
        $cp = count($_tmp);
        if ($cp == 0) {
            return null;
        }

        for ($j = 0; $j < $cp; $j++) {
            $res = $this->getplay_n($gid, $_tmp[$j]['id']);
            if ($res == null) {
                continue;
            }
            $_tmp[$j]['play'] = $res;
        }
        
        return $_tmp;
    } /* }}} */

    /**
     * @brief [无线代理]获取指定用户指定游戏的返水比率
     *      用户计算赔率的返水比率 = 系统设置最高返水比率 - 上级代理给此用户设置的返水比率剩余价值
     * @access public
     * @return false / ['uid','gid','rebate','user_rebate']
     */
    public function user_rebate($uid = 0, $gid = 1) /* {{{ */
    {
        $ret = ['uid'=>$uid, 'gid'=>$gid, 'rebate'=>0, 'user_rebate'=>0];
        /* 检测游戏是否支持代理返水 */
        if (empty(AGENT_GAMES[$gid])) {
            $ret['gid'] = 0;
            return $ret;
        }
        $games_type = AGENT_GAMES[$gid];
        /* 获取用户的代理线 */
        $key = TOKEN_CODE_AGENT.':line:'.$uid;
        $agent_list = $this->redis_get($key);
        //$agent_list = '{"20521":{"ssc":8,"k3":8,"11x5":8,"fc3d":8,"pl3":8,"bjkl8":8,"pk10":8,"lhc":8},"20526":{"ssc":7,"k3":6.5,"11x5":7,"fc3d":6,"pl3":6,"bjkl8":7,"pk10":5.6,"lhc":6.2},"20527":{"ssc":3,"k3":5.5,"11x5":6,"fc3d":4,"pl3":5,"bjkl8":6,"pk10":5,"lhc":5.2}}';
        $agent_list = json_decode($agent_list, true);
        if (!$agent_list || empty($agent_list[$uid])) {
            $ret['uid'] = 0;
            return $ret;
        }
        //ksort($agent_list);
        $user_top = reset($agent_list);
        $ret['rebate'] = $user_top[$games_type];
        $ret['user_rebate'] = round($user_top[$games_type] - $agent_list[$uid][$games_type], 1);

        return $ret;
    } /* }}} */

    /**
     * @brief 检测下注球号赔率
     *      需要处理特别玩法赔率：
     *          六和彩：合肖(球数不同赔率不同) 中 不中，过关(几个球号几个赔率)，连肖连尾(不同赔率取小赔率)，[连码(2赔率) 三中二 二中特]
     *          时时彩：五星组合，后四前四组合(多赔率)，[龙虎和(分注)]
     *          [快3：和值(分注)]
     *
     *      赔率与返水特别说明：
     *      在同一玩法中，同一注单组合，各球或组合注 有不同赔率时，需要分开为多个注单；赔率相同的球可任意组合下注单。
     *
     *      六合彩
     *      合肖 中，不中，服务器设置时是多赔率，但下注时只需返回与选中的球数对应的一个赔率即可。
     *      正码 过关，下注时选中的各球，赔率按球位置顺序依次以逗号隔开，一起传递过来。
     *      连肖连尾(每单只传一个赔率，不同赔率取小赔率)。
     *      [连码 三中二，二中特，服务器设置是两赔率，下注时直接返回此两赔率。]
     *      正码过关：$bet = "[\"1570329155205234\",1,\"100||104|104||\",2,1,2,\"1.97,,1.97,1.97,,\",0]";
     *
     *      时时彩
     *      五星组合，后四组合，前四组合 多赔率和返水，以逗号分隔，按从大到小依次排列传递过来。
     *      [龙虎和 各球赔率不同，一注只下一个球，不要组合。]
     *
     *      快3
     *      [和值 各球赔率不同，一注只下一个球，不要组合。]
     * @access public
     * @param $gid      游戏id
     * @param $tid      玩法id
     * @param $pids     下注球ids
     * @param $rates    赔率s
     * @param $rebate   返水比率
     * @param $uid      uid [无线代理检测返水用]
     * @return true/false
     */
    public function chk_rate($gname, $fn, $gid, $tid, $pids, $contents, $rates, $rebate, $dbn = '', $uid = 0) /* {{{ */
    {
        $user_rebate = $this->user_rebate($uid, $gid);
        if ($user_rebate['user_rebate'] != $rebate) {
            wlog(APPPATH.'logs/bets/'.$dbn.'_'.$gname.'_'.date('Ym').'.log', 'rebate error:'.$rebate.':'.json_encode($user_rebate));
            return false;
        }
        /* 取球号或玩法赔率 */
        $this->select_db('private');
        $res = $this->db->select('id,rate,rate_min,rebate')->get_where('games_products', array('tid' => $tid, 'code' => 888));
        $tmp = $res->result_array();
        /* 如果没有设置通用赔率，则取各球单个球的赔率 */
        if (empty($tmp)) {
            /* |1|||4,5 => 1,4,5 */
            $pids = str_replace('|', ',', $pids);
            $pids = explode(',', $pids);
            $pids = array_filter($pids);
            $pids = implode(',', $pids);
            $pids = empty($pids) ? 0 : $pids;
            $res = $this->db->select('id,code,rate,rate_min,rebate')->get_where('games_products', 'id in ('.$pids.") and tid='".$tid."'");
            $tmp = $res->result_array();
            if (empty($tmp) || (count($tmp) == 1 && $tmp[0]['code'] != $contents)) {
                @wlog(APPPATH.'logs/bets/'.$dbn.'_'.$gname.'_'.date('Ym').'.log', 'rate error1:'.$pids.':'.$contents.':'.$rates);
                return false;
            }
        }
        $tmp[0]['rebate'] = $user_rebate['rebate'];
        /* 检测返水 */
        if ($rebate > $tmp[0]['rebate']) {
            @wlog(APPPATH.'logs/bets/'.$dbn.'_'.$gname.'_'.date('Ym').'.log', 'rebate error:'.$pids.':'.$tmp[0]['rebate'].':'.$rebate);
            return false;
        }
        /* 赔率不可调(赔率不可调,大多为六合彩，多个球多个赔率)无需要返水 */
        if ($tmp[0]['rate_min'] == '0') {
            /* 处理特别赔率 lhc hx_z,hx_bz(1～10球1个赔率),zmgg(2~6个球，一球一赔率) */
            if ($fn == 'hx_z' || $fn == 'hx_bz') {
                $r = explode(',', $pids);
                $c = count($r);
                $r_s = explode(',', $tmp[0]['rate']);
                /* 设置赔率小于实际下注赔率，则出错 */
                if ($r_s[$c - 1] != $rates) {
                    @wlog(APPPATH.'logs/bets/'.$dbn.'_'.$gname.'_'.date('Ym').'.log', 'rate error2:'.$fn.':'.$r_s[$c - 1].':'.$rates);
                    return false;
                }
                return true;
            } elseif ($fn == 'zmgg') {
                $r = explode(',', $rates);
                $r = array_values(array_filter($r));
                $c = count($tmp);
                for ($i = 0; $i < $c; $i++) {
                    if ($tmp[$i]['rate'] != $r[$i]) {
                        @wlog(APPPATH.'logs/bets/'.$dbn.'_'.$gname.'_'.date('Ym').'.log', 'rate error3:'.$fn.':'.$tmp[$i]['rate'].':'.$rates.':'.$i);
                        return false;
                    }
                }
                return true;
            } elseif (in_array($fn, array('lx_2xl', 'lx_3xl', 'lx_4xl', 'lx_5xl', 'lw_2wp', 'lw_3wp', 'lw_4wp', 'lw_5wp'))) {
                /* 连肖连尾 组合注时连肖取最小赔率为下注赔率 连尾也取最小赔率 */
                $_min_rate = $tmp[0]['rate'];
                $c = count($tmp);
                for ($i = 1; $i < $c; $i++) {
                    if ($_min_rate > $tmp[$i]['rate']) {
                        $_min_rate = $tmp[$i]['rate'];
                    }
                }
                if ($_min_rate != $rates) {
                    @wlog(APPPATH.'logs/bets/'.$dbn.'_'.$gname.'_'.date('Ym').'.log', 'rate error4:'.$fn.':'.$_min_rate.':'.$rates);
                    return false;
                }
                return true;
            }
            /* 设置赔率小于实际下注赔率，则出错 */
            if ($tmp[0]['rate'] != $rates) {
                @wlog(APPPATH.'logs/bets/'.$dbn.'_'.$gname.'_'.date('Ym').'.log', 'rate error5:'.$fn.':'.$tmp[0]['rate'].':'.$rates);
                return false;
            }
        } else {
            /* 赔率可调，需要返水时，核对实际赔率与返水 */
            /* 处理特别赔率 lhc hx_z,hx_bz(1～10球1个赔率),zmgg(2~6个球，一球一赔率) lm_3z2,lm_2zt,连码(三中二，二中特) */
            if ($gname == 'jslhc') {
                wlog(APPPATH.'logs/bets/'.$dbn.'_'.$gname.'_'.date('Ym').'.log', 'rate error11:'.$fn.':'.json_encode($tmp).':'.$rates.':'.$rebate);
            }
            if (!is_numeric($tmp[0]['rate'])) {
                wlog(APPPATH.'logs/bets/'.$dbn.'_'.$gname.'_'.date('Ym').'.log', 'rate error21:'.$fn.':'.json_encode($tmp).':'.$rates);
            }
            if ($fn == 'hx_z' || $fn == 'hx_bz') {
                /* [有设置返水时需要计算实际赔率：找到当前最大赔率和最小赔率，并计算出实际赔率]. */
                /* 前端：1.838,后端："rate":"11.61,5.805,3.87,2.903,2.322,1.935,1.659,1.451,1.29,1.161,1.055","rate_min":"10.449,5.2245,3.483,2.6127,2.0898,1.7415,1.4931,1.3059,1.161,1.0449,0.9495" */
                $r = explode(',', $pids);
                $c = count($r);
                $r_s = explode(',', $tmp[0]['rate']);
                $r_m = explode(',', $tmp[0]['rate_min']);
                $n = ($r_s[$c - 1] - $r_m[$c - 1]) / $tmp[0]['rebate'];    /* 多赔率，(最大赔率-最小赔率)/最大返水=1%返水时减少的赔率 */
                $v = $rebate * $n;                                      /* 用户设置返水率实际应减少的赔率 */
                $rate_real = round($r_s[$c - 1] - $v, 3);               /* 用户设置反水率实际赔率 */
                /* 设置赔率小于实际下注赔率，则出错 */
                if ($rate_real != $rates) {
                    @wlog(APPPATH.'logs/bets/'.$dbn.'_'.$gname.'_'.date('Ym').'.log', $n.'*'.$rebate.'='.$v.'rate error12:'.$fn.':'.$rate_real.':'.$rates);
                    return false;
                }
                return true;
            } elseif ($fn == 'lm_3z2' || $fn == 'lm_2zt') {
                /* 前端传入赔率：48.45,26.45，数据库赔率：{"rate":"51,31","rate_min":"45.9,21.9"} */
                $r_r = explode(',', $rates);                /* 用户设置赔率 */
                $r_s = explode(',', $tmp[0]['rate']);       /* 系统设置最大赔率 */
                $r_m = explode(',', $tmp[0]['rate_min']);   /* 系统设置最小赔率 */
                $c_s = count($r_s);
                for ($i = 0; $i < $c_s; $i++) {
                    $n = ($r_s[$i] - $r_m[$i]) / $tmp[0]['rebate'];    /* 多赔率，(最大赔率-最小赔率)/最大返水=1%返水时减少的赔率 */
                    $v = $rebate * $n;                                      /* 用户设置返水率实际应减少的赔率 */
                    $rate_real = round($r_s[$i] - $v, 3);                   /* 用户设置反水率实际赔率 */
                    if ($rate_real < $r_r[$i]) {
                        @wlog(APPPATH.'logs/bets/'.$dbn.'_'.$gname.'_'.date('Ym').'.log', $n.'*'.$rebate.'='.$v.'rate error16:'.$fn.':'.$rate_real.':'.$rates.':'.$i);
                        return false;
                    }
                }
                return true;
            } elseif ($fn == 'zmgg') {
                /* 前端传入赔率：1.931,1.931,2.793,1.931 数据库赔率：[{"rate":"1.97","rate_min":"1.773"},{"rate":"1.97","rate_min":"1.773"}] */
                $r = explode(',', $rates);
                $r = array_values(array_filter($r));
                $c = count($tmp);
                for ($i = 0; $i < $c; $i++) {
                    $n = ($tmp[$i]['rate'] - $tmp[$i]['rate_min']) / $tmp[0]['rebate'];    /* 多赔率，(最大赔率-最小赔率)/最大返水=1%返水时减少的赔率 */
                    $v = $rebate * $n;                                      /* 用户设置返水率实际应减少的赔率 */
                    $rate_real = round($tmp[$i]['rate'] - $v, 3);           /* 用户设置反水率实际赔率 */
                    if ($rate_real != $r[$i]) {
                        @wlog(APPPATH.'logs/bets/'.$dbn.'_'.$gname.'_'.date('Ym').'.log', $n.'*'.$rebate.'='.$v.'rate error13:'.$fn.':'.$rate_real.':'.$rates.':'.$i);
                        return false;
                    }
                }
                return true;
            } elseif (in_array($fn, array('lx_2xl', 'lx_3xl', 'lx_4xl', 'lx_5xl', 'lw_2wp', 'lw_3wp', 'lw_4wp', 'lw_5wp'))) {
                /* 连肖连尾 组合注时连肖取最小赔率为下注赔率 连尾也取最小赔率 [有设置返水时需要计算实际赔率] */
                /* 前端传入赔率：3.021，数据库赔率：[{"rate":"3.18","rate_min":"2.862"},{"rate":"3.18","rate_min":"2.862"}] */
                $_min_rate = $tmp[0]['rate'];
                $c = count($tmp);
                for ($i = 0; $i < $c; $i++) {
                    $n = ($tmp[$i]['rate'] - $tmp[$i]['rate_min']) / $tmp[0]['rebate'];    /* 多赔率，(最大赔率-最小赔率)/最大返水=1%返水时减少的赔率 */
                    $v = $rebate * $n;                                      /* 用户设置返水率实际应减少的赔率 */
                    $rate_real = round($tmp[$i]['rate'] - $v, 3);           /* 用户设置反水率实际赔率 */
                    if ($_min_rate > $rate_real) {
                        $_min_rate = $rate_real;
                    }
                }
                if ($_min_rate != $rates) {
                    @wlog(APPPATH.'logs/bets/'.$dbn.'_'.$gname.'_'.date('Ym').'.log', $n.'*'.$rebate.'='.$v.'rate error14:'.$fn.':'.$_min_rate.':'.$rates);
                    return false;
                }
                return true;
            }
            /* 处理特别赔率 ssc 5x_5xzhx_zh,q4_q4zhx_zh,h4_h4zhx_zh (5[4]球组5[4]注5[4]个赔率[逗号分隔]) */
            if (($fn == '5x_5xzhx_zh' || $fn == 'q4_q4zhx_zh' || $fn == 'h4_h4zhx_zh') /*&& $rebate > 0*/) {
                /* rates: 85000,8500,850,85,8.5 */
                $r_r = explode(',', $rates);                /* 用户设置赔率 */
                $r_s = explode(',', $tmp[0]['rate']);       /* 系统设置最大赔率 */
                $r_m = explode(',', $tmp[0]['rate_min']);   /* 系统设置最小赔率 */
                $c_s = count($r_s);
                for ($i = 0; $i < $c_s; $i++) {
                    $m = $r_s[$i] - $r_m[$i];               /* 多赔率，(最大赔率-最小赔率)/最大返水=1%返水时减少的赔率 */
                    $n = $m / $tmp[0]['rebate'];
                    $v = $rebate * $n;                      /* 用户设置返水率实际应减少的赔率 */
                    $rate_real = round($r_s[$i] - $v, 3);             /* 用户设置反水率实际赔率 */
                    if ($rate_real < $r_r[$i]) {
                        @wlog(APPPATH.'logs/bets/'.$dbn.'_'.$gname.'_'.date('Ym').'.log', 'rate error16:'.$fn.':'.$rate_real.':'.$rates.':'.$i);
                        return false;
                    }
                }
                return true;
            }
            if (!is_numeric($tmp[0]['rate'])) {
                wlog(APPPATH.'logs/bets/'.$dbn.'_'.$gname.'_'.date('Ym').'.log', 'rate error31:'.$fn.':'.json_encode($tmp).':'.$rates);
                return false;
            }
            if ($rebate > 0) {
                /* 设置返水，(设置最大赔率-最小赔率)/最大返水 = 每1%点返水比率 实际减少的赔率 */
                $m = $tmp[0]['rate'] - $tmp[0]['rate_min'];
                /* 1% 返水时减少的赔率 */
                $n = $m / $tmp[0]['rebate'];
                /* 用户设置返水率实际应减少的赔率 */
                $v = $rebate * $n;
                /* 用户设置反水率实际赔率 */
                $rate_real = round($tmp[0]['rate'] - $v, 3);
                /* 需要返水，计算出的实际赔率小于实际下注赔率，则出错 */
                if ($rate_real < $rates) {
                    @wlog(APPPATH.'logs/bets/'.$dbn.'_'.$gname.'_'.date('Ym').'.log', 'rate error7:'.$fn.':'.$rate_real.':'.$rates);
                    return false;
                }
            } elseif ($tmp[0]['rate'] != $rates) {
                /* 没设置返水，设置赔率不等于实际下注赔率，则出错 */
                @wlog(APPPATH.'logs/bets/'.$dbn.'_'.$gname.'_'.date('Ym').'.log', 'rate error8:'.$fn.':'.$tmp[0]['rate'].':'.$rates);
                return false;
            }
        }

        return true;
    } /* }}} */

    /**
     * @brief 设置下注球号赔率
     *      需要处理特别玩法赔率：
     *          六和彩：合肖(球数不同赔率不同) 中 不中，过关(几个球号几个赔率)，连肖连尾(不同赔率取小赔率)，[连码(2赔率) 三中二 二中特]
     *          时时彩：五星组合，后四前四组合(多赔率)，[龙虎和(分注)]
     *          [快3：和值(分注)]
     *
     *      赔率与返水特别说明：
     *      在同一玩法中，同一注单组合，各球或组合注 有不同赔率时，需要分开为多个注单；赔率相同的球可任意组合下注单。
     *
     *      六合彩
     *      合肖 中，不中，服务器设置时是多赔率，但下注时只需返回与选中的球数对应的一个赔率即可。
     *      正码 过关，下注时选中的各球，赔率按球位置顺序依次以逗号隔开，一起传递过来。
     *      连肖连尾(每单只传一个赔率，不同赔率取小赔率)。
     *      [连码 三中二，二中特，服务器设置是两赔率，下注时直接返回此两赔率。]
     *      正码过关：$bet = "[\"1570329155205234\",1,\"100||104|104||\",2,1,2,\"1.97,,1.97,1.97,,\",0]";
     *
     *      时时彩
     *      五星组合，后四组合，前四组合 多赔率和返水，以逗号分隔，按从大到小依次排列传递过来。
     *      [龙虎和 各球赔率不同，一注只下一个球，不要组合。]
     *
     *      快3
     *      [和值 各球赔率不同，一注只下一个球，不要组合。]
     * @access public
     * @param $gid      游戏id
     * @param $tid      玩法id
     * @param $pids     下注球ids
     * @param $uid      uid [无线代理检测返水用]
     * @return true/false
     */
    public function set_rate($gname, $fn, $gid, $tid, $pids, $contents, &$bet, $dbn = '', $uid = 0) /* {{{ */
    {
        $user_rebate = $this->user_rebate($uid, $gid);
        /* 取球号或玩法赔率 */
        $this->select_db('private');
        $res = $this->db->select('id,rate,rate_min,rebate')->get_where('games_products', array('tid' => $tid, 'code' => 888));
        $tmp = $res->result_array();
        /* 如果没有设置通用赔率，则取各球单个球的赔率 */
        if (empty($tmp)) {
            /* |1|||4,5 => 1,4,5 */
            $pids = str_replace('|', ',', $pids);
            $pids = explode(',', $pids);
            $pids = array_filter($pids);
            $pids = implode(',', $pids);
            $pids = empty($pids) ? 0 : $pids;
            $res = $this->db->select('id,code,rate,rate_min,rebate')->get_where('games_products', 'id in ('.$pids.") and tid='".$tid."'");
            $tmp = $res->result_array();
            if (empty($tmp) || (count($tmp) == 1 && $tmp[0]['code'] != $contents)) {
                @wlog(APPPATH.'logs/bets/'.$dbn.'_'.$gname.'_'.date('Ym').'.log', 'rate error1:'.$pids.':'.$contents);
                return false;
            }
        }
        $tmp[0]['rebate'] = $user_rebate['rebate'];
        $bet['rebate'] = $user_rebate['user_rebate'];
        /* 赔率不可调(赔率不可调,大多为六合彩，多个球多个赔率)无需要返水 */
        if ($tmp[0]['rate_min'] == '0') {
            /* 处理特别赔率 lhc hx_z,hx_bz(1～10球1个赔率),zmgg(2~6个球，一球一赔率) */
            if ($fn == 'hx_z' || $fn == 'hx_bz') {
                $r = explode(',', $pids);
                $c = count($r);
                $r_s = explode(',', $tmp[0]['rate']);
                $bet['rate'] = $r_s[$c - 1];
                return true;
            } elseif ($fn == 'zmgg') {
                $c = count($tmp);
                for ($i = 0; $i < $c; $i++) {
                    $bet['rate'][$i] = $tmp[$i]['rate'];
                }
                $bet['rate'] = implode(',',$bet['rate']);
                return true;
            } elseif (in_array($fn, array('lx_2xl', 'lx_3xl', 'lx_4xl', 'lx_5xl', 'lw_2wp', 'lw_3wp', 'lw_4wp', 'lw_5wp'))) {
                /* 连肖连尾 组合注时连肖取最小赔率为下注赔率 连尾也取最小赔率 */
                $_min_rate = $tmp[0]['rate'];
                $c = count($tmp);
                for ($i = 1; $i < $c; $i++) {
                    if ($_min_rate > $tmp[$i]['rate']) {
                        $_min_rate = $tmp[$i]['rate'];
                    }
                }
                $bet['rate'] = $_min_rate;
                return true;
            }
            /* 设置赔率小于实际下注赔率，则出错 */
            $bet['rate'] = $tmp[0]['rate'];
        } else {
            /* 赔率可调，需要返水时，核对实际赔率与返水 */
            /* 处理特别赔率 lhc hx_z,hx_bz(1～10球1个赔率),zmgg(2~6个球，一球一赔率) lm_3z2,lm_2zt,连码(三中二，二中特) */
            if ($gname == 'jslhc') {
                wlog(APPPATH.'logs/bets/'.$dbn.'_'.$gname.'_'.date('Ym').'.log', 'rate error11:'.$fn.':'.json_encode($tmp));
                return false;
            }
            if (!is_numeric($tmp[0]['rate'])) {
                wlog(APPPATH.'logs/bets/'.$dbn.'_'.$gname.'_'.date('Ym').'.log', 'rate error21:'.$fn.':'.json_encode($tmp).':'.$tmp[0]['rate']);
                return false;
            }
            if ($fn == 'hx_z' || $fn == 'hx_bz') {
                /* [有设置返水时需要计算实际赔率：找到当前最大赔率和最小赔率，并计算出实际赔率]. */
                /* 前端：1.838,后端："rate":"11.61,5.805,3.87,2.903,2.322,1.935,1.659,1.451,1.29,1.161,1.055","rate_min":"10.449,5.2245,3.483,2.6127,2.0898,1.7415,1.4931,1.3059,1.161,1.0449,0.9495" */
                $r = explode(',', $pids);
                $c = count($r);
                $r_s = explode(',', $tmp[0]['rate']);
                $r_m = explode(',', $tmp[0]['rate_min']);
                $n = ($r_s[$c - 1] - $r_m[$c - 1]) / $tmp[0]['rebate'];    /* 多赔率，(最大赔率-最小赔率)/最大返水=1%返水时减少的赔率 */
                $v = $bet['rebate'] * $n;                                      /* 用户设置返水率实际应减少的赔率 */
                $rate_real = round($r_s[$c - 1] - $v, 3);               /* 用户设置反水率实际赔率 */
                $bet['rate'] = $rate_real;
                return true;
            } elseif ($fn == 'lm_3z2' || $fn == 'lm_2zt') {
                /* 前端传入赔率：48.45,26.45，数据库赔率：{"rate":"51,31","rate_min":"45.9,21.9"} */
                $r_r = [];                /* 用户设置赔率 */
                $r_s = explode(',', $tmp[0]['rate']);       /* 系统设置最大赔率 */
                $r_m = explode(',', $tmp[0]['rate_min']);   /* 系统设置最小赔率 */
                $c_s = count($r_s);
                for ($i = 0; $i < $c_s; $i++) {
                    $n = ($r_s[$i] - $r_m[$i]) / $tmp[0]['rebate'];    /* 多赔率，(最大赔率-最小赔率)/最大返水=1%返水时减少的赔率 */
                    $v = $bet['rebate'] * $n;                                      /* 用户设置返水率实际应减少的赔率 */
                    $rate_real = round($r_s[$i] - $v, 3);                   /* 用户设置反水率实际赔率 */
                    $r_r[$i] = $rate_real;
                }
                $bet['rate'] = implode(',',$r_r);
                return true;
            } elseif ($fn == 'zmgg') {
                /* 前端传入赔率：1.931,1.931,2.793,1.931 数据库赔率：[{"rate":"1.97","rate_min":"1.773"},{"rate":"1.97","rate_min":"1.773"}] */
                $r = [];
                $c = count($tmp);
                for ($i = 0; $i < $c; $i++) {
                    $n = ($tmp[$i]['rate'] - $tmp[$i]['rate_min']) / $tmp[0]['rebate'];    /* 多赔率，(最大赔率-最小赔率)/最大返水=1%返水时减少的赔率 */
                    $v = $bet['rebate'] * $n;                                      /* 用户设置返水率实际应减少的赔率 */
                    $rate_real = round($tmp[$i]['rate'] - $v, 3);           /* 用户设置反水率实际赔率 */
                    $r[$i] = $rate_real;
                }
                $bet['rate'] = implode(',',$r);
                return true;
            } elseif (in_array($fn, array('lx_2xl', 'lx_3xl', 'lx_4xl', 'lx_5xl', 'lw_2wp', 'lw_3wp', 'lw_4wp', 'lw_5wp'))) {
                /* 连肖连尾 组合注时连肖取最小赔率为下注赔率 连尾也取最小赔率 [有设置返水时需要计算实际赔率] */
                /* 前端传入赔率：3.021，数据库赔率：[{"rate":"3.18","rate_min":"2.862"},{"rate":"3.18","rate_min":"2.862"}] */
                $_min_rate = $tmp[0]['rate'];
                $c = count($tmp);
                for ($i = 0; $i < $c; $i++) {
                    $n = ($tmp[$i]['rate'] - $tmp[$i]['rate_min']) / $tmp[0]['rebate'];    /* 多赔率，(最大赔率-最小赔率)/最大返水=1%返水时减少的赔率 */
                    $v = $bet['rebate'] * $n;                                      /* 用户设置返水率实际应减少的赔率 */
                    $rate_real = round($tmp[$i]['rate'] - $v, 3);           /* 用户设置反水率实际赔率 */
                    if ($_min_rate > $rate_real) {
                        $_min_rate = $rate_real;
                    }
                }
                $bet['rate'] = $_min_rate;
                return true;
            }
            /* 处理特别赔率 ssc 5x_5xzhx_zh,q4_q4zhx_zh,h4_h4zhx_zh (5[4]球组5[4]注5[4]个赔率[逗号分隔]) */
            if (($fn == '5x_5xzhx_zh' || $fn == 'q4_q4zhx_zh' || $fn == 'h4_h4zhx_zh') /*&& $rebate > 0*/) {
                /* rates: 85000,8500,850,85,8.5 */
                $r_r = [];                /* 用户设置赔率 */
                $r_s = explode(',', $tmp[0]['rate']);       /* 系统设置最大赔率 */
                $r_m = explode(',', $tmp[0]['rate_min']);   /* 系统设置最小赔率 */
                $c_s = count($r_s);
                for ($i = 0; $i < $c_s; $i++) {
                    $m = $r_s[$i] - $r_m[$i];               /* 多赔率，(最大赔率-最小赔率)/最大返水=1%返水时减少的赔率 */
                    $n = $m / $tmp[0]['rebate'];
                    $v = $bet['rebate'] * $n;                      /* 用户设置返水率实际应减少的赔率 */
                    $rate_real = round($r_s[$i] - $v, 3);             /* 用户设置反水率实际赔率 */
                    $r_r[$i] = $rate_real;
                }
                $bet['rate'] = implode(',',$r_r);
                return true;
            }
            if (!is_numeric($tmp[0]['rate'])) {
                wlog(APPPATH.'logs/bets/'.$dbn.'_'.$gname.'_'.date('Ym').'.log', 'rate error31:'.$fn.':'.json_encode($tmp).':'.$tmp[0]['rate']);
                return false;
            }
            if ($bet['rebate'] > 0) {
                /* 设置返水，(设置最大赔率-最小赔率)/最大返水 = 每1%点返水比率 实际减少的赔率 */
                $m = $tmp[0]['rate'] - $tmp[0]['rate_min'];
                /* 1% 返水时减少的赔率 */
                $n = $m / $tmp[0]['rebate'];
                /* 用户设置返水率实际应减少的赔率 */
                $v = $bet['rebate'] * $n;
                /* 用户设置反水率实际赔率 */
                $rate_real = round($tmp[0]['rate'] - $v, 3);
                $bet['rate'] = $rate_real;
            } else {
                $bet['rate'] = $tmp[0]['rate'];
            }
        }

        return true;
    } /* }}} */

    /**
     * @brief 六合彩生肖色波与球号对应
     *      NOTE: 生肖只与下注日期有关，与开奖日期无关。
     *      波色与球号对应
     * @access  public
     * @param   array   $lottery    开奖号码 
     * @return  array 
     *      如果传入开奖号数组，则返回对应开奖号的生肖和色波，
     *      如果没传开奖号，则返回生肖和色波对应的所有球号列表
     */
    public function lhc_sx_balls($lottery = null) /* {{{ */
    {
        include_once(dirname(__FILE__).'/../common/Lunar.class.php');

        $base = array(1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20,
                    21, 22, 23, 24, 25, 26, 27, 28, 29, 30, 31, 32, 33, 34, 35, 36, 37, 38, 39, 40,
                    41, 42, 43, 44, 45, 46, 47, 48, 49);
        $zodiac = array('鼠', '牛', '虎', '兔', '龙', '蛇', '马', '羊', '猴', '鸡', '狗', '猪');
        $sx_code = array('鼠' => 129, '牛' => 130, '虎' => 131, '兔' => 132,
                '龙' => 133, '蛇' => 134, '马' => 135, '羊' => 136,
                '猴' => 137, '鸡' => 138, '狗' => 139, '猪' => 140
                );
        // 红波：01, 02, 07, 08, 12, 13, 18, 19, 23, 24, 29, 30, 34, 35, 40, 45, 46 
        // 蓝波：03, 04, 09, 10, 14, 15, 20, 25, 26, 31, 36, 37, 41, 42, 47, 48 
        // 绿波：05, 06, 11, 16, 17, 21, 22, 27, 28, 32, 33, 38, 39, 43, 44, 49
        $sb_code = array('red' => 124, 'green' => 125, 'blue' => 126);
        $sb = array('01' => 'red', '02' => 'red', '07' => 'red', '08' => 'red', '12' => 'red', '13' => 'red', '18' => 'red', '19' => 'red', 
            '23' => 'red', '24' => 'red', '29' => 'red', '30' => 'red', '34' => 'red', '35' => 'red', '40' => 'red', '45' => 'red', '46' => 'red',
            '03' => 'blue', '04' => 'blue', '09' => 'blue', '10' => 'blue', '14' => 'blue', '15' => 'blue', '20' => 'blue', '25' => 'blue', 
            '26' => 'blue', '31' => 'blue', '36' => 'blue', '37' => 'blue', '41' => 'blue', '42' => 'blue', '47' => 'blue', '48' => 'blue', 
            '05' => 'green', '06' => 'green', '11' => 'green', '16' => 'green', '17' => 'green', '21' => 'green', '22' => 'green', '27' => 'green',
            '28' => 'green', '32' => 'green', '33' => 'green', '38' => 'green', '39' => 'green', '43' => 'green', '44' => 'green', '49' => 'green');
        // 金：03、04、17、18、25、26、33、34、47、48 
        // 木：07、08、15、16、29、30、37、38、45、46 
        // 水：05、06、13、14、21、22、35、36、43、44 
        // 火：01、02、09、10、23、24、31、32、39、40 
        // 土：11、12、19、20、27、28、41、42、49
        $wx_name = array('金', '木', '水', '火', '土');
        $wx_code = array(286, 287, 288, 289, 290);
        $wx = array(array('03', '04', '17', '18', '25', '26', '33', '34', '47', '48'),
                array('07', '08', '15', '16', '29', '30', '37', '38', '45', '46'),
                array('05', '06', '13', '14', '21', '22', '35', '36', '43', '44'),
                array('01', '02', '09', '10', '23', '24', '31', '32', '39', '40'),
                array('11', '12', '19', '20', '27', '28', '41', '42', '49'));

        //$sx_list = '{"01":"鸡","13":"鸡","25":"鸡","37":"鸡","49":"鸡","02":"猴","14":"猴","26":"猴","38":"猴","03":"羊","15":"羊","27":"羊","39":"羊","04":"马","16":"马","28":"马","40":"马","05":"蛇","17":"蛇","29":"蛇","41":"蛇","06":"龙","18":"龙","30":"龙","42":"龙","07":"兔","19":"兔","31":"兔","43":"兔","08":"虎","20":"虎","32":"虎","44":"虎","09":"牛","21":"牛","33":"牛","45":"牛","10":"鼠","22":"鼠","34":"鼠","46":"鼠","11":"猪","23":"猪","35":"猪","47":"猪","12":"狗","24":"狗","36":"狗","48":"狗"}';
        $key = array('01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12', '13', '14', '15', '16', '17', '18', '19', '20',
                    '21', '22', '23', '24', '25', '26', '27', '28', '29', '30', '31', '32', '33', '34', '35', '36', '37', '38', '39', '40',
                    '41', '42', '43', '44', '45', '46', '47', '48', '49');

        $lunar = new Lunar();
        $lunar_day = $lunar->convertSolarToLunar(date('Y'), date('m'), date('d'));
        $sx = $lunar_day[6];
        $sx_list = array();
        $s = array_keys($zodiac, $sx);
        $s = current($s);
        for ($i = 0; $i <= 11; $i++) {
            if ($s < 0) {
                $s = 11;
            }
            $current_sx = $zodiac[$s];
            $s--;
            for ($b = $i; $b < 49; $b += 12) {
                $sx_list[$key[$b]] = $current_sx;
            }
        }

        /* 如果是计算开奖结果，则返回开奖结果对应的生肖和色波code */
        if (!empty($lottery['base'])) {
            foreach ($lottery['base'] as $v) {
                $v = strlen($v) > 1 ? $v : '0'.$v;
                $lottery['sx'][] = $sx_list[$v];
                $lottery['sb'][] = $sb[$v];
                $lottery['sx_code'][] = $sx_code[$sx_list[$v]];
                $lottery['sb_code'][] = $sb_code[$sb[$v]];
                for ($i = 0; $i < 5; $i++) {
                    if (in_array($v, $wx[$i])) {
                        $lottery['wx'][] = $wx_name[$i];
                        $lottery['wx_code'][] = $wx_code[$i];
                        break;
                    }
                }
            }
            return $lottery;
        }

        $key = 'sx_list';
        $sname = $this->redisP_set($key, json_encode($sx_list, JSON_UNESCAPED_UNICODE));
        return array('sx' => $sx_list, 'sb' => $sb, 'wx' => $wx);
    } /* }}} */

    /**
     * @brief 根据gid 将球号code转换成对应的name
     * @access  public
     * @param   int     $gid    游戏id
     * @param   int     $tid    游戏id
     * @param   string  $content    球号
     * @return  string
     *          传入 gid:11 content:0|100,101|10,9|102,100,1|5
     *          返回 0|大,小|10,9|单,大,1|5
     */
    public function get_balls_names($gid, $tid, $content) {
        $content = explode('|', $content);
        if (empty($gid) || empty($content)) {
            return '';
        }
        // redis获取彩种球号数据，没有则读库
        $key = 'games_code_to_name';
        $gInfo = $this->redisP_hget($key, $gid. $tid);
        if (empty($gInfo)) {
            $this->select_db('public');
            $gInfo = $this->db->select('code,name,tid')->group_by('code')->get_where('games_products', ['gid' => $gid, 'tid' => $tid])->result_array();
            if (empty($gInfo)) {
                return '';
            }
            $gInfo = json_encode($gInfo);
            $this->redisP_hset($key, $gid. $tid, $gInfo);
        }
        $gData = json_decode($gInfo, true);
        // 组装返回格式
        $rs = [];
        foreach ($content as $items) {
            $temp = [];
            $item = explode(',', $items);
            foreach ($item as $v) {
                foreach ($gData as $vv) {
                    if ($v == $vv['code']) {
                        array_push($temp, $vv['name']);
                    }
                }
            }
            array_push($rs, implode($temp, ','));
        }
        return empty($rs) ? '' : implode('|', $rs);
    }
}

/* end file */
