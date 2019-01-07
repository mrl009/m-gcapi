<?php
/**
 * @模块   会员中心／投注记录model
 * @版本   Version 1.0.0
 * @日期   2017-03-30
 * shensiming
 */

if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

class Bet_record_model extends MY_Model 
{

	public function __construct()
    {
        parent::__construct();
        $this->load->model('Games_model');
        $this->select_db('public');
        $games = $this->get_list('CONCAT("bet_",a.sname) as tablen, a.name as label, id', 'games');
        $this->games_arr = array_make_key($games, 'id');
        $this->select_db('private');
    }
    // 对应游戏下注表
    private $games_arr = null;


    /******************公共方法*******************/
    /**
     * 获取下注列表（app）
     */
    public function get_bet_list($type, $basic, $page)
    { 
    	/**
         * 查询下注汇总表
         * 全部，代开奖，未中奖(需要先获取最新开奖期号再去匹配下注的期号)
         */
        $table  = 'bet_index';
        $select = 'a.order_num,b.status,a.issue,a.uid,a.agent_id,b.price_sum,b.win_counts';
        $condition = array('join'=>'bet_wins', 'on'=>'b.order_num = a.order_num');
        if(in_array($type, array(STATUS_NOTOPEN))) {
            $this->db->distinct();
            $select = 'a.order_num, 4 status, a.issue ,a.uid,a.agent_id';
            $condition = null;
            $condition['join'] = [
                ['table'=>'bet_settlement as b', 'on'=>'(`a`.`issue` = `b`.`issue` AND `b`.`gid` = CONVERT(substring(a.order_num, 2, 2),SIGNED))'],
                ['table'=>'bet_wins as c',     'on'=>'c.order_num=a.order_num'],
            ];
            $basic['b.status'] = null;
            $condition['wheresql'][] = 'b.status is null and c.status is null';
        }
        $bet_idx = $this->get_list($select, $table, $basic, $condition, $page);
        $total = $bet_idx['total'];
        $bet_idx = $bet_idx['rows'];
        if(empty($bet_idx)) return false;

        /* 查询开奖状态 */
        $bet_idx = $this->_query_open_status($type, $bet_idx);
        if(empty($bet_idx)) return false;
        /* 提取订单号 */
        $order_arr = $this->_get_order_num($bet_idx);
        /* 查询详细的游戏下注信息 */
        $resu = $this->_query_games_bet($order_arr);
        if(empty($resu)) return false;
    	/* 对游戏下注表的数据进行格式化 */
    	$resu = $this->_format_data_app($resu, $bet_idx);
        /* 如果是代开奖，判断是否有封盘不能有撤单按钮 */
        $resu = $this->_query_is_close($type, $resu);

    	return array('rows'=>$resu,'total'=>$total);
    }

    /**
     * 获取下注列表操作（后台）测试接口
     */
    public function get_bet_list2($type, $basic, $page)
    {
        /**
         * 查询下注汇总表
         * 全部，代开奖，未中奖(需要先获取最新开奖期号再去匹配下注的期号)
         */
        $table  = 'bet_index';
        $select = 'a.order_num,b.status,issue,a.uid,a.agent_id,b.price_sum,b.win_counts, c.from_way as src';
        $condition = array('join'=>'bet_wins_copy', 'on'=>'b.order_num = a.order_num');
        if(in_array($type, array(STATUS_LOSE, STATUS_NOTOPEN))) {
            // $basic['b.status'] = null;
            // $this->db->having(array('b.status is null'=>null));
            $this->core->db->distinct();
            $join = null;
            $where['b.status'] = null;
            $select = 'a.order_num, 4 status, 0 price_sum,a.issue, c.username as account, a.created,c.id as uid, c.from_way as src';
            $join['join'] = [
                ['table'=>'bet_settlement as b', 'on'=>'(`a`.`issue` = `b`.`issue` AND `b`.`gid` = CONVERT(substring(a.order_num, 2, 2),SIGNED))'],
                ['table'=>'user as c',     'on'=>'a.uid=c.id'],
                ['table'=>'bet_wins as d',     'on'=>'d.order_num=a.order_num'],
            ];
            $join['wheresql'][] = 'b.status is null and d.status is null';
        }
        $bet_idx = $this->get_list($select, $table, $basic, $condition, $page);
        $bet_idx = $bet_idx['rows'];
        if(empty($bet_idx)) return false;

        /* 查询开奖状态 */
        $bet_idx = $this->_query_open_status($type, $bet_idx);
        if(empty($bet_idx)) return false;
        /* 提取订单号 */
        $order_arr = $this->_get_order_num($bet_idx);
        /* 查询详细的游戏下注信息 */
        $resu = $this->_query_games_bet($order_arr);
        if(empty($resu)) return false;
        /* 对游戏下注表的数据进行格式化 */
        $resu = $this->_format_data_app($resu, $bet_idx);
        /* 如果是代开奖，判断是否有封盘不能有撤单按钮 */
        $resu = $this->_query_is_close($type, $resu);
        return array('rows'=>$resu);
    }

