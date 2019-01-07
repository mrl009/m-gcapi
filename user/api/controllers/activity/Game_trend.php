<?php

/**
 * Created by PhpStorm.
 * User: dragon
 * Date: 2017/4/24
 * Time: 17:39
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class Game_trend extends GC_Controller
{

    private $sb = [];
    private $sx = [];

    public function __construct()
    {

        parent::__construct();
        $this->load->library('Lunar');
        $this->load->model('activity/Game_trend_model', 'core');
        $this->sb = array('1' => 'red', '2' => 'red', '7' => 'red', '8' => 'red', '12' => 'red', '13' => 'red', '18' => 'red', '19' => 'red',
            '23' => 'red', '24' => 'red', '29' => 'red', '30' => 'red', '34' => 'red', '35' => 'red', '40' => 'red', '45' => 'red', '46' => 'red',
            '3' => 'blue', '4' => 'blue', '9' => 'blue', '10' => 'blue', '14' => 'blue', '15' => 'blue', '20' => 'blue', '25' => 'blue',
            '26' => 'blue', '31' => 'blue', '36' => 'blue', '37' => 'blue', '41' => 'blue', '42' => 'blue', '47' => 'blue', '48' => 'blue',
            '5' => 'green', '6' => 'green', '11' => 'green', '16' => 'green', '17' => 'green', '21' => 'green', '22' => 'green', '27' => 'green',
            '28' => 'green', '32' => 'green', '33' => 'green', '38' => 'green', '39' => 'green', '43' => 'green', '44' => 'green', '49' => 'green');
    }

    public function get_game_trend_list()
    {
        $gid = (int)$this->G('gid');
        if($gid>50){
            $gid = gid_tran($gid);
        }

        if ($gid == 1 || $gid == 2 || $gid == 3) {
            $todayBegin = mktime(0, 0, 0, date("m") - 1, date("d"), date("Y"));
            $todayEnd = mktime(0, 0, 0, date("m"), date("d"), date("Y"));
        } else {
            $todayBegin = mktime(0, 0, 0, date("m"), date("d"), date("Y"));
            $todayEnd = mktime(0, 0, 0, date("m"), date("d") + 1, date("Y"));
        }

        $start = date("Y-m-d H:i:s", $todayBegin - (3600*24));
        $end = date("Y-m-d H:i:s", $todayEnd);


        $data = $this->core->game_trend_list($gid, $start, $end);

        if (!empty($data)) {
            $rs['rows'] = $this->data_formate($data, $gid);
            $this->return_json(OK, $rs);
        }
    }

    protected function hong_kong_transfer($data)
    {
        $result = 0;

        return $result;
    }

    public function h5_get_game_trend()
    {
        $url = null;
        $gid = (int)$this->G('gid');
        if($gid>50){
            $gid = gid_tran($gid);
        }
        $result['gid'] = $gid;
        $result['sn'] = $this->_sn;

        switch ($gid) {
            case 1:
            case 2:
            case 24:
            case 25:
                $result['balls'] = 10;
                $this->load->view('h5/record_three_d_lottery', $result);
                break;

            case 12:
            case 13:
            case 14:
            case 15:
            case 16:
            case 17:
                $result['balls'] = 6;
                $this->load->view('h5/record_fast_three', $result);
                break;
            case 3:
            case 4:
                $this->load->view('h5/record_hong_kong_lottery', $result);
                break;
            case 6:
            case 7:
            case 8:
            case 9:
            case 10:
            case 11:
                $result['balls'] = 10;
                $this->load->view('h5/record_every_color', $result);
                break;
            case 18:
            case 19:
            case 20:
            case 21:
            case 22:
                $result['balls'] = 11;
                $this->load->view('h5/record_eleven_select_five', $result);
                break;
//            case 24:
//            case 25:
//            $result['balls'] = 1;
//            $this->load->view('h5/record_arrange_three', $result);
//                break;

            case 26:
            case 27:
            case 29:
            case 30:
                $result['balls'] = 10;
                $this->load->view('h5/record_pk_ten', $result);
                break;
            case 73:
            case 74:
                $result['balls'] = 8;
                $this->load->view('h5/record_kl10', $result);
                break;
            default:
                break;

        }


    }

    private function data_formate($data, $gid)
    {
        if ($gid == 3 or $gid == 4) {

            foreach ($data as $key => $value) {
                $number = null;
           //     $data[$key]['kithe'] = substr($value['kithe'], -3, 3);

                $sx = null;
                $sx = null;

                $d=strtotime($value['open_time']);
                $year = date('Y',$d);
                $month = date('m',$d);
                $day = date('d',$d);
                $this->sx = $this->cal_sx($year,$month,$day);

                $sx = array_intersect_key($this->sx,array_flip(explode(',',$value['number'])));
                $sb = array_intersect_key($this->sb,array_flip(explode(',',$value['number'])));

                $valueData = explode(',',$value['number']);

                foreach ($valueData as $k => $v){

                    foreach ($sx as $k1 => $v1){
                        if($k1 == $v){
                            $number[$k]['num'] = $v;
                            $number[$k]['sx'] = $v1;
                        }
                    }

                    foreach ($sb as $k2 => $v2){
                        if($k2 == $v){
                            $number[$k]['sb'] = $v2;
                        }
                    }

                }
                $data[$key]['number_arr'] = $number;
            }
        } else {
            foreach ($data as $key => $value) {
               // $data[$key]['kithe'] = substr($value['kithe'], -3, 3);
                $data[$key]['number_arr'] = explode(',', $value['number']);
            }
        }

        return $data;

    }

    protected function cal_sx($year,$month,$day){
        $zodiac = array('鼠', '牛', '虎', '兔', '龙', '蛇', '马', '羊', '猴', '鸡', '狗', '猪');
        $sx_code = array('鼠' => 129, '牛' => 130, '虎' => 131, '兔' => 132,
            '龙' => 133, '蛇' => 134, '马' => 135, '羊' => 136,
            '猴' => 137, '鸡' => 138, '狗' => 139, '猪' => 140
        );
        $key = array('1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12', '13', '14', '15', '16', '17', '18', '19', '20',
            '21', '22', '23', '24', '25', '26', '27', '28', '29', '30', '31', '32', '33', '34', '35', '36', '37', '38', '39', '40',
            '41', '42', '43', '44', '45', '46', '47', '48', '49');
        $lunar = new Lunar();
        $lunar_day = $lunar->convertSolarToLunar($year, $month, $day);

        $sx = $lunar_day[6];
        $sx_list = array();
        $s = array_keys($zodiac, $sx);
        $s = current($s);
        for ($i = 0; $i <= 11; $i++) {
            if ($s < 0) {
                $s = 11;
            }
            $current_sx = $zodiac[$s];
            $s--;
            for ($b = $i; $b < 49; $b += 12) {
                $sx_list[$key[$b]] = $current_sx;
            }
        }
        return $sx_list;
    }
}
