<?php
/**
 * 会员管理
 * 会员统计
 * Created by PhpStorm.
 * User: shenshilin
 * Date: 2017/3/27
 * Time: 10:39
 *
 */
defined('BASEPATH') or exit('No direct script access allowed');

class Member extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('level/Member_model');
    }
    /**
     * 会员管理首页数据展示
    */
    public function index()
    {
        $this->user_index();
    }

    /**
     * 获取代理的列表
    */
    public function agent()
    {
        $this->user_index(2);
    }
    public function agent_name()
    {
        $page   = [
            'page'  => $this->G('page'),
            'rows'  => $this->G('rows'),
            'order' => $this->G('order'),
            'sort'  => $this->G('sort'),
            'total' => -1,

        ];
        $where = ['type'=>2];
        if (!empty($this->G('agent_name'))) {
            $where['username'] = $this->G('agent_name');
        }
        $data = $this->Member_model->get_list('username agent_name,id', 'user', $where, [], $page);
        $this->return_json(OK, $data);
    }
    /**
     * 获取会员 代理 列表
    */
    private function user_index($type=null)
    {
        $uid      = $this->G('id');
        $is_level = $this->G('is_level')  ;//层级
        $stat     = $this->G('status')    ;//状态
        $from_way = $this->G('from_way')  ; //来源
        $start    = $this->G('start') ;//注册时间
        $end      = $this->G('end')  ;//注册时间
        $tj       = $this->G('tj')      or $tj = 1 ;//条件
        $tj_txt   = trim($this->G('tj_txt'))    ;//条件内容
        $agent_name = $this->G('agent_name');
        $agent    = $agent_name?$this->G('agent_id') :''   ;//代理id

        if (!empty($start)) {
            if (empty($end)) {
                $end = date("Y-m-d");
            }
            $start = strtotime($start.' 00:00:00');
            $end   = strtotime($end.' 23:59:59');
        }

        $data =[
            'is_level'  => $is_level,
            'status'    => $stat,
            'from_way'  => $from_way,
            'tj'        => $tj   ,
            'tj_txt'    => $tj_txt,
        ];

        $bool = $this->check_data($data);
        if ($bool) {
            $where  = [
                'a.level_id'   => $is_level,
                'a.from_way'   => $from_way,
                'a.addtime >=' => $start,
                'a.addtime <=' => $end,
            ];
            $where2['join'] = [
                [ 'table' => 'user_detail b', 'on'    => 'a.id=b.uid',],
                [ 'table' => 'level c', 'on'    => 'a.level_id=c.id',],
                [ 'table' => 'grade_mechanism d', 'on'    => 'a.vip_id=d.id',],
                [ 'table' => 'agent_line l', 'on' => 'a.id=l.uid',],
            ];

            if ($stat == 3) {
                $in_online = $this->Member_model->check_online();
                if (empty($in_online)) {
                    $this->return_json(OK,[ 'total' => '' , 'rows' =>[] ]);
                }
                $where2['wherein'] = [
                    'a.id' => $in_online
                ];
            }else{
                $where['a.status'] = $stat;
            }

            if ($type) {
                $where['a.type'] =$type;
            }
            if ($uid) {
                $where['a.id'] = $uid;
            }
            if (empty($type) && !empty($agent)) {
                $where['agent_id'] =  $agent;
            }

            if ($tj_txt) {
                $key = $this->stort_user($tj);
                if ($tj==2 || $tj == 3) {
                    $where[$key] = $tj_txt;
                } elseif ($tj == 1) {
                    $tj_txt = str_replace('，', ',', $tj_txt);
                    $tj_txt = str_replace(' ', '', $tj_txt);
                    $tj_txt = explode(',', $tj_txt);
                    $where2['wherein'] = [$key => $tj_txt];
                    //$where = [];
                } elseif ($tj==7 || $tj==8) {
                    $tj_txt = preg_replace('/[^\d]/is','',$tj_txt);
                    $where[$key] = $tj_txt;
                } else {
                    $where[$key] = $tj_txt;
                }
            } else {
                $diff_time = (int)$end - (int)$start;
                if ((int)$diff_time > ADMIN_QUERY_TIME_SPAN) {
                    $this->return_json(E_ARGS,'查询时间不能跨度两个月');
                }
            }

            foreach ($where as $k=>$v) {
                if (empty($v)) {
                    unset($where[$k]);
                }
            }

            $page   = [
                'page'  => $this->G('page'),
                'rows'  => $this->G('rows'),
                'order' => $this->G('order'),
                'sort'  => $this->G('sort'),
                'total' => -1,

            ];

            $typename = [
                '1'=>'玩家',
                '2'=>'代理',
            ];

            $str = 'a.id,a.agent_id,a.off_sum,a.out_num , c.level_name,b.bank_name,a.username,a.level_id,a.from_way,a.balance,a.addtime,a.status,d.id as dengji,d.title,l.invite_code as code,a.type,l.level,l.rebate,l.ban';
            $arr = $this->Member_model->get_list($str, 'user', $where, $where2, $page);

            foreach ($arr['rows'] as $k=>$v) {
                $arr['rows'][$k]['addtime'] = date('Y-m-d H:i:s', $v['addtime']);
                $arr['rows'][$k]['online']  = (int)$this->Member_model->check_online($v['id']);
                if (empty($arr['rows'][$k]['level']) && $arr['rows'][$k]['type']==1) {
                    $arr['rows'][$k]['type']= '普通玩家';
                } elseif (empty($arr['rows'][$k]['level']) && $arr['rows'][$k]['type']==2) {
                    $arr['rows'][$k]['type']= '默认代理';
                } else {
                    $arr['rows'][$k]['type']= $arr['rows'][$k]['level'].'级'.$typename[$arr['rows'][$k]['type']];
                }
            }
            if (empty($type)) {
                foreach ($arr['rows'] as $k=> $v) {
                    $agent_id = $v['agent_id'];
                    $user = $this->Member_model->user_cache($agent_id);
                    !empty($user['username'])?  $arr['rows'][$k]['agent_name'] = $user['username']:$arr['rows'][$k]['agent_name']='';

                }
            }
            $this->return_json(OK, $arr);
        }
    }
    /**
     * 统计在线会员  今日注册数量 中注册数量
    */
    public function user_count_all()
    {
        $temp = $this->Member_model->count_user();
        $this->return_json(OK, $temp);
    }

    public function user_out()
    {
        $id = $this->P('id');
        $username = $this->P('username');
        if (empty($id)) {
            $this->return_json(E_ARGS, '错误的id号');
        }
        $this->load->model('Login_model');
        $this->Login_model->user_be_out($id);
        // 记录操作日志
        $this->load->model('log/Log_model');
        $this->Log_model->record($this->admin['id'], array('content' => "会员{$username}被踢线"));
        $this->return_json(OK, "踢线成功");
    }
    /**
     * 停用\启用 用户 已添加日志记录
    */
    public function chang_status()
    {
        $id = $this->P('id');
        $username = $this->P('username');
        if ($id <= 0) {
            $this->return_json(E_ARGS, 'id 错误');
        }
        $status = $this->P('status');
        if (!in_array($status, [1,2])) {
            $this->return_json(E_ARGS, 'status 错误');
        }
        if ($status == 2) {
            $this->load->model('Login_model');
            $this->Login_model->user_be_out($id);
        }
        ($status ==1)?$a="启用":$a="停用";
        $bool  = $this->Member_model->db->update('user', ['status'=>$status], ['id'=>$id]);
        $this->load->model('log/Log_model');
        $logData['content'] = "{$a}会员{$username}";//内容自己对应好
        $this->Log_model->record($this->admin['id'], $logData);

        if ($bool) {
            $this->return_json(OK, '更改成功');
        } else {
            $this->return_json(E_OK, '更新失败');
        }
    }

    /**
     * 恢复\禁止 用户反水 已添加日志记录
     */
    public function chang_rebate_status()
    {
        $id = $this->P('id');
        $username = $this->P('username');
        if ($id <= 0) {
            $this->return_json(E_ARGS, 'id 错误');
        }
        $ban = (int)$this->P('ban');
        if (!in_array($ban, [0,1])) {
            $this->return_json(E_ARGS, 'status 错误');
        }
        ($ban == 1)?$a="禁止":$a="恢复";
        $bool  = $this->Member_model->db->update('agent_line', ['ban'=>$ban], ['uid'=>$id,'type'=>2]);
        $this->load->model('log/Log_model');
        $logData['content'] = "{$a}代理{$username}反水,结果：" . ($bool ? '成功':'失败');//内容自己对应好
        $this->Log_model->record($this->admin['id'], $logData);

        if ($bool) {
            $this->return_json(OK, '更改成功');
        } else {
            $this->return_json(E_OK, '更新失败');
        }
    }

    /**
     * 修改用户资料的信息展示
    */
    public function update_show($uid =null)
    {
        if ($uid <= 0) {
            $this->return_json(E_ARGS, 'id 错误');
        }
        $detail = $this->G('detail');
        $where = ['a.id'=>$uid];
        /*$where2 = [
            'join'=>'user_detail',
            'on'=>'a.id=b.uid',
        ];*/
        $where2['join'] = [
            [ 'table' => 'user_detail b', 'on'    => 'a.id=b.uid',],
            [ 'table' => 'grade_mechanism c', 'on'    => 'a.vip_id=c.id',],
        ];
        $str = 'a.username,from_way,a.level_id,b.phone,a.max_game_price';
        if ($detail) {
            $str='a.username,a.out_num,a.level_id,a.update_time logintime,a.loginip,a.addtime,a.is_level_lock,b.*,a.max_game_price,c.id as dengji,c.title';
        }
        $arr = $this->Member_model->get_list($str, 'user', $where, $where2);
        $arr = $arr[0];
        if (!empty($arr['bank_pwd'])) {
            $arr['bank_pwd']   = '******';
        }
        if ($detail) {
            $arr['addtime']   = date('Y-m-d H:i:s', $arr['addtime']);
            $arr['logintime'] = date('Y-m-d H:i:s', $arr['logintime']);
            if (filter_var($arr['loginip'], FILTER_VALIDATE_IP) == false) {
                $arr['loginip']   = $arr['loginip'];
            }
            if (filter_var($arr['addip'], FILTER_VALIDATE_IP) == false) {
                $arr['addip']   = $arr['addip'];
            }


        }
        $arr['phone'] == 0?$arr['phone'] = null:false;
        //.将性别转换
        isset($arr['sex'])?($arr['sex']==1?$arr['sex']='男':$arr['sex']='女'):'';
        unset($arr['uid']);
        $this->return_json(OK, $arr);
    }

    /**
     * 更新用户的信息  已添加日志记录
    */
    public function update()
    {
        $id = $this->P('id');
        if ($id <= 0) {
            $this->return_json(E_ARGS, 'id 错误');
        }
        $data = [
            'birthday'  => trim($this->P('birthday')),//生日
            'address'   => trim($this->P('address')),//地区
            'idcard'    => trim($this->P('idcard')),//身份证号码
            'phone'     => trim($this->P('phone')),//手机号
            'qq'        => trim($this->P('qq')),//qq
            'email'     => trim($this->P('email')),//qq
            'bank_id'   => trim($this->P('bank_id')),//银行id
            'bank_num'  => trim($this->P('bank_num')),//银行卡号
            'bank_pwd'  => trim($this->P('bank_pwd')),//取款密码
            'remark'    => trim($this->P('remark')),//备注
            'max_game_price'   => (string)$this->P('max_game_price'),//每期游戏最大限额
        ];
        if ($this->admin['id'] == 1) {
            $data['wechat'] = trim($this->P('wechat'));//微信
            $data['wechat_qrcode'] = trim($this->P('wechat_qrcode'));//微信二维码
            $data['alipay'] = trim($this->P('alipay'));//支付宝
            $data['alipay_qrcode'] = trim($this->P('alipay_qrcode'));//支付宝二维码
        }
        if (isset($data['birthday'])) {
            $data['birthday'] = $data['birthday'].' 12:00:00';
        }
        if (isset($_POST['bank_pwd'])) {
            if (!empty($this->P('bank_pwd'))) {
                $data['bank_pwd'] = bank_pwd_md5($this->P('bank_pwd'));
            } else {
                $data['bank_pwd'] = '';
            }
        }else{
            unset($data['bank_pwd']);
        }

        if (!is_numeric($data['max_game_price']) || ($data['max_game_price'] != (int)$data['max_game_price'])) {
            $this->return_json(E_ARGS, "每期游戏最大限额必须为整形");
        }

        $data['bank_name'] = (string)$this->P('bank_name');
        $level_id  = (int)$this->P('level_id');
        $pwd       =  $this->P('pwd');
        $other     = [
            'level_id' => $level_id,
            'out_num'  => $this->P('out_num')
        ];
        //8.12 密码相关更改
       /* if (!empty($pwd)) {

            if (preg_match("/.+[\x{4e00}-\x{9fa5}\s]+.+/u", $pwd)== 1) {
                $this->return_json(E_ARGS,'登录密码不能有中文和空格');
            }
            if (preg_match('/^.*(?![0-9]+$)(?![a-zA-Z]+$)[A-Za-z0-9]+.*$/u', (string)$pwd)== 0) {
                $this->return_json(E_ARGS,'登录密码必须要有字母和数字');
            }
        }*/
        $gcSet  = $this->Member_model->get_gcset();
        if ($gcSet['is_unique_bank'] == 1) {
            $x = $this->Member_model->get_all('id', 'user_detail', ['bank_num'=>$data['bank_num'],'uid !=' => $id]);
            if (!empty($x) && !empty($data['bank_num'])) {
                $this->return_json(E_ARGS, '银行卡号重复');
            }
        }
        $this->load->helper('common_helper');

        if (!empty($pwd)) {
            $other['pwd'] = user_md5($pwd);
        }

        if ($this->check_user($data, $gcSet['bank_num_check'])) {
            $data['birthday'] = strtotime($data['birthday']);

            $user_d1 = $this->Member_model->get_one('*', 'user', ['id'=>$id]);
            $user_d2 = $this->Member_model->get_one('*', 'user_detail', ['uid'=>$id]);
            $user_d3 = array_merge($user_d1, $user_d2);
            $user_d4 = array_merge($data, $other);
            $flag = true;
            $user_d4['birthday'] = date('Y-m-d', $user_d4['birthday']);
            $user_d3['birthday'] = date('Y-m-d', $user_d3['birthday']);
            foreach ($user_d4 as $key => $value) {
                if (array_key_exists($key, $user_d3) && 
                    $user_d3[$key] != $user_d4[$key]) {
                    $flag = false; break;
                }
            }
            if ($flag) {
                $this->return_json(E_ARGS, '沒有修改');
            }
            $a = $this->Member_model->user_update($data, $id, $other);
            $a?$x="成功":$x="失败";
            $this->load->model('log/Log_model');
            $logData['content'] = "修改了用户信息 {$this->Member_model->username}:  状态$x 更新数据:".json_encode($data);//内容自己对应好
            $this->load->model('Login_model');
            $this->Login_model->update_token($id);//更新会员token信息
            $this->Log_model->record($this->admin['id'], $logData);
            if ($a) {
                $this->return_json(OK, '成功');//返回错误信息
            } else {
                $this->return_json(E_ARGS, '失败');//返回错误信息
            }
        }
    }

    public function update_agent()
    {
        $id = $this->P('id');
        if ($id <= 0) {
            $this->return_json(E_ARGS, 'id 错误');
        }
        $data = [
            'birthday'  => trim($this->P('birthday')),//生日
            'address'   => trim($this->P('address')),//地区
            'idcard'    => trim($this->P('idcard')),//身份证号码
            'bank_id'   => trim($this->P('bank_id')),//银行id
            'bank_num'  => trim($this->P('bank_num')),//银行卡号
            'bank_pwd'  => trim(trim($this->P('bank_pwd')),'.'),//取款密码
            'remark'    => trim($this->P('remark')),//备注
        ];
        if (!empty($this->P('bank_pwd'))) {
            $data['bank_pwd'] = bank_pwd_md5($this->P('bank_pwd'));
        }
        if ($this->admin['id'] == 1) {
            $data['bank_name'] = $this->P('bank_name');
        }
        $level_id  = (int)$this->P('level_id');
        $pwd       =  $this->P('pwd');
        $other     = ['level_id' => $level_id];
        if (isset($_POST['out_num'])) {
            $other['out_num'] = (int)$this->P('out_num');
        }
        /*if (!empty($pwd)) {
            if (strlen($pwd) <USER_PWD_MIN_LENGTH  || strlen($pwd) > USER_PWD_MAX_LENGTH) {
                $this->return_json(E_ARGS, "密码长度为".USER_PWD_MIN_LENGTH.'-'.USER_PWD_MAX_LENGTH);
            }
        }*/
        $gcSet  = $this->Member_model->get_gcset();
        if ($gcSet['is_unique_bank'] == 1) {
            $x = $this->Member_model->get_all('id', 'user_detail', ['bank_num'=>$data['bank_num'],'uid !=' => $id]);
            if (!empty($x) && !empty($data['bank_num'])) {
                $this->return_json(E_ARGS, '银行卡号重复');
            }
        }
        //判断 取款密码
       /* if (strlen($data['bank_pwd']) != 6 && !empty($data['bank_pwd'])) {
            $this->return_json(E_ARGS, "取款密码长度为6位数字");
        }*/

        $this->load->helper('common_helper');

        if (!empty($pwd)) {
            $other['pwd'] = user_md5($pwd);
        }

        if ($this->check_user($data, $gcSet['bank_num_check'])) {
            $data['birthday'] = strtotime($data['birthday']);
            $a = $this->Member_model->user_update($data, $id, $other);
            $a?$x="成功":$x="失败";
            $this->load->model('log/Log_model');
            $logData['content'] = "修改了用户信息 uid :$id:  状态$x";//内容自己对应好
            $this->Log_model->record($this->admin['id'], $logData);
            if ($a) {
                $this->return_json(OK, '成功');//返回错误信息
            } else {
                $this->return_json(OK, '失败');//返回错误信息
            }
        }
    }

    /**
     * 修改用户密码  已添加日志记录
    */

    public function chang_pwd()
    {
        $uid = $this->P('id');
        if ($uid <= 0) {
            $this->return_json(E_ARGS, 'id 错误');
        }
        $data = [
            'pwd'           => $this->P('pwd'),//密码
            'is_change_pwd' => $this->P('is_change_pwd'),//是否开启改密
        ];

        $data['pwd'] = user_md5($data['pwd']);
        if ($this->check_user($data)) {
            $a = $this->Member_model->write('user', $data, ['id'=>$uid]);

            $a?$x="成功":$x="失败";
            $this->load->model('log/Log_model');
            $logData['content'] = "修改了用户密码 uid :$uid:  状态$x";//内容自己对应好
            $this->Log_model->record($this->admin['id'], $logData);
            if ($a) {
                $a =[];
                $a['status'] = OK;
                $a['msg']    = "操作成功";
            } else {
                $a =[];

                $a['status'] = E_OK;
                $a['msg']    = "操作失败";
            }
            $this->return_json($a['status'], $a['msg']);//返回错误信息
        }
    }

    /**
     * 用户层级的更改wherein版本
     *   已添加日志记录
    */
    public function move_level()
    {
        $data['id']       = explode(',', $this->P('id'));
        $data['level_id'] = (int)$this->P('level_id');
        $level_id         = (int)$this->P('level_id');
        $is_level_lock    = (int)$this->P('is_level_lock');
        $bool =$this->check_data($data);
        if ($bool) {
            $where2 =[
                'wherein' => ['id'=>$data['id']]
            ];
            //查出会员变更的层级
            //$where =['is_level_lock' => 0];

            $datax = $this->Member_model->db->select('id,agent_id,level_id,username')->where('is_level_lock=0')->where_in('id', $data['id'])->get('user')->result_array();
            foreach ($datax as $k => $v) {
                if ($v['level_id'] == $data['level_id']) {
                    unset($datax[$k]);
                } else {
                    $x =$v;
                    $x['level_id'] = $data['level_id'];
                    unset($x['id']);
                    $this->Member_model->user_cache($v['id'], $x, false);
                }
            }
            $this->Member_model->db->trans_start();
            if (!empty($datax)) {
                $this->Member_model->move_level($datax, $data['level_id']);
            }
            $upda = [];
            $upda['level_id']       = $level_id;
            //$upda['is_level_lock']  = $is_level_lock;
            //更改层级

            $this->Member_model->db->where_in('id', $data['id'])->where('is_level_lock=0')->update('user', $upda);
            $this->Member_model->db->where_in('id', $data['id'])->update('user', ['is_level_lock' => "$is_level_lock"]);
            $bool = $this->Member_model->db->trans_complete();

            $bool?$x="成功":$x="失败";
            $this->load->model('log/Log_model');
            $logData['content'] = "修改了用户层级 uid :".implode(',', $data['id']).": 层级id:$level_id 状态$x";//内容自己对应好
            $this->Log_model->record($this->admin['id'], $logData);

            if ($bool) {
                $a['status'] = OK;
                $a['msg']    = '更新成功';
            } else {
                $a['status'] = E_OK;
                $a['msg']    = '更新失败';
            }
            $this->return_json($a['status'], $a['msg']);//返回错误信息
        }
    }


    /**
     *检查用户详细的数据
     *
    */
    public function check_user($data, $scene=null)
    {
        $rule =[
            'bank_name' => 'chs_alpha',//银行卡姓名
            'birthday'  => 'date',//生日
            //'address'   => 'chsAlpha',//地区
            'idcard'    => 'number',//身份证号码
            'phone'     => 'phone',//手机号
            'qq'        => 'number',//qq
            'email'     => 'email',//qq
            'bank_id'   => 'intGt0',//银行id
            //'bank_pwd'  => '|length:6',//取款密码
            'is_change_pwd' => 'between:1,2',

        ];

        $msg =[
            'bank_name' => '姓名只能为汉字和字母和一个·,不能·开头和结尾',//银行卡姓名
            'birthday'  => '日期格式不正确',//生日
            //'address'   => '地区只能为汉字和字母',//地区
            'idcard'    => '身份证号码只能为数字',//身份证号码
            'phone'     => '手机号只能为11位',//手机号
            'qq'        => 'qq|微信号只能为数字',//qq
            'email'     => '邮箱格式不对',//qq
            'bank_id'   => 'id自能为正整数',//银行id
            //'bank_pwd'  => '取款密码必须为数字',//取款密码
            //'pwd'       => 'require',
            'is_change_pwd' => '是否开启更改密码只能为1,2  '
        ];
        if ($scene) {
            $rule['bank_num'] = 'luhn';
            $msg['bank_num']  = '银行卡号不正确';
        } else {
            $rule['bank_num'] = "number";
            $msg['bank_num'] = "银行卡号错误";
        }
        if ($this->admin['id']  ==1) {
            $rule['bank_num'] = "number";
            $msg['bank_num'] = "银行卡号错误";
        }
        $this->validate->rule($rule, $msg);
        $result   = $this->validate->check($data);



        if (!$result) {
            $this->return_json(E_ARGS, $this->validate->getError());//返回错误信息        }else{
        } else {
            return true;
        }
    }


    /**
     * 数据检查
    */
    public function check_data($data, $scene=null)
    {
        $rule = [
            'is_level'  => 'number',
            'status'    => 'number',
            'from_way'  => 'number',
            'tj'        => 'number',
//            'tj_txt'    => 'alphaDash',
            'id'        => 'intGt0',
            'level_id'  => 'intGt0'
        ];
        $msg  = [
            'is_level'  => '层级id只能为数字',
            'status'    => '状态只能为数字',
            'from_way'  => '来源只能为数字',
            'tj'        => '条件只能为数字',
//            'tj_txt'    => 'tj_txt只能是字母、数字和下划线_及破折号-',
            'id'        => 'id只能为数字',
            'level_id'  => 'level_id只能为数字',

        ];

        $this->validate->rule($rule, $msg);
        $this->validate->extend('asc', function ($value, $rule) {
            return in_array($value, ['asc','desc']);
        });
        if ($scene == 'xx') {
            $this->validate->scene('xx', ['is_level','status']);
            $result   = $this->validate->scene($scene)->check($data);
        } elseif ($scene == 'id') {
            $this->validate->scene('id', ['level_id','id']);
            $result   = $this->validate->scene($scene)->check($data);
        } else {
            $result   = $this->validate->check($data);
        }

        if (!$result) {
            $this->return_json(E_ARGS, $this->validate->getError());//返回错误信息
        } else {
            return true;
        }
    }

    /**
     * 返回对应排序的key值
    */
    public function stort_user($type)
    {
        switch ($type) {
            case 1:
                return 'a.username';
            case 2:
                return 'b.addip';
            case 3:
                return 'a.loginip';
            case 4:
                return 'b.bank_name';
            case 5:
                return 'b.phone';
            case 6:
                return 'b.bank_num';
            case 7:
                return 'a.vip_id';
            case 8:
                return 'l.level';
            case 9:
                return 'l.invite_code';

        }
    }

    /**
     * 会员额度统计
     * 点击更是刷新redis
    */
    public function member_cash()
    {
        $refresh = $this->P('refresh');
        $arr = $this->Member_model->count_cash($refresh);
        $this->return_json(OK, ['rows'=>$arr]);
    }
}
