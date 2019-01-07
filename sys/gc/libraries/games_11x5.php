<?php
/**
 * @file games_11x5.php
 * @brief 11选5 下注库
 *      NOTE: 如果玩法为和局，status 返回 STATUS_HE: $ret['status'] = STATUS_HE;
 *
 * Copyright (C) 2017 GC.COM
 * All rights reserved.
 *
 * @package libraries
 * @author Langr <hua@langr.org> 2017/04/06 20:50
 *
 * $Id$
 */

include_once(dirname(__FILE__).'/games.php');

class games_11x5
{
    public $config_balls = [
        /* 基本球 */
        'base'  => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11],
    ];
    
    /**
     * @brief 三码_前三直选_复式
     *          从01-11中各选择3个不重复的号码组成一注，所选号码与当期5个开奖号码中的前3个号码相同，且顺序一致，即为中奖。
     *          投注方案：01 02 03
     *          开奖号码：01 02 03** （前三顺序一致），即中前三直选。
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
    public function bet_3m_q3zhx_fs(& $bet = array(), $settlement = false)
    {
        // 取球号
        $balls = get_balls($bet['contents'], $this->config_balls['base']);
        // 检测球
        if ($balls == false) {
            return false;
        }
        // 组合下注量
        $c = CC($balls, 3);
        $price_sum = round($bet['price'] * $bet['counts'], 3);
        return ($c['total'] == $bet['counts'] && $price_sum == $bet['price_sum']) ? true : false;
    }
    
    /**
     * @brief 三码_前三直选_单式
     *          从01-11中各选择3个不重复的号码组成一注，所选号码与当期5个开奖号码中的前3个号码相同，且顺序一致，即为中奖。
     *          投注方案：01 02 03
     *          开奖号码：01 02 03** （前三顺序一致），即中前三直选。
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
    public function bet_3m_q3zhx_ds(& $bet = array(), $settlement = false)
    {
        return $this->bet_3m_q3zhx_fs($bet, $settlement);
    }
    
    /**
     * @brief 三码_前三组选_复式
     *          从01-11中各选择3个不重复号码组成一注，所选号码与当期顺5个开奖号码中的前3个号码相同，且顺序不限，即为中奖。
     *          投注方案：01 02 03
     *          开奖号码：01 02 03** （前三顺序不限），即中前三直选。
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
    public function bet_3m_q3zx_fs(& $bet = array(), $settlement = false)
    {
        $balls = get_balls($bet['contents'], $this->config_balls['base']);
        if ($balls == false) {
            return false;
        }
        // 组合下注量
        $n = count($balls[0]);
        $c = C($n, 3);
        $price_sum = round($bet['price'] * $bet['counts'], 3);
        return ($c == $bet['counts'] && $price_sum == $bet['price_sum']) ? true : false;
    }
    
    /**
     * @brief 三码_前三组选_单式
     *          从01-11中各选择3个不重复号码组成一注，所选号码与当期顺5个开奖号码中的前3个号码相同，且顺序不限，即为中奖。
     *          投注方案：01 02 03
     *          开奖号码：01 02 03** （前三顺序不限），即中前三直选。
     * @access public
     * @param
     * @return
     */
    public function bet_3m_q3zx_ds(& $bet = array(), $settlement = false)
    {
        return $this->bet_3m_q3zx_fs($bet, $settlement);
    }
    
    /**
     * @brief  二码_前二直选_复式
     *          从01-11中选择2个不重复号码组成一注，所选号码与当期5个号码中的前2个号码相同，且顺序一致，即为中奖。
     *          投注方案：06 08
     *          开奖号码：06 08*** （前二顺序一致），即中前二直选
     * @access public
     * @param
     * @return
     */
    public function bet_2m_q2zhx_fs(& $bet = array(), $settlement = false)
    {
        // 获取球
        $balls = get_balls($bet['contents'], $this->config_balls['base']);
        // 校验球
        if ($balls == false) {
            return false;
        }
        // 组合数
        $c = CC($balls, 2);
        $price_sum = round($bet['price'] * $bet['counts'], 3);
        return ($c['total'] == $bet['counts'] && $price_sum == $bet['price_sum']) ? true : false;
    }
    
    /**
     * @brief  二码_前二直选_单式
     *          从01-11中选择2个不重复号码组成一注，所选号码与当期5个号码中的前2个号码相同，且顺序一致，即为中奖。
     *          投注方案：06 08
     *          开奖号码：06 08*** （前二顺序一致），即中前二直选
     * @access public
     * @param
     * @return
     */
    public function bet_2m_q2zhx_ds(& $bet = array(), $settlement = false)
    {
        return $this->bet_2m_q2zhx_fs($bet, $settlement);
    }
    
    /**
     * @brief 二码_前二组选_复式
     *          从01-11个号码中选择2个或多个号码，所选号码与当期5个开奖号码中的前2个号码相同，顺序不限，即为中奖。
     *          投注方案：06 08
     *          开奖号码：08 06*** （前二顺序不限），即中前二组选。
     * @access public
     * @param
     * @return
     */
    public function bet_2m_q2zx_fs(& $bet = array(), $settlement = false)
    {
        // 获取球
        $balls = get_balls($bet['contents'], $this->config_balls['base']);
        // 检验球
        if ($balls == false) {
            return false;
        }
        // 组合数
        $n = count($balls[0]);
        $c = C($n, 2);
        $price_sum = round($bet['price'] * $bet['counts'], 3);
        return ($c == $bet['counts'] && $price_sum == $bet['price_sum']) ? true : false;
    }
    
    /**
     * @brief 二码_前二组选_单式
     *          从01-11个号码中选择2个或多个号码，所选号码与当期5个开奖号码中的前2个号码相同，顺序不限，即为中奖。
     *          投注方案：06 08
     *          开奖号码：08 06*** （前二顺序不限），即中前二组选。
     * @access public
     * @param
     * @return
     */
    public function bet_2m_q2zx_ds(& $bet = array(), $settlement = false)
    {
        return $this->bet_2m_q2zx_fs($bet, $settlement);
    }
    
    /**
     * @brief 不定胆前三位
     *          从11个号码中选择1个或多个号码，每注由1个号码组成，只要当期开奖号码中的第一位、第二位、第三位包含所选号码，顺序不限，即为中奖。
     *          投注方案：08
     *          开奖号码：*08*** 08**** 顺序不限，即中前三位。
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
    public function bet_bdd_q3w(& $bet = array(), $settlement = false)
    {
        // 获取球
        $balls = get_balls($bet['contents'], $this->config_balls['base']);
        // 检验球
        if ($balls == false) {
            return false;
        }
        // 组合数
        $c = count($balls[0]);
        $price_sum = round($bet['price'] * $bet['counts'], 3);
        return ($c == $bet['counts'] && $price_sum == $bet['price_sum']) ? true : false;
    }
    
    /**
     * @brief 定位胆定位胆
     *          从第一位至第五位中任意1个位置或多个位置上选择1个或1个以上号码，投注号码与相同位置上的开奖号码对位一致，即为中奖。
     *          投注方案：第一位 08
     *          开奖号码：08**** 即中定位胆。
     * @access public
     * @param
     * @return
     */
    public function bet_dwd_dwd(& $bet = array(), $settlement = false)
    {
        // 获取球
        $balls = get_balls($bet['contents'], $this->config_balls['base']);
        // 检验球
        if ($balls == false) {
            return false;
        }
        // 组合数
        $c = 0;
        foreach ($balls as $v) {
            $c += count(array_filter($v));
        }
        $price_sum = round($bet['price'] * $bet['counts'], 3);
        return ($c == $bet['counts'] && $price_sum == $bet['price_sum']) ? true : false;
    }
    
    /**
     * @brief 任选复式一中一
     *          从11个号码中选择1个或多个号码，每注由1个号码组成，只要当期的5个开奖号码中包含所选号码，即为中奖。
     *          投注方案：08
     *          开奖号码：08 05 07 03 06 即中任选一中一。
     * @access public
     * @param
     * @return
     */
    public function bet_rx_rxfs_1z1(& $bet = array(), $settlement = false)
    {
        // 获取球
        $balls = get_balls($bet['contents'], $this->config_balls['base']);
        // 检验球
        if ($balls == false) {
            return false;
        }
        // 组合数
        $c = count($balls[0]);
        $price_sum = round($bet['price'] * $bet['counts'], 3);
        return ($c == $bet['counts'] && $price_sum == $bet['price_sum']) ? true : false;
    }
    
    /**
     * @brief 任选复式二中二
     *          从01-11共11个号码中选择2个号码进行购买，只要当期顺序摇出的5个开奖号码中包含所选号码，即为中奖。
     *          投注方案：06 08
     *          开奖号码：06 05 07 08 01 即中任选二中二。
     * @access public
     * @param
     * @return
     */
    public function bet_rx_rxfs_2z2(& $bet = array(), $settlement = false)
    {
        // 获取球
        $balls = get_balls($bet['contents'], $this->config_balls['base']);
        // 检验球
        if ($balls == false) {
            return false;
        }
        // 组合数
        $n = count($balls[0]);
        $c = C($n, 2);
        $price_sum = round($bet['price'] * $bet['counts'], 3);
        return ($c == $bet['counts'] && $price_sum == $bet['price_sum']) ? true : false;
    }
    
    /**
     * @brief 任选复式三中三
     *          从11个号码中选择3个或多个号码，每注由3个号码组成，只要当期的5个开奖号码中包含所选号码，即为中奖。
     *          投注方案：06 07 08
     *          开奖号码：06 05 07 03 08 即中任选三中三。
     * @access public
     * @param
     * @return
     */
    public function bet_rx_rxfs_3z3(& $bet = array(), $settlement = false)
    {
        // 获取球
        $balls = get_balls($bet['contents'], $this->config_balls['base']);
        // 检验球
        if ($balls == false) {
            return false;
        }
        // 组合数
        $n = count($balls[0]);
        $c = C($n, 3);
        $price_sum = round($bet['price'] * $bet['counts'], 3);
        return ($c == $bet['counts'] && $price_sum == $bet['price_sum']) ? true : false;
    }
    
    /**
     * @brief 任选复式四中四
     *          从11个号码中选择4个或多个号码，每注由4个号码组成，只要当期的5个开奖号码中包含所选号码，即为中奖。
     *          投注方案： 05 06 07 08
     *          开奖号码：08 05 07 06 01 即中任选四中四。
     * @access public
     * @param
     * @return
     */
    public function bet_rx_rxfs_4z4(& $bet = array(), $settlement = false)
    {
        // 获取球
        $balls = get_balls($bet['contents'], $this->config_balls['base']);
        // 检验球
        if ($balls == false) {
            return false;
        }
        // 组合数
        $n = count($balls[0]);
        $c = C($n, 4);
        $price_sum = round($bet['price'] * $bet['counts'], 3);
        return ($c == $bet['counts'] && $price_sum == $bet['price_sum']) ? true : false;
    }
    
    /**
     * @brief 任选复式五中五
     *          从11个号码中选择5个或多个号码，每注由5个号码组成，只要当期的5个开奖号码中包含所选号码，即为中奖。
     *          投注方案： 05 06 07 08 01
     *          开奖号码：08 05 07 06 01 即中任选五中五。
     * @access public
     * @param
     * @return
     */
    public function bet_rx_rxfs_5z5(& $bet = array(), $settlement = false)
    {
        // 获取球
        $balls = get_balls($bet['contents'], $this->config_balls['base']);
        // 检验球
        if ($balls == false) {
            return false;
        }
        // 组合数
        $n = count($balls[0]);
        $c = C($n, 5);
        $price_sum = round($bet['price'] * $bet['counts'], 3);
        return ($c == $bet['counts'] && $price_sum == $bet['price_sum']) ? true : false;
    }
    
    /**
     * @brief 任选复式六中五
     *          从11个号码中选择6个或多个号码，每注由5个号码组成，只要当期的5个开奖号码中包含所选号码，即为中奖。
     *          投注方案： 05 06 07 08 01 02
     *          开奖号码：08 05 07 06 01 即中任选六中五。
     * @access public
     * @param
     * @return
     */
    public function bet_rx_rxfs_6z5(& $bet = array(), $settlement = false)
    {
        // 获取球
        $balls = get_balls($bet['contents'], $this->config_balls['base']);
        // 检验球
        if ($balls == false) {
            return false;
        }
        // 组合数
        $n = count($balls[0]);
        $c = C($n, 6);
        $price_sum = round($bet['price'] * $bet['counts'], 3);
        return ($c == $bet['counts'] && $price_sum == $bet['price_sum']) ? true : false;
    }
    
    /**
     * @brief 任选复式七中五
     *          从11个号码中选择7个或多个号码，每注由5个号码组成，只要当期的5个开奖号码中包含所选号码，即为中奖。
     *          投注方案： 05 06 07 08 01 02 03
     *          开奖号码：08 05 07 06 01 即中任选七中五。
     * @access public
     * @param
     * @return
     */
    public function bet_rx_rxfs_7z5(& $bet = array(), $settlement = false)
    {
        // 获取球
        $balls = get_balls($bet['contents'], $this->config_balls['base']);
        // 检验球
        if ($balls == false) {
            return false;
        }
        // 组合数
        $n = count($balls[0]);
        $c = C($n, 7);
        $price_sum = round($bet['price'] * $bet['counts'], 3);
        return ($c == $bet['counts'] && $price_sum == $bet['price_sum']) ? true : false;
    }
    
    /**
     * @brief 任选复式八中五
     *          从11个号码中选择7个或多个号码，每注由5个号码组成，只要当期的5个开奖号码中包含所选号码，即为中奖。
     *          投注方案： 05 06 07 08 01 02 03 04
     *          开奖号码：08 05 07 06 01 即中任选八中五。
     * @access public
     * @param
     * @return
     */
    public function bet_rx_rxfs_8z5(& $bet = array(), $settlement = false)
    {
        // 获取球
        $balls = get_balls($bet['contents'], $this->config_balls['base']);
        // 检验球
        if ($balls == false) {
            return false;
        }
        // 组合数
        $n = count($balls[0]);
        $c = C($n, 8);
        $price_sum = round($bet['price'] * $bet['counts'], 3);
        return ($c == $bet['counts'] && $price_sum == $bet['price_sum']) ? true : false;
    }
    
    /**
     * @brief 任选单式一中一
     *          手动输入1个号码组成一注，只要当期的5个开奖号码中包含所输入号码，即为中奖。
     *          投注方案：08
     *          开奖号码：05 06 07 08 09 即中任选一中一。
     * @access public
     * @param
     * @return
     */
    public function bet_rx_rxds_1z1(& $bet = array(), $settlement = false)
    {
        return $this->bet_rx_rxfs_1z1($bet, $settlement);
    }
    
    /**
     * @brief 任选单式二中二
     *          手动输入2个号码组成一注，只要当期的5个开奖号码中包含所输入号码，即为中奖。
     *          投注方案：06 08
     *          开奖号码：06 05 07 08 01 即中任选二中二。
     * @access public
     * @param
     * @return
     */
    public function bet_rx_rxds_2z2(& $bet = array(), $settlement = false)
    {
        return $this->bet_rx_rxfs_2z2($bet, $settlement);
    }
    
    /**
     * @brief 任选单式三中三
     *          手动输入3个号码组成一注，只要当期的5个开奖号码中包含所输入号码，即为中奖。
     *          投注方案：06 07 08
     *          开奖号码：06 05 07 03 08 即中任选三中三。
     * @access public
     * @param
     * @return
     */
    public function bet_rx_rxds_3z3(& $bet = array(), $settlement = false)
    {
        return $this->bet_rx_rxfs_3z3($bet, $settlement);
    }
    
    /**
     * @brief 任选单式四中四
     *          手动输入4个号码组成一注，只要当期的5个开奖号码中包含输入号码，即为中奖。
     *          投注方案： 05 06 07 08
     *          开奖号码：08 05 07 06 01 即中任选四中四。
     * @access public
     * @param
     * @return
     */
    public function bet_rx_rxds_4z4(& $bet = array(), $settlement = false)
    {
        return $this->bet_rx_rxfs_4z4($bet, $settlement);
    }
    
    /**
     * @brief 任选单式五中五
     *          手动输入5个号码组成一注，只要当期的5个开奖号码中包含所输入号码，即为中奖。
     *          投注方案： 05 06 07 08 01
     *          开奖号码：08 05 07 06 01 即中任选五中五。
     * @access public
     * @param
     * @return
     */
    public function bet_rx_rxds_5z5(& $bet = array(), $settlement = false)
    {
        return $this->bet_rx_rxfs_5z5($bet, $settlement);
    }
    
    /**
     * @brief 任选单式六中五
     *          手动输入6个号码组成一注，只要当期的5个开奖号码中包含所输入号码，即为中奖。
     *          投注方案： 05 06 07 08 01 02
     *          开奖号码：08 05 07 06 01 即中任选六中五。
     * @access public
     * @param
     * @return
     */
    public function bet_rx_rxds_6z5(& $bet = array(), $settlement = false)
    {
        return $this->bet_rx_rxfs_6z5($bet, $settlement);
    }
    
    /**
     * @brief 任选单式七中五
     *          手动输入7个号码组成一注，只要当期的5个开奖号码中包含所输入号码，即为中奖。
     *          投注方案： 05 06 07 08 01 02 03
     *          开奖号码：08 05 07 06 01 即中任选七中五。
     * @access public
     * @param
     * @return
     */
    public function bet_rx_rxds_7z5(& $bet = array(), $settlement = false)
    {
        return $this->bet_rx_rxfs_7z5($bet, $settlement);
    }
    
    /**
     * @brief 任选单式八中五
     *          手动输入8个号码组成一注，只要当期的5个开奖号码中包含所输入号码，即为中奖。
     *          投注方案： 05 06 07 08 01 02 03 04
     *          开奖号码：08 05 07 06 01 即中任选八中五。
     * @access public
     * @param
     * @return
     */
    public function bet_rx_rxds_8z5(& $bet = array(), $settlement = false)
    {
        return $this->bet_rx_rxfs_8z5($bet, $settlement);
    }
}

/* end file */
