<?php
defined('BASEPATH') or exit('No direct script access allowed');


class Red_packet extends MY_Controller
{
    private static $wsAct;
    public function __construct()
    {
        if (is_cli()) {
            CI_Controller::__construct();
            $this->load->model('GC_Model', 'M');

        } else {
            parent::__construct();

            $this->load->model('GC_Model', 'M');

            $this->load->helper('interactive');
            self::$wsAct = new WsAct($this->M->sn);
        }
    }

    private function ruleCheck($act){
        $rule = self::$wsAct->get('wshddt:vip_access');
        $rule = json_decode($rule, true);

        $vip = $this->user['vip_id'];
        if (!$rule || empty($rule[$vip])) {
            // 没有对应的VIP，不允许操作
            $this->return_json(E_ARGS, 'VIP等级限制');
        }
        $rule = $rule[$vip];

        if ( empty($rule[$act]) ) $this->return_json(E_ARGS, 'VIP等级限制!');

        $date = date('Y-m-d');
        switch ($act) {
            case 'red_send_num':
                $row = $this->M->get_one('COUNT(id) AS count', 'red_packet',
                    ['addtime >=' => strtotime($date), 'uid' => $this->user['id']]);

                if ( $row['count'] >= $rule[$act] ) {
                    $this->return_json(E_ARGS, 'VIP等级限制!!');
                }
                break;

            case 'red_grab_num':
                $row = $this->M->get_one('COUNT(id) AS count', 'red_packet_get',
                    ['addtime >=' => strtotime($date), 'uid' => $this->user['id']]);

                if ( $row['count'] >= $rule[$act] ) {
                    $this->return_json(E_ARGS, 'VIP等级限制!!!');
                }
                break;
        }
    }

    /**
     * 发红包
     */
    public function send(){
        $this->ruleCheck('red_send_num');

        $msgData = ['rid' => 0, 'msg' => ''];
        // 炫耀红包
        $orderNu = $this->input->get_post('order_num', true);
        if ( !empty($orderNu) ) {
            $order = $this->M->get_one('gid, issue, price_sum', 'bet_index AS a',
                ['a.order_num' => $orderNu, 'a.uid' => $this->user['id'], 'b.status' => 1],
                ['join' => 'bet_wins', 'on' => 'b.order_num = a.order_num']);

            if (empty($order)) {
                $this->return_json(E_ARGS, '订单不存在');
            }

            $game = json_decode($this->M->redisP_hget('games', $order['gid']), true);
            if ( !empty($game['name']) ) {
                $msgData['game'] = $game['name'];
                $msgData['issue'] = $order['issue'];
                $msgData['win_price'] = $order['price_sum'];
            }
        }

        $uid = $this->user['id'];
        $num = (int) $this->input->get_post('num', TRUE);
        $money = (float) $this->input->get_post('money', TRUE);
        $msg = $this->input->get_post('msg', TRUE);
        $msg = mb_strlen($msg) > 10 ? '' : $msg;
        $msg = empty($msg) ? '恭喜发财，大吉大利' : $msg;

        if ( $money > 1000 || $money < 10 || $money / $num < 0.1 ) {
            $this->return_json(E_ARGS, '红包金额在 10 ~ 1000');
        }

        if ( $num > 50 || $num < 1 ) {
            $this->return_json(E_ARGS, '红包个数不能超过 50');
        }

        $this->M->db->trans_begin();

        // 记录
        $data = [
            'uid'   => $uid,
            'num'   => $num,
            'money' => $money,
            'msg'   => $msg,
            'addtime'   => time(),
            'surplus'   => $num,
            'balance'   => $money,
        ];
        $rs = $this->M->db->insert('red_packet', $data);
        if ( !$rs ) {
            $this->M->db->trans_rollback();
            $this->return_json(E_ARGS, '发送失败!');
        }
        $rid = $this->M->db->insert_id();

        $msgData['rid'] = $rid;
        $msgData['msg'] = $msg;

        // 现金流水
        $orderNu = date('ymdHis') . substr(microtime(), 2, 4);
        $rs = $this->M->update_banlace($uid, -$money, $orderNu, 51, '发红包', -$money);

        if ( !$rs ) {
            $this->M->db->trans_rollback();
            $this->return_json(E_ARGS, '发送失败!!');
        }

        // 预先分配
        $rs = $this->allot($rid, $money, $num);
        if ( !$rs ) {
            $this->M->db->trans_rollback();
            $this->return_json(E_ARGS, '发送失败!!!');
        }

        // 注入WS消息
        $username = mb_substr($this->user['username'], 0, 1) . '***' . mb_substr($this->user['username'], -1);
        $username = empty($this->user['nickname']) ? $username : $this->user['nickname'];
        self::$wsAct->sendWs([
            'type'  => 'red_packet',
            'from'      => $uid,
            'from_name' => $username,
            'headimg'   => $this->user['img'],
            'msg'   => json_encode($msgData, JSON_UNESCAPED_UNICODE),
            'vip'   => $this->user['vip_id']
        ]);

        $this->M->db->trans_commit();
        $this->return_json(OK, '发送成功');
    }

