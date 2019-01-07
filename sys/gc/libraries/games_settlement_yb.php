<?php
/**
 * @file games_settlement_yb.php
 * @brief 一般低频采种(福彩3D, 排列3)结算库
 *      NOTE: 如果玩法为和局，status 返回 STATUS_HE: $ret['status'] = STATUS_HE;
 *
 * Copyright (C) 2017 GC.COM
 * All rights reserved.
 *
 * @package libraries
 *
 * $Id$
 */

include_once(dirname(__FILE__) . '/games.php');

class games_settlement_yb
{
    public $config_balls = [
        /* 基本球 */
        'base' => [0, 1, 2, 3, 4, 5, 6, 7, 8, 9],
    ];
    
    /**
     * @brief 根据彩种当期的开奖号计算出各种玩法的开奖值
     *      如：某 ssc 20170404043当期开奖: 7, 0, 3, 6, 2;
     *          则计算出前三和值开奖值: 10, 中三和值: 9, 后三和值11.
     * @access public/protected
     * @param array $wins_balls
     * @return array
     */
    public function wins_balls(& $wins_balls = array()) /* {{{ */
    {
        //$wins_balls['base'] = ['1', '2', '3'];
        if (count($wins_balls['base']) != 3 ||
                !in_array($wins_balls['base'][0], $this->config_balls['base']) ||
                !in_array($wins_balls['base'][1], $this->config_balls['base']) ||
                !in_array($wins_balls['base'][2], $this->config_balls['base'])
                ) {
            $msg = date('[Y-m-d H:i:s]').'[yb] lottery error:'.implode(',', $wins_balls['base'])."\n";
            error_log($msg, 3, 'api/logs/wins_balls_error_'.date('Ym').'.log');
            exit($msg);
        }
        $wins_balls['he'] = $wins_balls['base'][0] + $wins_balls['base'][1] + $wins_balls['base'][2];
        $wins_balls['h2'] = $wins_balls['base'][1] + $wins_balls['base'][2];
        $wins_balls['q2'] = $wins_balls['base'][0] + $wins_balls['base'][1];
        /* Other... */
    
        return $wins_balls;
    } /* }}} */
    
