<?php
/**
 * @file games_yb.php
 * @brief 一般低频采种(福彩3D, 排列3)下注库
 *
 * Copyright (C) 2017 GC.COM
 * All rights reserved.
 *
 * @package libraries
 *
 * $Id$
 */

include_once(dirname(__FILE__).'/games.php');

class games_yb
{
    public $config_balls = [
        /* 基本球 */
        'base' => [0, 1, 2, 3, 4, 5, 6, 7, 8, 9],
        '3m_hz' => [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15,
            16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27],
        '2m_hz' => [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18]
    ];
    
    /**
     * @brief 三码_直选_直选复式
     *          从百位、十位、个位中选择一个3位数号码组成一注，所选号码与开奖号码相同，且顺序一致，即为中奖。
     *          投注方案：789
     *          开奖号码：789 即中三码直选
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
     * @param array $bet
     * @param bool $settlement
     * @return bool
     */
    public function bet_3m_zhx_fs(& $bet = array(), $settlement = false)
    {
        // 取球号
        $balls = get_balls($bet['contents'], $this->config_balls['base']);
        // 检测球
        if ($balls == false) {
            return false;
        }
        // 组合下注量
        $c = CC($balls, 3, true);
        $price_sum = round($bet['price'] * $bet['counts'], 3);
        return ($c['total'] == $bet['counts'] && $price_sum == $bet['price_sum']) ? true : false;
    }
    
    /**
     * @brief 三码_直选_直选单式
     *          手动输入一个3位数号码组成一注，所选号码与开奖号码的百位、十位、个位相同，且顺序一致，即为中奖。
     *          投注方案：789
     *          开奖号码：789 即中三码直选
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
     * @param array $bet
     * @param bool $settlement
     * @return bool
     */
    public function bet_3m_zhx_ds(& $bet = array(), $settlement = false)
    {
        return $this->bet_3m_zhx_fs($bet, $settlement);
    }
    
    /**
     * @brief 三码_直选_直选和值
     *          所选数值等于开奖号码的百位、十位、个位三个数字相加之和，即为中奖。
     *          投注方案：1
     *          开奖号码：001 010 100 即中三码直选和值。
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
     * @param array $bet
     * @param bool $settlement
     * @return bool
     */
    public function bet_3m_zhx_zxhz(& $bet = array(), $settlement = false)
    {
        // 取球号
        $balls = get_balls($bet['contents'], $this->config_balls['3m_hz']);
        // 检测球
        if ($balls == false) {
            return false;
        }
        $check_balls = [
            $this->config_balls['base'],
            $this->config_balls['base'],
            $this->config_balls['base'],
        ];
        $c = CC($check_balls, 3, true, $balls[0]);
        $price_sum = round($bet['price'] * $bet['counts'], 3);
        return ($c['total'] == $bet['counts'] && $price_sum == $bet['price_sum']) ? true : false;
    }
    
    /**
     * @brief 三码_组选_组三
     *          从0-9中选择2个数字组成两注，所选号码与开奖号码的百、十、个位相同且有1个号码重复，顺序不限，即为中奖。
     *          投注方案：58
     *          开奖号码：585 858等（顺序不限）即中组选三。
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
     * @param array $bet
     * @param bool $settlement
     * @return bool
     */
    public function bet_3m_zx_z3(& $bet = array(), $settlement = false)
    {
        // 取球号
        $balls = get_balls($bet['contents'], $this->config_balls['base']);
        // 检测球
        if ($balls == false) {
            return false;
        }
        $n = count($balls[0]);
        $c = C($n, 2) * 2;
        $price_sum = round($bet['price'] * $bet['counts'], 3);
        return ($c == $bet['counts'] && $price_sum == $bet['price_sum']) ? true : false;
    }
    
    /**
     * @brief 三码_组选_组六
     *          从0-9中任意选择3个号码组成一注，所选号码与开奖号码的百、十、个位相同，顺序不限，即为中奖。
     *          投注方案：2 5 8
     *          开奖号码：852或582（顺序不限），即中组选六
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
     * @param array $bet
     * @param bool $settlement
     * @return bool
     */
    public function bet_3m_zx_z6(& $bet = array(), $settlement = false)
    {
        // 取球号
        $balls = get_balls($bet['contents'], $this->config_balls['base']);
        // 检测球
        if ($balls == false) {
            return false;
        }
        $n = count($balls[0]);
        $c = C($n, 3);
        $price_sum = round($bet['price'] * $bet['counts'], 3);
        return ($c == $bet['counts'] && $price_sum == $bet['price_sum']) ? true : false;
    }
    
