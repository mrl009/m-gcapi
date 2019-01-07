<?php
//session_start();
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Red_bag_model extends MY_Model {

    private $red_set = "red_bag:set";
    private $zddKey  = "";
    private $hsetKey = "";
    private $bagInc  = "";
    public  $userInc = "";
    private $incrMoney = "";
    private $bagData = []; //红包数组
    private $bagSet  = [];//红包等级信息

    public function __construct()
    {
        parent::__construct();
        $this->zddKey = 'red_bag:zadd';
        $this->hsetKey = 'red_bag:list';
        $this->bagInc = 'red_bag:incr';
        $this->userInc = 'red_bag:user_num:%d';
    }

    /**
     * 从reids获取当前时间段的红包
     */
    public function get_bag()
    {

        //获取上一次cash的结果
        $createdKeys = 'red_bag:created'.date('Y-m-d');
        /*if ($this->redis_get($createdKeys) == 'empty') {
            return false;
        }*/
        if (!$this->redis_EXISTS($this->zddKey) || !$this->redis_EXISTS($this->hsetKey) || !$this->redis_exists($createdKeys)) {
            $bool = $this->cash_bag();
            if (!$bool) {
                return false;
            }
        }
        $i =1;
        while ($i <= 10) {
            $bagId = $this->redis_zrange($this->zddKey,0,0);

            if (!isset($bagId[0])) {
               return false;
            }
            $i++;
            $bagId = $bagId[0];
            $json = $this->redis_hget($this->hsetKey,$bagId);
            if (empty($json)) {
                $this->redis_del($this->zddKey);
                $bool = $this->cash_bag();
                if (!$bool) {
                    return false;
                }else{
                    continue;
                }
            }
            $data = json_decode($json,true);
            if($data['end_time'] <= $_SERVER['REQUEST_TIME']){
                $this->redis_zrem($this->zddKey,$bagId);
                $this->redis_hdel($this->hsetKey,$bagId);
                $this->redis_hdel($this->bagInc,$bagId);
                continue;
            }

            if (!$this->redis_hexists($this->bagInc,$bagId)) {
                $this->redis_HSET($this->bagInc,$bagId,0);
                //$this->redis_expire($this->bagInc,A_RED_ADD_TIME_LIMIT*60+3600);
            }
            return $data;
        }

    }

    /**
     * 检查红包是否已近开始
     * @param $id int 红包id
     * @return bool
    */
    public function check_bag($id)
    {
        $json = $this->redis_hget($this->hsetKey,$id);
        if (empty($json)) {
            return '没有该红包或者红包已经抢完';
        }
        $bagData = json_decode($json,true);
        if ($bagData['end_time'] <= $_SERVER["REQUEST_TIME"]) {
            return '来晚了红包已经结束';
        }

        if ($bagData['start_time'] >= $_SERVER['REQUEST_TIME']) {
            return '别急红包还没开始';
        }

        if (!empty($bagData['stop']) ) {
            return OK_RED;
        }
        return true;
    }

    /**
     * 抢红包!
     * @param $bagId int 红包id
     * @return  bool
    */
    public function bag_do($bagId)
    {
        $bagData = $this->redis_hget($this->hsetKey,$bagId);
        $this->bagData = json_decode($bagData,true);
        if (!is_array( $this->bagData)) {
            $this->cash_bag();
            return '数据解析失败';
        }
        if ($this->redis_hget($this->bagInc,$bagId) >=  $this->bagData['total'] && $this->bagData['total'] !=0 ) {
            return '红包已抢完,请等下次';
        }
        $ci = get_instance();
        $this->bagSet = $this->user_level($ci->user['id']);
        $this->userInc = sprintf($this->userInc,$bagId);
        $userNum = $this->redis_HINCRBY($this->userInc,$ci->user['id'],1);
        $this->redis_expire($this->userInc,60*24*60+100);
        if ($userNum > $this->bagSet['count']) {
            $this->redis_HINCRBY($this->userInc,$ci->user['id'],-1);
            return '没有剩余抢红包的次数';
        }
        $money = $this->red_rand($bagId);

        if ($money <= 0) {
            $this ->red_rollBACK($ci->user['id'],$bagId);
            return '红包抢完1';
        }
        //开始执行现金流程 不需要稽核
        $ip =  get_ip();
        $insert = [
            'uid'   => $ci->user['id'],
            'total' => $money,
            'src'   => $ci->from_way,
            'add_time' => $_SERVER['REQUEST_TIME'],
            'bag_id' => $bagId,
            'ip' => $ip,
        ];

        $this->db->trans_begin();
        $bool = $this->write('red_order',$insert);
        if (!$bool) {
            $this ->red_rollBACK($ci->user['id'],$bagId);
            $this->db->trans_rollback();
            return '请重试0001';
        }
        //$where = [ 'id' => $bagId, 'current_total >' => "0" ];
        //$bool = $this->db->set('current_total','current_total-'.$money,false)->update('red_activity',[],$where);

        if ($this->bagData['total'] == 0) {
            $sql = "UPDATE gc_red_activity set current_total = current_total+$money where  id =$bagId ";
        }else{
            $sql = "UPDATE gc_red_activity set current_total = current_total+$money where  id =$bagId  and current_total+$money<=total ";

        }
        $this->db->query($sql);
        $bool = $this->db->affected_rows();
        if ($bool<=0) {
            $this ->red_rollBACK($ci->user['id'],$bagId);
            $this->db->trans_rollback();
            return '请重试0002'.$money;
        }
        $order_num =order_num(1,99);
        $bool = $this->update_banlace($ci->user['id'],$money,$order_num,11,'抢红包');
        if (!$bool) {
            $this ->red_rollBACK($ci->user['id'],$bagId);
            $this->db->trans_rollback();
            return '请重试0003';
        }
        $this->load->model('Comm_model', 'comm');

        //写入报表
        $cash_data =  [
            'activity_total' => $money,
            'activity_num' => 1,
            'uid' => $ci->user['id'],
            'agent_id' => $ci->user['agent_id'],
        ];
        $bool = $this->comm->collect_cash_report($ci->user['id'], date('Y-m-d'),$cash_data);
        if (!$bool) {
            $this ->red_rollBACK($ci->user['id'],$bagId);
            //$this->redis_HINCRBY($this->userInc,$ci->user['id'],-1);
            //$this->redis_HINCRBYFLOAT($this->bagInc,$bagId,$money*-1);
            $this->db->trans_rollback();
            return '请重试0004';
        }
        $this->db->trans_commit();
        $str = "{$ci->user['id']}抢得红包(ID:{$bagId}){$money}使用参数".json_encode($_REQUEST);
        wlog(APPPATH.'logs/red_'.$this->sn.'_'.date('Ym').'log',$str);
        $this->redis_hset($this->hsetKey,$bagId,json_encode($this->bagData));
        $userNum = $this->bagSet['count'] - $userNum;
        if ($userNum < 0) {
            $userNum = 0;
        }
        $data = [];
        $data['money'] = (string)$money;
        $data['surplus_num'] = (string)$userNum;
        return $data;
    }

    /**
     * 红包金额随机
     * @param  $bagData array 红包数据
     * @return float 抢到金额
    */
    private function red_rand($bagId)
    {
        $i=0;
        while($i<5){
            $i++;
            $money = mt_rand( $this->bagSet['start_total']*100, $this->bagSet['end_total']*100)/100;
            if ($money > 0) {
                $i=111;
            }
        }
        $this->incrMoney = $money;
        $bagMoney = $this->redis_HINCRBYFLOAT($this->bagInc,$bagId,$money);

        if ($bagMoney >=  $this->bagData['total'] && $this->bagData['total'] != 0) {
            $money = $this->bagData['total']+$money-$bagMoney;
            /*$moneyData = $this->get_one('current_total','red_activity', [ 'id' => $bagId] );
            $money =  $moneyData['current_total'];*/
            $this->bagData['stop'] =1;
        }
        $money = round($money,2);
        return $money;
    }
    /**
     * 查询会员的存款量,可抢次数
     * @param $uid int 会员id
     * @return  array 会员昨日存款
     */
    public function user_level($uid)
    {
        $where = [
            'uid' => $uid,
            'report_date' => date('Y-m-d',strtotime('-1 day'))
        ];
        $userData = $this->get_one('sum(in_company_total+in_online_total+in_people_total) as total','cash_report',$where);
        $user_level = $this->get_bag_set();
        if (empty($user_level)) {
            return [ 'total' => $userData['total'], 'count' =>'0' ];
        }
        array_multisort(array_column($user_level,'start_recharge'),SORT_DESC,$user_level);
        empty($userData['total'])?$userData['total']="0":true;
        foreach ($user_level as $value) {
            if ($userData['total'] >= $value['start_recharge']  ) {
                $userData = array_merge($userData,$value);
                break;
            }else{
                $userData['count'] = "0";
                $userData['start_recharge'] = "0";
                $userData['end_recharge'] = "0";
                $userData['start_total'] = "0";
                $userData['end_total'] = "0";
            }
        }

        return $userData;
    }
    /**
     * 缓存红包
     */
    private function cash_bag()
    {
        $this->redis_del($this->zddKey);
        $this->redis_del($this->hsetKey);
        $start = strtotime(date('Y-m-d 00:00:00'));
        $end   = strtotime(date('Y-m-d 23:59:59'));
        /*$where = [
            'start_time >=' => $start,
            'start_time <=' => $end,
        ];
        $where2 = [
            'orwhere' => [
                'end_time >=' => $start,
                'end_time <=' => $end,
            ],
            'orderby' => [
                'start_time' => 'asc',
                'end_time' => 'asc',
            ],
        ];*/
        $now = $_SERVER['REQUEST_TIME'];
        $sql = "SELECT *
                FROM `gc_red_activity` `a`
                WHERE (start_time BETWEEN $start AND $end AND end_time > $now ) OR  (end_time BETWEEN $start AND $end AND end_time>$now)
                ORDER BY `start_time` ASC, `end_time` ASC";
        $arrData = $this->db->query($sql)->result_array();

        $creadKeys = 'red_bag:created'.date('Y-m-d');
        if (empty($arrData)) {
            $this->redis_setex($creadKeys,3600,'empty');
            return false;
        }
        $this->redis_setex($creadKeys,24*3600,$_SERVER['REQUEST_TIME']);
        $bagList = [];
        foreach ($arrData as  $k => $value) {
            $bagList[$value['id']] = json_encode($value,JSON_UNESCAPED_UNICODE);
            $this->redis_zadd($this->zddKey,$value['start_time'], $value['id']);
        }
        $this->redis_hmset($this->hsetKey,$bagList);
        $this->redis_EXPIRE($this->zddKey,24*3600+3600);
        $this->redis_EXPIRE($this->hsetKey,24*3600+3600);
        return true;
    }


    /**
     * 获取红包设置
     * @return  array
    */
    public function get_bag_set()
    {
        $redSet = $this->redis_get($this->red_set);
        if (!$redSet) {
            $where2 = [
                'orderby' => [ 'start_recharge' => 'desc', 'end_recharge'=> 'desc']
            ];
            $redSet = $this->get_all('','red_set',[],$where2);
            $this->redis_set($this->red_set,json_encode($redSet));
            $this->redis_expire($this->red_set,1800);
        }else{
            $redSet = json_decode($redSet,true);
        }
        return $redSet;
    }

    /**
     * 失败的时候回滚redis记录次数
     * @param  $uid float 会员ID
     * @param  $bagId int 红包ID
     * @param $money float 随机的红包金额
     *
    */
    private function red_rollBACK($uid,$bagId)
    {
        $this->redis_HINCRBY($this->userInc,$uid,-1);
        $this->redis_HINCRBYFLOAT($this->bagInc,$bagId,$this->incrMoney*-1);
    }

}
