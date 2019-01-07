<?php
/**
 * @模块   对接第三方自动入款公司入款接口
 * @版本   Version 1.0.0
 * @日期   2018-07-10
 * chengzi
 */
defined('BASEPATH') or exit('No direct script access allowed');


class Incompany extends GC_Controller
{
    protected $admin = null;
   //必需验证参数
    protected $passkey = '';//密钥
    protected $sf = 'sign'; //签名参数
    protected $id = 'id'; //订单id
    protected $st = 'status';//状态
    protected $rm = 'remark'; //remark
    protected $us = 'username';
    protected $ip = 'ip';
    protected $or = 'order_num';
    protected $vs =['parter','passkey','username','ip','id','order_num','status','remark'];
    protected $ks = '&key='; //参与签名字符串连接符
    protected $mt = 'D'; //返回签名是否大写 D/X


    public function __construct()
    {
        parent::__construct();
        /* 不需要登陆权限的控制器和方法[小写] */
        $pass = [
            'incompany' => ['index','get_unsure_list','in_company_do','get_banklist'],
        ];

        $this->load->model('wcash/Incompany_model', 'core');
        $this->load->model('wcash/Cash_common_model', 'comm');
        $this_class = strtolower($this->router->class);
        $this_method = strtolower($this->router->method);
        $head = get_auth_headers(TOKEN_CODE_AUTH);
        $pk= $this->comm->base_incom_online('bank_incompany',$this->P('parter'),'passkey,AuthGC');
        if(isset($pk)&&!empty($pk))
        {
            /*if($head != $pk[TOKEN_CODE_AUTH]) $this->return_json(E_DENY,"头部参数验证不通过！");*/
        }else{
            $this->return_json(E_DENY,"缺少配置参数！");
        }

            if ((isset($pass[$this_class]) && in_array($this_method, $pass[$this_class])))
            {
                //指定用户和ip
                $username=$this->comm-> html_trim($this->P('username'));
                $ip=$this->comm-> html_trim($this->P('ip'));
                $this->admin = $this->core->get_one('id,privileges','admin',array('status'=>3,'username'=>$username));
                $admin = $this->admin;
                if(isset($this->admin)&&!empty($this->admin))
                {
                    //密钥验证 key
                    $this->passkey = $pk['passkey'];
                    $key = $this->P('passkey');
                    if($key!==$this->passkey)$this->return_json(E_DENY,"密钥验证不通过！");
                    //授权ip
                    //if(!in_array($ip,explode(",",$admin['privileges'])))$this->return_json(E_DENY,"ip未授权！");
                }else
                {
                    $this->return_json(E_DENY);
                }
            }

    }

    public function index() {
        echo 'hello ';
    }
    /******************公共方法*******************/
    /**
     * 获取公司未确认入款数据列表
     */
    public function get_unsure_list()
    {
        //精确条件
        $basic = array(
            'a.agent_id'       => (int)$this->P('agent_id'),
            'a.bank_card_id'   => (int)$this->P('bankCard'),
            'a.from_way'       => (int)$this->P('froms'),
            'a.order_num'       => $this->P('f_ordernum'),
            'a.price >='       => (int)$this->P('price_start'),
            'a.price <='       => (int)$this->P('price_end'),
            'a.addtime >=' => strtotime($this->P('time_start').' 00:00:00'),
            'a.addtime <=' => strtotime($this->P('time_end').' 23:59:59'),
            'a.status'         => 1
            );

        /*** 查询时间跨度不能超过两个月 ***/
        $diff_time = $basic['a.update_time <=']-$basic['a.update_time >='];
        if ($diff_time > ADMIN_QUERY_TIME_SPAN) {
            $basic['a.update_time <='] = $basic['a.update_time >=']+ADMIN_QUERY_TIME_SPAN;
        }
        /*** 特殊查询则取消时间限制：订单号 ***/
        if (!empty($basic['a.order_num'])) {
            unset($basic['a.update_time >=']);
            unset($basic['a.update_time <=']);
        }
        $is_first = $this->P('is_first');
        if (is_numeric($is_first)) {
            $basic['a.is_first'] = ($is_first == 0 ? "'0'" : $is_first);
        }
        $username = $this->P('f_username');
        if (!empty($username)) {
            $uid = $this->core->get_one('id', 'user', ['username'=>$username]);
            $basic['a.uid'] = empty($uid) ? '0' : $uid['id'];
        }
        $discount = $this->P('discount');
        if (is_numeric($discount) && $discount != 9) {
            $basic['a.is_discount'] = ($discount == 0 ? "'0'" : $discount);
        }
        // 高级搜索
        $senior['join'] = [
            ['table' => 'admin as b','on' => 'b.id=a.admin_id']];
        $level_id = (int)$this->P('level_id');
        if ($level_id > 0) {
            $senior['join'][] = ['table' => 'user as c','on' => 'c.id=a.uid'];
            $basic['c.level_id ='] = $level_id;
        }
        
        // 分页，排序
        $page = (int)$this->P('page') > 0 ?
                (int)$this->P('page') : 1;
        $rows = (int)$this->P('rows') > 0 ?
                (int)$this->P('rows') : 50;
        if ($rows > 500 || $rows < 1) {
            $rows = 50;
        }
        $page   = array(
            'page'  => $page,
            'rows'  => $rows,
            'sort'  => $this->P('sort'),
            'order' => $this->P('order')
        );
        // 排序
        $sort_field = array('order_num', 'admin_id',
                            'bank_id', 'bank_style', 'bank_card_id',
                            'status', 'is_first', 'addtime',
                            'update_time', 'remark',
                            'price', 'total_price', 'discount_price');
        if (!in_array($page['sort'], $sort_field)) {
            $page['sort'] = 'id';
        }

        // 每查询一次判断是否需要更新
        // edit by wuya 20180726 更新过期未处理入款订单 改到 定时任务里处理
        // $this->comm->update_online_status(1);
        // 查询数据
        $arr = $this->core->get_in_company($basic, $senior, $page);
        reset($arr['rows']);
        $end = current($arr['rows']);//最后一条数据
        $first = end($arr['rows']);//第一条数据
        //将每次取得数据写入日志中
        $this->load->model('log/Log_model');
            $logData['content'] = '从cash_in_company表取数据,本次从'.$first['addtime'].'到'.$end['addtime'].'共取值'.$arr['total'].'条订单id从'.$first['id'].'到'.$end['id'];
            $this->Log_model->record($this->admin['id'], $logData);
        $this->return_json(OK, $arr);
    }