    /**
     * @brief 三码_直选_直选复式
     *          从百位、十位、个位中选择一个3位数号码组成一注，所选号码与开奖号码相同，且顺序一致，即为中奖。
     *          投注方案：789
     *          开奖号码：789 即中三码直选
     *          $bet = "[\"1570329144415671\",1,\"3|2,4,5||6\",2,3,6,\"6.12\",13]";
     *          $bet = "[\"1570329155205234\",1,\"3,4,5\",2,3,6,\"6.12\",13]";
     * @access public
     * @link http://www.gc360.com/orders/bet/15/1908
     * @param array $bet
     * @param array $lottery
     * @return
     *      中奖，和局，未中奖
     *      ret = array('win_contents'=>array(array(1,3)), 'win_counts'=>1, 'price_sum'=>3.9, 'status'=>STATUS_WIN);
     *      ret = array('win_contents'=>array(), 'win_counts'=>0, 'price_sum'=>4, 'status'=>STATUS_HE);
     *      ret = null;
     */
    public function settlement_3m_zhx_fs(& $bet = array(), & $lottery = array())
    {
        $balls = get_balls($bet[2]);
        $ret = null;
        // 三个同时中奖才能中,而且只能中一注
        if (in_array($lottery['base'][0], $balls[0]) && in_array($lottery['base'][1], $balls[1]) && in_array($lottery['base'][2], $balls[2])) {
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
     * @brief 三码_直选_直选单式
     *          手动输入一个3位数号码组成一注，所选号码与开奖号码的百位、十位、个位相同，且顺序一致，即为中奖。
     *          投注方案：789
     *          开奖号码：789 即中三码直选
     *          $bet = "[\"1570329144415671\",1,\"3|2,4,5||6\",2,3,6,\"6.12\",13]";
     *          $bet = "[\"1570329155205234\",1,\"3,4,5\",2,3,6,\"6.12\",13]";
     * @access public
     * @link http://www.gc360.com/orders/bet/15/1908
     * @param array $bet
     * @param array $lottery
     * @return
     *      中奖，和局，未中奖
     *      ret = array('win_contents'=>array(array(1,3)), 'win_counts'=>1, 'price_sum'=>3.9, 'status'=>STATUS_WIN);
     *      ret = array('win_contents'=>array(), 'win_counts'=>0, 'price_sum'=>4, 'status'=>STATUS_HE);
     *      ret = null;
     */
    public function settlement_3m_zhx_ds(& $bet = array(), & $lottery = array())
    {
        return $this->settlement_3m_zhx_fs($bet, $lottery);
    }
    
    /**
     * @brief 三码_直选_直选和值
     *          所选数值等于开奖号码的百位、十位、个位三个数字相加之和，即为中奖。
     *          投注方案：1
     *          开奖号码：001 010 100 即中三码直选和值。
     *          $bet = "[\"1570329144415671\",1,\"3|2,4,5||6\",2,3,6,\"6.12\",13]";
     *          $bet = "[\"1570329155205234\",1,\"3,4,5\",2,3,6,\"6.12\",13]";
     * @access public
     * @link http://www.gc360.com/orders/bet/15/1908
     * @param array $bet
     * @param array $lottery
     * @return
     *      中奖，和局，未中奖
     *      ret = array('win_contents'=>array(array(1,3)), 'win_counts'=>1, 'price_sum'=>3.9, 'status'=>STATUS_WIN);
     *      ret = array('win_contents'=>array(), 'win_counts'=>0, 'price_sum'=>4, 'status'=>STATUS_HE);
     *      ret = null;
     */
    public function settlement_3m_zhx_zxhz(& $bet = array(), & $lottery = array())
    {
        // 只能中一注
        $balls = get_balls($bet[2]);
        $ret = null;
        if (in_array($lottery['he'], $balls[0])) {
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
     * @brief 三码_组选_组三
     *          从0-9中选择2个数字组成两注，所选号码与开奖号码的百、十、个位相同且有1个号码重复，顺序不限，即为中奖。
     *          投注方案：58
     *          开奖号码：585 858等（顺序不限）即中组选三。
     *          $bet = "[\"1570329144415671\",1,\"3|2,4,5||6\",2,3,6,\"6.12\",13]";
     *          $bet = "[\"1570329155205234\",1,\"3,4,5\",2,3,6,\"6.12\",13]";
     * @access public
     * @link http://www.gc360.com/orders/bet/15/1908
     * @param array $bet
     * @param array $lottery
     * @return
     *      中奖，和局，未中奖
     *      ret = array('win_contents'=>array(array(1,3)), 'win_counts'=>1, 'price_sum'=>3.9, 'status'=>STATUS_WIN);
     *      ret = array('win_contents'=>array(), 'win_counts'=>0, 'price_sum'=>4, 'status'=>STATUS_HE);
     *      ret = null;
     */
    public function settlement_3m_zx_z3(& $bet = array(), & $lottery = array())
    {
        $balls = get_balls($bet[2]);
        $ret = null;
        // 中一注，要么不中
        if (count(array_unique($lottery['base'])) == 2) {
            if (in_array($lottery['base'][0], $balls[0]) && in_array($lottery['base'][1], $balls[0]) && in_array($lottery['base'][2], $balls[0])) {
                $ret['win_contents'][] = array_values(array_unique($lottery['base']));
                //$ret['win_contents'][] = array_reverse(array_unique($lottery['base']));
            }
        }
    
        if (!empty($ret['win_contents'])) {
            $ret['win_counts'] = count($ret['win_contents']);
            // 计算赔率
            $rate = explode(',', $bet[6]);
            $rate = isset($rate[0]) ? $rate[0] : '';
            $ret['price_sum'] = $bet[3] * $rate * $ret['win_counts'];
            $ret['status'] = STATUS_WIN;
        }
        return $ret;
    }
    
    /**
     * @brief 三码_组选_组六
     *          从0-9中任意选择3个号码组成一注，所选号码与开奖号码的百、十、个位相同，顺序不限，即为中奖。
     *          投注方案：2 5 8
     *          开奖号码：852或582（顺序不限），即中组选六
     *          $bet = "[\"1570329144415671\",1,\"3|2,4,5||6\",2,3,6,\"6.12\",13]";
     *          $bet = "[\"1570329155205234\",1,\"3,4,5\",2,3,6,\"6.12\",13]";
     * @access public
     * @link http://www.gc360.com/orders/bet/15/1908
     * @param array $bet
     * @param array $lottery
     * @return
     *      中奖，和局，未中奖
     *      ret = array('win_contents'=>array(array(1,3)), 'win_counts'=>1, 'price_sum'=>3.9, 'status'=>STATUS_WIN);
     *      ret = array('win_contents'=>array(), 'win_counts'=>0, 'price_sum'=>4, 'status'=>STATUS_HE);
     *      ret = null;
     */
    public function settlement_3m_zx_z6(& $bet = array(), & $lottery = array())
    {
        $balls = get_balls($bet[2]);
        $ret = null;
        if (count(array_unique($lottery['base'])) == 3) {
            if (in_array($lottery['base'][0], $balls[0]) && in_array($lottery['base'][1], $balls[0]) && in_array($lottery['base'][2], $balls[0])) {
                $ret['win_contents'][] = $lottery['base'];
            }
        }
    
        if (!empty($ret['win_contents'])) {
            $ret['win_counts'] = count($ret['win_contents']);
            // 计算赔率
            $rate = explode(',', $bet[6]);
            $rate = isset($rate[1]) ? $rate[1] : $rate[0];
            $ret['price_sum'] = $bet[3] * $rate * $ret['win_counts'];
            $ret['status'] = STATUS_WIN;
        }
        return $ret;
    }
    
    /**
     * @brief 三码_组选_混合组选
     *          键盘手动输入购买号码，3个数字为一注，开奖号码符合组三或组六均为中奖。
     *          投注方案：001和123，开奖号码010（顺序不限），或者312（顺序不限），即中混合组选。
     *          $bet = "[\"1570329144415671\",1,\"3|2,4,5||6\",2,3,6,\"6.12\",13]";
     *          $bet = "[\"1570329155205234\",1,\"3,4,5\",2,3,6,\"6.12\",13]";
     * @access public
     * @link http://www.gc360.com/orders/bet/15/1908
     * @param array $bet
     * @param array $lottery
     * @return
     *      中奖，和局，未中奖
     *      ret = array('win_contents'=>array(array(1,3)), 'win_counts'=>1, 'price_sum'=>3.9, 'status'=>STATUS_WIN);
     *      ret = array('win_contents'=>array(), 'win_counts'=>0, 'price_sum'=>4, 'status'=>STATUS_HE);
     *      ret = null;
     */
    public function settlement_3m_zx_hhzx(& $bet = array(), & $lottery = array())
    {
        $r = array_unique($lottery['base']);
        if (count($r) == 2) {
            return $this->settlement_3m_zx_z3($bet, $lottery);
        } elseif (count($r) == 3) {
            return $this->settlement_3m_zx_z6($bet, $lottery);
        }
    }
    
    /**
     * @brief 二码_后二直选_复式
     *          从十位、个位中选择一个2位数号码组成一注，所选号码与开奖号码的后二位相同，且顺序一致，即为中奖。
     *          投注方案：5 8
     *          开奖号码：后二位5 8，即中后二直选。
     *          $bet = "[\"1570329144415671\",1,\"3|2,4,5||6\",2,3,6,\"6.12\",13]";
     *          $bet = "[\"1570329155205234\",1,\"3,4,5\",2,3,6,\"6.12\",13]";
     * @access public
     * @link http://www.gc360.com/orders/bet/15/1908
     * @param array $bet
     * @param array $lottery
     * @return
     *      中奖，和局，未中奖
     *      ret = array('win_contents'=>array(array(1,3)), 'win_counts'=>1, 'price_sum'=>3.9, 'status'=>STATUS_WIN);
     *      ret = array('win_contents'=>array(), 'win_counts'=>0, 'price_sum'=>4, 'status'=>STATUS_HE);
     *      ret = null;
     */
    public function settlement_2m_hezhx_fs(& $bet = array(), & $lottery = array())
    {
        $balls = get_balls($bet[2]);
        $ret = null;
        // 同时中奖才能中,而且只能中一注
        if (in_array($lottery['base'][1], $balls[0]) && in_array($lottery['base'][2], $balls[1])) {
            $ret['win_contents'][] = array($lottery['base'][1], $lottery['base'][2]);
        }
    
        if (!empty($ret['win_contents'])) {
            $ret['win_counts'] = count($ret['win_contents']);
            $ret['price_sum'] = $bet[3] * $bet[6] * $ret['win_counts'];
            $ret['status'] = STATUS_WIN;
        }
        return $ret;
    }
    
    /**
     * @brief 二码_后二直选_单式
     *          手动输入一个2位数号码组成一注，所选号码的十位、个位与开奖号码相同，且顺序一致，即为中奖
     *          投注方案：58开奖号码：后二58，即中后二码直选。
     *          $bet = "[\"1570329144415671\",1,\"3|2,4,5||6\",2,3,6,\"6.12\",13]";
     *          $bet = "[\"1570329155205234\",1,\"3,4,5\",2,3,6,\"6.12\",13]";
     * @access public
     * @link http://www.gc360.com/orders/bet/15/1908
     * @param array $bet
     * @param array $lottery
     * @return
     *      中奖，和局，未中奖
     *      ret = array('win_contents'=>array(array(1,3)), 'win_counts'=>1, 'price_sum'=>3.9, 'status'=>STATUS_WIN);
     *      ret = array('win_contents'=>array(), 'win_counts'=>0, 'price_sum'=>4, 'status'=>STATUS_HE);
     *      ret = null;
     */
    public function settlement_2m_hezhx_ds(& $bet = array(), & $lottery = array())
    {
        return $this->settlement_2m_hezhx_fs($bet, $lottery);
    }
    
    /**
     * @brief 二码_后二直选_直选和值
     *          所选数值等于开奖号码的十位、个位二个数字相加之和，即为中奖
     *          和值1开奖号码：后二位01,10，即中后二直选和值。
     *          $bet = "[\"1570329144415671\",1,\"3|2,4,5||6\",2,3,6,\"6.12\",13]";
     *          $bet = "[\"1570329155205234\",1,\"3,4,5\",2,3,6,\"6.12\",13]";
     * @access public
     * @link http://www.gc360.com/orders/bet/15/1908
     * @param array $bet
     * @param array $lottery
     * @return
     *      中奖，和局，未中奖
     *      ret = array('win_contents'=>array(array(1,3)), 'win_counts'=>1, 'price_sum'=>3.9, 'status'=>STATUS_WIN);
     *      ret = array('win_contents'=>array(), 'win_counts'=>0, 'price_sum'=>4, 'status'=>STATUS_HE);
     *      ret = null;
     */
    public function settlement_2m_hezhx_zxhz(& $bet = array(), & $lottery = array())
    {
        $balls = get_balls($bet[2]);
        $ret = null;
        if (in_array($lottery['h2'], $balls[0])) {
            $ret['win_contents'][] = $lottery['h2'];
        }
    
        if (!empty($ret['win_contents'])) {
            $ret['win_counts'] = count($ret['win_contents']);
            $ret['price_sum'] = $bet[3] * $bet[6] * $ret['win_counts'];
            $ret['status'] = STATUS_WIN;
        }
        return $ret;
    }
    
    /**
     * @brief 二码_后二组选_复式
     *          从0-9中选2个号码组成一注，所选号码与开奖号码的十位、个位相同，（不含对子）顺序不限，即为中奖。
     *          投注方案：5 8
     *          开奖号码：后二位5 8或8 5（顺序不限，不含对子），即中后二组选。
     *          $bet = "[\"1570329144415671\",1,\"3|2,4,5||6\",2,3,6,\"6.12\",13]";
     *          $bet = "[\"1570329155205234\",1,\"3,4,5\",2,3,6,\"6.12\",13]";
     * @access public
     * @link http://www.gc360.com/orders/bet/15/1908
     * @param array $bet
     * @param array $lottery
     * @return
     *      中奖，和局，未中奖
     *      ret = array('win_contents'=>array(array(1,3)), 'win_counts'=>1, 'price_sum'=>3.9, 'status'=>STATUS_WIN);
     *      ret = array('win_contents'=>array(), 'win_counts'=>0, 'price_sum'=>4, 'status'=>STATUS_HE);
     *      ret = null;
     */
    public function settlement_2m_hezx_fs(& $bet = array(), & $lottery = array())
    {
        $balls = get_balls($bet[2]);
        $ret = null;
        if ($lottery['base'][1] != $lottery['base'][2]) {
            if (in_array($lottery['base'][1], $balls[0]) && in_array($lottery['base'][2], $balls[0])) {
                $ret['win_contents'][] = array($lottery['base'][1], $lottery['base'][2]);
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
     * @brief 二码_后二组选_单式
     *          手动输入一个2位数号码组成一注，所选号码的十位、个位与开奖号码相同，顺序不限，即为中奖。
     *          投注方案58，开奖号码58,85（顺序不限，不含对子），即中后二组选。
     *          $bet = "[\"1570329144415671\",1,\"3|2,4,5||6\",2,3,6,\"6.12\",13]";
     *          $bet = "[\"1570329155205234\",1,\"3,4,5\",2,3,6,\"6.12\",13]";
     * @access public
     * @link http://www.gc360.com/orders/bet/15/1908
     * @param array $bet
     * @param array $lottery
     * @return
     *      中奖，和局，未中奖
     *      ret = array('win_contents'=>array(array(1,3)), 'win_counts'=>1, 'price_sum'=>3.9, 'status'=>STATUS_WIN);
     *      ret = array('win_contents'=>array(), 'win_counts'=>0, 'price_sum'=>4, 'status'=>STATUS_HE);
     *      ret = null;
     */
    public function settlement_2m_hezx_ds(& $bet = array(), & $lottery = array())
    {
        return $this->settlement_2m_hezx_fs($bet, $lottery);
    }
    
    /**
     * @brief 二码_前二直选_复式
     *          从百位、十位中选择一个2位数号码组成一注，所选号码与开奖号码的前2位相同，且顺序一致，即为中奖。
     *          投注方案：5 8
     *          开奖号码：前二位5 8即中前二直选。
     *          $bet = "[\"1570329144415671\",1,\"3|2,4,5||6\",2,3,6,\"6.12\",13]";
     *          $bet = "[\"1570329155205234\",1,\"3,4,5\",2,3,6,\"6.12\",13]";
     * @access public
     * @link http://www.gc360.com/orders/bet/15/1908
     * @param array $bet
     * @param array $lottery
     * @return
     *      中奖，和局，未中奖
     *      ret = array('win_contents'=>array(array(1,3)), 'win_counts'=>1, 'price_sum'=>3.9, 'status'=>STATUS_WIN);
     *      ret = array('win_contents'=>array(), 'win_counts'=>0, 'price_sum'=>4, 'status'=>STATUS_HE);
     *      ret = null;
     */
    public function settlement_2m_qezhx_fs(& $bet = array(), & $lottery = array())
    {
        $balls = get_balls($bet[2]);
        $ret = null;
        // 同时中奖才能中,而且只能中一注
        if (in_array($lottery['base'][0], $balls[0]) && in_array($lottery['base'][1], $balls[1])) {
            $ret['win_contents'][] = array($lottery['base'][0], $lottery['base'][1]);
        }
    
        if (!empty($ret['win_contents'])) {
            $ret['win_counts'] = count($ret['win_contents']);
            $ret['price_sum'] = $bet[3] * $bet[6] * $ret['win_counts'];
            $ret['status'] = STATUS_WIN;
        }
        return $ret;
    }
    
    /**
     * @brief 二码_前二直选_单式
     *          从百位、十位中选择一个2位数号码组成一注，所选号码与开奖号码的前2位相同，且顺序一致，即为中奖。
     *          投注方案：5 8
     *          开奖号码：前二位5 8即中前二直选。
     *          $bet = "[\"1570329144415671\",1,\"3|2,4,5||6\",2,3,6,\"6.12\",13]";
     *          $bet = "[\"1570329155205234\",1,\"3,4,5\",2,3,6,\"6.12\",13]";
     * @access public
     * @link http://www.gc360.com/orders/bet/15/1908
     * @param array $bet
     * @param array $lottery
     * @return
     *      中奖，和局，未中奖
     *      ret = array('win_contents'=>array(array(1,3)), 'win_counts'=>1, 'price_sum'=>3.9, 'status'=>STATUS_WIN);
     *      ret = array('win_contents'=>array(), 'win_counts'=>0, 'price_sum'=>4, 'status'=>STATUS_HE);
     *      ret = null;
     */
    public function settlement_2m_qezhx_ds(& $bet = array(), & $lottery = array())
    {
        return $this->settlement_2m_qezhx_fs($bet, $lottery);
    }
    
    /**
     * @brief 二码_前二直选_直选和值
     *          所选数值等于开奖号码的百位、十位二个数字相加之和，即为中奖。
     *          和值1 开奖号码：前二位01，10，即中前二直选和值。
     *          $bet = "[\"1570329144415671\",1,\"3|2,4,5||6\",2,3,6,\"6.12\",13]";
     *          $bet = "[\"1570329155205234\",1,\"3,4,5\",2,3,6,\"6.12\",13]";
     * @access public
     * @link http://www.gc360.com/orders/bet/15/1908
     * @param array $bet
     * @param array $lottery
     * @return
     *      中奖，和局，未中奖
     *      ret = array('win_contents'=>array(array(1,3)), 'win_counts'=>1, 'price_sum'=>3.9, 'status'=>STATUS_WIN);
     *      ret = array('win_contents'=>array(), 'win_counts'=>0, 'price_sum'=>4, 'status'=>STATUS_HE);
     *      ret = null;
     */
    public function settlement_2m_qezhx_zxhz(& $bet = array(), & $lottery = array())
    {
        $balls = get_balls($bet[2]);
        $ret = null;
        if (in_array($lottery['q2'], $balls[0])) {
            $ret['win_contents'][] = $lottery['q2'];
        }
    
        if (!empty($ret['win_contents'])) {
            $ret['win_counts'] = count($ret['win_contents']);
            $ret['price_sum'] = $bet[3] * $bet[6] * $ret['win_counts'];
            $ret['status'] = STATUS_WIN;
        }
        return $ret;
    }
    
    /**
     * @brief 二码_前二组选_复式
     *          从0-9中选2个号码组成一注，所选号码与开奖号码的百位、十位相同，顺序不限，即为中奖。
     *          投注方案：5 8
     *          开奖号码：前二位5 8或8 5（顺序不限，不含对子），即中前二组选。
     *          $bet = "[\"1570329144415671\",1,\"3|2,4,5||6\",2,3,6,\"6.12\",13]";
     *          $bet = "[\"1570329155205234\",1,\"3,4,5\",2,3,6,\"6.12\",13]";
     * @access public
     * @link http://www.gc360.com/orders/bet/15/1908
     * @param array $bet
     * @param array $lottery
     * @return
     *      中奖，和局，未中奖
     *      ret = array('win_contents'=>array(array(1,3)), 'win_counts'=>1, 'price_sum'=>3.9, 'status'=>STATUS_WIN);
     *      ret = array('win_contents'=>array(), 'win_counts'=>0, 'price_sum'=>4, 'status'=>STATUS_HE);
     *      ret = null;
     */
    public function settlement_2m_qezx_fs(& $bet = array(), & $lottery = array())
    {
        $balls = get_balls($bet[2]);
        $ret = null;
        if ($lottery['base'][0] != $lottery['base'][1]) {
            if (in_array($lottery['base'][0], $balls[0]) && in_array($lottery['base'][1], $balls[0])) {
                $ret['win_contents'][] = array($lottery['base'][0], $lottery['base'][1]);
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
     * @brief 二码_前二组选_单式
     *          手动输入一个2位数号码组成一注，所选号码的百位、十位与开奖号码相同，顺序不限，即为中奖
     *          58 开奖号码：前二58,85（顺序不限，不含对子），即中前二组选。
     *          $bet = "[\"1570329144415671\",1,\"3|2,4,5||6\",2,3,6,\"6.12\",13]";
     *          $bet = "[\"1570329155205234\",1,\"3,4,5\",2,3,6,\"6.12\",13]";
     * @access public
     * @link http://www.gc360.com/orders/bet/15/1908
     * @param array $bet
     * @param array $lottery
     * @return
     *      中奖，和局，未中奖
     *      ret = array('win_contents'=>array(array(1,3)), 'win_counts'=>1, 'price_sum'=>3.9, 'status'=>STATUS_WIN);
     *      ret = array('win_contents'=>array(), 'win_counts'=>0, 'price_sum'=>4, 'status'=>STATUS_HE);
     *      ret = null;
     */
    public function settlement_2m_qezx_ds(& $bet = array(), & $lottery = array())
    {
        return $this->settlement_2m_qezx_fs($bet, $lottery);
    }
    
    /**
     * @brief 定位胆_定位胆
     *          从百位、十位、个位任意位置上至少选择1个以上号码，所选号码与相同位置上的开奖号码一致，即为中奖。
     *          投注方案：1
     *          开奖号码：百位1，即中定位胆百位。
     *          $bet = "[\"1570329144415671\",1,\"3|2,4,5||6\",2,3,6,\"6.12\",13]";
     *          $bet = "[\"1570329155205234\",1,\"3,4,5\",2,3,6,\"6.12\",13]";
     * @access public
     * @link http://www.gc360.com/orders/bet/15/1908
     * @param array $bet
     * @param array $lottery
     * @return
     *      中奖，和局，未中奖
     *      ret = array('win_contents'=>array(array(1,3)), 'win_counts'=>1, 'price_sum'=>3.9, 'status'=>STATUS_WIN);
     *      ret = array('win_contents'=>array(), 'win_counts'=>0, 'price_sum'=>4, 'status'=>STATUS_HE);
     *      ret = null;
     */
    public function settlement_dwd_dwd(& $bet = array(), & $lottery = array())
    {
        $balls = get_balls($bet[2]);
        $ret = null;
        // 一个位最多中一球，最多中三注
        foreach ($lottery['base'] as $k => $v) {
            $baseContents = ['', '', ''];
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
     * @brief 不定位_不定位
     *          从0-9中选择1个号码，每注由1个号码组成，只要开奖号码的百位、十位、个位中包含所选号码，即为中奖。
     *          投注方案：1
     *          开奖号码：至少出现1个1，即中一码不定位。
     *          $bet = "[\"1570329144415671\",1,\"3|2,4,5||6\",2,3,6,\"6.12\",13]";
     *          $bet = "[\"1570329155205234\",1,\"3,4,5\",2,3,6,\"6.12\",13]";
     * @access public
     * @link http://www.gc360.com/orders/bet/15/1908
     * @param array $bet
     * @param array $lottery
     * @return
     *      中奖，和局，未中奖
     *      ret = array('win_contents'=>array(array(1,3)), 'win_counts'=>1, 'price_sum'=>3.9, 'status'=>STATUS_WIN);
     *      ret = array('win_contents'=>array(), 'win_counts'=>0, 'price_sum'=>4, 'status'=>STATUS_HE);
     *      ret = null;
     */
    public function settlement_bdw_bdw(& $bet = array(), & $lottery = array())
    {
        $balls = get_balls($bet[2]);
        $ret = null;
        foreach ($balls[0] as $ball) {
            if (in_array($ball, $lottery['base'])) {
                $ret['win_contents'][] = $ball;
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
