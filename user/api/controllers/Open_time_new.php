<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Open_time_new extends GC_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Open_result_model', 'orm');
    }

    public $types_name = ["快三"=>'k3',"时时彩"=>'ssc',"11选5"=>'11x5',"六合彩"=>'lhc',"PK10"=>'pk10',"快乐拾"=>'kl10',"低频彩"=>'yb',"PC蛋蛋"=>'pcdd'];

    /**
     * 购彩页面使用
     * @param $ctg  对应彩票分类 sc:私彩  gc:官彩
     * @return array
     */
    public function get_games_all()
    {
        $ctg = $this->G('ctg');
        $all_list = $this->orm->get_all_games();
        if ($ctg) {
            if (!in_array($ctg,['sc','gc'])) {
                $this->return_json(E_ARGS,'参数有误');
            }
            foreach ($all_list as &$types) {
                foreach ($types as $gid => &$games) {
                    if ($games['ctg'] !== $ctg) {
                        unset($types[$gid]);
                    }
                }
            }
        }
        $display_order = $this->orm->get_gcset(['display_order']);
        if (empty($display_order['display_order'])) {
            $display_order = ["快三","时时彩","11选5","六合彩","PK10","快乐拾","低频彩","PC蛋蛋"];
        } else {
            $display_order = json_decode($display_order['display_order'],true);
        }
        $data = [];
        foreach ($display_order as $name) {
            if (isset($all_list[$name])) {
                $data[$name]=$all_list[$name];
            }
        }
        $this->return_json(OK, $data);
    }

    /*
     * 购彩页面使用   获取游戏类型列表 并排序返回
     *
     * @param $ctg  对应彩票分类 sc:私彩  gc:官彩
     * @return array
     */
    public function get_type_list()
    {
        $ctg = $this->G('ctg');
        empty($ctg)?$ctg='gc':$ctg;
        $array = $this->orm->get_type_list($ctg);
        $display_order = $this->orm->get_gcset(['display_order']);
        if (empty($display_order['display_order'])) {
            $display_order = ["快三","时时彩","11选5","六合彩","PK10","快乐拾","低频彩","PC蛋蛋"];
        } else {
            $display_order = json_decode($display_order['display_order'],true);
        }
        $hot['type'] = 'hot';
        $hot['img']= REMEN_URL;
        $hot['name'] = '全部';
        $data = [];
        array_unshift($data, $hot);
        foreach ($display_order as $name) {
            foreach ($array as $k => $v) {
                if ($v['name'] == $name) {
                    array_push($data,$v);
                }
            }
        }
        $this->return_json(OK, $data);
    }

    /**
     * 聊天室app 查看开奖列表接口
     */
    public function get_kj_list()
    {
        $result_list = $this->orm->get_allGame_new_result('', '', '', '', 'sc');
        foreach ($result_list as $key => $value) {
            if ($value['id'] == 3 || $value['id'] == 4) {
                $sc = $this->orm->get_sx_color(explode(',', $value['number']));
                $result_list[$key]['color'] = $sc['color'];
                $result_list[$key]['shengxiao'] = $sc['shengxiao'];
                $result_list[$key]['number']  = $sc['number'];
            } elseif ($value['id'] == 24 || $value['id'] == 25) {
                if (empty($value['code_str'])) {
                    $re = $this->orm->get_28_hecl(explode(',', $value['number']));
                    $result_list[$key]['code_str'] = (string)$re['code_str'];
                } else {
                    $re = $this->orm->get_28_hecl('', $result_list[$key]['code_str']);
                }
                $result_list[$key]['color'] = $re['color'];
            } elseif ($value['id'] == 26 || $value['id'] == 27 || $value['id'] == 29 || $value['id'] == 30) {//PK10号码补零
                $num_arr = explode(',', $value['number']);
                $result_list[$key]['number']= implode(',', array_map(function ($v) {
                    return str_pad($v, 2, '0', STR_PAD_LEFT);
                }, $num_arr));
            }
            $result_list[$key]['gid'] = $value['id'];
        }
        $result_list = $this->orm->guolv($result_list);
        $display_order = $this->orm->get_gcset(['display_order']);
        if (empty($display_order['display_order'])) {
            $display_order = ["快三","时时彩","11选5","六合彩","PK10","快乐拾","低频彩","PC蛋蛋"];
        } else {
            $display_order = json_decode($display_order['display_order'],true);
        }
        $data = [];
        foreach ($display_order as $name) {
            foreach ($result_list as $v) {
                if ($v['cptype'] == $this->types_name[$name]) {
                    $data[] = $v;
                }
            }
        }
        $this->return_json(OK, $data);
    }

}
