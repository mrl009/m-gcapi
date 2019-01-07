<?php
/**
 * Created by PhpStorm.
 * User: mr.xiaolin
 * Date: 2018/5/22
 * Time: 上午9:27
 */

class Reward_day_count extends GC_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Reward_day_model', 'core');
    }

    /**
     * 每日加奖日结表
     * @param string $sn 站点sn
     */
    public function count($sn = '')
    {
        empty($sn) && die('请添加sn');
        //初始化站点
        $this->core->init($sn);
        //日结
        $this->core->day_count($sn);
    }

    public function count_test($sn = '', $day)
    {
        empty($sn) && die('请添加sn');
        //初始化站点
        $this->core->init($sn);
        //日结
        $this->core->day_count_test($sn, $day);
    }
}