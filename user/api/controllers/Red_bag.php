<?php
/**
 * 红包
 * 使用表 gc_red_activity  gc_red_order gc_red_set
 * Created by PhpStorm.
 * User: shenshilin
 * Date: 2017/9/5
 * Time: 14:13
 */
defined('BASEPATH') or exit('No direct script access allowed');

class Red_bag extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Red_bag_model', 'redBag');
    }

    public $lock = 'temp:user:grab_red_bag:%d_%d';
    /**
     * 获取红包排名梯级
    */
    public function bag_level()
    {
        $user_level = $this->redBag->get_bag_set();
        $arr= [];
        foreach ($user_level as &$value) {
            unset($value['start_total']);
            unset($value['add_time']);
            $value['id'] = (string)$value['id'];
            $value['start_recharge'] = (string)round($value['start_recharge'],3);
            $value['end_recharge'] = (string)round($value['end_recharge'],3);
            $value['end_total'] = (string)round($value['end_total'],3);
            $value['count'] = (string)round($value['count'],3);
            $arr[] = $value;
        }
        if (!empty($arr)) {
            array_multisort(array_column($arr,'start_recharge'),SORT_ASC,$arr);
        }
        $this->return_json(OK,$arr);
    }
    /**
     * 获取红包 红包提前预读到reids
     * @return json
    */
    public function index()
    {
        $bagData = $this->redBag->get_bag();
        if (!empty($bagData)) {
            unset($bagData['total']);
            unset($bagData['current_total']);
            unset($bagData['add_time']);
            $bagData['server_time'] = (string)time();
            //$bagData['start_time']<= $_SERVER['REQUEST_TIME']+A_RED_UPD_TIME_LIMIT*60?$bagData['show'] = "1":$bagData['show'] = "0";
        }
        isset($bagData['stop'])?$bagData=[]:false;
        $this->return_json(OK,$bagData);
    }

    /**
     * 获取用户的信息
    */
    public function user_detail()
    {
        $red_id = $this->G('red_id');
        $data = $this->redBag->user_level($this->user['id']);
        $data['total'] = (float)$data['total'];
        $data['count'] = (int)$data['count'];
        if (!empty($red_id)) {
            $redisKey = sprintf($this->redBag->userInc,$red_id);
            $num = $this->redBag->redis_hget($redisKey,$this->user['id']);
            //$data['count']-$num<1?$data['count']=0:$data['count']-$num;
            $data['count'] = $data['count']-$num < 1 ? 0 : $data['count']-$num;
        }
        unset($data['start_recharge']);
        unset($data['end_recharge']);
        unset($data['start_total']);
        unset($data['end_total']);
        unset($data['id']);
        unset($data['add_time']);
        $this->return_json(OK,$data);
    }

    /**
     * 最近24小时以抢红包
    */
    public function bag_list()
    {
        $where = [
            'a.add_time >=' => time()-24*3600
        ];
        $where2 =[
            'limit' => SHOW_BET_WIN_ROWS,
            //'orderby' => [ 'total' => 'desc' ],
            'join' => 'user',
            'on' => 'b.id=a.uid',
        ];
        $data = $this->redBag->get_all('a.total,a.src,b.username','red_order',$where,$where2);
        foreach ($data as &$value) {
            $temp  = substr($value['username'],0,1).'***'.substr($value['username'],-1,1);
            $value['username'] = $temp;
        }
        $data = $this->add_rand_data($data);
        $this->return_json(OK,$data);
    }

    /**
     * 添加随机红包数据
     * @param array $data
     * @return array
     */
    public function add_rand_data($data = [])
    {
        $rs = [];
        $arr = range(0, 2 * SHOW_BET_WIN_ROWS);
        shuffle($arr);
        $pattern = '1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLOMNOPQRSTUVWXYZ';
        foreach ($arr as $k) {
            if (isset($data[$k])) {
                $rs[] = $data[$k];
            } else {
                $username = $pattern{mt_rand(0, 35)} . '***' . $pattern{mt_rand(0, 35)};
                $total = rand(100, 100000) / 100;
                $rs[] = ['username' => $username, 'total' => (string)$total, 'src' => 3];
            }
        }
        return $rs;
    }

    /**
     * 会员已抢红包
    */
    public function user_bag()
    {
        $where = [
            'uid' => $this->user['id']
        ];
        $where2 = [];
        $page = array(
            'page'=> (int)$this->G('page'),
            'rows'=> 15,
            'sort'=> 'id',
            'order'=> 'desc',
            'total'=> '-1'
        );
        $data = $this->redBag->get_list('total,src,add_time','red_order',$where,$where2,$page);
        foreach ($data['rows'] as &$value) {
            $value['add_time'] = date('Y-m-d H:i:s',$value['add_time']);
        }
        $this->return_json(OK,$data);
    }

    /**
     * 抢红包开始
    */
    public function grab_red_bag()
    {
        $bagId = (int)$this->P('id');
        if ($bagId <1) {
            $this->return_json(E_ARGS,"没有该红包");
        }
        //$bagData = $this->redBag->get_bag();
        $uid   = $this->user['id'];
        $this->lock = sprintf($this->lock,$bagId,$uid);

        if ( !$this->redBag->redis_setnx($this->lock,$_SERVER['REQUEST_TIME'] )) {
            $this->return_json(E_ARGS,'手太快,请重试');
        }
        $this->redBag->redis_expire($this->lock,2);

        $bool = $this->redBag->check_bag($bagId);

        if ((int)$bool === OK_RED) {
            $this->return_json(OK_RED,'来晚了红包已抢完');
        }
        if ($bool !== true) {
            $this->redBag->redis_del($this->lock);
            $this->return_json(E_ARGS,$bool);
        }
        $arrData = $this->redBag->bag_do($bagId);

        /**删除重放keys*/
        $chk_string = CLIENT_IP.':'.$_SERVER['REQUEST_URI'];
        $redis_key = 'api-repeat:'.md5($chk_string);
        $this->redBag->redisP_del($redis_key);

        if (is_array($arrData)) {
            $this->redBag->redis_del($this->lock);
            $this->return_json(OK,$arrData);
        }else{
            $this->redBag->redis_del($this->lock);
            $this->return_json(E_ARGS,$arrData);
        }
    }


}
