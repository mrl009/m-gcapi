<?php
/**
 * @file Syssettlement.php
 * @brief 系统自开彩结算
 *
 * Copyright (C) 2018 GC.COM
 * All rights reserved.
 * 
 * @author Fei <feifei@xxx.com> 2018/01/05 15:36
 * 
 * $Id$
 */

defined('BASEPATH') or exit('No direct script access allowed');

class Syssettlement extends GC_Controller 
{
    /* G/S 同彩种 */
    private $same = ['aj3fc'=>'s_sfssc', 's_sfssc'=>'aj3fc', 'sfpk10'=>'s_sfpk10', 's_sfpk10'=>'sfpk10', 'ffssc'=>'s_ffssc','s_ffssc'=>'ffssc'];
    private $sameid = [11=>61, 61=>11, 27=>77, 77=>27, 10=>60, 60=>10];
    
    public function __construct() /* {{{ */
    {
        parent::__construct();
        $this->load->model('bets_model');
        $this->load->model('games_model');
    } /* }}} */

    /**
     * @brief 指定站点系统自开彩种结算
     *      取游戏开奖期号和开奖号信息，计算中奖，返款，返水，清下注 redis 队列，修改日结 redis Hash，写日结数据库。。。 
     * @link http://api.101.com/index.php?
     * @method  GET
     * @param   $gname      game sname
     * @param   $dbn        库名或站点ID(dsn)
     * @param   $issue      期数号
     * @return  ok
     */
    public function index($dbn = '', $gname = '', $issue = '') /* {{{ */
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
        if (substr($issue_info['open_time'], 0, -3) > date('Y-m-d H:i')) {
            wlog(APPPATH.'logs/bets/all_'.$gname.'_settlement_'.date('Ym').'.log', $dbn.':Error:未到开奖时间!'.json_encode($issue_info), true);
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

        /* 找到未结算期号，准备结算。系统自开彩为防止多进程结算，最好先加锁再结算 */
        //$issue_info['number'] = '7,5,15,10,1,9,3';
        //$issue_info['number'] = '1,8,1';
        if (empty($issue_info['number']) || strlen($issue_info['number']) < 5) {
            wlog(APPPATH.'logs/bets/'.$dbn.'_'.$gname.'_settlement_'.date('Ym').'.log', '开奖号非法:'.$issue_info['number'], true);
            return false;
        }
        /* 结算加锁: 如果此处打开此行注释代码，则结算会被上锁，没法支持多进程结算 */
        //$this->bets_model->refresh_settlement($gid, $issue);
        $lock_key = 'lock:'.$gname.$issue;
        $this->bets_model->select_redis_db(REDIS_LONG);
        $islock = $this->bets_model->redis_setnx($lock_key, 1);
        if ($islock) {
            $this->bets_model->redis_expire($lock_key, 600);
        } else {
            wlog(APPPATH.'logs/bets/'.$dbn.'_'.$gname.'_settlement_'.date('Ym').'.log', '加锁失败:'.$issue, true);
            return false;
        }
        $lottery = ['base' => explode(',', $issue_info['number'])];

        /* 开奖: */
        /**
         * 取配置参数 win_rate，计算开奖结果，结算
         * win_rate[$gid]
         * win_rate['win_top']
         * win_rate['win_rand']
         */
        $win_rate = [];
        $gcset = $this->bets_model->get_gcset(['win_rate']);
        if (!empty($gcset['win_rate'])) {
            $win_rate = json_decode($gcset['win_rate'], true);
        }
        if (isset($win_rate[$gid])) {
            $win_rate['win_top'] = empty($win_rate['win_top']) ? 110 : $win_rate['win_top'];
            $win_rate['min_price'] = empty($win_rate['min_price']) ? 100 : $win_rate['min_price'];
            $win_rate['max_price'] = empty($win_rate['max_price']) ? 3500 : $win_rate['max_price'];
            /* 是否随机, win_rand 0-10, 越大越随机 */
            $tmp_rand = mt_rand(1, 10);
            if (!empty($win_rate['win_rand']) && $tmp_rand <= $win_rate['win_rand']) {
                if ($tmp_rand == $win_rate['win_rand']) {
                    $win_rate['win_top'] = 99;
                } else {
                    $win_rate[$gid] = 0;
                }
            }
            wlog(APPPATH.'logs/bets/'.$dbn.'_'.$gname.'_open_'.date('Ym').'.log', $issue.':start:'.$win_rate[$gid].':rand:'.$tmp_rand.':win_rate:'.json_encode($win_rate), true);
            $lottery['base'] = $this->open($dbn, $gname, $issue, $win_rate[$gid], $win_rate['win_top'], $win_rate['min_price'], $win_rate['max_price']);
            wlog(APPPATH.'logs/bets/'.$dbn.'_'.$gname.'_open_'.date('Ym').'.log', $issue.':end:'.json_encode($lottery['base']), true);
            if ($lottery['base'] == false) {
                wlog(APPPATH.'logs/bets/'.$dbn.'_'.$gname.'_settlement_'.date('Ym').'.log', $issue.':end. Unsupported Sys-open.', true);
                $this->bets_model->redis_del($lock_key);
                return false;
            }
            if ($win_rate[$gid] == 0) {
                $lottery['base'] = explode(',', $issue_info['number']);
                wlog(APPPATH.'logs/bets/'.$dbn.'_'.$gname.'_open_'.date('Ym').'.log', $issue.':rate0:'.json_encode($lottery['base']), true);
            }
        }

        /* 将开奖结果写入redis */
        $this->bets_model->set_open_issue($dbn, $gid, $issue, implode(',', $lottery['base']));

        wlog(APPPATH.'logs/bets/'.$dbn.'_'.$gname.'_settlement_'.date('Ym').'.log', date('Y-m-d H:i:s', $_SERVER['REQUEST_TIME']).' '.$issue.':start...'.json_encode($lottery['base']), true);
        if ($gname == 'lhc' || $gname == 'jslhc') {
            $lottery = $this->games_model->lhc_sx_balls($lottery);
        }
        /* 本期开奖日期 */
        //$issue_day = date('Ymd', strtotime($issue_info['open_time']));
        $now = time();
        /* G/S 结算 */
        $ret = $this->bets_model->settlement($dbn, $gname, $gid, $issue, $lottery);
        $ret['created'] = $now;
        /* 结算状态记录到私库 */
        $this->bets_model->refresh_settlement($gid, $issue, implode(',', $lottery['base']), $ret);
        /* 每日结算/未结算笔数 */
        $this->bets_model->bet_counts_redis('settlement', $ret['counts']);

        if (!empty($this->same[$gname]) && !empty($this->sameid[$gid])) {
            $this->bets_model->set_open_issue($dbn, $this->sameid[$gid], $issue, implode(',', $lottery['base']));
            wlog(APPPATH.'logs/bets/'.$dbn.'_'.$gname.'_settlement_'.date('Ym').'.log', $issue.':start '.$this->same[$gname], true);
            /* G/S 结算 */
            $ret = $this->bets_model->settlement($dbn, $this->same[$gname], $this->sameid[$gid], $issue, $lottery);
            $ret['created'] = $now;
            /* 结算状态记录到私库 */
            $this->bets_model->refresh_settlement($this->sameid[$gid], $issue, implode(',', $lottery['base']), $ret);
            /* 每日结算/未结算笔数 */
            $this->bets_model->bet_counts_redis('settlement', $ret['counts']);
        }
        wlog(APPPATH.'logs/bets/'.$dbn.'_'.$gname.'_settlement_'.date('Ym').'.log', $issue.':end.', true);
        $this->bets_model->redis_del($lock_key);
        
        return true;
    } /* }}} */

