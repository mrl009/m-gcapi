<?php
/**
 * 活动大厅 (聊天记录) 接口
 * User: lqh6249
 * Date: 1970/01/01
 * Time: 00:01
 */
defined('BASEPATH') or exit('No direct script access allowed');

class Chat extends MY_Controller
{
    static $wsAct = '';  
    public function __construct()
    {
        parent::__construct();
        $this->load->helper('interactive');
        $this->load->model('level/Member_model','MM');
        $this->load->model('interactive/Chat_model','CM');
        self::$wsAct = new WsAct($this->CM->sn);
    }

    //获取聊天记录数据
    public function get_record_list()
    {
        //初始化查询数据条件
        $where = [];
        $where2 =[];
        //获取参数并对参数进行默认设置
        $page = input('param.page',1,'intval');
        $rows = input('param.rows',50,'intval');
        $sort = input('param.sort','id','trim');
        $order = input('param.order','desc','trim');
        $start = input('param.start','','trim');
        $end = input('param.end','','trim');
        $name = input('param.tj_txt','','trim');
        //设置分页排序等参数
        $parms = array(
           'page' => $page,
           'rows' => $rows,
           'sort' => $sort,
           'order' => $order
        );
        if (!empty($start))
        {
            $st = strtotime($start.' 00:00:00');
            //如果开始时间比当日时间大 则以当日时间为准
            if ($st > time()) $st = time();
            $where['a.time >='] = $st;
        }
        if (!empty($end))
        {
            $et = strtotime($end.' 23:59:59');
            //如果截止时间比当日时间大 则以当日时间为准
            if ($et > time()) $et = time();
            $where['a.time <='] = $et;
        }
        //若查询时间范围是开始时间比截止时间大 则取开始日至当日时间范围
        if ((isset($st) && isset($et)) && ($st > $et))
        {
            $where['a.time >='] = $st;
            $where['a.time <='] = time();
        }
        if (!empty($name))
        {
            if (!empty($where))
            {
                $tw = "(b.`username` = '{$name}' OR a.`from_name` = '{$name}' OR a.`to` = '{$name}')";
                $where2['wheresql'] = ["{$tw}"];
            } else {
                $where['b.username'] = $name;
                $where2['orwhere'] = array('a.to' => $name,'a.from_name' => $name);
            }
        }
        $where2['join'] = array(
            array('table' => 'gc_user as b', 'on' => 'a.from = b.id',)
        );
        //根据条件获取数据
        $field = "a.*,b.username";
        $data = $this->CM->get_list($field,'ws_record',$where,$where2,$parms);
        if (!empty($data['rows']))
        {
            $st = 'time';
            $rows  = $data['rows'];
            foreach($rows as $key => $val)
            {
                $rows[$key][$st] = date('Y-m-d H:i:s',$val[$st]);
                if (0 == $val['from'])
                {
                    $rows[$key]['username'] = '计划员';
                }
            }
            $data['rows'] = $rows;
        }
        $this->return_json(OK, $data);
    }

    //删除聊天记录
    public function record_delete()
    {
        //获取参数并对参数进行默认设置
        $id = input('param.id');
        if (empty($id)) $this->return_json(E_ARGS,'Parameter is null');
        $id = explode(',', $id);
        //获取即将删除的数据 
        $where['wherein'] = ['id' => $id];
        $data = $this->CM->get_list('msg,time', 'ws_record', [], $where);
        //执行删除数据库操作
        $result = $this->CM->delete('ws_record', $id);
        if ($result) 
        {
            //删除数据库之前 将删除记录通知到用户端
            $sedn_data = array(
                'type' => 'ws_record_del',
                'data' => $data
            );
            self::$wsAct->sendWs($sedn_data); 
            unset($where,$data,$result,$sedn_data); 
            //返回执行结果
            $this->return_json(OK,'执行成功');
        } else {
            $this->return_json(E_ARGS,'执行失败');
        }
    }

    //获取最最新计划(最新一条数据)
    public function get_msg_send()
    {
        //从redis获取最新公告
        $key = 'last_notice';
        $data = self::$wsAct->get($key);
        $this->return_json(OK, $data);
    }

