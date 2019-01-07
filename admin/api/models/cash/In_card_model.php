<?php
/**
 * @模块   优惠卡入款
 * @版本   Version 1.0.0
 * @日期   2017-03-31
 * super
 */

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class In_card_model extends MY_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 计算出一个年月数组(编号前四列表)
     * @return array|mixed
     */
    public function calculate_Top4No_list()
    {
        $top4_list = $this->redis_get('card_top4list');
        if (!empty($top4_list) && date('d')=='01') {
            $this->redis_del('card_top4list');
        } elseif (!empty($top4_list)) {
            $top4_list = json_decode($top4_list, true);
            return $top4_list;
        }
        $yeartop = date('y', time());
        $moontop = date('m', time());
        $cha = (int)$yeartop*12+(int)$moontop - 204;//207=17*12
        $mtable =  array();
        $y = 17;
        $m = 1;
        for ($d=0;$d<$cha;$d++) {
            if ($d%12==0 && $d!=0) {
                $y++;
                $m = 1;
            }
            if ($m<10) {
                $mtable[] = $y.'0'.$m;
            } else {
                $mtable[] = $y.$m;
            }
            $m++;
        }
        $this->redis_set('card_top4list', json_encode($mtable));
        return $mtable;
    }
}