    /**
     * @brief 指定站点采种开奖
     *      三分时时彩 aj3fc, 
     *      三分时时采-私彩 s_ffssc，
     *      分分彩 ffssc
     *      极速六合 jslhc，
     *      幸运28 xjp28, 
     *      三分pk10 sfpk10,
     *      三分pk10-私彩 s_sfpk10, 
     *      极速快车 jspk10,
     *      极速飞艇 ftpk10
     * @link http://api.101.com/index.php?
     * @param   $dbn        库名或站点ID(dsn)
     * @param   $gname      game sname
     * @param   $win_rate   赔率/中奖率0~100
     * @param   $win_top    是否可输/最大输率0~100
     * @param   $min_price  启动智能算法最低总下注额金额
     * @return  $lottery / false
     */
    //private function open($dbn = '', $gname = '', $win_rate = []) /* {{{ */
    public function open($dbn = '', $gname = '', $issue = '', $win_rate = 0, $win_top = 110, $min_price = 100, $max_price = 10000) /* {{{ */
    {
        /* 自开彩配置：balls 球号范围，lengths 开奖号位数，unique 开奖号是否唯一，error 误差范围，times 最长计算时间 */
        $confs = [
            'aj3fc' => ['balls'=>[0, 1, 2, 3, 4, 5, 6, 7, 8, 9], 'lengths'=>5, 'unique'=>false, 'error'=>15, 'times'=>5],
            's_sfssc' => ['balls'=>[0, 1, 2, 3, 4, 5, 6, 7, 8, 9], 'lengths'=>5, 'unique'=>false, 'error'=>15, 'times'=>5],
            'ffssc' => ['balls'=>[0, 1, 2, 3, 4, 5, 6, 7, 8, 9], 'lengths'=>5, 'unique'=>false, 'error'=>15, 'times'=>3],
            'xjp28' => ['balls'=>[0, 1, 2, 3, 4, 5, 6, 7, 8, 9], 'lengths'=>3, 'unique'=>false, 'error'=>15, 'times'=>5],
            'jslhc' => ['balls'=>[1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20,
                21, 22, 23, 24, 25, 26, 27, 28, 29, 30, 31, 32, 33, 34, 35, 36, 37, 38, 39, 40,
                41, 42, 43, 44, 45, 46, 47, 48, 49], 'lengths'=>7, 'unique'=>true, 'error'=>15, 'times'=>6],
            's_sfpk10' => ['balls'=>[1, 2, 3, 4, 5, 6, 7, 8, 9, 10], 'lengths'=>10, 'unique'=>true, 'error'=>15, 'times'=>5],
            'sfpk10' => ['balls'=>[1, 2, 3, 4, 5, 6, 7, 8, 9, 10], 'lengths'=>10, 'unique'=>true, 'error'=>15, 'times'=>5],
            'jspk10' => ['balls'=>[1, 2, 3, 4, 5, 6, 7, 8, 9, 10], 'lengths'=>10, 'unique'=>true, 'error'=>15, 'times'=>3],
            'ftpk10' => ['balls'=>[1, 2, 3, 4, 5, 6, 7, 8, 9, 10], 'lengths'=>10, 'unique'=>true, 'error'=>15, 'times'=>3],
            's_yck3' => ['balls'=>[1, 2, 3, 4, 5, 6], 'lengths'=>3, 'unique'=>false, 'error'=>15, 'times'=>3],
            's_wfk3' => ['balls'=>[1, 2, 3, 4, 5, 6], 'lengths'=>3, 'unique'=>false, 'error'=>15, 'times'=>3],
        ];
        if (!isset($confs[$gname])) {
            return false;
        }
        $lottery = $this->rand_open($confs[$gname]);
        /* 随机开 */
        if ($win_rate == 0) {
            wlog(APPPATH.'logs/bets/'.$dbn.'_'.$gname.'_open_'.date('Ym').'.log', json_encode($lottery).':Rand-Ok');
            return $lottery;
        }
        //echo json_encode([$lottery,$dbn,$gname,$issue,$win_rate])."\r\n";exit;
        $this->load->model('sysbets_model');
        $this->sysbets_model->init($dbn);
        /**
         * G/S 开奖
         * $best = ['lottery'=>$lottery, 'error'=>tmp_rate - win_rate]
         *
         * (OOk)开奖有效金额为0，或没有下注，返回Ok
         * (F)  $tmp_rate 赔率大于100，放弃此次结果，利用上次记录优化
         * (Ok) $error 误差在设置范围内，且赔率小于等于100，返回Ok
         * (N)  误差不在设置范围内，比上次误差小，记录，进化
         * (D)  误差不在设置范围内，比上次误差大，大很多，放弃此次记录，利用上次记录优化
         * (P)  误差不在设置范围内，比上次误差大，大一点，记录1次，放弃此次记录，利用上次记录优化
         * (POk)误差不在设置范围内，比上次误差大，大一点，记录超过n次，结束，返回上次最优记录
         * (TOk)误差不在设置范围内，超时，结束，返回上次最优记录。
         */
        $log = '';
        $now = time();
        $best = ['lottery'=>$lottery, 'error'=>100];
        $n = 0;
        for ($i = 0; ; $i++) {
            /* Time-Out */
            if (time() - $now > $confs[$gname]['times']) {
                wlog(APPPATH.'logs/bets/'.$dbn.'_'.$gname.'_open_'.date('Ym').'.log', $log.':T-Ok');
                return $best['lottery'];
            }
            /* $ret = ['counts'=>0, 'bets_counts'=>0, 'price'=>0, 'valid_price'=>0, 'win_counts'=>0, 'win_price'=>0] */
            $tmp_lottery = ['base'=>$lottery];
            if ($gname == 'jslhc') {
                $tmp_lottery = $this->games_model->lhc_sx_balls(['base'=>$lottery]);
            }
//        echo json_encode([$i,3,$tmp_lottery])."\r\n";//exit;
            $ret = $this->sysbets_model->settlement($dbn, $gname, $issue, $tmp_lottery);
            if (!empty($this->same[$gname])) {
                $ret2 = $this->sysbets_model->settlement($dbn, $this->same[$gname], $issue, $tmp_lottery);
                wlog(APPPATH.'logs/bets/'.$dbn.'_'.$gname.'_open_'.date('Ym').'.log', 'ret2:'.json_encode([$ret, $ret2]));
                $ret['counts'] += $ret2['counts'];
                $ret['bets_counts'] += $ret2['bets_counts'];
                $ret['price'] += $ret2['price'];
                $ret['valid_price'] += $ret2['valid_price'];
                $ret['win_counts'] += $ret2['win_counts'];
                $ret['win_price'] += $ret2['win_price'];
            }
//        echo json_encode([$i,4,$ret])."\r\n"; //exit;
            $log = $i.':ret:'.json_encode([$lottery, $ret]);
            /* 此期无下注或有效下注额为0(无下注/全为和局) */
            if ($ret['counts'] == 0 || $ret['valid_price'] <= $min_price) {
                wlog(APPPATH.'logs/bets/'.$dbn.'_'.$gname.'_open_'.date('Ym').'.log', $log.':O-Ok');
                return $lottery;
            }
            if ($ret['valid_price'] >= ($max_price * 2)) {
                $win_top = 98;
            } elseif ($ret['valid_price'] >= $max_price) {
                $win_top = 103;
            }
            $tmp_rate = round($ret['win_price'] / $ret['valid_price'] * 100);
            $error = abs($tmp_rate - $win_rate);         /* 与目标误差值 (越小超好，为负表示比设置赔率低) */
            $log = $log.':result:'.$tmp_rate.'-'.$win_rate.'='.$error;
            /* You Know! */
            if ($tmp_rate > $win_top) {
                $lottery = $this->optimize_open($confs[$gname], $best['lottery'], $best['error']);
                wlog(APPPATH.'logs/bets/'.$dbn.'_'.$gname.'_open_'.date('Ym').'.log', $log.':'.$win_top.':F');
                continue;
            }
            if ($error <= $confs[$gname]['error']) {
                wlog(APPPATH.'logs/bets/'.$dbn.'_'.$gname.'_open_'.date('Ym').'.log', $log.':Ok');
                return $lottery;
            }
            /* 比上次误差小，进化 */
            if ($error < $best['error']) {
                $best = ['lottery'=>$lottery, 'error'=>$error];
                $n = 0;
                $lottery = $this->optimize_open($confs[$gname], $lottery, $error);
                wlog(APPPATH.'logs/bets/'.$dbn.'_'.$gname.'_open_'.date('Ym').'.log', $log.':N');
                continue;
            }
            /* 比上次误差大或相等，大很多/大一点 */
            if ($error < $best['error'] + $confs[$gname]['error']) {
                $n += 1;
                if ($n > 5) {
                    wlog(APPPATH.'logs/bets/'.$dbn.'_'.$gname.'_open_'.date('Ym').'.log', $log.':P-Ok');
                    return $best['lottery'];
                }
                wlog(APPPATH.'logs/bets/'.$dbn.'_'.$gname.'_open_'.date('Ym').'.log', $log.':P');
            } else {
                wlog(APPPATH.'logs/bets/'.$dbn.'_'.$gname.'_open_'.date('Ym').'.log', $log.':D');
            }
            $lottery = $this->optimize_open($confs[$gname], $best['lottery'], $best['error']);
        }

        return $best['lottery'];
    } /* }}} */

