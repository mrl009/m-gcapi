<?php
/**
 * @file games_ssc.php
 * @brief 时时彩下注库
 *      NOTE: 如果玩法为和局，status 返回 STATUS_HE: $ret['status'] = STATUS_HE;
 *
 * Copyright (C) 2017 GC.COM
 * All rights reserved.
 *
 * @package libraries
 * @author Langr <hua@langr.org> 2017/04/06 20:37
 *
 * $Id$
 */

include_once(dirname(__FILE__) . '/games.php');

class games_ssc
{
    public $config_balls = [
        /* 基本球 */
        'base' => [0, 1, 2, 3, 4, 5, 6, 7, 8, 9],
        /* 前三/中三/后三 直选 和值 */
        '3x_hz' => [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27],
        /* 二星 后二/前二 直选 和值 */
        '2x_hz' => [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18],
        /* 大小单双 后二/前二 直选 组选 */
        '2x_dxds' => [100, 101, 102, 103],
        /* 任选: 万 千 百 十 个位数 + 基本球号 */
        'rx_wqbsg' => [0, 1, 2, 3, 4, 5, 6, 7, 8, 9,291, 292, 293, 294, 295,],
        /* 龙虎: 龙133, 虎131, 和200 */
        'lh' => [133, 131, 200],
        /* 三星 和值 每个球对应的下注数表 */
        '3x_hz_tab' => [1, 3, 6, 10, 15, 21, 28, 36, 45, 55, 63, 69, 73, 75, 75, 73, 69, 63, 55, 45, 36, 28, 21, 15, 10, 6, 3, 1],
        /* 二星 和值 每个球对应的下注数表 */
        '2x_hz_tab' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 9, 8, 7, 6, 5, 4, 3, 2, 1],
        /* 跨度 前三 中三 后三 每个球对应的下注数表*/
        'kd_q3_tab' => [10, 54, 96, 126, 144, 150, 144, 126, 96, 54],
        /* 跨度 前二 后二 每个球对应的下注数表 */
        'kd_q2_tab' => [10, 18, 16, 14, 12, 10, 8, 6, 4, 2]
    ];
    
    /**
     * @brief 五星_五星直选_复式
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
     */
    public function bet_5x_5xzhx_fs(& $bet = []) /* {{{ */
    {
        // 取球号
        $balls = get_balls($bet['contents'], $this->config_balls['base']);
        // 检测球
        if ($balls == false || count($balls) != 5) {
            return false;
        }
        // 组合下注量
        $c = CC($balls, 5, true);
        $price_sum = round($bet['price'] * $bet['counts'], 3);
        return ($c['total'] == $bet['counts'] && $price_sum == $bet['price_sum']) ? true : false;
    } /* }}} */
    
    /**
     * @brief 五星_五星直选_单式
     *          手动输入一个5位数号码组成一注，所选号码的万位、千位、百位、十位、个位与开奖号码相同，且顺序一致，即为中奖。
     *          投注方案：23456
     *          开奖号码：23456，即中五星直选
     * @access public
     */
    public function bet_5x_5xzhx_ds(& $bet = []) /* {{{ */
    {
        // 取球号
        return $this->bet_5x_5xzhx_fs($bet);
    } /* }}} */
    
    /**
     * @brief 五星_五星直选_组合
     *          从万位、千位、百位、十位、个位中至少各选一个号码组成1-5星的组合，共五注，所选号码的个位与开奖号码相同，则中1个5等奖；所选号码的个位、十位与开奖号码相同，则中1个5等奖以及1个4等奖，依此类推，最高可中5个奖。
     *          五星组合示例，如购买：4+5+6+7+8，该票共10元，由以下5注：45678(五星)、5678(四星)、678(三星)、78(二星)、8(一星)构成。
     *          开奖号码：45678，即可中五星、四星、三星、二星、一星奖各1注。
     * @access public
     * @param
     * @return
     */
    public function bet_5x_5xzhx_zh(& $bet = []) /* {{{ */
    {
        // 取球号
        $balls = get_balls($bet['contents'], $this->config_balls['base']);
        // 检测球
        if ($balls == false || count($balls) != 5) {
            return false;
        }
        // 组合下注量
        $c = 5*count($balls[0])*count($balls[1])*count($balls[2])*count($balls[3])*count($balls[4]);
        $price_sum = round($bet['price'] * $bet['counts'], 3);
        return ($c == $bet['counts'] && $price_sum == $bet['price_sum']) ? true : false;
    } /* }}} */
    
    /**
     * @brief 五星_五星组选_组选120
     *          从0-9中任意选择5个号码组成一注，所选号码与开奖号码的万位、千位、百位、十位、个位相同，顺序不限，即为中奖。
     *          五星组合示例，如购买：4+5+6+7+8，该票共10元，由以下5注：45678(五星)、5678(四星)、678(三星)、78(二星)、8(一星)构成。
     *          开奖号码：45678，即可中五星、四星、三星、二星、一星奖各1注。
     * @access public
     */
    public function bet_5x_5xzx_zx120(& $bet = []) /* {{{ */
    {
        // 取球号
        $balls = get_balls($bet['contents'], $this->config_balls['base']);
        // 检测球
        if ($balls == false || count($balls) != 1) {
            return false;
        }
        // 组合下注量
        $c = CountGroupSelect($balls, 5);
        $price_sum = round($bet['price'] * $bet['counts'], 3);
        return ($c == $bet['counts'] && $price_sum == $bet['price_sum']) ? true : false;
    } /* }}} */
    
    /**
     * @brief 五星_五星组选_组选60
     *          选择1个二重号码和3个单号号码组成一注，所选的单号号码与开奖号码相同，且所选二重号码在开奖号码中出现了2次，即为中奖。
     *          投注方案：二重号：8，单号：0、2、5，
     *          只要开奖的5个数字包括 0、2、5、8、8，即可中五星组选60一等奖。
     * @access public
     */
    public function bet_5x_5xzx_zx60(& $bet = []) /* {{{ */
    {
        // 取球号
        $balls = get_balls($bet['contents'], $this->config_balls['base']);
        // 检测球
        if ($balls == false || count($balls) != 2) {
            return false;
        }
        // 组合下注量
        $c = CountGroupSelect($balls, 3);
        $price_sum = round($bet['price'] * $bet['counts'], 3);
        return ($c == $bet['counts'] && $price_sum == $bet['price_sum']) ? true : false;
    } /* }}} */
    
    
    /**
     * @brief 五星_五星组选_组选30
     *          选择2个二重号和1个单号号码组成一注，所选的单号号码与开奖号码相同，且所选的2个二重号码分别在开奖号码中出现了2次，即为中奖。
     *          投注方案：二重号：2、8，单号：0，
     *          只要开奖的5个数字包括 0、2、2、8、8，即可中五星组选30一等奖。
     * @access public
     */
    public function bet_5x_5xzx_zx30(& $bet = []) /* {{{ */
    {
        // 取球号
        $balls = get_balls($bet['contents'], $this->config_balls['base']);
        // 检测球
        if ($balls == false || count($balls) != 2) {
            return false;
        }
    
        // 组合下注量
        $c = CountGroupSelect($balls, 2, true);
        $price_sum = round($bet['price'] * $bet['counts'], 3);
        return ($c == $bet['counts'] && $price_sum == $bet['price_sum']) ? true : false;
    } /* }}} */
    
    /**
     * @brief 五星_五星组选_组选20
     *          选择1个三重号码和2个单号号码组成一注，所选的单号号码与开奖号码相同，且所选三重号码在开奖号码中出现了3次，即为中奖。
     *          投注方案：三重号：8，单号：0、2，
     *          只要开奖的5个数字包括 0、2、8、8、8，即可中五星组选20一等奖。
     * @access public
     */
    public function bet_5x_5xzx_zx20(& $bet = []) /* {{{ */
    {
        // 取球号
        $balls = get_balls($bet['contents'], $this->config_balls['base']);
        // 检测球
        if ($balls == false || count($balls) != 2) {
            return false;
        }
        // 组合下注量
        $c = CountGroupSelect($balls, 2);
        $price_sum = round($bet['price'] * $bet['counts'], 3);
        return ($c == $bet['counts'] && $price_sum == $bet['price_sum']) ? true : false;
    } /* }}} */
    
    /**
     * @brief 五星_五星组选_组选10
     *          选择1个三重号码和1个二重号码，所选三重号码在开奖号码中出现3次，并且所选二重号码在开奖号码中出现了2次，即为中奖。
     *          投注方案：三重号：8，二重号：2，
     *          只要开奖的5个数字包括 2、2、8、8、8，即可中五星组选10一等奖。
     * @access public
     */
    public function bet_5x_5xzx_zx10(& $bet = []) /* {{{ */
    {
        // 取球号
        $balls = get_balls($bet['contents'], $this->config_balls['base']);
        // 检测球
        if ($balls == false || count($balls) != 2) {
            return false;
        }
        // 组合下注量
        $c = CountGroupSelect($balls, 1);
        $price_sum = round($bet['price'] * $bet['counts'], 3);
        return ($c == $bet['counts'] && $price_sum == $bet['price_sum']) ? true : false;
    } /* }}} */
    
    /**
     * @brief 五星_五星组选_组选5
     *          选择1个四重号码和1个单号号码组成一注，所选的单号号码与开奖号码相同，且所选四重号码在开奖号码中出现了4次，即为中奖。
     *          投注方案：四重号：8，单号：2，
     *          只要开奖的5个数字包括 2、8、8、8、8，即可中五星组选5一等奖。
     * @access public
     */
    public function bet_5x_5xzx_zx5(& $bet = []) /* {{{ */
    {
        // 取球号
        $balls = get_balls($bet['contents'], $this->config_balls['base']);
        // 检测球
        if ($balls == false || count($balls) != 2) {
            return false;
        }
        // 组合下注量
        $c = CountGroupSelect($balls, 1);
        $price_sum = round($bet['price'] * $bet['counts'], 3);
        return ($c == $bet['counts'] && $price_sum == $bet['price_sum']) ? true : false;
    
    } /* }}} */
    
    /**
     * @brief 后四_后四直选_复式
     *          从千位、百位、十位、个位中至少各选1个号码组成一注，所选号码与开奖后4位相同，且顺序一致，即为中奖。
     *          投注方案：* 6 7 8 9
     *          开奖号码：* 6 7 8 9 即中四星直选。
     * @access public
     */
    public function bet_h4_h4zhx_fs(& $bet = []) /* {{{ */
    {
        // 取球号
        $balls = get_balls($bet['contents'], $this->config_balls['base']);
        // 检测球
        if ($balls == false || count($balls) != 4) {
            return false;
        }
        // 组合下注量
        $c = CC($balls, 4, true);
        $price_sum = round($bet['price'] * $bet['counts'], 3);
        return ($c['total'] == $bet['counts'] && $price_sum == $bet['price_sum']) ? true : false;
    } /* }}} */
    
    /**
     * @brief 后四_后四直选_单式
     *          从千位、百位、十位、个位中至少各选1个号码组成一注，所选号码与开奖后4位相同，且顺序一致，即为中奖。
     *          投注方案：* 6 7 8 9
     *          开奖号码：* 6 7 8 9 即中四星直选。
     * @access public
     */
    public function bet_h4_h4zhx_ds(& $bet = []) /* {{{ */
    {
        // 取球号
        return $this->bet_h4_h4zhx_fs($bet);
    } /* }}} */
    
    /**
     * @brief 后四_后四直选_组合
     *          从千位、百位、十位、个位中至少各选一个号码组成1-4星的组合共4注，所选号码的个位与开奖号码全部相同，则中1个四等奖；所选号码的十位、个位与开奖号码全部相同，则中一个四等奖以及一个三等奖，依此类推，最高可中4个奖。
     *          投注方案：5 6 7 8，
     *          有以下4注：5678（四星）、678（三星）、78（二星）、8（一星）构成。开奖号码：5678，即中四星、三星、二星、一星各1注。
     * @access public
     * @param
     * @return
     */
    public function bet_h4_h4zhx_zh(& $bet = []) /* {{{ */
    {
        // 取球号
        $balls = get_balls($bet['contents'], $this->config_balls['base']);
        // 检测球
        if ($balls == false || count($balls) != 4) {
            return false;
        }
        // 组合下注量
        $c = 4*count($balls[0])*count($balls[1])*count($balls[2])*count($balls[3]);
        $price_sum = round($bet['price'] * $bet['counts'], 3);
        return ($c == $bet['counts'] && $price_sum == $bet['price_sum']) ? true : false;
    } /* }}} */
    
    /**
     * @brief 后四_后四组选_组选24
     *          从千位、百位、十位、个位中至少各选1个号码组成一注，所选号码与开奖后4位相同，且顺序一致，即为中奖。
     *          投注方案：* 6 7 8 9
     *          开奖号码：* 6 7 8 9 即中四星直选。
     * @access public
     */
    public function bet_h4_h4zx_zx24(& $bet = []) /* {{{ */
    {
        // 取球号
        // 取球号
        $balls = get_balls($bet['contents'], $this->config_balls['base']);
        // 检测球
        if ($balls == false || count($balls) != 1) {
            return false;
        }
        // 组合下注量
        $c = CountGroupSelect($balls, 4);
        $price_sum = round($bet['price'] * $bet['counts'], 3);
        return ($c == $bet['counts'] && $price_sum == $bet['price_sum']) ? true : false;
    } /* }}} */
    
    /**
     * @brief 后四_后四组选_组选12
     *          从0-9中任意选择4个号码组成一注，后四位开奖号码包含所选号码，且顺序不限，即为中奖。
     *          投注方案：0 5 6 8
     *          开奖号码：* 8 5 6 0（顺序不限）即中后四组选24。
     * @access public
     */
    public function bet_h4_h4zx_zx12(& $bet = []) /* {{{ */
    {
        // 取球号
        $balls = get_balls($bet['contents'], $this->config_balls['base']);
        // 检测球
        if ($balls == false || count($balls) != 2) {
            return false;
        }
        // 组合下注量
        $c = CountGroupSelect($balls, 2);
        $price_sum = round($bet['price'] * $bet['counts'], 3);
        return ($c == $bet['counts'] && $price_sum == $bet['price_sum']) ? true : false;
    } /* }}} */
    
    /**
     * @brief 后四_后四组选_组选6
     *          选择1个二重号码和2个单号号码组成一注，所选单号号码与开奖号码相同，且所选二重号码在开奖号码中出现2次，即为中奖。
     *          投注方案：二重号：8，单号：0、6，
     *          只要开奖的四个数字包括 0、6、8、8，即可中四星组选12。
     * @access public
     */
    public function bet_h4_h4zx_zx6(& $bet = []) /* {{{ */
    {
        // 取球号
        $balls = get_balls($bet['contents'], $this->config_balls['base']);
        // 检测球
        if ($balls == false || count($balls) != 1) {
            return false;
        }
        // 组合下注量
        $c = CountGroupSelect($balls, 2);
        $price_sum = round($bet['price'] * $bet['counts'], 3);
        return ($c == $bet['counts'] && $price_sum == $bet['price_sum']) ? true : false;
    
    } /* }}} */
    
    /**
     * @brief 后四_后四组选_组选4
     *          选择2个二重号码组成一注，所选的2个二重号码在开奖号码中分别出现了2次，即为中奖。
     *          投注方案：二重号：6、8，
     *          只要开奖的四个数字从小到大排列为 6、6、8、8，即可中四星组选6。
     * @access public
     */
    public function bet_h4_h4zx_zx4(& $bet = []) /* {{{ */
    {
        // 取球号
        $balls = get_balls($bet['contents'], $this->config_balls['base']);
        // 检测球
        if ($balls == false || count($balls) != 2) {
            return false;
        }
        // 组合下注量
        $c = CountGroupSelect($balls, 1);
        $price_sum = round($bet['price'] * $bet['counts'], 3);
        return ($c == $bet['counts'] && $price_sum == $bet['price_sum']) ? true : false;
    
    } /* }}} */
    
    /**
     * @brief 前四_前四直选_复试
     *          选择2个二重号码组成一注，所选的2个二重号码在开奖号码中分别出现了2次，即为中奖。
     *          投注方案：二重号：6、8，
     *          只要开奖的四个数字从小到大排列为 6、6、8、8，即可中四星组选6。
     * @access public
     */
    public function bet_q4_q4zhx_fs(& $bet = []) /* {{{ */
    {
        return $this->bet_h4_h4zhx_fs($bet);
    } /* }}} */
    
    /**
     * @brief 前四_前四直选_单试
     *          选择2个二重号码组成一注，所选的2个二重号码在开奖号码中分别出现了2次，即为中奖。
     *          投注方案：二重号：6、8，
     *          只要开奖的四个数字从小到大排列为 6、6、8、8，即可中四星组选6。
     * @access public
     */
    public function bet_q4_q4zhx_ds(& $bet = []) /* {{{ */
    {
        // 取球号
        return $this->bet_h4_h4zhx_fs($bet);
    } /* }}} */
    
    /**
     * @brief 前四_前四直选_组合
     *          选择2个二重号码组成一注，所选的2个二重号码在开奖号码中分别出现了2次，即为中奖。
     *          投注方案：二重号：6、8，
     *          只要开奖的四个数字从小到大排列为 6、6、8、8，即可中四星组选6。
     * @access public
     */
    public function bet_q4_q4zhx_zh(& $bet = []) /* {{{ */
    {
        // 取球号
        $balls = get_balls($bet['contents'], $this->config_balls['base']);
        // 检测球
        if ($balls == false || count($balls) != 4) {
            return false;
        }
        // 组合下注量
        $c = 4*count($balls[0])*count($balls[1])*count($balls[2])*count($balls[3]);
        $price_sum = round($bet['price'] * $bet['counts'], 3);
        return ($c == $bet['counts'] && $price_sum == $bet['price_sum']) ? true : false;
    } /* }}} */
    
    /**
     * @brief 前四_前四组选_组选24
     *          选择2个二重号码组成一注，所选的2个二重号码在开奖号码中分别出现了2次，即为中奖。
     *          投注方案：二重号：6、8，
     *          只要开奖的四个数字从小到大排列为 6、6、8、8，即可中四星组选6。
     * @access public
     */
    public function bet_q4_q4zx_zx24(& $bet = []) /* {{{ */
    {
        // 取球号
        return $this->bet_h4_h4zx_zx24($bet);
    } /* }}} */
    
    /**
     * @brief 前四_前四组选_组选12
     *          选择2个二重号码组成一注，所选的2个二重号码在开奖号码中分别出现了2次，即为中奖。
     *          投注方案：二重号：6、8，
     *          只要开奖的四个数字从小到大排列为 6、6、8、8，即可中四星组选6。
     * @access public
     */
    public function bet_q4_q4zx_zx12(& $bet = []) /* {{{ */
    {
        // 取球号
        return $this->bet_h4_h4zx_zx12($bet);
    } /* }}} */
    
    /**
     * @brief 前四_前四组选_组选6
     *          选择2个二重号码组成一注，所选的2个二重号码在开奖号码中分别出现了2次，即为中奖。
     *          投注方案：二重号：6、8，
     *          只要开奖的四个数字从小到大排列为 6、6、8、8，即可中四星组选6。
     * @access public
     */
    public function bet_q4_q4zx_zx6(& $bet = []) /* {{{ */
    {
        // 取球号
        return $this->bet_h4_h4zx_zx6($bet);
    } /* }}} */
    
    /**
     * @brief 前四_前四组选_组选4
     *          选择2个二重号码组成一注，所选的2个二重号码在开奖号码中分别出现了2次，即为中奖。
     *          投注方案：二重号：6、8，
     *          只要开奖的四个数字从小到大排列为 6、6、8、8，即可中四星组选6。
     * @access public
     */
    public function bet_q4_q4zx_zx4(& $bet = []) /* {{{ */
    {
        // 取球号
        return $this->bet_h4_h4zx_zx4($bet);
    } /* }}} */
    
    /**
     * @brief 后三_后三直选_复式
     *          从百位、十位、个位中选择一个3位数号码组成一注，所选号码与开奖号码后3位相同，且顺序一致，即为中奖。
     *          投注方案：345；
     *          开奖号码：345；即中后三直选。
     * @access public
     */
    public function bet_h3_h3zhx_fs(& $bet = []) /* {{{ */
    {
        // 取球号
        $balls = get_balls($bet['contents'], $this->config_balls['base']);
        // 检测球
        if ($balls == false || count($balls) != 3) {
            return false;
        }
        // 组合下注量
        $c = CC($balls, 3, true);
        $price_sum = round($bet['price'] * $bet['counts'], 3);
        return ($c['total'] == $bet['counts'] && $price_sum == $bet['price_sum']) ? true : false;
    } /* }}} */
    
    /**
     * @brief 后三_后三直选_单式
     *          手动输入一个3位数号码组成一注，所选号码的百位、十位、个位与开奖号码相同，且顺序一致，即为中奖。
     *          投注方案：345；
     *          开奖号码：345，即中后三直选。
     * @access public
     */
    public function bet_h3_h3zhx_ds(& $bet = []) /* {{{ */
    {
        // 取球号
        return $this->bet_h3_h3zhx_fs($bet);
    } /* }}} */
    
    /**
     * @brief 后三_后三直选_直选和值
     *          所选数值等于开奖号码的百位、十位、个位三个数字相加之和，即为中奖。
     *          投注方案：和值1；
     *          开奖号码后三位：001,010,100,即中后三直选。
     * @access public
     */
    public function bet_h3_h3zhx_zxhz(& $bet = []) /* {{{ */
    {
        // 取球号
        $balls = get_balls($bet['contents'], $this->config_balls['3x_hz']);
        // 检测球
        if ($balls == false || count($balls) != 1) {
            return false;
        }
        // 组合下注量
        $c = 0;
        foreach ($balls[0] as $value) {
            $c += $this->config_balls['3x_hz_tab'][$value];
        }
        $price_sum = round($bet['price'] * $bet['counts'], 3);
        return ($c == $bet['counts'] && $price_sum == $bet['price_sum']) ? true : false;
    } /* }}} */
    
    /**
     * @brief 后三_后三组选_组三
     *          从0-9中选择2个数字组成两注，所选号码与开奖号码的百位、十位、个位相同，且顺序不限，即为中奖。
     *          投注方案：58，
     *          开奖号码：1个5,2个8；或者2个5，一个8（顺序不限），即中组选三。
     * @access public
     */
    public function bet_h3_h3zx_zx3(& $bet = []) /* {{{ */
    {
        // 取球号
        $balls = get_balls($bet['contents'], $this->config_balls['base']);
        // 检测球
        if ($balls == false || count($balls) != 1) {
            return false;
        }
        // 组合下注量
        $c = A(count($balls[0]), 2);
        $price_sum = round($bet['price'] * $bet['counts'], 3);
        return ($c == $bet['counts'] && $price_sum == $bet['price_sum']) ? true : false;
    
    } /* }}} */
    
    /**
     * @brief 后三_后三组选_组六
     *          从0-9中任意选择3个号码组成一注，所选号码与开奖号码的百位、十位、个位相同，顺序不限，即为中奖。
     *          投注方案：2,5,8；
     *          开奖号码后三位：1个2、1个5、1个8 (顺序不限)，即中后三组选六。
     * @access public
     */
    public function bet_h3_h3zx_zx6(& $bet = []) /* {{{ */
    {
        // 取球号
        $balls = get_balls($bet['contents'], $this->config_balls['base']);
        // 检测球
        if ($balls == false || count($balls) != 1) {
            return false;
        }
        // 组合下注量
        $c = CountGroupSelect($balls, 3);
        $price_sum = round($bet['price'] * $bet['counts'], 3);
        return ($c == $bet['counts'] && $price_sum == $bet['price_sum']) ? true : false;
    } /* }}} */
    
    /**
     * @brief 后三_后三组选_混合组选
     *          手动输入购买号码，3个号码为一注，开奖号码的百位、十位、个位符合前三组三或组六均为中奖。
     *          投注方案：分别投注（668）（123）
     *          开奖号码后三位：686 668等，（顺序不限，需开出两个6）即中组选三；或者123 213等，（顺序不限，不可有号码重复）即中组选六。
     * @access public
     */
    public function bet_h3_h3zx_hhzx(& $bet = []) /* {{{ */
    {
        return $this->bet_h3_h3zx_zx6($bet);
    } /* }}} */
    
    /**
     * @brief 中三_中三直选_复式
     *          从千位、百位、十位中选择一个3位数号码组成一注，所选号码与开奖号码的中间3位相同，且顺序一致，即为中奖。
     *          投注方案：345；
     *          开奖号码：23456，即中中三直选。
     * @access public
     */
    public function bet_z3_z3zhx_fs(& $bet = []) /* {{{ */
    {
        // 取球号
        return $this->bet_h3_h3zhx_fs($bet);
    } /* }}} */
    
    /**
     * @brief 中三_中三直选_单式
     *          手动输入一个3位数号码组成一注，所选号码的千位、百位、十位与开奖号码相同，且顺序一致，即为中奖。
     *          投注方案：345；
     *          开奖号码：23456，即中奖中三直选。
     * @access public
     */
    public function bet_z3_z3zhx_ds(& $bet = []) /* {{{ */
    {
        // 取球号
        return $this->bet_h3_h3zhx_fs($bet);
    } /* }}} */
    
    /**
     * @brief 中三_中三直选_直选和值
     *          所选数值等于开奖号码的千位、百位、十位三个数字相加之和，即为中奖。
     *          投注方案：和值1；
     *          开奖号码中间三位：01001,00010,00100,即中中三直选。
     * @access public
     */
    public function bet_z3_z3zhx_zxhz(& $bet = []) /* {{{ */
    {
        // 取球号
        return $this->bet_h3_h3zhx_zxhz($bet);
    } /* }}} */
    
    /**
     * @brief 中三_中三组选_组三
     *          从0-9中选择2个数字组成两注，所选号码与开奖号码的千位、百位、十位相同，且顺序不限，即为中奖。
     *          投注方案：5,8,8；
     *          开奖号码中间三位：1个5，2个8 (顺序不限)，即中中三组选三。
     * @access public
     */
    public function bet_z3_z3zx_zx3(& $bet = []) /* {{{ */
    {
        // 取球号
        return $this->bet_h3_h3zx_zx3($bet);
    } /* }}} */
    
    /**
     * @brief 中三_中三组选_组六
     *          从0-9中任意选择3个号码组成一注，所选号码与开奖号码的千位、百位、十位相同，顺序不限，即为中奖。
     *          投注方案：2,5,8；
     *          开奖号码中间三位：1个2、1个5、1个8 (顺序不限)，即中中三组选六。
     * @access public
     */
    public function bet_z3_z3zx_zx6(& $bet = []) /* {{{ */
    {
        // 取球号
        return $this->bet_h3_h3zx_zx6($bet);
    } /* }}} */
    
    /**
     * @brief 中三_中三组选_混合组选
     *          手动输入购买号码，3个号码为一注，开奖号码的千位、百位、十位符合前三组三或组六均为中奖。
     *          投注方案：分别投注（668）（123）
     *          开奖号码中三位：686 668等，（顺序不限，需开出两个6）即中组选三；或者123 213等，（顺序不限，不可有号码重复）即中组选六。
     * @access public
     */
    public function bet_z3_z3zx_hhzx(& $bet = []) /* {{{ */
    {
        // 取球号
        return $this->bet_z3_z3zx_zx6($bet);
    } /* }}} */
    
    
    /**
     * @brief 前三_前三直选_复试
     *          从万位、千位、百位中选择一个3位数号码组成一注，所选号码与开奖号码的前3位相同，且顺序一致，即为中奖。
     *          投注方案：345；
     *          开奖号码：345，即中前三直选。
     * @access public
     */
    public function bet_q3_q3zhx_fs(& $bet = []) /* {{{ */
    {
        // 取球号
        return $this->bet_h3_h3zhx_fs($bet);
    } /* }}} */
    
    /**
     * @brief 前三_前三直选_单试
     *          手动输入一个3位数号码组成一注，所选号码的万位、千位、百位与开奖号码相同，且顺序一致，即为中奖。
     *          投注方案：345；
     *          开奖号码：345，即中前三直选。
     * @access public
     */
    public function bet_q3_q3zhx_ds(& $bet = []) /* {{{ */
    {
        // 取球号
        return $this->bet_h3_h3zhx_fs($bet);
    } /* }}} */
    
    /**
     * @brief 前三_前三直选_直选和值
     *          从0-27中任意选择1个或1个以上号码，所选数值等于开奖号码的万位、千位、百位三个数字相加之和，即为中奖。
     *          投注方案：和值1；
     *          开奖号码前三位：001,010,100,即中前三直选和值。
     * @access public
     */
    public function bet_q3_q3zhx_zxhz(& $bet = []) /* {{{ */
    {
        // 取球号
        return $this->bet_h3_h3zhx_zxhz($bet);
    } /* }}} */
    
    /**
     * @brief 前三_前三组选_组三
     *          从0至9中任选2个不同号码组成两注，开奖号码的万位、千位、百位包含所选号码，且其中必须有一个号码重复，顺序不限，即为中奖。
     *          投注方案：5,8,8；
     *          开奖号码前三位：1个5，2个8 (顺序不限)，即中前三组选三。
     * @access public
     */
    public function bet_q3_q3zx_zx3(& $bet = []) /* {{{ */
    {
        // 取球号
        return $this->bet_h3_h3zx_zx3($bet);
    } /* }}} */
    
    /**
     * @brief 前三_前三组选_组六
     *          从0至9中任选3个不同号码组成一注，开奖号码的万位、千位、百位包含所选号码，不可有号码重复，顺序不限，即为中奖。
     *          投注方案：2,5,8；
     *          开奖号码前三位：1个2、1个5、1个8 (顺序不限)，即中前三组选六。
     * @access public
     */
    public function bet_q3_q3zx_zx6(& $bet = []) /* {{{ */
    {
        // 取球号
        return $this->bet_h3_h3zx_zx6($bet);
    } /* }}} */
    
    /**
     * @brief 前三_前三组选_混合组选
     *          手动输入购买号码，3个号码为一注，开奖号码的万位、千位、百位符合前三组三或组六均为中奖。
     *          投注方案：分别投注（668）（123）
     *          开奖号码前三位：686 668等，（顺序不限，需开出两个6）即中组选三；或者123 213等，（顺序不限，不可有号码重复）即中组选六。
     * @access public
     */
    public function bet_q3_q3zx_hhzx(& $bet = []) /* {{{ */
    {
        // 取球号
        return $this->bet_q3_q3zx_zx6($bet);
    } /* }}} */
    
    /**
     * @brief 二星_后二直选_复试
     *          从十位、个位中选择一个2位数号码组成一注，所选号码与开奖号码的十位、个位相同，且顺序一致，即为中奖。
     *          投注方案：58；
     *          开奖号码后二位：58，即中后二直选一等奖。
     * @access public
     */
    public function bet_2x_h2zhx_fs(& $bet = []) /* {{{ */
    {
        // 取球号
        $balls = get_balls($bet['contents'], $this->config_balls['base']);
        // 检测球
        if ($balls == false || count($balls) != 2) {
            return false;
        }
        // 组合下注量
        $c = count($balls[0])*count($balls[1]);
        $price_sum = round($bet['price'] * $bet['counts'], 3);
        return ($c == $bet['counts'] && $price_sum == $bet['price_sum']) ? true : false;
    } /* }}} */
    
    /**
     * @brief 二星_后二直选_单试
     *          手动输入一个2位数号码组成一注，所选号码的十位、个位与开奖号码相同，且顺序一致，即为中奖。
     *          投注方案：58；
     *          开奖号码后二位：58，即中后二直选一等奖。
     * @access public
     */
    public function bet_2x_h2zhx_ds(& $bet = []) /* {{{ */
    {
        // 取球号
        return $this->bet_2x_h2zhx_fs($bet);
    } /* }}} */
    
    /**
     * @brief 二星_后二直选_直选和值
     *          从0-18中任意选择1个或1个以上的和值号码，所选数值等于开奖号码的十位、个位二个数字相加之和，即为中奖。
     *          投注方案：和值1；
     *          开奖号码后二位：01,10，即中后二直选和值。
     * @access public
     */
    public function bet_2x_h2zhx_zxhz(& $bet = []) /* {{{ */
    {
        // 取球号
        $balls = get_balls($bet['contents'], $this->config_balls['2x_hz']);
        // 检测球
        if ($balls == false || count($balls) != 1) {
            return false;
        }
        // 组合下注量
        $c = 0;
        foreach ($balls[0] as $value) {
            $c += $this->config_balls['2x_hz_tab'][$value];
        }
        $price_sum = round($bet['price'] * $bet['counts'], 3);
        return ($c == $bet['counts'] && $price_sum == $bet['price_sum']) ? true : false;
    
    } /* }}} */
    
    /**
     * @brief 二星_后二直选_直选和值
     *          对十位和个位的“大（56789）小（01234）、单（13579）双（02468）”形态进行购买，所选号码的位置、形态与开奖号码的位置、形态相同，即为中奖。
     *          投注方案：大单；
     *          开奖号码十位与个位：大单，即中后二大小单双。
     * @access public
     */
    public function bet_2x_h2zhx_dxds(& $bet = []) /* {{{ */
    {
        // 取球号
        $balls = get_balls($bet['contents'], $this->config_balls['2x_dxds']);
        // 检测球
        if ($balls == false || count($balls) != 2) {
            return false;
        }
        // 组合下注量
        $c = count($balls[0]) * count($balls[1]);
        $price_sum = round($bet['price'] * $bet['counts'], 3);
        return ($c == $bet['counts'] && $price_sum == $bet['price_sum']) ? true : false;
    } /* }}} */
    
    /**
     * @brief 二星_后二组选_复式
     *          从0-9中选2个号码组成一注，所选号码与开奖号码的十位、个位相同，顺序不限，即中奖。
     *          投注方案：5,8；
     *          开奖号码后二位：1个5，1个8 (顺序不限)，即中后二组选。
     * @access public
     */
    public function bet_2x_h2zx_fs(& $bet = []) /* {{{ */
    {
        // 取球号
        $balls = get_balls($bet['contents'], $this->config_balls['base']);
        // 检测球
        if ($balls == false || count($balls) != 1) {
            return false;
        }
        // 组合下注量
        $c = CountGroupSelect($balls, 2);
        $price_sum = round($bet['price'] * $bet['counts'], 3);
        return ($c == $bet['counts'] && $price_sum == $bet['price_sum']) ? true : false;
    } /* }}} */
    
    /**
     * @brief 二星_后二组选_单式
     *          手动输入一个2位数号码组成一注，所选号码的十位、个位与开奖号码相同，顺序不限，即为中奖。
     *          投注方案：5,8；
     *          开奖号码后二位：1个5，1个8 (顺序不限)，即中后二组选。
     * @access public
     */
    public function bet_2x_h2zx_ds(& $bet = []) /* {{{ */
    {
        // 取球号
        return $this->bet_2x_h2zx_fs($bet);
    } /* }}} */
    
    
    /**
     * @brief 二星_前二直选_复式
     *          从万位、千位中选择一个2位数号码组成一注，所选号码与开奖号码的前2位相同，且顺序一致，即为中奖。
     *          投注方案：58；
     *          开奖号码前二位：58，即中前二直选。
     * @access public
     */
    public function bet_2x_q2zhx_fs(& $bet = []) /* {{{ */
    {
        // 取球号
        return $this->bet_2x_h2zhx_fs($bet);
    } /* }}} */
    
    /**
     * @brief 二星_前二直选_单式
     *          手动输入一个2位数号码组成一注，所选号码的万位、千位与开奖号码相同，且顺序一致，即为中奖。
     *          投注方案：58；
     *          开奖号码前二位：58，即中前二直选。
     * @access public
     */
    public function bet_2x_q2zhx_ds(& $bet = []) /* {{{ */
    {
        // 取球号
        return $this->bet_2x_h2zhx_fs($bet);
    } /* }}} */
    
    /**
     * @brief 二星_前二直选_直选和值
     *          从0-18中任意选择1个或1个以上的和值号码。所选数值等于开奖号码的万位、千位二个数字相加之和，即为中奖。
     *          投注方案：和值1；
     *          开奖号码前二位：01,10，即中前二直选和值。
     * @access public
     */
    public function bet_2x_q2zhx_zxhz(& $bet = []) /* {{{ */
    {
        // 取球号
        return $this->bet_2x_h2zhx_zxhz($bet);
    } /* }}} */
    
    /**
     * @brief 二星_前二直选_大小单双
     *          对万位、千位的【大 56789】【小 01234】【单 13579】【双 02468】号码形态进行购买，所选号码（形态）与开奖号码（形态）相同，顺序一致，即为中奖。
     *          投注方案：小双；
     *          开奖号码万位2 千位8（万位小千位双），即中前二大小单双。
     * @access public
     */
    public function bet_2x_q2zhx_dxds(& $bet = []) /* {{{ */
    {
        // 取球号
        return $this->bet_2x_h2zhx_dxds($bet);
    } /* }}} */
    
    /**
     * @brief 二星_前二组选_复试
     *          从0-9中选2个号码组成一注，所选号码与开奖号码的万位、千位相同，顺序不限，即中奖。
     *          投注方案：5,8；
     *          开奖号码前二位：1个5，1个8 (顺序不限)，即中前二组选。
     * @access public
     */
    public function bet_2x_q2zx_fs(& $bet = []) /* {{{ */
    {
        // 取球号
        return $this->bet_2x_h2zx_fs($bet);
    } /* }}} */
    
    /**
     * @brief 二星_前二组选_单试
     *          手动输入一个2位数号码组成一注，所选号码的万位、千位与开奖号码相同，顺序不限，即为中奖。
     *          投注方案：5,8；
     *          开奖号码前二位：1个5，1个8 (顺序不限)，即中前二组选。
     * @access public
     */
    public function bet_2x_q2zx_ds(& $bet = []) /* {{{ */
    {
        // 取球号
        return $this->bet_2x_q2zx_fs($bet);
    } /* }}} */
    
    /**
     * @brief 定位胆_定位胆_定位胆
     *          从万位、千位、百位、十位、个位任意位置上至少选择1个以上号码，所选号码与相同位置上的开奖号码一致，即为中奖。
     *          投注方案：万位1；
     *          开奖号码万位：1，即中定位胆万位。
     * @access public
     */
    public function bet_dwd_dwd(& $bet = []) /* {{{ */
    {
        // 取球号
        $balls = get_balls($bet['contents'], $this->config_balls['base']);
        // 检测球
        if ($balls == false || count($balls) != 5) {
            return false;
        }
        // 组合下注量
        $c = 0;
        foreach ($balls as $v) {
            if (count($v) == 1 && $v[0] == "") {
                continue;
            };
            $c += count($v);
        }
        $price_sum = round($bet['price'] * $bet['counts'], 3);
        return ($c == $bet['counts'] && $price_sum == $bet['price_sum']) ? true : false;
    } /* }}} */
    
    /**
     * @brief 不定胆_三星不定胆一码_后三
     *          从0-9中选择1个号码，每注由1个号码组成，只要开奖号码的百位、十位、个位中包含所选号码，即为中奖。
     *          投注方案：1；
     *          开奖号码后三位：至少出现1个1，即中后三一码不定位。
     * @access public
     */
    public function bet_bdw_3x1m_h3(& $bet = []) /* {{{ */
    {
        // 取球号
        $balls = get_balls($bet['contents'], $this->config_balls['base']);
        // 检测球
        if ($balls == false || count($balls) != 1) {
            return false;
        }
        // 组合下注量
        $c = count($balls[0]);
        $price_sum = round($bet['price'] * $bet['counts'], 3);
        return ($c == $bet['counts'] && $price_sum == $bet['price_sum']) ? true : false;
    } /* }}} */
    
    /**
     * @brief 不定胆_三星不定胆一码_中三
     *          从0-9中选择1个号码，每注由1个号码组成，只要开奖号码千位、百位、十位中包含所选号码，即为中奖。
     *          投注方案：1；
     *          开奖号码中间三位：至少出现1个1，即中中三一码不定位。
     * @access public
     */
    public function bet_bdw_3x1m_z3(& $bet = []) /* {{{ */
    {
        // 取球号
        return $this->bet_bdw_3x1m_h3($bet);
    } /* }}} */
    
    /**
     * @brief 不定胆_三星不定胆一码_前三
     *          从0-9中选择1个号码，每注由1个号码组成，只要开奖号码的万位、千位、百位中包含所选号码，即为中奖。
     *          投注方案：1；
     *          开奖号码前三位：至少出现1个1，即中前三一码不定位。
     * @access public
     */
    public function bet_bdw_3x1m_q3(& $bet = []) /* {{{ */
    {
        // 取球号
        return $this->bet_bdw_3x1m_h3($bet);
    } /* }}} */
    
    /**
     * @brief 不定胆_三星不定胆二码_后三
     *          从0-9中选择2个号码，每注由2个不同的号码组成，开奖号码的百位、十位、个位中同时包含所选的2个号码，即为中奖。
     *          投注方案：1,2；
     *          开奖号码后三位：至少出现1和2各1个，即中后三二码不定位。
     * @access public
     */
    public function bet_bdw_3x2m_h3(& $bet = []) /* {{{ */
    {
        // 取球号
        $balls = get_balls($bet['contents'], $this->config_balls['base']);
        // 检测球
        if ($balls == false || count($balls) != 1) {
            return false;
        }
        // 组合下注量
        $c = CountGroupSelect($balls, 2);
        $price_sum = round($bet['price'] * $bet['counts'], 3);
        return ($c == $bet['counts'] && $price_sum == $bet['price_sum']) ? true : false;
    } /* }}} */
    
    /**
     * @brief 不定胆_三星不定胆二码_中三
     *          从0-9中选择2个号码，每注由2个不同的号码组成，开奖号码的千位、百位、十位中同时包含所选的2个号码，即为中奖。
     *          投注方案：1,2；
     *          开奖号码中间三位：至少出现1和2各1个，即中中三二码不定位。
     * @access public
     */
    public function bet_bdw_3x2m_z3(& $bet = []) /* {{{ */
    {
        return $this->bet_bdw_3x2m_h3($bet);
    } /* }}} */
    
    /**
     * @brief 不定胆_三星不定胆二码_前三
     *          从0-9中选择2个号码，每注由2个不同的号码组成，开奖号码的万位、千位、百位中同时包含所选的2个号码，即为中奖。
     *          投注方案：1,2；
     *          开奖号码前三位：至少出现1和2各1个，即中前三二码不定位。
     * @access public
     */
    public function bet_bdw_3x2m_q3(& $bet = []) /* {{{ */
    {
        return $this->bet_bdw_3x2m_h3($bet);
    } /* }}} */
    
    /**
     * @brief 任选_任二_复试
     *          从万，千，百，十，个位中至少选择两个位置，至少各选一个号码组成一注，所选号码与开奖号码的指定位置上的号码相同，且顺序一致，即为中奖。
     *          投注方案：万位1，千位2
     *          开奖号码：12345，即为中奖。
     * @access public
     */
    public function bet_rx_rx2_fs(& $bet = []) /* {{{ */
    {
        // 取球号
        $balls = get_balls($bet['contents'], $this->config_balls['base']);
        // 检测球
        if ($balls == false || count($balls) != 5) {
            return false;
        }
    
        $c = RxBetCount($balls,2);
        $price_sum = round($bet['price'] * $bet['counts'], 3);
        return ($c == $bet['counts'] && $price_sum == $bet['price_sum']) ? true : false;
    } /* }}} */
    
    /**
     * @brief 任选_任二_单试
     *          从万、千、百、十、个位中至少选择两个位置，至少手动输入一个两位数的号码构成一注，所选号码与开奖号码的指定位置上的号码相同，且顺序一致，即为中奖。
     *          投注方案：位置选择万、千位，输入号码12
     *          开奖号码：12345，即为中奖。
     * @access public
     */
    public function bet_rx_rx2_ds(& $bet = []) /* {{{ */
    {
        return $this->bet_rx_rx2_fs($bet);
    } /* }}} */
    
    /**
     * @brief 任选_任二_组选
     *          从万、千、百、十、个位中至少选择两个位置，至少选个两个号码组成一注，所选号码与开奖号码指定位置上的号码相同，且顺序不限，即为中奖。
     *          投注方案：位置选择万、千位，选择号码56
     *          开奖号码：56823或者65789，即为中奖。
     * @access public
     */
    public function bet_rx_rx2_zx(& $bet = []) /* {{{ */
    {
        // 取球号
        $balls = get_balls($bet['contents'], $this->config_balls['rx_wqbsg']);
        // 检测球
        if ($balls == false || count($balls) != 2) {
            return false;
        }
    
        $c = C(count($balls[0]),2)*C(count($balls[1]),2);
        $price_sum = round($bet['price'] * $bet['counts'], 3);
        return ($c == $bet['counts'] && $price_sum == $bet['price_sum']) ? true : false;
    } /* }}} */
    
    /**
     * @brief 任选_任三_复试
     *          从万、千、百、十、个中至少3个位置各选一个或多个号码，将各个位置的号码进行组合，所选位置号码与开奖位置号码相同则中奖。
     *          投注方案：万位买0，千位买1，百位买2，十位买3，
     *          开奖01234，则中奖。
     * @access public
     */
    public function bet_rx_rx3_fs(& $bet = []) /* {{{ */
    {
        // 取球号
        $balls = get_balls($bet['contents'], $this->config_balls['base']);
        // 检测球
        if ($balls == false || count($balls) != 5) {
            return false;
        }
        // 组合下注量
        $c = RxBetCount($balls,3);
    
        $price_sum = round($bet['price'] * $bet['counts'], 3);
        return ($c == $bet['counts'] && $price_sum == $bet['price_sum']) ? true : false;
    } /* }}} */
    
    /**
     * @brief 任选_任三_单试
     *          手动输入一注或者多注的三个号码和至少三个位置，如果选中的号码与位置和开奖号码对应则中奖。
     *          输入号码012选择万、千、百位置，
     *          如开奖号码位012**； 则中奖。
     * @access public
     */
    public function bet_rx_rx3_ds(& $bet = []) /* {{{ */
    {
        // 取球号
        return $this->bet_rx_rx3_fs($bet);
    } /* }}} */
    
    /**
     * @brief 任选_任三_组三
     *          从0-9中任意选择2个或2个以上号码和万、千、百、十、个任意的三个位置，如果组合的号码与开奖号码对应则中奖
     *          位置选择万、千、百，号码选择01；
     *          开奖号码为110**、则中奖
     * @access public
     * @param
     * @return
     */
    public function bet_rx_rx3_z3(& $bet = []) /* {{{ */
    {
        // 取球号
        $balls = get_balls($bet['contents'], $this->config_balls['rx_wqbsg']);
        // 检测球
        if ($balls == false || count($balls) != 2) {
            return false;
        }
    
        $c = C(count($balls[0]),3)*A(count($balls[1]),2);
        $price_sum = round($bet['price'] * $bet['counts'], 3);
        return ($c == $bet['counts'] && $price_sum == $bet['price_sum']) ? true : false;
    } /* }}} */
    
    /**
     * @brief 任选_任三_组六
     *          从0-9中任意选择3个或3个以上号码和万、千、百、十、个任意的三个位置，如果组合的号码与开奖号码对应则中奖
     *          位置选择万、千、百，号码选择012；
     *          开奖号码为012**、则中奖
     * @access public
     */
    public function bet_rx_rx3_z6(& $bet = []) /* {{{ */
    {
        // 取球号
        $balls = get_balls($bet['contents'], $this->config_balls['rx_wqbsg']);
        // 检测球
        if ($balls == false || count($balls) != 2) {
            return false;
        }
    
        $c = C(count($balls[0]),3)*C(count($balls[1]),3);
        $price_sum = round($bet['price'] * $bet['counts'], 3);
        return ($c == $bet['counts'] && $price_sum == $bet['price_sum']) ? true : false;
    } /* }}} */
    
    /**
     * @brief 任选_任三_混合组选
     *          手动输入购买号码，至少选三个位置输入3个号码为一注，所选位置号码符合开奖号码的组三或组六均为中奖。
     *          投注方案：位置选择百、十、个位，所选号码345，
     *          开奖号码：**345，即为中奖。
     * @access public
     */
    public function bet_rx_rx3_hhzx(& $bet = []) /* {{{ */
    {
        // 取球号
        return $this->bet_rx_rx3_z6($bet);
    } /* }}} */
    
    /**
     * @brief 任选_任四_复试
     *          从万、千、百、十、个中至少4个位置各选一个或多个号码，将各个位置的号码进行组合，所选位置号码与开奖位置号码相同则中奖。
     *          万位买0，千位买1，百位买2，十位买3，个位买4，
     *          开奖号码01234，则中奖。
     * @access public
     */
    public function bet_rx_rx4_fs(& $bet = []) /* {{{ */
    {
        // 取球号
        $balls = get_balls($bet['contents'], $this->config_balls['base']);
        // 检测球
        if ($balls == false || count($balls) != 5) {
            return false;
        }
        // 组合下注量
        $c = RxBetCount($balls,4);
    
        $price_sum = round($bet['price'] * $bet['counts'], 3);
        return ($c == $bet['counts'] && $price_sum == $bet['price_sum']) ? true : false;
    } /* }}} */
    
    /**
     * @brief 任选_任四_单试
     *          手动输入一注或者多注的四个号码和至少四个位置，如果选中的号码与位置和开奖号码对应则中奖
     *          输入号码0123选择万、千、百、十位置
     *          如开奖号码位0123*； 则中奖
     * @access public
     */
    public function bet_rx_rx4_ds(& $bet = []) /* {{{ */
    {
        // 取球号
        return $this->bet_rx_rx4_fs($bet);
    } /* }}} */
    
    /**
     * @brief 跨度_跨度_前三跨度
     *          玩法：从0-9任选一个号码组成一注，所选号码与开奖号前三位的最大最小数字的差值相等，即中奖。
     *          投注方案：选择5, 等于开奖号前三位2,5,7的最大数7与最小数字2的差值，即为中奖。
     * @access public
     */
    public function bet_kd_kdq3(& $bet = []) /* {{{ */
    {
        // 取球号
        $balls = get_balls($bet['contents'], $this->config_balls['base']);
        // 检测球
        if ($balls == false || count($balls) != 1) {
            return false;
        }
        // 组合下注量
        $c = 0;
        foreach ($balls[0] as $value){
            $c += $this->config_balls['kd_q3_tab'][$value];
        }
        $price_sum = round($bet['price'] * $bet['counts'], 3);
        return ($c == $bet['counts'] && $price_sum == $bet['price_sum']) ? true : false;
    } /* }}} */
    
    /**
     * @brief 跨度_跨度_中三跨度
     *          玩法：从0-9任选一个号码组成一注，所选号码与开奖号中三位的最大最小数字的差值相等，即中奖。
     *          投注方案：选择5, 等于开奖号中三位2,5,7的最大数7与最小数字2的差值，即为中奖。
     * @access public
     */
    public function bet_kd_kdz3(& $bet = []) /* {{{ */
    {
        // 取球号
        return $this->bet_kd_kdq3($bet);
    } /* }}} */
    
    /**
     * @brief 跨度_跨度_后三跨度
     *          玩法：从0-9任选一个号码组成一注，所选号码与开奖号后三位的最大最小数字的差值相等，即中奖。
     *          投注方案：选择5, 等于开奖号后三位2,5,7的最大数7与最小数字2的差值，即为中奖。
     * @access public
     * @param
     * @return
     */
    public function bet_kd_kdh3(& $bet = []) /* {{{ */
    {
        // 取球号
        return $this->bet_kd_kdq3($bet);
    } /* }}} */
    
    /**
     * @brief 跨度_跨度_前二跨度
     *          玩法：从0-9任选一个号码组成一注，所选号码与开奖号前二位的最大最小数字的差值相等，即中奖。
     *          投注方案：选择5, 等于开奖号前二位2,7的最大数7与最小数字2的差值，即为中奖。
     * @access public
     */
    public function bet_kd_kdq2(& $bet = []) /* {{{ */
    {
        // 取球号
        $balls = get_balls($bet['contents'], $this->config_balls['base']);
        // 检测球
        if ($balls == false || count($balls) != 1) {
            return false;
        }
        // 组合下注量
        $c = 0;
        foreach ($balls[0] as $value){
            $c += $this->config_balls['kd_q2_tab'][$value];
        }
        $price_sum = round($bet['price'] * $bet['counts'], 3);
        return ($c == $bet['counts'] && $price_sum == $bet['price_sum']) ? true : false;
    } /* }}} */
    
    /**
     * @brief 跨度_跨度_后二跨度
     *          玩法：从0-9任选一个号码组成一注，所选号码与开奖号后二位的最大最小数字的差值相等，即中奖。
     *          投注方案：选择5, 等于开奖号后二位2,7的最大数7与最小数字2的差值，即为中奖。
     * @access public
     */
    public function bet_kd_kdh2(& $bet = []) /* {{{ */
    {
        // 取球号
        return $this->bet_kd_kdq2($bet);
    } /* }}} */
    
    /**
     * @brief 趣味_特殊_一帆风顺
     *          从0-9中任意选择1个号码组成一注，只要开奖号码的万位、千位、百位、十位、个位中包含所选号码，即为中奖。
     *          投注方案：8；开奖号码：至少出现1个8，如：0 0 4 3 8，即中一帆风顺。
     * @access public
     */
    public function bet_qw_ts_qw1(& $bet = []) /* {{{ */
    {
        // 取球号
        $balls = get_balls($bet['contents'], $this->config_balls['base']);
        // 检测球
        if ($balls == false || count($balls) != 1) {
            return false;
        }
        // 组合下注量
        $c = count($balls[0]);
        $price_sum = round($bet['price'] * $bet['counts'], 3);
        return ($c == $bet['counts'] && $price_sum == $bet['price_sum']) ? true : false;
    } /* }}} */
    
    /**
     * @brief 趣味_特殊_好事成双
     *          从0-9中任意选择1个号码组成一注，只要所选号码在开奖号码的万位、千位、百位、十位、个位中出现2次，即为中奖。
     *          投注方案：8；开奖号码：至少出现2个8，如：0 0 4 8 8，即中好事成双。
     * @access public
     */
    public function bet_qw_ts_qw2(& $bet = []) /* {{{ */
    {
        return $this->bet_qw_ts_qw1($bet);
    } /* }}} */
    
    /**
     * @brief 趣味_特殊_三星报喜
     *          从0-9中任意选择1个号码组成一注，只要所选号码在开奖号码的万位、千位、百位、十位、个位中出现3次，即为中奖。
     *          投注方案：8；开奖号码：至少出现3个8，如：0 8 4 8 8，即中三星报喜。
     * @access public
     */
    public function bet_qw_ts_qw3(& $bet = []) /* {{{ */
    {
        return $this->bet_qw_ts_qw1($bet);
    } /* }}} */
    
    /**
     * @brief 趣味_特殊_四季发财
     *          从0-9中任意选择1个号码组成一注，只要所选号码在开奖号码的万位、千位、百位、十位、个位中出现4次，即为中奖。
     *          投注方案：8；开奖号码：至少出现4个8，如：0 8 8 8 8，即中四季发财。
     * @access public
     */
    public function bet_qw_ts_qw4(& $bet = []) /* {{{ */
    {
        return $this->bet_qw_ts_qw1($bet);
    } /* }}} */
    
    /**
     * @brief 龙虎_龙虎_万千
     *          根据万位、千位号码数值比大小，万位号码大于千位号码为龙，万位号码小于千位号码为虎，号码相同则为和。所选形态与开奖号码形态一致，即为中奖。
     *          投注方案：龙；开奖号码 8 6 2 4 0,即中奖。
     *          投注方案：虎；开奖号码 6 8 2 4 0,即中奖。
     *          投注方案：和；开奖号码 6 6 2 4 0,即中奖。
     * @access public
     */
    public function bet_lh_wq(& $bet = []) /* {{{ */
    {
        // 取球号
        $balls = get_balls($bet['contents'], $this->config_balls['lh']);
        // 检测球
        if ($balls == false || count($balls) != 1) {
            return false;
        }
        // 组合下注量
        $c = 1;
        $price_sum = round($bet['price'] * $bet['counts'], 3);
        return ($c == $bet['counts'] && $price_sum == $bet['price_sum']) ? true : false;
    } /* }}} */
    
    /**
     * @brief 龙虎_龙虎_万百
     *          根据万位、百位号码数值比大小，万位号码大于百位号码为龙，万位号码小于百位号码为虎，号码相同则为和。所选形态与开奖号码形态一致，即为中奖。
     *          投注方案：龙；开奖号码 8 2 6 4 0,即中奖。
     *          投注方案：虎；开奖号码 6 2 8 4 0,即中奖。
     *          投注方案：和；开奖号码 6 2 6 4 0,即中奖。
     * @access public
     */
    public function bet_lh_wb(& $bet = []) /* {{{ */
    {
        return $this->bet_lh_wq($bet);
    } /* }}} */
    
    /**
     * @brief 龙虎_龙虎_万十
     *          根据万位、十位号码数值比大小，万位号码大于十位号码为龙，万位号码小于十位号码为虎，号码相同则为和。所选形态与开奖号码形态一致，即为中奖。
     *          投注方案：龙；开奖号码 8 2 4 6 0,即中奖。
     *          投注方案：虎；开奖号码 6 2 4 8 0,即中奖。
     *          投注方案：和；开奖号码 6 2 4 6 0,即中奖。
     * @access public
     */
    public function bet_lh_ws(& $bet = []) /* {{{ */
    {
        return $this->bet_lh_wq($bet);
    } /* }}} */
    
    /**
     * @brief 龙虎_龙虎_万个
     *          根据万位、个位号码数值比大小，万位号码大于个位号码为龙，万位号码小于个位号码为虎，号码相同则为和。所选形态与开奖号码形态一致，即为中奖。
     *          投注方案：龙；开奖号码 8 2 4 0 6,即中奖。
     *          投注方案：虎；开奖号码 6 2 4 0 8,即中奖。
     *          投注方案：和；开奖号码 6 2 4 0 6,即中奖。
     * @access public
     */
    public function bet_lh_wg(& $bet = []) /* {{{ */
    {
        return $this->bet_lh_wq($bet);
    } /* }}} */
    
    /**
     * @brief 龙虎_龙虎_千百
     *          根据千位、百位号码数值比大小，千位号码大于百位号码为龙，千位号码小于百位号码为虎，号码相同则为和。所选形态与开奖号码形态一致，即为中奖。
     *          投注方案：龙；开奖号码 2 8 6 4 0,即中奖。
     *          投注方案：虎；开奖号码 2 6 8 4 0,即中奖。
     *          投注方案：和；开奖号码 2 6 6 4 0,即中奖。
     * @access public
     */
    public function bet_lh_qb(& $bet = []) /* {{{ */
    {
        return $this->bet_lh_wq($bet);
    } /* }}} */
    
    /**
     * @brief 龙虎_龙虎_千十
     *          根据千位、十位号码数值比大小，千位号码大于十位号码为龙，千位号码小于十位号码为虎，号码相同则为和。所选形态与开奖号码形态一致，即为中奖
     *          投注方案：龙；开奖号码 2 8 4 6 0,即中奖。
     *          投注方案：虎；开奖号码 2 6 4 8 0,即中奖。
     *          投注方案：和；开奖号码 2 6 4 6 0,即中奖。
     * @access public
     */
    public function bet_lh_qs(& $bet = []) /* {{{ */
    {
        return $this->bet_lh_wq($bet);
    } /* }}} */
    
    /**
     * @brief 龙虎_龙虎_千个
     *          根据千位、个位号码数值比大小，千位号码大于个位号码为龙，千位号码小于个位号码为虎，号码相同则为和。所选形态与开奖号码形态一致，即为中奖。
     *          投注方案：龙；开奖号码 2 8 4 0 6,即中奖。
     *          投注方案：虎；开奖号码 2 6 4 0 8,即中奖。
     *          投注方案：和；开奖号码 2 6 4 0 6,即中奖。
     * @access public
     */
    public function bet_lh_qg(& $bet = []) /* {{{ */
    {
        return $this->bet_lh_wq($bet);
    } /* }}} */
    
    /**
     * @brief 龙虎_龙虎_百十
     *          根据百位、十位号码数值比大小，百位号码大于十位号码为龙，百位号码小于十位号码为虎，号码相同则为和。所选形态与开奖号码形态一致，即为中奖。
     *          投注方案：龙；开奖号码 2 4 8 6 0,即中奖。
     *          投注方案：虎；开奖号码 2 4 6 8 0,即中奖。
     *          投注方案：和；开奖号码 2 4 6 6 0,即中奖。
     * @access public
     */
    public function bet_lh_bs(& $bet = []) /* {{{ */
    {
        return $this->bet_lh_wq($bet);
    } /* }}} */
    
    /**
     * @brief 龙虎_龙虎_百个
     *          根据百位、个位号码数值比大小，百位号码大于个位号码为龙，百位号码小于个位号码为虎，号码相同则为和。所选形态与开奖号码形态一致，即为中奖。
     *          投注方案：龙；开奖号码 2 4 8 0 6,即中奖。
     *          投注方案：虎；开奖号码 2 4 6 0 8,即中奖。
     *          投注方案：和；开奖号码 2 4 6 0 6,即中奖。
     * @access public
     */
    public function bet_lh_bg(& $bet = []) /* {{{ */
    {
        return $this->bet_lh_wq($bet);
    } /* }}} */
    
    /**
     * @brief 龙虎_龙虎_十个
     *          根据十位、个位号码数值比大小，十位号码大于个位号码为龙，十位号码小于个位号码为虎，号码相同则为和。所选形态与开奖号码形态一致，即为中奖。
     *          投注方案：龙；开奖号码 2 4 0 8 6,即中奖。
     *          投注方案：虎；开奖号码 2 4 0 6 8,即中奖。
     *          投注方案：和；开奖号码 2 4 0 6 6,即中奖。
     * @access public
     */
    public function bet_lh_sg(& $bet = []) /* {{{ */
    {
        return $this->bet_lh_wq($bet);
    } /* }}} */
}

/* end file */
