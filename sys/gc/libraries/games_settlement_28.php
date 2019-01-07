<?php
/**
 * @file games_settlement_28.php
 * @brief pcdd 结算库
 *
 * Copyright (C) 2017 GC.COM
 * All rights reserved.
 *
 * @package libraries
 * @author Langr <hua@langr.org> 2017/04/07 10:44
 *
 * $Id$
 */

include_once(dirname(__FILE__) . '/games.php');

class games_settlement_28
{
    public $config_balls = [
        /* 基本球 */
        'base' => [0, 1, 2, 3, 4, 5, 6, 7, 8, 9],
        // 特码
        'tm' => [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15,
            16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27],
        // 特码包3
        'tmb3' => [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15,
            16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27],
        // 混合
        'hh' => [
            '100' => [14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27], // 大
            '101' => [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13], // 小
            '102' => [1, 3, 5, 7, 9, 11, 13, 15, 17, 19, 21, 23, 25, 27], // 单
            '103' => [0, 2, 4, 6, 8, 10, 12, 14, 16, 18, 20, 22, 24, 26], // 双
            '108' => [15, 17, 19, 21, 23, 25, 27], // 大单
            '109' => [1, 3, 5, 7, 9, 11, 13], // 小单
            '110' => [14, 16, 18, 20, 22, 24, 26], // 大双
            '111' => [0, 2, 4, 6, 8, 10, 12], // 小双
            '242' => [22, 23, 24, 25, 26, 27], // 极大
            '243' => [0, 1, 2, 3, 4, 5], // 极小
        ],
        // 波色
        'bs' => [
            '124' => [3, 6, 9, 12, 15, 18, 21, 24],   // 红波
            '125' => [1, 4, 7, 10, 16, 19, 22, 25],   // 绿波
            '126' => [2, 5, 8, 11, 17, 20, 23, 26]    // 蓝波
        ],
        // 豹子
        'bz' => [
            '169' => [000, 111, 222, 333, 444, 555, 666, 777, 888, 999]
        ]
    ];
    
    /**
     * @brief 根据彩种当期的开奖号计算出各种玩法的开奖值
     *      如：某 11x5 20170404043当期开奖: 7, 0, 3, 6, 2;
     *          则计算出...
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
            $msg = date('[Y-m-d H:i:s]').'[pcdd] lottery error:'.implode(',', $wins_balls['base'])."\n";
            error_log($msg, 3, 'api/logs/wins_balls_error_'.date('Ym').'.log');
            exit($msg);
        }
        $wins_balls['tm'] = array_sum($wins_balls['base']);
        return $wins_balls;
    } /* }}} */
    
    /**
     * @brief 特码
     *          所选特码与开奖的3个号码相加之和相同，即中奖。
     *          投注号码示例 5
     *          开奖号码示例 0 0 5（顺序不限）等
     * @access public
     * @param
     * @return
     */
    public function settlement_tm(& $bet = array(), & $lottery = array())
    {
        $balls = get_balls($bet[2]);
        $ret = null;
        if (count($balls[0]) != 1) {
            return $ret;
        }
        if ($balls[0][0] == $lottery['tm']) {
            $ret['win_contents'][] = array($balls[0][0]);
        }
    
        if (!empty($ret['win_contents'])) {
            $ret['win_counts'] = count($ret['win_contents']);
            $ret['price_sum'] = $bet[3] * $bet[6] * $ret['win_counts'];
            $ret['status'] = STATUS_WIN;
        }
        return $ret;
    }
    
    /**
     * @brief 特码包三
     *          投选三个特码，任意一个特码与开奖的3个号码相加之和相同，即中奖。
     *          投注号码示例 5 6 7
     *          开奖号码示例 0 0 5（顺序不限）等
     * @access public
     * @param
     * @return
     */
    public function settlement_tmb3(& $bet = array(), & $lottery = array())
    {
        $balls = get_balls($bet[2]);
        $ret = null;
        if (count($balls[0]) != 3) {
            return $ret;
        }
        foreach ($balls[0] as $ball) {
            if ($ball == $lottery['tm']) {
                $ret['win_contents'][] = array($ball);
                break;
            }
        }
    
        if (!empty($ret['win_contents'])) {
            $ret['win_counts'] = count($ret['win_contents']);
            $ret['price_sum'] = $bet[3] * $bet[6] * $ret['win_counts'];
            $ret['status'] = STATUS_WIN;
        }
        return $ret;
    }
    
