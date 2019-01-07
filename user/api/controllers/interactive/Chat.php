<?php
/**
 * @模块   聊天室信息
 * @版本   Version 1.0.0
 * @日期   2018-06-28
 * super
 */

/**
 * @apidoc 聊天室文档
 * @apiVersion 1.0
 * @apiBaseURL http://ltsuserapi.changge0374.com
 * @apiLicense MIT https://opensource.org/licenses/MIT
 * @apiContent
 * <p></p>
 * <p></p>
 */

defined('BASEPATH') or exit('No direct script access allowed');


class Chat extends GC_Controller
{
    private static $wsAct;

    public function __construct()
    {
        parent::__construct();
        $this->load->helper('interactive');
        $this->load->model('MY_Model', 'M');
        $this->load->model('games_model','GM');
        $this->load->model('Open_time_model','OM');
        $this->load->model('user/Bet_record_model','KJ');
        if (!is_cli()) {
            self::$wsAct = new WsAct($this->M->sn);
        }

    }

    /**
     * 获取 WS 地址
     */
    public function get_ws_url()
    {
        $dsn = self::$wsAct->getDsn();
        if ( empty($dsn) ) {
            $this->return_json(E_ARGS, 'sn 错误');
        };
        $dsn = json_decode($dsn, true);

        // 转入用户 token
        $user = $this->M->check_token($this->_token);
        if ( $user === TOKEN_BE_OUTED ) {
            $this->return_json(TOKEN_BE_OUTED,'被踢出');
        } elseif ( false === $user ) {
            $this->return_json(E_TOKEN, '请登录');
        }

        // VIP 等级
        $userRow = $this->M->get_one('vip_id', 'user', ['id' => $user['id']]);
        $user['vip_id'] = $userRow['vip_id'];

        $key = 'token:User:'. $this->_token;
        self::$wsAct->setex($key, 180, json_encode($user));

        $vipAccess = self::$wsAct->get('wshddt:vip_access');
        $vipAccess = json_decode($vipAccess, true);
        $slevel = 9;
        foreach ($vipAccess as $access) {
            if ( $access['is_speak'] == 1 ) {
                $slevel = $access['vip_id'];
                break;
            }
        }
        //获取当前大厅禁言设置 并给定默认值
        $is_all_silence = self::$wsAct->get('is_all_silence');
        $is_all_silence = ('0' == $is_all_silence) ? '0' : '1';

        $data = [
            'url' => $dsn['ws_server'] .'?sn='. $dsn['sn'],
            'uid' => $user['id'],
            'vip' => $user['vip_id'],
            'vip_speak_level' => $slevel,
            'is_all_silence' => $is_all_silence
        ];

        $this->return_json(OK, $data);
    }

