<?php
/**
 * @file games_settlement_11x5.php
 * @brief 11选5 结算库
 *      NOTE: 如果玩法为和局，status 返回 STATUS_HE: $ret['status'] = STATUS_HE;
 *
 * Copyright (C) 2017 GC.COM
 * All rights reserved.
 *
 * @package libraries
 * @author Langr <hua@langr.org> 2017/04/06 20:51
 *
 * $Id$
 */

include_once(dirname(__FILE__) . '/games.php');

class games_settlement_11x5
{
    public $config_balls = [
        /* 基本球 */
        'base'  => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11],
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
        //$wins_balls['base'] = ['1', '2', '3', '4', '5'];
        /* NOTE: 11x5 开奖号不可以有重复 */
        if (count(array_unique($wins_balls['base'])) != 5 ||
                !in_array($wins_balls['base'][0], $this->config_balls['base']) ||
                !in_array($wins_balls['base'][1], $this->config_balls['base']) ||
                !in_array($wins_balls['base'][2], $this->config_balls['base']) ||
                !in_array($wins_balls['base'][3], $this->config_balls['base']) ||
                !in_array($wins_balls['base'][4], $this->config_balls['base'])
                ) {
            $msg = date('[Y-m-d H:i:s]').'[11x5] lottery error:'.implode(',', $wins_balls['base'])."\n";
            error_log($msg, 3, 'api/logs/wins_balls_error_'.date('Ym').'.log');
            exit($msg);
        }
        return $wins_balls;
    } /* }}} */
    
    /**
     * @brief 三码_前三直选_复式
     *      从01-11中各选择3个不重复的号码组成一注，所选号码与当期5个开奖号码中的前3个号码相同，且顺序一致，即为中奖。
     *          投注方案：01 02 03
     *          开奖号码：01 02 03** （前三顺序一致），即中前三直选。
     *          $bet = "[\"1570329144415671\",1,\"3|2,4,5||6\",2,3,6,\"6.12\",13]";
     *          $bet = "[\"1570329155205234\",1,\"3,4,5\",2,3,6,\"6.12\",13]";
     * @access public
     * @link http://www.gc360.com/orders/bet/15/1908
     * @param
     * @return
     *      中奖，和局，未中奖
     *      ret = array('win_contents'=>array(array(1,3)), 'win_counts'=>1, 'price_sum'=>3.9, 'status'=>STATUS_WIN);
     *      ret = array('win_contents'=>array(), 'win_counts'=>0, 'price_sum'=>4, 'status'=>STATUS_HE);
     *      ret = null;
     */
    public function settlement_3m_q3zhx_fs(& $bet = array(), & $lottery = array())
    {
        $balls = get_balls($bet[2]);
        $ret = null;
        // 三个同时中奖才能中,而且只能中一注
        if (in_array($lottery['base'][0], $balls[0]) && in_array($lottery['base'][1], $balls[1]) && in_array($lottery['base'][2], $balls[2])) {
            $ret['win_contents'][] = array($lottery['base'][0], $lottery['base'][1], $lottery['base'][2]);
        }
    
        if (!empty($ret['win_contents'])) {
            $ret['win_counts'] = count($ret['win_contents']);
            $ret['price_sum'] = $bet[3] * $bet[6] * $ret['win_counts'];
            $ret['status'] = STATUS_WIN;
        }
        return $ret;
    }
    
    /**
     * @brief 三码_前三直选_单式
     *      从01-11中各选择3个不重复的号码组成一注，所选号码与当期5个开奖号码中的前3个号码相同，且顺序一致，即为中奖。
     *          投注方案：01 02 03
     *          开奖号码：01 02 03** （前三顺序一致），即中前三直选。
     *          $bet = "[\"1570329144415671\",1,\"3|2,4,5||6\",2,3,6,\"6.12\",13]";
     *          $bet = "[\"1570329155205234\",1,\"3,4,5\",2,3,6,\"6.12\",13]";
     * @access public
     * @link http://www.gc360.com/orders/bet/15/1908
     * @param
     * @return
     *      中奖，和局，未中奖
     *      ret = array('win_contents'=>array(array(1,3)), 'win_counts'=>1, 'price_sum'=>3.9, 'status'=>STATUS_WIN);
     *      ret = array('win_contents'=>array(), 'win_counts'=>0, 'price_sum'=>4, 'status'=>STATUS_HE);
     *      ret = null;
     */
    public function settlement_3m_q3zhx_ds(& $bet = array(), & $lottery = array())
    {
        return $this->settlement_3m_q3zhx_fs($bet, $lottery);
    }
    
    /**
     * @brief 三码_前三组选_复式
     *      从01-11中各选择3个不重复号码组成一注，所选号码与当期顺5个开奖号码中的前3个号码相同，且顺序不限，即为中奖。
     *          投注方案：01 02 03
     *          开奖号码：01 02 03** （前三顺序不限），即中前三直选。
     *          $bet = "[\"1570329144415671\",1,\"3|2,4,5||6\",2,3,6,\"6.12\",13]";
     *          $bet = "[\"1570329155205234\",1,\"3,4,5\",2,3,6,\"6.12\",13]";
     * @access public
     * @link http://www.gc360.com/orders/bet/15/1908
     * @param
     * @return
     *      中奖，和局，未中奖
     *      ret = array('win_contents'=>array(array(1,3)), 'win_counts'=>1, 'price_sum'=>3.9, 'status'=>STATUS_WIN);
     *      ret = array('win_contents'=>array(), 'win_counts'=>0, 'price_sum'=>4, 'status'=>STATUS_HE);
     *      ret = null;
     */
    public function settlement_3m_q3zx_fs(& $bet = array(), & $lottery = array())
    {
        $balls = get_balls($bet[2]);
        $ret = null;
        // 获取组数
        $r = combination($balls[0], 3);
        foreach ($r as $v) {
            if (in_array($v[0], array($lottery['base'][0], $lottery['base'][1], $lottery['base'][2]))
                && in_array($v[1], array($lottery['base'][0], $lottery['base'][1], $lottery['base'][2]))
                && in_array($v[2], array($lottery['base'][0], $lottery['base'][1], $lottery['base'][2]))
            ) {
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
     * @brief 三码_前三组选_单式
     *      从01-11中各选择3个不重复号码组成一注，所选号码与当期顺5个开奖号码中的前3个号码相同，且顺序不限，即为中奖。
     *          投注方案：01 02 03
     *          开奖号码：01 02 03** （前三顺序不限），即中前三直选。
     *          $bet = "[\"1570329144415671\",1,\"3|2,4,5||6\",2,3,6,\"6.12\",13]";
     *          $bet = "[\"1570329155205234\",1,\"3,4,5\",2,3,6,\"6.12\",13]";
     * @access public
     * @link http://www.gc360.com/orders/bet/15/1908
     * @param
     * @return
     *      中奖，和局，未中奖
     *      ret = array('win_contents'=>array(array(1,3)), 'win_counts'=>1, 'price_sum'=>3.9, 'status'=>STATUS_WIN);
     *      ret = array('win_contents'=>array(), 'win_counts'=>0, 'price_sum'=>4, 'status'=>STATUS_HE);
     *      ret = null;
     */
    public function settlement_3m_q3zx_ds(& $bet = array(), & $lottery = array())
    {
        return $this->settlement_3m_q3zx_fs($bet, $lottery);
    }
    
    /**
     * @brief 二码_前二直选_复式
     *      从01-11中选择2个不重复号码组成一注，所选号码与当期5个号码中的前2个号码相同，且顺序一致，即为中奖。
     *          投注方案：06 08
     *          开奖号码：06 08*** （前二顺序一致），即中前二直选
     *          $bet = "[\"1570329144415671\",1,\"3|2,4,5||6\",2,3,6,\"6.12\",13]";
     *          $bet = "[\"1570329155205234\",1,\"3,4,5\",2,3,6,\"6.12\",13]";
     * @access public
     * @link http://www.gc360.com/orders/bet/15/1908
     * @param
     * @return
     *      中奖，和局，未中奖
     *      ret = array('win_contents'=>array(array(1,3)), 'win_counts'=>1, 'price_sum'=>3.9, 'status'=>STATUS_WIN);
     *      ret = array('win_contents'=>array(), 'win_counts'=>0, 'price_sum'=>4, 'status'=>STATUS_HE);
     *      ret = null;
     */
    public function settlement_2m_q2zhx_fs(& $bet = array(), & $lottery = array())
    {
        $balls = get_balls($bet[2]);
        $ret = null;
        // 两个同时中奖才能中,而且只能中一注
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
     *      从01-11中选择2个不重复号码组成一注，所选号码与当期5个号码中的前2个号码相同，且顺序一致，即为中奖。
     *          投注方案：06 08
     *          开奖号码：06 08*** （前二顺序一致），即中前二直选
     *          $bet = "[\"1570329144415671\",1,\"3|2,4,5||6\",2,3,6,\"6.12\",13]";
     *          $bet = "[\"1570329155205234\",1,\"3,4,5\",2,3,6,\"6.12\",13]";
     * @access public
     * @link http://www.gc360.com/orders/bet/15/1908
     * @param
     * @return
     *      中奖，和局，未中奖
     *      ret = array('win_contents'=>array(array(1,3)), 'win_counts'=>1, 'price_sum'=>3.9, 'status'=>STATUS_WIN);
     *      ret = array('win_contents'=>array(), 'win_counts'=>0, 'price_sum'=>4, 'status'=>STATUS_HE);
     *      ret = null;
     */
    public function settlement_2m_q2zhx_ds(& $bet = array(), & $lottery = array())
    {
        return $this->settlement_2m_q2zhx_fs($bet, $lottery);
    }
    
    /**
     * @brief 二码_前二组选_复式
     *          从01-11个号码中选择2个或多个号码，所选号码与当期5个开奖号码中的前2个号码相同，顺序不限，即为中奖。
     *          投注方案：06 08
     *          开奖号码：08 06*** （前二顺序不限），即中前二组选。
     *          $bet = "[\"1570329144415671\",1,\"3|2,4,5||6\",2,3,6,\"6.12\",13]";
     *          $bet = "[\"1570329155205234\",1,\"3,4,5\",2,3,6,\"6.12\",13]";
     * @access public
     * @link http://www.gc360.com/orders/bet/15/1908
     * @param
     * @return
     *      中奖，和局，未中奖
     *      ret = array('win_contents'=>array(array(1,3)), 'win_counts'=>1, 'price_sum'=>3.9, 'status'=>STATUS_WIN);
     *      ret = array('win_contents'=>array(), 'win_counts'=>0, 'price_sum'=>4, 'status'=>STATUS_HE);
     *      ret = null;
     */
    public function settlement_2m_q2zx_fs(& $bet = array(), & $lottery = array())
    {
        $balls = get_balls($bet[2]);
        $ret = null;
        // 获取组数
        $r = combination($balls[0], 2);
        foreach ($r as $v) {
            if (in_array($v[0], array($lottery['base'][0], $lottery['base'][1])) && in_array($v[1], array($lottery['base'][0], $lottery['base'][1]))) {
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
     * @brief 二码_前二组选_单式
     *          从01-11个号码中选择2个或多个号码，所选号码与当期5个开奖号码中的前2个号码相同，顺序不限，即为中奖。
     *          投注方案：06 08
     *          开奖号码：08 06*** （前二顺序不限），即中前二组选。
     *          $bet = "[\"1570329144415671\",1,\"3|2,4,5||6\",2,3,6,\"6.12\",13]";
     *          $bet = "[\"1570329155205234\",1,\"3,4,5\",2,3,6,\"6.12\",13]";
     * @access public
     * @link http://www.gc360.com/orders/bet/15/1908
     * @param
     * @return
     *      中奖，和局，未中奖
     *      ret = array('win_contents'=>array(array(1,3)), 'win_counts'=>1, 'price_sum'=>3.9, 'status'=>STATUS_WIN);
     *      ret = array('win_contents'=>array(), 'win_counts'=>0, 'price_sum'=>4, 'status'=>STATUS_HE);
     *      ret = null;
     */
    public function settlement_2m_q2zx_ds(& $bet = array(), & $lottery = array())
    {
        return $this->settlement_2m_q2zx_fs($bet, $lottery);
    }
    
    /**
     * @brief 不定胆前三位
     *          从11个号码中选择1个或多个号码，每注由1个号码组成，只要当期开奖号码中的第一位、第二位、第三位包含所选号码，顺序不限，即为中奖。
     *          投注方案：08
     *          开奖号码：*08*** 08**** 顺序不限，即中前三位。
     *          $bet = "[\"1570329144415671\",1,\"3|2,4,5||6\",2,3,6,\"6.12\",13]";
     *          $bet = "[\"1570329155205234\",1,\"3,4,5\",2,3,6,\"6.12\",13]";
     * @access public
     * @link http://www.gc360.com/orders/bet/15/1908
     * @param
     * @return
     *      中奖，和局，未中奖
     *      ret = array('win_contents'=>array(array(1,3)), 'win_counts'=>1, 'price_sum'=>3.9, 'status'=>STATUS_WIN);
     *      ret = array('win_contents'=>array(), 'win_counts'=>0, 'price_sum'=>4, 'status'=>STATUS_HE);
     *      ret = null;
     */
    public function settlement_bdd_q3w(& $bet = array(), & $lottery = array())
    {
        $balls = get_balls($bet[2]);
        $ret = null;
        foreach ($balls[0] as $ball) {
            if (in_array($ball, array($lottery['base'][0], $lottery['base'][1], $lottery['base'][2]))) {
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
    
    /**
     * @brief 定位胆定位胆
     *          从第一位至第五位中任意1个位置或多个位置上选择1个或1个以上号码，投注号码与相同位置上的开奖号码对位一致，即为中奖。
     *          投注方案：第一位 08
     *          开奖号码：08**** 即中定位胆。
     *          $bet = "[\"1570329144415671\",1,\"3|2,4,5||6\",2,3,6,\"6.12\",13]";
     *          $bet = "[\"1570329155205234\",1,\"3,4,5\",2,3,6,\"6.12\",13]";
     * @access public
     * @link http://www.gc360.com/orders/bet/15/1908
     * @param
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
     * @brief 任选复式一中一
     *          从11个号码中选择1个或多个号码，每注由1个号码组成，只要当期的5个开奖号码中包含所选号码，即为中奖。
     *          投注方案：08
     *          开奖号码：08 05 07 03 06 即中任选一中一。
     *          $bet = "[\"1570329144415671\",1,\"3|2,4,5||6\",2,3,6,\"6.12\",13]";
     *          $bet = "[\"1570329155205234\",1,\"3,4,5\",2,3,6,\"6.12\",13]";
     * @access public
     * @link http://www.gc360.com/orders/bet/15/1908
     * @param
     * @return
     *      中奖，和局，未中奖
     *      ret = array('win_contents'=>array(array(1,3)), 'win_counts'=>1, 'price_sum'=>3.9, 'status'=>STATUS_WIN);
     *      ret = array('win_contents'=>array(), 'win_counts'=>0, 'price_sum'=>4, 'status'=>STATUS_HE);
     *      ret = null;
     */
    public function settlement_rx_rxfs_1z1(& $bet = array(), & $lottery = array())
    {
        $balls = get_balls($bet[2]);
        $ret = null;
        foreach ($balls[0] as $v) {
            if (in_array($v, $lottery['base'])) {
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
     * @brief 任选复式二中二
     *          从01-11共11个号码中选择2个号码进行购买，只要当期顺序摇出的5个开奖号码中包含所选号码，即为中奖。
     *          投注方案：06 08
     *          开奖号码：06 05 07 08 01 即中任选二中二。
     *          $bet = "[\"1570329144415671\",1,\"3|2,4,5||6\",2,3,6,\"6.12\",13]";
     *          $bet = "[\"1570329155205234\",1,\"3,4,5\",2,3,6,\"6.12\",13]";
     * @access public
     * @link http://www.gc360.com/orders/bet/15/1908
     * @param
     * @return
     *      中奖，和局，未中奖
     *      ret = array('win_contents'=>array(array(1,3)), 'win_counts'=>1, 'price_sum'=>3.9, 'status'=>STATUS_WIN);
     *      ret = array('win_contents'=>array(), 'win_counts'=>0, 'price_sum'=>4, 'status'=>STATUS_HE);
     *      ret = null;
     */
    public function settlement_rx_rxfs_2z2(& $bet = array(), & $lottery = array())
    {
        $balls = get_balls($bet[2]);
        $ret = null;
        $r = combination($balls[0], 2);
        foreach ($r as $v) {
            // count(array_diff($lottery['base'], $v)) == 3
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
    }
    
    /**
     * @brief 任选复式三中三
     *          从11个号码中选择3个或多个号码，每注由3个号码组成，只要当期的5个开奖号码中包含所选号码，即为中奖。
     *          投注方案：06 07 08
     *          开奖号码：06 05 07 03 08 即中任选三中三。
     *          $bet = "[\"1570329144415671\",1,\"3|2,4,5||6\",2,3,6,\"6.12\",13]";
     *          $bet = "[\"1570329155205234\",1,\"3,4,5\",2,3,6,\"6.12\",13]";
     * @access public
     * @link http://www.gc360.com/orders/bet/15/1908
     * @param
     * @return
     *      中奖，和局，未中奖
     *      ret = array('win_contents'=>array(array(1,3)), 'win_counts'=>1, 'price_sum'=>3.9, 'status'=>STATUS_WIN);
     *      ret = array('win_contents'=>array(), 'win_counts'=>0, 'price_sum'=>4, 'status'=>STATUS_HE);
     *      ret = null;
     */
    public function settlement_rx_rxfs_3z3(& $bet = array(), & $lottery = array())
    {
        $balls = get_balls($bet[2]);
        $ret = null;
        $r = combination($balls[0], 3);
        foreach ($r as $v) {
            if (in_array($v[0], $lottery['base']) && in_array($v[1], $lottery['base']) && in_array($v[2], $lottery['base'])) {
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
     * @brief 任选复式四中四
     *          从11个号码中选择4个或多个号码，每注由4个号码组成，只要当期的5个开奖号码中包含所选号码，即为中奖。
     *          投注方案： 05 06 07 08
     *          开奖号码：08 05 07 06 01 即中任选四中四。
     *          $bet = "[\"1570329144415671\",1,\"3|2,4,5||6\",2,3,6,\"6.12\",13]";
     *          $bet = "[\"1570329155205234\",1,\"3,4,5\",2,3,6,\"6.12\",13]";
     * @access public
     * @link http://www.gc360.com/orders/bet/15/1908
     * @param
     * @return
     *      中奖，和局，未中奖
     *      ret = array('win_contents'=>array(array(1,3)), 'win_counts'=>1, 'price_sum'=>3.9, 'status'=>STATUS_WIN);
     *      ret = array('win_contents'=>array(), 'win_counts'=>0, 'price_sum'=>4, 'status'=>STATUS_HE);
     *      ret = null;
     */
    public function settlement_rx_rxfs_4z4(& $bet = array(), & $lottery = array())
    {
        $balls = get_balls($bet[2]);
        $ret = null;
        $r = combination($balls[0], 4);
        foreach ($r as $v) {
            if (in_array($v[0], $lottery['base']) && in_array($v[1], $lottery['base'])
                && in_array($v[2], $lottery['base']) && in_array($v[3], $lottery['base'])
            ) {
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
     * @brief 任选复式五中五
     *          从11个号码中选择5个或多个号码，每注由5个号码组成，只要当期的5个开奖号码中包含所选号码，即为中奖。
     *          投注方案： 05 06 07 08 01
     *          开奖号码：08 05 07 06 01 即中任选五中五。
     *          $bet = "[\"1570329144415671\",1,\"3|2,4,5||6\",2,3,6,\"6.12\",13]";
     *          $bet = "[\"1570329155205234\",1,\"3,4,5\",2,3,6,\"6.12\",13]";
     * @access public
     * @link http://www.gc360.com/orders/bet/15/1908
     * @param
     * @return
     *      中奖，和局，未中奖
     *      ret = array('win_contents'=>array(array(1,3)), 'win_counts'=>1, 'price_sum'=>3.9, 'status'=>STATUS_WIN);
     *      ret = array('win_contents'=>array(), 'win_counts'=>0, 'price_sum'=>4, 'status'=>STATUS_HE);
     *      ret = null;
     */
    public function settlement_rx_rxfs_5z5(& $bet = array(), & $lottery = array())
    {
        $balls = get_balls($bet[2]);
        $ret = null;
        $r = combination($balls[0], 5);
        foreach ($r as $k => $v) {
            if (empty($ret['win_contents']) && in_array($v[0], $lottery['base']) && in_array($v[1], $lottery['base'])
                && in_array($v[2], $lottery['base']) && in_array($v[3], $lottery['base']) && in_array($v[4], $lottery['base'])
            ) {
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
     * @brief 任选复式六中五
     *          从11个号码中选择6个或多个号码，每注由6个号码组成，只要当期的5个开奖号码中包含所选号码，即为中奖。
     *          投注方案： 05 06 07 08 01 02
     *          开奖号码：08 05 07 06 01 即中任选六中五。
     *          $bet = "[\"1570329144415671\",1,\"3|2,4,5||6\",2,3,6,\"6.12\",13]";
     *          $bet = "[\"1570329155205234\",1,\"3,4,5\",2,3,6,\"6.12\",13]";
     * @access public
     * @link http://www.gc360.com/orders/bet/15/1908
     * @param
     * @return
     *      中奖，和局，未中奖
     *      ret = array('win_contents'=>array(array(1,3)), 'win_counts'=>1, 'price_sum'=>3.9, 'status'=>STATUS_WIN);
     *      ret = array('win_contents'=>array(), 'win_counts'=>0, 'price_sum'=>4, 'status'=>STATUS_HE);
     *      ret = null;
     */
    public function settlement_rx_rxfs_6z5(& $bet = array(), & $lottery = array())
    {
        $balls = get_balls($bet[2]);
        $ret = null;
        $r = combination($balls[0], 6);
        foreach ($r as $v) {
            if (in_array($lottery['base'][0], $v) && in_array($lottery['base'][1], $v) && in_array($lottery['base'][2], $v)
                && in_array($lottery['base'][3], $v) && in_array($lottery['base'][4], $v)
            ) {
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
     * @brief 任选复式七中五
     *          从11个号码中选择7个或多个号码，每注由7个号码组成，只要当期的5个开奖号码中包含所选号码，即为中奖。
     *          投注方案： 05 06 07 08 01 02 03
     *          开奖号码：08 05 07 06 01 即中任选七中五。
     *          $bet = "[\"1570329144415671\",1,\"3|2,4,5||6\",2,3,6,\"6.12\",13]";
     *          $bet = "[\"1570329155205234\",1,\"3,4,5\",2,3,6,\"6.12\",13]";
     * @access public
     * @link http://www.gc360.com/orders/bet/15/1908
     * @param
     * @return
     *      中奖，和局，未中奖
     *      ret = array('win_contents'=>array(array(1,3)), 'win_counts'=>1, 'price_sum'=>3.9, 'status'=>STATUS_WIN);
     *      ret = array('win_contents'=>array(), 'win_counts'=>0, 'price_sum'=>4, 'status'=>STATUS_HE);
     *      ret = null;
     */
    public function settlement_rx_rxfs_7z5(& $bet = array(), & $lottery = array())
    {
        $balls = get_balls($bet[2]);
        $ret = null;
        $r = combination($balls[0], 7);
        foreach ($r as $v) {
            if (in_array($lottery['base'][0], $v) && in_array($lottery['base'][1], $v) && in_array($lottery['base'][2], $v)
                && in_array($lottery['base'][3], $v) && in_array($lottery['base'][4], $v)
            ) {
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
     * @brief 任选复式八中五
     *          从11个号码中选择8个或多个号码，每注由8个号码组成，只要当期的5个开奖号码中包含所选号码，即为中奖。
     *          投注方案： 05 06 07 08 01 02 03 04
     *          开奖号码：08 05 07 06 01 即中任选八中五。
     *          $bet = "[\"1570329144415671\",1,\"3|2,4,5||6\",2,3,6,\"6.12\",13]";
     *          $bet = "[\"1570329155205234\",1,\"3,4,5\",2,3,6,\"6.12\",13]";
     * @access public
     * @link http://www.gc360.com/orders/bet/15/1908
     * @param
     * @return
     *      中奖，和局，未中奖
     *      ret = array('win_contents'=>array(array(1,3)), 'win_counts'=>1, 'price_sum'=>3.9, 'status'=>STATUS_WIN);
     *      ret = array('win_contents'=>array(), 'win_counts'=>0, 'price_sum'=>4, 'status'=>STATUS_HE);
     *      ret = null;
     */
    public function settlement_rx_rxfs_8z5(& $bet = array(), & $lottery = array())
    {
        $balls = get_balls($bet[2]);
        $ret = null;
        $r = combination($balls[0], 8);
        foreach ($r as $v) {
            if (in_array($lottery['base'][0], $v) && in_array($lottery['base'][1], $v) && in_array($lottery['base'][2], $v)
                && in_array($lottery['base'][3], $v) && in_array($lottery['base'][4], $v)
            ) {
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
     * @brief 任选单式一中一
     *          手动输入1个号码组成一注，只要当期的5个开奖号码中包含所输入号码，即为中奖。
     *          投注方案：08
     *          开奖号码：05 06 07 08 09 即中任选一中一。
     *          $bet = "[\"1570329144415671\",1,\"3|2,4,5||6\",2,3,6,\"6.12\",13]";
     *          $bet = "[\"1570329155205234\",1,\"3,4,5\",2,3,6,\"6.12\",13]";
     * @access public
     * @link http://www.gc360.com/orders/bet/15/1908
     * @param
     * @return
     *      中奖，和局，未中奖
     *      ret = array('win_contents'=>array(array(1,3)), 'win_counts'=>1, 'price_sum'=>3.9, 'status'=>STATUS_WIN);
     *      ret = array('win_contents'=>array(), 'win_counts'=>0, 'price_sum'=>4, 'status'=>STATUS_HE);
     *      ret = null;
     */
    public function settlement_rx_rxds_1z1(& $bet = array(), & $lottery = array())
    {
        return $this->settlement_rx_rxfs_1z1($bet, $lottery);
    }
    
    /**
     * @brief 任选单式二中二
     *          手动输入2个号码组成一注，只要当期的5个开奖号码中包含所输入号码，即为中奖。
     *          投注方案：06 08
     *          开奖号码：06 05 07 08 01 即中任选二中二。
     *          $bet = "[\"1570329144415671\",1,\"3|2,4,5||6\",2,3,6,\"6.12\",13]";
     *          $bet = "[\"1570329155205234\",1,\"3,4,5\",2,3,6,\"6.12\",13]";
     * @access public
     * @link http://www.gc360.com/orders/bet/15/1908
     * @param
     * @return
     *      中奖，和局，未中奖
     *      ret = array('win_contents'=>array(array(1,3)), 'win_counts'=>1, 'price_sum'=>3.9, 'status'=>STATUS_WIN);
     *      ret = array('win_contents'=>array(), 'win_counts'=>0, 'price_sum'=>4, 'status'=>STATUS_HE);
     *      ret = null;
     */
    public function settlement_rx_rxds_2z2(& $bet = array(), & $lottery = array())
    {
        return $this->settlement_rx_rxfs_2z2($bet, $lottery);
    }
    
    /**
     * @brief 任选单式三中三
     *          手动输入3个号码组成一注，只要当期的5个开奖号码中包含所输入号码，即为中奖。
     *          投注方案：06 07 08
     *          开奖号码：06 05 07 03 08 即中任选三中三。
     *          $bet = "[\"1570329144415671\",1,\"3|2,4,5||6\",2,3,6,\"6.12\",13]";
     *          $bet = "[\"1570329155205234\",1,\"3,4,5\",2,3,6,\"6.12\",13]";
     * @access public
     * @link http://www.gc360.com/orders/bet/15/1908
     * @param
     * @return
     *      中奖，和局，未中奖
     *      ret = array('win_contents'=>array(array(1,3)), 'win_counts'=>1, 'price_sum'=>3.9, 'status'=>STATUS_WIN);
     *      ret = array('win_contents'=>array(), 'win_counts'=>0, 'price_sum'=>4, 'status'=>STATUS_HE);
     *      ret = null;
     */
    public function settlement_rx_rxds_3z3(& $bet = array(), & $lottery = array())
    {
        return $this->settlement_rx_rxfs_3z3($bet, $lottery);
    }
    
    /**
     * @brief 任选单式四中四
     *          手动输入4个号码组成一注，只要当期的5个开奖号码中包含输入号码，即为中奖。
     *          投注方案： 05 06 07 08
     *          开奖号码：08 05 07 06 01 即中任选四中四。
     *          $bet = "[\"1570329144415671\",1,\"3|2,4,5||6\",2,3,6,\"6.12\",13]";
     *          $bet = "[\"1570329155205234\",1,\"3,4,5\",2,3,6,\"6.12\",13]";
     * @access public
     * @link http://www.gc360.com/orders/bet/15/1908
     * @param
     * @return
     *      中奖，和局，未中奖
     *      ret = array('win_contents'=>array(array(1,3)), 'win_counts'=>1, 'price_sum'=>3.9, 'status'=>STATUS_WIN);
     *      ret = array('win_contents'=>array(), 'win_counts'=>0, 'price_sum'=>4, 'status'=>STATUS_HE);
     *      ret = null;
     */
    public function settlement_rx_rxds_4z4(& $bet = array(), & $lottery = array())
    {
        return $this->settlement_rx_rxfs_4z4($bet, $lottery);
    }
    
    /**
     * @brief 任选单式五中五
     *          手动输入5个号码组成一注，只要当期的5个开奖号码中包含所输入号码，即为中奖。
     *          投注方案： 05 06 07 08 01
     *          开奖号码：08 05 07 06 01 即中任选五中五。
     *          $bet = "[\"1570329144415671\",1,\"3|2,4,5||6\",2,3,6,\"6.12\",13]";
     *          $bet = "[\"1570329155205234\",1,\"3,4,5\",2,3,6,\"6.12\",13]";
     * @access public
     * @link http://www.gc360.com/orders/bet/15/1908
     * @param
     * @return
     *      中奖，和局，未中奖
     *      ret = array('win_contents'=>array(array(1,3)), 'win_counts'=>1, 'price_sum'=>3.9, 'status'=>STATUS_WIN);
     *      ret = array('win_contents'=>array(), 'win_counts'=>0, 'price_sum'=>4, 'status'=>STATUS_HE);
     *      ret = null;
     */
    public function settlement_rx_rxds_5z5(& $bet = array(), & $lottery = array())
    {
        return $this->settlement_rx_rxfs_5z5($bet, $lottery);
    }
    
    /**
     * @brief 任选单式六中五
     *          手动输入6个号码组成一注，只要当期的5个开奖号码中包含所输入号码，即为中奖。
     *          投注方案： 05 06 07 08 01 02
     *          开奖号码：08 05 07 06 01 即中任选六中五。
     *          $bet = "[\"1570329144415671\",1,\"3|2,4,5||6\",2,3,6,\"6.12\",13]";
     *          $bet = "[\"1570329155205234\",1,\"3,4,5\",2,3,6,\"6.12\",13]";
     * @access public
     * @link http://www.gc360.com/orders/bet/15/1908
     * @param
     * @return
     *      中奖，和局，未中奖
     *      ret = array('win_contents'=>array(array(1,3)), 'win_counts'=>1, 'price_sum'=>3.9, 'status'=>STATUS_WIN);
     *      ret = array('win_contents'=>array(), 'win_counts'=>0, 'price_sum'=>4, 'status'=>STATUS_HE);
     *      ret = null;
     */
    public function settlement_rx_rxds_6z5(& $bet = array(), & $lottery = array())
    {
        return $this->settlement_rx_rxfs_6z5($bet, $lottery);
    }
    
    /**
     * @brief 任选单式七中五
     *          手动输入7个号码组成一注，只要当期的5个开奖号码中包含所输入号码，即为中奖。
     *          投注方案： 05 06 07 08 01 02 03
     *          开奖号码：08 05 07 06 01 即中任选七中五。
     *          $bet = "[\"1570329144415671\",1,\"3|2,4,5||6\",2,3,6,\"6.12\",13]";
     *          $bet = "[\"1570329155205234\",1,\"3,4,5\",2,3,6,\"6.12\",13]";
     * @access public
     * @link http://www.gc360.com/orders/bet/15/1908
     * @param
     * @return
     *      中奖，和局，未中奖
     *      ret = array('win_contents'=>array(array(1,3)), 'win_counts'=>1, 'price_sum'=>3.9, 'status'=>STATUS_WIN);
     *      ret = array('win_contents'=>array(), 'win_counts'=>0, 'price_sum'=>4, 'status'=>STATUS_HE);
     *      ret = null;
     */
    public function settlement_rx_rxds_7z5(& $bet = array(), & $lottery = array())
    {
        return $this->settlement_rx_rxfs_7z5($bet, $lottery);
    }
    
    /**
     * @brief 任选单式八中五
     *          手动输入8个号码组成一注，只要当期的5个开奖号码中包含所输入号码，即为中奖。
     *          投注方案： 05 06 07 08 01 02 03 04
     *          开奖号码：08 05 07 06 01 即中任选八中五。
     *          $bet = "[\"1570329144415671\",1,\"3|2,4,5||6\",2,3,6,\"6.12\",13]";
     *          $bet = "[\"1570329155205234\",1,\"3,4,5\",2,3,6,\"6.12\",13]";
     * @access public
     * @link http://www.gc360.com/orders/bet/15/1908
     * @param
     * @return
     *      中奖，和局，未中奖
     *      ret = array('win_contents'=>array(array(1,3)), 'win_counts'=>1, 'price_sum'=>3.9, 'status'=>STATUS_WIN);
     *      ret = array('win_contents'=>array(), 'win_counts'=>0, 'price_sum'=>4, 'status'=>STATUS_HE);
     *      ret = null;
     */
    public function settlement_rx_rxds_8z5(& $bet = array(), & $lottery = array())
    {
        return $this->settlement_rx_rxfs_8z5($bet, $lottery);
    }
}

/* end file */
