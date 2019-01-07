<?php
/**
 * @file games_settlement_pk10.php
 * @brief pk10 结算库
 *      NOTE: 如果玩法为和局，status 返回 STATUS_HE: $ret['status'] = STATUS_HE;
 *
 * Copyright (C) 2017 GC.COM
 * All rights reserved.
 *
 * @package libraries
 * @author Langr <hua@langr.org> 2017/04/07 10:41
 *
 * $Id$
 */

include_once(dirname(__FILE__) . '/games.php');

class games_settlement_pk10
{
    public $config_balls = [
        /* 基本球 */
        'base' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10],
        'dx' => [
            '100' => [6, 7, 8, 9, 10], // 大：100
            '101' => [1, 2, 3, 4, 5]   // 小：101
        ],
        'ds' => [
            '102' => [1, 3, 5, 7, 9],  // 单：102
            '103' => [2, 4, 6, 8, 10]  // 双：103
        ],
    ];
    
    /**
     * @brief 根据彩种当期的开奖号计算出各种玩法的开奖值
     *      如：某 11x5 20170404043当期开奖: 7, 0, 3, 6, 2;
     *          则计算出...
     * @access public/protected
     * @param
     * @return
     */
    public function wins_balls(& $wins_balls = []) /* {{{ */
    {
        //$wins_balls['base'] = ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10'];
        /* NOTE: pk10 开奖号不可以有重复 */
        if (count(array_unique($wins_balls['base'])) != 10 ||
                !in_array($wins_balls['base'][0], $this->config_balls['base']) ||
                !in_array($wins_balls['base'][1], $this->config_balls['base']) ||
                !in_array($wins_balls['base'][2], $this->config_balls['base']) ||
                !in_array($wins_balls['base'][3], $this->config_balls['base']) ||
                !in_array($wins_balls['base'][4], $this->config_balls['base']) ||
                !in_array($wins_balls['base'][5], $this->config_balls['base']) ||
                !in_array($wins_balls['base'][6], $this->config_balls['base']) ||
                !in_array($wins_balls['base'][7], $this->config_balls['base']) ||
                !in_array($wins_balls['base'][8], $this->config_balls['base']) ||
                !in_array($wins_balls['base'][9], $this->config_balls['base'])
                ) {
            $msg = date('[Y-m-d H:i:s]').'[pk10] lottery error:'.implode(',', $wins_balls['base'])."\n";
            error_log($msg, 3, 'api/logs/wins_balls_error_'.date('Ym').'.log');
            exit($msg);
        }
    
        return $wins_balls;
    } /* }}} */
    
    /**
     * @brief 前一
     *          从01-10中至少选择1个号码组成一注，所选号码与开奖号码中第一位相同即中奖。
     *          投注方案：01
     *          开奖号码：01 02 03 04 05 06 07 08 09 10即可中前一直选。
     *          $bets = {
     *              gid:15,tid:1908,price:2,counts:3,price_sum:6,
     *              rate:"第一个赔率,第二个赔率...",
     *              rebate:反水比率,
     *              pids:"第一球products_id1,第一球products_id2|第二球products_id1|第三球不选为空|第四球products_id1...",
     *              contents:"第一球code1,第一球code2|第二球code1|第三球不选为空|第四球code1...",
     *              names:"01,05|08||03..."
     *          }
     *          demo:
     *          POST http://www.gc360.com/orders/bet/15/20170327024/1908
     *          $bets = {
     *              gid:15,tid:1908,price:2,counts:3,price_sum:6,
     *              rate:"9.8,2.179",
     *              rebate:13,
     *              pids:"22418,22422|22426||22444,22446",
     *              contents:"1,5|3||4,6",
     *              names:"01,05|03||04,06"
     *          }
     * @access public
     * @param
     * @return
     */
    public function settlement_q1_q1(& $bet = [], & $lottery = [])
    {
        $balls = get_balls($bet[2]);
        $ret = null;
        // 有且只有第一个中了，只能中一注
        if (in_array($lottery['base'][0], $balls[0])) {
            $ret['win_contents'][] = [$lottery['base'][0]];
        }
    
        if (!empty($ret['win_contents'])) {
            $ret['win_counts'] = count($ret['win_contents']);
            $ret['price_sum'] = $bet[3] * $bet[6] * $ret['win_counts'];
            $ret['status'] = STATUS_WIN;
        }
        return $ret;
    }
    
    /**
     * @brief 前二_前二复式
     *          从第一名、第二名中至少各选一个号码组成一注，开奖号码中第一、第二位与选号按位相同，即为中奖。
     *          投注方案：第一名01，第二名02
     *          开奖号码：01 02 03 04 05 06 07 08 09 10即可中前二直选。
     *          $bets = {
     *              gid:15,tid:1908,price:2,counts:3,price_sum:6,
     *              rate:"第一个赔率,第二个赔率...",
     *              rebate:反水比率,
     *              pids:"第一球products_id1,第一球products_id2|第二球products_id1|第三球不选为空|第四球products_id1...",
     *              contents:"第一球code1,第一球code2|第二球code1|第三球不选为空|第四球code1...",
     *              names:"01,05|08||03..."
     *          }
     *          demo:
     *          POST http://www.gc360.com/orders/bet/15/20170327024/1908
     *          $bets = {
     *              gid:15,tid:1908,price:2,counts:3,price_sum:6,
     *              rate:"9.8,2.179",
     *              rebate:13,
     *              pids:"22418,22422|22426||22444,22446",
     *              contents:"1,5|3||4,6",
     *              names:"01,05|03||04,06"
     *          }
     * @access public
     * @param
     * @return
     */
    public function settlement_q2_q2fs(& $bet = [], & $lottery = [])
    {
        $balls = get_balls($bet[2]);
        $ret = null;
        if (count($balls) != 2) {
            return $ret;
        }
        $r = CC($balls,2);
        foreach ($r['data'] as $v) {
            if ($v[0] == $lottery['base'][0] && $v[1] == $lottery['base'][1]) {
                $ret['win_contents'][] = $v;
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
     * @brief 前二_前二单式
     *          手动输入两个号码组成一注，所选号码与开奖号码中第一、第二位相同，且顺序一致，即为中奖。
     *          投注方案：01 02
     *          开奖号码：01 02 03 04 05 06 07 08 09 10 即可中前二直选。
     *          $bets = {
     *              gid:15,tid:1908,price:2,counts:3,price_sum:6,
     *              rate:"第一个赔率,第二个赔率...",
     *              rebate:反水比率,
     *              pids:"第一球products_id1,第一球products_id2|第二球products_id1|第三球不选为空|第四球products_id1...",
     *              contents:"第一球code1,第一球code2|第二球code1|第三球不选为空|第四球code1...",
     *              names:"01,05|08||03..."
     *          }
     *          demo:
     *          POST http://www.gc360.com/orders/bet/15/20170327024/1908
     *          $bets = {
     *              gid:15,tid:1908,price:2,counts:3,price_sum:6,
     *              rate:"9.8,2.179",
     *              rebate:13,
     *              pids:"22418,22422|22426||22444,22446",
     *              contents:"1,5|3||4,6",
     *              names:"01,05|03||04,06"
     *          }
     * @access public
     * @param
     * @return
     */
    public function settlement_q2_q2ds(& $bet = [], & $lottery = [])
    {
        return $this->settlement_q2_q2fs($bet, $lottery);
    }
    
    /**
     * @brief 前三_前三复式
     *          从第一名、第二名、第三名中至少各选择一个号码组成一注，开奖号码中第一、第二、第三位与选号按位相同，即为中奖
     *          投注方案：第一名01 第二名02 第三名03，
     *          开奖号码：01 02 03 04 05 06 07 08 09 10即可中前三直选
     *          $bets = {
     *              gid:15,tid:1908,price:2,counts:3,price_sum:6,
     *              rate:"第一个赔率,第二个赔率...",
     *              rebate:反水比率,
     *              pids:"第一球products_id1,第一球products_id2|第二球products_id1|第三球不选为空|第四球products_id1...",
     *              contents:"第一球code1,第一球code2|第二球code1|第三球不选为空|第四球code1...",
     *              names:"01,05|08||03..."
     *          }
     *          demo:
     *          POST http://www.gc360.com/orders/bet/15/20170327024/1908
     *          $bets = {
     *              gid:15,tid:1908,price:2,counts:3,price_sum:6,
     *              rate:"9.8,2.179",
     *              rebate:13,
     *              pids:"22418,22422|22426||22444,22446",
     *              contents:"1,5|3||4,6",
     *              names:"01,05|03||04,06"
     *          }
     * @access public
     * @param
     * @return
     */
    public function settlement_q3_q3fs(& $bet = [], & $lottery = [])
    {
        $balls = get_balls($bet[2]);
        $ret = null;
        if (count($balls) != 3) {
            return $ret;
        }
        $r = CC($balls,3);
        foreach ($r['data'] as $v) {
            if ($v[0] == $lottery['base'][0] && $v[1] == $lottery['base'][1] && $v[2] == $lottery['base'][2]) {
                $ret['win_contents'][] = $v;
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
     * @brief 前三_前三单式
     *          手动输入三个号码组成一注，所选号码与开奖号码中第一、第二、第三位相同，且顺序一致，即为中奖。
     *          投注方案：第一名01 第二名02 第三名03，
     *          开奖号码：01 02 03 04 05 06 07 08 09 10即可中前三直选
     *          $bets = {
     *              gid:15,tid:1908,price:2,counts:3,price_sum:6,
     *              rate:"第一个赔率,第二个赔率...",
     *              rebate:反水比率,
     *              pids:"第一球products_id1,第一球products_id2|第二球products_id1|第三球不选为空|第四球products_id1...",
     *              contents:"第一球code1,第一球code2|第二球code1|第三球不选为空|第四球code1...",
     *              names:"01,05|08||03..."
     *          }
     *          demo:
     *          POST http://www.gc360.com/orders/bet/15/20170327024/1908
     *          $bets = {
     *              gid:15,tid:1908,price:2,counts:3,price_sum:6,
     *              rate:"9.8,2.179",
     *              rebate:13,
     *              pids:"22418,22422|22426||22444,22446",
     *              contents:"1,5|3||4,6",
     *              names:"01,05|03||04,06"
     *          }
     * @access public
     * @param
     * @return
     */
    public function settlement_q3_q3ds(& $bet = [], & $lottery = [])
    {
        return $this->settlement_q3_q3fs($bet, $lottery);
    }
    
    /**
     * @brief 定位胆_第1-5名
     *          从第一名到第五名任意位置上选择1个或1个以上号码，每注由1个号码组成，所选号码与相同位置上的开奖号码一致，即为中奖
     *          投注方案：第一名01，
     *          开奖号码：01 02 03 04 05 06 07 08 09 10即中定位胆。
     *          $bets = {
     *              gid:15,tid:1908,price:2,counts:3,price_sum:6,
     *              rate:"第一个赔率,第二个赔率...",
     *              rebate:反水比率,
     *              pids:"第一球products_id1,第一球products_id2|第二球products_id1|第三球不选为空|第四球products_id1...",
     *              contents:"第一球code1,第一球code2|第二球code1|第三球不选为空|第四球code1...",
     *              names:"01,05|08||03..."
     *          }
     *          demo:
     *          POST http://www.gc360.com/orders/bet/15/20170327024/1908
     *          $bets = {
     *              gid:15,tid:1908,price:2,counts:3,price_sum:6,
     *              rate:"9.8,2.179",
     *              rebate:13,
     *              pids:"22418,22422|22426||22444,22446",
     *              contents:"1,5|3||4,6",
     *              names:"01,05|03||04,06"
     *          }
     * @access public
     * @param
     * @return
     */
    public function settlement_dwd_dwd1(& $bet = [], & $lottery = [])
    {
        $balls = get_balls($bet[2]);
        $ret = null;
        // 一个位只能中一球,最多中五球
        $winBalls = array_slice($lottery['base'], 0, 5);
        foreach ($winBalls as $k => $v) {
            $baseContents = ['', '', '', '', ''];
            if (in_array($v, $balls[$k])) {
                $baseContents[$k] = $v;
                $ret['win_contents'][] = $baseContents;
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
     * @brief 定位胆_第6-10名
     *          从第六名到第十名任意位置上选择1个或1个以上号码，每注由1个号码组成，所选号码与相同位置上的开奖号码一致，即为中奖。
     *          投注方案：第六名06，
     *          开奖号码：01 02 03 04 05 06 07 08 09 10即中定位胆。
     *          $bets = {
     *              gid:15,tid:1908,price:2,counts:3,price_sum:6,
     *              rate:"第一个赔率,第二个赔率...",
     *              rebate:反水比率,
     *              pids:"第一球products_id1,第一球products_id2|第二球products_id1|第三球不选为空|第四球products_id1...",
     *              contents:"第一球code1,第一球code2|第二球code1|第三球不选为空|第四球code1...",
     *              names:"01,05|08||03..."
     *          }
     *          demo:
     *          POST http://www.gc360.com/orders/bet/15/20170327024/1908
     *          $bets = {
     *              gid:15,tid:1908,price:2,counts:3,price_sum:6,
     *              rate:"9.8,2.179",
     *              rebate:13,
     *              pids:"22418,22422|22426||22444,22446",
     *              contents:"1,5|3||4,6",
     *              names:"01,05|03||04,06"
     *          }
     * @access public
     * @param
     * @return
     */
    public function settlement_dwd_dwd6(& $bet = [], & $lottery = [])
    {
        $balls = get_balls($bet[2]);
        $ret = null;
        // 一个位只能中一球,最多中五球
        $winBalls = array_slice($lottery['base'], 5, 5);
        foreach ($winBalls as $k => $v) {
            $baseContents = ['', '', '', '', ''];
            if (in_array($v, $balls[$k])) {
                $baseContents[$k] = $v;
                $ret['win_contents'][] = $baseContents;
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
     * @brief 大小_第一名
     *          所选投注类型与开奖号码相对应，即为中奖，如第一名购买号码为大，开奖号码为大（6,7,8,9,10）即为中奖。
     *          如第一名购买号码为大，
     *          开奖号码第一位为大（6,7,8,9,10）即为中奖。（1,2,3,4,5,）即为不中奖。
     *          开奖号码：01 02 03 04 05 06 07 08 09 10即中定位胆。
     *          $bets = {
     *              gid:15,tid:1908,price:2,counts:3,price_sum:6,
     *              rate:"第一个赔率,第二个赔率...",
     *              rebate:反水比率,
     *              pids:"第一球products_id1,第一球products_id2|第二球products_id1|第三球不选为空|第四球products_id1...",
     *              contents:"第一球code1,第一球code2|第二球code1|第三球不选为空|第四球code1...",
     *              names:"01,05|08||03..."
     *          }
     *          demo:
     *          POST http://www.gc360.com/orders/bet/15/20170327024/1908
     *          $bets = {
     *              gid:15,tid:1908,price:2,counts:3,price_sum:6,
     *              rate:"9.8,2.179",
     *              rebate:13,
     *              pids:"22418,22422|22426||22444,22446",
     *              contents:"1,5|3||4,6",
     *              names:"01,05|03||04,06"
     *          }
     * @access public
     * @param
     * @return
     */
    public function settlement_dx_d1(& $bet = [], & $lottery = [])
    {
        $balls = get_balls($bet[2]);
        $ret = null;
        foreach ($balls[0] as $ball) {
            if (in_array($lottery['base'][0], $this->config_balls['dx'][$ball])) {
                $ret['win_contents'][] = [$ball];
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
     * @brief 大小_第二名
     *          所选投注类型开奖号码相对应，即为中奖，如第二名购买号码为大，开奖号码为大（6,7,8,9,10）即为中奖。
     *          如第二名购买号码为大，开奖号码第二位为大（6,7,8,9,10）
     *          即为中奖。（1,2,3,4,5,）即为不中奖。
     *          $bets = {
     *              gid:15,tid:1908,price:2,counts:3,price_sum:6,
     *              rate:"第一个赔率,第二个赔率...",
     *              rebate:反水比率,
     *              pids:"第一球products_id1,第一球products_id2|第二球products_id1|第三球不选为空|第四球products_id1...",
     *              contents:"第一球code1,第一球code2|第二球code1|第三球不选为空|第四球code1...",
     *              names:"01,05|08||03..."
     *          }
     *          demo:
     *          POST http://www.gc360.com/orders/bet/15/20170327024/1908
     *          $bets = {
     *              gid:15,tid:1908,price:2,counts:3,price_sum:6,
     *              rate:"9.8,2.179",
     *              rebate:13,
     *              pids:"22418,22422|22426||22444,22446",
     *              contents:"1,5|3||4,6",
     *              names:"01,05|03||04,06"
     *          }
     * @access public
     * @param
     * @return
     */
    public function settlement_dx_d2(& $bet = [], & $lottery = [])
    {
        $balls = get_balls($bet[2]);
        $ret = null;
        foreach ($balls[0] as $ball) {
            if (in_array($lottery['base'][1], $this->config_balls['dx'][$ball])) {
                $ret['win_contents'][] = [$ball];
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
     * @brief 大小_第三名
     *          所选投注类型与开奖号码相对应，即为中奖，如第三名购买号码为大，开奖号码为大（6,7,8,9,10）即为中奖。
     *          如第三名购买号码为大，
     *          开奖号码第三位为大（6,7,8,9,10）即为中奖。（1,2,3,4,5,）即为不中奖。
     *          $bets = {
     *              gid:15,tid:1908,price:2,counts:3,price_sum:6,
     *              rate:"第一个赔率,第二个赔率...",
     *              rebate:反水比率,
     *              pids:"第一球products_id1,第一球products_id2|第二球products_id1|第三球不选为空|第四球products_id1...",
     *              contents:"第一球code1,第一球code2|第二球code1|第三球不选为空|第四球code1...",
     *              names:"01,05|08||03..."
     *          }
     *          demo:
     *          POST http://www.gc360.com/orders/bet/15/20170327024/1908
     *          $bets = {
     *              gid:15,tid:1908,price:2,counts:3,price_sum:6,
     *              rate:"9.8,2.179",
     *              rebate:13,
     *              pids:"22418,22422|22426||22444,22446",
     *              contents:"1,5|3||4,6",
     *              names:"01,05|03||04,06"
     *          }
     * @access public
     * @param
     * @return
     */
    public function settlement_dx_d3(& $bet = [], & $lottery = [])
    {
        $balls = get_balls($bet[2]);
        $ret = null;
        foreach ($balls[0] as $ball) {
            if (in_array($lottery['base'][2], $this->config_balls['dx'][$ball])) {
                $ret['win_contents'][] = [$ball];
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
     * @brief 单双_第一名
     *          所选投注类型与开奖号码相对应，即为中奖，如第一名购买号码为单，开奖号码为单（1,3,5,7,9）即为中奖。
     *          如第一名购买号码为单，
     *          开奖号码第一位为单（1,3,5,7,9）即为中奖。（2,4,6,8,10,）即为不中奖。
     *          $bets = {
     *              gid:15,tid:1908,price:2,counts:3,price_sum:6,
     *              rate:"第一个赔率,第二个赔率...",
     *              rebate:反水比率,
     *              pids:"第一球products_id1,第一球products_id2|第二球products_id1|第三球不选为空|第四球products_id1...",
     *              contents:"第一球code1,第一球code2|第二球code1|第三球不选为空|第四球code1...",
     *              names:"01,05|08||03..."
     *          }
     *          demo:
     *          POST http://www.gc360.com/orders/bet/15/20170327024/1908
     *          $bets = {
     *              gid:15,tid:1908,price:2,counts:3,price_sum:6,
     *              rate:"9.8,2.179",
     *              rebate:13,
     *              pids:"22418,22422|22426||22444,22446",
     *              contents:"1,5|3||4,6",
     *              names:"01,05|03||04,06"
     *          }
     * @access public
     * @param
     * @return
     */
    public function settlement_ds_d1(& $bet = [], & $lottery = [])
    {
        $balls = get_balls($bet[2]);
        $ret = null;
        foreach ($balls[0] as $ball) {
            if (in_array($lottery['base'][0], $this->config_balls['ds'][$ball])) {
                $ret['win_contents'][] = [$ball];
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
     * @brief 单双_第二名
     *          所选投注类型与开奖号码相对应，即为中奖，如第二位购买号码为单，开奖号码为单（1,3,5,7,9）即为中奖。
     *          如第二名购买号码为单，
     *          开奖号码第二位为单（1,3,5,7,9）即为中奖。（2,4,6,8,10,）即为不中奖。
     *          $bets = {
     *              gid:15,tid:1908,price:2,counts:3,price_sum:6,
     *              rate:"第一个赔率,第二个赔率...",
     *              rebate:反水比率,
     *              pids:"第一球products_id1,第一球products_id2|第二球products_id1|第三球不选为空|第四球products_id1...",
     *              contents:"第一球code1,第一球code2|第二球code1|第三球不选为空|第四球code1...",
     *              names:"01,05|08||03..."
     *          }
     *          demo:
     *          POST http://www.gc360.com/orders/bet/15/20170327024/1908
     *          $bets = {
     *              gid:15,tid:1908,price:2,counts:3,price_sum:6,
     *              rate:"9.8,2.179",
     *              rebate:13,
     *              pids:"22418,22422|22426||22444,22446",
     *              contents:"1,5|3||4,6",
     *              names:"01,05|03||04,06"
     *          }
     * @access public
     * @param
     * @return
     */
    public function settlement_ds_d2(& $bet = [], & $lottery = [])
    {
        $balls = get_balls($bet[2]);
        $ret = null;
        foreach ($balls[0] as $ball) {
            if (in_array($lottery['base'][1], $this->config_balls['ds'][$ball])) {
                $ret['win_contents'][] = [$ball];
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
     * @brief 单双_第三名
     *          所选投注类型与开奖号码相对应，即为中奖，如第二位购买号码为单，开奖号码为单（1,3,5,7,9）即为中奖。
     *          如第三名购买号码为单，
     *          开奖号码第三位为单（1,3,5,7,9）即为中奖。（2,4,6,8,10,）即为不中奖。
     *          $bets = {
     *              gid:15,tid:1908,price:2,counts:3,price_sum:6,
     *              rate:"第一个赔率,第二个赔率...",
     *              rebate:反水比率,
     *              pids:"第一球products_id1,第一球products_id2|第二球products_id1|第三球不选为空|第四球products_id1...",
     *              contents:"第一球code1,第一球code2|第二球code1|第三球不选为空|第四球code1...",
     *              names:"01,05|08||03..."
     *          }
     *          demo:
     *          POST http://www.gc360.com/orders/bet/15/20170327024/1908
     *          $bets = {
     *              gid:15,tid:1908,price:2,counts:3,price_sum:6,
     *              rate:"9.8,2.179",
     *              rebate:13,
     *              pids:"22418,22422|22426||22444,22446",
     *              contents:"1,5|3||4,6",
     *              names:"01,05|03||04,06"
     *          }
     * @access public
     * @param
     * @return
     */
    public function settlement_ds_d3(& $bet = [], & $lottery = [])
    {
        $balls = get_balls($bet[2]);
        $ret = null;
        foreach ($balls[0] as $ball) {
            if (in_array($lottery['base'][2], $this->config_balls['ds'][$ball])) {
                $ret['win_contents'][] = [$ball];
            }
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

