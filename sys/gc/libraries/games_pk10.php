<?php
/**
 * @file games_pk10.php
 * @brief pk10 下注库
 *      NOTE: 如果玩法为和局，status 返回 STATUS_HE: $ret['status'] = STATUS_HE;
 *
 * Copyright (C) 2017 GC.COM
 * All rights reserved.
 *
 * @package libraries
 * @author Langr <hua@langr.org> 2017/04/07 10:40
 *
 * $Id$
 */

include_once(dirname(__FILE__) . '/games.php');

class games_pk10
{
    public $config_balls = [
        /* 基本球 */
        'base' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10],
        'dx' => [100, 101], // 大：100，小：101
        'ds' => [102, 103], // 单：102，双：103
    ];
    
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
    public function bet_q1_q1(& $bet = [])
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
    }
    
    /**
     * @brief 前二_前二复式
     *          从01-10中至少选择1个号码组成一注，所选号码与开奖号码中第一位相同即中奖。
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
    public function bet_q2_q2fs(& $bet = [])
    {
        // 取球号
        $balls = get_balls($bet['contents'], $this->config_balls['base']);
        // 检测球
        if ($balls == false || count($balls) != 2) {
            return false;
        }
        // 组合下注量
        $c = CC($balls, 2);
        $price_sum = round($bet['price'] * $bet['counts'], 3);
        return ($c['total'] == $bet['counts'] && $price_sum == $bet['price_sum']) ? true : false;
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
    public function bet_q2_q2ds(& $bet = [])
    {
        return $this->bet_q2_q2fs($bet);
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
    public function bet_q3_q3fs(& $bet = [])
    {
        // 取球号
        $balls = get_balls($bet['contents'], $this->config_balls['base']);
        // 检测球
        if ($balls == false || count($balls) != 3) {
            return false;
        }
        // 组合下注量
        $c = CC($balls, 3);
        $price_sum = round($bet['price'] * $bet['counts'], 3);
        return ($c['total'] == $bet['counts'] && $price_sum == $bet['price_sum']) ? true : false;
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
    public function bet_q3_q3ds(& $bet = [])
    {
        return $this->bet_q3_q3fs($bet);
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
    public function bet_dwd_dwd1(& $bet = [])
    {
        // 获取球
        $balls = get_balls($bet['contents'], $this->config_balls['base']);
        // 检验球
        if ($balls == false || count($balls) != 5) {
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
    public function bet_dwd_dwd6(& $bet = [])
    {
        // 获取球
        $balls = get_balls($bet['contents'], $this->config_balls['base']);
        // 检验球
        if ($balls == false || count($balls) != 5) {
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
    public function bet_dx_d1(& $bet = [])
    {
        // 获取球
        $balls = get_balls($bet['contents'], $this->config_balls['dx']);
        // 检验球
        if ($balls == false || count($balls) != 1) {
            return false;
        }
        // 组合数
        $c = count($balls[0]);
        $price_sum = round($bet['price'] * $bet['counts'], 3);
        return ($c == $bet['counts'] && $price_sum == $bet['price_sum']) ? true : false;
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
    public function bet_dx_d2(& $bet = [])
    {
        // 获取球
        $balls = get_balls($bet['contents'], $this->config_balls['dx']);
        // 检验球
        if ($balls == false || count($balls) != 1) {
            return false;
        }
        // 组合数
        $c = count($balls[0]);
        $price_sum = round($bet['price'] * $bet['counts'], 3);
        return ($c == $bet['counts'] && $price_sum == $bet['price_sum']) ? true : false;
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
    public function bet_dx_d3(& $bet = [])
    {
        // 获取球
        $balls = get_balls($bet['contents'], $this->config_balls['dx']);
        // 检验球
        if ($balls == false || count($balls) != 1) {
            return false;
        }
        // 组合数
        $c = count($balls[0]);
        $price_sum = round($bet['price'] * $bet['counts'], 3);
        return ($c == $bet['counts'] && $price_sum == $bet['price_sum']) ? true : false;
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
    public function bet_ds_d1(& $bet = [])
    {
        // 获取球
        $balls = get_balls($bet['contents'], $this->config_balls['ds']);
        // 检验球
        if ($balls == false || count($balls) != 1) {
            return false;
        }
        // 组合数
        $c = count($balls[0]);
        $price_sum = round($bet['price'] * $bet['counts'], 3);
        return ($c == $bet['counts'] && $price_sum == $bet['price_sum']) ? true : false;
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
    public function bet_ds_d2(& $bet = [])
    {
        // 获取球
        $balls = get_balls($bet['contents'], $this->config_balls['ds']);
        // 检验球
        if ($balls == false || count($balls) != 1) {
            return false;
        }
        // 组合数
        $c = count($balls[0]);
        $price_sum = round($bet['price'] * $bet['counts'], 3);
        return ($c == $bet['counts'] && $price_sum == $bet['price_sum']) ? true : false;
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
    public function bet_ds_d3(& $bet = [])
    {
        // 获取球
        $balls = get_balls($bet['contents'], $this->config_balls['ds']);
        // 检验球
        if ($balls == false || count($balls) != 1) {
            return false;
        }
        // 组合数
        $c = count($balls[0]);
        $price_sum = round($bet['price'] * $bet['counts'], 3);
        return ($c == $bet['counts'] && $price_sum == $bet['price_sum']) ? true : false;
    }
}

/* end file */