    /**
     * 抢红包
     */
    public function get(){
        $this->ruleCheck('red_grab_num');

        $uid = $this->user['id'];
        $rid = (int) $this->input->get_post('rid', TRUE);
        $key = 'red_packet:r'. $rid;

        $row = $this->M->get_one('is_refund, surplus, best_money, balance', 'red_packet', ['id' => $rid, 'addtime >' => time() - 1800]);
        if (empty($row) || $row['is_refund'] ) {
            $this->return_json(E_ARGS, '红包已经飞走了');
        }
        if ( $row['surplus'] == 0 ) {
            $this->return_json(E_ARGS, '红包已抢完');
        }

        // 自己是否已抢
        $row2 = $this->M->get_one('money', 'red_packet_get', ['rid' => $rid, 'uid' => $uid]);
        if ( !empty($row2['money']) ) {
            $this->return_json(OK, $row2['money']);
        }

        $money = $this->M->redis_lpop($key);
        if ( empty($money) ) {
            $this->return_json(E_ARGS, '红包已抢完');
        }

        $money = $money / 100;

        $this->M->db->trans_begin();

        // 记录
        $data = [
            'rid'   => $rid,
            'uid'   => $uid,
            'money' => $money,
            'addtime' => time()
        ];
        $rs = $this->M->db->insert('red_packet_get', $data);
        if ( !$rs ) {
            $this->M->db->trans_rollback();

            // redis 回滚
            $this->M->redis_lpush($key, $money * 100);

            $this->return_json(E_ARGS, '抢红包失败!');
        }

        // 现金流水
        $orderNu = date('ymdHis') . substr(microtime(), 2, 4);
        $rs = $this->M->update_banlace($uid, $money, $orderNu, 52, '抢红包', $money);
        if ( !$rs ) {
            $this->M->db->trans_rollback();
            // redis 回滚
            $this->M->redis_lpush($key, $money * 100);
            $this->return_json(E_ARGS, '抢红包失败!!');
        }

        // 抢进度
        $update_data = [
            'surplus'   => $row['surplus'] - 1,
            'balance'   => $row['balance'] - $money
        ];
        if ( $row['best_money'] < $money ) {
            // 最佳
            $update_data['best_uid'] = $uid;
            $update_data['best_money'] = $money;
        }
        $rs = $this->M->db->update('red_packet', $update_data, ['id' => $rid]);
        if ( !$rs ) {
            $this->M->db->trans_rollback();
            // redis 回滚
            $this->M->redis_lpush($key, $money * 100);
            $this->return_json(E_ARGS, '抢红包失败!!!');
        }

        $this->M->db->trans_commit();
        $this->return_json(OK, $money);
    }

