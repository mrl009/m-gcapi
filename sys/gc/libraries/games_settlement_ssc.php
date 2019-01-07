<?php
/**
 * @file games_settlement_ssc.php
 * @brief 时时彩结算库
 *      NOTE: 如果玩法为和局，status 返回 STATUS_HE: $ret['status'] = STATUS_HE;
 *
 * Copyright (C) 2017 GC.COM
 * All rights reserved.
 *
 * @package libraries
 * @author Langr <hua@langr.org> 2017/04/06 20:41
 *
 * $Id$
 */

include_once(dirname(__FILE__) . '/games.php');

class games_settlement_ssc
{
    public $config_balls = [
        /* 基本球 */
        'base' => [0, 1, 2, 3, 4, 5, 6, 7, 8, 9],
        /* 前三/中三/后三 直选 和值 */
        '3x_hz' => [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27],
        /* 二星 后二/前二 直选 和值 */
        '2x_hz' => [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18],
        /* 龙虎: 龙133, 虎131, 和200 */
        'lh' => [133, 131, 200],
        /* 二星大小单双*/
        '2x_dxds' => [
            '100' => [5, 6, 7, 8, 9], // 大
            '101' => [0, 1, 2, 3, 4], // 小
            '102' => [1, 3, 5, 7, 9], // 单
            '103' => [0, 2, 4, 6, 8], // 双
        ],
        /* 万位*/
        'ww' => '291',
        /* 千位*/
        'qw' => '292',
        /* 百位*/
        'bw' => '293',
        /* 十位*/
        'sw' => '294',
        /* 个位*/
        'gw' => '295',
    ];
    
    /**
     * @brief 根据彩种当期的开奖号计算出各种玩法的开奖值
     *      如：某 ssc 20170404043当期开奖: 7, 0, 3, 6, 2;
     *          则计算出前三和值开奖值: 10, 中三和值: 9, 后三和值11.
     * @access public/protected
     * @param
     * @return
     */
    public function wins_balls(& $wins_balls = []) /* {{{ */
    {
        //$wins_balls['base'] = ['1', '2', '3', '4', '5'];
        if (count($wins_balls['base']) != 5 ||
                !in_array($wins_balls['base'][0], $this->config_balls['base']) ||
                !in_array($wins_balls['base'][1], $this->config_balls['base']) ||
                !in_array($wins_balls['base'][2], $this->config_balls['base']) ||
                !in_array($wins_balls['base'][3], $this->config_balls['base']) ||
                !in_array($wins_balls['base'][4], $this->config_balls['base'])
                ) {
            $msg = date('[Y-m-d H:i:s]').'[ssc] lottery error:'.implode(',', $wins_balls['base'])."\n";
            error_log($msg, 3, 'api/logs/wins_balls_error_'.date('Ym').'.log');
            exit($msg);
        }
        $wins_balls['q3_he'] = $wins_balls['base'][0] + $wins_balls['base'][1] + $wins_balls['base'][2];
        $wins_balls['z3_he'] = $wins_balls['base'][1] + $wins_balls['base'][2] + $wins_balls['base'][3];
        $wins_balls['h3_he'] = $wins_balls['base'][2] + $wins_balls['base'][3] + $wins_balls['base'][4];
        $wins_balls['h2_he'] = $wins_balls['base'][3] + $wins_balls['base'][4];
        $wins_balls['q2_he'] = $wins_balls['base'][0] + $wins_balls['base'][1];
        /* Other... */
    
        return $wins_balls;
    } /* }}} */
    
    /**
     * @brief 五星_五星直选_复式
     *          从万位、千位、百位、十位、个位中选择一个5位数号码组成一注，所选号码与开奖号码全部相同，且顺序一致，即为中奖。
     *          投注方案：23456；开奖号码：23456，即中五星直选。
     *          $bet = "[\"1570329144415671\",1,\"3|2,4,5||6\",2,3,6,\"6.12\",13]";
     *          $bet = "[\"1570329155205234\",1,\"3,4,5\",2,3,6,\"6.12\",13]";
     * @access public
     * @param array $bet
     * @param array $lottery
     * @link http://www.gc360.com/orders/bet/6/555
     * @return
     * 中奖，和局，未中奖
     *      ret = ['win_contents'=>[[1,3]], 'win_counts'=>1, 'price_sum'=>3.9, 'status'=>STATUS_WIN];
     *      ret = ['win_contents'=>[], 'win_counts'=>0, 'price_sum'=>4, 'status'=>STATUS_HE];
     *      ret = null;
     */
    public function settlement_5x_5xzhx_fs(& $bet = [], & $lottery = [])
    {
        $balls = get_balls($bet[2]);
        $ret = null;
        // 只能中一注
        if (in_array($lottery['base'][0], $balls[0]) && in_array($lottery['base'][1], $balls[1]) && in_array($lottery['base'][2], $balls[2])
            && in_array($lottery['base'][3], $balls[3]) && in_array($lottery['base'][4], $balls[4])
        ) {
            $ret['win_contents'][] = $lottery['base'];
        }
    
        if (!empty($ret['win_contents'])) {
            $ret['win_counts'] = count($ret['win_contents']);
            $ret['price_sum'] = $bet[3] * $bet[6] * $ret['win_counts'];
            $ret['status'] = STATUS_WIN;
        }
        return $ret;
    }
    
    /**
     * @brief 五星_五星直选_单式
     *          手动输入一个5位数号码组成一注，所选号码的万位、千位、百位、十位、个位与开奖号码相同，且顺序一致，即为中奖。
     *          投注方案：23456；开奖号码：23456，即中五星直选。
     * @access public
     * @param array $bet
     * @param array $lottery
     * @return
     */
    public function settlement_5x_5xzhx_ds(& $bet = [], & $lottery = [])
    {
        return $this->settlement_5x_5xzhx_fs($bet, $lottery);
    }
    
    /**
     * @brief 五星_五星直选_组合
     *          从万位、千位、百位、十位、个位中至少各选一个号码组成1-5星的组合，共五注，
     *          所选号码的个位与开奖号码相同，则中1个5等奖；所选号码的个位、十位与开奖号码相同，则中1个5等奖以及1个4等奖，依此类推，最高可中5个奖。
     *          五星组合示例:
     *          如购买：4+5+6+7+8，该票共10元，由以下5注：45678(五星)、5678(四星)、678(三星)、78(二星)、8(一星)构成。
     *          开奖号码：45678，即可中五星、四星、三星、二星、一星奖各1注。
     * @access public
     * @param array $bet
     * @param array $lottery
     * @return
     */
    public function settlement_5x_5xzhx_zh(& $bet = [], & $lottery = [])
    {
        $balls = get_balls($bet[2]);
        $ret = null;
        $ret['price_sum'] = 0;
        $c = CC($balls, 5, true);
        foreach ($c['data'] as $v) {
            if ($lottery['base'][4] == $v[4]) {
                // 中一星
                $rate = explode(',', $bet[6]);
                $ret['price_sum'] += $bet[3] * $rate[4];
                $ret['win_contents'][] = ['', '', '', '', $lottery['base'][4]];
                if ($lottery['base'][3] == $v[3]) {
                    // 中了一星才可能中二星
                    $ret['price_sum'] += $bet[3] * $rate[3];
                    $ret['win_contents'][] = ['', '', '', $lottery['base'][3], $lottery['base'][4]];
                    if ($lottery['base'][2] == $v[2]) {
                        // 中三星
                        $ret['price_sum'] += $bet[3] * $rate[2];
                        $ret['win_contents'][] = ['', '', $lottery['base'][2], $lottery['base'][3], $lottery['base'][4]];
                        if ($lottery['base'][1] == $v[1]) {
                            // 中四星
                            $ret['price_sum'] += $bet[3] * $rate[1];
                            $ret['win_contents'][] = ['', $lottery['base'][1], $lottery['base'][2], $lottery['base'][3], $lottery['base'][4]];
                            if ($lottery['base'][0] == $v[0]) {
                                // 中五星
                                $ret['price_sum'] += $bet[3] * $rate[0];
                                $ret['win_contents'][] = $lottery['base'];
                            }
                        }
                    }
                }
            }
        }
    
        if (!empty($ret['win_contents'])) {
            $ret['win_counts'] = count($ret['win_contents']);
            $ret['status'] = STATUS_WIN;
        }
        return $ret;
    }
    