    /**
     * @api {GET} /chat/index?gid=1 /chat/index?gid=1[&id=1|&time=1234567890]
     * @apiName 聊天记录
     * @apiGroup Chat
     * @apiVersion 1.0.0
     * @apiDescription 获取历史聊天记录
     * @apiPermission anyone
     * @apiSampleRequest http://gc360.com/index/test
     *
     * @apiHeader {string} AuthGC=gc0;xxx xxx:前一接口获取的token
     *
     * @apiParam {number} [id] any id
     * @apiParam {json} data object
     *
     * @apiParamExample {json} Request Example
     *   POST /api/test/hello/1
     *   {
     *     "data": {
     *       "firstname": "test",
     *       "lastname": "sails",
     *       "country": "cn"
     *     }
     *   }
     *
     * @apiSuccess (成功返回) {number} code 200
     * @apiSuccess (成功返回) {json} [data='""'] 如果有数据返回
     */
    public function index()
    {
        $time = $this->G('time');
        //$id  = (int) $this->G('id');

        //$gtTime = $this->G('gt_time'); // 客服中，只获取最新的几条

        // 用户间的历史消息
        $from = $this->G('from');
        $to   = $this->G('to');
        //$field = '*,img as headimg';
        $where = ['status' => 1];
        $condition = ['orderby' => ['time' => 'desc', 'id' => 'desc'], 'limit' => 20];

        $pm = false;
        if ( !empty($from) && !empty($to) ) {
            // 私聊
            $pm = true;
            $condition['wheresql'] = ["( (`from` = '$from' AND to = '$to') OR (`from` = '$to' AND to = '$from') )"];
        }

        // 查看了消息，清掉离线消息记录
        // 为后台客服时
        if ( isset($_GET['isser']) /*substr($from, 0, 2) == 's_'*/ ) {
            //$s = substr($from, 0, 2) == 's_' ? $from : (substr($to, 0, 2) == 's_' ? $to : '');

            if ( substr($from, 0, 2) == 's_' ) {
                self::$wsAct->srem('wshddt:offline_msg:'. $from, $to);
            }
        }

        /*
        if ( $id ) {
            $where['id < '] = $id;

            $data = $this->M->get_list($field, 'ws_record', $where, $condition);
            $data = array_reverse($data);

            $this->return_json(OK, $data);
        }
        */

        //$this->M->redis_select(9);
        $list = self::$wsAct->lrange('wshddt:record',0,-1);
        $len = count($list);
        $data = [];
        for( $len; $len > 0; $len-- ) {
            $_data = json_decode($list[$len-1], true);

            // 私聊的
            if ($pm && ($_data['from'] != $from || $_data['to'] != $to)
                && ($_data['from'] != $to || $_data['to'] != $from)
            ) {
                continue;
            }

            $_data['id'] = 0;

            if ($time) { // 按时间找
                if ($time > $_data['time']) {
                    $data[] = $_data;
                }
//            } elseif ($gtTime) {
//                if ($gtTime <= $_data['time']) $data[] = $_data;
            } else {
                $data[] = $_data;
            }
        }

        //if ( $gtTime ) { $where['time >= '] = $gtTime; }

        if ( $time ) { $where['time < '] = $time; }
    
        // 查询几条有id的
        $field = 'id,from,img as headimg,type,from_name,to,msg,time,vip';
        $_data = $this->M->get_list($field, 'ws_record', $where, $condition);
        $data = array_merge($data, $_data);
        $data = array_reverse($data);
        /*
         *  聊天信息中增加彩种图片和给用户头像默认值  
         *  lqh 2018/12/21 
         */
        if (!empty($data) && is_array($data))
        {
            foreach($data as $key => $val)
            {
                //判断消息类型为lottery 且没有end_time 或者 game_img参数
                if (('lottery' == $val['type']) && !empty($val['msg']) 
                    && ((false === stripos($val['msg'],'end_time')) 
                    || (false === stripos($val['msg'],'game_img'))))
                {
                    $msg = $this->get_share_msg($val['msg']);
                    $data[$key]['msg'] = $msg;
                }
            }
        }
        //返回data信息
        $this->return_json(OK, $data);
    }

    /**
     * 最新计划
     */
    public function get_last_plan(){
        $txt = self::$wsAct->get('last_plan');
        $txt = empty($txt) ? '请等待计划员发布最新的计划' : $txt;

        $this->return_json(OK, ['text' => $txt]);
    }

    /**
     * 公告
     */
    public function get_notice(){
        $txt = self::$wsAct->get('last_notice');
        $txt = empty($txt) ? '' : $txt;

        $this->return_json(OK, ['text' => $txt]);
    }