    /**
     * 获取下注列表操作（后台）
     *
     * @access public
     * @param Array $where  查询条件
     * @param Array $senior   高级查询
     * @param Array $page   分页条件
     * @param int   $type    cha xun
     * @param int   $src 来源
     *
     */
    public function get_order_list($where, $senior, $page, $type, $src)
    {
        $select = 'a.issue, a.order_num, a.agent_id,c.username as account, b.status, b.price_sum, a.created,b.win_counts,c.id as uid, c.from_way as src';
        /* 待开奖查询数据 */
        if(in_array($type, array(STATUS_NOTOPEN))) {
            $this->core->db->distinct();
            $where['b.status'] = null;
            $select = 'a.order_num, 4 status, 0 price_sum,a.issue, c.username as account, a.created,c.id as uid, c.from_way as src,a.agent_id';
            $senior['join'] = [
                ['table'=>'bet_settlement as b', 'on'=>'(`a`.`issue` = `b`.`issue` AND `b`.`gid` = CONVERT(substring(a.order_num, 2, 2),SIGNED))'],
                ['table'=>'user as c',     'on'=>'a.uid=c.id'],
                ['table'=>'bet_wins as d',     'on'=>'d.order_num=a.order_num'],
            ];
            $senior['wheresql'][] = 'b.status is null and d.status is null';
        }
        /* 未中奖查询数据 */
        if(in_array($type, array(STATUS_LOSE))) {
            $this->core->db->distinct();
            $where['b.status'] = null;
            $select = 'a.order_num, 5 status, 0 price_sum,a.issue, c.username as account, a.created,c.id as uid, c.from_way as src,a.agent_id';
            $senior['join'] = [
                ['table'=>'bet_settlement as b', 'on'=>'(`a`.`issue` = `b`.`issue` AND `b`.`gid` = CONVERT(substring(a.order_num, 2, 2),SIGNED))'],
                ['table'=>'user as c',     'on'=>'a.uid=c.id'],
                ['table'=>'bet_wins as d', 'on'=>'d.order_num=a.order_num'],
            ];
            $senior['wheresql'][] = 'b.status = 3 and d.status is null';
        }
        $arr = $this->core->get_list($select, 'bet_index', $where, $senior, $page);

        $total= $arr['total'];
        /* 对于没有状态的要去查询 */
        $arr = $this->_query_open_status($type, $arr['rows']);
        if (empty($arr)) {
            return array('rows' => [], 'total' => 0, 'footer' => []);
        }
        /* 提取订单号 */
        $order = $this->_get_order_num($arr);
        /* 查询详细的游戏下注信息 */
        $resu = $this->_query_games_bet($order);
        if (empty($resu)) {
            return array('rows' => [], 'total' => 0);
        }
        /* 对游戏下注表的数据进行格式化 */
        $resu = $this->_format_data_admin($resu, $arr, $src);
        /* 把代理id转换为代理名称 */
        $resu = $this->_format_agent($resu);
        /* 获取汇总数据 */
        $resu = $this->_get_footer($resu);
        $resu['total'] = $total;
        return $resu;
    }

