<?php
/**
 * @file Settlement.php
 * @brief 
 * 
 * Copyright (C) 2017 GC.COM
 * All rights reserved.
 * 
 * @package controllers
 * @author Langr <hua@langr.org> 2017/03/30 11:16
 * 
 * $Id$
 */

defined('BASEPATH') or exit('No direct script access allowed');

class Settlement extends GC_Controller 
{
    public function __construct() /* {{{ */
    {
        parent::__construct();
        $this->load->model('bets_model');
        $this->load->model('games_model');
    } /* }}} */

    /**
     * @brief 指定站点采种结算
     *      取游戏开奖期号和开奖号信息，计算中奖，返款，返水，清下注 redis 队列，修改日结 redis Hash，写日结数据库。。。 
     * @link http://api.101.com/index.php?
     * @method  GET
     * @param   $gname      game sname
     * @param   $dbn        库名或站点ID(dsn)
     * @param   $issue      期数号
     * @return  ok
     */
    public function index($gname = '', $dbn = '', $issue = '') /* {{{ */
    {
        if (!is_cli()) {
            header('HTTP/1.1 405 fuck u!');
            $this->return_json(E_METHOD, 'method nonsupport!');
        }
        if (empty($gname)) {
            wlog(APPPATH.'logs/bets/'.$dbn.'_'.$gname.'_settlement_'.date('Ym').'.log', '游戏信息无效!', true);
            return false;
        }
        /* 支持动态配置的私库时可用 */
        if (!empty($dbn)) {
            // $this->games_model->select_db($dbn);
            $this->games_model->init($dbn);
            $this->bets_model->init($dbn);
            $dbn = $this->games_model->sn;
        } else {
            wlog(APPPATH.'logs/dsn_'.date('Y').'.log', 'dsn error:'.$dbn, true);
            return false;
        }
        
        $game = $this->games_model->info($gname);
        if (count($game) < 1) {
            wlog(APPPATH.'logs/bets/'.$dbn.'_'.$gname.'_settlement_'.date('Ym').'.log', '无效的gid!', true);
            //$this->return_json(E_ARGS, '无效的gid!');
            return false;
        }
        $gid = $game['id'];

        /* 第一次，有自定义结算期号，则取指定期号结算；没指定期号，则先获取最近一期未开奖期号,检测是否结算过 */
        $this->load->model('open_result_model');
        $this->open_result_model->init($dbn);
        /* 验证期号时间有效性 */
        $issue_info = $this->open_result_model->get_result($gid, STATUS_OPEN, $issue);
        $issue = !empty($issue_info['kithe']) ? $issue_info['kithe'] : $issue;
        if (empty($issue_info['kithe']) || $issue_info['status'] != STATUS_OPEN) {
            wlog(APPPATH.'logs/bets/'.$dbn.'_'.$gname.'_settlement_'.date('Ym').'.log', '无未处理开奖期号!'.$issue, true);
            return false;
        }
        /* 检测私库是否有开奖记录 */
        $private_open = $this->bets_model->get_bet('settlement', ['gid'=>$gid, 'issue'=>$issue]);
        if ($private_open && $private_open['status'] == STATUS_END) {
            wlog(APPPATH.'logs/bets/'.$dbn.'_'.$gname.'_settlement_'.date('Ym').'.log', $issue.'已处理过', true);
            /* 第二次，如果当期结算过，则检测是否有早期未结算期号，获取早一期未开奖期号,及开奖球号 */
            $issue = $this->bets_model->get_issue($gname, $issue);
            $issue_info = $this->open_result_model->get_result($gid, STATUS_OPEN, $issue);
            $issue = !empty($issue_info['kithe']) ? $issue_info['kithe'] : $issue;
            if (empty($issue_info['kithe']) || $issue_info['status'] != STATUS_OPEN) {
                wlog(APPPATH.'logs/bets/'.$dbn.'_'.$gname.'_settlement_'.date('Ym').'.log', '无未处理开奖期号2!'.$issue, true);
                return false;
            }
            /* 检测私库是否有开奖记录 */
            $private_open = $this->bets_model->get_bet('settlement', ['gid'=>$gid, 'issue'=>$issue]);
            if ($private_open && $private_open['status'] == STATUS_END) {
                wlog(APPPATH.'logs/bets/'.$dbn.'_'.$gname.'_settlement_'.date('Ym').'.log', $issue.'已处理过2', true);
                return false;
            }
        }

        /* 找到未结算期号，准备结算 */
        //$issue_info['number'] = '7,5,15,10,1,9,3';
        //$issue_info['number'] = '1,8,1';
        if (empty($issue_info['number']) || strlen($issue_info['number']) < 5) {
            wlog(APPPATH.'logs/bets/'.$dbn.'_'.$gname.'_settlement_'.date('Ym').'.log', '开奖号非法:'.$issue_info['number'], true);
            return false;
        }
        /* 结算加锁: 如果此处打开此行注释代码，则结算会被上锁，没法支持多进程结算 */
        //$this->bets_model->refresh_settlement($gid, $issue, $issue_info['number']);
        $lottery = array('base' => explode(',', $issue_info['number']));
        if ($gname == 'lhc' || $gname == 'jslhc') {
            $lottery = $this->games_model->lhc_sx_balls($lottery);
        }

        wlog(APPPATH.'logs/bets/'.$dbn.'_'.$gname.'_settlement_'.date('Ym').'.log', $issue.':start...'.$issue_info['number'], true);
        $now = time();
        /* 结算: */
        $ret = $this->bets_model->settlement($dbn, $gname, $gid, $issue, $lottery);
        $ret['created'] = $now;
        /* 结算状态记录到私库 */
        $this->bets_model->refresh_settlement($gid, $issue, $issue_info['number'], $ret);
        /* 每日结算/未结算笔数 */
        $this->bets_model->bet_counts_redis('settlement', $ret['counts']);
        wlog(APPPATH.'logs/bets/'.$dbn.'_'.$gname.'_settlement_'.date('Ym').'.log', $issue.':end.', true);
        
        return true;
    } /* }}} */

