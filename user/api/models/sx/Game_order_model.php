<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Game_order_model extends GC_Model
{
    public function __construct()
    {
        $this->select_db('shixun');
    }

    /**
     * 获取注单数据
     * @param $data
     * @return array
     */
    public function bet_record($data)
    {
        $time_field = $this->get_time_field($data['platform']);
        $table = 'gc_' . $data['platform'] . '_game_order' . $data['m'];
        //总数
        if ($data['order_num']) {
            $sql_format = 'SELECT %s FROM `%s` WHERE `sn` = \'%s\' AND `snuid` = \'%s\' AND `bill_no` >= \'%s\'';
            $sql = sprintf($sql_format, 'COUNT(1)', $table, $data['sn'], $data['snuid'], $data['order_num']);
        } else {
            $sql_format = 'SELECT %s FROM `%s` WHERE `sn` = \'%s\' AND `snuid` = \'%s\' AND %s >= \'%s\' AND %s < \'%s\'';
            $sql = sprintf($sql_format, 'COUNT(1)', $table, $data['sn'], $data['snuid'], $time_field, $data['start'], $time_field, $data['end']);
        }
        $count = current($this->db->query($sql)->row_array());
        //列表
        $sql_format .= ' LIMIT %d,%d';
        $filed = $this->get_field($data['platform']);
        $sql = sprintf($sql_format, $filed, $table, $data['sn'], $data['snuid'], $time_field, $data['start'], $time_field, $data['end'], $data['page'] * $data['num'], $data['num']);
        $rows = $this->db->query($sql)->result_array();
        $rows = empty($rows) ? [] : $this->format($data['platform'], $rows);
        return ['total' => $count, 'rows' => $rows];
    }

    private function get_time_field($platform)
    {
        switch ($platform) {
            case 'dg' :
                $filed = 'betTime';
                break;
            case 'ag' :
                $filed = 'bet_time';
                break;
            case 'lebo' :
                $filed = 'start_time';
                break;
            case 'pt' :
                $filed = 'game_date';
                break;
            case 'agh' :
                $filed = 'creation_time';
                break;
            case 'ky':
                $filed = 'bet_time';
                break;
            default:
                $filed = 'bet_time';
        }
        return $filed;
    }

    private function get_field($platform)
    {
        $rs = '';
        if ($platform == 'ag') {
            $rs = 'bet_time,bill_no as order_num,game_code as game,bet_amount,valid_betamount as valid_bet_amount,netamount as win_or_lose';
        } elseif ($platform == 'dg') {
            $rs = 'betTime as bet_time,sx_id as order_num,betPoints as bet_amount,availableBet as valid_bet_amount,winOrLoss as win_or_lose,GameType,tableId,lobbyId';
        } elseif ($platform == 'ky') {
            $rs = 'bet_time,game_code as order_num,game_code as game,bet_amount,valid_betamount as valid_bet_amount,netamount as win_or_lose';
        } elseif ($platform == 'lebo'){
            $rs = 'start_time as bet_time,round_no as order_num,game_id as game,total_bet_score as bet_amount,valid_bet_score_total as valid_bet_amount,total_win_score as win_or_lose';
        }
        return $rs;
    }

    /**
     * DG游戏类型数字转汉字
     * @param $type
     * @return mixed
     */
    private function gameType($type)
    {
        $game = array(
            '1' => '百家乐', '3' => '龙虎', '4' => '轮盘',
            '5' => '骰子', '7' => '牛牛', '8' => '竞咪百家乐'
        );
        return $game[$type];
    }

    /**
     * DG桌号数字转汉字
     * @param $num
     * @return mixed
     */
    private function dg_zhuo($num)
    {
        $zhuo = array(
            '10101' => 'DG01', '10102' => 'DG02', '10103' => 'DG03', '10105' => 'DG05',
            '10106' => 'DG06', '10107' => 'DG07', '10301' => 'DG12', '10401' => 'DG13',
            '10501' => 'DG15', '10701' => 'DG16', '20801' => 'DG08', '20802' => 'DG09',
            '20803' => 'DG10', '20805' => 'DG11'
        );
        return $zhuo[$num];
    }

    /**
     * DG注单游戏大厅号
     * @param $lobbyid
     * @return mixed
     */
    private function dating($lobbyid)
    {
        $arr = array(
            '1' => '旗舰店', '2' => '竞咪厅'
        );
        return $arr[$lobbyid];
    }

    private function format($platform, $data)
    {
        foreach ($data as $k => $v) {
            if ($platform == 'dg') {
                $data[$k]['win_or_lose'] = $v['win_or_lose'] - $v['valid_bet_amount'];
                $data[$k]['game'] = $this->dating($v['lobbyId']);
                $data[$k]['table'] = $this->gameType($v['GameType']) . ' ' . $this->dg_zhuo($v['tableId']);
                unset($data[$k]['lobbyId']);
                unset($data[$k]['GameType']);
                unset($data[$k]['tableId']);
            } else {
                $data[$k]['table'] = '';
            }
        }
        return $data;
    }
}