    /**
     * @brief 三码_组选_混合组选
     *          键盘手动输入购买号码，3个数字为一注，开奖号码符合组三或组六均为中奖。
     *          投注方案：001和123，开奖号码010（顺序不限），或者312（顺序不限），即中混合组选。
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
     * @param array $bet
     * @param bool $settlement
     * @return bool
     */
    public function bet_3m_zx_hhzx(& $bet = array(), $settlement = false)
    {
        return $this->bet_3m_zx_z6($bet, $settlement);
    }
    
    /**
     * @brief 二码_后二直选_复式
     *          从十位、个位中选择一个2位数号码组成一注，所选号码与开奖号码的前二位相同，且顺序一致，即为中奖。
     *          投注方案：5 8
     *          开奖号码：后二位5 8，即中后二直选。
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
     * @param array $bet
     * @param bool $settlement
     * @return bool
     */
    public function bet_2m_hezhx_fs(& $bet = array(), $settlement = false)
    {
        // 取球号
        $balls = get_balls($bet['contents'], $this->config_balls['base']);
        // 检测球
        if ($balls == false) {
            return false;
        }
        $c = CC($balls, 2, true);
        $price_sum = round($bet['price'] * $bet['counts'], 3);
        return ($c['total'] == $bet['counts'] && $price_sum == $bet['price_sum']) ? true : false;
    }
    
    /**
     * @brief 二码_后二直选_单式
     *          手动输入一个2位数号码组成一注，所选号码的十位、个位与开奖号码相同，且顺序一致，即为中奖
     *          投注方案：58开奖号码：后二58，即中后二码直选。
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
     * @param array $bet
     * @param bool $settlement
     * @return bool
     */
    public function bet_2m_hezhx_ds(& $bet = array(), $settlement = false)
    {
        return $this->bet_2m_hezhx_fs($bet, $settlement);
    }
    
    /**
     * @brief 二码_后二直选_直选和值
     *          所选数值等于开奖号码的十位、个位二个数字相加之和，即为中奖
     *          和值1开奖号码：后二位01,10，即中后二直选和值。
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
     * @param array $bet
     * @param bool $settlement
     * @return bool
     */
    public function bet_2m_hezhx_zxhz(& $bet = array(), $settlement = false)
    {
        // 取球号
        $balls = get_balls($bet['contents'], $this->config_balls['2m_hz']);
        // 检测球
        if ($balls == false) {
            return false;
        }
        $check_balls = [
            $this->config_balls['base'],
            $this->config_balls['base'],
        ];
        $c = CC($check_balls, 2, true, $balls[0]);
        $price_sum = round($bet['price'] * $bet['counts'], 3);
        return ($c['total'] == $bet['counts'] && $price_sum == $bet['price_sum']) ? true : false;
    }
    
    /**
     * @brief 二码_后二组选_复式
     *          从0-9中选2个号码组成一注，所选号码与开奖号码的十位、个位相同，（不含对子）顺序不限，即为中奖。
     *          投注方案：5 8
     *          开奖号码：后二位5 8或8 5（顺序不限，不含对子），即中后二组选。
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
     * @param array $bet
     * @param bool $settlement
     * @return bool
     */
    public function bet_2m_hezx_fs(& $bet = array(), $settlement = false)
    {
        // 取球号
        $balls = get_balls($bet['contents'], $this->config_balls['base']);
        // 检测球
        if ($balls == false) {
            return false;
        }
        $n = count($balls[0]);
        $c = C($n, 2);
        $price_sum = round($bet['price'] * $bet['counts'], 3);
        return ($c == $bet['counts'] && $price_sum == $bet['price_sum']) ? true : false;
    }
    
    /**
     * @brief 二码_后二组选_单式
     *          手动输入一个2位数号码组成一注，所选号码的十位、个位与开奖号码相同，顺序不限，即为中奖。
     *          投注方案58，开奖号码58,85（顺序不限，不含对子），即中后二组选。
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
     * @param array $bet
     * @param bool $settlement
     * @return bool
     */
    public function bet_2m_hezx_ds(& $bet = array(), $settlement = false)
    {
        return $this->bet_2m_hezx_fs($bet, $settlement);
    }
    
    /**
     * @brief 二码_前二直选_复式
     *          从百位、十位中选择一个2位数号码组成一注，所选号码与开奖号码的前2位相同，且顺序一致，即为中奖。
     *          投注方案：5 8
     *          开奖号码：前二位5 8即中前二直选。
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
     * @param array $bet
     * @param bool $settlement
     * @return bool
     */
    public function bet_2m_qezhx_fs(& $bet = array(), $settlement = false)
    {
        // 取球号
        $balls = get_balls($bet['contents'], $this->config_balls['base']);
        // 检测球
        if ($balls == false) {
            return false;
        }
        $c = CC($balls, 2, true);
        $price_sum = round($bet['price'] * $bet['counts'], 3);
        return ($c['total'] == $bet['counts'] && $price_sum == $bet['price_sum']) ? true : false;
    }
    