    /**
     * @brief 指定站点采种结算
     *      取游戏开奖期号和开奖号信息，计算中奖，返款，返水，清下注 redis 队列，修改日结 redis Hash，写日结数据库。。。 
     * @link http://api.101.com/index.php?
     * @method  GET
     * @param   $dbn        库名或站点ID(dsn)
     * @param   $gn         group sname
     * @return  ok
     */
    public function groups($dbn = '', $gn = '') /* {{{ */
    {
        $group = [
            /* 第一种分组法 */
            /* 1-2天一期,20:30~21:45 */
            'yb' => ['lhc', 'fc3d', 'pl3'],
            's_yb' => ['s_fc3d', 's_pl3'],
            /* 10分钟一期 */
            'ssc' => ['cqssc', 'tjssc', 'xjssc', 'bjssc', 'aj3fc'],
            'k3' => ['jsk3', 'jlk3', 'hbk3', 'gxk3', 'ahk3'],
            '11x5' => ['sd11x5', 'jx11x5', 'gd11x5', 'sh11x5', 'ah11x5'],
            's_ssc' => ['s_cqssc', 's_tjssc', 's_xjssc', 's_bjssc', 's_sfssc'],
            's_k3' => ['s_jsk3', 's_jlk3', 's_hbk3', 's_gxk3', 's_ahk3'],
            's_11x5' => ['s_sd11x5', 's_jx11x5', 's_gd11x5', 's_sh11x5', 's_ah11x5'],
            's_kl10' => ['s_cqkl10', 's_gdkl10'],
            /* 5分钟一期 */
            'pk10' => ['bjpk10', 'sfpk10'],
            'pcdd' => ['jslhc', 'xjp28', 'bj28'],
            's_pk10' => ['s_bjpk10', 's_sfpk10'],
            /* 1分钟一期 */
            'js' => ['jspk10', 'ftpk10'],

            /* 第二种分组 */
            'a_yb' => ['fc3d', 'pl3', 'lhc', 's_fc3d', 's_pl3'],
            'a_ssc' => ['cqssc', 'tjssc', 'xjssc', 'bjssc', 's_cqssc', 's_tjssc', 's_xjssc', 's_bjssc'],
            'a_k3' => ['jsk3', 'jlk3', 'hbk3', 'gxk3', 'ahk3', 's_jsk3', 's_jlk3', 's_hbk3', 's_gxk3', 's_ahk3'],
            'a_11x5' => ['sd11x5', 'jx11x5', 'gd11x5', 'sh11x5', 'ah11x5', 's_sd11x5', 's_jx11x5', 's_gd11x5', 's_sh11x5', 's_ah11x5'],
            'a_kl10' => ['s_cqkl10', 's_gdkl10', 'bj28', 'bjpk10', 's_bjpk10'],
            'a_s_k3' => ['s_bjk3', 's_hebk3', 's_gsk3', 's_shk3', 's_gzk3'],
            'a_js' => ['jspk10', 'ftpk10'],
            'a_sys' => ['aj3fc', 's_sfssc', 'sfpk10', 's_sfpk10', 'jslhc', 'xjp28', 's_yck3' ,'s_wfk3'],
        ];

        wlog(APPPATH.'logs/bets/'.$dbn.'_settlement_'.date('Ym').'.log', $gn.':start.', true);
        if (!empty($group[$gn])) {
            foreach ($group[$gn] as $gname) {
                $this->index($gname, $dbn);
                wlog(APPPATH.'logs/bets/'.$dbn.'_settlement_'.date('Ym').'.log', $gn.':'.$gname.':end.', true);
            }
        } else {
            $this->index($gn, $dbn);
        }
        wlog(APPPATH.'logs/bets/'.$dbn.'_settlement_'.date('Ym').'.log', $gn.':end.', true);
        return true;
    } /* }}} */
}

/* end file */