    /**
     * 在线会员
     */
    public function online_user(){
        $gid = $this->G('gid');
        $isNum   = $this->G('is_num');

        $query = $this->M->db->get_where('group', ['id' => $gid], 1);
        $group = $query->result_array();
        if ( empty( $group[0] ) ) {
            $this->return_json(E_ARGS);
        }

        $condition = ['join' => [['table' => 'robot as b', 'on' => 'a.robotId=b.id']]];
        $robotIds = $this->M->get_list('group_concat(robotId) as ids', 'group_robot', ['chatRoomId' => $gid, 'b.status' => 1], $condition);
        $robotIds = explode(',', $robotIds[0]['ids']);
        $robotIds = array_unique($robotIds);

        $ids = $this->M->redis_smembers('wshddt:online:'. $gid);

        $onlinCount = count($robotIds)+ count($ids) + $group[0]['add_num'];
        if ( $isNum ) $this->return_json(OK, ['count' => $onlinCount]);

        // 返回列表
        $userList = [];
        if ( !empty($ids) ) {
            $condition = [
                'join'  => [['table' => 'user_detail as b', 'on' => 'a.id=b.uid']],
                'wherein' => ['a.id' => $ids],
            ];
            $userList = $this->M->get_list('a.id, username, nickname, img as headimg, vip_id', 'user', [], $condition);
        }

        // 机器人的
        $robotList = [];
        if ( !empty( $robotIds ) ) {
            $robotList = $this->M->get_list('concat("r", id) as id, robotInfo as username, robotInfo as nickname, img as headimg, 1 as vip_id', 'robot', ['status' => 1], ['wherein' => ['id' => $robotIds]]);
        }

        // 游客的
        $guestList = [];
        foreach ($ids as $id) {
            if ( strstr($id, 'g_') ) {
                $guestName = '游客'. substr($id, 2);
                $_data = [
                    'id'    => $id,
                    'username' => $guestName,
                    'nickname' => $guestName,
                    'headimg'      => 'http://'. $_SERVER['HTTP_HOST'] .'/static/images/avatar/avatar12.png',

                    'vip_id'    => 0
                ];

                $guestList[] = $_data;
            }
        }

        $data = array_merge($userList, $robotList, $guestList);

        foreach ($data as &$val) {
            $val['vip_id'] = (int) $val['vip_id'];
        }

        $this->return_json(OK, ['list' => $data, 'count' => $onlinCount]);
    }

    private function wsRule($uid, $vip){
        $rule = self::$wsAct->get('wshddt:vip_access');
        $rule = json_decode($rule, true);
        if ( !$rule || empty($rule[$vip]) ) return false; // 没有对应的VIP，不允许操作
        $rule = $rule[$vip];

        // 发言限制
        if ( $rule['is_speak'] == 0 ) {
            return 'VIP发言限制';
        }

        // 每分钟限制
        $nu = self::$wsAct->hget('wshddt:send_num', $uid);
        $min= date('H:i');
        if ( $nu[0] == $min && $nu[1] >= $rule['record_num'] ) {
            // 超过发言次数了
            return 'VIP发言限制';
        }

        // 分享限制
        if ( $rule['is_share'] == 0 ) {
            return 'VIP分享限制';
        }

        // 大厅是否禁言
        if ( self::$wsAct->get('is_all_silence') == 1 ){
            return '互动大厅已暂停发言';
        }

        return true;
    }

    /**
     * 分享下注的
     * bets: [{"gid":"26","tid":"2907","price":2,"counts":1,"price_sum":2,"rate":"9.800","rebate":0,"pids":"25404","contents":"1","names":"01","atitle":"前一","btitle":"前一"}]
     */
    public function share_bets()
    {
        $user = $this->M->check_token($this->_token);
        if ( !$user ) {
            $this->return_json(E_TOKEN, '请登录');
        }

        // 判断
        $rs = $this->wsRule($user['id'], $user['vip_id']);
        if ( $rs !== true ) {
            $this->return_json(E_ARGS, $rs);
        }

        $bets = $this->P('bets');
        $bets = $this->set_share_msg($bets, $user);

        if ( !empty($user['nickname']) ) {
            $name = $user['nickname'];
        } else {
            $name = mb_substr($user['username'], 0, 1) . '***' . mb_substr($user['username'], -1);
        }

        //新增用户头像信息
        $headimg = !empty($user['img']) ? $user['img'] : '0';
        //构造ws发送投注信息
        $send_data = array(
            'type' => 'lottery',
            'msg' => $bets,
            'to' => 'all',
            'from' => $user['id'],
            'from_name' => $name,
            'headimg' => $headimg,
            'vip' => $user['vip_id']
        );
        $rs = self::$wsAct->sendWs($send_data);

        if ( !$rs ) $this->return_json(E_SYS_1, '分享失败');

        $this->return_json(OK);
    }

