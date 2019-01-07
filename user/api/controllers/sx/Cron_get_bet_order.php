<?php

if (!is_cli()) {
//    exit('No direct script access allowed');
}

/**
 * Class Get_bet_order
 * task抓取注单类
 */
class Cron_get_bet_order extends GC_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library('BaseApi');
    }

    /**
     *  dg定时任务
     */
    public function get_dg_bet_record()
    {
        $this->load->library('get_bet_record');
        $platform_name = 'dg';
        //查出所有站点，遍历站点拉取注单
        $result = $this->get_all_site();

        foreach ($result as $k => $row) {
            $sn = $row['sn'];
            $game_api = BaseApi::getinstance($platform_name, 'game', $sn);
            $data = $game_api->getReport(['method' => 'getReport']);
            if (isset($data['list'])) {
                if ($this->get_bet_record->create_dg_bet_record($data, $game_api)) {
                    $message = '当前时间:' . date('Y-m-d H:i:s') . ',站点' . $sn . '拉取数据完毕';
                } else {
                    $message = '当前时间:' . date('Y-m-d H:i:s') . ',站点' . $sn . '拉取失败';
                }
            } else {
                $message = '当前时间:' . date('Y-m-d H:i:s') . ',站点' . $sn . '尚无注单';
            }
            wlog(APPPATH . 'logs/' . $platform_name . '/' . date('Y-m-d') . '-dg.log', $message);
            echo $message;
        }
    }

    /**
     * ag定时任务
     */
    public function get_ag_bet_record($type)
    {
        $platform_name = 'ag';
        $g_type = empty($type) ? [] : [$type];
        $this->load->library('ag/FtpReport', '', 'report');
        $this->report->get_bet_order($g_type);
        wlog(APPPATH . 'logs/' . $platform_name . '/' . date('Y-m-d') . '-ag.log', __FUNCTION__ . ' run complete ,等待下一轮拉取数据');
        echo '操作成功';
    }

    /**
     * lebo定时任务
     */
    public function get_lebo_bet_record($interval = 10)
    {
        $platform_name = 'lebo';
        //查出所有站点，遍历站点拉取注单
        $result = $this->get_all_site();
        $this->load->model('sx/lebo/Game_order_model');
        foreach ($result as $k => $row) {
            $sn = $row['sn'];
            $game_api = BaseApi::getinstance($platform_name, 'game', $sn);
            $data = $game_api->get_bet_data($interval);
            if (!empty($data['result'])) {
                $list_id = $this->Game_order_model->inset_order($data['result'],$sn);
                if ($list_id) {
                    $message = '当前时间:' . date('Y-m-d H:i:s') . ',站点' . $sn . '拉取数据完毕';
                } else {
                    $message = '当前时间:' . date('Y-m-d H:i:s') . ',站点' . $sn . '拉取失败';
                }
            } else {
                $message = '当前时间:' . date('Y-m-d H:i:s') . ',站点' . $sn . '尚无注单';
            }

            wlog(APPPATH . 'logs/' . $platform_name . '/' . date('Y-m-d') . '-lebo.log', $message);
            echo $message;
        }
    }

    /**
     * pt定时任务
     */
    public function get_pt_bet_record()
    {
        $platform_name = 'pt';
        $this->load->model('sx/pt/Game_order_model', 'order');
        $game_api = BaseApi::getinstance($platform_name, 'game');

        $time = time();
        $prev_hour = date('Y-m-d%20H.', $time - 3600);
        $next_hour = date('Y-m-d%20H.', $time + 3600);
        $curent_hour = date('Y-m-d%20H.', $time);
        $time_between = [
            $prev_hour . '50.00' => $curent_hour . '00.00',
            $curent_hour . '00.00' => $curent_hour . '10.00',
            $curent_hour . '10.00' => $curent_hour . '20.00',
            $curent_hour . '20.00' => $curent_hour . '30.00',
            $curent_hour . '30.00' => $curent_hour . '40.00',
            $curent_hour . '40.00' => $curent_hour . '50.00',
            $curent_hour . '50.00' => $next_hour . '00.00',
        ];

        foreach ($time_between as $start => $end) {
            $data = $game_api->get_report($start, $end);
            if ($data['Code'] == 0 && !empty($data['Result'])) {
                if ($data['Pagination']['TotalPage'] == 1) {
                    $this->order->inset_order($data['Result']);
                } else {
                    for ($i = 1; $i <= $data['Pagination']['TotalPage']; $i += 1) {
                        $list = $game_api->get_report($start, $end, $i);
                        $this->order->inset_order($list['Result']);
                    }
                }
            }
        }

        wlog(APPPATH . 'logs/' . $platform_name . '/' . date('Y-m-d') . '-pt.log', '抓取注单成功,等待下一轮拉取数据');
        echo '操作成功';
    }
    /*mg定時任務*/
    public function get_mg_bet_record()
    {
        $result = $this->get_all_site();
        $platform_name='mg';
        $this->load->model('sx/mg/Game_order_model', 'order');
        foreach ($result as $k => $row) {
            $sn = $row['sn'];
            $user_api = BaseApi::getinstance($platform_name, 'user', $sn);
            $row_id=$this->order->get_lastRowId($sn);
            $data['LastRowId']=$row_id;
            $rs = $user_api->GetSpinBySpinData( $data,  5 );
            if(isset($rs['Status'])&&$rs['Status']['ErrorName'] == 'SUCCEED'){
                 $data=$rs['Result'];
                 $res = $this->order->inset_order($data, $platform_name, $sn);
                $message = '当前时间:' . date('Y-m-d H:i:s') . ',站点' . $sn . '拉取数据完毕';
                echo $message;
            }else{
                $message = '当前时间:' . date('Y-m-d H:i:s') . ',站点' . $sn . '尚无注单';
                echo $message;
            }
        }
    }
    /**
     * kyqp定时任务
     * @param $interval 拉取时间间隔
     */
    public function get_ky_bet_order($interval=5)
    {
        $result = $this->get_all_site();
        $platform_name='ky';
        $this->load->model('sx/ky/Game_order_model', 'order');
        $time=$this->getMillisecond();
        $data=[];
        $data['s']=6;
        $data['startTime']=$time-1000*60*$interval;
        $data['endTime']=$time;
        foreach ($result as $k => $row) {
            $sn = $row['sn'];
            $user_api = BaseApi::getinstance($platform_name, 'Kyuser', $sn);
            $rs = $user_api->get_api_data($data, 2, $sn);
            if ($rs['d']['code'] == 0) {
                $get_data = $rs['d']['list'];
                $res = $this->order->inset_order($get_data, $platform_name);
                $message = '当前时间:' . date('Y-m-d H:i:s') . ',站点' . $sn . '拉取数据完毕';
                echo $message;
            }else{
                $message = '当前时间:' . date('Y-m-d H:i:s') . ',站点' . $sn . '尚无注单';
                echo $message;
            }
        }
    }
    public function get_sn()
    {
        $this->load->model('MY_Model','M');
        $this->M->redisP_select(REDIS_PUBLIC);
        $snInfo = $this->M->redisP_hget('dsn', $this->_sn);
        if (empty($snInfo)) {
            $this->return_json(E_ARGS, '请求头出错');
        }
        $snInfo = json_decode($snInfo, true);
        return isset($snInfo['sn']) ? $snInfo['sn'] : '';
    }
    /**
     * 获取当前时间的毫秒
     */
    public function getMillisecond()
    {
        list($t1, $t2) = explode(' ', microtime());
        return $t2 .  ceil( ($t1 * 1000) );
    }
    /**
     * 获取所有站点
     */
    public function get_all_site()
    {
        $this->load->model('sx/set_model');
        return $this->set_model->get_all_site();
    }
}
