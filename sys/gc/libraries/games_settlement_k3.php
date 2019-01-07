<?php
/**
 * @file games_settlement_k3.php
 * @brief 快3结算库
 *      NOTE: 如果玩法为和局，status 返回 STATUS_HE: $ret['status'] = STATUS_HE;
 * 
 * Copyright (C) 2017 GC.COM
 * All rights reserved.
 * 
 * @package libraries
 * @author Langr <hua@langr.org> 2017/03/29 20:16
 * 
 * $Id$
 */

include_once(dirname(__FILE__).'/games.php');

class games_settlement_k3
{
    public $config_balls = [
        /* 基本球 */
        'base'  => [1, 2, 3, 4, 5, 6],
        /* 和值 */
        'hz'    => [3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18]
    ];

    /**
     * @brief 根据彩种当期的开奖号计算出各种玩法的开奖值
     *      如：k3 20170404043当期开奖: 2, 5, 6;
     *          则计算出和值开奖值: 13.
     * @access public/protected 
     * @param 
     * @return 
     */
    public function wins_balls(& $wins_balls = array()) /* {{{ */
    {
        //$wins_balls['base'] = ['1', '2', '3'];
        if (count($wins_balls['base']) != 3 ||
                !in_array($wins_balls['base'][0], $this->config_balls['base']) ||
                !in_array($wins_balls['base'][1], $this->config_balls['base']) ||
                !in_array($wins_balls['base'][2], $this->config_balls['base'])
                ) {
            $msg = date('[Y-m-d H:i:s]').'[k3] lottery error:'.implode(',', $wins_balls['base'])."\n";
            error_log($msg, 3, 'api/logs/wins_balls_error_'.date('Ym').'.log');
            exit($msg);
        }
        $wins_balls['he'] = array_sum($wins_balls['base']);
        return $wins_balls;
    } /* }}} */
    
    /**
     * @brief 二不同号_标准选号
     *      对三个号码中两个指定的不同号码和一个任意号码进行投注。
     *      选两个不相同的号为一组，开奖号中有任意两号与所选号相同为中奖。
     *          $bet = "[\"1570329144415671\",1,\"3|2,4,5||6\",2,3,6,\"6.12\",13]";
     *          $bet = "[\"1570329155205234\",1,\"3,4,5\",2,3,6,\"6.12\",13]";
     *          投注方案：6 3 开奖号码：634 663 ，即中二不同号。
     * @access public
     * @link http://www.gc360.com/orders/bet/15/1908
     * @param 
     * @return 
     *      中奖，和局，未中奖
     *      ret = array('win_contents'=>array(array(1,3)), 'win_counts'=>1, 'price_sum'=>3.9, 'status'=>STATUS_WIN);
     *      ret = array('win_contents'=>array(), 'win_counts'=>0, 'price_sum'=>4, 'status'=>STATUS_HE);
     *      ret = null;
     */
    public function k_settlement_2bth_bzxh(& $bet = array(), & $lottery = array()) /* {{{ */
    {
        /* 标准通用算法 */
        $balls = get_balls($bet[2]);
        $ret = null;
        /* 1笔组合注单要么中一注；要么中三注：3个开奖号都在组合单里面且没有同号。 */
        $r2 = combination($balls[0], 2);
        foreach ($r2 as $v) {
            if (in_array($v[0], $lottery['base']) && in_array($v[1], $lottery['base'])) {
                $ret['win_contents'][] = $v;
            }
        }
    
        if (!empty($ret['win_contents'])) {
            $ret['win_counts'] = count($ret['win_contents']);
            $ret['price_sum'] = $bet[3] * $bet[6] * $ret['win_counts'];
            $ret['status'] = STATUS_WIN;
        }
        return $ret;
    } /* }}} */
    