    /**
     * 分享今日战绩
     */
    public function share_standings(){
        $user = $this->M->check_token($this->_token);
        if ( !$user ) {
            $this->return_json(E_TOKEN, '请登录');
        }

        // 判断
        $rs = $this->wsRule($user['id'], $user['vip_id']);
        if ( $rs !== true ) {
            $this->return_json(E_ARGS, $rs);
        }

        $id = (int)$user['id'];

        $this->load->model('user/User_info_model', 'model');
        $data = $this->model->profit($id);
        $data = json_encode($data);

        if ( !empty($user['nickname']) ) {
            $name = $user['nickname'];
        } else {
            $name = mb_substr($user['username'], 0, 1) . '***' . mb_substr($user['username'], -1);
        }
        
        //新增用户头像信息
        $headimg = !empty($user['img']) ? $user['img'] : '0';
        //构造ws发送投注信息
        $send_data = array(
            'type' => 'standings',
            'msg' => $data,
            'to' => 'all',
            'from' => $user['id'],
            'from_name' => $name,
            'headimg' => $headimg,
            'vip' => $user['vip_id']
        );
        
        $rs = self::$wsAct->sendWs($send_data);

        if ( !$rs ) $this->return_json(E_SYS_1, '分享失败');

        $this->return_json(OK);
    }

    /**
     * 用户禁言
     */
    public function user_mute($gid, $uid, $type, $time) {
        $data = [
            'gid'   => $gid,
            'type'  => 'user_mute',
            'uid'   => $uid,
            'end_nospeak_time' => $time,
            'speak_status' => $type,
            'code'  => md5('_pwd_12345')
        ];

        return $this->sendWs($data);
    }

    /**
     * 文件上传
     */
    public function upload(){
        $token = $this->_token;
        if (empty($token) || !isset($token{10})) {
            $this->return_json(E_TOKEN, '参数出错token');
        }
        $res = $this->M->check_token($token);
        if(empty($res)){
            $this->return_json(E_TOKEN, '没有登陆');
        }

        $config['upload_path'] = 'uploads/'. date('Ym') .'/';
        $config['allowed_types'] = 'gif|jpg|png|jpeg';
        $config['max_size'] = '20480'; // 20M
        $config['max_width'] = '0';
        $config['max_height'] = '0';

        if ( !is_dir($config['upload_path']) ) {
            mkdir($config['upload_path']);
        }

        $this->config->set_item('language', 'cn');
        $this->load->library('upload', $config);
        $data = $this->upload->do_upload('file');

        if ( !$data ) {
            $this->return_json(E_DATA_INVALID, $this->upload->error_msg[0]);
        }

        $data = $this->upload->data();
        $data = [
            'url' => 'http://'. $_SERVER['HTTP_HOST'].'/'. $config['upload_path'] .$data['file_name'],
            'img' => ($data['is_image'] ? ['w' => $data['image_width'],'h' => $data['image_height'], 'type' => $data['image_type']] : []),
            'size'  => $data['file_size'],
            'ext'   => $data['file_ext'],
            'name'  => $data['file_name'],
            'raw_name' => $data['raw_name'],
            'file_type' => $data['file_type'],
        ];
        $this->return_json(OK, $data);
    }



    /// --- 客服管理 ---
    /**
     * 用户列表 - 咨询过的记录
     * 需要 最后一条记录时间
     */
    public function consulting_list(){
        $sid = $this->G('id', true); // 客服id
        $cids = $this->M->redis_hgetall('wshddt:consulting_list:'. $sid); // 所有咨询过的客户

        $data = [];
        foreach ($cids as $id => $name) {
            $arr = explode('|', $name);
            $offlineMsg = $this->M->redis_smembers('wshddt:offline_msg:'. $sid); // 离线消息

            $data[] = [
                'online'    => true,
                'client_id' => $id,
                'client_name' => $arr[0],
                'time'  => $arr[1], // 最后消息的时间

                'offline_msg'   => in_array($id, $offlineMsg) ? 1 : 0
                //'gt_time'   => $arr[2] // 发送第一次消息的时间，，
            ];
        }
        $this->return_json(OK, $data);
    }