    //保存发送的聊天室消息
    public function msg_send_save()
    {
        //富文本编辑框消息包含HTML标签不使用过滤
        $msg = $_REQUEST['msg'];
        //获取参数数据
        $type = input('param.type','','trim');
        $f_type = input('param.form_type',0,'intval');
        //设置发送信息的默认值
        $from_name = (2 == $f_type) ? '计划员' : '房管'; //管理員類別
        $send_type = ('img' == $type) ? 'img' : 'txt'; //消息類型
        if (empty($msg)) $this->return_json(E_ARGS,'Parameter is null');
        //发送消息 推送至前端客户端
        $send_data = array(
            'type' => $send_type,
            'from' => 0,
            'to' => 'all',
            'msg' => $msg,// 发送的信息
            'headimg' =>'',
            'from_name' => $from_name,
            'vip'   => 10
        );
        //计划任务消息添加进redis 
        if ('plan' == $type)
        {
            self::$wsAct->set('last_plan', $msg, 600);
        }
        //大廳公告 消息添加进redis
        if ('notice' == $type)
        {
            self::$wsAct->set('last_notice', $msg);
        }
        $result = self::$wsAct->sendWs($send_data);
        if ($result) {
            $this->return_json(OK, '发送成功');
        } else {
            $this->return_json(E_ARGS, '发送失败');//返回错误信息
        }
    }

    // 获取消息过滤内容
    public function get_msg_filter()
    {
        //从redis获取消息过滤内容
        $key = 'wshddt:msg_filter';
        $data = self::$wsAct->get($key);
        $this->return_json(OK, $data);
    }

    //消息过滤 保存操作
    public function msg_filter_save()
    {
        $content = input('param.data_filter');
        if (empty($content)) 
        {
            $this->return_json(E_ARGS,'Parameter is null');
        }
        //消息过滤内容保存在redis中
        $key = 'wshddt:msg_filter';
        self::$wsAct->set($key, $content);
        //发送客服端消息通知
        $send_data = array(
            'type' => 'msg_filter',
            'data' => $content
        );
        $result = self::$wsAct->sendWs($send_data);
        if ($result) {
            $this->return_json(OK, '保存成功');
        } else {
            $this->return_json(E_ARGS, '保存失败');//返回错误信息
        }
    }

    //获取聊天记录数据
    public function get_silence_list()
    {
        //初始化查询数据条件
        $where = [];
        $where2 =[];
        //获取参数并对参数进行默认设置
        $page = input('param.page',1,'intval');
        $rows = input('param.rows',50,'intval');
        $sort = input('param.sort','id','trim');
        $order = input('param.order','desc','trim');
        $start = input('param.start','','trim');
        $end = input('param.end','','trim');
        $name = input('param.tj_txt','','trim');
        $status = input('param.status','','intval');
        //设置分页排序等参数
        $parms = array(
           'page' => $page,
           'rows' => $rows,
           'sort' => $sort,
           'order' => $order
        );
        //构造查询条件
        if (!empty($name)) $where['a.username'] = $name;
        if (!empty($status) && (1 <> $status)) 
        {
            $where['c.silence_type'] = $status;
        }
        if (!empty($start))
        {
            $st = strtotime($start.' 00:00:00');
            //如果开始时间比当日时间大 则以当日时间为准
            if ($st > time()) $st = time();
            $where['c.start_silence_time >='] = $st;
        }
        if (!empty($end))
        {
            $et = strtotime($end.' 23:59:59');
            //如果截止时间比当日时间大 则以当日时间为准
            if ($et > time()) $et = time();
            $where['c.start_silence_time <='] = $et;
        }
        //若查询时间范围是开始时间比截止时间大 则取开始日至当日时间范围
        if ((isset($st) && isset($et)) && ($st > $et))
        {
            $where['c.start_silence_time >='] = $st;
            $where['c.start_silence_time <='] = time();
        }
        $where2['join'] = array(
            array('table' => 'user_detail as b', 'on' => 'a.id = b.uid',),
            array('table' => 'ws_silence as c', 'on' => 'a.id = c.user_id',)
        );
        //构造查询字段
        $af = 'a.id,a.username,a.vip_id';
        $bf = 'b.nickname';
        $cf = 'c.silence_type,c.silence_name,c.start_silence_time';
        $cf .= ',c.end_silence_time';
        $field = "{$af},{$bf},{$cf}";
        //根据条件获取数据
        $data = $this->CM->get_list($field,'user',$where,$where2,$parms);
        if (!empty($data['rows']))
        {
            $st = 'start_silence_time';
            $et = 'end_silence_time';
            $rows = $data['rows'];
            foreach($rows as $key => $val)
            {
                //获取用户在线状态
                $online = $this->MM->check_online($val['id']);
                $rows[$key][$st] = date('Y-m-d H:i:s',$val[$st]);
                $rows[$key][$et] = date('Y-m-d H:i:s',$val[$et]);
                $rows[$key]['online'] = (int)$online;
            }
            $data['rows'] = $rows;
        }
        $this->return_json(OK, $data);
    }

