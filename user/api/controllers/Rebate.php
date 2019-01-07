<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Rebate extends GC_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('MY_Model', 'M');
    }

    public function games_name_list()
    {
        $data = $this->M->redisP_get('games_name_list');
        if (!$data) {
            $this->M->select_db('public');
            $games = $this->M->db
                ->select("name,sname,type,tname")
                ->from('games')
                ->where('id < ',100)
                ->get()
                ->result_array();
            $data = [];
            foreach ($games as $item) {
                if (isset($data[$item['type']])) {
                    continue;
                }
                if ($item['type'] === 'yb') {
                    $item['type'] = $item['sname'];
                    $item['tname'] = $item['name'];
                }
                $data[$item['type']] = $item['tname'];
            }
            $this->M->redisP_set('games_name_list',json_encode($data,JSON_UNESCAPED_UNICODE));
        } else {
            $data = json_decode($data,true);
        }
        $display_order = $this->M->get_gcset(['display_order']);
        if (empty($display_order['display_order'])) {
            $display_order = ["快三","时时彩","11选5","六合彩","PK10","快乐拾","低频彩","PC蛋蛋"];
        } else {
            $display_order = json_decode($display_order['display_order'],true);
        }
        $arr = ["快三"=>'k3',"时时彩"=>'ssc',"11选5"=>'11x5',"六合彩"=>'lhc',"PK10"=>'pk10',"快乐拾"=>'kl10',"低频彩"=>['fc3d','pl3'],"PC蛋蛋"=>'pcdd'];
        $order = [];
        foreach ($display_order as $name) {
            if ($arr[$name]) {
                if (is_string($arr[$name])) {
                    array_push($order,$arr[$name]);
                } else {
                    array_push($order,$arr[$name][0],$arr[$name][1]);
                }
            }
        }
        $res = [];
        foreach ($order as $key) {
            foreach ($data as $k => $v) {
                if ($key == $k) {
                    //array_push($res,$v);
                    $res[$k] = $v;
                }
            }
        }
        $this->return_json(OK,$res);
    }
}