    /**
     * 获取中奖榜数据列表
     */
    public function get_list_wins($page)
    {
        // $table  = 'bet_wins';
        // $basic['status']  = 1;
        // $select = 'a.price_sum, b.username, a.order_num';
        // $condition = array(
        //     'join' =>'user', 
        //     'on'   =>'b.id = a.uid');
        // $resu = $this->get_list($select, $table, 
        //                         $basic, $condition, $page);
        // unset($resu['total']);
        $time = time()-86440;
//        $sql = '
//                (SELECT `a`.`price_sum`, `b`.`nickname` as `username`, `b`.`uid`, `b`.`img`, `a`.`order_num` FROM `gc_bet_wins` as `a` LEFT JOIN `gc_user_detail` as `b` ON `b`.`uid` = `a`.`uid` WHERE `a`.`price_sum` > 10 and `a`.`status` = 1 and `a`.`created` >= '.$time.'  ORDER BY `a`.`id` DESC LIMIT '.SHOW_BET_WIN_ROWS.') union
//                (SELECT `a`.`price_sum`, `b`.`nickname` as `username`, `b`.`uid`, `b`.`img`, `a`.`order_num` FROM `gc_bet_wins` as `a` LEFT JOIN `gc_user_detail` as `b` ON `b`.`uid` = `a`.`uid` WHERE `a`.`price_sum` > 10 and `a`.`status` = 1 and `a`.`created` >= '.$time.' ORDER BY `a`.`price_sum` DESC LIMIT '.SHOW_BET_WIN_ROWS.') ';
        $sql = 'SELECT `a`.`price_sum`, `b`.`nickname` as `username`, `b`.`uid`, `b`.`img`, `a`.`order_num` FROM `gc_bet_wins` as `a` LEFT JOIN `gc_user_detail` as `b` ON `b`.`uid` = `a`.`uid` WHERE `a`.`price_sum` > 10 and `a`.`status` = 1 and `a`.`created` >= ' . $time . '  ORDER BY `a`.`id` DESC LIMIT ' . SHOW_BET_WIN_ROWS;
        $resu['rows'] = $this->db->query($sql)->result_array();
        $cache['user_id'][0] = ['username'=>'-'];

        foreach ($resu['rows'] as $key => &$value) {
            $id = (int)substr($value['order_num'], 1, 2);
            $resu['rows'][$key]['game'] = $this->games_arr[$id]['label'];
            $this->load->helper('common_helper');


            if (empty($value['img'])) {
                $value['img'] = 0;//默認頭像
            }

            if (empty($value['username'])) {
                $user_id = $value['uid'];
                if (empty($cache['user_id'][$user_id])) {
                    $user = $this->user_cache($user_id);
                    $cache['user_id'][$user_id] = $user;
                }
                $username = $cache['user_id'][$user_id]['username'];
//                $value['username'] = $username{0}.$username{1}.
//                                    str_repeat('*', strlen($username)-3).
//                                    $username{strlen($username)-1};
                $value['username'] = uname_hide($username);
            }

            if ( strlen($value['username']) >= 15 ) {
                // 用户名的字符串超长处理
                $value['username'] = mb_substr($value['username'], 0, 2, 'utf-8') .'***'. mb_substr($value['username'], -1, 1, 'utf-8');
            }

            unset($resu['rows'][$key]['order_num']);
            //unset($value['uid']);
        }

        if (empty($resu['rows'])) {
            return [];
        } else {
            return $resu['rows'];
        }
        
    }


