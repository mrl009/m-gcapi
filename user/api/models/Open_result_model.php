<?php
/**
 * @模块   开奖结果
 * @版本   Version 1.0.0
 * @日期   2017-04-05
 * super
 */
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Open_result_model extends MY_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->select_db('private');
    }

    public $type_list = array(
        'hot'=>'热门','ssc'=>'时时彩', 'lhc'=>'六合彩','k3'=>'快3','11x5'=>'11选5',
        'yb'=>'低频彩','pcdd'=>'pc蛋蛋','pk10'=>'PK10','kl10'=>'快乐十');

    private $games_hash = 'games';
    private $games_table = 'games';
    /**
     * 变更开奖结果的状态
     * @param $gid 游戏ID
     * @param $issue 期数
     * @param $status 状态
     * @return bool
     */
    public function set_result_status($gid, $issue, $status)
    {
        if (empty($gid) || empty($issue) || empty($status)) {
            return false;
        }
        if($gid>50){
            $gid = gid_tran($gid);
        }
        $where['gid'] = $gid;
        $where['kithe'] = $issue;
        $arr['status'] = $status;
        $is = $this->write('open_num', $arr, $where);
        return $is;
    }



    /**
     * 获取彩票类型
     * @return array
     */
    public function get_games_group()
    {
        $this->select_db('public');
        $con['orderby'] = array('sort'=>'desc');
        $arr = $this->get_list('name,type', 'games_group', array(), $con);
        $new_arr = array();
        if (empty($arr)) {
            return $this->type_list;
        } else {
            foreach ($arr as $k=>$v) {
                $new_arr[$v['type']] = $v['name'];
            }
            return $new_arr;
        }
    }


    /**
     * 获取所有彩种最新一期的开奖结果,如果带参数，则获取某彩种指定状态的最新一条开奖结果
     * @param $where  $gid 根据gid指定彩种
     * @param $status 指定彩种的状态 2为已开奖未结算，3为已结算
     * @return bool or array
     */
    public function get_result($gid = '', $status='', $kithe = '')
    {
        $this->select_db('public');
        if (!empty($gid)) {
            if($gid>50){
                $gid = gid_tran($gid);
            }
            $where['gid'] = $gid;
            if (!empty($status) && is_numeric($status) && $status>0) {
                $where['status'] = $status;
            } else {
                $where['status >='] = 2;
                $where['status <='] = 3;
            }
            if ($kithe) {
                $where['kithe'] = $kithe;
            }
            $this->db->order_by('id', 'desc');
            $this->db->select('id,gid,kithe,open_time,number,status,code_kithe,code_str');
            $this->db->limit(1);
            $data =$this->db->get_where('gc_open_num', $where, 1)->result_array();
            if (empty($data)) {
                return false;
            }
            $data = $data[0];
            if ($where['gid'] == 3) {
                $sx_color = $this->get_sx_color(explode(',', $data['number']));
                $data['color'] = $sx_color['color'];
                $data['shengxiao'] = $sx_color['shengxiao'];
            } elseif ($where['gid'] == 24 || $where['gid'] == 25) {
                if (empty($data['code_str'])) {
                    $re = $this->get_28_hecl(explode(',', $data['number']));
                    $data['code_str'] = (string)$re['code_str'];
                } else {
                    $re = $this->get_28_hecl('', $data['code_str']);
                }
                $data['color'] = $re['color'];
            }
            return $data;
        }
        $page = array(
            'sort'=>$this->G('sort')?$this->G('sort'):'gid',
            'order'=>$this->G('order'));
        //$openData =$this->get_all_new_result($page);
        if (empty($openData)) {
            return false;
        }
        foreach ($openData as $key => $value) {
            unset($openData[$key]['open_time']);
            if ($value['type']==1) {
                $ot_arr = explode(',', $value['open_time']);
                $openData[$key]['issue_count'] = count($ot_arr);
            } else {
                $openData[$key]['issue_count'] = 1;
            }
            if ($value['gid']==3) {
                $aaa = explode(',', $openData[$key]['number']);
                $sx_color = $this->get_sx_color($aaa);
                $openData[$key]['color'] = $sx_color['color'];
                $openData[$key]['shengxiao'] = $sx_color['shengxiao'];
            } elseif ($value['gid']==24 || $value['gid']==25) {
                if (empty($value['code_str'])) {
                    $re = $this->get_28_hecl(explode(',', $value['number']));
                    $openData[$key]['code_str'] = (string)$re['code_str'];
                } else {
                    $re = $this->get_28_hecl('', $openData[$key]['code_str']);
                }
                $openData[$key]['color'] = $re['color'];
            }
            unset($openData[$key]['open_time']);
            unset($openData[$key]['type']);
        }
        return $openData;
    }


    /**
     * 获取所有彩种最新一期的开奖结果与部分期数信息 gc_open_num join gc_open_time
     * @auther super
     * @return array
     **/
    /*public function get_all_new_result($page)
    {
        $this->select_db('public');
        $order = $page['order'];
        $sort = 'son.'.$page['sort'];
        $sql = "select * FROM (select gt.type,gt.every_time,gn.number,gn.kithe,gn.gid,gn.open_time as kaijiang_time,gn.status,gn.code_kithe,gn.code_str,gt.open_time as open_time from gc_open_num AS gn INNER JOIN gc_open_time AS gt ON  gn.gid=gt.gid ORDER BY gn.open_time DESC) AS son GROUP BY son.gid order by $sort $order";
        $openData =$this->db->query($sql)->result_array();
        return $openData;
    }*/



    /**
     * 获取所有彩种最新一期的开奖结果，带参数则查询相应的结果
     * @auther super
     * @param $type 根据游戏类型查询
     * @param $gid 根据游戏ID查询单个
     * @param $hot 是否热门游戏
     * @return array
     * Version 4.0
     */
    public function get_allGame_new_result($type, $gid, $hot, $new_hot, $ctg)
    {
        $this->select_db('public');
        $guodu = $openData = $where = array();
        $games_hash = $this->games_hash;
        $cp = $this->get_gcset(['cp','cp_index']);
        $cp_arr = explode(',',$cp['cp']);//后台配置的彩种
        $cp_index = explode(',',$cp['cp_index']);//首页显示的彩种
        if (!empty($gid)) {//区分获取单个彩种还是获取多个彩种
            $where2['id'] = $gid;
            $tmp = $this->redisP_hget($games_hash, $gid);
            if (!empty($tmp)) {
                $games_json[$gid] = $tmp;
            }
        } else {
            if (!empty($type)) {//第一层筛选，根据类型获取
                $where2['type'] = $type;
                $zindex = $this->redisP_zrevrange('type:' . $type, 0, -1);//获取索引
            }  elseif (!empty($new_hot)) {//第一层筛选，热门彩种
                $wherein = $cp_index;
                if(empty($cp_index)){
                    return array();
                }
                $zindex = $cp_index;
            } else {
                $zindex = $this->redisP_zrevrange('all', 0, -1);//获取索引
            }
            if (!empty($ctg)) {//第二层筛选，按照国彩、私彩、电子、视讯分类 (ctg)
                /*$where2['ctg'] = $ctg;
                $zindex2 = $this->redisP_zrange('ctg:' . $ctg, 0, -1);//获取索引
                if(empty($zindex2)){
                    $zindex2 = $cp_arr;
                }
                if (empty($zindex)) {
                    $zindex = $zindex2;
                } else {
                    $zindex = array_intersect($zindex,(array)$zindex2);//交集
                }*/
                $where2['ctg'] = $ctg;
                if ($new_hot !=1) {
                    if ($ctg == 'gc') {
                        $zindex = explode(',', GC);
                    } elseif ($ctg == 'sc') {
                        $zindex = explode(',', SC);
                    } elseif ($ctg == 'sx') {
                        $zindex = explode(',', SX);
                    } else {
                        $zindex = $cp_arr;
                    }
                }
            }
        }
        if(!empty($zindex)){//筛选与排序
            $zindex3 = [];
            $ck = array_flip($cp_arr);
            foreach($ck as $key => $value){
                if(in_array($key,$zindex)){
                    $zindex3[$value] = $key;
                }
            }
            $games_json = array();
            if (!empty($hot)) {//第三层筛选，原来的热门
                //$where2['hot'] = $hot;
                $wherein = $zindex3 = array_slice($zindex3,0,6);
                //$zindex = $this->redisP_zrevrange('hot', 0, -1);//获取索引
            }
            foreach ($zindex3 as $gid) {
                $tmp = $this->redisP_hget($games_hash, $gid);
                if (!empty($tmp)) {
                    $games_json[] = $tmp;
                }
            }
        }

        if (empty($games_json)) {
            $where2['a.status <>'] = 2;
            $field = 'a.id,a.name,a.sname,a.type as cptype,a.img,a.hot,a.sort,a.show,a.wh_content,a.status,a.tmp,a.ctg,b.every_time';
            $this->db->from($this->games_table . ' as a');
            $this->db->select($field);
            $this->db->join('open_time as b','a.id=b.gid','left');
            $this->db->order_by('a.sort', 'desc');
            if(!empty($wherein)){
                $this->db->where_in('a.id', $wherein);
            }
            $id_list = $this->db->where($where2)->get()->result_array();//此处查询用于展示
            $redis_list = $id_list;//此处查询用于向redis缓存
            foreach ($redis_list as $jian => $zhi) {
                $this->redisP_hset($games_hash, $zhi['id'], json_encode($zhi, JSON_UNESCAPED_UNICODE));
                $this->redisP_zadd('type:'.$zhi['cptype'], $zhi['sort'], $zhi['id']);//按照彩票类型分类
                $this->redisP_zadd('all', $zhi['sort'], $zhi['id']);//所有游戏
                if(in_array($zhi['id'],$cp_arr)){
                    $this->redisP_zadd('ctg:'.$zhi['ctg'], $zhi['sort'], $zhi['id']);//按照彩票ctg类型分类
                }
                if(in_array($zhi['id'],[3,4,24,25])){
                    $this->redisP_zadd('ctg:gc', $zhi['sort'], $zhi['id']);//按照彩票ctg类型分类
                }
                /*if ($zhi['sort'] != 0) {
                    $this->redisP_zadd('hot', $zhi['sort'], $zhi['id']);//更多彩种:原来的热门，现在根据排序是否为零进行判断
                }*/
            }
        } else {
            $id_list = array_map(function ($v) {
                return json_decode($v, true);
            }, $games_json);
            //sortArrByField($id_list, 'sort', 'desc');
        }
        $num_arr = $num_json = array();
        $hash = 'new_open_num';
        $id_list = $this->guolv($id_list);
        if(empty($id_list)){
            return array();
        }
        foreach ($id_list as $key => $value) {
            if (empty($value)) {
                continue;
            }
            $num_json = $this->redisP_hget($hash, $value['id']);
            if (empty($num_json) && $value['id']>50) {
                $id = gid_tran($value['id']);
                $num_json = $this->redisP_hget($hash, $id);
            }
            if (empty($num_json) || in_array($value['id'], explode(',', ZKC))) {
                $where['gid'] = $value['id'];
                if($where['gid']>50){
                    $where['gid'] = gid_tran($where['gid']);
                }
                $where['status >='] = 2;
                $where['status <='] = 3;
                // @modify 2018-1-23 自开彩改用私库
                if (in_array($value['id'], explode(',', ZKC))) {
                    $this->select_db('private');
                    $field = 'lottery as number,issue as kj_issue,updated as kj_time,status as kj_status';
                    $this->db->select($field);
                    $this->db->order_by('id', 'desc');
                    $rs = $this->db->get_where('bet_settlement', $where, 1)->row_array();
                    $guodu[$key] = $this->format_time($rs);
                } else {
                    $this->select_db('public');
                    $field = 'number,kithe as kj_issue,code_kithe,open_time as kj_time,status as kj_status';
                    $this->db->select($field);
                    $this->db->order_by('id', 'desc');
                    $guodu[$key] = $this->db->get_where('open_num', $where, 1)->row_array();
                }
                if (empty($guodu[$key])) {
                    $guodu[$key]['kj_status'] = $guodu[$key]['kj_time'] = $guodu[$key]['code_kithe'] = $guodu[$key]['kj_issue'] = $guodu[$key]['number'] = '';
                }
            } else {
                $num_arr[$key] = json_decode($num_json, true);
                $guodu[$key]['number'] = $num_arr[$key]['number'];
                $guodu[$key]['kj_issue'] = $num_arr[$key]['kithe'];
                $guodu[$key]['code_kithe'] = $num_arr[$key]['code_kithe'];
                $guodu[$key]['kj_time'] = $num_arr[$key]['open_time'];
                $guodu[$key]['kj_status'] = (string)$num_arr[$key]['status'];
                unset($num_arr[$key]);
            }
            $openData[] = array_merge($value, $guodu[$key]);
        }
        return $openData;
    }

    //获取游戏列表(部分信息)
    public function get_games($field, $page)
    {
        $this->select_db('public');
        $condition['orderby'] = array('sort'=>$page['order']?$page['order']:'esc');
        /*$condition['orderby'][0]=$page['sort'];
        $condition['orderby'][1]=$page['order']?$page['order']:'esc';*/
        $where['status <>'] = 2;
        $arr =  $this->get_list($field, $this->games_table, $where, $condition);
        return $arr;
    }


    /**
     * 查询移动端右上角的全部彩票
     * @auther super
     * @return array
     **/
    public function get_all_games()
    {
        $hash = 'all_games';
        $all_list_json =  $this->redisP_hgetall($hash);
        $new_list = array();
        if (!empty($all_list_json)) {
            $arr = array_map(function ($n) {
                return json_decode($n, true);
            }, $all_list_json);
        } else {
            $this->select_db('public');
            $where['status <'] = 2;
            //$condition = array();
            $condition['join'] = 'open_time';
            $condition['on'] = 'a.id=b.gid';
            $arr =  $this->get_list('a.id as gid,a.name,a.img,a.type,a.status,a.tmp,a.ctg,b.every_time', $this->games_table, $where, $condition);
            if (!empty($arr)) {
                $tl = $this->get_games_group();
                foreach ($arr as $key=>$value) {
                    $new_list[$tl[$value['type']]][$value['gid']] = $value;
                }
            }
            foreach ($new_list as $jian => $zhi) {
                $this->redisP_hset($hash, $jian, json_encode($zhi, JSON_UNESCAPED_UNICODE));
            }
            $arr = $new_list;
        }
        foreach($arr as $typename => $gamelist){
            $guodu = $this->guolv($gamelist);
            if(empty($guodu)){
                unset($arr[$typename]);
            }else{
                $arr[$typename] = $guodu;
            }
        }
        //$arr = $this->guolv($arr,'all');
        return $arr;
    }

    /*public function get_open_list($field,$where,$page)
    {
        $this->select_db('public');
        $this->db->select($field);
        $this->db->order_by($page['sort'],$page['order']);
        foreach($where as $key => $value){
            if(empty($value)){
                unset($where[$key]);
            }
        }
        $data['rows'] =  $this->db->get_where('open_num',$where,$page['rows'],0)->result_array();
        return $data;
    }*/


    /**
     * 查询新版热门彩种(4个)
     * @auther sssss
     * @return array | bool
     **/
    /*public function get_new_hot()
    {
        $zkey = 'new_hot';
        $zlist = $this->redisP_Zrevrange($zkey, 0, -1);
        $new_hot = array();
        if(empty($zlist)){
            return false;
        }
        foreach($zlist as $gid){
            $one = $this->redisP_hget($this->games_hash, $gid);
            if(!empty($one)){
                $new_hot[] = json_decode($one,true);
            }
        }
        return $new_hot;
    }*/


    /**
     * 查询移动端购彩页面的彩票type列表
     * @auther super
     * @return array
     **/
    public function get_type_list($ctg='gc')
    {
        $list = 'type_list:'.$ctg;
        $type_list_json =  $this->redisP_lrange($list, 0, -1);
        $zindex = $this->redisP_zrange('ctg:' . $ctg, 0, -1);//获取索引
        $arr = $arr2 = $games_json = [];
        if(!empty($zindex)){
            foreach ($zindex as $gid) {
                $tmp = $this->redisP_hget($this->games_hash, $gid);
                if (!empty($tmp)) {
                    $games_json[] = json_decode($tmp,true);
                }
            }
            $games_json = array_flip(array_column($games_json,'cptype'));
        }
        if (!empty($type_list_json)) {
            $arr = array_map(function ($n) {
                return json_decode($n, true);
            }, $type_list_json);
        } else {
            $where['status <'] = 2;
            $where['ctg'] = $ctg;
            $condition['groupby'] = array('type');
            $this->select_db('public');
            $arr = $this->get_list('type,img,tmp', $this->games_table, $where, $condition);
            if($ctg=='gc'){
                $arr2 = $this->get_list('type,img,tmp', $this->games_table, ['status <'=>2], ['wherein'=>['id'=>[3,4,24,25]],'groupby'=>['type']]);
                $arr = array_merge($arr,$arr2);
            }
            if (!empty($arr)) {
                $tl = $this->get_games_group();
                $new_arr = array();
                foreach ($arr as $key => $value) {
                    $arr[$key]['name'] = $new_arr[$value['type']]['name'] = $tl[$value['type']];
                    $new_arr[$value['type']]['type'] = $value['type'];
                    $new_arr[$value['type']]['img'] = $value['img'];
                    $new_arr[$value['type']]['tmp'] = $value['tmp'];
                }

                $tl = array_flip($tl);
                array_shift($tl);
                array_walk($tl, function ($v) use ($new_arr, $list) {
                    if(!empty($new_arr[$v])){
                        $this->redisP_rpush($list, json_encode($new_arr[$v], JSON_UNESCAPED_UNICODE));
                    }
                });
            }
        }

        foreach($arr as $k => $v){
            if(empty($games_json[$v['type']])){
                unset($arr[$k]);
            }
        }
        $hot['type'] = 'hot';
        $hot['img']= REMEN_URL;
        $hot['name'] = '热门';
        array_unshift($arr, $hot);
        return $arr;
    }

    /**
     * 查询移动端购彩页面的彩票type列表 旧版本
     * @auther super
     * @return array
     **/
    public function get_type_list_old()
    {
        $list = 'type_list';
        $type_list_json =  $this->redisP_lrange($list, 0, -1);
        if (!empty($type_list_json)) {
            $arr = array_map(function ($n) {
                return json_decode($n, true);
            }, $type_list_json);
        } else {
            $where['status <'] = 2;
            $condition['groupby'] = array('type');
            $this->select_db('public');
            $arr = $this->get_list('type,img,tmp', $this->games_table, $where, $condition);
            if (!empty($arr)) {
                $tl = $this->get_games_group();
                $new_arr = array();
                foreach ($arr as $key => $value) {
                    $arr[$key]['name'] = $new_arr[$value['type']]['name'] = $tl[$value['type']];
                    $new_arr[$value['type']]['type'] = $value['type'];
                    $new_arr[$value['type']]['img'] = $value['img'];
                    $new_arr[$value['type']]['tmp'] = $value['tmp'];
                }
                $tl = array_flip($tl);
                array_shift($tl);
                array_walk($tl, function ($v) use ($new_arr, $list) {
                    $this->redisP_rpush($list, json_encode($new_arr[$v], JSON_UNESCAPED_UNICODE));
                });
            }
        }
        $hot['type'] = 'hot';
        $hot['img']= REMEN_URL;
        $hot['name'] = '热门';
        array_unshift($arr, $hot);
        return $arr;
    }

    public function get_main_games_new()
    {
        $cp_index = $this->get_gcset(['cp_index']);
        $cp_index = explode(',',$cp_index['cp_index']);
        $hash = 'main_game';
        $all_list_json =  $this->redis_Zrevrange($hash, 0, -1);
        if (!empty($all_list_json)) {
            $arr = array_map(function ($n) { return json_decode($n, true); }, $all_list_json);
            $arr = (array)$arr;
        } else {
            $this->select_db('public');
            $condition['join'] = 'open_time';
            $condition['on'] = 'a.id=b.gid';
            //$condition['orderby'] = array('sort'=>'desc');
            $condition['wherein'] = array('a.id'=>$cp_index);
            $where['a.status <'] = 2;
            //$where['a.hot']=1;
            $arr =  $this->get_list('name,gid,img,every_time,a.type,sort,status,tmp,ctg', $this->games_table, $where, $condition);
            if (empty($arr)) {
                return array();
            }
            $i = $k = 0;
            foreach ($arr as $jian => $zhi) {
                if ($arr[$jian]['type']=='pcdd') {
                    if ($i===1) {
                        unset($arr[$jian]);
                        continue;
                    } else {
                        $arr[$jian]['name']='PC蛋蛋';
                        $arr[$jian]['gid']='pcdd';
                        $i++;
                    }
                }
                $this->redis_zadd($hash,$zhi['sort'],json_encode($arr[$jian], JSON_UNESCAPED_UNICODE));
                $k++;
            }
        }
        //$arr = $this->guolv($arr);
        return $arr;
    }

    /**
     * 查询移动端主页的热门彩票
     * @auther super
     * @return array
     **/
    public function get_main_games()
    {
        $hash = 'main_game';
        $all_list_json =  $this->redisP_Zrevrange($hash, 0, -1);
        if (!empty($all_list_json)) {
            $arr = array_map(function ($n) {
                return json_decode($n, true);
            }, $all_list_json);
            $arr = (array)$arr;
        } else {
            $this->select_db('public');
            $condition['join'] = 'open_time';
            $condition['on'] = 'a.id=b.gid';
            $condition['orderby'] = array('sort'=>'desc');
            $where['a.status <'] = 2;
            $where['a.hot']=1;
            $arr =  $this->get_list('name,gid,img,every_time,a.type,sort,status,tmp,ctg', $this->games_table, $where, $condition);
            if (empty($arr)) {
                return array();
            }
            $i = $k = 0;
            foreach ($arr as $jian => $zhi) {
                /*if ($arr[$jian]['type']=='pcdd') {
                    if ($i===1) {
                        unset($arr[$jian]);
                        continue;
                    } else {
                        $arr[$jian]['name']='PC蛋蛋';
                        $arr[$jian]['gid']='pcdd';
                        $i++;
                    }
                }*/
                $this->redisP_zadd($hash,$zhi['sort'],json_encode($arr[$jian], JSON_UNESCAPED_UNICODE));
                $k++;
            }
        }
        $arr = $this->guolv($arr);
        return $arr;
    }

    //根據gc_set的cp配置過濾彩種
    public function guolv($arr)
    {
        $gc_set = $this->get_gcset(['cp','lottery_auth']);
        if(!empty($gc_set['cp'])){
            $cp = explode(',',$gc_set['cp']);
            $lottery_auth = explode(',',$gc_set['lottery_auth']);
            $ctg = ['gc','sc','sx'];
            if (!in_array('1',$lottery_auth)) {
                unset($ctg[0]);
            }
            if (!in_array('2',$lottery_auth)) {
                unset($ctg[1]);
            }
            if (!in_array('4',$lottery_auth)) {
                unset($ctg[2]);
            }
            foreach ($arr as $kk => $vv){
                if(empty($vv['gid'])){
                    $vv['gid'] = $vv['id'];
                }
                if(!in_array($vv['gid'],$cp)){
                    unset($arr[$kk]);
                }
                if (isset($vv['ctg']) && !in_array($vv['ctg'],$ctg)) {
                    unset($arr[$kk]);
                }
            }
        }
        return $arr;
    }

    public function delredis()
    {
        $this->redisP_del('hot');
        $this->redisP_del($this->games_hash);
        $this->redisP_del('all_games');
        $this->redisP_del('main_games');
    }


    /**
     * 遍历数组并将其中的number转换成颜色和生肖
     * @param $rows
     * @return mixed
     */
    public function for_sx_color($rows)
    {
        foreach ($rows as $k => $v) {
            $num_arr = explode(',', $v['number']);
            $sx_color = $this->get_sx_color($num_arr);
            $rows[$k]['color'] =  $sx_color['color'];
            $rows[$k]['shengxiao'] =  $sx_color['shengxiao'];
            $rows[$k]['number'] =  $sx_color['number'];
        }
        return $rows;
    }

    /**
     * 根据和值或者开奖号码转换成和值的波色
     * @param array $num_arr 开奖号码数组
     * @param float $he 和值
     * @return mixed
     */
    public function get_28_hecl($num_arr='', $he = '')
    {
        $cl = $this->get_28_color();
        if ($num_arr!='') {
            $re['code_str'] =  $he = array_sum($num_arr);
        }
        if (strlen($he)<2) {
            $he='0'.$he;
        }
        $re['color'] = $cl[$he];
        return $re;
    }

    /**
     * 根据传过来的数字转换成波色与生肖
     * @param $number 开奖号码数组array(0=>'1',1=>'6'....)
     * @return mixed
     */
    public function get_sx_color($number)
    {
        $this->load->model('Games_model', 'gm');
        $sx = $this->redisP_get('sx_list');
        if (empty($sx)) {
            $dragonball = $this->gm->lhc_sx_balls();
            $sx = $dragonball['sx'];
        } else {
            $sx = json_decode($sx, true);
        }
        foreach ($number as $k=>$v) {
            if (strlen($v)==1) {
                $number[$k] = '0'.$v;
            }
        }
        $cl = $this->get_lhc_color();
        $result['color'] = $result['shengxiao'] = '';
        $result['color'] = array_map(function ($value) use ($cl) {
            return $cl[$value];
        }, $number);
        $result['shengxiao'] = array_map(function ($value2) use ($sx) {
            return $sx[$value2];
        }, $number);
        $result['color']  = implode(',', $result['color']);
        $result['shengxiao'] = implode(',', $result['shengxiao']);
        $result['number'] = implode(',', $number);
        return $result;
    }


    /**
     * 六合彩号码转颜色
     * @return array
     */
    public function get_lhc_color()
    {
        return array(
            '01' => 'red', '02' => 'red', '03' => 'blue', '04' => 'blue', '05' => 'green',
            '06' => 'green', '07' => 'red', '08' => 'red', '09' => 'blue', 10 => 'blue',
            11 => 'green', 12 => 'red', 13 => 'red', 14 => 'blue', 15 => 'blue',
            16 => 'green', 17 => 'green', 18 => 'red', 19 => 'red', 20 => 'blue',
            21 => 'green', 22 => 'green', 23 => 'red', 24 => 'red', 25 => 'blue',
            26 => 'blue', 27 => 'green', 28 => 'green', 29 => 'red', 30 => 'red',
            31 => 'blue', 32 => 'green', 33 => 'green', 34 => 'red', 35 => 'red',
            36 => 'blue', 37 => 'blue', 38 => 'green', 39 => 'green', 40 => 'red',
            41 => 'blue', 42 => 'blue', 43 => 'green', 44 => 'green', 45 => 'red',
            46 => 'red', 47 => 'blue', 48 => 'blue', 49 => 'green'
        );
    }

    /**
     * 北京28、幸运28号码转颜色
     * @return array
     */
    public function get_28_color()
    {
        return array(
            '00' => 'gray',
            '01' => 'green', '02' => 'blue', '03' => 'red', '04' => 'green', '05' => 'blue',
            '06' => 'red', '07' => 'green', '08' => 'blue', '09' => 'red', 10 => 'green',
            11 => 'blue', 12 => 'red', 13 => 'gray', 14 => 'gray', 15 => 'red',
            16 => 'green', 17 => 'blue', 18 => 'red', 19 => 'green', 20 => 'blue',
            21 => 'red', 22 => 'green', 23 => 'blue', 24 => 'red', 25 => 'green',
            26 => 'blue', 27 => 'gray'
        );
    }

    /**
      *@desc 删除公库指定表的数据,都不要调这个代码
      *@param 指定的表名
      *@param $field 判断的字段
      **/
    public function delete_base_data($table,$field)
    {
        $this->select_db('public');
        $date = date("Y-m-d H:i:s",strtotime('-60 day'));
        // $date = date("Y-m-d H:i:s",strtotime('-46 day'));   
        //.gc_open_num表中没有和id排序同步的字段,根据实际条件进行删除
        if($table =='gc_open_num'){
            //。gc_open_num 表测试
            $sql4 = "select id from  ".$table."  where ".$field. " < '".$date."' and gid <>3  order by id  asc limit 1";
            $ids = $this->db->query($sql4)->row_array();
            $from_id = $ids['id'];
            //.删除数据,lhc数据不删
            $bool1 = 1;
            $data = [];
            $data['from_id'] = $from_id;
            $data['affected_rows'] = 0;
            while ($bool1>0) {
                //.根据id去删除数据
                $sql5 = "delete from  ".$table." where gid <> 3 and  ".$field." < '".$date."'   limit 10000";
                $rs = $this->db->query($sql5);
                $bool1 = $this->db->affected_rows();
                $data['affected_rows'] +=$bool1;       
            }
            return $data;
        }
        //.取2个月前的数据id
        $sql1 = "select id from  ".$table."  where ".$field. " < '".$date."'   order by id  desc limit 1";
        $ids = $this->db->query($sql1)->row_array();
        if(!$ids){
            return false;
        }
        $from_id = '';//.最小的id
        $sql2 = "select  id from  ".$table. " where id <= ".$ids['id']. ' order by id asc  limit 1';
        $id_data = $this->db->query($sql2)->row_array();
        $from_id = $id_data['id'];
        $bool = 1;
        $data = [];
        $data['from_id'] = $from_id;
        $data['affected_rows'] = 0;
        while ($bool>0) {
            //.根据id去删除数据
            $sql3 = "delete from  ".$table." where id <= ".$ids['id']." limit 10000";
            $rs = $this->db->query($sql3);
            $bool = $this->db->affected_rows();
            $data['affected_rows'] +=$bool;         
        }
        return $data;
    }


    /**
      *@desc 删除私库指定表的数据,都不要调这个代码
      *@param 指定的表名
      *@param $field 判断的字段
      **/
    public function delete_private_data($table,$field)
    {
        $date = date("Y-m-d H:i:s",strtotime('-60 day'));
        // //.测试数据
        // $date = date("Y-m-d H:i:s",strtotime('-60 day'));
        $is_field = strstr($field,'time');
        $is_table = strstr($table, 'bet');
        if($is_field||$is_table){
            $date = strtotime($date);
        };
        //.取2个月前的数据id
        $sql1 = "select id from  ".$table."  where ".$field. " < '".$date."' order by id  desc limit 1";
        $ids = $this->db->query($sql1)->row_array();
        if(!$ids){
            return false;
        }
        $from_id = '';//.最小的id
        $sql2 = "select  id from  ".$table. " where id <= ".$ids['id']. ' order by id asc  limit 1';
        $id_data = $this->db->query($sql2)->row_array();
        $from_id = $id_data['id'];
        $bool = 1;
        $data = [];
        $data['from_id'] = $from_id;
        $data['affected_rows'] = 0;
        while ($bool>0) {
            //.根据id去删除数据
            $sql3 = "delete from  ".$table." where id <= ".$ids['id']." limit 10000";
            $rs = $this->db->query($sql3);
            $bool = $this->db->affected_rows();
            $data['affected_rows'] += $bool;         
        }
        return $data;
    }

    /**
     * unix时间戳转换
     * @param $rs
     * @return array
     */
    private function format_time($rs) {
        $rs = [
            'number' => isset($rs['number']) ? $rs['number'] : '',
            'kj_issue' => isset($rs['kj_issue']) ? $rs['kj_issue'] : '',
            'kj_time' => isset($rs['kj_time']) ? date('Y-m-d H:i:s', $rs['kj_time']) : '',
            'kj_status' => isset($rs['kj_status']) ? $rs['kj_status'] : ''
        ];
        return $rs;
    }
}