    /**
     * 确认入款
     * @auther frank
     * @return bool
     *2、修改入款状态
     **/
    public function in_company_do()
    {
        //验证返回参数是否完整
        $data = $this -> get_returndata();
        //验证签名
        $this->fySign($data);

        $id = (int)$this->P('id');
        $status = (int)$this->P('status');
        if ($status==2) {
            $logData['content'] = 'ID'.$id.'-确认入款';
        } else {
            $status=3;
            $logData['content'] = 'ID'.$id.'-取消入款';
        }
        if ($id<=0) {
            $this->return_json(E_ARGS, '参数出错');
        }

        $remark = $this->P('remark');
        if (!empty($remark)) {
            $reg = '/^[\x{4e00}-\x{9fa5}a-zA-Z0-9\_\-]+$/u';
            $bool = preg_match($reg,$remark);
            if (!$bool) {
                $this->return_json(E_ARGS,"备注只能是汉字、字母、数字和下划线_及破折号-");
            }
        }
        $rkinCompanyLock = 'cash:lock:in:'.$id;
        $fbs = $this->core->fbs_lock($rkinCompanyLock);//加锁
        if (!$fbs) {
            $this->return_json(E_ARGS, '数据正在处理中');
        }
        $res = $this->core->handle_in($id, $status, $this->admin,$remark);
        $this->core->fbs_unlock($rkinCompanyLock);//解锁
        $this->load->model('log/Log_model');
        if ($res['status']) {
            $logData['content'] = $this->core->push_str .'id:'.$id;
            $this->Log_model->record($this->admin['id'], $logData);
            $this->return_json(OK);
        } else {
            $logData['content'] = $this->core->push_str. 'id:'. $id;
            $this->Log_model->record($this->admin['id'], $logData);
            $this->return_json(E_OK, $res['content']);
        }
    }

    /*获取银行账户列表*/
    public function get_banklist()
    {
        $res['rows'] = $this->core->get_list(
            'id, card_num as name', 'bank_card');
        array_unshift($res['rows'], array('id'=>0,'name'=>'全部'));
        $this->return_json(OK, $res);

    }
    /**
     * 获取返回得数据
     */
    private function get_returndata()
    {
        //获取接口返回的数据（数组形式）
        $us = $this->un;
        $ip = $this->ip;
        $or = $this->or;
        $sf = $this->id; //订单id
        $of = $this->st; //状态
        $mf = $this->rm; //remark
        $name = 'or:'.$this->P($sf);
        //获取返回的参数 GET,POST方式
        if (!empty($_REQUEST) && empty($data))
        {
            //如果是数组 转化成json记录数据库
            if(is_array($_REQUEST))
            {
                //数组转化成json 录入数据
                $temp = json_encode($_REQUEST,JSON_UNESCAPED_UNICODE);
                $this->core->online_erro("{$name}_REQUEST_array", '数据:' . $temp);
                unset($temp);
                $data = $_REQUEST;
            }
            //如果json格式 记录数据 同时转化成数组
            if (is_string($_REQUEST) && (false !== strpos($_REQUEST,'{'))
                && (false !== strpos($_REQUEST,'}')))
            {
                $this->core->online_erro("{$name}_REQUEST_json", '数据:' . $_REQUEST);
                //json格式数据先进行转码
                $data = string_decoding($_REQUEST);
            }
        }
        //判断是否获取到数据
        if (empty($data))
        {
            $msg = "2种方式都没获取到任何数据";
            $this->core->online_erro("{$name}_MUST", $msg);
            exit('ERROR:0000');
        }

        //验证参与签名参数是否都存在
        if (!empty($this->vs))
        {
            //返回不存在的签名参数
            $ds = array_diff($this->vs,array_keys($data));
            if (!empty($ds))
            {
                $pp = implode(',',$ds);
                $msg = "缺少验证签名参数：{$pp}";
                $this->core->online_erro('erro:0002',$msg);
                exit($this->error);
            }
            unset($ds,$pp,$msg);
        }
        return $data;
    }

    /**
     * 验证签名
     * @access protected
     * @param Array $data   回调参数数组
     * @param String $key 秘钥
     * @return boolean $name 错误标识
     */
    private function fySign($data)
    {
        //获取加密验证字段
        $key =$this->ks.$this->passkey;
        $sign = $data[$this->sf];
        unset($data[$this->rm]);
        unset($data[$this->sf]);//去掉sign
        //验证签名字符串
        ksort($data);
        $flag =$this->comm->get_pay_sign($data,$key,$this->sf,$this->mt);
        if ($flag[$this->sf]<>$sign)
        {
            $msg = "签名验证失败:{$sign}";
            $this->core->online_erro('erro:0003',$msg);
            $this->return_json(E_ARGS, $msg);
            exit($this->error);
        }
    }

}
