<?php
/**
 * @file games_lhc.php
 * @brief 
 * 
 * Copyright (C) 2017 GC.COM
 * All rights reserved.
 * 
 * @package libraries
 * @author Langr <hua@langr.org> 2017/04/07 11:04
 * 
 * $Id$
 */

include_once(dirname(__FILE__).'/games.php');

class games_lhc
{
    public $config_balls = [
        /* 基本球 */
        'base'  => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20,
            21, 22, 23, 24, 25, 26, 27, 28, 29, 30, 31, 32, 33, 34, 35, 36, 37, 38, 39, 40,
            41, 42, 43, 44, 45, 46, 47, 48, 49],
        /* 两面其他：特单特双和单和双总单总双 */
        'lm_qt' => [104, 105, 106, 107, 116, 117, 118, 119, 120, 121, 122, 123],
        /* 两面正码1-6：大小单双，和大和小和单和双 */
        'lm_zm' => [100, 101, 102, 103, 104, 105, 106, 107],
        /* 特码AB 其他：和大和小和单和双，特大特小特单特双，尾大尾小，大双小双大单小单 */
        'tm_qt' => [104, 105, 106, 107, 108, 109, 110, 111, 112, 113, 116, 117, 118, 119],
        /* 正码1-6 大小单双，和大和小和单和双，尾大尾小，红绿蓝波 */
        'zm_16' => [100, 101, 102, 103, 104, 105, 106, 107, 112, 113, 124, 125, 126],
        /* 生肖: */
        'sx' => [129, 130, 131, 132, 133, 134, 135, 136, 137, 138, 139, 140],
        /* 总肖: 234肖，5肖6肖7肖，总肖单总肖双 */
        'zhx' => [251, 252, 253, 254, 255, 256],
        /* 尾数: 尾0-尾9，头尾数: 头0-头4 */
        'ws' => [159, 160, 161, 162, 163, 164, 165, 166, 167, 168],
        'tws' => [159, 160, 161, 162, 163, 164, 165, 166, 167, 168, 260, 261, 262, 263, 264],
        /* 色波 红绿蓝，7色波，半波，半半波 */
        'sb' => [124, 125, 126],
        '7sb' => [124, 125, 126, 200],
        'bb' => [141, 142, 143, 144, 147, 148, 149, 150, 153, 154, 155, 156],
        'bbb' => [301, 302, 303, 304, 305, 306, 307, 308, 309, 310, 311, 312],
        /* 七码五行 */
        '7m'   => [270, 271, 272, 273, 274, 275, 276, 277, 278, 279, 280, 281, 282, 283, 284, 285],
        '5x'   => [286, 287, 288, 289, 290]
    ];

    /**
     * @brief 两面_其他
     *      每单1注，每注1球
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
     * @link http://www.gc360.com/orders/bet/3/
     * @param 
     * @return 
     */
    public function bet_lm_qt(& $bet = array()) /* {{{ */
    {
        /* 取球号 */
        $balls = get_balls($bet['contents'], $this->config_balls['lm_qt']);
        /* 检测球号 */
        if ($balls == false) {
            return false;
        }
        /* 组合下注量 */
        $c = count($balls[0]);
        if ($c != 1) {
            return false;
        }
        $price_sum = $bet['price'] * $bet['counts'];
        return ($c == $bet['counts'] && $price_sum == $bet['price_sum']) ? true : false;
    } /* }}} */
    
    /**
     * @brief 两面_正1
     *      每单同赔率可多注，不同赔率需分单，每注1球
     * @access public/protected 
     * @param 
     * @return 
     */
    public function bet_lm_z1(& $bet = array()) /* {{{ */
    {
        /* 取球号 */
        $balls = get_balls($bet['contents'], $this->config_balls['lm_zm']);
        /* 检测球号 */
        if ($balls == false) {
            return false;
        }
        /* 组合下注量 */
        $c = count($balls[0]);
        $price_sum = $bet['price'] * $bet['counts'];
        return ($c == $bet['counts'] && $price_sum == $bet['price_sum']) ? true : false;
    } /* }}} */
    
    /**
     * @brief 两面_正2
     *      每单同赔率可多注，不同赔率需分单，每注1球
     * @access public/protected 
     * @param 
     * @return 
     */
    public function bet_lm_z2(& $bet = array()) /* {{{ */
    {
        return $this->bet_lm_z1($bet);
    } /* }}} */
    
    /**
     * @brief 两面_正3
     *      每单同赔率可多注，不同赔率需分单，每注1球
     * @access public/protected 
     * @param 
     * @return 
     */
    public function bet_lm_z3(& $bet = array()) /* {{{ */
    {
        return $this->bet_lm_z1($bet);
    } /* }}} */
    
    /**
     * @brief 两面_正4
     *      每单同赔率可多注，不同赔率需分单，每注1球
     * @access public/protected 
     * @param 
     * @return 
     */
    public function bet_lm_z4(& $bet = array()) /* {{{ */
    {
        return $this->bet_lm_z1($bet);
    } /* }}} */
    
    /**
     * @brief 两面_正5
     *      每单同赔率可多注，不同赔率需分单，每注1球
     * @access public/protected 
     * @param 
     * @return 
     */
    public function bet_lm_z5(& $bet = array()) /* {{{ */
    {
        return $this->bet_lm_z1($bet);
    } /* }}} */
    
    /**
     * @brief 两面_正6
     *      每单同赔率可多注，不同赔率需分单，每注1球
     * @access public/protected 
     * @param 
     * @return 
     */
    public function bet_lm_z6(& $bet = array()) /* {{{ */
    {
        return $this->bet_lm_z1($bet);
    } /* }}} */
    
    /**
     * @brief 特码AB_特码A
     *      每单同赔率可多注，不同赔率需分单，每注1球
     * @access public/protected 
     * @param 
     * @return 
     */
    public function bet_tmab_ta(& $bet = array()) /* {{{ */
    {
        /* 取球号 */
        $balls = get_balls($bet['contents'], $this->config_balls['base']);
        /* 检测球号 */
        if ($balls == false) {
            return false;
        }
        /* 组合下注量 */
        $c = count($balls[0]);
        $price_sum = $bet['price'] * $bet['counts'];
        return ($c == $bet['counts'] && $price_sum == $bet['price_sum']) ? true : false;
    } /* }}} */
    
    /**
     * @brief 特码AB_特码B
     *      每单同赔率可多注，不同赔率需分单，每注1球
     * @access public/protected 
     * @param 
     * @return 
     */
    public function bet_tmab_tb(& $bet = array()) /* {{{ */
    {
        return $this->bet_tmab_ta($bet);
    } /* }}} */
    
    /**
     * @brief 特码AB_特码其他
     *      每单1注，每注1球
     * @access public/protected 
     * @param 
     * @return 
     */
    public function bet_tmab_qt(& $bet = array()) /* {{{ */
    {
        /* 取球号 */
        $balls = get_balls($bet['contents'], $this->config_balls['tm_qt']);
        /* 检测球号 */
        if ($balls == false) {
            return false;
        }
        /* 组合下注量 */
        $c = count($balls[0]);
        if ($c != 1) {
            return false;
        }
        $price_sum = $bet['price'] * $bet['counts'];
        return ($c == $bet['counts'] && $price_sum == $bet['price_sum']) ? true : false;
    } /* }}} */
    
    /**
     * @brief 正码_正码
     *      每单同赔率可多注，不同赔率需分单，每注1球
     * @access public/protected 
     * @param 
     * @return 
     */
    public function bet_zm_zm(& $bet = array()) /* {{{ */
    {
        /* 取球号 */
        $balls = get_balls($bet['contents'], $this->config_balls['base']);
        /* 检测球号 */
        if ($balls == false) {
            return false;
        }
        /* 组合下注量 */
        $c = count($balls[0]);
        $price_sum = $bet['price'] * $bet['counts'];
        return ($c == $bet['counts'] && $price_sum == $bet['price_sum']) ? true : false;
    } /* }}} */
    
    /**
     * @brief 正码特_正码特1
     *      每单同赔率可多注，不同赔率需分单，每注1球
     * @access public/protected 
     * @param 
     * @return 
     */
    public function bet_zmt_z1t(& $bet = array()) /* {{{ */
    {
        return $this->bet_zm_zm($bet);
    } /* }}} */
    
    /**
     * @brief 正码特_正码特2
     *      每单同赔率可多注，不同赔率需分单，每注1球
     * @access public/protected 
     * @param 
     * @return 
     */
    public function bet_zmt_z2t(& $bet = array()) /* {{{ */
    {
        return $this->bet_zm_zm($bet);
    } /* }}} */
    
    /**
     * @brief 正码特_正码特3
     *      每单同赔率可多注，不同赔率需分单，每注1球
     * @access public/protected 
     * @param 
     * @return 
     */
    public function bet_zmt_z3t(& $bet = array()) /* {{{ */
    {
        return $this->bet_zm_zm($bet);
    } /* }}} */
    
    /**
     * @brief 正码特_正码特4
     *      每单同赔率可多注，不同赔率需分单，每注1球
     * @access public/protected 
     * @param 
     * @return 
     */
    public function bet_zmt_z4t(& $bet = array()) /* {{{ */
    {
        return $this->bet_zm_zm($bet);
    } /* }}} */
    
    /**
     * @brief 正码特_正码特5
     *      每单同赔率可多注，不同赔率需分单，每注1球
     * @access public/protected 
     * @param 
     * @return 
     */
    public function bet_zmt_z5t(& $bet = array()) /* {{{ */
    {
        return $this->bet_zm_zm($bet);
    } /* }}} */
    
    /**
     * @brief 正码特_正码特6
     *      每单同赔率可多注，不同赔率需分单，每注1球
     * @access public/protected 
     * @param 
     * @return 
     */
    public function bet_zmt_z6t(& $bet = array()) /* {{{ */
    {
        return $this->bet_zm_zm($bet);
    } /* }}} */
    
    /**
     * @brief 正码1-6_正码1
     *      每单1，每注1球
     * @access public/protected 
     * @param 
     * @return 
     */
    public function bet_zm16_zm1(& $bet = array()) /* {{{ */
    {
        /* 取球号 */
        $balls = get_balls($bet['contents'], $this->config_balls['zm_16']);
        /* 检测球号 */
        if ($balls == false) {
            return false;
        }
        /* 组合下注量 */
        $c = count($balls[0]);
        if ($c != 1) {
            return false;
        }
        $price_sum = $bet['price'] * $bet['counts'];
        return ($c == $bet['counts'] && $price_sum == $bet['price_sum']) ? true : false;
    } /* }}} */
    
    /**
     * @brief 正码1-6_正码2
     *      每单1，每注1球
     * @access public/protected 
     * @param 
     * @return 
     */
    public function bet_zm16_zm2(& $bet = array()) /* {{{ */
    {
        return $this->bet_zm16_zm1($bet);
    } /* }}} */
    
    
    /**
     * @brief 正码1-6_正码3
     *      每单1，每注1球
     * @access public/protected 
     * @param 
     * @return 
     */
    public function bet_zm16_zm3(& $bet = array()) /* {{{ */
    {
        return $this->bet_zm16_zm1($bet);
    } /* }}} */
    
    
    /**
     * @brief 正码1-6_正码4
     *      每单1，每注1球
     * @access public/protected 
     * @param 
     * @return 
     */
    public function bet_zm16_zm4(& $bet = array()) /* {{{ */
    {
        return $this->bet_zm16_zm1($bet);
    } /* }}} */
    
    
    /**
     * @brief 正码1-6_正码5
     *      每单1，每注1球
     * @access public/protected 
     * @param 
     * @return 
     */
    public function bet_zm16_zm5(& $bet = array()) /* {{{ */
    {
        return $this->bet_zm16_zm1($bet);
    } /* }}} */
    
    
    /**
     * @brief 正码1-6_正码6
     *      每单1，每注1球
     * @access public/protected 
     * @param 
     * @return 
     */
    public function bet_zm16_zm6(& $bet = array()) /* {{{ */
    {
        return $this->bet_zm16_zm1($bet);
    } /* }}} */
    
    /**
     * @brief 正码过关
     *      每单1注，每注2～6球
     *          $bet = "[\"1570329155205234\",1,\"100||104|104||\",2,1,2,\"1.97,,1.97,1.97,,\",0]";
     * @access public/protected 
     * @param 
     * @return 
     */
    public function bet_zmgg(& $bet = array()) /* {{{ */
    {
        /* 取球号 */
        $balls = get_balls($bet['contents'], $this->config_balls['zm_16']);
        /* 检测球号 */
        if ($balls == false) {
            return false;
        }
        /* 组合下注量 */
        $c = count(array_filter($balls));
        if ($c < 2 || $c > 6) {
            return false;
        }
        $price_sum = $bet['price'] * $bet['counts'];
        return (1 == $bet['counts'] && $price_sum == $bet['price_sum']) ? true : false;
    } /* }}} */
    
    /**
     * @brief 连码_四全中
     *      每单多注，每注4球
     * @access public/protected 
     * @param 
     * @return 
     */
    public function bet_lm_4qz(& $bet = array()) /* {{{ */
    {
        /* 取球号 */
        $balls = get_balls($bet['contents'], $this->config_balls['base']);
        /* 检测球号 */
        if ($balls == false) {
            return false;
        }
        /* 组合下注量 */
        $n = count($balls[0]);
        if ($n < 4 || $n > 12) {
            return false;
        }
        $c = C($n, 4);
        $price_sum = $bet['price'] * $bet['counts'];
        return ($c == $bet['counts'] && $price_sum == $bet['price_sum']) ? true : false;
    } /* }}} */
    
    /**
     * @brief 连码_三全中
     *      每单多注，每注3球
     * @access public/protected 
     * @param 
     * @return 
     */
    public function bet_lm_3qz(& $bet = array()) /* {{{ */
    {
        /* 取球号 */
        $balls = get_balls($bet['contents'], $this->config_balls['base']);
        /* 检测球号 */
        if ($balls == false) {
            return false;
        }
        /* 组合下注量 */
        $n = count($balls[0]);
        if ($n < 3 || $n > 12) {
            return false;
        }
        $c = C($n, 3);
        $price_sum = $bet['price'] * $bet['counts'];
        return ($c == $bet['counts'] && $price_sum == $bet['price_sum']) ? true : false;
    } /* }}} */
    
    /**
     * @brief 连码_三中二
     *      每单多注，每注3球，可中两种：三中二之中三，三中二之中二，
     * @access public/protected 
     * @param 
     * @return 
     */
    public function bet_lm_3z2(& $bet = array()) /* {{{ */
    {
        return $this->bet_lm_3qz($bet);
    } /* }}} */
    
    /**
     * @brief 连码_二全中
     *      每单多注，每注2球，
     * @access public/protected 
     * @param 
     * @return 
     */
    public function bet_lm_2qz(& $bet = array()) /* {{{ */
    {
        /* 取球号 */
        $balls = get_balls($bet['contents'], $this->config_balls['base']);
        /* 检测球号 */
        if ($balls == false) {
            return false;
        }
        /* 组合下注量 */
        $n = count($balls[0]);
        if ($n < 2 || $n > 12) {
            return false;
        }
        $c = C($n, 2);
        $price_sum = $bet['price'] * $bet['counts'];
        return ($c == $bet['counts'] && $price_sum == $bet['price_sum']) ? true : false;
    } /* }}} */
    
    /**
     * @brief 连码_二中特
     *      每单多注，每注2球，
     * @access public/protected 
     * @param 
     * @return 
     */
    public function bet_lm_2zt(& $bet = array()) /* {{{ */
    {
        return $this->bet_lm_2qz($bet);
    } /* }}} */
    
    /**
     * @brief 连码_特串
     *      每单多注，每注2球，
     * @access public/protected 
     * @param 
     * @return 
     */
    public function bet_lm_tc(& $bet = array()) /* {{{ */
    {
        return $this->bet_lm_2qz($bet);
    } /* }}} */
    
    /**
     * @brief 连肖_二肖连
     *      每单同赔率可多注，不同赔率需分单，每注2球，每单最多6球
     *      高赔率与低赔率组合为一注时，以低赔率为当注标准赔率
     * @access public/protected 
     * @param 
     * @return 
     */
    public function bet_lx_2xl(& $bet = array(), $m = 2) /* {{{ */
    {
        /* 取球号 */
        $balls = get_balls($bet['contents'], $this->config_balls['sx']);
        /* 检测球号 */
        if ($balls == false) {
            return false;
        }
        /* 组合下注量 */
        $n = count($balls[0]);
        if ($n < $m || $n > 6) {
        //if ($n != $m) {}
            return false;
        }
        $c = C($n, $m);
        $price_sum = $bet['price'] * $bet['counts'];
        return ($c == $bet['counts'] && $price_sum == $bet['price_sum']) ? true : false;
    } /* }}} */
    
    /**
     * @brief 连肖_三肖连
     *      每单同赔率可多注，不同赔率需分单，每注3球，每单最多6球
     *      高赔率与低赔率组合为一注时，以低赔率为当注标准赔率
     * @access public/protected 
     * @param 
     * @return 
     */
    public function bet_lx_3xl(& $bet = array()) /* {{{ */
    {
        return $this->bet_lx_2xl($bet, 3);
    } /* }}} */
    
    /**
     * @brief 连肖_四肖连
     *      每单同赔率可多注，不同赔率需分单，每注4球，每单最多6球
     *      高赔率与低赔率组合为一注时，以低赔率为当注标准赔率
     * @access public/protected 
     * @param 
     * @return 
     */
    public function bet_lx_4xl(& $bet = array()) /* {{{ */
    {
        return $this->bet_lx_2xl($bet, 4);
    } /* }}} */
    
    /**
     * @brief 连肖_五肖连
     *      每单同赔率可多注，不同赔率需分单，每注5球，每单最多6球
     *      高赔率与低赔率组合为一注时，以低赔率为当注标准赔率
     * @access public/protected 
     * @param 
     * @return 
     */
    public function bet_lx_5xl(& $bet = array()) /* {{{ */
    {
        return $this->bet_lx_2xl($bet, 5);
    } /* }}} */
    
    /**
     * @brief 连尾_二尾碰
     *      每单同赔率可多注，不同赔率需分单，每注2球，每单最多6球
     *      高赔率与低赔率组合为一注时，以高赔率为当注标准赔率
     * @access public/protected 
     * @param 
     * @return 
     */
    public function bet_lw_2wp(& $bet = array(), $m = 2) /* {{{ */
    {
        /* 取球号 */
        $balls = get_balls($bet['contents'], $this->config_balls['ws']);
        /* 检测球号 */
        if ($balls == false) {
            return false;
        }
        /* 组合下注量 */
        $n = count($balls[0]);
        if ($n < $m || $n > 6) {
            return false;
        }
        $c = C($n, $m);
        $price_sum = $bet['price'] * $bet['counts'];
        return ($c == $bet['counts'] && $price_sum == $bet['price_sum']) ? true : false;
    } /* }}} */
    
    /**
     * @brief 连尾_三尾碰
     *      每单同赔率可多注，不同赔率需分单，每注3球，每单最多6球
     *      高赔率与低赔率组合为一注时，以高赔率为当注标准赔率
     * @access public/protected 
     * @param 
     * @return 
     */
    public function bet_lw_3wp(& $bet = array()) /* {{{ */
    {
        return $this->bet_lw_2wp($bet, 3);
    } /* }}} */
    
    /**
     * @brief 连尾_四尾碰
     *      每单同赔率可多注，不同赔率需分单，每注4球，每单最多6球
     *      高赔率与低赔率组合为一注时，以高赔率为当注标准赔率
     * @access public/protected 
     * @param 
     * @return 
     */
    public function bet_lw_4wp(& $bet = array()) /* {{{ */
    {
        return $this->bet_lw_2wp($bet, 4);
    } /* }}} */
    
    /**
     * @brief 连尾_五尾碰
     *      每单同赔率可多注，不同赔率需分单，每注5球，每单最多6球
     *      高赔率与低赔率组合为一注时，以高赔率为当注标准赔率
     * @access public/protected 
     * @param 
     * @return 
     */
    public function bet_lw_5wp(& $bet = array()) /* {{{ */
    {
        return $this->bet_lw_2wp($bet, 5);
    } /* }}} */
    
    /**
     * @brief 自选不中_五不中
     *      每单多注，每注5球，每单最多12球
     * @access public/protected 
     * @param 
     * @return 
     */
    public function bet_zxbz_5bz(& $bet = array(), $m = 5, $limit = 10) /* {{{ */
    {
        /* 取球号 */
        $balls = get_balls($bet['contents'], $this->config_balls['base']);
        /* 检测球号 */
        if ($balls == false) {
            return false;
        }
        /* 组合下注量 */
        $n = count($balls[0]);
        if ($n < $m || $n > $limit) {
            return false;
        }
        $c = C($n, $m);
        $price_sum = $bet['price'] * $bet['counts'];
        return ($c == $bet['counts'] && $price_sum == $bet['price_sum']) ? true : false;
    } /* }}} */
    
    /**
     * @brief 自选不中_六不中
     *      每单多注，每注6球，每单最多12球
     * @access public/protected 
     * @param 
     * @return 
     */
    public function bet_zxbz_6bz(& $bet = array()) /* {{{ */
    {
        return $this->bet_zxbz_5bz($bet, 6);
    } /* }}} */
    
    /**
     * @brief 自选不中_七不中
     *      每单多注，每注7球，每单最多12球
     * @access public/protected 
     * @param 
     * @return 
     */
    public function bet_zxbz_7bz(& $bet = array()) /* {{{ */
    {
        return $this->bet_zxbz_5bz($bet, 7);
    } /* }}} */
    
    /**
     * @brief 自选不中_八不中
     *      每单多注，每注8球，每单最多12球
     * @access public/protected 
     * @param 
     * @return 
     */
    public function bet_zxbz_8bz(& $bet = array()) /* {{{ */
    {
        return $this->bet_zxbz_5bz($bet, 8, 11);
    } /* }}} */
    
    /**
     * @brief 自选不中_九不中
     *      每单多注，每注9球，每单最多12球
     * @access public/protected 
     * @param 
     * @return 
     */
    public function bet_zxbz_9bz(& $bet = array()) /* {{{ */
    {
        return $this->bet_zxbz_5bz($bet, 9, 12);
    } /* }}} */
    
    /**
     * @brief 自选不中_十不中
     *      每单多注，每注10球，每单最多12球
     * @access public/protected 
     * @param 
     * @return 
     */
    public function bet_zxbz_10bz(& $bet = array()) /* {{{ */
    {
        return $this->bet_zxbz_5bz($bet, 10, 13);
    } /* }}} */
    
    /**
     * @brief 自选不中_十一不中
     *      每单多注，每注11球，每单最多14球
     * @access public/protected 
     * @param 
     * @return 
     */
    public function bet_zxbz_11bz(& $bet = array()) /* {{{ */
    {
        return $this->bet_zxbz_5bz($bet, 11, 13);
    } /* }}} */
    
    /**
     * @brief 自选不中_十二不中
     *      每单多注，每注12球，每单最多14球
     * @access public/protected 
     * @param 
     * @return 
     */
    public function bet_zxbz_12bz(& $bet = array()) /* {{{ */
    {
        return $this->bet_zxbz_5bz($bet, 12, 14);
    } /* }}} */
    
    /**
     * @brief 生肖_十二肖
     *      每单1注，每注1球，每单最多1球
     * @access public/protected 
     * @param 
     * @return 
     */
    public function bet_sx_12x(& $bet = array()) /* {{{ */
    {
        /* 取球号 */
        $balls = get_balls($bet['contents'], $this->config_balls['sx']);
        /* 检测球号 */
        if ($balls == false) {
            return false;
        }
        /* 组合下注量 */
        $c = count($balls[0]);
        if ($c != 1) {
            return false;
        }
        $price_sum = $bet['price'] * $bet['counts'];
        return (1 == $bet['counts'] && $price_sum == $bet['price_sum']) ? true : false;
    } /* }}} */
    
    /**
     * @brief 生肖_一肖
     *      每单1注，每注1球，每单最多1球
     * @access public/protected 
     * @param 
     * @return 
     */
    public function bet_sx_1x(& $bet = array()) /* {{{ */
    {
        return $this->bet_sx_12x($bet);
    } /* }}} */
    
    /**
     * @brief 生肖_正肖
     *      每单1注，每注1球，每单最多1球
     * @access public/protected 
     * @param 
     * @return 
     */
    public function bet_sx_zx(& $bet = array()) /* {{{ */
    {
        return $this->bet_sx_12x($bet);
    } /* }}} */
    
    /**
     * @brief 生肖_总肖
     *      每单1注，每注1球，每单最多1球
     * @access public/protected 
     * @param 
     * @return 
     */
    public function bet_sx_zhongx(& $bet = array()) /* {{{ */
    {
        /* 取球号 */
        $balls = get_balls($bet['contents'], $this->config_balls['zhx']);
        /* 检测球号 */
        if ($balls == false) {
            return false;
        }
        /* 组合下注量 */
        $c = count($balls[0]);
        if ($c != 1) {
            return false;
        }
        $price_sum = $bet['price'] * $bet['counts'];
        return (1 == $bet['counts'] && $price_sum == $bet['price_sum']) ? true : false;
    } /* }}} */
    
    /**
     * @brief 合肖_中
     *      每单1注，每注1~11球，
     * @access public/protected 
     * @param 
     * @return 
     */
    public function bet_hx_z(& $bet = array()) /* {{{ */
    {
        /* 取球号 */
        $balls = get_balls($bet['contents'], $this->config_balls['sx']);
        /* 检测球号 */
        if ($balls == false) {
            return false;
        }
        /* 组合下注量 */
        $c = count($balls[0]);
        if ($c < 1 || $c > 11) {
            return false;
        }
        $price_sum = $bet['price'] * $bet['counts'];
        return (1 == $bet['counts'] && $price_sum == $bet['price_sum']) ? true : false;
    } /* }}} */
    
    /**
     * @brief 合肖_不中
     *      每单1注，每注1~10球，
     * @access public/protected 
     * @param 
     * @return 
     */
    public function bet_hx_bz(& $bet = array()) /* {{{ */
    {
        /* 取球号 */
        $balls = get_balls($bet['contents'], $this->config_balls['sx']);
        /* 检测球号 */
        if ($balls == false) {
            return false;
        }
        /* 组合下注量 */
        $c = count($balls[0]);
        if ($c < 1 || $c > 10) {
            return false;
        }
        $price_sum = $bet['price'] * $bet['counts'];
        return (1 == $bet['counts'] && $price_sum == $bet['price_sum']) ? true : false;
    } /* }}} */
    
    /**
     * @brief 色波_3色波
     *      每单1注，每注1球，
     * @access public/protected 
     * @param 
     * @return 
     */
    public function bet_sb_3sb(& $bet = array(), $ball = 'sb') /* {{{ */
    {
        /* 取球号 */
        $balls = get_balls($bet['contents'], $this->config_balls[$ball]);
        /* 检测球号 */
        if ($balls == false) {
            return false;
        }
        /* 组合下注量 */
        $c = count($balls[0]);
        if ($c != 1) {
            return false;
        }
        $price_sum = $bet['price'] * $bet['counts'];
        return (1 == $bet['counts'] && $price_sum == $bet['price_sum']) ? true : false;
    } /* }}} */
    
    /**
     * @brief 色波_半波
     *      每单1注，每注1球，
     * @access public/protected 
     * @param 
     * @return 
     */
    public function bet_sb_bb(& $bet = array()) /* {{{ */
    {
        return $this->bet_sb_3sb($bet, 'bb');
    } /* }}} */
    
    /**
     * @brief 色波_半半波
     *      每单1注，每注1球，
     * @access public/protected 
     * @param 
     * @return 
     */
    public function bet_sb_bbb(& $bet = array()) /* {{{ */
    {
        return $this->bet_sb_3sb($bet, 'bbb');
    } /* }}} */
    
    /**
     * @brief 色波_7色波
     *      每单1注，每注1球，
     * @access public/protected 
     * @param 
     * @return 
     */
    public function bet_sb_7sb(& $bet = array()) /* {{{ */
    {
        return $this->bet_sb_3sb($bet, '7sb');
    } /* }}} */
    
    /**
     * @brief 尾数_头尾数
     *      每单1注，每注1球，
     * @access public/protected 
     * @param 
     * @return 
     */
    public function bet_ws_tws(& $bet = array()) /* {{{ */
    {
        return $this->bet_sb_3sb($bet, 'tws');
    } /* }}} */
    
    /**
     * @brief 尾数_正特尾数
     *      每单1注，每注1球，
     * @access public/protected 
     * @param 
     * @return 
     */
    public function bet_ws_ztws(& $bet = array()) /* {{{ */
    {
        return $this->bet_sb_3sb($bet, 'ws');
    } /* }}} */
    
    /**
     * @brief 七码五行_七码
     *      每单1注，每注1球，
     * @access public/protected 
     * @param 
     * @return 
     */
    public function bet_wx_7m(& $bet = array()) /* {{{ */
    {
        return $this->bet_sb_3sb($bet, '7m');
    } /* }}} */
    
    /**
     * @brief 七码五行_五行
     *      每单1注，每注1球，
     * @access public/protected 
     * @param 
     * @return 
     */
    public function bet_wx_5x(& $bet = array()) /* {{{ */
    {
        return $this->bet_sb_3sb($bet, '5x');
    } /* }}} */
    
    /**
     * @brief 中一_五中一
     *      每单多注，每注5球，每单最多12球
     * @access public/protected 
     * @param 
     * @return 
     */
    public function bet_z1_5z1(& $bet = array(), $m = 5, $limit = 12) /* {{{ */
    {
        /* 取球号 */
        $balls = get_balls($bet['contents'], $this->config_balls['base']);
        /* 检测球号 */
        if ($balls == false) {
            return false;
        }
        /* 组合下注量 */
        $n = count($balls[0]);
        if ($n < $m || $n > $limit) {
            return false;
        }
        $c = C($n, $m);
        $price_sum = $bet['price'] * $bet['counts'];
        return ($c == $bet['counts'] && $price_sum == $bet['price_sum']) ? true : false;
    } /* }}} */
    
    /**
     * @brief 中一_六中一
     *      每单多注，每注6球，每单最多12球
     * @access public/protected 
     * @param 
     * @return 
     */
    public function bet_z1_6z1(& $bet = array()) /* {{{ */
    {
        return $this->bet_z1_5z1($bet, 6);
    } /* }}} */
    
    /**
     * @brief 中一_七中一
     *      每单多注，每注7球，每单最多12球
     * @access public/protected 
     * @param 
     * @return 
     */
    public function bet_z1_7z1(& $bet = array()) /* {{{ */
    {
        return $this->bet_z1_5z1($bet, 7);
    } /* }}} */
    
    /**
     * @brief 中一_八中一
     *      每单多注，每注8球，每单最多12球
     * @access public/protected 
     * @param 
     * @return 
     */
    public function bet_z1_8z1(& $bet = array()) /* {{{ */
    {
        return $this->bet_z1_5z1($bet, 8);
    } /* }}} */
    
    /**
     * @brief 中一_九中一
     *      每单多注，每注9球，每单最多12球
     * @access public/protected 
     * @param 
     * @return 
     */
    public function bet_z1_9z1(& $bet = array()) /* {{{ */
    {
        return $this->bet_z1_5z1($bet, 9);
    } /* }}} */
    
    /**
     * @brief 中一_十中一
     *      每单多注，每注10球，每单最多12球
     * @access public/protected 
     * @param 
     * @return 
     */
    public function bet_z1_10z1(& $bet = array()) /* {{{ */
    {
        return $this->bet_z1_5z1($bet, 10, 13);
    } /* }}} */
}

/* end file */