    /**
     * @brief 五星_五星组选_组选120
     *          从0-9中任意选择5个号码组成一注，所选号码与开奖号码的万位、千位、百位、十位、个位相同，顺序不限，即为中奖。
     *          投注方案：02568，开奖号码的五个数字只要包含0、2、5、6、8，即可中五星组选120一等奖。
     * @access public
     * @param array $bet
     * @param array $lottery
     * @return
     */
    public function settlement_5x_5xzx_zx120(& $bet = [], & $lottery = [])
    {
        $balls = get_balls($bet[2]);
        $ret = null;
        // 开奖的球没有相同时才可能中奖
        if (count(array_unique($lottery['base'])) == 5) {
            if (in_array($lottery['base'][0], $balls[0]) && in_array($lottery['base'][1], $balls[0]) && in_array($lottery['base'][2], $balls[0])
                && in_array($lottery['base'][3], $balls[0]) && in_array($lottery['base'][4], $balls[0])
            ) {
                $ret['win_contents'][] = $lottery['base'];
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
     * @brief 五星_五星组选_组选60
     *          选择1个二重号码和3个单号号码组成一注，所选的单号号码与开奖号码相同，且所选二重号码在开奖号码中出现了2次，即为中奖。
     *          投注方案：二重号：8，单号：0、2、5，只要开奖的5个数字包括 0、2、5、8、8，即可中五星组选60一等奖。
     * @access public
     * @param array $bet
     * @param array $lottery
     * @return
     */
    public function settlement_5x_5xzx_zx60(& $bet = [], & $lottery = [])
    {
        $balls = get_balls($bet[2]);
        $ret = null;
        // 开奖的球有两个相同时才可能中奖
        $unique_arr = array_unique($lottery['base']);
        if (count($unique_arr) == 4) {
            // 二重号
            $repeat_arr = array_values(array_diff_assoc($lottery['base'], $unique_arr));
            // 单号
            $unique_arr = array_values(array_diff($unique_arr, $repeat_arr));
            // 二重号跟单号都中才算中奖
            if (count($repeat_arr) == 1 && in_array($repeat_arr[0], $balls[0])) {
                if (count($unique_arr) == 3 && in_array($unique_arr[0], $balls[1]) && in_array($unique_arr[1], $balls[1])
                    && in_array($unique_arr[2], $balls[1])
                ) {
                    $ret['win_contents'][] = $lottery['base'];
                }
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
     * @brief 五星_五星组选_组选30
     *          选择2个二重号和1个单号号码组成一注，所选的单号号码与开奖号码相同，且所选的2个二重号码分别在开奖号码中出现了2次，即为中奖。
     *          投注方案：二重号：2、8，单号：0，只要开奖的5个数字包括 0、2、2、8、8，即可中五星组选30一等奖。
     * @access public
     * @param array $bet
     * @param array $lottery
     * @return
     */
    public function settlement_5x_5xzx_zx30(& $bet = [], & $lottery = [])
    {
        $balls = get_balls($bet[2]);
        $ret = null;
        $unique_arr = array_unique($lottery['base']);
        if (count($unique_arr) == 3) {
            // 二重号
            $repeat_arr = array_values(array_diff_assoc($lottery['base'], $unique_arr));
            // 单号
            $unique_arr = array_values(array_diff($unique_arr, $repeat_arr));
            // 二重号跟单号都中才算中奖
            if ($repeat_arr[0] != $repeat_arr[1] && in_array($repeat_arr[0], $balls[0]) && in_array($repeat_arr[1], $balls[0])) {
                if (count($unique_arr) == 1 && in_array($unique_arr[0], $balls[1])) {
                    $ret['win_contents'][] = $lottery['base'];
                }
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
     * @brief 五星_五星组选_组选20
     *          选择1个三重号码和2个单号号码组成一注，所选的单号号码与开奖号码相同，且所选三重号码在开奖号码中出现了3次，即为中奖。
     *          投注方案：三重号：8，单号：0、2，只要开奖的5个数字包括 0、2、8、8、8，即可中五星组选20一等奖。
     * @access public
     * @param array $bet
     * @param array $lottery
     * @return
     */
    public function settlement_5x_5xzx_zx20(& $bet = [], & $lottery = [])
    {
        $balls = get_balls($bet[2]);
        $ret = null;
        $unique_arr = array_unique($lottery['base']);
        if (count($unique_arr) == 3) {
            // 三重号
            $repeat_arr = array_values(array_diff_assoc($lottery['base'], $unique_arr));
            // 单号
            $unique_arr = array_values(array_diff($unique_arr, $repeat_arr));
            // 三重号跟单号都中才算中奖
            if ($repeat_arr[0] == $repeat_arr[1] && in_array($repeat_arr[0], $balls[0])) {
                if (count($unique_arr) == 2 && in_array($unique_arr[0], $balls[1]) && in_array($unique_arr[1], $balls[1])) {
                    $ret['win_contents'][] = $lottery['base'];
                }
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
     * @brief 五星_五星组选_组选10
     *          选择1个三重号码和1个二重号码，所选三重号码在开奖号码中出现3次，并且所选二重号码在开奖号码中出现了2次，即为中奖。
     *          投注方案：三重号：8，二重号：2，只要开奖的5个数字包括 2、2、8、8、8，即可中五星组选10一等奖。
     * @access public
     * @param array $bet
     * @param array $lottery
     * @return
     */
    public function settlement_5x_5xzx_zx10(& $bet = [], & $lottery = [])
    {
        $balls = get_balls($bet[2]);
        $ret = null;
        if (count(array_unique($lottery['base'])) == 2) {
            $arr = array_flip(array_count_values($lottery['base']));
            // 三重号跟二重号都中才算中奖
            if (isset($arr[3]) && in_array($arr[3], $balls[0])) {
                if (isset($arr[2]) && in_array($arr[2], $balls[1])) {
                    $ret['win_contents'][] = $lottery['base'];
                }
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
     * @brief 五星_五星组选_组选5
     *          选择1个四重号码和1个单号号码组成一注，所选的单号号码与开奖号码相同，且所选四重号码在开奖号码中出现了4次，即为中奖。
     *          投注方案：四重号：8，单号：2，只要开奖的5个数字包括 2、8、8、8、8，即可中五星组选5一等奖。
     * @access public
     * @param array $bet
     * @param array $lottery
     * @return
     */
    public function settlement_5x_5xzx_zx5(& $bet = [], & $lottery = [])
    {
        $balls = get_balls($bet[2]);
        $ret = null;
        if (count(array_unique($lottery['base'])) == 2) {
            $arr = array_flip(array_count_values($lottery['base']));
            // 四重号跟单号都中才算中奖
            if (isset($arr[4]) && in_array($arr[4], $balls[0])) {
                if (isset($arr[1]) && in_array($arr[1], $balls[1])) {
                    $ret['win_contents'][] = $lottery['base'];
                }
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
     * @brief 后四_后四直选_复式
     *          从千位、百位、十位、个位中至少各选1个号码组成一注，所选号码与开奖后4位相同，且顺序一致，即为中奖。
     *          投注方案：* 6 7 8 9
     *          开奖号码：* 6 7 8 9 即中四星直选。
     * @access public
     * @param array $bet
     * @param array $lottery
     * @return
     */
    public function settlement_h4_h4zhx_fs(& $bet = [], & $lottery = [])
    {
        $balls = get_balls($bet[2]);
        $ret = null;
        if (in_array($lottery['base'][1], $balls[0]) && in_array($lottery['base'][2], $balls[1]) && in_array($lottery['base'][3], $balls[2])
            && in_array($lottery['base'][4], $balls[3])
        ) {
            $ret['win_contents'][] = [$lottery['base'][1], $lottery['base'][2], $lottery['base'][3], $lottery['base'][4]];
        }
    
        if (!empty($ret['win_contents'])) {
            $ret['win_counts'] = count($ret['win_contents']);
            $ret['price_sum'] = $bet[3] * $bet[6] * $ret['win_counts'];
            $ret['status'] = STATUS_WIN;
        }
        return $ret;
    }
    
    /**
     * @brief 后四_后四直选_单式
     *          手动输入一个4位数号码组成一注，所选号码的千、百、十、个位与开奖号码相同，且顺序一致，即为中奖。
     *          投注方案：* 6 7 8 9
     *          开奖号码：* 6 7 8 9 即中四星直选。
     * @access public
     * @param array $bet
     * @param array $lottery
     * @return
     */
    public function settlement_h4_h4zhx_ds(& $bet = [], & $lottery = [])
    {
        return $this->settlement_h4_h4zhx_fs($bet, $lottery);
    }
    
    /**
     * @brief 后四_后四直选_组合
     *          从千位、百位、十位、个位中至少各选一个号码组成1-4星的组合共4注，所选号码的个位与开奖号码全部相同，则中1个四等奖；
     *          所选号码的十位、个位与开奖号码全部相同，则中一个四等奖以及一个三等奖，依此类推，最高可中4个奖。
     *          投注方案：5 6 7 8，有以下4注：5678（四星）、678（三星）、78（二星）、8（一星）构成。
     *          开奖号码：5678，即中四星、三星、二星、一星各1注
     * @access public
     * @param array $bet
     * @param array $lottery
     * @return
     */
    public function settlement_h4_h4zhx_zh(& $bet = [], & $lottery = [])
    {
        $balls = get_balls($bet[2]);
        $ret = null;
        $ret['price_sum'] = 0;
        $c = CC($balls, 4, true);
        foreach ($c['data'] as $v) {
            if ($lottery['base'][4] == $v[3]) {
                // 中一星
                $rate = explode(',', $bet[6]);
                $ret['price_sum'] += $bet[3] * $rate[3];
                $ret['win_contents'][] = ['', '', '', '', $lottery['base'][4]];
                if ($lottery['base'][3] == $v[2]) {
                    // 中二星
                    $ret['price_sum'] += $bet[3] * $rate[2];
                    $ret['win_contents'][] = ['', '', '', $lottery['base'][3], $lottery['base'][4]];
                    if ($lottery['base'][2] == $v[1]) {
                        // 中三星
                        $ret['price_sum'] += $bet[3] * $rate[1];
                        $ret['win_contents'][] = ['', '', $lottery['base'][2], $lottery['base'][3], $lottery['base'][4]];
                        if ($lottery['base'][1] == $v[0]) {
                            // 中四星
                            $ret['price_sum'] += $bet[3] * $rate[0];
                            $ret['win_contents'][] = ['', $lottery['base'][1], $lottery['base'][2], $lottery['base'][3], $lottery['base'][4]];
                        }
                    }
                }
            }
        }
    
        if (!empty($ret['win_contents'])) {
            $ret['win_counts'] = count($ret['win_contents']);
            $ret['status'] = STATUS_WIN;
        }
        return $ret;
    }
    
    /**
     * @brief 后四_后四组选_组选24
     *          从0-9中任意选择4个号码组成一注，后四位开奖号码包含所选号码，且顺序不限，即为中奖。
     *          投注方案：0 5 6 8
     *          开奖号码：* 8 5 6 0（顺序不限）即中后四组选24。
     * @access public
     * @param array $bet
     * @param array $lottery
     * @return
     */
    public function settlement_h4_h4zx_zx24(& $bet = [], & $lottery = [])
    {
        $balls = get_balls($bet[2]);
        $ret = null;
        // 开奖的球没有相同时才可能中奖
        if (count(array_unique(array($lottery['base'][1], $lottery['base'][2], $lottery['base'][3], $lottery['base'][4]))) == 4) {
            if (in_array($lottery['base'][1], $balls[0]) && in_array($lottery['base'][2], $balls[0]) && in_array($lottery['base'][3], $balls[0])
                && in_array($lottery['base'][4], $balls[0])
            ) {
                $ret['win_contents'][] = [$lottery['base'][1], $lottery['base'][2], $lottery['base'][3], $lottery['base'][4]];
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
     * @brief 后四_后四组选_组选12
     *          选择1个二重号码和2个单号号码组成一注，所选单号号码与开奖号码相同，且所选二重号码在开奖号码中出现2次，即为中奖。
     *          投注方案：二重号：8，单号：0、6，只要开奖的四个数字包括 0、6、8、8，即可中四星组选12。
     * @access public
     * @param array $bet
     * @param array $lottery
     * @return
     */
    public function settlement_h4_h4zx_zx12(& $bet = [], & $lottery = [])
    {
        $balls = get_balls($bet[2]);
        $ret = null;
        $unique_arr = array_unique(array($lottery['base'][1], $lottery['base'][2], $lottery['base'][3], $lottery['base'][4]));
        if (count($unique_arr) == 3) {
            // 二重号
            $repeat_arr = array_values(array_diff_assoc(array($lottery['base'][1], $lottery['base'][2], $lottery['base'][3], $lottery['base'][4]), $unique_arr));
            // 单号
            $unique_arr = array_values(array_diff($unique_arr, $repeat_arr));
            // 二重号跟单号都中才算中奖
            if (count($repeat_arr) == 1 && in_array($repeat_arr[0], $balls[0])) {
                if (count($unique_arr) == 2 && in_array($unique_arr[0], $balls[1]) && in_array($unique_arr[1], $balls[1])) {
                    $ret['win_contents'][] = [$lottery['base'][1], $lottery['base'][2], $lottery['base'][3], $lottery['base'][4]];
                }
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
     * @brief 后四_后四组选_组选6
     *          选择2个二重号码组成一注，所选的2个二重号码在开奖号码中分别出现了2次，即为中奖。
     *          投注方案：二重号：6、8，只要开奖的四个数字从小到大排列为 6、6、8、8，即可中四星组选6
     * @access public
     * @param array $bet
     * @param array $lottery
     * @return
     */
    public function settlement_h4_h4zx_zx6(& $bet = [], & $lottery = [])
    {
        $balls = get_balls($bet[2]);
        $ret = null;
        /*if (count(array_unique(array($lottery['base'][1], $lottery['base'][2], $lottery['base'][3], $lottery['base'][4]))) == 2) {
            $arr1 = array_values(array_count_values(array($lottery['base'][1], $lottery['base'][2], $lottery['base'][3], $lottery['base'][4])));
            $arr2 = array_keys(array_count_values(array($lottery['base'][1], $lottery['base'][2], $lottery['base'][3], $lottery['base'][4])));
            if ($arr1[0] == 2 && $arr1[1] == 2) {
                if (in_array($arr2[0], $balls[0]) && in_array($arr2[1], $balls[0])) {
                    $ret['win_contents'][] = [$lottery['base'][1], $lottery['base'][2], $lottery['base'][3], $lottery['base'][4]];
                }
            }
        }*/
        $arr = array_count_values(array($lottery['base'][1], $lottery['base'][2], $lottery['base'][3], $lottery['base'][4]));
        if (count($arr) == 2) {
            $flag = true;
            foreach ($arr as $k => $v) {
                if ($v != 2 || !in_array($k, $balls[0])) {
                    $flag = false;
                    break;
                }
            }
            $flag && $ret['win_contents'][] = [$lottery['base'][1], $lottery['base'][2], $lottery['base'][3], $lottery['base'][4]];
        }
    
        if (!empty($ret['win_contents'])) {
            $ret['win_counts'] = count($ret['win_contents']);
            $ret['price_sum'] = $bet[3] * $bet[6] * $ret['win_counts'];
            $ret['status'] = STATUS_WIN;
        }
        return $ret;
    }
    
    /**
     * @brief 后四_后四组选_组选4
     *          选择1个三重号码和1个单号号码组成一注，所选单号号码与开奖号码相同，且所选三重号码在开奖号码中出现了3次，即为中奖。
     *          投注方案：三重号：8，单号：2，只要开奖的四个数字从小到大排列为 2、8、8、8，即可中四星组选4。
     * @access public
     * @param array $bet
     * @param array $lottery
     * @return
     */
    public function settlement_h4_h4zx_zx4(& $bet = [], & $lottery = [])
    {
        $balls = get_balls($bet[2]);
        $ret = null;
        $arr = array_flip(array_count_values(array($lottery['base'][1], $lottery['base'][2], $lottery['base'][3], $lottery['base'][4])));
        if (count($arr) == 2) {
            // 验证3重码
            if (isset($arr[3]) && in_array($arr[3], $balls[0])) {
                // 验证单码
                if (isset($arr[1]) && in_array($arr[1], $balls[1])) {
                    $ret['win_contents'][] = [$lottery['base'][1], $lottery['base'][2], $lottery['base'][3], $lottery['base'][4]];
                }
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
     * @brief 前四_前四直选_复式
     *          从万位、千位、百位、十位中选择一个4位数号码组成一注，所选号码与开奖号码相同，且顺序一致，即为中奖。
     *          投注方案：3456；开奖号码：3456*，即中四星直选。
     * @access public
     * @param array $bet
     * @param array $lottery
     * @return
     */
    public function settlement_q4_q4zhx_fs(& $bet = [], & $lottery = [])
    {
        $balls = get_balls($bet[2]);
        $ret = null;
        if (in_array($lottery['base'][0], $balls[0]) && in_array($lottery['base'][1], $balls[1]) && in_array($lottery['base'][2], $balls[2])
            && in_array($lottery['base'][3], $balls[3])
        ) {
            $ret['win_contents'][] = [$lottery['base'][0], $lottery['base'][1], $lottery['base'][2], $lottery['base'][3]];
        }
    
        if (!empty($ret['win_contents'])) {
            $ret['win_counts'] = count($ret['win_contents']);
            $ret['price_sum'] = $bet[3] * $bet[6] * $ret['win_counts'];
            $ret['status'] = STATUS_WIN;
        }
        return $ret;
    }
    
    /**
     * @brief 前四_前四直选_单式
     *          手动输入一个四位数号码组成一注，所选号码万位、千位、百位、十位与开奖号码相同，且顺序一致即为中奖。
     *          投注方案：3456；开奖号码：3456*，即中四星直选。
     * @access public
     * @param array $bet
     * @param array $lottery
     * @return
     */
    public function settlement_q4_q4zhx_ds(& $bet = [], & $lottery = [])
    {
        return $this->settlement_q4_q4zhx_fs($bet, $lottery);
    }
    
    /**
     * @brief 前四_前四直选_组合
     *          从万位、千位、百位、十位中至少各选一个号码组成1-4星的组合共4注，所选号码的个位与开奖号码全部相同，则中1个四等奖；
     *          所选号码的十位、个位与开奖号码全部相同，则中一个四等奖以及一个三等奖，依此类推，最高可中4个奖。
     *          投注方案：四星组合示例，如购买5+6+7+8，有以下4注：5678（四星）、678（三星）、78（二星）、8（一星）构成。
     *          开奖号码：5678，即中四星、三星、二星、一星各1注。
     * @access public
     * @param array $bet
     * @param array $lottery
     * @return
     */
    public function settlement_q4_q4zhx_zh(& $bet = [], & $lottery = [])
    {
        $balls = get_balls($bet[2]);
        $ret = null;
        $ret['price_sum'] = 0;
        $c = CC($balls, 4, true);
        foreach ($c['data'] as $v) {
            if ($lottery['base'][3] == $v[3]) {
                // 中一星
                $rate = explode(',', $bet[6]);
                $ret['price_sum'] += $bet[3] * $rate[3];
                $ret['win_contents'][] = ['', '', '', $lottery['base'][3], ''];
                if ($lottery['base'][2] == $v[2]) {
                    // 中二星
                    $ret['price_sum'] += $bet[3] * $rate[2];
                    $ret['win_contents'][] = ['', '', $lottery['base'][2], $lottery['base'][3], ''];
                    if ($lottery['base'][1] == $v[1]) {
                        // 中三星
                        $ret['price_sum'] += $bet[3] * $rate[1];
                        $ret['win_contents'][] = ['', $lottery['base'][1], $lottery['base'][2], $lottery['base'][3], ''];
                        if ($lottery['base'][0] == $v[0]) {
                            // 中四星
                            $ret['price_sum'] += $bet[3] * $rate[0];
                            $ret['win_contents'][] = [$lottery['base'][0], $lottery['base'][1], $lottery['base'][2], $lottery['base'][3], ''];
                        }
                    }
                }
            }
        }
    
        if (!empty($ret['win_contents'])) {
            $ret['win_counts'] = count($ret['win_contents']);
            $ret['status'] = STATUS_WIN;
        }
        return $ret;
    }
    
    /**
     * @brief 前四_前四组选_组选24
     *          从0-9中任意选择4个号码组成一注，所选号码与开奖号码的千位、百位、十位、个位相同，且顺序不限，即为中奖。
     *          投注方案：0568，开奖号码的四个数字只要包含0、5、6、8，即可中四星组选24一等奖。
     * @access public
     * @param array $bet
     * @param array $lottery
     * @return
     */
    public function settlement_q4_q4zx_zx24(& $bet = [], & $lottery = [])
    {
        $balls = get_balls($bet[2]);
        $ret = null;
        // 开奖的球没有相同时才可能中奖
        if (count(array_unique(array($lottery['base'][0], $lottery['base'][1], $lottery['base'][2], $lottery['base'][3]))) == 4) {
            if (in_array($lottery['base'][0], $balls[0]) && in_array($lottery['base'][1], $balls[0]) && in_array($lottery['base'][2], $balls[0])
                && in_array($lottery['base'][3], $balls[0])
            ) {
                $ret['win_contents'][] = [$lottery['base'][0], $lottery['base'][1], $lottery['base'][2], $lottery['base'][3]];
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
     * @brief 前四_前四组选_组选12
     *          选择1个二重号码和2个单号号码组成一注，所选单号号码与开奖号码相同，且所选二重号码在开奖号码中出现了2次，即为中奖。
     *          投注方案：二重号：8，单号：0、6，只要开奖的四个数字包括 0、6、8、8，即可中四星组选12一等奖。
     * @access public
     * @param array $bet
     * @param array $lottery
     * @return
     */
    public function settlement_q4_q4zx_zx12(& $bet = [], & $lottery = [])
    {
        $balls = get_balls($bet[2]);
        $ret = null;
        $unique_arr = array_unique(array($lottery['base'][0], $lottery['base'][1], $lottery['base'][2], $lottery['base'][3]));
        if (count($unique_arr) == 3) {
            // 二重号
            $repeat_arr = array_values(array_diff_assoc(array($lottery['base'][0], $lottery['base'][1], $lottery['base'][2], $lottery['base'][3]), $unique_arr));
            // 单号
            $unique_arr = array_values(array_diff($unique_arr, $repeat_arr));
            // 二重号跟单号都中才算中奖
            if (count($repeat_arr) == 1 && in_array($repeat_arr[0], $balls[0])) {
                if (count($unique_arr) == 2 && in_array($unique_arr[0], $balls[1]) && in_array($unique_arr[1], $balls[1])) {
                    $ret['win_contents'][] = [$lottery['base'][0], $lottery['base'][1], $lottery['base'][2], $lottery['base'][3]];
                }
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
     * @brief 前四_前四组选_组选6
     *          选择2个二重号码组成一注，所选的2个二重号码在开奖号码中分别出现了2次，即为中奖。
     *          投注方案：二重号：6、8，只要开奖的四个数字从小到大排列为 6、6、8、8，即可中四星组选6
     * @access public
     * @param array $bet
     * @param array $lottery
     * @return
     */
    public function settlement_q4_q4zx_zx6(& $bet = [], & $lottery = [])
    {
        $balls = get_balls($bet[2]);
        $ret = null;
        $arr = array_count_values(array($lottery['base'][0], $lottery['base'][1], $lottery['base'][2], $lottery['base'][3]));
        if (count($arr) == 2) {
            $flag = true;
            foreach ($arr as $k => $v) {
                if ($v != 2 || !in_array($k, $balls[0])) {
                    $flag = false;
                    break;
                }
            }
            $flag && $ret['win_contents'][] = [$lottery['base'][0], $lottery['base'][1], $lottery['base'][2], $lottery['base'][3]];
        }
    
        if (!empty($ret['win_contents'])) {
            $ret['win_counts'] = count($ret['win_contents']);
            $ret['price_sum'] = $bet[3] * $bet[6] * $ret['win_counts'];
            $ret['status'] = STATUS_WIN;
        }
        return $ret;
    }
    
    /**
     * @brief 前四_前四组选_组选4
     *          选择1个三重号码和1个单号号码组成一注，所选单号号码与开奖号码相同，且所选三重号码在开奖号码中出现了3次，即为中奖。
     *          投注方案：三重号：8，单号：2，只要开奖的四个数字从小到大排列为 2、8、8、8，即可中四星组选4。
     * @access public
     * @param array $bet
     * @param array $lottery
     * @return
     */
    public function settlement_q4_q4zx_zx4(& $bet = [], & $lottery = [])
    {
        $balls = get_balls($bet[2]);
        $ret = null;
        $arr = array_flip(array_count_values(array($lottery['base'][0], $lottery['base'][1], $lottery['base'][2], $lottery['base'][3])));
        if (count($arr) == 2) {
            // 验证3重码
            if (isset($arr[3]) && in_array($arr[3], $balls[0])) {
                // 验证单码
                if (isset($arr[1]) && in_array($arr[1], $balls[1])) {
                    $ret['win_contents'][] = [$lottery['base'][0], $lottery['base'][1], $lottery['base'][2], $lottery['base'][3]];
                }
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
     * @brief 后三_后三直选_复式
     *          从百位、十位、个位中选择一个3位数号码组成一注，所选号码与开奖号码后3位相同，且顺序一致，即为中奖。
     *          投注方案：345；投注方案：345；即中后三直选。
     * @access public
     * @param array $bet
     * @param array $lottery
     * @return
     */
    public function settlement_h3_h3zhx_fs(& $bet = [], & $lottery = [])
    {
        $balls = get_balls($bet[2]);
        $ret = null;
        if (in_array($lottery['base'][2], $balls[0]) && in_array($lottery['base'][3], $balls[1]) && in_array($lottery['base'][4], $balls[2])) {
            $ret['win_contents'][] = [$lottery['base'][2], $lottery['base'][3], $lottery['base'][4]];
        }
    
        if (!empty($ret['win_contents'])) {
            $ret['win_counts'] = count($ret['win_contents']);
            $ret['price_sum'] = $bet[3] * $bet[6] * $ret['win_counts'];
            $ret['status'] = STATUS_WIN;
        }
        return $ret;
    }
    
    /**
     * @brief 后三_后三直选_单式
     *          手动输入一个3位数号码组成一注，所选号码的百位、十位、个位与开奖号码相同，且顺序一致，即为中奖。
     *          投注方案：345；投注方案：345；即中后三直选。
     * @access public
     * @param array $bet
     * @param array $lottery
     * @return
     */
    public function settlement_h3_h3zhx_ds(& $bet = [], & $lottery = [])
    {
        return $this->settlement_h3_h3zhx_fs($bet, $lottery);
    }
    
    /**
     * @brief 后三_后三直选_直选和值
     *          所选数值等于开奖号码的百位、十位、个位三个数字相加之和，即为中奖。
     *          投注方案：和值1；开奖号码后三位：001,010,100,即中后三直选。
     * @access public
     * @param array $bet
     * @param array $lottery
     * @return
     */
    public function settlement_h3_h3zhx_zxhz(& $bet = [], & $lottery = [])
    {
        $balls = get_balls($bet[2]);
        $ret = null;
        if (in_array($lottery['h3_he'], $balls[0])) {
            $ret['win_contents'][] = [$lottery['h3_he']];
        }
    
        if (!empty($ret['win_contents'])) {
            $ret['win_counts'] = count($ret['win_contents']);
            $ret['price_sum'] = $bet[3] * $bet[6] * $ret['win_counts'];
            $ret['status'] = STATUS_WIN;
        }
        return $ret;
    }
    
    /**
     * @brief 后三_后三组选_组选组3
     *          从0-9中选择2个数字组成两注，所选号码与开奖号码的百位、十位、个位相同，且顺序不限，即为中奖。
     *          投注方案58，开奖号码1个5,2个8；或者2个5，一个8（顺序不限），即中组选三。
     * @access public
     * @param array $bet
     * @param array $lottery
     * @return
     */
    public function settlement_h3_h3zx_zx3(& $bet = [], & $lottery = [])
    {
        $balls = get_balls($bet[2]);
        $ret = null;
        $tempLottery = array(
            $lottery['base'][2],
            $lottery['base'][3],
            $lottery['base'][4],
        );
        $tempLottery = array_values(array_unique($tempLottery));
        if (count($tempLottery) == 2) {
            if (in_array($tempLottery[0], $balls[0]) && in_array($tempLottery[1], $balls[0])) {
                // 要么不中要么中一注
                $ret['win_contents'][] = $tempLottery;
                //$ret['win_contents'][] = array_reverse($tempLottery);
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
     * @brief 后三_后三组选_组选组6
     *          从0-9中任意选择3个号码组成一注，所选号码与开奖号码的百位、十位、个位相同，顺序不限，即为中奖。
     *          投注方案：2,5,8；开奖号码后三位：1个2、1个5、1个8 (顺序不限)，即中后三组选六
     * @access public
     * @param array $bet
     * @param array $lottery
     * @return
     */
    public function settlement_h3_h3zx_zx6(& $bet = [], & $lottery = [])
    {
        $balls = get_balls($bet[2]);
        $ret = null;
        if (count(array_unique(array($lottery['base'][2],$lottery['base'][3],$lottery['base'][4]))) == 3) {
            if (in_array($lottery['base'][2], $balls[0]) && in_array($lottery['base'][3], $balls[0]) && in_array($lottery['base'][4], $balls[0])) {
                $ret['win_contents'][] = [$lottery['base'][2],$lottery['base'][3],$lottery['base'][4]];
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
     * @brief 后三_后三组选_混合组选
     *          手动输入购买号码，3个号码为一注，开奖号码的百位、十位、个位符合前三组三或组六均为中奖。
     *          投注方案：分别投注（668）（123）开奖号码后三位：686 668等，（顺序不限，需开出两个6）即中组选三；或者123 213等，（顺序不限，不可有号码重复）即中组选六。
     * @access public
     * @param array $bet
     * @param array $lottery
     * @return
     */
    public function settlement_h3_h3zx_hhzx(& $bet = [], & $lottery = [])
    {
        $balls = get_balls($bet[2]);
        $ret = null;
        $tempLottery = array(
            $lottery['base'][2],
            $lottery['base'][3],
            $lottery['base'][4],
        );
        foreach ($balls[0] as $ball) {
            if (preg_match("/$tempLottery[0]/", $ball) && preg_match("/$tempLottery[1]/", $ball) && preg_match("/$tempLottery[2]/", $ball)) {
                $ret['win_contents'][] = $ball;
            }
        }
    
        if (!empty($ret['win_contents'])) {
            $ret['win_counts'] = count($ret['win_contents']);
            // 计算赔率
            $rate = explode(',', $bet[6]);
            $rate = count(array_unique($tempLottery)) == 2 ? $rate[0] : $rate[1];
            $ret['price_sum'] = $bet[3] * $rate * $ret['win_counts'];
            $ret['status'] = STATUS_WIN;
        }
        return $ret;
    }
    
    /**
     * @brief 中三_中三直选_复式
     *          从千位、百位、十位中选择一个3位数号码组成一注，所选号码与开奖号码的中间3位相同，且顺序一致，即为中奖。
     *          投注方案：345； 开奖号码：23456，即中奖中三直选
     * @access public
     * @param array $bet
     * @param array $lottery
     * @return
     */
    public function settlement_z3_z3zhx_fs(& $bet = [], & $lottery = [])
    {
        $balls = get_balls($bet[2]);
        $ret = null;
        if (in_array($lottery['base'][1], $balls[0]) && in_array($lottery['base'][2], $balls[1]) && in_array($lottery['base'][3], $balls[2])) {
            $ret['win_contents'][] = [$lottery['base'][1], $lottery['base'][2], $lottery['base'][3]];
        }
    
        if (!empty($ret['win_contents'])) {
            $ret['win_counts'] = count($ret['win_contents']);
            $ret['price_sum'] = $bet[3] * $bet[6] * $ret['win_counts'];
            $ret['status'] = STATUS_WIN;
        }
        return $ret;
    }
    
    /**
     * @brief 中三_中三直选_单式
     *          手动输入一个3位数号码组成一注，所选号码的千位、百位、十位与开奖号码相同，且顺序一致，即为中奖。
     *          投注方案：345； 开奖号码：23456，即中奖中三直选
     * @access public
     * @param array $bet
     * @param array $lottery
     * @return
     */
    public function settlement_z3_z3zhx_ds(& $bet = [], & $lottery = [])
    {
        return $this->settlement_z3_z3zhx_fs($bet, $lottery);
    }
    
    /**
     * @brief 中三_中三直选_直选和值
     *          所选数值等于开奖号码的千位、百位、十位三个数字相加之和，即为中奖。
     *          投注方案：和值1；开奖号码中间三位：01001,00010,00100,即中中三直选。
     * @access public
     * @param array $bet
     * @param array $lottery
     * @return
     */
    public function settlement_z3_z3zhx_zxhz(& $bet = [], & $lottery = [])
    {
        $balls = get_balls($bet[2]);
        $ret = null;
        if (in_array($lottery['z3_he'], $balls[0])) {
            $ret['win_contents'][] = [$lottery['z3_he']];
        }
    
        if (!empty($ret['win_contents'])) {
            $ret['win_counts'] = count($ret['win_contents']);
            $ret['price_sum'] = $bet[3] * $bet[6] * $ret['win_counts'];
            $ret['status'] = STATUS_WIN;
        }
        return $ret;
    }
    
    /**
     * @brief 中三_中三组选_组选组3
     *          从0-9中选择2个数字组成两注，所选号码与开奖号码的千位、百位、十位相同，且顺序不限，即为中奖。
     *          投注方案：5,8,8；开奖号码中间三位：1个5，2个8 (顺序不限)，即中中三组选三。
     * @access public
     * @param array $bet
     * @param array $lottery
     * @return
     */
    public function settlement_z3_z3zx_zx3(& $bet = [], & $lottery = [])
    {
        $balls = get_balls($bet[2]);
        $ret = null;
        $tempLottery = array(
            $lottery['base'][1],
            $lottery['base'][2],
            $lottery['base'][3],
        );
        $tempLottery = array_values(array_unique($tempLottery));
        if (count($tempLottery) == 2) {
            if (in_array($tempLottery[0], $balls[0]) && in_array($tempLottery[1], $balls[0])) {
                $ret['win_contents'][] = $tempLottery;
                //$ret['win_contents'][] = array_reverse($tempLottery);
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
     * @brief 中三_中三组选_组选组6
     *          从0-9中任意选择3个号码组成一注，所选号码与开奖号码的千位、百位、十位相同，顺序不限，即为中奖。
     *          投注方案：2,5,8；开奖号码中间三位：1个2、1个5、1个8 (顺序不限)，即中中三组选六。
     * @access public
     * @param array $bet
     * @param array $lottery
     * @return
     */
    public function settlement_z3_z3zx_zx6(& $bet = [], & $lottery = [])
    {
        $balls = get_balls($bet[2]);
        $ret = null;
        if (count(array_unique(array($lottery['base'][1],$lottery['base'][2],$lottery['base'][3]))) == 3) {
            if (in_array($lottery['base'][1], $balls[0]) && in_array($lottery['base'][2], $balls[0]) && in_array($lottery['base'][3], $balls[0])) {
                $ret['win_contents'][] = [$lottery['base'][1],$lottery['base'][2],$lottery['base'][3]];
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
     * @brief 中三_中三组选_混合组选
     *          手动输入购买号码，3个号码为一注，开奖号码的百位、十位、个位符合前三组三或组六均为中奖。
     *          投注方案：分别投注（668）（123）开奖号码后三位：686 668等，（顺序不限，需开出两个6）即中组选三；或者123 213等，（顺序不限，不可有号码重复）即中组选六。
     * @access public
     * @param array $bet
     * @param array $lottery
     * @return
     */
    public function settlement_z3_z3zx_hhzx(& $bet = [], & $lottery = [])
    {
        $balls = get_balls($bet[2]);
        $ret = null;
        $tempLottery = array(
            $lottery['base'][1],
            $lottery['base'][2],
            $lottery['base'][3],
        );
        foreach ($balls[0] as $ball) {
            if (preg_match("/$tempLottery[0]/", $ball) && preg_match("/$tempLottery[1]/", $ball) && preg_match("/$tempLottery[2]/", $ball)) {
                $ret['win_contents'][] = $ball;
            }
        }
    
        if (!empty($ret['win_contents'])) {
            $ret['win_counts'] = count($ret['win_contents']);
            // 计算赔率
            $rate = explode(',', $bet[6]);
            $rate = count(array_unique($tempLottery)) == 2 ? $rate[0] : $rate[1];
            $ret['price_sum'] = $bet[3] * $rate * $ret['win_counts'];
            $ret['status'] = STATUS_WIN;
        }
        return $ret;
    }
    
    /**
     * @brief 前三_前三直选_复式
     *          从万位、千位、百位中选择一个3位数号码组成一注，所选号码与开奖号码的前3位相同，且顺序一致，即为中奖。
     *          投注方案：345； 开奖号码：345，即中前三直选。
     * @access public
     * @param array $bet
     * @param array $lottery
     * @return
     */
    public function settlement_q3_q3zhx_fs(& $bet = [], & $lottery = [])
    {
        $balls = get_balls($bet[2]);
        $ret = null;
        if (in_array($lottery['base'][0], $balls[0]) && in_array($lottery['base'][1], $balls[1]) && in_array($lottery['base'][2], $balls[2])) {
            $ret['win_contents'][] = [$lottery['base'][0], $lottery['base'][1], $lottery['base'][2]];
        }
    
        if (!empty($ret['win_contents'])) {
            $ret['win_counts'] = count($ret['win_contents']);
            $ret['price_sum'] = $bet[3] * $bet[6] * $ret['win_counts'];
            $ret['status'] = STATUS_WIN;
        }
        return $ret;
    }
    
    /**
     * @brief 前三_前三直选_单式
     *          手动输入一个3位数号码组成一注，所选号码的万位、千位、百位与开奖号码相同，且顺序一致，即为中奖。
     *          投注方案：345； 开奖号码：23456，即中奖中三直选
     * @access public
     * @param array $bet
     * @param array $lottery
     * @return
     */
    public function settlement_q3_q3zhx_ds(& $bet = [], & $lottery = [])
    {
        return $this->settlement_q3_q3zhx_fs($bet, $lottery);
    }
    
    /**
     * @brief 前三_前三直选_直选和值
     *          所选数值等于开奖号码的万位、千位、百位三个数字相加之和，即为中奖。
     *          投注方案：和值1；开奖号码前三位：001,010,100,即中前三直选和值。
     * @access public
     * @param array $bet
     * @param array $lottery
     * @return
     */
    public function settlement_q3_q3zhx_zxhz(& $bet = [], & $lottery = [])
    {
        $balls = get_balls($bet[2]);
        $ret = null;
        if (in_array($lottery['q3_he'], $balls[0])) {
            $ret['win_contents'][] = [$lottery['q3_he']];
        }
    
        if (!empty($ret['win_contents'])) {
            $ret['win_counts'] = count($ret['win_contents']);
            $ret['price_sum'] = $bet[3] * $bet[6] * $ret['win_counts'];
            $ret['status'] = STATUS_WIN;
        }
        return $ret;
    }
    
    /**
     * @brief 前三_前三组选_组选组3
     *          从0至9中任选2个不同号码组成两注，开奖号码的万位、千位、百位包含所选号码，且其中必须有一个号码重复，顺序不限，即为中奖
     *          投注方案：5,8,8；开奖号码前三位：1个5，2个8 (顺序不限)，即中前三组选三。
     * @access public
     * @param array $bet
     * @param array $lottery
     * @return
     */
    public function settlement_q3_q3zx_zx3(& $bet = [], & $lottery = [])
    {
        $balls = get_balls($bet[2]);
        $ret = null;
        $tempLottery = [
            $lottery['base'][0],
            $lottery['base'][1],
            $lottery['base'][2]
        ];
        $tempLottery = array_values(array_unique($tempLottery));
        if (count($tempLottery) == 2) {
            if (in_array($tempLottery[0], $balls[0]) && in_array($tempLottery[1], $balls[0])) {
                $ret['win_contents'][] = $tempLottery;
                //$ret['win_contents'][] = array_reverse($tempLottery);
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
     * @brief 前三_前三组选_组选组6
     *          从0至9中任选3个不同号码组成一注，开奖号码的万位、千位、百位包含所选号码，不可有号码重复，顺序不限，即为中奖。
     *          投注方案：2,5,8；开奖号码前三位：1个2、1个5、1个8 (顺序不限)，即中前三组选六。
     * @access public
     * @param array $bet
     * @param array $lottery
     * @return
     */
    public function settlement_q3_q3zx_zx6(& $bet = [], & $lottery = [])
    {
        $balls = get_balls($bet[2]);
        $ret = null;
        if (count(array_unique(array($lottery['base'][0],$lottery['base'][1],$lottery['base'][2]))) == 3) {
            if (in_array($lottery['base'][0], $balls[0]) && in_array($lottery['base'][1], $balls[0]) && in_array($lottery['base'][2], $balls[0])) {
                $ret['win_contents'][] = [$lottery['base'][0], $lottery['base'][1], $lottery['base'][2]];
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
     * @brief 前三_前三组选_混合组选
     *          手动输入购买号码，3个号码为一注，开奖号码的万位、千位、百位符合前三组三或组六均为中奖。
     *          投注方案：分别投注（668）（123）开奖号码前三位：686 668等，（顺序不限，需开出两个6）即中组选三；或者123 213等，（顺序不限，不可有号码重复）即中组选六。
     * @access public
     * @param array $bet
     * @param array $lottery
     * @return
     */
    public function settlement_q3_q3zx_hhzx(& $bet = [], & $lottery = [])
    {
        $balls = get_balls($bet[2]);
        $ret = null;
        $tempLottery = [
            $lottery['base'][0],
            $lottery['base'][1],
            $lottery['base'][2]
        ];
        foreach ($balls[0] as $ball) {
            if (preg_match("/$tempLottery[0]/", $ball) && preg_match("/$tempLottery[1]/", $ball) && preg_match("/$tempLottery[2]/", $ball)) {
                $ret['win_contents'][] = $ball;
            }
        }
    
        if (!empty($ret['win_contents'])) {
            $ret['win_counts'] = count($ret['win_contents']);
            // 计算赔率
            $rate = explode(',', $bet[6]);
            $rate = count(array_unique($tempLottery)) == 2 ? $rate[0] : $rate[1];
            $ret['price_sum'] = $bet[3] * $rate * $ret['win_counts'];
            $ret['status'] = STATUS_WIN;
        }
        return $ret;
    }
    
    /**
     * @brief 二星_后二直选_复式
     *          从十位、个位中选择一个2位数号码组成一注，所选号码与开奖号码的十位、个位相同，且顺序一致，即为中奖。
     *          投注方案：58；开奖号码后二位：58，即中后二直选一等奖。
     * @access public
     * @param array $bet
     * @param array $lottery
     * @return
     */
    public function settlement_2x_h2zhx_fs(& $bet = [], & $lottery = [])
    {
        $balls = get_balls($bet[2]);
        $ret = null;
        if (in_array($lottery['base'][3], $balls[0]) && in_array($lottery['base'][4], $balls[1])) {
            $ret['win_contents'][] = [$lottery['base'][3], $lottery['base'][4]];
        }
    
        if (!empty($ret['win_contents'])) {
            $ret['win_counts'] = count($ret['win_contents']);
            $ret['price_sum'] = $bet[3] * $bet[6] * $ret['win_counts'];
            $ret['status'] = STATUS_WIN;
        }
        return $ret;
    }
    
    /**
     * @brief 二星_后二直选_单式
     *          手动输入一个2位数号码组成一注，所选号码的十位、个位与开奖号码相同，且顺序一致，即为中奖。
     *          投注方案：58；开奖号码后二位：58，即中后二直选一等奖。
     * @access public
     * @param array $bet
     * @param array $lottery
     * @return
     */
    public function settlement_2x_h2zhx_ds(& $bet = [], & $lottery = [])
    {
        return $this->settlement_2x_h2zhx_fs($bet, $lottery);
    }
    
    /**
     * @brief 二星_后二直选_直选和值
     *          所选数值等于开奖号码的十位、个位二个数字相加之和，即为中奖。
     *          投注方案：和值1；开奖号码后二位：01,10，即中后二直选和值。
     * @access public
     * @param array $bet
     * @param array $lottery
     * @return
     */
    public function settlement_2x_h2zhx_zxhz(& $bet = [], & $lottery = [])
    {
        $balls = get_balls($bet[2]);
        $ret = null;
        if (in_array($lottery['h2_he'], $balls[0])) {
            $ret['win_contents'][] = [$lottery['h2_he']];
        }
    
        if (!empty($ret['win_contents'])) {
            $ret['win_counts'] = count($ret['win_contents']);
            $ret['price_sum'] = $bet[3] * $bet[6] * $ret['win_counts'];
            $ret['status'] = STATUS_WIN;
        }
        return $ret;
    }
    
    /**
     * @brief 二星_后二直选_大小单双
     *          对十位和个位的“大（56789）小（01234）、单（13579）双（02468）”形态进行购买，所选号码的位置、形态与开奖号码的位置、形态相同，即为中奖。
     *          投注方案：大单；开奖号码十位与个位：大单，即中后二大小单双。
     * @access public
     * @param array $bet
     * @param array $lottery
     * @return
     */
    public function settlement_2x_h2zhx_dxds(& $bet = [], & $lottery = [])
    {
        $balls = get_balls($bet[2]);
        $ret = null;
        foreach ($balls[0] as $v1) {
            if (in_array($lottery['base'][3], $this->config_balls['2x_dxds'][$v1])) {
                foreach ($balls[1] as $v2) {
                    if (in_array($lottery['base'][4], $this->config_balls['2x_dxds'][$v2])) {
                        $ret['win_contents'][] = [$v1, $v2];
                        //break;
                    }
                }
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
     * @brief 二星_后二组选_复式
     *          从0-9中选2个号码组成一注，所选号码与开奖号码的十位、个位相同，顺序不限，即中奖。
     *          投注方案：5,8；开奖号码后二位：1个5，1个8 (顺序不限)，即中后二组选。
     * @access public
     * @param array $bet
     * @param array $lottery
     * @return
     */
    public function settlement_2x_h2zx_fs(& $bet = [], & $lottery = [])
    {
        $balls = get_balls($bet[2]);
        $ret = null;
        if ($lottery['base'][3] != $lottery['base'][4]) {
            if (in_array($lottery['base'][3], $balls[0]) && in_array($lottery['base'][4], $balls[0])) {
                $ret['win_contents'][] = [$lottery['base'][3], $lottery['base'][4]];
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
     * @brief 二星_后二组选_单式
     *          手动输入一个2位数号码组成一注，所选号码的十位、个位与开奖号码相同，顺序不限，即为中奖。
     *          投注方案：5,8；开奖号码后二位：1个5，1个8 (顺序不限)，即中后二组选。
     * @access public
     * @param array $bet
     * @param array $lottery
     * @return
     */
    public function settlement_2x_h2zx_ds(& $bet = [], & $lottery = [])
    {
        return $this->settlement_2x_h2zx_fs($bet, $lottery);
    }
    
    /**
     * @brief 二星_前二直选_复式
     *          从万位、千位中选择一个2位数号码组成一注，所选号码与开奖号码的前2位相同，且顺序一致，即为中奖。
     *          投注方案：58；开奖号码前二位：58，即中前二直选。
     * @access public
     * @param array $bet
     * @param array $lottery
     * @return
     */
    public function settlement_2x_q2zhx_fs(& $bet = [], & $lottery = [])
    {
        $balls = get_balls($bet[2]);
        $ret = null;
        if (in_array($lottery['base'][0], $balls[0]) && in_array($lottery['base'][1], $balls[1])) {
            $ret['win_contents'][] = [$lottery['base'][0], $lottery['base'][1]];
        }
    
        if (!empty($ret['win_contents'])) {
            $ret['win_counts'] = count($ret['win_contents']);
            $ret['price_sum'] = $bet[3] * $bet[6] * $ret['win_counts'];
            $ret['status'] = STATUS_WIN;
        }
        return $ret;
    }
    
    /**
     * @brief 二星_前二直选_单式
     *          手动输入一个2位数号码组成一注，所选号码的万位、千位与开奖号码相同，且顺序一致，即为中奖。
     *          投注方案：58；开奖号码前二位：58，即中前二直选。
     * @access public
     * @param array $bet
     * @param array $lottery
     * @return
     */
    public function settlement_2x_q2zhx_ds(& $bet = [], & $lottery = [])
    {
        return $this->settlement_2x_q2zhx_fs($bet, $lottery);
    }
    
    /**
     * @brief 二星_前二直选_直选和值
     *          所选数值等于开奖号码的万位、千位二个数字相加之和，即为中奖。
     *          投注方案：和值1；开奖号码前二位：01,10，即中前二直选和值。
     * @access public
     * @param array $bet
     * @param array $lottery
     * @return
     */
    public function settlement_2x_q2zhx_zxhz(& $bet = [], & $lottery = [])
    {
        $balls = get_balls($bet[2]);
        $ret = null;
        if (in_array($lottery['q2_he'], $balls[0])) {
            $ret['win_contents'][] = [$lottery['q2_he']];
        }
    
        if (!empty($ret['win_contents'])) {
            $ret['win_counts'] = count($ret['win_contents']);
            $ret['price_sum'] = $bet[3] * $bet[6] * $ret['win_counts'];
            $ret['status'] = STATUS_WIN;
        }
        return $ret;
    }
    
    /**
     * @brief 二星_前二直选_大小单双
     *          对万位、千位的【大 56789】【小 01234】【单 13579】【双 02468】号码形态进行购买，所选号码（形态）与开奖号码（形态）相同，顺序一致，即为中奖。
     *          投注方案：小双；开奖号码万位2 千位8（万位小千位双），即中前二大小单双。
     * @access public
     * @param array $bet
     * @param array $lottery
     * @return
     */
    public function settlement_2x_q2zhx_dxds(& $bet = [], & $lottery = [])
    {
        $balls = get_balls($bet[2]);
        $ret = null;
        foreach ($balls[0] as $v1) {
            if (in_array($lottery['base'][0], $this->config_balls['2x_dxds'][$v1])) {
                foreach ($balls[1] as $v2) {
                    if (in_array($lottery['base'][1], $this->config_balls['2x_dxds'][$v2])) {
                        $ret['win_contents'][] = [$v1, $v2];
                        //break;
                    }
                }
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
     * @brief 二星_前二组选_复式
     *          从0-9中选2个号码组成一注，所选号码与开奖号码的万位、千位相同，顺序不限，即中奖。
     *          投注方案：5,8；开奖号码前二位：1个5，1个8 (顺序不限)，即中前二组选。
     * @access public
     * @param array $bet
     * @param array $lottery
     * @return
     */
    public function settlement_2x_q2zx_fs(& $bet = [], & $lottery = [])
    {
        $balls = get_balls($bet[2]);
        $ret = null;
        if ($lottery['base'][0] != $lottery['base'][1]) {
            if (in_array($lottery['base'][0], $balls[0]) && in_array($lottery['base'][1], $balls[0])) {
                $ret['win_contents'][] = [$lottery['base'][0], $lottery['base'][1]];
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
     * @brief 二星_前二组选_单式
     *          手动输入一个2位数号码组成一注，所选号码的万位、千位与开奖号码相同，顺序不限，即为中奖。
     *          投注方案：5,8；开奖号码前二位：1个5，1个8 (顺序不限)，即中前二组选。
     * @access public
     * @param array $bet
     * @param array $lottery
     * @return
     */
    public function settlement_2x_q2zx_ds(& $bet = [], & $lottery = [])
    {
        return $this->settlement_2x_q2zx_fs($bet, $lottery);
    }
    
    /**
     * @brief 定位胆_定位胆
     *          从万位、千位、百位、十位、个位任意位置上至少选择1个以上号码，所选号码与相同位置上的开奖号码一致，即为中奖。
     *          投注方案：万位1；开奖号码万位：1，即中定位胆万位。
     * @access public
     * @param array $bet
     * @param array $lottery
     * @return
     */
    public function settlement_dwd_dwd(& $bet = [], & $lottery = [])
    {
        $balls = get_balls($bet[2]);
        $ret = null;
        // 一个位只能中一球,最多中五球
        foreach ($lottery['base'] as $k => $v) {
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
     * @brief 不定位_三星一码_后三
     *          从0-9中选择1个号码，每注由1个号码组成，只要开奖号码的百位、十位、个位中包含所选号码，即为中奖。
     *          投注方案：1；开奖号码后三位：至少出现1个1，即中后三一码不定位。
     * @access public
     * @param array $bet
     * @param array $lottery
     * @return
     */
    public function settlement_bdw_3x1m_h3(& $bet = [], & $lottery = [])
    {
        $balls = get_balls($bet[2]);
        $ret = null;
        foreach ($balls[0] as $ball) {
            if ($ball == $lottery['base'][2] || $ball == $lottery['base'][3] || $ball == $lottery['base'][4]) {
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
     * @brief 不定位_三星一码_中三
     *          从0-9中选择1个号码，每注由1个号码组成，只要开奖号码千位、百位、十位中包含所选号码，即为中奖。
     *          投注方案：1；开奖号码中间三位：至少出现1个1，即中中三一码不定位。
     * @access public
     * @param array $bet
     * @param array $lottery
     * @return
     */
    public function settlement_bdw_3x1m_z3(& $bet = [], & $lottery = [])
    {
        $balls = get_balls($bet[2]);
        $ret = null;
        foreach ($balls[0] as $ball) {
            if ($ball == $lottery['base'][1] || $ball == $lottery['base'][2] || $ball == $lottery['base'][3]) {
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
     * @brief 不定位_三星一码_前三
     *          从0-9中选择1个号码，每注由1个号码组成，只要开奖号码的万位、千位、百位中包含所选号码，即为中奖。
     *          投注方案：1；开奖号码前三位：至少出现1个1，即中前三一码不定位。
     * @access public
     * @param array $bet
     * @param array $lottery
     * @return
     */
    public function settlement_bdw_3x1m_q3(& $bet = [], & $lottery = [])
    {
        $balls = get_balls($bet[2]);
        $ret = null;
        foreach ($balls[0] as $ball) {
            if ($ball == $lottery['base'][0] || $ball == $lottery['base'][1] || $ball == $lottery['base'][2]) {
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
     * @brief 不定位_三星二码_后三
     *          从0-9中选择2个号码，每注由2个不同的号码组成，开奖号码的百位、十位、个位中同时包含所选的2个号码，即为中奖。
     *          投注方案：1,2；开奖号码后三位：至少出现1和2各1个，即中后三二码不定位。
     * @access public
     * @param array $bet
     * @param array $lottery
     * @return
     */
    public function settlement_bdw_3x2m_h3(& $bet = [], & $lottery = [])
    {
        $balls = get_balls($bet[2]);
        $ret = null;
        if (count(array_unique(array($lottery['base'][2],$lottery['base'][3],$lottery['base'][4]))) != 1) {
            $r = combination($balls[0], 2);
            foreach ($r as $v) {
                if (in_array($v[0], [$lottery['base'][2],$lottery['base'][3],$lottery['base'][4]])
                    && in_array($v[1], [$lottery['base'][2],$lottery['base'][3],$lottery['base'][4]])) {
                    $ret['win_contents'][] = $v;
                }
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
     * @brief 不定位_三星二码_中三
     *          从0-9中选择2个号码，每注由2个不同的号码组成，开奖号码的千位、百位、十位中同时包含所选的2个号码，即为中奖。
     *          投注方案：1,2；开奖号码中间三位：至少出现1和2各1个，即中中三二码不定位。
     * @access public
     * @param array $bet
     * @param array $lottery
     * @return
     */
    public function settlement_bdw_3x2m_z3(& $bet = [], & $lottery = [])
    {
        $balls = get_balls($bet[2]);
        $ret = null;
        if (count(array_unique(array($lottery['base'][1],$lottery['base'][2],$lottery['base'][3]))) != 1) {
            $r = combination($balls[0], 2);
            foreach ($r as $v) {
                if (in_array($v[0], [$lottery['base'][1],$lottery['base'][2],$lottery['base'][3]])
                    && in_array($v[1], [$lottery['base'][1],$lottery['base'][2],$lottery['base'][3]])) {
                    $ret['win_contents'][] = $v;
                }
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
     * @brief 不定位_三星二码_前三
     *          从0-9中选择2个号码，每注由2个不同的号码组成，开奖号码的万位、千位、百位中同时包含所选的2个号码，即为中奖。
     *          投注方案：1,2；开奖号码前三位：至少出现1和2各1个，即中前三二码不定位。
     * @access public
     * @param array $bet
     * @param array $lottery
     * @return
     */
    public function settlement_bdw_3x2m_q3(& $bet = [], & $lottery = [])
    {
        $balls = get_balls($bet[2]);
        $ret = null;
        if (count(array_unique(array($lottery['base'][0],$lottery['base'][1],$lottery['base'][2]))) != 1) {
            $r = combination($balls[0], 2);
            foreach ($r as $v) {
                if (in_array($v[0], [$lottery['base'][0],$lottery['base'][1],$lottery['base'][2]])
                    && in_array($v[1], [$lottery['base'][0],$lottery['base'][1],$lottery['base'][2]])) {
                    $ret['win_contents'][] = $v;
                }
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
     * @brief 任选_任选2_复式
     *          从万，千，百，十，个位中至少选择两个位置，至少各选一个号码组成一注，所选号码与开奖号码的指定位置上的号码相同，且顺序一致，即为中奖。
     *          投注方案：万位1，千位2 开奖号码：12345，即为中奖。
     * @access public
     * @param array $bet
     * @param array $lottery
     * @return
     */
    public function settlement_rx_rx2_fs(& $bet = [], & $lottery = [])
    {
        $balls = get_balls($bet[2]);
        $ret = null;
        // 去掉没中奖的万，千，百，十，个位的号码
        $lotteryTemp = $lottery['base'];
        if (!in_array($lotteryTemp[0], $balls[0])) {
            unset($lotteryTemp[0]);
        }
        if (!in_array($lotteryTemp[1], $balls[1])) {
            unset($lotteryTemp[1]);
        }
        if (!in_array($lotteryTemp[2], $balls[2])) {
            unset($lotteryTemp[2]);
        }
        if (!in_array($lotteryTemp[3], $balls[3])) {
            unset($lotteryTemp[3]);
        }
        if (!in_array($lotteryTemp[4], $balls[4])) {
            unset($lotteryTemp[4]);
        }
        // 只有两个以上匹配才有得中奖
        if (count($lotteryTemp) >= 2) {
            $r = combination(array_values($lotteryTemp), 2);
            foreach ($r as $v) {
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
     * @brief 任选_任选2_单式
     *          从万、千、百、十、个位中至少选择两个位置，至少手动输入一个两位数的号码构成一注，所选号码与开奖号码的指定位置上的号码相同，且顺序一致，即为中奖。
     *          投注方案：万位1，千位2 开奖号码：12345，即为中奖。
     *          $bets = {
     *              gid:15,tid:1908,price:2,counts:3,price_sum:6,
     *              rate:"第一个赔率,第二个赔率...",
     *              rebate:反水比率,
     *              pids:"第一球products_id1,第一球products_id2|第二球products_id1|第三球不选为空|第四球products_id1...",
     *              contents:"第一球code1,第一球code2|第二球code1|第三球不选为空|第四球code1...",
     *              names:"01,05|08||03..."
     *          }
     * @access public
     * @param array $bet
     * @param array $lottery
     * @return
     */
    public function settlement_rx_rx2_ds(& $bet = [], & $lottery = [])
    {
        return $this->settlement_rx_rx2_fs($bet, $lottery);
    }
    
    /**
     * @brief 任选_任选2_组选
     *          从万、千、百、十、个位中至少选择两个位置，至少选个两个号码组成一注，所选号码与开奖号码指定位置上的号码相同，且顺序不限，即为中奖。
     *          投注方案：位置选择万、千位，选择号码56 开奖号码：56823或者65789，即为中奖。
     * @access public
     * @param array $bet
     * @param array $lottery
     * @return
     */
    public function settlement_rx_rx2_zx(& $bet = [], & $lottery = [])
    {
        $balls = get_balls($bet[2]);
        $ret = null;
        // 去掉没选中的号码
        $lotteryTemp = $lottery['base'];
        if (!in_array($this->config_balls['ww'], $balls[0])) {
            unset($lotteryTemp[0]);
        }
        if (!in_array($this->config_balls['qw'], $balls[0])) {
            unset($lotteryTemp[1]);
        }
        if (!in_array($this->config_balls['bw'], $balls[0])) {
            unset($lotteryTemp[2]);
        }
        if (!in_array($this->config_balls['sw'], $balls[0])) {
            unset($lotteryTemp[3]);
        }
        if (!in_array($this->config_balls['gw'], $balls[0])) {
            unset($lotteryTemp[4]);
        }
        if (count(array_unique($lotteryTemp)) >= 2) {
            $r = combination(array_values($lotteryTemp), 2);
            foreach ($r as $v1) {
                if ($v1[0] != $v1[1] && in_array($v1[0], $balls[1]) && in_array($v1[1], $balls[1])) {
                    $ret['win_contents'][] = $v1;
                }
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
     * @brief 任选_任选3_复式
     *          从万、千、百、十、个中至少3个位置各选一个或多个号码，将各个位置的号码进行组合，所选位置号码与开奖位置号码相同则中奖。
     *          万位买0，千位买1，百位买2，十位买3，开奖01234，则中奖。
     * @access public
     * @param array $bet
     * @param array $lottery
     * @return
     */
    public function settlement_rx_rx3_fs(& $bet = [], & $lottery = [])
    {
        $balls = get_balls($bet[2]);
        $ret = null;
        // 去掉没中奖的万，千，百，十，个位的号码
        $lotteryTemp = $lottery['base'];
        if (!in_array($lotteryTemp[0], $balls[0])) {
            unset($lotteryTemp[0]);
        }
        if (!in_array($lotteryTemp[1], $balls[1])) {
            unset($lotteryTemp[1]);
        }
        if (!in_array($lotteryTemp[2], $balls[2])) {
            unset($lotteryTemp[2]);
        }
        if (!in_array($lotteryTemp[3], $balls[3])) {
            unset($lotteryTemp[3]);
        }
        if (!in_array($lotteryTemp[4], $balls[4])) {
            unset($lotteryTemp[4]);
        }
        // 只有三个以上匹配才有得中奖
        if (count($lotteryTemp) >= 3) {
            $r = combination(array_values($lotteryTemp), 3);
            foreach ($r as $v) {
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
     * @brief 任选_任选3_单式
     *          手动输入一注或者多注的三个号码和至少三个位置，如果选中的号码与位置和开奖号码对应则中奖。
     *          万位买0，千位买1，百位买2，十位买3，开奖01234，则中奖。
     * @access public
     * @param array $bet
     * @param array $lottery
     * @return
     */
    public function settlement_rx_rx3_ds(& $bet = [], & $lottery = [])
    {
        return $this->settlement_rx_rx3_fs($bet, $lottery);
    }
    
    /**
     * @brief 任选_任选3_组3
     *          从0-9中任意选择2个或2个以上号码和万、千、百、十、个任意的三个位置，如果组合的号码与开奖号码对应则中奖
     *          位置选择万、千、百，号码选择01；开奖号码为110**、则中奖
     * @access public
     * @param array $bet
     * @param array $lottery
     * @return
     */
    public function settlement_rx_rx3_z3(& $bet = [], & $lottery = [])
    {
        $balls = get_balls($bet[2]);
        $ret = null;
        // 去掉没选中的号码
        $lotteryTemp = $lottery['base'];
        if (!in_array($this->config_balls['ww'], $balls[0])) {
            unset($lotteryTemp[0]);
        }
        if (!in_array($this->config_balls['qw'], $balls[0])) {
            unset($lotteryTemp[1]);
        }
        if (!in_array($this->config_balls['bw'], $balls[0])) {
            unset($lotteryTemp[2]);
        }
        if (!in_array($this->config_balls['sw'], $balls[0])) {
            unset($lotteryTemp[3]);
        }
        if (!in_array($this->config_balls['gw'], $balls[0])) {
            unset($lotteryTemp[4]);
        }
        if (count(array_unique($lotteryTemp)) >= 2) {
            $r = combination(array_values($lotteryTemp), 3);
            foreach ($r as $v1) {
                // 只有有一个相同的时候能中奖
                $v1 = array_values(array_unique($v1));
                if (count($v1) == 2 && in_array($v1[0], $balls[1]) && in_array($v1[1], $balls[1])) {
                    // 可以中一注
                    $ret['win_contents'][] = $v1;
                    //$ret['win_contents'][] = array_reverse($v1);
                }
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
     * @brief 任选_任选3_组6
     *          从0-9中任意选择3个或3个以上号码和万、千、百、十、个任意的三个位置，如果组合的号码与开奖号码对应则中奖
     *          位置选择万、千、百，号码选择012；开奖号码为012**、则中奖
     * @access public
     * @param array $bet
     * @param array $lottery
     * @return
     */
    public function settlement_rx_rx3_z6(& $bet = [], & $lottery = [])
    {
        $balls = get_balls($bet[2]);
        $ret = null;
        // 去掉没选中的号码
        $lotteryTemp = $lottery['base'];
        if (!in_array($this->config_balls['ww'], $balls[0])) {
            unset($lotteryTemp[0]);
        }
        if (!in_array($this->config_balls['qw'], $balls[0])) {
            unset($lotteryTemp[1]);
        }
        if (!in_array($this->config_balls['bw'], $balls[0])) {
            unset($lotteryTemp[2]);
        }
        if (!in_array($this->config_balls['sw'], $balls[0])) {
            unset($lotteryTemp[3]);
        }
        if (!in_array($this->config_balls['gw'], $balls[0])) {
            unset($lotteryTemp[4]);
        }
        if (count(array_unique($lotteryTemp)) >= 3) {
            $r = combination(array_values($lotteryTemp), 3);
            foreach ($r as $v1) {
                $v1 = array_unique($v1);
                if (count($v1) == 3 && in_array($v1[0], $balls[1]) && in_array($v1[1], $balls[1]) && in_array($v1[2], $balls[1])) {
                    $ret['win_contents'][] = $v1;
                }
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
     * @brief 任选_任选3_混合组选
     *          手动输入购买号码，至少选三个位置输入3个号码为一注，所选位置号码符合开奖号码的组三或组六均为中奖。
     *          投注方案：位置选择百、十、个位，所选号码345，开奖号码：**345，即为中奖。
     * @access public
     * @param array $bet
     * @param array $lottery
     * @return
     */
    public function settlement_rx_rx3_hhzx(& $bet = [], & $lottery = [])
    {
        $balls = get_balls($bet[2]);
        $ret = null;
        // 去掉没选中的号码
        $lotteryTemp = $lottery['base'];
        if (!in_array($this->config_balls['ww'], $balls[0])) {
            unset($lotteryTemp[0]);
        }
        if (!in_array($this->config_balls['qw'], $balls[0])) {
            unset($lotteryTemp[1]);
        }
        if (!in_array($this->config_balls['bw'], $balls[0])) {
            unset($lotteryTemp[2]);
        }
        if (!in_array($this->config_balls['sw'], $balls[0])) {
            unset($lotteryTemp[3]);
        }
        if (!in_array($this->config_balls['gw'], $balls[0])) {
            unset($lotteryTemp[4]);
        }
        if (count(array_unique($lotteryTemp)) >= 2) {
            $r = combination(array_values($lotteryTemp), 3);
            $rate = explode(',', $bet[6]);
            foreach ($r as $v1) {
                $v1 = array_values(array_unique($v1));
                if (count($v1) == 2) {
                    foreach ($balls[1] as $v2) {
                        $m1 = preg_match_all("/$v1[0]/", $v2);
                        $m2 = preg_match_all("/$v1[1]/", $v2);
                        $m3 = $m1 + $m2;
                        if (!empty($m1) && !empty($m2) && $m3 == 3) {
                            // 中组三
                            $ret['win_contents'][] = $v1;
                            $ret['price_sum'] = isset($ret['price_sum']) ? $ret['price_sum'] + $bet[3] * $rate[0] : $bet[3] * $rate[0];
                        }
                    }
                } elseif (count($v1) == 3) {
                    foreach ($balls[1] as $v2) {
                        if (preg_match("/$v1[0]/", $v2) && preg_match("/$v1[1]/", $v2) && preg_match("/$v1[2]/", $v2)) {
                            // 中组六
                            $ret['win_contents'][] = $v1;
                            $ret['price_sum'] = isset($ret['price_sum']) ? $ret['price_sum'] + $bet[3] * $rate[1] : $bet[3] * $rate[1];
                        }
                    }
                }
            }
        }
    
        if (!empty($ret['win_contents'])) {
            $ret['win_counts'] = count($ret['win_contents']);
            $ret['status'] = STATUS_WIN;
        }
    
        return $ret;
    }
    
    /**
     * @brief 任选_任选4_复式
     *          从万、千、百、十、个中至少4个位置各选一个或多个号码，将各个位置的号码进行组合，所选位置号码与开奖位置号码相同则中奖。
     *          万位买0，千位买1，百位买2，十位买3，个位买4，开奖号码01234，则中奖。
     * @access public
     * @param array $bet
     * @param array $lottery
     * @return
     */
    public function settlement_rx_rx4_fs(& $bet = [], & $lottery = [])
    {
        $balls = get_balls($bet[2]);
        $ret = null;
    
        // 去掉没中奖的万，千，百，十，个位的号码
        $lotteryTemp = $lottery['base'];
        if (!in_array($lotteryTemp[0], $balls[0])) {
            unset($lotteryTemp[0]);
        }
        if (!in_array($lotteryTemp[1], $balls[1])) {
            unset($lotteryTemp[1]);
        }
        if (!in_array($lotteryTemp[2], $balls[2])) {
            unset($lotteryTemp[2]);
        }
        if (!in_array($lotteryTemp[3], $balls[3])) {
            unset($lotteryTemp[3]);
        }
        if (!in_array($lotteryTemp[4], $balls[4])) {
            unset($lotteryTemp[4]);
        }
        if (count($lotteryTemp) >= 4) {
            $r = combination(array_values($lotteryTemp), 4);
            foreach ($r as $v) {
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
     * @brief 任选_任选4_单式
     *          手动输入一注或者多注的四个号码和至少四个位置，如果选中的号码与位置和开奖号码对应则中奖
     *          输入号码0123选择万、千、百、十位置，如开奖号码位0123*； 则中奖
     * @access public
     * @param array $bet
     * @param array $lottery
     * @return
     */
    public function settlement_rx_rx4_ds(& $bet = [], & $lottery = [])
    {
        return $this->settlement_rx_rx4_fs($bet, $lottery);
    }
    
    /**
     * @brief 跨度_跨度_前三跨度
     *          玩法：从0-9任选一个号码组成一注，所选号码与开奖号前三位的最大最小数字的差值相等，即中奖。
     *          投注方案：选择5,
     *          等于开奖号前三位2,5,7的最大数7与最小数字2的差值，即为中奖。
     * @access public
     */
    public function settlement_kd_kdq3(& $bet = [], & $lottery = []) /* {{{ */
    {
        $balls = get_balls($bet[2]);
        $ret = null;
        $sum = arrayMaxValue(array($lottery['base'][0],$lottery['base'][1],$lottery['base'][2]))
            - arrayMinValue(array($lottery['base'][0],$lottery['base'][1],$lottery['base'][2]));
    
        if (in_array($sum, $balls[0])) {
            $ret['win_contents'][] = [$sum];
        }
    
        if (!empty($ret['win_contents'])) {
            $ret['win_counts'] = count($ret['win_contents']);
            $ret['price_sum'] = $bet[3] * $bet[6] * $ret['win_counts'];
            $ret['status'] = STATUS_WIN;
        }
        return $ret;
    } /* }}} */
    
    /**
     * @brief 跨度_跨度_中三跨度
     *          玩法：从0-9任选一个号码组成一注，所选号码与开奖号中三位的最大最小数字的差值相等，即中奖。
     *          投注方案：选择5,
     *          等于开奖号中三位2,5,7的最大数7与最小数字2的差值，即为中奖。
     * @access public
     */
    public function settlement_kd_kdz3(& $bet = [], & $lottery = []) /* {{{ */
    {
        $balls = get_balls($bet[2]);
        $ret = null;
        $sum = arrayMaxValue(array($lottery['base'][1],$lottery['base'][2],$lottery['base'][3]))
            - arrayMinValue(array($lottery['base'][1],$lottery['base'][2],$lottery['base'][3]));
    
        if (in_array($sum, $balls[0])) {
            $ret['win_contents'][] = [$sum];
        }
    
        if (!empty($ret['win_contents'])) {
            $ret['win_counts'] = count($ret['win_contents']);
            $ret['price_sum'] = $bet[3] * $bet[6] * $ret['win_counts'];
            $ret['status'] = STATUS_WIN;
        }
    
        return $ret;
    } /* }}} */
    
    /**
     * @brief 跨度_跨度_后三跨度
     *          玩法：从0-9任选一个号码组成一注，所选号码与开奖号后三位的最大最小数字的差值相等，即中奖。
     *          投注方案：选择5,
     *          等于开奖号后三位2,5,7的最大数7与最小数字2的差值，即为中奖。
     * @access public
     * @param
     * @return
     */
    public function settlement_kd_kdh3(& $bet = [], & $lottery = []) /* {{{ */
    {
        $balls = get_balls($bet[2]);
        $ret = null;
        $sum = arrayMaxValue(array($lottery['base'][2],$lottery['base'][3],$lottery['base'][4]))
            - arrayMinValue(array($lottery['base'][2],$lottery['base'][3],$lottery['base'][4]));
    
        if (in_array($sum, $balls[0])) {
            $ret['win_contents'][] = [$sum];
        }
    
        if (!empty($ret['win_contents'])) {
            $ret['win_counts'] = count($ret['win_contents']);
            $ret['price_sum'] = $bet[3] * $bet[6] * $ret['win_counts'];
            $ret['status'] = STATUS_WIN;
        }
    
        return $ret;
    } /* }}} */
    
    /**
     * @brief 跨度_跨度_前二跨度
     *          玩法：从0-9任选一个号码组成一注，所选号码与开奖号前二位的最大最小数字的差值相等，即中奖。
     *          投注方案：选择5,
     *          等于开奖号前二位2,7的最大数7与最小数字2的差值，即为中奖。
     * @access public
     */
    public function settlement_kd_kdq2(& $bet = [], & $lottery = []) /* {{{ */
    {
        $balls = get_balls($bet[2]);
        $ret = null;
        /*$sum = arrayMaxValue(array($lottery['base'][0],$lottery['base'][1]))
            - arrayMinValue(array($lottery['base'][0],$lottery['base'][1]));
    
        if (in_array($sum, $balls[0])) {
            $ret['win_contents'][] = [$sum];
        }*/
        if (in_array(abs($lottery['base'][0] - $lottery['base'][1]), $balls[0])) {
            $ret['win_contents'][] = [abs($lottery['base'][0] - $lottery['base'][1])];
        }
    
        if (!empty($ret['win_contents'])) {
            $ret['win_counts'] = count($ret['win_contents']);
            $ret['price_sum'] = $bet[3] * $bet[6] * $ret['win_counts'];
            $ret['status'] = STATUS_WIN;
        }
        return $ret;
    
    } /* }}} */
    
    /**
     * @brief 跨度_跨度_后二跨度
     *          玩法：从0-9任选一个号码组成一注，所选号码与开奖号后二位的最大最小数字的差值相等，即中奖。
     *          投注方案：选择5,
     *          等于开奖号后二位2,7的最大数7与最小数字2的差值，即为中奖。
     * @access public
     */
    public function settlement_kd_kdh2(& $bet = [], & $lottery = []) /* {{{ */
    {
        $balls = get_balls($bet[2]);
        $ret = null;
        /*$sum = arrayMaxValue(array($lottery['base'][3],$lottery['base'][4]))
            - arrayMinValue(array($lottery['base'][3],$lottery['base'][4]));
    
        if (in_array($sum, $balls[0])) {
            $ret['win_contents'][] = [$sum];
        }*/
        if (in_array(abs($lottery['base'][3] - $lottery['base'][4]), $balls[0])) {
            $ret['win_contents'][] = [abs($lottery['base'][3] - $lottery['base'][4])];
        }
    
        if (!empty($ret['win_contents'])) {
            $ret['win_counts'] = count($ret['win_contents']);
            $ret['price_sum'] = $bet[3] * $bet[6] * $ret['win_counts'];
            $ret['status'] = STATUS_WIN;
        }
        return $ret;
    } /* }}} */
    
    /**
     * @brief 趣味_特殊_一帆风顺
     *          从0-9中任意选择1个号码组成一注，只要开奖号码的万位、千位、百位、十位、个位中包含所选号码，即为中奖。
     *          投注方案：8；开奖号码：至少出现1个8，如：0 0 4 3 8，即中一帆风顺。
     * @access public
     */
    public function settlement_qw_ts_qw1(& $bet = [], & $lottery = [])
    {
        $balls = get_balls($bet[2]);
        $ret = null;
        // 开奖的球没有相同时才可能中奖
        foreach ($balls[0] as $item) {
            if (in_array($item, $lottery['base'])) {
                $ret['win_contents'][] = [$item];
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
     * @brief 趣味_特殊_好事成双
     *          从0-9中任意选择1个号码组成一注，只要所选号码在开奖号码的万位、千位、百位、十位、个位中出现2次，即为中奖。
     *          投注方案：8；开奖号码：至少出现2个8，如：0 0 4 8 8，即中好事成双。
     * @access public
     */
    public function settlement_qw_ts_qw2(& $bet = [], & $lottery = [])
    {
        $balls = get_balls($bet[2]);
        $ret = null;
        $tempLottery = array_count_values($lottery['base']);
        foreach ($balls[0] as $v) {
            if (isset($tempLottery[$v]) && $tempLottery[$v] >= 2) {
                $ret['win_contents'][] = [$v];
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
     * @brief 趣味_特殊_三星报喜
     *          从0-9中任意选择1个号码组成一注，只要所选号码在开奖号码的万位、千位、百位、十位、个位中出现3次，即为中奖。
     *          投注方案：8；开奖号码：至少出现3个8，如：0 8 4 8 8，即中三星报喜。
     * @access public
     */
    public function settlement_qw_ts_qw3(& $bet = [], & $lottery = [])
    {
        $balls = get_balls($bet[2]);
        $ret = null;
        $tempLottery = array_count_values($lottery['base']);
        foreach ($balls[0] as $v) {
            if (isset($tempLottery[$v]) && $tempLottery[$v] >= 3) {
                $ret['win_contents'][] = [$v];
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
     * @brief 趣味_特殊_四季发财
     *          从0-9中任意选择1个号码组成一注，只要所选号码在开奖号码的万位、千位、百位、十位、个位中出现4次，即为中奖。
     *          投注方案：8；开奖号码：至少出现4个8，如：0 8 8 8 8，即中四季发财。
     * @access public
     */
    public function settlement_qw_ts_qw4(& $bet = [], & $lottery = [])
    {
        $balls = get_balls($bet[2]);
        $ret = null;
        $tempLottery = array_count_values($lottery['base']);
        foreach ($balls[0] as $v) {
            if (isset($tempLottery[$v]) && $tempLottery[$v] >= 4) {
                $ret['win_contents'][] = [$v];
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
     * @brief 龙虎_龙虎_万千
     *          根据万位、千位号码数值比大小，万位号码大于千位号码为龙，万位号码小于千位号码为虎，号码相同则为和。所选形态与开奖号码形态一致，即为中奖。
     *          投注方案：龙；开奖号码 8 6 2 4 0,即中奖。
     *          投注方案：虎；开奖号码 6 8 2 4 0,即中奖。
     *          投注方案：和；开奖号码 6 6 2 4 0,即中奖。
     * @access public
     */
    public function settlement_lh_wq(& $bet = [], & $lottery = []) /* {{{ */
    {
        $balls = get_balls($bet[2]);
        $ret = null;
        /*$transfer = null;
    
        if ($lottery['base'][0] > $lottery['base'][1]) {
            $transfer = $this->config_balls['lh'][0];
        } else if ($lottery['base'][0] < $lottery['base'][1]) {
            $transfer = $this->config_balls['lh'][1];
        } else if ($lottery['base'][0] == $lottery['base'][1]) {
            $transfer = $this->config_balls['lh'][2];
        }
    
        if (in_array($transfer, $balls[0])) {
            $ret['win_contents'][] = [$transfer];
        }*/
        if ($lottery['base'][0] > $lottery['base'][1] && in_array($this->config_balls['lh'][0], $balls[0])) {
            // 龙133
            $ret['win_contents'][] = [$this->config_balls['lh'][0]];
        } elseif ($lottery['base'][0] < $lottery['base'][1] && in_array($this->config_balls['lh'][1], $balls[0])) {
            // 虎131
            $ret['win_contents'][] = [$this->config_balls['lh'][1]];
        } elseif ($lottery['base'][0] == $lottery['base'][1] && in_array($this->config_balls['lh'][2], $balls[0])) {
            // 和200
            $ret['win_contents'][] = [$this->config_balls['lh'][2]];
        }
    
        if (!empty($ret['win_contents'])) {
            $ret['win_counts'] = count($ret['win_contents']);
            $ret['price_sum'] = $bet[3] * $bet[6] * $ret['win_counts'];
            $ret['status'] = STATUS_WIN;
        }
        return $ret;
    } /* }}} */
    
    /**
     * @brief 龙虎_龙虎_万百
     *          根据万位、百位号码数值比大小，万位号码大于百位号码为龙，万位号码小于百位号码为虎，号码相同则为和。所选形态与开奖号码形态一致，即为中奖。
     *          投注方案：龙；开奖号码 8 2 6 4 0,即中奖。
     *          投注方案：虎；开奖号码 6 2 8 4 0,即中奖。
     *          投注方案：和；开奖号码 6 2 6 4 0,即中奖。
     * @access public
     */
    public function settlement_lh_wb(& $bet = [], & $lottery = []) /* {{{ */
    {
        $balls = get_balls($bet[2]);
        $ret = null;
    
        if ($lottery['base'][0] > $lottery['base'][2] && in_array($this->config_balls['lh'][0], $balls[0])) {
            // 龙133
            $ret['win_contents'][] = [$this->config_balls['lh'][0]];
        } elseif ($lottery['base'][0] < $lottery['base'][2] && in_array($this->config_balls['lh'][1], $balls[0])) {
            // 虎131
            $ret['win_contents'][] = [$this->config_balls['lh'][1]];
        } elseif ($lottery['base'][0] == $lottery['base'][2] && in_array($this->config_balls['lh'][2], $balls[0])) {
            // 和200
            $ret['win_contents'][] = [$this->config_balls['lh'][2]];
        }
    
        if (!empty($ret['win_contents'])) {
            $ret['win_counts'] = count($ret['win_contents']);
            $ret['price_sum'] = $bet[3] * $bet[6] * $ret['win_counts'];
            $ret['status'] = STATUS_WIN;
        }
        return $ret;
    } /* }}} */
    
    /**
     * @brief 龙虎_龙虎_万十
     *          根据万位、十位号码数值比大小，万位号码大于十位号码为龙，万位号码小于十位号码为虎，号码相同则为和。所选形态与开奖号码形态一致，即为中奖。
     *          投注方案：龙；开奖号码 8 2 4 6 0,即中奖。
     *          投注方案：虎；开奖号码 6 2 4 8 0,即中奖。
     *          投注方案：和；开奖号码 6 2 4 6 0,即中奖。
     * @access public
     */
    public function settlement_lh_ws(& $bet = [], & $lottery = []) /* {{{ */
    {
        $balls = get_balls($bet[2]);
        $ret = null;
    
        if ($lottery['base'][0] > $lottery['base'][3] && in_array($this->config_balls['lh'][0], $balls[0])) {
            // 龙133
            $ret['win_contents'][] = [$this->config_balls['lh'][0]];
        } elseif ($lottery['base'][0] < $lottery['base'][3] && in_array($this->config_balls['lh'][1], $balls[0])) {
            // 虎131
            $ret['win_contents'][] = [$this->config_balls['lh'][1]];
        } elseif ($lottery['base'][0] == $lottery['base'][3] && in_array($this->config_balls['lh'][2], $balls[0])) {
            // 和200
            $ret['win_contents'][] = [$this->config_balls['lh'][2]];
        }
    
        if (!empty($ret['win_contents'])) {
            $ret['win_counts'] = count($ret['win_contents']);
            $ret['price_sum'] = $bet[3] * $bet[6] * $ret['win_counts'];
            $ret['status'] = STATUS_WIN;
        }
        return $ret;
    } /* }}} */
    
    /**
     * @brief 龙虎_龙虎_万个
     *          根据万位、个位号码数值比大小，万位号码大于个位号码为龙，万位号码小于个位号码为虎，号码相同则为和。所选形态与开奖号码形态一致，即为中奖。
     *          投注方案：龙；开奖号码 8 2 4 0 6,即中奖。
     *          投注方案：虎；开奖号码 6 2 4 0 8,即中奖。
     *          投注方案：和；开奖号码 6 2 4 0 6,即中奖。
     * @access public
     */
    public function settlement_lh_wg(& $bet = [], & $lottery = []) /* {{{ */
    {
        $balls = get_balls($bet[2]);
        $ret = null;
    
        if ($lottery['base'][0] > $lottery['base'][4] && in_array($this->config_balls['lh'][0], $balls[0])) {
            // 龙133
            $ret['win_contents'][] = [$this->config_balls['lh'][0]];
        } elseif ($lottery['base'][0] < $lottery['base'][4] && in_array($this->config_balls['lh'][1], $balls[0])) {
            // 虎131
            $ret['win_contents'][] = [$this->config_balls['lh'][1]];
        } elseif ($lottery['base'][0] == $lottery['base'][4] && in_array($this->config_balls['lh'][2], $balls[0])) {
            // 和200
            $ret['win_contents'][] = [$this->config_balls['lh'][2]];
        }
    
        if (!empty($ret['win_contents'])) {
            $ret['win_counts'] = count($ret['win_contents']);
            $ret['price_sum'] = $bet[3] * $bet[6] * $ret['win_counts'];
            $ret['status'] = STATUS_WIN;
        }
        return $ret;
    } /* }}} */
    
    /**
     * @brief 龙虎_龙虎_千百
     *          根据千位、百位号码数值比大小，千位号码大于百位号码为龙，千位号码小于百位号码为虎，号码相同则为和。所选形态与开奖号码形态一致，即为中奖。
     *          投注方案：龙；开奖号码 2 8 6 4 0,即中奖。
     *          投注方案：虎；开奖号码 2 6 8 4 0,即中奖。
     *          投注方案：和；开奖号码 2 6 6 4 0,即中奖。
     * @access public
     */
    public function settlement_lh_qb(& $bet = [], & $lottery = []) /* {{{ */
    {
        $balls = get_balls($bet[2]);
        $ret = null;
    
        if ($lottery['base'][1] > $lottery['base'][2] && in_array($this->config_balls['lh'][0], $balls[0])) {
            // 龙133
            $ret['win_contents'][] = [$this->config_balls['lh'][0]];
        } elseif ($lottery['base'][1] < $lottery['base'][2] && in_array($this->config_balls['lh'][1], $balls[0])) {
            // 虎131
            $ret['win_contents'][] = [$this->config_balls['lh'][1]];
        } elseif ($lottery['base'][1] == $lottery['base'][2] && in_array($this->config_balls['lh'][2], $balls[0])) {
            // 和200
            $ret['win_contents'][] = [$this->config_balls['lh'][2]];
        }
    
        if (!empty($ret['win_contents'])) {
            $ret['win_counts'] = count($ret['win_contents']);
            $ret['price_sum'] = $bet[3] * $bet[6] * $ret['win_counts'];
            $ret['status'] = STATUS_WIN;
        }
        return $ret;
    } /* }}} */
    
    /**
     * @brief 龙虎_龙虎_千十
     *          根据千位、十位号码数值比大小，千位号码大于十位号码为龙，千位号码小于十位号码为虎，号码相同则为和。所选形态与开奖号码形态一致，即为中奖
     *          投注方案：龙；开奖号码 2 8 4 6 0,即中奖。
     *          投注方案：虎；开奖号码 2 6 4 8 0,即中奖。
     *          投注方案：和；开奖号码 2 6 4 6 0,即中奖。
     * @access public
     */
    public function settlement_lh_qs(& $bet = [], & $lottery = []) /* {{{ */
    {
        $balls = get_balls($bet[2]);
        $ret = null;
    
        if ($lottery['base'][1] > $lottery['base'][3] && in_array($this->config_balls['lh'][0], $balls[0])) {
            // 龙133
            $ret['win_contents'][] = [$this->config_balls['lh'][0]];
        } elseif ($lottery['base'][1] < $lottery['base'][3] && in_array($this->config_balls['lh'][1], $balls[0])) {
            // 虎131
            $ret['win_contents'][] = [$this->config_balls['lh'][1]];
        } elseif ($lottery['base'][1] == $lottery['base'][3] && in_array($this->config_balls['lh'][2], $balls[0])) {
            // 和200
            $ret['win_contents'][] = [$this->config_balls['lh'][2]];
        }
    
        if (!empty($ret['win_contents'])) {
            $ret['win_counts'] = count($ret['win_contents']);
            $ret['price_sum'] = $bet[3] * $bet[6] * $ret['win_counts'];
            $ret['status'] = STATUS_WIN;
        }
    
        return $ret;
    } /* }}} */
    
    /**
     * @brief 龙虎_龙虎_千个
     *          根据千位、个位号码数值比大小，千位号码大于个位号码为龙，千位号码小于个位号码为虎，号码相同则为和。所选形态与开奖号码形态一致，即为中奖。
     *          投注方案：龙；开奖号码 2 8 4 0 6,即中奖。
     *          投注方案：虎；开奖号码 2 6 4 0 8,即中奖。
     *          投注方案：和；开奖号码 2 6 4 0 6,即中奖。
     * @access public
     */
    public function settlement_lh_qg(& $bet = [], & $lottery = []) /* {{{ */
    {
        $balls = get_balls($bet[2]);
        $ret = null;
    
        if ($lottery['base'][1] > $lottery['base'][4] && in_array($this->config_balls['lh'][0], $balls[0])) {
            // 龙133
            $ret['win_contents'][] = [$this->config_balls['lh'][0]];
        } elseif ($lottery['base'][1] < $lottery['base'][4] && in_array($this->config_balls['lh'][1], $balls[0])) {
            // 虎131
            $ret['win_contents'][] = [$this->config_balls['lh'][1]];
        } elseif ($lottery['base'][1] == $lottery['base'][4] && in_array($this->config_balls['lh'][2], $balls[0])) {
            // 和200
            $ret['win_contents'][] = [$this->config_balls['lh'][2]];
        }
    
        if (!empty($ret['win_contents'])) {
            $ret['win_counts'] = count($ret['win_contents']);
            $ret['price_sum'] = $bet[3] * $bet[6] * $ret['win_counts'];
            $ret['status'] = STATUS_WIN;
        }
    
        return $ret;
    } /* }}} */
    
    /**
     * @brief 龙虎_龙虎_百十
     *          根据百位、十位号码数值比大小，百位号码大于十位号码为龙，百位号码小于十位号码为虎，号码相同则为和。所选形态与开奖号码形态一致，即为中奖。
     *          投注方案：龙；开奖号码 2 4 8 6 0,即中奖。
     *          投注方案：虎；开奖号码 2 4 6 8 0,即中奖。
     *          投注方案：和；开奖号码 2 4 6 6 0,即中奖。
     * @access public
     */
    public function settlement_lh_bs(& $bet = [], & $lottery = []) /* {{{ */
    {
        $balls = get_balls($bet[2]);
        $ret = null;
    
        if ($lottery['base'][2] > $lottery['base'][3] && in_array($this->config_balls['lh'][0], $balls[0])) {
            // 龙133
            $ret['win_contents'][] = [$this->config_balls['lh'][0]];
        } elseif ($lottery['base'][2] < $lottery['base'][3] && in_array($this->config_balls['lh'][1], $balls[0])) {
            // 虎131
            $ret['win_contents'][] = [$this->config_balls['lh'][1]];
        } elseif ($lottery['base'][2] == $lottery['base'][3] && in_array($this->config_balls['lh'][2], $balls[0])) {
            // 和200
            $ret['win_contents'][] = [$this->config_balls['lh'][2]];
        }
    
        if (!empty($ret['win_contents'])) {
            $ret['win_counts'] = count($ret['win_contents']);
            $ret['price_sum'] = $bet[3] * $bet[6] * $ret['win_counts'];
            $ret['status'] = STATUS_WIN;
        }
    
        return $ret;
    } /* }}} */
    
    /**
     * @brief 龙虎_龙虎_百个
     *          根据百位、个位号码数值比大小，百位号码大于个位号码为龙，百位号码小于个位号码为虎，号码相同则为和。所选形态与开奖号码形态一致，即为中奖。
     *          投注方案：龙；开奖号码 2 4 8 0 6,即中奖。
     *          投注方案：虎；开奖号码 2 4 6 0 8,即中奖。
     *          投注方案：和；开奖号码 2 4 6 0 6,即中奖。
     * @access public
     */
    public function settlement_lh_bg(& $bet = [], & $lottery = []) /* {{{ */
    {
        $balls = get_balls($bet[2]);
        $ret = null;
    
        if ($lottery['base'][2] > $lottery['base'][4] && in_array($this->config_balls['lh'][0], $balls[0])) {
            // 龙133
            $ret['win_contents'][] = [$this->config_balls['lh'][0]];
        } elseif ($lottery['base'][2] < $lottery['base'][4] && in_array($this->config_balls['lh'][1], $balls[0])) {
            // 虎131
            $ret['win_contents'][] = [$this->config_balls['lh'][1]];
        } elseif ($lottery['base'][2] == $lottery['base'][4] && in_array($this->config_balls['lh'][2], $balls[0])) {
            // 和200
            $ret['win_contents'][] = [$this->config_balls['lh'][2]];
        }
    
        if (!empty($ret['win_contents'])) {
            $ret['win_counts'] = count($ret['win_contents']);
            $ret['price_sum'] = $bet[3] * $bet[6] * $ret['win_counts'];
            $ret['status'] = STATUS_WIN;
        }
    
        return $ret;
    } /* }}} */
    
    /**
     * @brief 龙虎_龙虎_十个
     *          根据十位、个位号码数值比大小，十位号码大于个位号码为龙，十位号码小于个位号码为虎，号码相同则为和。所选形态与开奖号码形态一致，即为中奖。
     *          投注方案：龙；开奖号码 2 4 0 8 6,即中奖。
     *          投注方案：虎；开奖号码 2 4 0 6 8,即中奖。
     *          投注方案：和；开奖号码 2 4 0 6 6,即中奖。
     * @access public
     */
    public function settlement_lh_sg(& $bet = [], & $lottery = []) /* {{{ */
    {
        $balls = get_balls($bet[2]);
        $ret = null;
    
        if ($lottery['base'][3] > $lottery['base'][4] && in_array($this->config_balls['lh'][0], $balls[0])) {
            // 龙133
            $ret['win_contents'][] = [$this->config_balls['lh'][0]];
        } elseif ($lottery['base'][3] < $lottery['base'][4] && in_array($this->config_balls['lh'][1], $balls[0])) {
            // 虎131
            $ret['win_contents'][] = [$this->config_balls['lh'][1]];
        } elseif ($lottery['base'][3] == $lottery['base'][4] && in_array($this->config_balls['lh'][2], $balls[0])) {
            // 和200
            $ret['win_contents'][] = [$this->config_balls['lh'][2]];
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