    /**
     * @brief 二码_前二直选_单式
     *          从百位、十位中选择一个2位数号码组成一注，所选号码与开奖号码的前2位相同，且顺序一致，即为中奖。
     *          投注方案：5 8
     *          开奖号码：前二位5 8即中前二直选。
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
     * @param array $bet
     * @param bool $settlement
     * @return bool
     */
    public function bet_2m_qezhx_ds(& $bet = array(), $settlement = false)
    {
        return $this->bet_2m_qezhx_fs($bet, $settlement);
    }
    
    /**
     * @brief 二码_前二直选_直选和值
     *          所选数值等于开奖号码的百位、十位二个数字相加之和，即为中奖。
     *          和值1 开奖号码：前二位01，10，即中前二直选和值。
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
     * @param array $bet
     * @param bool $settlement
     * @return bool
     */
    public function bet_2m_qezhx_zxhz(& $bet = array(), $settlement = false)
    {
        // 取球号
        $balls = get_balls($bet['contents'], $this->config_balls['2m_hz']);
        // 检测球
        if ($balls == false) {
            return false;
        }
        $check_balls = [
            $this->config_balls['base'],
            $this->config_balls['base'],
        ];
        $c = CC($check_balls, 2, true, $balls[0]);
        $price_sum = round($bet['price'] * $bet['counts'], 3);
        return ($c['total'] == $bet['counts'] && $price_sum == $bet['price_sum']) ? true : false;
    }
    
    /**
     * @brief 二码_前二组选_复式
     *          从0-9中选2个号码组成一注，所选号码与开奖号码的百位、十位相同，顺序不限，即为中奖。
     *          投注方案：5 8
     *          开奖号码：前二位5 8或8 5（顺序不限，不含对子），即中前二组选。
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
     * @param array $bet
     * @param bool $settlement
     * @return bool
     */
    public function bet_2m_qezx_fs(& $bet = array(), $settlement = false)
    {
        // 取球号
        $balls = get_balls($bet['contents'], $this->config_balls['base']);
        // 检测球
        if ($balls == false) {
            return false;
        }
        $n = count($balls[0]);
        $c = C($n, 2);
        $price_sum = round($bet['price'] * $bet['counts'], 3);
        return ($c == $bet['counts'] && $price_sum == $bet['price_sum']) ? true : false;
    }
    
    /**
     * @brief 二码_前二组选_单式
     *          手动输入一个2位数号码组成一注，所选号码的百位、十位与开奖号码相同，顺序不限，即为中奖
     *          58 开奖号码：前二58,85（顺序不限，不含对子），即中前二组选。
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
    public function bet_2m_qezx_ds(& $bet = array(), $settlement = false)
    {
        return $this->bet_2m_qezx_fs($bet, $settlement);
    }
    
    /**
     * @brief 定位胆_定位胆
     *          从百位、十位、个位任意位置上至少选择1个以上号码，所选号码与相同位置上的开奖号码一致，即为中奖。
     *          投注方案：1
     *          开奖号码：百位1，即中定位胆百位。
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
     * @param array $bet
     * @param bool $settlement
     * @return bool
     */
    public function bet_dwd_dwd(& $bet = array(), $settlement = false)
    {
        // 取球号
        $balls = get_balls($bet['contents'], $this->config_balls['base']);
        // 检测球
        if ($balls == false) {
            return false;
        }
        // 组合数
        $c = 0;
        foreach ($balls as $v) {
            if (count($v) == 1 && $v[0] == "") {
                continue;
            };
            $c += count($v);
        }
        $price_sum = round($bet['price'] * $bet['counts'], 3);
        return ($c == $bet['counts'] && $price_sum == $bet['price_sum']) ? true : false;
    }
    
    /**
     * @brief 不定位_不定位
     *          从0-9中选择1个号码，每注由1个号码组成，只要开奖号码的百位、十位、个位中包含所选号码，即为中奖。
     *          投注方案：1
     *          开奖号码：至少出现1个1，即中一码不定位。
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
     * @param array $bet
     * @param bool $settlement
     * @return bool
     */
    public function bet_bdw_bdw(& $bet = array(), $settlement = false)
    {
        // 取球号
        $balls = get_balls($bet['contents'], $this->config_balls['base']);
        // 检测球
        if ($balls == false) {
            return false;
        }
        // 组合数
        $c = count($balls[0]);
        $price_sum = round($bet['price'] * $bet['counts'], 3);
        return ($c == $bet['counts'] && $price_sum == $bet['price_sum']) ? true : false;
    }
}

/* end file */