    /**
     * 获取中奖榜数据列表
     */
    public function get_win($page,$yesterday,$mmday)
    {
        $rows = 10;
        $sql = "SELECT sum(a.lucky_price) as lucky_price, b.nickname as username, b.uid, b.img
                FROM 
                gc_report as a LEFT JOIN gc_user_detail as b ON a.uid = b.uid 
                WHERE 
                report_date <= '{$yesterday}'AND report_date >= '{$mmday}' AND a.lucky_price > 0  GROUP BY a.uid  ORDER BY a.lucky_price DESC  LIMIT {$rows}";
        $resu['rows'] = $this->db->query($sql)->result_array();


        $cache['user_id'][0] = ['username'=>'-'];
        foreach ($resu['rows'] as $key => &$value) {
            if (is_null($value['lucky_price'])) {
                unset($resu['rows'][$key]);
                continue;
            }

            if (empty($value['img'])) {
                $value['img'] = 0;
            }

            if (empty($value['username'])) {
                $user_id = $value['uid'];
                if (empty($cache['user_id'][$user_id])) {
                    $user = $this->user_cache($user_id);
                    $cache['user_id'][$user_id] = $user;
                }
                $username = $cache['user_id'][$user_id]['username'];
//                $value['username'] = $username{0}.$username{1}.
//                                    str_repeat('*', strlen($username)-3).
//                                    $username{strlen($username)-1};
                $value['username'] = uname_hide($username);
            }

            //unset($value['uid']);
        }

        array_multisort(array_column($resu['rows'], 'lucky_price'), SORT_DESC, $resu['rows']);
        
        if (empty($resu['rows'])) {
            return [];
        } else {
            return $resu['rows'];
        }
    }

    /**
     * @param $data
     * @return array
     */
    public function get_report($data)
    {
        $field = 'SUM(bets_num) as bets_num, SUM(price) as bets_total, SUM(valid_price) as total_v_bet, SUM(lucky_price - price) as win_or_lose';
        $where = [
            'uid' => $data['uid'],
            'report_date >=' => $data['start'],
            'report_date <' => $data['end'],
        ];
        $rs = $this->get_one($field, 'report', $where);
        return [
            'total' => (float)$rs['bets_num'],
            'total_bet' => (float)$rs['bets_total'],
            'total_v_bet' => (float)$rs['total_v_bet'],
            'win_or_lose' => (float)$rs['win_or_lose'],
        ];
    }


    /**
      *@desc 互动大厅获取最新的中奖金额大于指定金额的情况
      **/
    public function get_wins_list()
    {
        //.从gc_set里面获取设置的指定金额 默认为1000
        $gc_set = $this->get_gcset(['ws_lt_wins_money']);
        $ws_lt_wins_money = isset($gc_set['ws_lt_wins_money'])?$gc_set['ws_lt_wins_money']:1000;
        //.取上一次保存的bet_wins中最后一条的中奖信息,取这个值以后的数据
        $last_win_key = 'ws_wins_last_id';
        $ws_wins_last_id = $this->redis_get($last_win_key);
        $ws_wins_last_id = $ws_wins_last_id?$ws_wins_last_id:1;//.默认从头开始取数据
        //.查询数据
        $where['a.id >'] = $ws_wins_last_id;
        $where['a.status'] = 1;
        $where['a.price_sum >='] = $ws_lt_wins_money;
        $field = "a.id,a.order_num,a.uid,a.price_sum,a.win_counts,a.status,b.gid,b.issue,c.username";
        $this->db->select($field);
        $this->db->from('bet_wins as a');
        $this->db->join('bet_index as b','a.order_num = b.order_num');
        $this->db->join('user as c','a.uid = c.id');
        $this->db->order_by('a.id', 'desc');
        $winsData = $this->db->where($where)->get()->result_array();
        //.将到现在为止,wins表中的最后一条数据存入redis
        $sql = "select max(id) as last_id from gc_bet_wins where id >".$ws_wins_last_id;
        $id_max = $this->db->query($sql)->row_array();

        if(!empty($id_max['last_id'])){
            $this->redis_set($last_win_key,$id_max['last_id']);
        }
        if (empty($winsData)) {
            return false;
        }
        //.转换彩种名字,
        foreach($winsData as $k=>$row){
            $game_msg = json_decode($this->redisP_hget('games',$row['gid']),true);
            $winsData[$k]['game_name'] =$game_msg['name'];
            $winsData[$k]['sname'] =$game_msg['sname'];
            $winsData[$k]['cptype'] =$game_msg['cptype'];
        }
        /* 提取订单号 */
        $order = $this->_get_order_num($winsData);
        $resu = $this->_query_games_bet($order);
          /* 对游戏下注表的数据进行格式化 */
        $data = $this->_format_ws_wins_data($resu,$winsData);
        return $data;
    }