    /*
     * 删除资讯用户
     */
    public function consulting_del(){
        $cid = $this->G('cid', true); // clident_id
        $sid = $this->G('id', true); // 客服id
        $rs  = $this->M->redis_hdel('wshddt:consulting_list:'. $sid, $cid); // 所有咨询过的客户

        //if ( $rs == 0 ) $this->return_json(E_ARGS);

        // 先把redis中记录复制到数据库中
        ob_start();
        $this->writeDb('');
        ob_end_clean();
        // 更新表
        $where = "(`from` = '$sid' AND to = '$cid') OR (`from` = '$cid' AND to = '$sid')";
        $this->M->write('ws_record', ['status' => 0], $where);

        $this->return_json(OK);
    }

    /*
     * 快速讯息
     */
    public function msg_list(){
        $data = $this->M->get_list('*', 'service_message');

        $this->return_json(OK, $data);
    }

    /*
     * 客服列表
     */
    public function service_list(){
        /*
        $ids = $this->M->redis_smembers('wshddt:online:service');
        if ( empty($ids) ) $this->return_json(OK, []);

        foreach ($ids as &$id) {
            $id = substr($id, 2);
        }
        $condistion = ['wherein' => ['adminId' => $ids]];
        */

        // 不判断在线，允许客户端发送离线消息
        $data = $this->M->get_list('concat("s_", adminId) as id, onlineServiceName as name, img as headimg',
            'onlineService', []);

        $this->return_json(OK, $data);
    }


    /*
     * 聊天室列表
     * 前台切换聊天室
     */
    public function group_list(){
        $data = $this->M->get_list('id, name, vip_id', 'group', ['status' => 1]);
        $this->return_json(OK, $data);
    }



    /*
     * 软件发送过来的消息
     */
    public function send_plan(){
        //$setArr = $arr = $this->M->get_gcset();
        $_code = self::$wsAct->get('plan_code');
        if ( !isset($_GET['code']) || empty($_code) || trim($_GET['code']) != $_code ){
            //wlog(APPPATH .'logs/log_open_msg.txt', 'KEY错误');
            exit('fail code err');
        }

        $fname = '计划员';
        //$fimg  = 'http://'. $_SERVER['HTTP_HOST'] .'/static/images/avatar/avatar12.png';
        //$fsn   = 'w01';
        $fkey  = empty($_GET['fkey']) ? 'text' : trim($_GET['fkey']);

        if ( !isset($_REQUEST[$fkey]) || empty($_REQUEST[$fkey]) ) {
            wlog(APPPATH .'logs/log_open_msg.txt', '内容错误');
            exit('fail 200');
        }
        $text = $_REQUEST[$fkey];

        $data = [
            'type'       => 'txt',
            'from'       => 0,
            'from_name'  => $fname,
            'headimg'    => '',
            'to'         => 'all',
            'msg'        => $text,
            'vip'        => 10
        ];

        $rs = self::$wsAct->sendWs($data);
        if (!$rs ) {
            wlog(APPPATH .'logs/log_open_msg.txt', var_export($data, true));
        }

        // 记录最后一条
        self::$wsAct->set('last_plan', 600, $text);

        echo 'SUCCEED';

        /*
        $fgid = [$fgid];
        if ( strpos($fgid, ',') ) {
            // 多个
            $fgid = explode(',', $fgid);
        }

        foreach ($fgid as $gid) {
            if ( $gid != 0 && empty($gid) ) continue;
            $data['gid'] = $gid;

            $rs = $this->sendWs($data);
            if (!$rs ) {
                wlog(APPPATH .'logs/log_open_msg.txt', var_export($data, true));
            }
        }
        */
    }