    /**
     * @brief 随机开
     * @link http://api.101.com/index.php?
     * @param   $conf      ['balls'=>[], 'lengths'=>3, 'unique'=>false]
     * @return  lottery number array / false
     */
    public function rand_open($conf = []) /* {{{ */
    {
        if (empty($conf['balls']) || empty($conf['lengths'])) {
           return false;
        }
        $conf['unique'] = isset($conf['unique']) ? $conf['unique'] : true;
        $conf['error'] = isset($conf['error']) ? $conf['error'] : 15;
        $conf['times'] = isset($conf['times']) ? $conf['times'] : 30;

        $ret = [];
        $balls = $conf['balls'];
        for ($i = 0; $i < $conf['lengths']; $i++) {
            $n = count($balls);
            $r = mt_rand(0, $n - 1);
            $ret[] = $balls[$r];
            if ($conf['unique']) {
                array_splice($balls, $r, 1);
            }
        }
        return $ret;
    } /* }}} */

    /**
     * @brief 优化开
     * @link http://api.101.com/index.php?
     * @param   $conf      ['balls'=>[], 'lengths'=>3, 'unique'=>false, 'error'=>15, 'times'=>30]
     * @param   $lottery    上一次计算最优结果
     * @param   $error      上一次计算(与目标)误差值(-100~100)
     * @param   $optimize   进化成功，取共同点，进化失败，取不共同点
     * @return  lottery number array / false
     */
    public function optimize_open($conf = [], $lottery = [], $error = 50, $optimize = []) /* {{{ */
    {
        if (empty($conf['balls']) || empty($conf['lengths'])) {
           return false;
        }
        $conf['unique'] = isset($conf['unique']) ? $conf['unique'] : true;
        $conf['error'] = isset($conf['error']) ? $conf['error'] : 15;
        $conf['times'] = isset($conf['times']) ? $conf['times'] : 30;

        /* 变动个数 */
        $change = round($conf['lengths'] * abs($error) / 100);
        /* TODO: 变动位置 */

        $ret = $lottery;
        $balls = $conf['balls'];
        if ($conf['unique']) {
            $balls = array_values(array_diff($balls, $lottery));
        }
        for ($i = 0; $i < $change; $i++) {
            $seat = mt_rand(0, $conf['lengths'] - 1);
            $n = count($balls);
            /* pk10 或 无可变动的球号，则将旧号打乱指定次数顺序 */
            if ($n == 0) {
                $seat_des = mt_rand(0, $conf['lengths'] - 1);
                $tmp = $ret[$seat];
                $ret[$seat] = $ret[$seat_des];
                $ret[$seat_des] = $tmp;
                continue;
            }
            $r = mt_rand(0, $n - 1);
            $ret[$seat] = $balls[$r];
            if ($conf['unique']) {
                array_splice($balls, $r, 1);
            }
        }
        return $ret;
    } /* }}} */
}

/* end file */
 
                
            