    /**
      *@desc 互动大厅获取所有用户的中奖命中率
      *@desc uid
      **/
    public function get_wins_rate_list($uid)
    {
        $date = date('Y-m-d');//.判断日期
        //.测试数据
        // $date = '2018-11-21';
        //.直接从report表中取出今天用户的注单数据

        $where = 'report_date = "'. $date .'"';
        if ( !empty($uid) ) $where .= ' AND uid='. $uid;

        $sql = "select sum(num) as bet_counts,sum(num_win) as win_counts,uid from gc_report where ". $where ." group by uid" ;
        $hit_data = $this->db->query($sql)->row_array();
        if ( empty($hit_data) ) return ['bet_counts' => 0, 'win_counts' => 0, 'hit_rate' => ''];

        $hit_data['hit_rate'] = sprintf('%.2f',($hit_data['win_counts']/$hit_data['bet_counts'])*100).'%';
        return $hit_data;

        //.复杂逻辑
        // //.首先先取用户的今天的注单数,根据今天的注单数算命中率
        // $sql_bet_index = "select uid, order_num from  gc_bet_index where created >= ".$time;
        // $bet_data = $this->db->query($sql_bet_index)->result_array();
        // //.获取用户注单的详细信息,主要是获取注数数据
        // $order = $this->_get_order_num($bet_data);
        // $resu = $this->_query_games_bet($order);
        // //.取出中奖数据
        // $sql_bet_wins = "select order_num,win_counts,uid from gc_bet_wins where status = 1 and created >$time";
        // $wins_data = $this->db->query($sql_bet_wins)->result_array();
        // //。计算用户今天的命中率
        // $data = $this->_user_hit_rate($bet_data,$resu,$wins_data);
        // return $data;
    }

    /*******************************************/






    /******************私有方法*******************/
    /**
     * 提取订单号
     * @param $bet_idx 订单号数据
     */
    private function _get_order_num($bet_idx)
    {
        $order_arr = array();
        foreach ($bet_idx as $key => $value) {
            $gid = (int)substr($value['order_num'], 1, 2);
            $order_arr[$gid][] = $value['order_num'];
        }
        return $order_arr;
    }

    /** 
     * 查询开奖状态
     * @param type 查询状态
     * @param bet_idx 查询数据
     * return bet_idx
     */
    private function _query_open_status($type, $bet_idx)
    {
        $this->select_db('private');
        $open_arr = array();
        foreach ($bet_idx as $key => $value) {
            $gid = (int)substr($value['order_num'], 1, 2);
            if(empty($open_arr[$gid][$value['issue']])) {
                $where['gid'] = $gid;
                $where['issue'] = $value['issue'];
                $open_arr[$gid][$value['issue']] = $this->get_one('lottery, issue, status','bet_settlement', $where);
            }
            $open = $open_arr[$gid][$value['issue']];
            if(!empty($open)) {
                $bet_idx[$key]['open_resu_num'] = $open['lottery'];
            }
            // 如果已有状态则不用去查询
            if(!empty($value['status'])) continue;

            // 如果没有找到数据，则为代开奖
            if(empty($open)) {
                $bet_idx[$key]['status'] = STATUS_NOTOPEN;
            }
            /* 如果期数已开奖，那么全部状态改为代开奖 */
            elseif($open['status'] == STATUS_OPEN) {
                $bet_idx[$key]['status'] = STATUS_NOTOPEN;
            }
            /* 如果期数已结算，那么状态改为未中奖 */
            elseif($open['status'] == STATUS_END) {
                $bet_idx[$key]['status'] = STATUS_LOSE;
            }
            /* 如果期数结算中，那么状态改为待开奖 */
            elseif($open['status'] == STATUS_ENDING) {
                $bet_idx[$key]['status'] = STATUS_NOTOPEN;
            }
            else {
                $bet_idx[$key]['status'] = STATUS_NOTOPEN;
            }
            
            if($type == STATUS_NOTOPEN &&
               $bet_idx[$key]['status'] != STATUS_NOTOPEN ) {
                unset($bet_idx[$key]);
            }
            elseif($type == STATUS_LOSE &&
               $bet_idx[$key]['status'] != STATUS_LOSE ) {
                unset($bet_idx[$key]);
            }
        }
        return $bet_idx;
    }