    public function user_list(){
        $name = $this->input->get('username', true);
        $name = $this->M->db->escape_like_str($name);

        $condition = [
            'join'  => [['table' => 'user_detail as b', 'on' => 'a.id=b.uid']],
            'wheresql' => ['username like "%'. $name .'%" OR nickname like "%'. $name .'%"']
        ];

        $data = $this->M->get_list('a.id, username, nickname, img as headimg, vip_id', 'user', [], $condition);
        $this->return_json(OK, $data);
    }


    public function robot_msg($dbn = 'lts'){
        if (!is_cli()) {
            header('HTTP/1.1 405 fuck u!');
            $this->return_json(E_METHOD, 'method nonsupport!');
        }

        $this->M->init($dbn);

        $groups = $this->M->get_list('id', 'group', ['status' => 1, 'mute' => 1]);
        foreach ($groups as $group) {
            $this->robotMsg($group['id']);
        }
    }

    /**
     * 机器人消息
     */
    private function robotMsg($gid){
        $nowTime = time();
        $robotNum = 5; // 每分钟多少个
        $repTime  = 1; // 重复时间间隔(s)

//        $query = $this->M->db->get_where('group', ['id' => $gid], 1);
//        $group = $query->result_array();
//        if ( empty( $group[0] ) ) {
//            return ;
//        }

        $robotIds = $this->M->get_list('group_concat(robotId) as ids', 'group_robot', ['chatRoomId' => $gid]);
        $robotIds = explode(',', $robotIds[0]['ids']);
        $robotIds = array_unique($robotIds);

        if ( empty($robotIds) ) return;


        // 词库
        $msg = [];
        $_msg = $this->M->get_list('id, robot_lexicon', 'robot_type');
        // 已发送的词
        $_recordMsg = $this->M->redis_smembers('wshddt:robot_msg:'. $gid);
        $recordMsg = [];
        foreach ($_recordMsg as $val) {
            $_val = explode('-', $val);
            if ( $nowTime - $_val[1] > $repTime ) {
                $this->M->redis_srem('wshddt:robot_msg:'. $gid, $val);
                continue;
            }

            $recordMsg[] = $_val[0];
        }
        foreach ( $_msg as $val ) {
            $m = explode("\r\n", $val['robot_lexicon']);
            $m = array_diff($m, $recordMsg);
            $tmp = array_rand($m, $robotNum);
            foreach ( $tmp as $i ) {
                $msg[$val['id']][] = $m[$i];
            }
        }

        // 已发的机器人
        $_recordIds = $this->M->redis_smembers('wshddt:robot_ids:'. $gid);
        $recordIds = [];
        foreach ($_recordIds as $val) {
            $_val = explode('-', $val);
            if ( $nowTime - $_val[1] > $repTime ) {
                $this->M->redis_srem('wshddt:robot_ids:'. $gid, $val);
                continue;
            }

            $recordIds[] = $_val[0];
        }

        $robotIds2 = array_diff($robotIds, $recordIds);
        //shuffle($robotIds);
        $tmp = array_rand($robotIds2, $robotNum);
        $robotIds = [];
        foreach ( $tmp as $i ) {
            $robotIds[] = $robotIds2[$i];
        }

        $robotList = $this->M->get_list('concat("r", id) as rid, id, iCategory, robotInfo as username, robotInfo as nickname, img',
            'robot', ['status' => 1], ['wherein' => ['id' => $robotIds]]);
        $second = (int) 60/$robotNum;
        foreach ( $robotList as $key => $robot ) {
            // 聊天室禁言了
            $groupInfo = $this->M->redis_get('wshddt:group:'. $gid);
            $groupInfo = json_decode($groupInfo, true);
            if ( isset($groupInfo['mute']) && $groupInfo['mute'] == 0 ) break;

            $data = [
                'type'       => 'txt',
                'from'       => $robot['rid'],
                'from_name'  => $robot['username'],
                'headimg'    => $robot['img'],
                'to'         => 'all',
                'msg'        => $msg[$robot['iCategory']][$key],
                'gid'        => $gid
            ];
            $rs = $this->sendWs($data);
            $this->M->redis_sadd('wshddt:robot_ids:'. $gid, $robot['id'] .'-'. $nowTime);
            $this->M->redis_sadd('wshddt:robot_msg:'. $gid, $msg[$robot['iCategory']][$key] .'-'. $nowTime);

            echo $robot['username'] .'->'. $msg[$robot['iCategory']][$key] ."\t{$rs} \n";

            sleep(mt_rand(1, $second));
        }
    }

