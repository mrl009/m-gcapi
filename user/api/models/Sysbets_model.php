<?php
/**
 * @file models/Sysbets_model.php
 * @brief
 *
 * Copyright (C) 2017 GC.COM
 * All rights reserved.
 *
 * @package model
 * @author Langr <hua@langr.org> 2018/01/11 11:25
 *
 * $Id$
 */

defined('BASEPATH') or exit('No direct script access allowed');

class Sysbets_model extends MY_Model
{
    public function __construct() /* {{{ */
    {
        parent::__construct();
        // $this->db2 = $this->load->database('private', true);
        // $this->select_db('public');
    } /* }}} */

    /**
     * @brief 试结算
     *      消费下注 redis 队列, 处理日结 redis 记录，生产中奖队列
     *      gc:bets:lhc:20170302
     *          {玩法:[注单号,uid,注单内容,单注金额,总组合注数,总注单金额,赔率,返水比率,追号时间或编号[boyou分支追号中奖后停止追号使用]]}
     *          {玩法:[注单号,uid,注单内容,单注金额,总组合注数,总注单金额,赔率,返水比率,是否测试注单[yicai无线分支用]]}
     *          {lm_z1:[order_num,uid,contents,price,counts,price_sum,rate,rebate,chase_time/is_test]}
     *          {"5x_5xzhx_zh":["909706092047226921","113","1,2|2|3,4|4|5","1",20,"20","98000,9800,980,98,9.8","0.0",1501156312]}
     *      x 中奖[和局]队列
     *      x gc:bets_wins:lhc:20170302
     *      x    {lm_z1:[order_num,uid,win_contents,win_counts,win_price,status]}
     * @access public
     * @param string    $dbn        数据库名(站点id)
     * @param string    $gname      游戏简写代号
     * @param string    $issue      期号
     * @return
     */
    public function settlement($dbn = '', $gname = '', $issue = '', $lottery = array()) /* {{{ */
    {
        /* 下注队列 */
        $bets_queue = 'bets:'.$gname.':'.$issue;
        $tmp_queue = 'bets_tmp:'.$gname.':'.$issue;
        /* 撤单 hash */
        $cancel_hash = 'bets_cancel';
        /* 返回结算结果 */
        $result = ['counts'=>0, 'bets_counts'=>0, 'price'=>0, 'valid_price'=>0, 'win_counts'=>0, 'win_price'=>0];

        /* 加载游戏结算插件 */
        $plugin = null;
        $plugin_name = 'games_settlement_'.$gname;
        $plugin_file = BASEPATH.'gc/libraries/'.$plugin_name.'.php';
        if (file_exists($plugin_file)) {
            include_once($plugin_file);
            if (class_exists($plugin_name)) {
                $plugin = new $plugin_name;
            }
        }

        /* 下注单放到持久库 */
        $this->select_redis_db(REDIS_LONG);
        /* 获取游戏当期开奖号 */
        if (method_exists($plugin, 'wins_balls')) {
            $lottery = $plugin->wins_balls($lottery);
        }
        //var_dump($lottery['dxds']);exit;

        $bets_counts = 0;
        $llen = $this->redis_llen($bets_queue);
        for ($i = 0; $i < $llen; $i++) {
            $bet = $this->redis_lpop($bets_queue);
            //$bet = "{\"2th_2tdx_bzxh\":[\"1570329144415671\",1,\"3|2,4,5||6\",2,3,6,\"6.12\",13]}";
            if ($bet == false) {
                /* done */
                break;
            }
            $this->redis_rpush($bets_queue, $bet);
            //$this->redis_rpush($tmp_queue, $bet);
            $bet = json_decode($bet, true);
            $fn = key($bet);
            $bet = $bet[$fn];
            $fn_settlement = 'settlement_'.$fn;
            /* 测试注单检测 */
            if ($bet[8] == 1) {
                continue;
            }
            /* 退款检测redis, 检测此注单是否已经取消/处理过 */
            $ret = $this->redis_hget($cancel_hash, $bet[0]);
            if (!empty($ret)) {
                //$this->redis_rpop($tmp_queue);
                continue;
            }
            $result['counts']++;                         // 总结算笔数
            $result['bets_counts'] += $bet[4];           // 总有效结算注数(减和局)
            $result['price'] += $bet[5];                 // 总金额
            $result['valid_price'] += $bet[5];           // 总有效金额(减和局)
            /**
             * 中奖，和局，未中奖
             * ret = array('win_contents'=>array(array(1,3)), 'win_counts'=>1, 'price_sum'=>3.9, 'status'=>STATUS_WIN);
             * ret = array('win_contents'=>null, 'win_counts'=>0, 'price_sum'=>4, 'status'=>STATUS_HE);
             * ret = null;
             */
            $ret = array();
            if (method_exists($plugin, $fn_settlement)) {
                $ret = $plugin->$fn_settlement($bet, $lottery);
            }
            /* 处理中奖[或和局]结果 */
            if (isset($ret['price_sum']) && $ret['price_sum'] > 0) {
                if ($ret['status'] == STATUS_WIN) {
                    /* 中奖 */
                    //$ret['price_sum'] = round($ret['price_sum'], 3);
                    //$ret['win_contents'] = json_encode($ret['win_contents']);
                    $result['win_counts'] += $ret['win_counts'];        // 中奖注数
                    $result['win_price'] += round($ret['price_sum'], 3);          // 中奖额
                } else {
                    /* 打和 */
                    //$ret['win_contents'] = '';
                    //$ret['win_counts'] = $bet[4];
                    $result['bets_counts'] -= $bet[4];          // 总有效结算注数(减和局)
                    $result['valid_price'] -= $bet[5];          // 总有效金额(减和局)
                }
            }
            //$this->redis_rpop($tmp_queue);
        }
        //wlog(APPPATH.'logs/bets/'.$dbn.'_'.$gname.'_settlement_'.date('Ym').'.log', $issue.':settlement done:'.$result['bets_counts'], true);

        return $result;
    } /* }}} */
}

/* end file */