    /**
     * 查询详细的游戏下注信息
     * @param arr $order_arr
     * $order_arr = array(
     *     1=>array('order_num', 'order_num'),
     *     2=>array('order_num', 'order_num'))
     */
    private function _query_games_bet($order_arr)
    {
        /* 根据下注汇总表的记录去查询游戏下注表 */
        $this->select_db('private');
        $sql = '';
        foreach ($order_arr as $key => $value) {
            if(empty($value)) continue;
            $os = '';
            foreach ($value as $k => $v) {
                $os .= '"'.(string)$v.'"'.',';
            }
            $os = rtrim($os, ',');
            $sql .= '(SELECT gid, order_num, issue, price, price_sum, src,
                    tid, names, bet_time, rate,counts, rebate,end_time,status as info_status,"'.
                    $this->games_arr[$key]['label'].'" game 
                     FROM gc_'.$this->games_arr[$key]['tablen'].' 
                     WHERE order_num IN('.$os.'))
                     UNION ALL';
        }
        if(empty($sql)) return false;
        $sql = rtrim($sql, 'UNION ALL');
        $resu = $this->db->query($sql)->result_array();
        if(empty($resu)) return false;
        return $resu;
    }

    /**
     * 对下注数据进行格式化(APP)
     * 例如：日期转换，金额转换，获取玩法，以及未中奖，代开奖状态的复制，删除不必要字段
     * @param array resu     游戏下注数据
     * @param array $bet_idx 游戏下注汇总数据
     */
    private function _format_data_app($resu, &$bet_idx)
    {
        /* 对结果进行日期排序 */
        foreach($resu as $k=>$v){
            $tag[]=$v['bet_time'];
        }
        array_multisort($tag,SORT_DESC,$resu);

        /* 数据格式化 */
        $type_ids = array();
        $bet_idx = array_make_key($bet_idx, 'order_num');
        foreach ($resu as $key => $value) {
            $o_num = $value['order_num'];
            $value['bet_time']  = date('Y-m-d H:i:s', $value['bet_time']);
            $value['status']    = (string)$bet_idx[$o_num]['status'];
            $value['rebate']    = (float)$value['rebate'];
            $value['price']     = (float)$value['price'];
            $value['price_sum'] = empty($value['price_sum']) ? 0 : $value['price_sum'];
            $gid = substr($o_num, 1, 2);
            $value['tname']     = $this->Games_model
                                ->sname($gid, $value['tid'], true);
            if(!empty($bet_idx[$o_num]['open_resu_num'])) {
                $value['open_resu_num'] = $bet_idx[$o_num]['open_resu_num'];
            }
            if($value['status'] == STATUS_WIN) {
                $value['win_price'] = $bet_idx[$o_num]['price_sum'];
                $value['win_counts'] = $bet_idx[$o_num]['win_counts'];
            }
            if($value['status'] != STATUS_WIN) {
                unset($value['rate']);
                unset($value['rebate']);
            }
            if (isset($bet_idx[$o_num]['agent_id']) && $bet_idx[$o_num]['agent_id']) {
                $value['username']  = $this->get_agent_name($bet_idx[$o_num]['uid']);
            }
            unset($value['tid']);
            $resu[$key] = $value;
        }
        return $resu;
    }

    /**
      *@desc 互动大厅中奖格式化数据
      *@param $resu 订单数据
      *@param $bet_idx 数组数据
      */
    private function _format_ws_wins_data($resu, $bet_idx)
    {
        //.互动大厅显示的彩种和游戏名在里面的才通知
        $ws_cz = ['k3','ssc','pk10'];
        $ws_games = ['jslhc'];
        $bet_idx = array_make_key($bet_idx, 'order_num');
        $data = [];
        foreach ($resu as $key => $value) {
            $o_num = $value['order_num'];
            $gid = substr($o_num, 1, 2);
            if(in_array($bet_idx[$o_num]['sname'], $ws_games)||in_array($bet_idx[$o_num]['cptype'], $ws_cz)){
                $bet_idx[$o_num]['gname']  = $this->Games_model
                                ->sname($gid, $value['tid'], true);
                $data[] = $bet_idx[$o_num];
            }
            
        }
        return $data;
    }

    /**
      *@desc 互动大厅中奖格式化数据
      *@param $bet_data 用户订单数据
      *@param 用户订单数据的详细信息
      *@param $win_data 用户赢的订单数据
      */
    private function _user_hit_rate($bet_data,$resu, $win_data)
    {
        //.先将注单里面的注数写道订单数据中
        $bet_data = array_make_key($bet_data, 'order_num');
        $data = [];
        foreach ($resu as $key => $value) {
            $o_num = $value['order_num'];
            $bet_data[$o_num]['counts']  = $value['counts'];
            $data[] = $bet_data[$o_num];
        }
        //。$data转换成最新的注单信息 
        $uid_group_bet_data = [];
        foreach ($data as $k1 => $row1) {
            $uid = $row1['uid'];
            $uid_group_bet_data[$uid][] = $row1;
        }
        foreach ($uid_group_bet_data as $k11 => $row11) {
            $uid_group_bet_data[$k11]['counts']='';
            foreach ($row11 as $k111 => $row111) {
                $uid_group_bet_data[$k11]['counts']+=$row111['counts'];
            }
        }
        //.转换赢得注单数 
        $uid_group_win_data = [];
        foreach ($win_data as $k2 => $row2) {
             $uid = $row2['uid'];
             $uid_group_win_data[$uid][] = $row2;
        }
        foreach ($uid_group_win_data as $k22 => $row22) {
            $uid_group_win_data[$k22]['counts'] = '';
            foreach ($row22 as $k222 => $row222) {
                $uid_group_win_data[$k22]['counts']+=$row222['win_counts'];
            }
        }
        //.计算下注用户的命中率
        $hit_data = [];
        foreach($uid_group_bet_data as  $kk =>$rr){
            //.当前用户赢的注数
            $user_win_counts = isset($uid_group_win_data[$kk])?$uid_group_win_data[$kk]['counts']:0;
            $hit_data[$kk]['uid'] = $kk;
            $hit_data[$kk]['counts'] = $rr['counts'];
            $hit_data[$kk]['hit_rate'] = sprintf("%01.2f", ($user_win_counts/$rr['counts'])*100).'%';
        }
        return $hit_data;
    }



    /**
     * 对下注数据进行格式化(后台)
     * 例如：日期转换，金额转换，获取玩法，以及未中奖，代开奖状态的复制，删除不必要字段
     * @param array resu     游戏下注数据
     * @param array $bet_idx 游戏下注汇总数据
     * @param int   $src     来源
     */
    private function _format_data_admin($resu, &$bet_idx, $src)
    {
        /* 对结果进行日期排序 */
        foreach($resu as $k=>$v){
            $tag[]=$v['bet_time'];
        }
        array_multisort($tag,SORT_DESC,$resu);
        /* 数据格式化 */
        $type_ids = array();
        $bet_idx = array_make_key($bet_idx, 'order_num');
        foreach ($resu as $key => $value) {
            if (!empty($src) && $value['src'] != $src) {
                unset($resu[$key]);
                continue;
            }
            $o_num = $value['order_num'];
            $value['bet_time']  = date('Y-m-d H:i:s', $value['bet_time']);
            $value['agent_id']  = $bet_idx[$o_num]['agent_id'];
            //$value['src']       = $bet_idx[$o_num]['src'];
            $value['status']    = $bet_idx[$o_num]['status'];
            $value['account']    = $bet_idx[$o_num]['account'];
            $value['price']     = $value['price'];
            $value['uid']     = $bet_idx[$o_num]['uid'];
            $value['win_price'] = empty($bet_idx[$o_num]['price_sum']) ? 0 : (float)$bet_idx[$o_num]['price_sum'];
            $value['price_sum'] = (float)$value['price_sum'];
            if (in_array($value['status'], [STATUS_LOSE,STATUS_WIN])) {
                $value['price_return'] = round($value['price_sum']*$value['rebate']/100, 3);
            } else {
                $value['price_return'] = 0;
            }
            if((int)$value['status'] != STATUS_NOTOPEN) {
                $value['price_diff'] = round($value['win_price'] - $value['price_sum'], 3);
            } else {
                $value['price_diff'] = '-';
            }
            $value['rebate']    = $value['rebate'].'%';
            $gid = substr($o_num, 1, 2);
            $value['tname']     = $this->Games_model->sname($gid, $value['tid'], true);
            unset($value['tid']);
            $resu[$key] = $value;
        }
        return $resu;
    }

    /**
     * 如果查询的下注有代开奖，则需要判断是否能撤单（根据最新期数是否封盘）
     */
    public function _query_is_close($type, $resu)
    {
        if(!in_array($type, array(STATUS_All, STATUS_NOTOPEN)))
            return $resu;

        /* 需要查询的彩种 */
        $this->load->model('Open_time_model');
        $gid_arr = array();
        foreach ($resu as $key => $value) {
            if($value['status'] == 4) {
                $gid = (int)substr($value['order_num'], 1, 2);
                if(empty($gid_arr[$gid][$value['issue']])) {
                    $gid_arr[$gid][$value['issue']] = $this->Open_time_model->get_kithe($gid);
                }
                $resu[$key]['is_open'] = $gid_arr[$gid][$value['issue']]['is_open'];
            }
        }
        return $resu;
    }


    /**
     * 把代理id转换为代理名称
     *
     * @access private
     * @param Array $resu   注单数组
     * @return Array        注单数组
     */
    private function _format_agent($data)
    {
        if(empty($data)) return $data;

        // 初始化0的值
        $cache['user_id'][0] = ['username'=>'-'];
        foreach ($data as $k => $v) {
            $agent_id = $v['agent_id'];

            if(empty($cache['user_id'][$agent_id])) {
                $user = $this->user_cache($agent_id);
                $cache['user_id'][$agent_id] = $user;
            }

            $v['agent_name'] = $cache['user_id'][$agent_id]['username'];
            $data[$k] = $v;
        }
        return $data;
    }

    /**
     * 根据代理id获取代理名：用redis
     * @param $id
     * @return string
     */
    private function get_agent_name($id)
    {
        $r = $this->core->user_cache($id);
        return isset($r['username']) ? $r['username'] : '';
    }

    /**
     * 获取汇总数据
     *
     * @access private
     * @param Array $data 注单数组
     * @return Array
     */
    private function _get_footer($data = [])
    {
        // 如果注单状态=3(撤单)，中奖金额=0
        $price_return = $price = $price_sum = $win_price = $price_diff = 0;
        foreach ($data as $key => $value) {
            if ($value['status']==3) {
                $data[$key]['win_price'] = 0;
                $value['win_price'] = 0;
            }
            $price_return += $value['price_return'];
            $price += $value['price'];
            $price_sum += $value['price_sum'];
            $win_price += $value['win_price'];
            $price_diff += $value['price_diff'] == '-' ? 0 : $value['price_diff'];
        }
        $rs = ['total' => 100000, 'rows' => $data, 'footer'=>[
            ['price_return'=>(float)sprintf('%0.3f', $price_return),
            'price'=>(float)sprintf('%0.3f', $price),
            'price_sum'=>(float)sprintf('%0.3f', $price_sum),
            'win_price'=>(float)sprintf('%0.3f', $win_price),
            'price_diff'=>(float)sprintf('%0.3f', $price_diff)]]];
        return $rs;
    }
    /*******************************************/
}