    /**
     * 定时任务，每分钟将redis聊天记录转到数据库
     */
    public function writeDb($dbn = 'w01'){
        if (!is_cli()) {
            header('HTTP/1.1 405 fuck u!');
            $this->return_json(E_METHOD, 'method nonsupport!');
        }
        $this->M->init($dbn);
        self::$wsAct = new WsAct($this->M->sn);

        $roll_back_arr = []; // 回滚数据
        $data = []; // 入库数据
        while ( true ) {
            $record = self::$wsAct->lPop("wshddt:record");
            if ( empty($record) ) break;

            $_data = json_decode($record, true);
            if ( (empty($_data['from']) && $_data['from'] != 0) || empty($_data['type']) ) continue;

            $roll_back_arr[] = $record;

            $data[] = [
                'gid'   => !empty($_data['gid']) ? $_data['gid'] : 0,
                'type'  => $_data['type'],
                'from'  => $_data['from'],
                'from_name' => empty($_data['from_name']) ? '' : $_data['from_name'],
                'img'   => $_data['headimg'],
                'to'    => empty($_data['to']) ? 'all' : $_data['to'],
                'msg'   => is_array($_data['msg']) ? json_encode($_data['msg']) : $_data['msg'],
                'time'  => $_data['time'],
                'vip'   => $_data['vip']
            ];
            //$count++;
        }

        if ( empty($data) ) return;

        $num = $this->M->db->insert_batch('ws_record', $data);
        if ( !$num ) {
            // 还原redis数据
            $l = count($roll_back_arr);
            for ( $l; $l > 0; $l-- ) {
                self::$wsAct->lPush('wshddt:record', $roll_back_arr[$l-1]);
            }

            $logFile = APPPATH.'logs/'.$dbn.'_ws_record_err_'.date('Ymd').'.log';
            wlog($logFile, $this->M->db->last_query() ."\n", false);
            echo "write db fail!\n";
        }
    }

    /**
      *@desc 定时任务,将中奖信息推送到聊天服务器中 10秒中一次
      **/
    public function sendWinsMsg($dbn = 'w01')
    {
        if (!is_cli()) {
            header('HTTP/1.1 405 fuck u!');
            $this->return_json(E_METHOD, 'method nonsupport!');
        }
        $this->KJ->init($dbn);
        self::$wsAct = new WsAct($this->KJ->sn);
        //.获取这期间的大额中奖信息
        $winsData = $this->KJ->get_wins_list();
        if(empty($winsData)){
            echo '没有最新中奖数据';
            return;
        }
        $send_data = [];
        $send_data['type'] = 'winning';
        foreach ($winsData as $key => $row) {
            //.中奖信息
            $send_data['msg'][] = [
                'username'=>$row['username'],
                'gname'=>$row['game_name'],
                'pname'=>$row['gname'],
                'prize'=>$row['price_sum']
            ];
        }
        $send_data['msg']=json_encode($send_data['msg']);
        $rs = self::$wsAct->sendWs($send_data);
        if(!$rs){
            echo '向ws发送数据失败';
        }
        echo '发送数据成功';
    }
    

