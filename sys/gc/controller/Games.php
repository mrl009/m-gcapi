<?php
/**
 * @file Games.php
 * @brief 后台游戏列表，玩法，球号，赔率 管理接口
 * 
 * Copyright (C) 2017 GC.COM
 * All rights reserved.
 * 
 * @package controllers
 * @author Langr <hua@langr.org> 2017/03/13 16:59
 * 
 * $Id$
 */

defined('BASEPATH') OR exit('No direct script access allowed');

class Games extends MY_Controller 
{
    /**
     * 认证模块
     */
    protected $auth2 = array(
        /* 免登陆 */
        'pass' => array(
            'Games' => array('index'),
        ),
        /* 需要登陆的权限 */
        'login' => array(
            'Games' => array(),
        )
    );

    public function __construct() /* {{{ */
    {
        parent::__construct();
        $this->load->model('games_model');
    } /* }}} */

    /**
     * @brief games intro 
     * @param int       $gid            游戏id
     *          为空则返回全部游戏列表
     * @param string    $_GET['type']   hot,yb,ssc,k3,11x5,pcdd,pk10,ssl
     * @return 游戏信息简介
     */
    public function info($gid = 0) /* {{{ */
    {
        $type = $this->G('type');
        $ctg = $this->G('ctg');
        if (empty($gid) || !is_numeric($gid)) {
            $rows = $this->games_model->getlist();
        } else {
            $rows = $this->games_model->info($gid);
            if (count($rows) < 1) {
                $this->return_json(E_ARGS, '无效的gid');
            }
            // $rows = array($rows);
            $this->return_json(OK, $rows);
        }

        // 按类型热度取列表
        if (!empty($type)) {
            foreach ($rows as $k => $v) {
                if ($type == 'hot' && $v['hot'] != IS_HOT) {
                    unset($rows[$k]);
                } else if ($type != 'hot' && $type != $v['type']) {
                    unset($rows[$k]);
                }
            }
        }
        // 过滤ctg
        if (!empty($ctg) && in_array($ctg, ['sc','gc','sx','dz'])) {
            foreach ($rows as $k => $v) {
                if ($ctg != $v['ctg']) {
                    unset($rows[$k]);
                }
            }
        }
        $rows = array('rows' => array_values($rows));
        $rows['total'] = count($rows['rows']);
        $this->return_json(OK, $rows);
    } /* }}} */

    /**
     * @brief games types list 
     * @link http://api.101.com/index.php?
     * @param 
     * @return 游戏玩法json
     */
    public function play($gid = 0) /* {{{ */
    {
        if (empty($gid) || !is_numeric($gid)) {
            $this->return_json(E_ARGS, '无效的gid');
        }
        $rows = $this->games_model->getplay($gid);
        $rows = ['aid' => 0] + $rows;
        /* 加入站点cp判断，加入国私关联检测 */
        $ids = $this->games_model->get_gcset(['cp']);
        if (isset($ids['cp'])) {
            $ids = explode(',', $ids['cp']);
            if (!in_array($gid, $ids)) {
                $this->return_json(E_ARGS, '未开通的gid!');
            }
            $aid = ($gid < 50) ? $gid + 50 : (($gid < 100) ? $gid - 50 : 0);
            if (in_array($aid, $ids)) {
                $aid_info = $this->games_model->info($aid);
                if (!empty($rows['ctg']) && !empty($aid_info['ctg']) && $aid_info['ctg'] != $rows['ctg']) {
                    $rows['aid'] = $aid;
                }
            }
        }
        $this->return_json(OK, $rows);
    } /* }}} */

    /**
     * @brief games products list 
     * @param 
     * @return 游戏玩法json
     */
    public function products($gid = 0, $tid = 0) /* {{{ */
    {
        if (empty($gid) || !is_numeric($gid)) {
            $this->return_json(E_ARGS, '无效的gid');
        }
        $ret = $this->games_model->getproducts($gid, $tid);
        $rows['balls_rate'] = $ret;
        $rows['user_rebate'] = $this->games_model->user_rebate($this->user['id'], $gid);

        $this->return_json(OK, $rows);
    } /* }}} */

    /**
     * @brief 六合彩生肖 
     * @param 
     * @return 生肖，色波json
     */
    public function lhc_sx() /* {{{ */
    {
        $this->return_json(OK, $this->games_model->lhc_sx_balls());
    } /* }}} */

    /**
     * @brief clean cache
     *      cache/games_list.json
     *      cache/games_play.json
     *      cache/games_play_xx.json
     *
     *      改为 redis:
     *          公 key: games:list
     *          公 key: games:play
     *          私 hash: games_play gid=>value
     * @param $fn = list,play,1,2,3[gid]
     * @return ok
     */
    public function cleancache($fn = '') /* {{{ */
    {
        /* 删除 cache 目录下指定缓存文件 */
        /*if (!empty($fn)) {
            $file = APPPATH.'cache/games_'.$fn.'.json';
            if (is_file($file)) {
                @unlink($file);
            }
            return $this->return_json(OK);
        }*/
        /* 删除 cache 目录下全部缓存文件 */
        //array_map('unlink', glob(APPPATH.'cache/games_*'));

        /* 删除 redis 缓存 */
        if (empty($fn)) {
            $this->games_model->redis_del('games_play');
        } elseif (in_array($fn, ['list', 'play'])) {
            $this->games_model->redisP_del('games:'.$fn);
        } elseif (is_numeric($fn)) {
            $this->games_model->redis_hdel('games_play', $fn);
        }

        return $this->return_json(OK);
    } /* }}} */
}

/* end file */