    /**
     * 抢红包记录
     */
    public function get_list(){
        $rid = (int) $this->input->get_post('rid', TRUE);
        $join = [
            ['table' => 'user as b', 'on' => 'red_packet.uid=b.id'],
            ['table' => 'user_detail as c', 'on' => 'red_packet.uid=c.uid'],
        ];
        $master = $this->M->get_one('red_packet.*, c.img, 
            FROM_UNIXTIME('.$this->M->db->dbprefix('red_packet') .'.addtime, "%Y-%m-%d %H:%i:%s") as addtime,
            IF(c.nickname = "", CONCAT(LEFT(b.username, 1), "***", RIGHT(b.username, 1)), c.nickname)
             as username', 'red_packet', ['red_packet.id' => $rid], ['join' => $join]);

        // 列表
        $join = [
            ['table' => 'user as b', 'on' => 'a.uid=b.id'],
            ['table' => 'user_detail as c', 'on' => 'a.uid=c.uid'],
        ];
        $list = $this->M->get_all('a.*, c.img, 
            FROM_UNIXTIME(a.addtime, "%Y-%m-%d %H:%i:%s") as addtime,
            IF(c.nickname = "", CONCAT(LEFT(b.username, 1), "***", RIGHT(b.username, 1)), c.nickname)
             as username', 'red_packet_get', ['a.rid' => $rid], ['join' => $join]);

        $this->return_json(OK, ['master' => $master, 'list' => $list]);
    }

    /**
     * 超时退回
     */
    public function refund($dbn = 'w01'){
        if (!is_cli()) {
            header('HTTP/1.1 405 fuck u!');
            $this->return_json(E_METHOD, 'method nonsupport!');
        }
        $this->M->init($dbn);

        // 生成前一天统计
        $date = date('Y-m-d');
        $row = $this->M->get_one('id', 'red_packet_report', ['report_date' => $date]);
        if ( empty($row['id']) && date('H') > 1 ) {
            $et = strtotime($date);
            $where = ['addtime <' => $et, 'addtime >=' => $et - 24 * 3600];
            $list = $this->M->get_all('uid, SUM(money) as money, SUM(balance) as balance', 'red_packet',
                $where, ['groupby' => ['uid']]);

            $reportData = [];
            if (!empty($list)) {
                $list2 = $this->M->get_all('uid, SUM(money) as money', 'red_packet_get',
                    $where, ['groupby' => ['uid']]);

                foreach ($list as $val) {
                    $reportData[$val['uid']] = [
                        'uid' => $val['uid'],
                        'report_date' => $date,
                        'packet_in' => 0,
                        'packet_out' => $val['money'],
                        'packet_refund' => $val['balance']
                    ];
                }

                foreach ($list2 as $val) {
                    $reportData[$val['uid']]['packet_in'] = $val['money'];
                }

                $this->M->db->insert_batch('red_packet_report', $reportData);
            }
        }


        $list = $this->M->get_list('*', 'red_packet', ['is_refund <>' => 1, 'balance >=' => '0.01', 'addtime <' => time() - 1800]);

        if ( empty($list) ) exit('执行完成');

        $ids = [];
        foreach ( $list as $item) {
            $orderNu = date('ymdHis') . substr(microtime(), 2, 4);
            $rs = $this->M->update_banlace($item['uid'], $item['balance'], $orderNu, 53, '红包退回', $item['balance']);
            if ( !$rs ) {
                $this->M->db->trans_rollback();
                @wlog(APPPATH.'logs/'.$this->sn.'_red_packet_'.date('Ym').'.log', var_export($item, true));

                exit('更新用户金额失败');
            }

            //
            $key = 'red_packet:r'. $item['id'];
            $this->M->redis_del($key);
            $ids[] = $item['id'];
        }

        // 更新表信息
        $rs = $this->M->db->update('red_packet', ['is_refund' => 1, 'refund_time' => time()], 'id in ('. implode(',', $ids) .')');
        if ( !$rs ) {
            $this->M->db->trans_rollback();
            @wlog(APPPATH.'logs/'.$this->sn.'_red_packet_'.date('Ym').'.log', var_export($ids, true));

            exit('更新红包退回失败');
        }

        exit('执行成功');
    }


    /**
     * 红包分配
     * 预先存入redis
     */
    private function allot($rid, $money, $num){
        $key = 'red_packet:r'. $rid;
        $mlist = [$key];
        $amount = 0;
        $money = $money * 100;
        for ( $num; $num > 0; $num -- ) {
            $surplus = $money - $amount - $num;
            $m = $num == 1 ? $surplus+1 : mt_rand(1, $surplus);
            $mlist[] = $m;
            $amount += $m;
        }

        return call_user_func_array([$this->M, 'redis_lpush'], $mlist);
    }
}