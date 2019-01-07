<?php
if (!defined('BASEPATH')) {exit('No direct access allowed.');}

class Agent_bet_detail_model extends MY_Model{
    public function __construct()
    {
        parent::__construct();
        //获取游戏列表
        $this->select_db('public');
        $gamelist = $this->get_list('CONCAT("bet_",a.sname) as tablen,a.name as label,id,img','games');
        $this->games_arr = array_make_key($gamelist, 'id');
        $this->select_db('private');
    }

    private $games_arr = null;

    public function get_bet_list($type,$where,$condition)
    {
        $select = 'a.order_num,a.issue,a.uid,a.gid,a.created,u.username,s.lottery,';
        $this->db->from('bet_index as a')
            ->join('user as u','u.id=a.uid','inner');
        if ($type === STATUS_WIN) {
            $select .= 'w.price_sum,1 `status`';
            $this->db->join('bet_wins as w','a.order_num=w.order_num and w.status=1','inner');
            $this->db->join('bet_settlement as s','a.gid=s.gid and a.issue=s.issue','inner');
        }elseif ($type === STATUS_HE){
            $select .= '0 price_sum,2 `status`';
            $this->db->join('bet_wins as w','a.order_num=w.order_num and w.status=2','inner');
            $this->db->join('bet_settlement as s','a.gid=s.gid and a.issue=s.issue','inner');
        }elseif ($type === STATUS_CANCEL){
            $select .= '0 price_sum,3 `status`';
            $this->db->join('bet_wins as w','a.order_num=w.order_num and w.status=3','inner');
            $this->db->join('bet_settlement as s','a.gid=s.gid and a.issue=s.issue','inner');
        } elseif ($type === STATUS_LOSE) {
            $select .= '0 price_sum,5 `status`';
            $this->db->join('bet_settlement as s','a.gid=s.gid and a.issue=s.issue','inner');
            $this->db->join('bet_wins as w','a.order_num=w.order_num','left')
                ->where('w.order_num IS NULL');
        } elseif ($type === STATUS_NOTOPEN) {
            $select .= '0 price_sum,4 `status`';
            $this->db->join('bet_settlement as s','a.gid=s.gid and a.issue=s.issue ','left');
            $this->db->join('bet_wins as w','a.order_num=w.order_num','left')
            ->where('s.lottery IS NULL AND w.`status` IS NULL ');
        } elseif ($type === 0) {
            $select .=
                '(CASE 
                WHEN w.`status`=1 
                THEN w.`price_sum` 
                ELSE 0 END) as `price_sum`,
                (CASE 
                WHEN s.`status`=3 
                  AND w.`status` IS NULL 
                  THEN 5 
                WHEN s.`status`=3 
                  AND w.`status`=1 
                  THEN 1 
                WHEN s.`status`=3 
                  AND w.`status`=2 
                  THEN 2 
                WHEN w.`status`=3 
                  THEN 3 
                WHEN s.`lottery` IS NULL 
                  AND w.`status` IS NULL 
                  THEN 4 
                ELSE 4 END) AS `status`';
            $this->db->join('bet_wins as w','a.order_num=w.order_num','left');
            $this->db->join('bet_settlement as s','a.gid=s.gid and a.issue=s.issue','left');
        } else {
            return false;
        }
        $this->db->select($select);
        if (isset($where['wherein'])){
            foreach ($where['wherein'] as $k => $v) {
                if ($v != '') {
                    $this->db->where_in($k, $v);
                }
            }
            unset($where['wherein']);
        }
        $sql = $this->db->where($where)
            ->order_by('a.created','DESC')
            ->limit($condition[0],$condition[1])
            ->get_compiled_select();
        $bet_list = $this->db->query($sql)->result_array();
        if (empty($bet_list)) {
            return false;
        }
        $bet_list = $this->get_games_bet($bet_list);
        if (empty($bet_list)) {
            return false;
        }
        return $bet_list;
    }

    public function get_games_bet($lottery_status){
        $this->select_db('private');
        foreach ($lottery_status as $k => $v){
            $gid = $v['gid'];
            $res = $this->db->select("contents,names,price_sum,'{$this->games_arr[$gid]['label']}' as game")->from($this->games_arr[$gid]['tablen'])->where(array('order_num'=>$v['order_num']))->get()->row_array();
            $lottery_status[$k]['contents'] = $res['contents'];
            $lottery_status[$k]['names'] = $res['names'];
            $lottery_status[$k]['price_sum'] = $res['price_sum'];
            $lottery_status[$k]['game'] = $res['game'];
        }
        return $lottery_status;
    }

    public function get_game_detail($order_num,$gid)
    {
        $this->select_db('private');
        $detail = $this->db->select("order_num,issue,'{$this->games_arr[$gid]['label']}' as game,tid,contents,names,counts,price,price_sum,rate,bet_time,CONCAT(rebate,'%','/',ROUND(price_sum*rebate*0.01,3))as rebate_price")
            ->from($this->games_arr[$gid]['tablen'])
            ->where(array('order_num'=>$order_num))
            ->get()
            ->row_array();
        //获得开奖号及开奖状态
        $issue = $detail['issue'];
        $lottery = $this->db->select('lottery,status')->get_where('bet_settlement',array('gid'=>$gid,'issue'=>$issue))->row_array();
        $detail['lottery']=$lottery['lottery'];
        //获得中奖数据
        $wins = $this->db->select('win_counts,price_sum as win_sum,status')->get_where('bet_wins',array('order_num'=>$order_num))->row_array();
        if ($lottery['lottery'] == '' && $wins['status'] == ''){
            $detail['status'] = STATUS_NOTOPEN;
            $detail['win_counts'] = 0;
            $detail['win_sum'] = 0;
            $detail['rebate_price'] = 0;
        } else if ($lottery['lottery'] == '' && $wins['status'] == 3){
            $detail['win_counts'] = 0;
            $detail['win_sum'] = 0;
            $detail['status'] = $wins['status'];
            $detail['rebate_price'] = 0;
        }else if (!empty($wins) && $wins['status'] == 1) {
            $detail['win_counts'] = $wins['win_counts'];
            $detail['win_sum'] = $wins['win_sum'];
            $detail['status'] = $wins['status'];
        } else if (!empty($wins) && $wins['status'] == 2) {
            $detail['win_counts'] = 0;
            $detail['win_sum'] = $detail['price_sum'];
            $detail['status'] = $wins['status'];
            $detail['rebate_price'] = 0;
        } else if (!empty($wins) && $wins['status'] == 3) {
            $detail['win_counts'] = 0;
            $detail['win_sum'] = 0;
            $detail['status'] = $wins['status'];
            $detail['rebate_price'] = 0;
        } else if (empty($wins)) {
            $detail['win_counts'] = 0;
            $detail['status'] = STATUS_LOSE;
            $detail['win_sum'] = 0;
        }

        //将tid数字转换成对应玩法的文字
        $tid = $detail['tid'];
        $this->select_db('public');
        $types = $this->db->from('games_types as a')->select('b.name')->join('games_types as b','a.pid=b.id')->where(array('a.id'=>$tid))->get()->row_array();
        $detail['tid'] = $types['name'];
        $detail['gid'] =$gid;
        foreach ($this->games_arr as $k => $v){
            if ($gid==$k){
                $detail['gameimg'] = $v['img'];
            }
        }
        $this->select_db('private');
        return $detail;

    }
    public function get_userId_by_username($username)
    {
        $this->select_db('private');
        $uid = $this->db->select('id')->get_where('user',array('username'=>$username))->row();
        return $uid->id;
    }

    public function games_list()
    {
        $games = $this->games_arr;
        return $games;
    }

    /*获得所有下级代理的uid*/
    public function get_children_uid($uid)
    {
        $this->select_db('private');
        $childrenUid = $this->db->select('descendant')->get_where('agent_tree',array('ancestor'=>$uid))->result_array();
        $childrenUid = array_column($childrenUid,'descendant');
        return $childrenUid;
    }

}