    //获取用户禁言设置信息
    public function get_silence_info()
    {
        $id = input("param.id",'','intval'); 
        if (empty($id)) $this->return_json(E_ARGS,'Parameter is null');
        //构造条件查询语句
        $tb1 = "gc_user AS a";
        $tb2 = "gc_ws_silence AS b";
        $where = "a.id = {$id}";
        $onwhere = "a.id = b.user_id";
        $field = 'a.id,a.username,b.silence_type,b.silence_name';
        $field .= ',b.start_silence_time,b.end_silence_time';
        $sql = "SELECT {$field} FROM {$tb1} LEFT JOIN {$tb2} ";
        $sql .= "ON {$onwhere} WHERE {$where} LIMIT 1";
        //获取数据
        $info = $this->CM->db->query($sql)->row_array();
        $this->return_json(OK,$info);
    }

    //保存用户禁言设置
    public function silence_save()
    {
        $id = input('param.id','','intval');
        $status = input('param.status','','intval');
        if (empty($id) || empty($status))
        {
            $this->return_json(E_ARGS,'Parameter is null');
        }
        $data = $this->set_silence($status);
        $data['user_id'] = $id;
        //查询数据表是否有数据，如果没有数据则执行插入操作
        $where['user_id'] = $id;
        $info = $this->CM->get_one('*','ws_silence',$where);
        if (!empty($info))
        {
            //执行更新数据操作
            $result = $this->CM->write('ws_silence',$data,$where);
        } else {
            //执行插入数据操作
            $result = $this->CM->write('ws_silence',$data);
        }
        if ($result) 
        {
            //保存用户禁言设置到redis
            $key = 'wshddt:mute_time';
            $est = $data['end_silence_time'];
            self::$wsAct->hset($key,$id,$est);
            //将用户禁言设置通知到用户端
            $sedn_data = array(
                'uid' => $id,
                'type' => 'user_mute',
                'speak_status' => $data['silence_type'],
                'mute_time' => $data['end_silence_time']
            );
            self::$wsAct->sendWs($sedn_data);
            unset($where,$data,$result,$key,$est,$sedn_data);
            //返回执行结果
            $this->return_json(OK,'执行成功');
        } else {
            $this->return_json(E_ARGS,'没有数据被修改');
        }
    }

    /*
    *@ 刷新用户禁言状态
    * 当设置用户过期时间
     */
    public function silence_refresh()
    {
        $where['end_silence_time <'] = time();
        $data = $this->set_silence(1);
        $result = $this->CM->write('ws_silence',$data,$where);
        $this->return_json(OK,'执行成功');
    }

    //设置禁言参数
    private function set_silence($status)
    {
        $data = [];
        $sn = array(
            '2' => '禁言24小时',
            '3' => '禁言30天',
            '4' => '永久禁言'
        );
        $nt = array(
            '2' => strtotime("+1 day"),
            '3' => strtotime("+1 month"),
            '4' => 9999999999
        );
        if (!empty($status) && array_key_exists($status,$sn) 
            && array_key_exists($status,$nt))
        {
            $data['silence_type'] = $status;
            $data['silence_name'] = $sn[$status];
            $data['start_silence_time'] = time();
            $data['end_silence_time'] = $nt[$status];
        } else {
            $data['silence_type'] = 1;
            $data['silence_name'] = '解禁';
            $data['start_silence_time'] = '';
            $data['end_silence_time'] = '';
        }
        return $data;
    }
}
