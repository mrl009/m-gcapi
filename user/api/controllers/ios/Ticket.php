<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * 车票管理
 *
 * @file        user/api/controllers/ios/ticket
 * @package     user/api/controllers/ios
 * @author      ssm
 * @version     v1.0 2017/07/12
 * @created 	2017/07/12
 */
class Ticket extends GC_Controller
{
    /**
     * 城市列表
     */
    protected $city_arr = [
        '深圳客运站'=>[96,120],'广州客运站'=>[88,140],'汕头客运站'=>[160,200],'东莞客运站'=>[75,110],'汕尾客运站'=>[120,180],'珠海客运站'=>[90,130],'湛江客运站'=>[140,200],'潮州客运站'=>[200,240]];

    /**
     * 车票列表
     */
    protected $ticket_arr = [
        ['from'=>'广州','destined'=>'杭州',
        'starttime'=>'19:00','endtime'=>'22:00',
        'pricesum'=>'322','train'=>'D2992',
        'data'=>'2017年7月10日','seat'=>'普通车',
        'opt_price'=>[1.2,1.4,1.8],
        'opt_seat'=>['高速车','豪华车','商务车']]
    ];


    /**
     * 查询汽车票
     *
     * @access public
     * @return json
     */
    public function query()
    {
        $from = $this->G('from');
        $destined = $this->G('destined');
        $startdate = $this->G('startdate');
        $time = strtotime($startdate);
        $ticket_arr = [];
        $ticket = $this->ticket_arr[0];
        for ($i=1; $i < 20; $i++) {
            $t = $ticket;
            $t['from'] = $from;
            $t['destined'] = $destined;
            $t['train'] = 'K'.rand(1000, 9999);
            $t['pricesum'] = $this->city_arr[$destined][0]+rand(1, 40);
            $diff_time = rand(120, 1440);
            $start_time = $diff_time+$this->city_arr[$destined][1]+rand(1, 40);
            $t['starttime'] = date('H:i', $time+($diff_time*60));
            $t['endtime'] = date('H:i', $time+($start_time*60));
            $t['data'] = date('Y-m-d',$time);
            if ($diff_time > $start_time) {
                $t['data'] = date('Y-m-d', $time+86440);
            }
            $ticket_arr[] = $t;
        }
        foreach ($ticket_arr as $k=>$v) {
            $tag1[] = strtotime($v['data']);
        }
        array_multisort($tag1, SORT_DESC, $ticket_arr);
        $this->return_json(OK, ['rows'=>$ticket_arr]);
    }

    /**
     * 查询城市
     *
     * @access public
     * @return json
     */
    public function citys()
    {
        $this->return_json(OK, array_keys($this->city_arr));
    }
}