    /**
     * @brief 混合
     *          玩法1：大小（对特码大（14~27），小（0~13）形态进行投注，所选号码的形态与开奖号码的形态相同，即为中奖。）
     *          玩法2：单双（对特码单（1，3，5~27），双（0，2，4~26）形态进行投注，所选号码的形态与开奖号码相加之和的形态相同，即为中奖。）
     *          玩法3：组合（对特码大单（15，17，19，21，23，25，27），
     *                          小单（1，3，5，7，9，11，13），
     *                          大双（14，16，18，20，22，24，26），
     *                          小双（0，2，4，6，8.10，12）
     *                          形态进行投注，所选号码的形态与开奖号码相加之和的形态相同，即为中奖。）
     *          玩法4：极值（对特码极大（22，23，24，25，26，27），
     *                          极小（0，1，2，3，4，5）
     *                          形态进行投注，所选号码的形态与开奖号码相加之和的形态相同，即为中奖。）
     * @access public
     * @param
     * @return
     */
    public function settlement_hh(& $bet = array(), & $lottery = array())
    {
        $balls = get_balls($bet[2]);
        $ret = null;
        if (count($balls[0]) != 1) {
            return $ret;
        }
        if (in_array($lottery['tm'], $this->config_balls['hh'][$balls[0][0]])) {
            $ret['win_contents'][] = array($balls[0][0]);
        }
    
        if (!empty($ret['win_contents'])) {
            $ret['win_counts'] = count($ret['win_contents']);
            $ret['price_sum'] = $bet[3] * $bet[6] * $ret['win_counts'];
            $ret['status'] = STATUS_WIN;
        }
        return $ret;
    }
    
    /**
     * @brief 波色
     *          对绿波（1，4，7，10，16，19，22，25），
     *          蓝波（2，5，8，11，17，20，23，26），
     *          红波（3，6，9，12，15，18，21，24）
     *          形态进行投注，所选号码的形态与开奖号码相加之和的形态相同，即为中奖。
     *          投注号码示例 125
     *          开奖号码示例 1 2 4（顺序不限）等
     * @access public
     * @param
     * @return
     */
    public function settlement_bs(& $bet = array(), & $lottery = array())
    {
        $balls = get_balls($bet[2]);
        $ret = null;
        if (count($balls[0]) != 1) {
            return $ret;
        }
        if (in_array($lottery['tm'], $this->config_balls['bs'][$balls[0][0]])) {
            $ret['win_contents'][] = array($balls[0][0]);
        }
    
        if (!empty($ret['win_contents'])) {
            $ret['win_counts'] = count($ret['win_contents']);
            $ret['price_sum'] = $bet[3] * $bet[6] * $ret['win_counts'];
            $ret['status'] = STATUS_WIN;
        }
        return $ret;
    }
    
    /**
     * @brief 豹子
     *          对所有的豹子（000，111，222，333，444，555，666，777，888，999）进行投注。当开奖号码为任意1个豹子时，即中奖。
     *          投注号码示例 169
     *          开奖号码示例 1 1 1（顺序不限）等
     * @access public
     * @param
     * @return
     */
    public function settlement_bz(& $bet = array(), & $lottery = array())
    {
        $balls = get_balls($bet[2]);
        $ret = null;
        if (count($balls[0]) != 1) {
            return $ret;
        }
        if ($lottery['base'][0] == $lottery['base'][1] && $lottery['base'][1] == $lottery['base'][2]) {
            $ret['win_contents'][] = array($balls[0][0]);
        }
    
        if (!empty($ret['win_contents'])) {
            $ret['win_counts'] = count($ret['win_contents']);
            $ret['price_sum'] = $bet[3] * $bet[6] * $ret['win_counts'];
            $ret['status'] = STATUS_WIN;
        }
        return $ret;
    }
}

/* end file */