    public function settlement_2bth_bzxh(& $bet = array(), & $lottery = array()) /* {{{ */
    {
        /* 较快算法 */
        $balls = get_balls($bet[2]);
        $ret = null;
        /* 1笔组合注单要么中一注；要么中三注：3个开奖号都在组合单里面且没有同号。 */
        $res = array_values(array_unique($lottery['base']));
        $c = count($res);
        if ($c == 1) {
            return null;
        }
        if ($c == 2) {
            if (in_array($res[0], $balls[0]) && in_array($res[1], $balls[0])) {
                $ret['win_contents'][] = $res;
            } else {
                return null;
            }
        } else {
            if (in_array($res[0], $balls[0]) && in_array($res[1], $balls[0])) {
                $ret['win_contents'][] = array($res[0], $res[1]);
            }
            if (in_array($res[0], $balls[0]) && in_array($res[2], $balls[0])) {
                $ret['win_contents'][] = array($res[0], $res[2]);
            }
            if (in_array($res[1], $balls[0]) && in_array($res[2], $balls[0])) {
                $ret['win_contents'][] = array($res[1], $res[2]);
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
     * @brief 二不同号_手动选号
     *      手动输入号码，至少输入1-6中两个不同的数字组成一注号码。
     *          $bet = "[\"1570329155205234\",1,\"3,4,5\",2,3,6,\"6.12\",13]";
     * @access public
     * @link http://www.gc360.com/orders/bet/15/1909
     * @param 
     * @return 
     */
    public function settlement_2bth_sdxh(& $bet = array(), & $lottery = array()) /* {{{ */
    {
        return $this->settlement_2bth_bzxh($bet, $settlement);
    } /* }}} */
    
    /**
     * @brief 二不同号_胆拖选号
     *      从1~6中，选取2个及以上的号码进行投注，每注需至少包括1个胆码及1个拖码。
     *      胆码选中后的球，拖码不能再选。
     *      投注方案：胆码1 拖码2 开奖号码：123 321 即中二不同号胆拖。
     *          $bet = "[\"1570329155205234\",1,\"3|1,4,5\",2,3,6,\"6.12\",13]";
     * @access public
     * @link http://www.gc360.com/orders/bet/15/1910
     * @param 
     * @return 
     */
    public function settlement_2bth_tdxh(& $bet = array(), & $lottery = array()) /* {{{ */
    {
        $balls = get_balls($bet[2]);
        $ret = null;
        /* 1笔组合注 一个胆码多个拖码，可中一注或两注 */
        foreach ($balls[1] as $v) {
            if (in_array($balls[0][0], $lottery['base']) && in_array($v[0], $lottery['base'])) {
                $ret['win_contents'][] = array($balls[0][0], $v[0]);
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
     * @brief 二同号_二同单选_标准选号
     *      对三个号码中两个指定的相同号码与一个指定的不同号码进行投注。
     *      二同号 选中后的球，不同号 不能再选。
     *      投注方案：同号66 不同号3 开奖号码：663 366中任意一个，即中二同号单选。
     *          $bet = "[\"1570329155205234\",1,\"3|1,4,5\",2,3,6,\"6.12\",13]";
     * @access public
     * @param 
     * @return 
     */
    public function settlement_2th_2tdx_bzxh(& $bet = array(), & $lottery = array()) /* {{{ */
    {
        $balls = get_balls($bet[2]);
        $ret = null;
        /* 1笔组合注 一个二同号多个不同号，只会中一注 */
        //$res = array_unique($lottery['base']);
        //if (count($res) != 2) {
        //    return null;
        //}
        /* 找出二同号, 并判断不同号 */
        if ($lottery['base'][0] == $lottery['base'][1] && $lottery['base'][0] != $lottery['base'][2]) {
            /* 如果二同号为第 1,2 球，第3球在不同号中，则中奖 */
            if ($balls[0][0] == $lottery['base'][0] && in_array($lottery['base'][2], $balls[1])) {
                $ret['win_contents'][] = array($balls[0][0], $balls[0][0], "{$lottery['base'][2]}");
            }
        } elseif ($lottery['base'][0] == $lottery['base'][2] && $lottery['base'][0] != $lottery['base'][1]) {
            /* 同理：如果二同号为第 1,3 球，第2球在不同号中，则中奖 */
            if ($balls[0][0] == $lottery['base'][0] && in_array($lottery['base'][1], $balls[1])) {
                $ret['win_contents'][] = array($balls[0][0], $balls[0][0], "{$lottery['base'][1]}");
            }
        } elseif ($lottery['base'][1] == $lottery['base'][2] && $lottery['base'][0] != $lottery['base'][1]) {
            if ($balls[0][0] == $lottery['base'][1] && in_array($lottery['base'][0], $balls[1])) {
                $ret['win_contents'][] = array($balls[0][0], $balls[0][0], "{$lottery['base'][0]}");
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
     * @brief 二同号_二同单选_手动选号
     *      对三个号码中两个指定的相同号码与一个指定的不同号码进行投注。
     *      二同号 选中后的球，不同号 不能再选。
     *      [此行注释无效]!手动输入号码，至少输入1个三位数（其中1个号码需相同）号码组成一注。
     * @access public
     * @param 
     * @return 
     */
    public function settlement_2th_2tdx_sdxh(& $bet = array(), & $lottery = array()) /* {{{ */
    {
        return $this->settlement_2th_2tdx_bzxh($bet, $lottery);
    } /* }}} */
    
    /**
     * @brief 二同号_二同复选
     *      对所有的对子（11 22 33 44 55 66）进行投注，开奖号码中包含所选的对子（不含豹子）时，即中奖。
     *      投注方案：66 开奖号码：661 662 366 466 566 即中二同号复选。
     *          $bet = "[\"1570329155205234\",1,\"3,4,5\",2,3,6,\"6.12\",13]";
     * @access public
     * @param 
     * @return 
     */
    public function settlement_2th_2tfx(& $bet = array(), & $lottery = array()) /* {{{ */
    {
        $balls = get_balls($bet[2]);
        $ret = null;
        /* 1笔组合注 多个二同号，只会中一注 */
        //$res = array_unique($lottery['base']);
        //if (count($res) != 2) {
        //    return null;
        //}
        /* 找出二同号, 并判断是否中奖 */
        if ($lottery['base'][0] == $lottery['base'][1] && $lottery['base'][0] != $lottery['base'][2]) {
            /* 如果二同号为第 1,2 球，并已选中，第3球为不同号，则中奖 */
            if (in_array($lottery['base'][0], $balls[0])) {
                $ret['win_contents'][] = array("{$lottery['base'][0]}", "{$lottery['base'][0]}");
            }
        } elseif ($lottery['base'][0] == $lottery['base'][2] && $lottery['base'][0] != $lottery['base'][1]) {
            /* 同理：如果二同号为第 1,3 球，并已选中，第2球为不同号，则中奖 */
            if (in_array($lottery['base'][0], $balls[0])) {
                $ret['win_contents'][] = array("{$lottery['base'][0]}", "{$lottery['base'][0]}");
            }
        } elseif ($lottery['base'][1] == $lottery['base'][2] && $lottery['base'][0] != $lottery['base'][1]) {
            if (in_array($lottery['base'][1], $balls[0])) {
                $ret['win_contents'][] = array("{$lottery['base'][1]}", "{$lottery['base'][1]}");
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
     * @brief 三不同号_标准选号
     *      对三个各不相同的号码进行投注。
     *      投注方案：5 6 3 开奖号码：635或536(顺序不限)即中三不同号。
     * @access public
     * @param 
     * @return 
     */
    public function settlement_3bth_bzxh(& $bet = array(), & $lottery = array()) /* {{{ */
    {
        /* 标准通用算法 */
        $balls = get_balls($bet[2]);
        $ret = null;
        /* 1笔组合注 最少三个不同号，只会中一注 */
        $res = array_unique($lottery['base']);
        if (count($res) != 3) {
            return null;
        }
        /* 判断是否中奖：如果开奖号的每一个号都在组合注里，则中奖 */
        if (in_array($lottery['base'][0], $balls[0]) && in_array($lottery['base'][1], $balls[0]) && in_array($lottery['base'][2], $balls[0])) {
            $ret['win_contents'][] = array("{$lottery['base'][0]}", "{$lottery['base'][1]}", "{$lottery['base'][2]}");
        }
    
        if (!empty($ret['win_contents'])) {
            $ret['win_counts'] = count($ret['win_contents']);
            $ret['price_sum'] = $bet[3] * $bet[6] * $ret['win_counts'];
            $ret['status'] = STATUS_WIN;
        }
        return $ret;
    } /* }}} */
    
    /**
     * @brief 三不同号_手动选号
     *      对三个各不相同的号码进行投注。
     * @access public
     * @param 
     * @return 
     */
    public function settlement_3bth_sdxh(& $bet = array(), & $lottery = array()) /* {{{ */
    {
        return $this->settlement_3bth_bzxh($bet, $lottery);
    } /* }}} */
    
    /**
     * @brief 三同号_三同单选
     *      对所有相同的三个号码：（111 222 333 444 555 666）中任意选择一组号码进行投注。
     * @access public
     * @param 
     * @return 
     */
    public function settlement_3th_3tdx(& $bet = array(), & $lottery = array()) /* {{{ */
    {
        /* 标准通用算法 */
        $balls = get_balls($bet[2]);
        $ret = null;
        /* 1笔组合注 最少一个三同号，只会中一注 */
        //$res = array_unique($lottery['base']);
        //if (count($res) != 1) {
        //    return null;
        //}
        /* 判断是否中奖：如果开奖号的每一个号都在组合注里，则中奖 */
        if ($lottery['base'][0] == $lottery['base'][1] && $lottery['base'][0] == $lottery['base'][2] && in_array($lottery['base'][0], $balls[0])) {
            $ret['win_contents'][] = array("{$lottery['base'][0]}", "{$lottery['base'][1]}", "{$lottery['base'][2]}");
        }
    
        if (!empty($ret['win_contents'])) {
            $ret['win_counts'] = count($ret['win_contents']);
            $ret['price_sum'] = $bet[3] * $bet[6] * $ret['win_counts'];
            $ret['status'] = STATUS_WIN;
        }
        return $ret;
    } /* }}} */
    
    /**
     * @brief 三同号_三同通选
     *      对所有相同的三个号码：（111 222 333 444 555 666）进行投注。
     *      $bet['contents'] = '1,2,3,4,5,6'
     * @access public
     * @param 
     * @return 
     */
    public function settlement_3th_3ttx(& $bet = array(), & $lottery = array()) /* {{{ */
    {
        /* 标准通用算法 */
        //$balls = get_balls($bet[2]);
        $ret = null;
        /* 1笔组合注 通选六个三同号，只会中一注 */
        //$res = array_unique($lottery['base']);
        //if (count($res) != 1) {
        //    return null;
        //}
        /* 判断是否中奖：只需判断开奖是否三同号 */
        if ($lottery['base'][0] == $lottery['base'][1] && $lottery['base'][0] == $lottery['base'][2]) {
            $ret['win_contents'][] = array("{$lottery['base'][0]}", "{$lottery['base'][1]}", "{$lottery['base'][2]}");
        }
    
        if (!empty($ret['win_contents'])) {
            $ret['win_counts'] = count($ret['win_contents']);
            $ret['price_sum'] = $bet[3] * $bet[6] * $ret['win_counts'];
            $ret['status'] = STATUS_WIN;
        }
        return $ret;
    } /* }}} */
    
    /**
     * @brief 三连号_三连通选
     *      对所有三个相连的号码（仅限：123、234、345、456）进行投注，当开奖号码为任意1个三连号时，即中奖。
     *      投注方案：三连号通选 开奖号码：123 234 345 456即中三连号通选。
     *      $bet['contents'] = '1,2,3,4'
     * @access public
     * @param 
     * @return 
     */
    public function settlement_3lh_3ltx(& $bet = array(), & $lottery = array()) /* {{{ */
    {
        $ret = null;
        /* 判断是否中奖：只需判断开奖是否为三连号 */
        if ($lottery['base'][0] == ($lottery['base'][1] - 1) && $lottery['base'][1] == ($lottery['base'][2] - 1)) {
            $ret['win_contents'][] = array("{$lottery['base'][0]}", "{$lottery['base'][1]}", "{$lottery['base'][2]}");
        }
    
        if (!empty($ret['win_contents'])) {
            $ret['win_counts'] = count($ret['win_contents']);
            $ret['price_sum'] = $bet[3] * $bet[6] * $ret['win_counts'];
            $ret['status'] = STATUS_WIN;
        }
        return $ret;
    } /* }}} */
    
    /**
     * @brief 和值_和值
     *      从3-18中任意选择1个或1个以上号码。
     * @access public
     * @param 
     * @return 
     */
    public function settlement_hz_hz($bet = array(), & $lottery = array()) /* {{{ */
    {
        //$balls = get_balls($bet[2]);
        $ret = null;
        /* 1笔组合注 只选一个球 只会中一注 */
        /* 判断是否中奖： */
        if ($bet[2] == $lottery['he']) {
            $ret['win_contents'][] = array($bet[2]);
        }
    
        if (!empty($ret['win_contents'])) {
            $ret['win_counts'] = count($ret['win_contents']);
            $ret['price_sum'] = $bet[3] * $bet[6] * $ret['win_counts'];
            $ret['status'] = STATUS_WIN;
        }
        return $ret;
    } /* }}} */
}

/* end file */
