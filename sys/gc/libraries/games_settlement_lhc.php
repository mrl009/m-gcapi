<?php
/**
 * @file games_settlement_lhc.php
 * @brief 
 * 
 * Copyright (C) 2017 GC.COM
 * All rights reserved.
 * 
 * @package libraries
 * @author Langr <hua@langr.org> 2017/04/07 11:04
 * 
 * $Id$
 */

include_once(dirname(__FILE__).'/games.php');

class games_settlement_lhc
{
    public $config_balls = [
        /* 基本球 */
        'base'  => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20,
            21, 22, 23, 24, 25, 26, 27, 28, 29, 30, 31, 32, 33, 34, 35, 36, 37, 38, 39, 40,
            41, 42, 43, 44, 45, 46, 47, 48, 49],
        /* 两面其他：和大和小和单和双 特大特小特单特双 总大总小总单总双 */
        'lm_qt' => [104, 105, 106, 107, 116, 117, 118, 119, 120, 121, 122, 123],
        /* 两面正码1-6：大小单双，和大和小和单和双 */
        'lm_zm' => [100, 101, 102, 103, 104, 105, 106, 107],
        /* 特码AB 其他：和大和小和单和双，大单小单大双小双，尾大尾小，特大特小特单特双 */
        'tm_qt' => [104, 105, 106, 107, 108, 109, 110, 111, 112, 113, 116, 117, 118, 119],
        /* 正码1-6 大小单双，和大和小和单和双，尾大尾小，红绿蓝波 */
        'zm_16' => [100, 101, 102, 103, 104, 105, 106, 107, 112, 113, 124, 125, 126],
        /* 生肖: */
        'sx' => [129, 130, 131, 132, 133, 134, 135, 136, 137, 138, 139, 140],
        /* 总肖: 234肖，5肖6肖7肖，总肖单总肖双 */
        'zhx' => [251, 252, 253, 254, 255, 256],
        /* 尾数: 尾0-尾9，头尾数: 头0-头4 */
        'ws' => [159, 160, 161, 162, 163, 164, 165, 166, 167, 168],
        'tws' => [159, 160, 161, 162, 163, 164, 165, 166, 167, 168, 260, 261, 262, 263, 264],
        /* 色波，7色波 红绿蓝和，半波 红单红双红大红小 绿单绿双绿大绿小，半半波 红大单 红大双 红小单 红小双 */
        'sb' => [124, 125, 126],
        '7sb' => [124, 125, 126, 200],
        'bb' => [141, 142, 143, 144, 147, 148, 149, 150, 153, 154, 155, 156],
        'bbb' => [301, 302, 303, 304, 305, 306, 307, 308, 309, 310, 311, 312],
        /* 七码五行 单0 单1 单2 单3 单4 单5 单6 单7，大0 大1 大2 大3 大4 大5 大6 大7；五行：金木水火土 */
        '7m'   => [270, 271, 272, 273, 274, 275, 276, 277, 278, 279, 280, 281, 282, 283, 284, 285],
        '5x'   => [286, 287, 288, 289, 290]
    ];

    /**
     * @brief 根据彩种当期的开奖号计算出各种玩法的开奖值：'01','02'...'49'
     *      如：lhc 2017042 当期开奖: 31 17 22 29 30 14 + 38
     *          则计算出生肖开奖值: 兔 蛇 鼠 蛇 龙 猴 + 猴.
     *          色波开奖值: 蓝 绿 绿 红 红 蓝 + 绿
     * @access public/protected 
     * @param 
     * @return 
     */
    public function wins_balls(& $wins_balls = []) /* {{{ */
    {
        //$wins_balls['base'] = ['1', '2', '3', '4', '5', '6', '7'];
        /* NOTE: lhc 开奖号不可以有重复 */
        if (count(array_unique($wins_balls['base'])) != 7 ||
                !in_array($wins_balls['base'][0], $this->config_balls['base']) ||
                !in_array($wins_balls['base'][1], $this->config_balls['base']) ||
                !in_array($wins_balls['base'][2], $this->config_balls['base']) ||
                !in_array($wins_balls['base'][3], $this->config_balls['base']) ||
                !in_array($wins_balls['base'][4], $this->config_balls['base']) ||
                !in_array($wins_balls['base'][5], $this->config_balls['base']) ||
                !in_array($wins_balls['base'][6], $this->config_balls['base'])
                ) {
            $msg = date('[Y-m-d H:i:s]').'[lhc] lottery error:'.implode(',', $wins_balls['base'])."\n";
            error_log($msg, 3, 'api/logs/wins_balls_error_'.date('Ym').'.log');
            exit($msg);
        }
        //$wins_balls['base'] = array('31', '17', '22', '29', '30', '14', '38');
        //$wins_balls['sx'] = array('兔', '蛇', '鼠', '蛇', '龙', '猴', '猴');
        //$wins_balls['sx_code'] = array(132, 134, 129, 134, 133, 137, 137);
        //$wins_balls['sb'] = array('blue', 'green', 'green', 'red', 'red', 'blue', 'green');
        //$wins_balls['sb_code'] = array(126, 125, 125, 124, 124, 126, 125);
        //$wins_balls['wx'] = array(126, 125, 125, 124, 124, 126, 125);
        //$wins_balls['wx_code'] = array(126, 125, 125, 124, 124, 126, 125);
        
        //$wins_balls['dxds'] = array(0=>array('dx'=>code, 'ds', 'he_dx', 'he_ds', 'w_dx', 'ddxs'),1=>array()...);
        //$wins_balls['ws'] = array(160,166,161,168,159,163,167);  // 1, 7, 2, 9, 0, 4, 8
        //$wins_balls['he_dxds'] = array('dx'=>code, 'ds');
        //$wins_balls['zhx'] = array('he'=>code, 'ds'=>code);
        //$wins_balls['tm_bb'] = array('ds'=>code, 'dx'=>code);
        //$wins_balls['tm_bbb'] = code;
        //$wins_balls['tm_ts'] = code;
        //$wins_balls['7sb'] = code;
        //$wins_balls['7m'] = array('ds'=>code, 'dx'=>code);
        $wins_balls['he'] = 0;
        /* 大小 单双 和大和小 和单和双 尾大尾小 大双小双大单小单 */
        $i = 0;
        foreach ($wins_balls['base'] as $v) {
            $tmp = [];
            $v = (int) $v;
            /* 大小 */
            if ($v > 24) {
                $tmp['dx'] = ($i == 6) ? 116 : 100;
            } else {
                $tmp['dx'] = ($i == 6) ? 117 : 101;
            }
            /* 单双 */
            if (($v % 2) == 1) {
                $tmp['ds'] = ($i == 6) ? 118 : 102;
            } else {
                $tmp['ds'] = ($i == 6) ? 119 : 103;
            }
            /* 和大和小 */
            if (($v % 10) + intval($v / 10) > 6) {
                $tmp['he_dx'] = 104;
            } else {
                $tmp['he_dx'] = 105;
            }
            /* 和单和双 */
            if ((($v % 10) + intval($v / 10)) % 2 == 1) {
                $tmp['he_ds'] = 106;
            } else {
                $tmp['he_ds'] = 107;
            }
            /* 尾大尾小 */
            if (($v % 10) > 4) {
                $tmp['w_dx'] = 112;
            } else {
                $tmp['w_dx'] = 113;
            }
            /* 大单小单 */
            if ($v > 24 && $v % 2 == 1) {
                $tmp['ddxs'] = 108;
            } elseif ($v < 25 && $v % 2 == 1) {
                $tmp['ddxs'] = 109;
            }
            /* 大双小双 */
            if ($v > 24 && $v % 2 == 0) {
                $tmp['ddxs'] = 110;
            } elseif ($v < 25 && $v % 2 == 0) {
                $tmp['ddxs'] = 111;
            }
            /* 尾数 */
            $wins_balls['ws'][] = $this->config_balls['ws'][$v % 10];
            $wins_balls['dxds'][] = $tmp;
            $wins_balls['he'] += $v;
            $i++;
        }
    
        /* 总和大小 */
        if ($wins_balls['he'] > 174) {
            $wins_balls['he_dxds']['dx'] = 120;
        } else {
            $wins_balls['he_dxds']['dx'] = 121;
        }
        /* 总和单双 */
        if (($wins_balls['he'] % 2) == 1) {
            $wins_balls['he_dxds']['ds'] = 122;
        } else {
            $wins_balls['he_dxds']['ds'] = 123;
        }
    
        /* 总肖 */
        $tmp = array_unique($wins_balls['sx_code']);
        $c = count($tmp);
        if ($c < 5) {
            $wins_balls['zhx']['he'] = $this->config_balls['zhx'][0];
        } else {
            $wins_balls['zhx']['he'] = $this->config_balls['zhx'][$c - 4];
        }
        if ($c % 2 == 1) {
            $wins_balls['zhx']['ds'] = $this->config_balls['zhx'][4];
        } else {
            $wins_balls['zhx']['ds'] = $this->config_balls['zhx'][5];
        }
    
        /* 特码 头数 半波 半半波 */
        $wins_balls['tm_ts'] = $this->config_balls['tws'][10 + intval($wins_balls['base'][6] / 10)];
        if ($wins_balls['sb_code'][6] == $this->config_balls['sb'][0]) {
            /* 半波 红单双 */
            if (($wins_balls['base'][6] % 2) == 1) {
                $wins_balls['tm_bb']['ds'] = $this->config_balls['bb'][0];
            } else {
                $wins_balls['tm_bb']['ds'] = $this->config_balls['bb'][1];
            }
            /* 半波 红大小 */
            if ($wins_balls['base'][6] > 24) {
                $wins_balls['tm_bb']['dx'] = $this->config_balls['bb'][2];
                /* 半半波 红大单大双 */
                if (($wins_balls['base'][6] % 2) == 1) {
                    $wins_balls['tm_bbb'] = $this->config_balls['bbb'][0];
                } else {
                    $wins_balls['tm_bbb'] = $this->config_balls['bbb'][1];
                }
            } else {
                $wins_balls['tm_bb']['dx'] = $this->config_balls['bb'][3];
                /* 半半波 红小单小双 */
                if (($wins_balls['base'][6] % 2) == 1) {
                    $wins_balls['tm_bbb'] = $this->config_balls['bbb'][2];
                } else {
                    $wins_balls['tm_bbb'] = $this->config_balls['bbb'][3];
                }
            }
        } elseif ($wins_balls['sb_code'][6] == $this->config_balls['sb'][1]) {
            /* 绿单双 */
            if (($wins_balls['base'][6] % 2) == 1) {
                $wins_balls['tm_bb']['ds'] = $this->config_balls['bb'][4];
            } else {
                $wins_balls['tm_bb']['ds'] = $this->config_balls['bb'][5];
            }
            /* 绿大小 */
            if ($wins_balls['base'][6] > 24) {
                $wins_balls['tm_bb']['dx'] = $this->config_balls['bb'][6];
                /* 半半波 绿大单大双 */
                if (($wins_balls['base'][6] % 2) == 1) {
                    $wins_balls['tm_bbb'] = $this->config_balls['bbb'][4];
                } else {
                    $wins_balls['tm_bbb'] = $this->config_balls['bbb'][5];
                }
            } else {
                $wins_balls['tm_bb']['dx'] = $this->config_balls['bb'][7];
                /* 半半波 绿小单小双 */
                if (($wins_balls['base'][6] % 2) == 1) {
                    $wins_balls['tm_bbb'] = $this->config_balls['bbb'][6];
                } else {
                    $wins_balls['tm_bbb'] = $this->config_balls['bbb'][7];
                }
            }
        } else {
            /* 蓝单双 */
            if (($wins_balls['base'][6] % 2) == 1) {
                $wins_balls['tm_bb']['ds'] = $this->config_balls['bb'][8];
            } else {
                $wins_balls['tm_bb']['ds'] = $this->config_balls['bb'][9];
            }
            /* 蓝大小 */
            if ($wins_balls['base'][6] > 24) {
                $wins_balls['tm_bb']['dx'] = $this->config_balls['bb'][10];
                /* 半半波 蓝大单大双 */
                if (($wins_balls['base'][6] % 2) == 1) {
                    $wins_balls['tm_bbb'] = $this->config_balls['bbb'][8];
                } else {
                    $wins_balls['tm_bbb'] = $this->config_balls['bbb'][9];
                }
            } else {
                $wins_balls['tm_bb']['dx'] = $this->config_balls['bb'][11];
                /* 半半波 蓝小单小双 */
                if (($wins_balls['base'][6] % 2) == 1) {
                    $wins_balls['tm_bbb'] = $this->config_balls['bbb'][10];
                } else {
                    $wins_balls['tm_bbb'] = $this->config_balls['bbb'][11];
                }
            }
        }
        
        /* 7色波 */
        $tmp = [$this->config_balls['7sb'][0] => 0, $this->config_balls['7sb'][1] => 0, $this->config_balls['7sb'][2] => 0];
        for ($i = 0; $i < 6; $i++) {
            $tmp[$wins_balls['sb_code'][$i]] += 1;
        }
        $tmp[$wins_balls['sb_code'][6]] += 1.5;
        $c_b = max($tmp);
        $c_s = min($tmp);
        /* 7色波 和局 3 3 1.5 */
        if ($c_b == 3 && ($c_s > 1 && $c_s < 2)) {
            $wins_balls['7sb'] =  $this->config_balls['7sb'][3];
        } else {
            foreach ($tmp as $k => $v) {
                if ($c_b == $v) {
                    $wins_balls['7sb'] =  $k;
                }
            }
        }
    
        /* 7码 5行 */
        $dx_d = 0;  /* 大小-大 个数 */
        $ds_d = 0;  /* 单双-单 个数 */
        for ($i = 0; $i < 7; $i++) {
            /* 大? */
            if ($wins_balls['dxds'][$i]['dx'] == $this->config_balls['zm_16'][0]) {
                $dx_d += 1;
            }
            /* 单? */
            if ($wins_balls['dxds'][$i]['ds'] == $this->config_balls['zm_16'][2]) {
                $ds_d += 1;
            }
        }
        $dx_d = ($wins_balls['dxds'][6]['dx'] == 116) ? $dx_d + 1 : $dx_d;
        $ds_d = ($wins_balls['dxds'][6]['ds'] == 118) ? $ds_d + 1 : $ds_d;
        $wins_balls['7m'] = ['ds' => $this->config_balls['7m'][$ds_d], 'dx' => $this->config_balls['7m'][$dx_d + 8]];
        
        return $wins_balls;
    } /* }}} */
    
    /**
     * @brief 两面_其他
     *      每单1注，每注1球
     *          $bet = "[\"1570329144415671\",1,\"101,104\",2,2,4,\"1.98\",0]";
     *          $bet = "[\"1570329155205234\",1,\"100\",2,1,2,\"1.98\",0]";
     *          特码[49为和] 大小 单双 和单和双 和大和小
     *          全码[无和局] 总单总双总大总小
     * @access public
     * @link http://www.gc360.com/orders/bet/15/1908
     * @param 
     * @return 
     *      中奖，和局，未中奖
     *      ret = array('win_contents'=>array(array(1,3)), 'win_counts'=>1, 'price_sum'=>3.9, 'status'=>STATUS_WIN);
     *      ret = array('win_contents'=>array(), 'win_counts'=>0, 'price_sum'=>4, 'status'=>STATUS_HE);
     *      ret = null;
     */
    public function settlement_lm_qt(& $bet = array(), & $lottery = array()) /* {{{ */
    {
        /* 取球号 */
        $balls = get_balls($bet[2]);
        $ret = null;
        /* 每单1注，每注1球。 */
        $v = $balls[0][0];
        /* 中 总大总小 */
        if ($v == $lottery['he_dxds']['dx']) {
            $ret['win_contents'][] = array($v);
        } elseif ($v == $lottery['he_dxds']['ds']) {
        /* 中 总单总双 */
            $ret['win_contents'][] = array($v);
        }
    
        if ($lottery['base'][6] != '49') {
            /* 中 特码大小 */
            if ($v == $lottery['dxds'][6]['dx']) {
                $ret['win_contents'][] = array($v);
            } elseif ($v == $lottery['dxds'][6]['ds']) {
            /* 中 特码单双 */
                $ret['win_contents'][] = array($v);
            } elseif ($v == $lottery['dxds'][6]['he_dx']) {
            /* 中 特码和大和小 */
                $ret['win_contents'][] = array($v);
            } elseif ($v == $lottery['dxds'][6]['he_ds']) {
            /* 中 特码和单和双 */
                $ret['win_contents'][] = array($v);
            }
        }
    
        /* 返回 中奖 或 和 */
        if (!in_array($v, array(120, 121, 122, 123)) && $lottery['base'][6] == '49') {
            $ret['win_counts'] = 0;
            $ret['price_sum'] = $bet[5];
            $ret['status'] = STATUS_HE;
        } elseif (!empty($ret['win_contents'])) {
            $ret['win_counts'] = count($ret['win_contents']);
            $ret['price_sum'] = $bet[3] * $bet[6] * $ret['win_counts'];
            $ret['status'] = STATUS_WIN;
        }
        return $ret;
    } /* }}} */
    
    /**
     * @brief 两面_正1
     *      每单同赔率可多注，不同赔率需分单，每注1球
     *      两面正码1-6：大小单双，和大和小和单和双
     * @access public/protected 
     * @param 
     * @return 
     */
    public function settlement_lm_z1(& $bet = array(), & $lottery = array(), $i = 0) /* {{{ */
    {
        /* 取球号 */
        $balls = get_balls($bet[2]);
        $ret = null;
        /* 1笔组合注单可能中多注。 */
        foreach ($balls[0] as $v) {
            if ($lottery['base'][$i] == '49') {
                break;
            }
            /* 中 正码 $i 大小 */
            if ($v == $lottery['dxds'][$i]['dx']) {
                $ret['win_contents'][] = array($v);
            }
            /* 中 正码单双 */
            if ($v == $lottery['dxds'][$i]['ds']) {
                $ret['win_contents'][] = array($v);
            }
            /* 中 正码和大和小 */
            if ($v == $lottery['dxds'][$i]['he_dx']) {
                $ret['win_contents'][] = array($v);
            }
            /* 中 正码和单和双 */
            if ($v == $lottery['dxds'][$i]['he_ds']) {
                $ret['win_contents'][] = array($v);
            }
        }
    
        /* 返回 中奖 或 和 */
        if ($lottery['base'][$i] == '49') {
            $ret['win_counts'] = 0;
            $ret['price_sum'] = $bet[5];
            $ret['status'] = STATUS_HE;
        } elseif (!empty($ret['win_contents'])) {
            $ret['win_counts'] = count($ret['win_contents']);
            $ret['price_sum'] = $bet[3] * $bet[6] * $ret['win_counts'];
            $ret['status'] = STATUS_WIN;
        }
        return $ret;
    } /* }}} */
    
    /**
     * @brief 两面_正2
     *      每单同赔率可多注，不同赔率需分单，每注1球
     * @access public/protected 
     * @param 
     * @return 
     */
    public function settlement_lm_z2(& $bet = array(), & $lottery = array()) /* {{{ */
    {
        return $this->settlement_lm_z1($bet, $lottery, 1);
    } /* }}} */
    
    /**
     * @brief 两面_正3
     *      每单同赔率可多注，不同赔率需分单，每注1球
     * @access public/protected 
     * @param 
     * @return 
     */
    public function settlement_lm_z3(& $bet = array(), & $lottery = array()) /* {{{ */
    {
        return $this->settlement_lm_z1($bet, $lottery, 2);
    } /* }}} */
    
    /**
     * @brief 两面_正4
     *      每单同赔率可多注，不同赔率需分单，每注1球
     * @access public/protected 
     * @param 
     * @return 
     */
    public function settlement_lm_z4(& $bet = array(), & $lottery = array()) /* {{{ */
    {
        return $this->settlement_lm_z1($bet, $lottery, 3);
    } /* }}} */
    
    /**
     * @brief 两面_正5
     *      每单同赔率可多注，不同赔率需分单，每注1球
     * @access public/protected 
     * @param 
     * @return 
     */
    public function settlement_lm_z5(& $bet = array(), & $lottery = array()) /* {{{ */
    {
        return $this->settlement_lm_z1($bet, $lottery, 4);
    } /* }}} */
    
    /**
     * @brief 两面_正6
     *      每单同赔率可多注，不同赔率需分单，每注1球
     * @access public/protected 
     * @param 
     * @return 
     */
    public function settlement_lm_z6(& $bet = array(), & $lottery = array()) /* {{{ */
    {
        return $this->settlement_lm_z1($bet, $lottery, 5);
    } /* }}} */
    
    /**
     * @brief 特码AB_特码A
     *      每单同赔率可多注，不同赔率需分单，每注1球，
     *      无和局
     *      特码AB 
     * @access public/protected 
     * @param 
     * @return 
     */
    public function settlement_tmab_ta(& $bet = array(), & $lottery = array()) /* {{{ */
    {
        /* 取球号 */
        $balls = get_balls($bet[2]);
        $ret = null;
        /* 1笔组合注单可能中多注。 */
        foreach ($balls[0] as $v) {
            /* 中 特码 */
            if ($v == $lottery['base'][6]) {
                $ret['win_contents'][] = array($v);
            }
        }
    
        if (!empty($ret['win_contents'])) {
            $ret['win_counts'] = count($ret['win_contents']);
            $ret['price_sum'] = $bet[3] * $bet[6] * $ret['win_counts'];
            $ret['status'] = STATUS_WIN;
        }
        return $ret;
    } /* }}} */
    
    /**
     * @brief 特码AB_特码B
     *      每单同赔率可多注，不同赔率需分单，每注1球
     *      无和局
     * @access public/protected 
     * @param 
     * @return 
     */
    public function settlement_tmab_tb(& $bet = array(), & $lottery = array()) /* {{{ */
    {
        return $this->settlement_tmab_ta($bet, $lottery);
    } /* }}} */
    
    /**
     * @brief 特码AB_特码其他
     *      每单1注，每注1球
     *      49为和局
     *      其他：和大和小和单和双，大单小单大双小双，尾大尾小，特大特小特单特双
     * @access public/protected 
     * @param 
     * @return 
     */
    public function settlement_tmab_qt(& $bet = array(), & $lottery = array()) /* {{{ */
    {
        $ret = null;
        /* 取球号 */
        $balls = get_balls($bet[2]);
        /* 每单1注，每注1球。 */
        $v = $balls[0][0];
        if ($lottery['base'][6] != '49') {
            /* 中 特码大小 */
            if ($v == $lottery['dxds'][6]['dx']) {
                $ret['win_contents'][] = array($v);
            } elseif ($v == $lottery['dxds'][6]['ds']) {
            /* 中 特码单双 */
                $ret['win_contents'][] = array($v);
            } elseif ($v == $lottery['dxds'][6]['he_dx']) {
            /* 中 特码和大和小 */
                $ret['win_contents'][] = array($v);
            } elseif ($v == $lottery['dxds'][6]['he_ds']) {
            /* 中 特码和单和双 */
                $ret['win_contents'][] = array($v);
            } elseif ($v == $lottery['dxds'][6]['w_dx']) {
            /* 中 特码尾大尾小 */
                $ret['win_contents'][] = array($v);
            } elseif ($v == $lottery['dxds'][6]['ddxs']) {
            /* 中 特码大单小单 大双小双 */
                $ret['win_contents'][] = array($v);
            }
        }
    
        /* 返回 中奖 或 和 */
        if ($lottery['base'][6] == '49') {
            $ret['win_counts'] = 0;
            $ret['price_sum'] = $bet[5];
            $ret['status'] = STATUS_HE;
        } elseif (!empty($ret['win_contents'])) {
            $ret['win_counts'] = count($ret['win_contents']);
            $ret['price_sum'] = $bet[3] * $bet[6] * $ret['win_counts'];
            $ret['status'] = STATUS_WIN;
        }
        return $ret;
    } /* }}} */
    
    /**
     * @brief 正码_正码
     *      每单同赔率可多注，不同赔率需分单，每注1球
     *      无和局
     * @access public/protected 
     * @param 
     * @return 
     */
    public function settlement_zm_zm(& $bet = array(), & $lottery = array()) /* {{{ */
    {
        /* 取球号 */
        $balls = get_balls($bet[2]);
        $ret = null;
        /* 1笔组合注单可能中多注。 */
        foreach ($balls[0] as $v) {
            /* 中 正码 */
            if ($v != $lottery['base'][6] && in_array($v, $lottery['base'])) {
                $ret['win_contents'][] = array($v);
            }
        }
    
        if (!empty($ret['win_contents'])) {
            $ret['win_counts'] = count($ret['win_contents']);
            $ret['price_sum'] = $bet[3] * $bet[6] * $ret['win_counts'];
            $ret['status'] = STATUS_WIN;
        }
        return $ret;
    } /* }}} */
    
    /**
     * @brief 正码特_正码特1
     *      每单同赔率可多注，不同赔率需分单，每注1球
     * @access public/protected 
     * @param 
     * @return 
     */
    public function settlement_zmt_z1t(& $bet = array(), & $lottery = array(), $i = 0) /* {{{ */
    {
        /* 取球号 */
        $balls = get_balls($bet[2]);
        $ret = null;
        /* 1笔组合注单只能中1注。 */
        /* 中 正码特 $i */
        if (in_array($lottery['base'][$i], $balls[0])) {
            $ret['win_contents'][] = array($lottery['base'][$i]);
        }
    
        if (!empty($ret['win_contents'])) {
            $ret['win_counts'] = count($ret['win_contents']);
            $ret['price_sum'] = $bet[3] * $bet[6] * $ret['win_counts'];
            $ret['status'] = STATUS_WIN;
        }
        return $ret;
    } /* }}} */
    
    /**
     * @brief 正码特_正码特2
     *      每单同赔率可多注，不同赔率需分单，每注1球
     * @access public/protected 
     * @param 
     * @return 
     */
    public function settlement_zmt_z2t(& $bet = array(), & $lottery = array()) /* {{{ */
    {
        return $this->settlement_zmt_z1t($bet, $lottery, 1);
    } /* }}} */
    
    /**
     * @brief 正码特_正码特3
     *      每单同赔率可多注，不同赔率需分单，每注1球
     * @access public/protected 
     * @param 
     * @return 
     */
    public function settlement_zmt_z3t(& $bet = array(), & $lottery = array()) /* {{{ */
    {
        return $this->settlement_zmt_z1t($bet, $lottery, 2);
    } /* }}} */
    
    /**
     * @brief 正码特_正码特4
     *      每单同赔率可多注，不同赔率需分单，每注1球
     * @access public/protected 
     * @param 
     * @return 
     */
    public function settlement_zmt_z4t(& $bet = array(), & $lottery = array()) /* {{{ */
    {
        return $this->settlement_zmt_z1t($bet, $lottery, 3);
    } /* }}} */
    
    /**
     * @brief 正码特_正码特5
     *      每单同赔率可多注，不同赔率需分单，每注1球
     * @access public/protected 
     * @param 
     * @return 
     */
    public function settlement_zmt_z5t(& $bet = array(), & $lottery = array()) /* {{{ */
    {
        return $this->settlement_zmt_z1t($bet, $lottery, 4);
    } /* }}} */
    
    /**
     * @brief 正码特_正码特6
     *      每单同赔率可多注，不同赔率需分单，每注1球
     * @access public/protected 
     * @param 
     * @return 
     */
    public function settlement_zmt_z6t(& $bet = array(), & $lottery = array()) /* {{{ */
    {
        return $this->settlement_zmt_z1t($bet, $lottery, 5);
    } /* }}} */
    
    /**
     * @brief 正码1-6_正码1
     *      正码1-6 大小单双，和大和小和单和双，尾大尾小，红绿蓝波
     *      每单1，每注1球
     *      49为和局，选 红绿蓝 时无和局。
     * @access public/protected 
     * @param 
     * @return 
     */
    public function settlement_zm16_zm1(& $bet = array(), & $lottery = array(), $i = 0) /* {{{ */
    {
        /* 取球号 */
        $balls = get_balls($bet[2]);
        $ret = null;
        /* 每单1注，每注1球。 */
        $v = $balls[0][0];
        /* 中 正码 红绿蓝波 (无和局) */
        if ($v == $lottery['sb_code'][$i]) {
            $ret['win_contents'][] = array($v);
        }
        /* 正码 $i 大小单双... 49 为和 */
        if ($lottery['base'][$i] != '49') {
            /* 中 正码大小 */
            if ($v == $lottery['dxds'][$i]['dx']) {
                $ret['win_contents'][] = array($v);
            } elseif ($v == $lottery['dxds'][$i]['ds']) {
            /* 中 正码单双 */
                $ret['win_contents'][] = array($v);
            } elseif ($v == $lottery['dxds'][$i]['he_dx']) {
            /* 中 正码和大和小 */
                $ret['win_contents'][] = array($v);
            } elseif ($v == $lottery['dxds'][$i]['he_ds']) {
            /* 中 正码和单和双 */
                $ret['win_contents'][] = array($v);
            } elseif ($v == $lottery['dxds'][$i]['w_dx']) {
            /* 中 正码尾大尾小 */
                $ret['win_contents'][] = array($v);
            }
        }
    
        /* 返回 中奖 或 和 */
        if (!in_array($v, $this->config_balls['sb']) && $lottery['base'][$i] == '49') {
            $ret['win_counts'] = 0;
            $ret['price_sum'] = $bet[5];
            $ret['status'] = STATUS_HE;
        } elseif (!empty($ret['win_contents'])) {
            $ret['win_counts'] = count($ret['win_contents']);
            $ret['price_sum'] = $bet[3] * $bet[6] * $ret['win_counts'];
            $ret['status'] = STATUS_WIN;
        }
        return $ret;
    } /* }}} */
    
    /**
     * @brief 正码1-6_正码2
     *      每单1，每注1球
     * @access public/protected 
     * @param 
     * @return 
     */
    public function settlement_zm16_zm2(& $bet = array(), & $lottery = array()) /* {{{ */
    {
        return $this->settlement_zm16_zm1($bet, $lottery, 1);
    } /* }}} */
    
    
    /**
     * @brief 正码1-6_正码3
     *      每单1，每注1球
     * @access public/protected 
     * @param 
     * @return 
     */
    public function settlement_zm16_zm3(& $bet = array(), & $lottery = array()) /* {{{ */
    {
        return $this->settlement_zm16_zm1($bet, $lottery, 2);
    } /* }}} */
    
    
    /**
     * @brief 正码1-6_正码4
     *      每单1，每注1球
     * @access public/protected 
     * @param 
     * @return 
     */
    public function settlement_zm16_zm4(& $bet = array(), & $lottery = array()) /* {{{ */
    {
        return $this->settlement_zm16_zm1($bet, $lottery, 3);
    } /* }}} */
    
    
    /**
     * @brief 正码1-6_正码5
     *      每单1，每注1球
     * @access public/protected 
     * @param 
     * @return 
     */
    public function settlement_zm16_zm5(& $bet = array(), & $lottery = array()) /* {{{ */
    {
        return $this->settlement_zm16_zm1($bet, $lottery, 4);
    } /* }}} */
    
    
    /**
     * @brief 正码1-6_正码6
     *      每单1，每注1球
     * @access public/protected 
     * @param 
     * @return 
     */
    public function settlement_zm16_zm6(& $bet = array(), & $lottery = array()) /* {{{ */
    {
        return $this->settlement_zm16_zm1($bet, $lottery, 5);
    } /* }}} */
    
    /**
     * @brief 正码过关
     *      每单1注，每注2～6球，
     *      49为和局，选 红绿蓝 时无和局。
     *      每球都正确则中奖，有球为和局时，此球赔率作 1 算，此注中奖赔率为各球赔率之积。
     *          $bet = "[\"1570329155205234\",1,\"100||104|104||\",2,1,2,\"1.97,,1.97,1.97,,\",0]";
     *      改为以下方式：
     *      {"zmgg":["903705271044127841","108","105||105||124|","2",1,"2","1.97,1.97,2.7","0.0"]};
     * @access public/protected 
     * @param 
     * @return 
     */
    public function settlement_zmgg(& $bet = array(), & $lottery = array()) /* {{{ */
    {
        /* 取球号 */
        $balls = get_balls($bet[2]);
        $ret = null;
        /* 每单1注，每注2~6球，开奖位每位最多1球，全部下对为中奖，只要有一个错则不中奖。 */
        $rates_r = explode(',', $bet[6]);
        $rates = [];
        $b_c = count($balls);
        $b = 0;     /* 有效的下注球数 */
        $w = 0;     /* 中奖的球数 */
        $rate = 1;  /* 总赔率 */
        for ($i = 0; $i < $b_c; $i++) {
            if (empty($balls[$i][0])) {
                $rates[$i] = 1;
                $ret['win_contents'][0][] = '';
                continue;
            }
            $v = $balls[$i][0];
            $rates[$i] = $rates_r[$b];
            $b++;   /* 有效的下注球数 */
            /* 中 正码 红绿蓝波 (无和局) */
            if ($v == $lottery['sb_code'][$i]) {
                $ret['win_contents'][0][] = $v;
                $w++;
            }
            /* 正码 $i 大小单双... 49 为和 */
            if ($lottery['base'][$i] != '49') {
                /* 中 正码大小 */
                if ($v == $lottery['dxds'][$i]['dx']) {
                    $ret['win_contents'][0][] = $v;
                    $w++;
                } elseif ($v == $lottery['dxds'][$i]['ds']) {
                /* 中 正码单双 */
                    $ret['win_contents'][0][] = $v;
                    $w++;
                } elseif ($v == $lottery['dxds'][$i]['he_dx']) {
                /* 中 正码和大和小 */
                    $ret['win_contents'][0][] = $v;
                    $w++;
                } elseif ($v == $lottery['dxds'][$i]['he_ds']) {
                /* 中 正码和单和双 */
                    $ret['win_contents'][0][] = $v;
                    $w++;
                } elseif ($v == $lottery['dxds'][$i]['w_dx']) {
                /* 中 正码尾大尾小 */
                    $ret['win_contents'][0][] = $v;
                    $w++;
                }
            } elseif (!in_array($v, $this->config_balls['sb']) && $lottery['base'][$i] == '49') {
                /* 和局，赔率不算 */
                $rates[$i] = 1;
                $ret['win_contents'][0][] = $v;
                $w++;
            }
            $rate = $rate * $rates[$i];
        }
        /* 返回 中奖 或 不中 */
        if ($b > 1 && $b == $w) {
            $ret['win_counts'] = 1;
            $ret['price_sum'] = $bet[3] * $rate * $ret['win_counts'];
            $ret['status'] = STATUS_WIN;
        } else {
            $ret = null;
        }
        return $ret;
    } /* }}} */
    
    /**
     * @brief 连码_四全中
     *      每单多注，每注4球
     *      选择投注号码每四个为一组（四个或四个以上），兑奖号为正码，
     *      如四个号码都在开奖号码的正码里面，视为中奖，其他情形都视为不中奖 。无和局。[1单可多注，1注4球，可中多注15]
     * @access public/protected 
     * @param 
     * @return 
     */
    public function settlement_lm_4qz(& $bet = array(), & $lottery = array(), $m = 4) /* {{{ */
    {
        /* 取球号 */
        $balls = get_balls($bet[2]);
        $ret = null;
        $wins = array();
        $lottery_zm = $lottery['base'];
        unset($lottery_zm[6]);
        /* 1笔组合注单可能中多注。 */
        foreach ($balls[0] as $v) {
            /* 中 正码 */
            if (in_array($v, $lottery_zm)) {
                $wins[] = $v;
            }
        }
        $c = count($wins);
        if ($c >= $m) {
            $ret['win_contents'] = combination($wins, $m);
        }
        if (!empty($ret['win_contents'])) {
            $ret['win_counts'] = count($ret['win_contents']);
            $ret['price_sum'] = $bet[3] * $bet[6] * $ret['win_counts'];
            $ret['status'] = STATUS_WIN;
        }
        return $ret;
    } /* }}} */
    
    /**
     * @brief 连码_三全中
     *      每单多注，每注3球
     * @access public/protected 
     * @param 
     * @return 
     */
    public function settlement_lm_3qz(& $bet = array(), & $lottery = array()) /* {{{ */
    {
        return $this->settlement_lm_4qz($bet, $lottery, 3);
    } /* }}} */
    
    /**
     * @brief 连码_三中二
     *      每单多注，每注3球，可中两种：三中二之中三(赔率大)，三中二之中二(赔率小)，
     * @access public/protected 
     * @param 
     * @return 
     */
    public function settlement_lm_3z2(& $bet = array(), & $lottery = array()) /* {{{ */
    {
        /* 取球号 */
        $balls = get_balls($bet[2]);
        $ret = null;
        $bets = combination($balls[0], 3);
        $lottery_zm = $lottery['base'];
        unset($lottery_zm[6]);
        $wins_3 = array();  /* 中三 */
        $wins_2 = array();  /* 中二 */
        /* 1笔组合注单可能中多注。 */
        foreach ($bets as $v) {
            /* 中 正码 */
            if (in_array($v[0], $lottery_zm)) {
                if (in_array($v[1], $lottery_zm)) {
                    if (in_array($v[2], $lottery_zm)) {
                        /* 中三 */
                        $wins_3[] = $v;
                    } else {
                        /* 中二 */
                        $wins_2[] = $v;
                    }
                } else {
                    if (in_array($v[2], $lottery_zm)) {
                        /* 中二 */
                        $wins_2[] = $v;
                    }
                }
            } else {
                if (in_array($v[1], $lottery_zm)) {
                    if (in_array($v[2], $lottery_zm)) {
                        /* 中二 */
                        $wins_2[] = $v;
                    }
                }
            }
        }
        $c_3 = count($wins_3);
        $c_2 = count($wins_2);
        if ($c_3 > 0 || $c_2 > 0) {
            $ret['win_contents'] = array_merge($wins_3, $wins_2);
        }
        if (!empty($ret['win_contents'])) {
            $rates = explode(',', $bet[6]);
            $rate_3 = $rates[0] > $rates[1] ? $rates[0] : $rates[1];
            $rate_2 = $rates[0] < $rates[1] ? $rates[0] : $rates[1];
            $ret['win_counts'] = count($ret['win_contents']);
            $ret['price_sum'] = ($bet[3] * $rate_3 * $c_3) + ($bet[3] * $rate_2 * $c_2);
            $ret['status'] = STATUS_WIN;
        }
        return $ret;
    } /* }}} */
    
    /**
     * @brief 连码_二全中
     *      每单多注，每注2球，
     * @access public/protected 
     * @param 
     * @return 
     */
    public function settlement_lm_2qz(& $bet = array(), & $lottery = array()) /* {{{ */
    {
        return $this->settlement_lm_4qz($bet, $lottery, 2);
    } /* }}} */
    
    /**
     * @brief 连码_二中特
     *      每单多注，每注2球，
     *      所投注的每二个号码为一组合，二个号码都是开奖号码之正码，叫二中特之中二（赔率比二中特之中特高）；
     *      若其中一个是正码，一个是特别号码，叫二中特之中特；其余情形视为不中奖。无和局。[1单可多注，1注2球，可中多注]
     * @access public/protected 
     * @param 
     * @return 
     */
    public function settlement_lm_2zt(& $bet = array(), & $lottery = array()) /* {{{ */
    {
        /* 取球号 */
        $balls = get_balls($bet[2]);
        $ret = null;
        $lottery_zm = $lottery['base'];
        unset($lottery_zm[6]);
        $wins_2 = array();  /* 中二 */
        $wins_t = 0;        /* 中特 */
        /* 1笔组合注单可能中多注。 */
        foreach ($balls[0] as $v) {
            /* 中 正码 */
            if (in_array($v, $lottery_zm)) {
                $wins_2[] = $v;
            }
            /* 中 特码 */
            if ($v == $lottery['base'][6]) {
                $wins_t = $v;
            }
        }
        $c = count($wins_2);
        $c_2 = 0;   /* 中二注数 */
        $c_t = 0;   /* 中特注数 */
        /* 中二 */
        if ($c >= 2) {
            $ret['win_contents'] = combination($wins_2, 2);
            $c_2 = count($ret['win_contents']);
        }
        /* 中特 */
        if ($c >= 1 && $wins_t > 0) {
            for ($i = 0; $i < $c; $i++) {
                $ret['win_contents'][] = array($wins_2[$i], $wins_t);
            }
            $c_t = $c;
        }
    
        if (!empty($ret['win_contents'])) {
            $rates = explode(',', $bet[6]);
            $rate_2 = $rates[0] > $rates[1] ? $rates[0] : $rates[1];
            $rate_t = $rates[0] < $rates[1] ? $rates[0] : $rates[1];
            $ret['win_counts'] = count($ret['win_contents']);
            $ret['price_sum'] = ($bet[3] * $rate_2 * $c_2) + ($bet[3] * $rate_t * $c_t);
            $ret['status'] = STATUS_WIN;
        }
        return $ret;
    } /* }}} */
    
    /**
     * @brief 连码_特串
     *      每单多注，每注2球，
     *      所投注的每二个号码为一组合，其中一个是正码，一个是特别号码，视为中奖，其余情形视为不中奖（含二个号码都是正码之情形）。
     *      无和局。[1单可多注，1注2球，可中多注]
     * @access public/protected 
     * @param 
     * @return 
     */
    public function settlement_lm_tc(& $bet = array(), & $lottery = array()) /* {{{ */
    {
        /* 取球号 */
        $balls = get_balls($bet[2]);
        $ret = null;
        $lottery_zm = $lottery['base'];
        unset($lottery_zm[6]);
        $wins_z = array();  /* 中正码 */
        $wins_t = 0;        /* 中特码 */
        /* 1笔组合注单可能中多注。 */
        foreach ($balls[0] as $v) {
            /* 中 正码 */
            if (in_array($v, $lottery_zm)) {
                $wins_z[] = $v;
            }
            /* 中 特码 */
            if ($v == $lottery['base'][6]) {
                $wins_t = $v;
            }
        }
        $c = count($wins_z);
        /* 中特串 */
        if ($c >= 1 && $wins_t > 0) {
            for ($i = 0; $i < $c; $i++) {
                $ret['win_contents'][] = array($wins_z[$i], $wins_t);
            }
        }
    
        if (!empty($ret['win_contents'])) {
            $ret['win_counts'] = $c;
            $ret['price_sum'] = $bet[3] * $bet[6] * $ret['win_counts'];
            $ret['status'] = STATUS_WIN;
        }
        return $ret;
    } /* }}} */
    
    /**
     * @brief 连肖_二肖连
     *      每单同赔率可多注，不同赔率需分单，每注2球，每单最多6球
     *      高赔率与低赔率组合为一注时，以低赔率为当注标准赔率
     *      选择二个生肖为一投注组合进行下注。该注的二个生肖必须在当期开出的7个开奖号码相对应的生肖中，视为中奖。
     *      无和局。[1单可多注，最好1注，1注2球，可中多注]
     * @access public/protected 
     * @param 
     * @return 
     */
    public function settlement_lx_2xl(& $bet = array(), & $lottery = array(), $m = 2) /* {{{ */
    {
        /* 取球号 */
        $balls = get_balls($bet[2]);
        $ret = null;
        $wins = array();
        /* 1笔组合注单可能中多注。 */
        foreach ($balls[0] as $v) {
            /* 中 正码 */
            if (in_array($v, $lottery['sx_code'])) {
                $wins[] = $v;
            }
        }
        $c = count($wins);
        if ($c >= $m) {
            $ret['win_contents'] = combination($wins, $m);
        }
        if (!empty($ret['win_contents'])) {
            $ret['win_counts'] = count($ret['win_contents']);
            $ret['price_sum'] = $bet[3] * $bet[6] * $ret['win_counts'];
            $ret['status'] = STATUS_WIN;
        }
        return $ret;
    } /* }}} */
    
    /**
     * @brief 连肖_三肖连
     *      每单同赔率可多注，不同赔率需分单，每注3球，每单最多6球
     *      高赔率与低赔率组合为一注时，以低赔率为当注标准赔率
     * @access public/protected 
     * @param 
     * @return 
     */
    public function settlement_lx_3xl(& $bet = array(), & $lottery = array()) /* {{{ */
    {
        return $this->settlement_lx_2xl($bet, $lottery, 3);
    } /* }}} */
    
    /**
     * @brief 连肖_四肖连
     *      每单同赔率可多注，不同赔率需分单，每注4球，每单最多6球
     *      高赔率与低赔率组合为一注时，以低赔率为当注标准赔率
     * @access public/protected 
     * @param 
     * @return 
     */
    public function settlement_lx_4xl(& $bet = array(), & $lottery = array()) /* {{{ */
    {
        return $this->settlement_lx_2xl($bet, $lottery, 4);
    } /* }}} */
    
    /**
     * @brief 连肖_五肖连
     *      每单同赔率可多注，不同赔率需分单，每注5球，每单最多6球
     *      高赔率与低赔率组合为一注时，以低赔率为当注标准赔率
     * @access public/protected 
     * @param 
     * @return 
     */
    public function settlement_lx_5xl(& $bet = array(), & $lottery = array()) /* {{{ */
    {
        return $this->settlement_lx_2xl($bet, $lottery, 5);
    } /* }}} */
    
    /**
     * @brief 连尾_二尾碰
     *      每单同赔率可多注，不同赔率需分单，每注2球，每单最多6球
     *      高赔率与低赔率组合为一注时，以高赔率为当注标准赔率
     *      选择二个尾数为一投注组合进行下注。该注的二个尾数必须在当期开出的7个开奖号码相对应的尾数中，视为中奖。
     *      无和局。[1单可多注，最好1注，1注2球，可中多注]
     * @access public/protected 
     * @param 
     * @return 
     */
    public function settlement_lw_2wp(& $bet = array(), & $lottery = array(), $m = 2) /* {{{ */
    {
        /* 取球号 */
        $balls = get_balls($bet[2]);
        $ret = null;
        $wins = array();
        /* 1笔组合注单可能中多注。 */
        foreach ($balls[0] as $v) {
            /* 中 尾数 */
            if (in_array($v, $lottery['ws'])) {
                $wins[] = $v;
            }
        }
        $c = count($wins);
        if ($c >= $m) {
            $ret['win_contents'] = combination($wins, $m);
        }
        if (!empty($ret['win_contents'])) {
            $ret['win_counts'] = count($ret['win_contents']);
            $ret['price_sum'] = $bet[3] * $bet[6] * $ret['win_counts'];
            $ret['status'] = STATUS_WIN;
        }
        return $ret;
    } /* }}} */
    
    /**
     * @brief 连尾_三尾碰
     *      每单同赔率可多注，不同赔率需分单，每注3球，每单最多6球
     *      高赔率与低赔率组合为一注时，以高赔率为当注标准赔率
     * @access public/protected 
     * @param 
     * @return 
     */
    public function settlement_lw_3wp(& $bet = array(), & $lottery = array()) /* {{{ */
    {
        return $this->settlement_lw_2wp($bet, $lottery, 3);
    } /* }}} */
    
    /**
     * @brief 连尾_四尾碰
     *      每单同赔率可多注，不同赔率需分单，每注4球，每单最多6球
     *      高赔率与低赔率组合为一注时，以高赔率为当注标准赔率
     * @access public/protected 
     * @param 
     * @return 
     */
    public function settlement_lw_4wp(& $bet = array(), & $lottery = array()) /* {{{ */
    {
        return $this->settlement_lw_2wp($bet, $lottery, 4);
    } /* }}} */
    
    /**
     * @brief 连尾_五尾碰
     *      每单同赔率可多注，不同赔率需分单，每注5球，每单最多6球
     *      高赔率与低赔率组合为一注时，以高赔率为当注标准赔率
     * @access public/protected 
     * @param 
     * @return 
     */
    public function settlement_lw_5wp(& $bet = array(), & $lottery = array()) /* {{{ */
    {
        return $this->settlement_lw_2wp($bet, $lottery, 5);
    } /* }}} */
    
    /**
     * @brief 自选不中_五不中
     *      每单多注，每注5球，每单最多12球
     *      挑选5个号码为一投注组合进行下注。当期开出的7个开奖号码都没有在该下注组合中，即视为中奖。
     *      如下注组合为1-2-3-4-5，开奖号码为6，7，8，9，10，11，12，即为中奖，如果开奖号码为5，6，7，8，9，10，11，则为不中奖。
     *      无和局。[1单可多注，1注5球，可中多注]
     * @access public/protected 
     * @param 
     * @return 
     */
    public function settlement_zxbz_5bz(& $bet = array(), & $lottery = array(), $m = 5) /* {{{ */
    {
        /* 取球号 */
        $balls = get_balls($bet[2]);
        $ret = null;
        $wins = array();
        /* 1笔组合注单可能中多注。 */
        foreach ($balls[0] as $v) {
            /* 不中 尾数 */
            if (!in_array($v, $lottery['base'])) {
                $wins[] = $v;
            }
        }
        $c = count($wins);
        if ($c >= $m) {
            $ret['win_contents'] = combination($wins, $m);
        }
        if (!empty($ret['win_contents'])) {
            $ret['win_counts'] = count($ret['win_contents']);
            $ret['price_sum'] = $bet[3] * $bet[6] * $ret['win_counts'];
            $ret['status'] = STATUS_WIN;
        }
        return $ret;
    } /* }}} */
    
    /**
     * @brief 自选不中_六不中
     *      每单多注，每注6球，每单最多12球
     * @access public/protected 
     * @param 
     * @return 
     */
    public function settlement_zxbz_6bz(& $bet = array(), & $lottery = array()) /* {{{ */
    {
        return $this->settlement_zxbz_5bz($bet, $lottery, 6);
    } /* }}} */
    
    /**
     * @brief 自选不中_七不中
     *      每单多注，每注7球，每单最多12球
     * @access public/protected 
     * @param 
     * @return 
     */
    public function settlement_zxbz_7bz(& $bet = array(), & $lottery = array()) /* {{{ */
    {
        return $this->settlement_zxbz_5bz($bet, $lottery, 7);
    } /* }}} */
    
    /**
     * @brief 自选不中_八不中
     *      每单多注，每注8球，每单最多12球
     * @access public/protected 
     * @param 
     * @return 
     */
    public function settlement_zxbz_8bz(& $bet = array(), & $lottery = array()) /* {{{ */
    {
        return $this->settlement_zxbz_5bz($bet, $lottery, 8);
    } /* }}} */
    
    /**
     * @brief 自选不中_九不中
     *      每单多注，每注9球，每单最多12球
     * @access public/protected 
     * @param 
     * @return 
     */
    public function settlement_zxbz_9bz(& $bet = array(), & $lottery = array()) /* {{{ */
    {
        return $this->settlement_zxbz_5bz($bet, $lottery, 9);
    } /* }}} */
    
    /**
     * @brief 自选不中_十不中
     *      每单多注，每注10球，每单最多12球
     * @access public/protected 
     * @param 
     * @return 
     */
    public function settlement_zxbz_10bz(& $bet = array(), & $lottery = array()) /* {{{ */
    {
        return $this->settlement_zxbz_5bz($bet, $lottery, 10);
    } /* }}} */
    
    /**
     * @brief 自选不中_十一不中
     *      每单多注，每注11球，每单最多14球
     * @access public/protected 
     * @param 
     * @return 
     */
    public function settlement_zxbz_11bz(& $bet = array(), & $lottery = array()) /* {{{ */
    {
        return $this->settlement_zxbz_5bz($bet, $lottery, 11);
    } /* }}} */
    
    /**
     * @brief 自选不中_十二不中
     *      每单多注，每注12球，每单最多14球
     * @access public/protected 
     * @param 
     * @return 
     */
    public function settlement_zxbz_12bz(& $bet = array(), & $lottery = array()) /* {{{ */
    {
        return $this->settlement_zxbz_5bz($bet, $lottery, 12);
    } /* }}} */
    
    /**
     * @brief 生肖_十二肖
     *      每单1注，每注1球，每单最多1球
     *      十二生肖(生肖，特肖，特码生肖)：若当期特别号，落在下注生肖范围内，视为中奖。如：开奖特码18为特码-龙，投注特码-龙即中奖。
     *      无和局。[1单1注，1注1球，中1注]
     * @access public/protected 
     * @param 
     * @return 
     */
    public function settlement_sx_12x(& $bet = array(), & $lottery = array()) /* {{{ */
    {
        /* 取球号 */
        $balls = get_balls($bet[2]);
        $ret = null;
        /* 每单1注，每注1球。 */
        $v = $balls[0][0];
        /* 中 特码 生肖 (无和局) */
        if ($v == $lottery['sx_code'][6]) {
            $ret['win_contents'][] = array($v);
        }
    
        if (!empty($ret['win_contents'])) {
            $ret['win_counts'] = count($ret['win_contents']);
            $ret['price_sum'] = $bet[3] * $bet[6] * $ret['win_counts'];
            $ret['status'] = STATUS_WIN;
        }
        return $ret;
    } /* }}} */
    
    /**
     * @brief 生肖_一肖
     *      每单1注，每注1球，每单最多1球
     *      当期开奖的全部号码(前6个号码和特码)，其中只要有一个球号在投注的生肖范围则中奖；
     *      没有一个球号在投注的生肖范围内，则不中奖；多个球号在投注生肖范围内，则中奖；
     *      但奖金不倍增，派彩只派一次，即不论同生肖号码出现一个或多个号码都只派一次。
     *      无和局。[1单1注，1注1球，中1注]
     * @access public/protected 
     * @param 
     * @return 
     */
    public function settlement_sx_1x(& $bet = array(), & $lottery = array()) /* {{{ */
    {
        /* 取球号 */
        $balls = get_balls($bet[2]);
        $ret = null;
        /* 每单1注，每注1球。 */
        $v = $balls[0][0];
        /* 中 全码 一肖，1注中1次 (无和局) */
        if (in_array($v, $lottery['sx_code'])) {
            $ret['win_contents'][] = array($v);
        }
    
        if (!empty($ret['win_contents'])) {
            $ret['win_counts'] = count($ret['win_contents']);
            $ret['price_sum'] = $bet[3] * $bet[6] * $ret['win_counts'];
            $ret['status'] = STATUS_WIN;
        }
        return $ret;
    } /* }}} */
    
    /**
     * @brief 生肖_正肖
     *      每单1注，每注1球，每单最多1球
     *      当期开奖的前6个号码(不含特码，不分先后顺序)，其中有一个球号在投注的生肖范围即算中奖。
     *      如果有多个球号开在投注生肖范围内，派彩金额将自动倍增。
     *      无和局。[1单1注，1注1球，中1注]
     * @access public/protected 
     * @param 
     * @return 
     */
    public function settlement_sx_zx(& $bet = array(), & $lottery = array()) /* {{{ */
    {
        /* 取球号 */
        $balls = get_balls($bet[2]);
        $ret = null;
        /* 每单1注，每注1球。 */
        $v = $balls[0][0];
        /* 中 正码 生肖，1注可中多次 (无和局) */
        for ($i = 0; $i < 6; $i++) {
            if ($v == $lottery['sx_code'][$i]) {
                $ret['win_contents'][] = array($v);
            }
        }
    
        if (!empty($ret['win_contents'])) {
            $ret['win_counts'] = count($ret['win_contents']);
            $ret['price_sum'] = $bet[3] * $bet[6] * $ret['win_counts'];
            $ret['status'] = STATUS_WIN;
        }
        return $ret;
    } /* }}} */
    
    /**
     * @brief 生肖_总肖
     *      每单1注，每注1球，每单最多1球
     *      当期号码(所有正码与最后开出的特码)开出的不同生肖总数，与所投注之预计开出之生肖总和数(不用指定特定生肖)，则视为中奖，其余情形视为不中奖。
     *      例如：如果当期号码为19、24、12、34、40、39 特别号：49，总计六个生肖，若选总肖【6】则为中奖。
     *      无和局。[1单1注，1注1球(234肖5肖6肖7肖，总肖单，总肖双)，中1注]
     * @access public/protected 
     * @param 
     * @return 
     */
    public function settlement_sx_zhongx(& $bet = array(), & $lottery = array()) /* {{{ */
    {
        /* 取球号 */
        $balls = get_balls($bet[2]);
        $ret = null;
        /* 每单1注，每注1球。 */
        $v = $balls[0][0];
        /* 中 全码 总肖，1注中1次 (无和局) */
        if (in_array($v, $lottery['zhx'])) {
            $ret['win_contents'][] = array($v);
        }
    
        if (!empty($ret['win_contents'])) {
            $ret['win_counts'] = count($ret['win_contents']);
            $ret['price_sum'] = $bet[3] * $bet[6] * $ret['win_counts'];
            $ret['status'] = STATUS_WIN;
        }
        return $ret;
    } /* }}} */
    
    /**
     * @brief 合肖_中
     *      每单1注，每注1~11球，
     *      合肖[NOTE:趣彩合肖无和局，彩票33有和局，此处以彩票33为准]
     *      中：挑选2-11个生肖『排列如同生肖』为一个组合，并选择开奖号码的特码是否在此组合内『49号除外』，即视为中奖；
     *      若特码开出49号为和局。[1单1注，1注2~11球，中1注，选球越多赔率越低]
     * @access public/protected 
     * @param 
     * @return 
     */
    public function settlement_hx_z(& $bet = array(), & $lottery = array()) /* {{{ */
    {
        $ret = null;
        /* 返回 特码 和 */
        if ($lottery['base'][6] == '49') {
            $ret['win_counts'] = 0;
            $ret['price_sum'] = $bet[5];
            $ret['status'] = STATUS_HE;
            return $ret;
        }
        /* 取球号 */
        $balls = get_balls($bet[2]);
        /* 中 特码 合肖，1注中1次 (49为和局) */
        if (in_array($lottery['sx_code'][6], $balls[0])) {
            $ret['win_contents'][] = $balls[0];
        }
    
        if (!empty($ret['win_contents'])) {
            $ret['win_counts'] = count($ret['win_contents']);
            $ret['price_sum'] = $bet[3] * $bet[6] * $ret['win_counts'];
            $ret['status'] = STATUS_WIN;
        }
        return $ret;
    } /* }}} */
    
    /**
     * @brief 合肖_不中
     *      每单1注，每注1~10球，
     *      不中：挑选2-10个生肖『排列如同生肖』为一个组合，并选择开奖号码的特码是否不在此组合内『49号除外』，即视为中奖；
     *      若特码开出49号为和局。[1单1注，1注2~11球，中1注，选球越多赔率越高]
     * @access public/protected 
     * @param 
     * @return 
     */
    public function settlement_hx_bz(& $bet = array(), & $lottery = array()) /* {{{ */
    {
        $ret = null;
        /* 返回 特码 和 */
        if ($lottery['base'][6] == '49') {
            $ret['win_counts'] = 0;
            $ret['price_sum'] = $bet[5];
            $ret['status'] = STATUS_HE;
            return $ret;
        }
        /* 取球号 */
        $balls = get_balls($bet[2]);
        /* 不中 特码 合肖，1注中1次 (49为和局) */
        if (!in_array($lottery['sx_code'][6], $balls[0])) {
            $ret['win_contents'][] = $balls[0];
        }
    
        if (!empty($ret['win_contents'])) {
            $ret['win_counts'] = count($ret['win_contents']);
            $ret['price_sum'] = $bet[3] * $bet[6] * $ret['win_counts'];
            $ret['status'] = STATUS_WIN;
        }
        return $ret;
    } /* }}} */
    
    /**
     * @brief 色波_3色波
     *      每单1注，每注1球，
     *      色波(3色波，特码色波)：以特码开出的颜色和投注的颜色相同视为中奖。
     *      无和局。[1单1注，1注1球，中1注]
     * @access public/protected 
     * @param 
     * @return 
     */
    public function settlement_sb_3sb(& $bet = array(), & $lottery = array()) /* {{{ */
    {
        $ret = null;
        /* 取球号 */
        $balls = get_balls($bet[2]);
        /* 中 特码 3色波，1注中1次 */
        if ($balls[0][0] == $lottery['sb_code'][6]) {
            $ret['win_contents'][] = $balls[0];
        }
    
        if (!empty($ret['win_contents'])) {
            $ret['win_counts'] = count($ret['win_contents']);
            $ret['price_sum'] = $bet[3] * $bet[6] * $ret['win_counts'];
            $ret['status'] = STATUS_WIN;
        }
        return $ret;
    } /* }}} */
    
    /**
     * @brief 色波_半波
     *      每单1注，每注1球，
     *      以特码色波和特单，特双，特大，特小为一个投注组合，当期特码开出符合投注组合，即视为中奖； 
     *      若当期特码开出49号，则视为和局。[1单1注，1注1球，中1注]
     * @access public/protected 
     * @param 
     * @return 
     */
    public function settlement_sb_bb(& $bet = array(), & $lottery = array()) /* {{{ */
    {
        $ret = null;
        /* 返回 特码 和 */
        if ($lottery['base'][6] == '49') {
            $ret['win_counts'] = 0;
            $ret['price_sum'] = $bet[5];
            $ret['status'] = STATUS_HE;
            return $ret;
        }
        /* 取球号 */
        $balls = get_balls($bet[2]);
        /* 中 特码 半波，1注1球中1次 (49为和局) */
        if (in_array($balls[0][0], $lottery['tm_bb'])) {
            $ret['win_contents'][] = $balls[0];
        }
    
        if (!empty($ret['win_contents'])) {
            $ret['win_counts'] = count($ret['win_contents']);
            $ret['price_sum'] = $bet[3] * $bet[6] * $ret['win_counts'];
            $ret['status'] = STATUS_WIN;
        }
        return $ret;
    } /* }}} */
    
    /**
     * @brief 色波_半半波
     *      每单1注，每注1球，
     *      半半波：以特码色波和特单双及特大小等游戏为一个投注组合，当期特码开出符合投注组合，即视为中奖；
     *      若当期特码开出49号，则视为和局。[1单1注，1注1球，中1注]
     * @access public/protected 
     * @param 
     * @return 
     */
    public function settlement_sb_bbb(& $bet = array(), & $lottery = array()) /* {{{ */
    {
        $ret = null;
        /* 返回 特码 和 */
        if ($lottery['base'][6] == '49') {
            $ret['win_counts'] = 0;
            $ret['price_sum'] = $bet[5];
            $ret['status'] = STATUS_HE;
            return $ret;
        }
    
        /* 取球号 */
        $balls = get_balls($bet[2]);
        /* 中 特码 半半波，1注中1次 */
        if ($balls[0][0] == $lottery['tm_bbb']) {
            $ret['win_contents'][] = $balls[0];
        }
    
        if (!empty($ret['win_contents'])) {
            $ret['win_counts'] = count($ret['win_contents']);
            $ret['price_sum'] = $bet[3] * $bet[6] * $ret['win_counts'];
            $ret['status'] = STATUS_WIN;
        }
        return $ret;
    } /* }}} */
    
    /**
     * @brief 色波_7色波
     *      每单1注，每注1球，
     *      以开出的7个色波，那种颜色最多为中奖。 开出的6个正码各以1个色波计，特别号以1.5个色波计。
     *      而以下3种结果视为和局。[1单1注，1注1球，中1注]
     *          1： 6个正码开出3蓝3绿，而特别码是1.5红
     *          2： 6个正码开出3蓝3红，而特别码是1.5绿
     *          3： 6个正码开出3绿3红，而特别码是1.5蓝
     * @access public/protected 
     * @param 
     * @return 
     */
    public function settlement_sb_7sb(& $bet = array(), & $lottery = array()) /* {{{ */
    {
        $ret = null;
        /* 取球号 */
        $balls = get_balls($bet[2]);
        /* 中 特码 半半波，1注中1次 */
        if ($balls[0][0] == $lottery['7sb']) {
            $ret['win_contents'][] = $balls[0];
        }
    
        if (!empty($ret['win_contents'])) {
            $ret['win_counts'] = count($ret['win_contents']);
            $ret['price_sum'] = $bet[3] * $bet[6] * $ret['win_counts'];
            $ret['status'] = STATUS_WIN;
        }
        return $ret;
    } /* }}} */
    
    /**
     * @brief 尾数_头尾数
     *      每单1注，每注1球，
     *      头尾数(特码头尾数)：若当期特别号的头数或尾数与下注头数或尾数相同，视为中奖。如：开奖特码18为特码，投注特码头1或尾8即中奖。
     *      无和局。[1单1注，1注1球，中1注]
     * @access public/protected 
     * @param 
     * @return 
     */
    public function settlement_ws_tws(& $bet = array(), & $lottery = array()) /* {{{ */
    {
        $ret = null;
        /* 取球号 */
        $balls = get_balls($bet[2]);
        /* 中 特码 头尾，1注1球中1次，中头或者中尾 */
        if ($balls[0][0] == $lottery['tm_ts'] || $balls[0][0] == $lottery['ws'][6]) {
            $ret['win_contents'][] = $balls[0];
        }
    
        if (!empty($ret['win_contents'])) {
            $ret['win_counts'] = count($ret['win_contents']);
            $ret['price_sum'] = $bet[3] * $bet[6] * $ret['win_counts'];
            $ret['status'] = STATUS_WIN;
        }
        return $ret;
    } /* }}} */
    
    /**
     * @brief 尾数_正特尾数
     *      每单1注，每注1球，
     *      正特尾数：当期开奖的全部号码(前6个号码和特码)，其中只要有一个球号在投注的尾数范围则中奖；
     *      没有一个球号在投注的尾数范围内，则不中奖；
     *      多个球号在投注尾数范围内，则中奖；但奖金不倍增，派彩只派一次，即不论同尾数号码出现一个或多个号码都只派一次。
     *      无和局。[1单1注，1注1球，中1注]
     * @access public/protected 
     * @param 
     * @return 
     */
    public function settlement_ws_ztws(& $bet = array(), & $lottery = array()) /* {{{ */
    {
        $ret = null;
        /* 取球号 */
        $balls = get_balls($bet[2]);
        /* 中 全码 尾，1注1球中1次 */
        for ($i = 0; $i < 7; $i++) {
            if ($balls[0][0] == $lottery['ws'][$i]) {
                $ret['win_contents'][] = $balls[0];
                break;
            }
        }
    
        if (!empty($ret['win_contents'])) {
            $ret['win_counts'] = count($ret['win_contents']);
            $ret['price_sum'] = $bet[3] * $bet[6] * $ret['win_counts'];
            $ret['status'] = STATUS_WIN;
        }
        return $ret;
    } /* }}} */
    
    /**
     * @brief 七码五行_七码
     *      每单1注，每注1球，
     *      七码：对开奖号的全部7个号的大小单双个数投注，比如：当期开奖号 11 30 27 14 02 20 + 19，则投 单3双4 和 大2小5 中奖，其他不中奖。
     *      无和局。[1单1注，1注1球]
     * @access public/protected 
     * @param 
     * @return 
     */
    public function settlement_wx_7m(& $bet = array(), & $lottery = array()) /* {{{ */
    {
        $ret = null;
        /* 取球号 */
        $balls = get_balls($bet[2]);
        /* 中 7码，1注1球中1次 */
        if ($balls[0][0] == $lottery['7m']['ds'] || $balls[0][0] == $lottery['7m']['dx']) {
            $ret['win_contents'][] = $balls[0];
        }
    
        if (!empty($ret['win_contents'])) {
            $ret['win_counts'] = count($ret['win_contents']);
            $ret['price_sum'] = $bet[3] * $bet[6] * $ret['win_counts'];
            $ret['status'] = STATUS_WIN;
        }
        return $ret;
    } /* }}} */
    
    /**
     * @brief 七码五行_五行
     *      每单1注，每注1球，
     *      五行：挑选一个五行选项为一个组合，若开奖号码的特码在此组合内，即视为中奖；若开奖号码的特码不在此组合内，即视为不中奖。
     *      无和局。[1单1注，1注1球，中1注]
     * @access public/protected 
     * @param 
     * @return 
     */
    public function settlement_wx_5x(& $bet = array(), & $lottery = array()) /* {{{ */
    {
        $ret = null;
        /* 取球号 */
        $balls = get_balls($bet[2]);
        /* 中 5行，1注1球中1次 */
        if ($balls[0][0] == $lottery['wx_code'][6]) {
            $ret['win_contents'][] = $balls[0];
        }
    
        if (!empty($ret['win_contents'])) {
            $ret['win_counts'] = count($ret['win_contents']);
            $ret['price_sum'] = $bet[3] * $bet[6] * $ret['win_counts'];
            $ret['status'] = STATUS_WIN;
        }
        return $ret;
    } /* }}} */
    
    /**
     * @brief 中一_五中一
     *      每单多注，每注5球，每单最多12球
     *      五中一：挑选5个号码为一投注组合进行下注。当期开出的7个开奖号码有且只有一个在该下注组合中，即视为中奖，有多个在该组合中时不算中奖。
     *      如下注组合为1-2-3-4-5，开奖号码为6，7，8，9，10，11，12，即为不中奖，如果开奖号码为5，6，7，8，9，10，11，则为中奖。
     *      无和局。[1单可多注，1注5球，可中多注]
     * @access public/protected 
     * @param 
     * @return 
     */
    public function settlement_z1_5z1(& $bet = array(), & $lottery = array(), $m = 5) /* {{{ */
    {
        /* 取球号 */
        $balls = get_balls($bet[2]);
        $ret = null;
        $wins = array();
        $lose = array();
        /* 1笔组合注单可能中多注。 */
        foreach ($balls[0] as $v) {
            /* 中 */
            if (in_array($v, $lottery['base'])) {
                $wins[] = $v;
            } else {
                $lose[] = $v;
            }
        }
        $c_l = count($lose);
        $c_w = count($wins);
        /* 不中球数大于4 (m-1)，中的球数大于1，即可中 m中一 */
        if ($c_l >= ($m - 1) && $c_w > 0) {
            $bz = combination($lose, $m - 1);
            foreach ($bz as $v) {
                for ($i = 0; $i < $c_w; $i++) {
                    $ret['win_contents'][] = array_merge($v, array($wins[$i]));
                }
            }
        }
    
        if (!empty($ret['win_contents'])) {
            $ret['win_counts'] = count($ret['win_contents']);
            $ret['price_sum'] = $bet[3] * $bet[6] * $ret['win_counts'];
            $ret['status'] = STATUS_WIN;
        }
        return $ret;
    } /* }}} */
    
    /**
     * @brief 中一_六中一
     *      每单多注，每注6球，每单最多12球
     * @access public/protected 
     * @param 
     * @return 
     */
    public function settlement_z1_6z1(& $bet = array(), & $lottery = array()) /* {{{ */
    {
        return $this->settlement_z1_5z1($bet, $lottery, 6);
    } /* }}} */
    
    /**
     * @brief 中一_七中一
     *      每单多注，每注7球，每单最多12球
     * @access public/protected 
     * @param 
     * @return 
     */
    public function settlement_z1_7z1(& $bet = array(), & $lottery = array()) /* {{{ */
    {
        return $this->settlement_z1_5z1($bet, $lottery, 7);
    } /* }}} */
    
    /**
     * @brief 中一_八中一
     *      每单多注，每注8球，每单最多12球
     * @access public/protected 
     * @param 
     * @return 
     */
    public function settlement_z1_8z1(& $bet = array(), & $lottery = array()) /* {{{ */
    {
        return $this->settlement_z1_5z1($bet, $lottery, 8);
    } /* }}} */
    
    /**
     * @brief 中一_九中一
     *      每单多注，每注9球，每单最多12球
     * @access public/protected 
     * @param 
     * @return 
     */
    public function settlement_z1_9z1(& $bet = array(), & $lottery = array()) /* {{{ */
    {
        return $this->settlement_z1_5z1($bet, $lottery, 9);
    } /* }}} */
    
    /**
     * @brief 中一_十中一
     *      每单多注，每注10球，每单最多12球
     * @access public/protected 
     * @param 
     * @return 
     */
    public function settlement_z1_10z1(& $bet = array(), & $lottery = array()) /* {{{ */
    {
        return $this->settlement_z1_5z1($bet, $lottery, 10);
    } /* }}} */
}

/* end file */
