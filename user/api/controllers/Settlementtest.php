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

class Settlementtest extends GC_Controller 
{
    public function __construct() /* {{{ */
    {
        parent::__construct();
        $this->load->model('betstest_model', 'bets_model');
        $this->load->model('games_model');
    } /* }}} */

    /**
     * @brief 指定站点采种结算
     *      取游戏开奖期号和开奖号信息，计算中奖，返款，返水，清下注 redis 队列，修改日结 redis Hash，写日结数据库。。。 
     * @link http://api.101.com/index.php?
     * @method  GET
     * @param   $gname      game sname
     * @param   $dbn        库名或站点ID
     * @param   $issue      期数号
     * @return  ok
     */
    public function index($gname = '', $open_str = '', $dbn = '', $issue = '') /* {{{ */
    {
        if (!is_cli()) {
            header('HTTP/1.1 405 fuck u!');
        //    $this->return_json(E_METHOD, 'method nonsupport!');
        }
        if (empty($gname)) {
            wlog(APPPATH.'logs/betstest/'.$dbn.'_'.$gname.'_settlement_'.date('Ym').'.log', '游戏信息无效!', true);
            return false;
        }
        /* 支持动态配置的私库时可用 */
        if (!empty($dbn)) {
            $this->games_model->init($dbn);
            $this->bets_model->init($dbn);
            $dbn = $this->games_model->sn;
        } else {
            wlog(APPPATH.'logs/dsn_web'.date('Y').'.log', 'dsn error:'.$dbn, true);
            return false;
        }

        $game = $this->games_model->info($gname);
        if (count($game) < 1) {
            wlog(APPPATH.'logs/betstest/'.$dbn.'_'.$gname.'_settlement_'.date('Ym').'.log', '无效的gid!', true);
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
            wlog(APPPATH.'logs/betstest/'.$dbn.'_'.$gname.'_settlement_'.date('Ym').'.log', '无未处理开奖期号!'.$issue, true);
        //    return false;
        }
        /* 检测私库是否有开奖记录 */
        $private_open = $this->bets_model->get_bet('settlement', ['gid'=>$gid, 'issue'=>$issue]);
        if ($private_open && $private_open['status'] == STATUS_END) {
            wlog(APPPATH.'logs/betstest/'.$dbn.'_'.$gname.'_settlement_'.date('Ym').'.log', $issue.'已处理过', true);
            /* 第二次，如果当期结算过，则检测是否有早期未结算期号，获取早一期未开奖期号,及开奖球号 */
            /*
            $issue = $this->bets_model->get_issue($gname, $issue);
            $issue_info = $this->open_result_model->get_result($gid, STATUS_OPEN, $issue);
            $issue = !empty($issue_info['kithe']) ? $issue_info['kithe'] : $issue;
            if (empty($issue_info['kithe']) || $issue_info['status'] != STATUS_OPEN) {
                wlog(APPPATH.'logs/betstest/'.$dbn.'_'.$gname.'_settlement_'.date('Ym').'.log', '无未处理开奖期号2!'.$issue, true);
            //    return false;
            }
            */
            /* 检测私库是否有开奖记录 */
            /*
            $private_open = $this->bets_model->get_bet('settlement', ['gid'=>$gid, 'issue'=>$issue]);
            if ($private_open && $private_open['status'] == STATUS_END) {
                wlog(APPPATH.'logs/betstest/'.$dbn.'_'.$gname.'_settlement_'.date('Ym').'.log', $issue.'已处理过2', true);
                return false;
            }
            */
        }

        /* 找到未结算期号，准备结算 */
        //$issue = $issue_info['kithe'];
        //$issue_info['number'] = '7,5,15,10,1,9,3';
        if (!empty($open_str)) {
            $issue_info['number'] = str_replace('-', ',', $open_str);
        }
        if (empty($issue_info['number']) || strlen($issue_info['number']) < 5) {
            wlog(APPPATH.'logs/betstest/'.$dbn.'_'.$gname.'_settlement_'.date('Ym').'.log', '开奖号非法:'.$issue_info['number'], true);
            return false;
        }
        $lottery = array('base' => explode(',', $issue_info['number']));
        if ($gname == 'lhc' || $gname == 'jslhc') {
            $lottery = $this->games_model->lhc_sx_balls($lottery);
        }
        /* 本期开奖日期 */
        $issue_day = date('Ymd', strtotime(!empty($issue_info['open_time']) ? $issue_info['open_time'] : time()));

        wlog(APPPATH.'logs/betstest/'.$dbn.'_'.$gname.'_settlement_'.date('Ym').'.log', $issue.':start...'.$issue_info['number'], true);
        $now = time();
        /* 结算: 如果此处打开此行注释代码，则结算会被上锁，没法支持多进程结算 */
        // $this->open_result_model->set_result_status($gid, $issue, STATUS_ENDING);
        $ret = $this->bets_model->settlement($dbn, $gname, $gid, $issue, $lottery);
        $ret['created'] = $now;
        /* 结算状态记录到私库 */
        $this->bets_model->refresh_settlement($gid, $issue, $issue_info['number'], $ret);
        /* Refresh Statistics */
        //$this->bets_model->refresh_statistics($dbn, $gid, $issue_day);
        /* 每日结算/未结算笔数 */
        $this->bets_model->bet_counts_redis('settlement', $ret['counts']);
        wlog(APPPATH.'logs/betstest/'.$dbn.'_'.$gname.'_settlement_'.date('Ym').'.log', $issue.':end.', true);
        
        return true;
    } /* }}} */
}

/* end file */