     /**
      *@desc 定时任务,将用户的命中率定时写进redis 1分钟 1次 暂时不用
      **/
     public function send_hit_rate_msg($dbn = 'w01')
     {
        if (!is_cli()) {
            header('HTTP/1.1 405 fuck u!');
            $this->return_json(E_METHOD, 'method nonsupport!');
        }
        $this->KJ->init($dbn);
        self::$wsAct = new WsAct($this->KJ->sn);
        //.获取用户的命中率数据
        $user_hit_data = $this->KJ->get_wins_rate_list();
        if(empty($user_hit_data)){
            return;
        }
        $hit_data = [];
        $hit_data['type'] = 'user_info';
        //.将命中率保存到wsredis中
        foreach ($user_hit_data as $k => $row) {
            //.先判断与redis中上次命中率是否相同 相同就不推送 不请求
            $user_rate_msg = json_decode(self::$wsAct->hget('hit_rate',$row['uid']),true);
            if($user_rate_msg['hit_rate'] !=$row['hit_rate']){
                self::$wsAct->hset("hit_rate",$row['uid'],json_encode($row));
                //.请求的数据
                $hit_data['data'][] = [
                    'uid' =>$row['uid'],
                    'hit_rate'=>$row['hit_rate'],
                    'counts'=>$row['counts']
                ];
            }
        }
        $rs = self::$wsAct->sendWs($hit_data);
        
     }

    /*
     *@php 给历史msg消息增加 彩种图片
     *@php 给分享投注msg增加 当前期期的截止日期
     *@     msg  前端传参 json格式的投注信息
     */
    private function get_share_msg($msg)
    {
        //转化msg信息格式
        $msg = json_decode($msg,true);
        //没有end_time参数 增加此参数
        /*if (empty($msg['end_time']))
        {
            $msg['end_time'] = '0';
        }*/
        //没有图片地址 增加图片地址
        if (empty($msg['bet'][0]['game_img']))
        {
            $msg['bet'] = $this->add_game_img($msg['bet']);
        }
        return json_encode($msg,320);
    }

    /*
     *@php 给分享投注msg增加 彩种图片
     *@php 给分享投注msg增加 当前期期的截止日期
     *@     msg  前端传参 json格式的投注信息
     */
    private function set_share_msg($msg, $user)
    {
        //转化msg信息格式
        $msg = json_decode($msg,true);
        //添加开奖信息
        /*if (!empty($msg['bet'][0]['gid']))
        {
            $gid = intval($msg['bet'][0]['gid']);
            $msg['end_time'] = $this->add_kithe_msg($gid,$msg);
        } else {
            $msg['end_time'] = '0';
        }*/
        //添加彩种图片
        if (!empty($msg['bet']))
        {
            $msg['bet'] = $this->add_game_img($msg['bet']);
        } else {
            $msg['bet'] = '0';
        }

        // 命中率
        $rate = $this->KJ->get_wins_rate_list($user['id']);
        $msg['hit_rate'] = '';
        if ( $rate['bet_counts'] > 10 && (float) $rate['hit_rate'] > 40 ) {
            $msg['hit_rate'] = $rate['hit_rate'];
        }

        return json_encode($msg,320);
    }

    /**
     * 根据gid给投注消息增加 封盘时间参数
     * @param [type] $gid [description]
     */
    private function add_kithe_msg($gid)
    {
        $kithe = $this->OM->get_kithe($gid);
        if (!empty($kithe['kithe_time_stamp']))
        {
           return $kithe['kithe_time_stamp'];
        }
        return false;
    }

    /**
     * 根据gid给投注消息增加 封盘时间参数
     * @param [type] $gid [description]
     */
    private function add_game_img($bets)
    {
        $games = $this->get_game_imgs();
        foreach($bets as $key => $val)
        {
            if (array_key_exists($val['gid'],$games))
            {
                $bets[$key]['game_img'] = $games[$val['gid']];
            } else {
                $bets[$key]['game_img'] = '0';
            }
        }
        return $bets;
    }   

    /**
     * @php 获取公库中全部彩种图片地址
     */
    private function get_game_imgs()
    {
        $data = [];
        $games = $this->GM->getlist();
        if (!empty($games))
        {
            foreach($games as $key => $val)
            {
                $data[$val['id']] = $val['img'];
            }
        }
        return $data;
    }
}
