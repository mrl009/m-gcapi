<?php
/**
 * @模块   公共Model
 * @版本   Version 1.0.0
 * @日期   2017-03-22
 * BIG CHAO：谁动这个MODEL吊死他
 */
defined('BASEPATH') or exit('No direct script access allowed');

class GC_Model extends CI_Model
{
    public $redis_private = null;
    private $redis_private_pre = 'gc:';
    public $redis_public = null;        //公库的redis
    public $redis_public_ro = null;     //公库的read only redis
    private $redis_public_pre = '';     //公库的redis key 前缀
    public $redis = null;
    public $redis_pre = '';
    public $db_private = null;
    public $db_public = null;
    public $db_public_w = null;
    public $db_card = null;
    public $db_shixun = null;
    public $db = null;
    public $db_shixun_w=null;//为了新增读写分写添加
    public $sn = '';            /* 站点识别码/私库数据库名 */
    public $dsn_redis = '';     /* 私库 redis */
    public $dsn_mysql = '';     /* 私库 mysql */
    public $dsn_card = '';      /* 私库 card */

    protected $private_key = '';//私钥
    private $tbname='';

    public $pay_model_1 = array(1,4,8,9,10,17,19,24);//生成二维码扫码支付的code

    public function __construct()
    {
        parent::__construct();
        if (empty($this->redis_public) || empty($this->db_private) || empty($this->db)) {
            if (!is_cli()) {
                //$this->init($_SERVER['HTTP_HOST']);  //默认库
                $this->init(get_instance()->_sn);  //默认库
            }
        }
    }

    /**
     * 初始化redis,db_private,db_private
     * 在非 cli 模式时，model 在创建时就初始化连接
     * 在 cli 模式时，需要程序显式的调用 model->init() 来初始化连接。
     *
     * 先DSN文件,后Pub Redis，取私库 db 和 redis 连接信息，
     * 再连私库 db 和 redis；私库 db_card 和公库 db 在需要时通过 select_db 才连接。
     * @param string $key domain/sn 在 cli 模式，传 sn, 其他模式传 domain
     */
    public function init($key = '')
    {
        $controller = &get_instance();
        if (empty($key)) {
            $this_class = strtolower($controller->router->class);
            $this_method = strtolower($controller->router->method);
            /* 是否为验证码等不需要 dsn 信息的请求？ */
            if (isset($controller->no_dsn[$this_class]) && in_array($this_method,$controller->no_dsn[$this_class])) {
                return false;
            } else {
                /* 支付域名,不用传 AuthGC 头 */
                if ($controller->_dsn) {
                    $dsn = $controller->_dsn;
                } else {
                    $controller->_dsn = get_dsn($_SERVER['HTTP_HOST']);
                    $dsn = $controller->_dsn;
                }
                if (empty($dsn)) {
                    wlog(APPPATH.'logs/'.$key.'_'.date('Y').'.log', $_SERVER["HTTP_HOST"].$_SERVER["REQUEST_URI"].':empty key.');
                    $controller->return_json(E_ARGS, 'init error, empty key.');
                    return false;
                }
            }
        } else {
            if ($key===$controller->_sn && $controller->_dsn) {
                $dsn = $controller->_dsn;
            } else {
                $controller->_dsn = get_dsn($key);
                $dsn = $controller->_dsn;
            }
            if (empty($dsn)) {
                @wlog(APPPATH.'logs/'.$key.'_'.date('Y').'.log', $_SERVER["HTTP_HOST"].$_SERVER["REQUEST_URI"].':empty dsn:'.$key);
                $controller->return_json(E_ARGS, 'init error, empty dsn.');
                return false;
            }
        }
        $dsn = json_decode($dsn, true);
        $this->sn = isset($dsn['sn']) ? $dsn['sn'] : '';                    /* 站点识别码 */
        $this->dsn_mysql = isset($dsn['database']) ? $dsn['database'] : ''; /* 私库 dsn */
        $this->dsn_redis = isset($dsn['redis']) ? $dsn['redis'] : '';       /* 私库 redis */
        $this->dsn_card = str_replace('?', '_card?', $this->dsn_mysql);     /* 私库 db_card */

        /* 再连私 redis */
        if ($controller->_redis_private) {
            $this->redis_private = $controller->_redis_private;
        }
        if (!$this->redis_private) {
            $dsn = parse_url($this->dsn_redis);
            $dsn['host'] = isset($dsn['host']) ? ($dsn['host']) : '127.0.0.1';
            $dsn['port'] = isset($dsn['port']) ? ($dsn['port']) : '6379';
            $dsn['user'] = isset($dsn['user']) ? ($dsn['user']) : '';
            $dsn['path'] = isset($dsn['path']) ? (substr($dsn['path'], 1)) : '1';
            $controller->_redis_private = new Redis();
            $controller->_redis_private->connect($dsn['host'], $dsn['port']);
            $controller->_redis_private->auth($dsn['user']);
            $controller->_redis_private->select($dsn['path']); //默认库
            $this->redis_private = $controller->_redis_private;
        }
        /* 再连私库 */
        if ($controller->_db_private) {
            $this->db_private = $controller->_db_private;
        }
        if (!$this->db_private) {
            $controller->_db_private = $this->load->database($this->dsn_mysql, true);  //加载私有数据库
            $this->db_private = $controller->_db_private;
        }
        $this->db = $this->db_private; //设置私库为默认库
        return true;
    }

    /**
     * 接收get
     * @param mixed $key 键值
     * return mixed 接收到的值
     */
    protected function G($key, $b = true)
    {
        return $this->input->get($key, $b);
    }

    /**
     * 接收post
     * @param mixed $key 键值
     * return mixed 接收到的值
     */
    protected function P($key, $b = true)
    {
        return $this->input->post($key, $b);
    }

    /**
     * 选择数据库
     */
    public function select_db($_db = 'private')
    {
        /* 公库/card 按需加载 */
        if ($_db == 'public') {
            if (!$this->db_public) {
                $this->db_public = $this->load->database($_db, true);   //加载共用只读数据库
            }
        } elseif ($_db == 'public_w') {
            if (!$this->db_public_w) {
                $this->db_public_w = $this->load->database($_db, true); //加载共用读写数据库
            }
        } elseif ($_db == 'card') {
            if (!$this->db_card) {
                $this->db_card = $this->load->database($this->dsn_card, true);//加载优惠卡数据库
            }
        } elseif ($_db == 'shixun') {
            if (!$this->db_shixun) {
                $this->db_shixun = $this->load->database($_db, true);//加载视讯数据库
            }
        }elseif ($_db == 'shixun_w'){//为了新增读写分写添加
            if (!$this->db_shixun_w) {
                $this->db_shixun_w = $this->load->database($_db, true);//加载视讯数据库
            }
        }
        if ($_db == 'private') {
            $this->db = $this->db_private;
        } elseif ($_db == 'public') {
            $this->db = $this->db_public;
        } elseif ($_db == 'public_w') {
            $this->db = $this->db_public_w;
        } elseif ($_db == 'card') {
            $this->db = $this->db_card;
        } elseif ($_db == 'shixun') {
            $this->db = $this->db_shixun;
        }elseif ($_db == 'shixun_w'){//为了新增读写分写添加
            $this->db = $this->db_shixun_w;
        }
    }

    /**
     * 选择数据库
     */
    public function select_redis_db($db = 0, $_db = 'private')
    {
        $CI = &get_instance();
        if ($_db == 'public') {
            if (empty($CI->_redis_public)) {
                $CI->load->driver('cache', array('adapter' => 'redis'));
                $CI->_redis_public = $CI->cache;
                $CI->_redis_public->select($db ? $db : REDIS_PUBLIC);
            }
            $this->redis_public = $CI->_redis_public;
            $this->redis = $this->redis_public;
            return $this->redis_public->select($db);
        } elseif ($_db == 'public_ro') {
            if (!defined('PUBLIC_REDIS_RO_HOST') || !defined('PUBLIC_REDIS_RO_PORT')) {
                $this->select_redis_db($db, 'public');
                $CI->_redis_public_ro = $this->redis_public;
            } elseif (empty($CI->_redis_public_ro)) {
                $CI->_redis_public_ro = new Redis();
                $CI->_redis_public_ro->connect(PUBLIC_REDIS_RO_HOST, PUBLIC_REDIS_RO_PORT);
                $CI->_redis_public_ro->auth(PUBLIC_REDIS_RO_PWD);
                $CI->_redis_public_ro->select($db ? $db : REDIS_PUBLIC);
            }
            $this->redis_public_ro = $CI->_redis_public_ro;
            $this->redis = $this->redis_public_ro;
        } else {
            $this->redis = $this->redis_private;
            return $this->redis_private->select($db);
        }
    }

    /**
     * 会员增加额度，并写入日志
     * @param  $uid         int       用户ID
     * @param  $balance     float     会员当前额度（累加之前）
     * @param  $amount      float     会员增加或者减少额度
     * @param  $order_num   string    现金流订单号
     * @param  $type        int       现金流水类型
     * @param  $remark      string    备注
     * @param  $Prcie       float     实际金额 不含手续费和优惠费用
     * @param  $integral    int       增加的积分
     * @param  $vip_id      int       晋级ID
     * @return $bool
     */
    public function update_banlace($uid = 0, $amount = 0, $order_num = '', $type = 6, $remark = '', $price = 0, $integral = 0, $vip_id = 0)
    {
        //计算用户最大最小出入款的cashtype'
        $inCashType = array(5,6,7,8,12);
        $outCashType = array(13,14);
        $disOutCashType = [18];
        $discountCashType = array(3,11,20,22);
        $cashType   = array(5,6,7,8,12,13,14);
        $realAmount = $amount;
        $userData = $this->db->select('*')->limit(1)->get_where('user', ['id'=>$uid])->row_array();
        //$userData = $this->get_one_user($uid,'balance,max_income_price,max_out_price');
        $balance = $userData['balance'];
        $whereUser['id'] = $uid;
        /* 先写记录 */
        $cashData['uid'] = $uid;
        $cashData['order_num'] = $order_num;
        $cashData['type'] = $type;
        $cashData['before_balance'] = $userData['balance'];
        $cashData['amount'] = $realAmount;
        $cashData['balance'] = $amount + $balance;
        $cashData['remark'] = $remark;
        $cashData['agent_id'] = $userData['agent_id'];
        $cashData['addtime'] = time();
        $b5 = $this->write('cash_list', $cashData);     // 4、加入现金记录
        if (!$b5) {
            @wlog(APPPATH.'logs/'.$this->sn.'_cash_list_error_'.date('Ym').'.log', "[cash_list error]查询".json_encode($userData)."插入:".json_encode($cashData));
            return false;
        }

        if ($amount > 0) {
            if ($price > $userData['max_income_price'] && in_array($type, $cashType)) {
                $this->db->set('max_income_price', $price);
            }
            if ($price > 0 && $userData['max_income_price'] == 0 && in_array($type, $inCashType)) {
                if ( $type != 12 || strpos($remark,'人工存款-人工存入') !== false) {
                    $this->db->set('first_time', time());
                }
            }
            $this->db->set('balance', 'balance+'.$amount, FALSE);
            $balance = $balance + $amount;
            $whereUser['balance>='] = 0;
        } else {
            if (abs($price) > $userData['max_out_price'] && in_array($type, $cashType)) {
                $this->db->set('max_out_price', abs($price));
            }
            $amount = $amount * -1;
            $this->db->set('balance', 'balance-'.$amount, FALSE);
            $whereUser['balance>='] = $amount;
            $balance = $balance - $amount;
        }

        // 加积分
        if ($integral) {
            $this->db->set('integral', 'integral+' . intval($integral), false);
        }
        // 修改等级
        if ($vip_id) {
            $this->db->set('vip_id', $vip_id);
        }
        // 优惠统计
        if (in_array($type, $discountCashType)) {
            $this->db->set('discount', 'discount+' . abs($amount), FALSE);
        }
        // 出款数据
        if (in_array($type, $outCashType)) {
            $this->db->set('out_t_total', 'out_t_total+' . abs($amount), FALSE);
            $this->db->set('out_t_num', 'out_t_num+1', FALSE);
        }
        // 取消出款
        if (in_array($type, $disOutCashType)) {
            $this->db->set('out_t_total', 'out_t_total-' . abs($amount), FALSE);
            $this->db->set('out_t_num', 'out_t_num-1', FALSE);
        }
        // 入款数据
        if (in_array($type, $inCashType)) {
            $this->db->set('in_t_total', 'in_t_total+' . abs($amount), FALSE);
            $this->db->set('in_t_num', 'in_t_num+1', FALSE);
        }
        $this->db->where($whereUser)->update('user');   // 3、加钱
        if (!$this->db->affected_rows()) {
            @wlog(APPPATH.'logs/'.$this->sn.'_cash_list_error_'.date('Ym').'.log', "[update error]查询".json_encode($userData)."插入:".json_encode($cashData));
            return false;
        }
        //@wlog(APPPATH.'logs/'.$this->sn.'_cash_list_'.date('Ym').'.log', "查询".json_encode($userData)."插入:".json_encode($cashData));
        return true;
    }

    /**
     * 打开表
     */
    public function open($table)
    {
        $this->tbname = $table;
    }

    /**
     * 获取一条数据
     * @flids       字段
     * @table       表名
     * @parm        tj_arr     条件array('字段'=>'值')
     * @condition  group order like条件
    */
    public function get_one($flids = '*', $table, $tj_arr = array(), $condition = array())
    {
        $this->db->select($flids);
        $this->db->from($table);
        if (array_key_exists('orderby', $condition)) {
            foreach ($condition['orderby'] as $k => $v) {
                $this->db->order_by($k, $v);
            }
        }
        if (array_key_exists('join', $condition)) {
            if (is_array($condition['join'])) {
                foreach ($condition['join'] as $joinData) {
                    $this->db->join($joinData['table'], $joinData['on'], 'left');
                }
            } else {
                $this->db->join($condition['join'].' as b', $condition['on'], 'left');
            }
        }
        if (array_key_exists('wherein', $tj_arr)) {
            foreach ($tj_arr['wherein'] as $k => $v) {
                if ($v != '') {
                    $this->db->where_in($k, $v);
                }
            }
            unset($tj_arr['wherein']);
        }
        $this->db->limit(1);
        $this->db->where($tj_arr);
        $query = $this->db->get();
        if ($row = $query->row_array()) {
            return $row;
        }
        return array();
    }

     /**
     * 获取多条数据
     * @parm    flids       字段
     * @parm    table       表名
     * @parm    tj_arr     条件array('字段'=>'值')
     * @parm    db          数据库
     * @parm    condition   group order like条件
    */
    public function get_list($flids = '*', $table = '', $tj_arr = array(), $condition = array(), $page = array())
    {
        if (empty($page)) {
            return $this->get_all($flids, $table, $tj_arr, $condition);
        } else {
            $this->open($table);
            return $this->get_list_limit($flids, $tj_arr, $page, $condition);
        }
    }
    
    /**
     * 获取多条数据
     * @parm    flids       字段
     * @parm    table       表名
     * @parm    tj_arr     条件array('字段'=>'值')
     * @parm    db          数据库
     * @parm    condition   group order like条件
    */
    public function get_all($flids = '*', $table = '', $tj_arr = array(), $condition = array())
    {
        $tj1 = array();
        foreach ($tj_arr as $k => $v) {
            if (isset($v) && !empty($v)) {
                $tj1[$k] = $v;
            }
        }
        if (empty($table)) {
            $table = $this->tbname;
        }
        $this->db->from($table.' as a');
        $this->db->select($flids);
        if (array_key_exists('join', $condition)) {
            if (is_array($condition['join'])) {
                foreach ($condition['join'] as $joinData) {
                    $this->db->join($joinData['table'], $joinData['on'], 'left');
                }
            } else {
                $this->db->join($condition['join'].' as b', $condition['on'], 'left');
            }
        }
        if (array_key_exists('limit', $condition)) {
            $this->db->limit($condition['limit']);
        }
        if (array_key_exists('page_limit', $condition)) {
            $this->db->limit($condition['page_limit'][0], $condition['page_limit'][1]);
        }
        if (array_key_exists('wherein', $condition)) {
            foreach ($condition['wherein'] as $k => $v) {
                $this->db->where_in($k, $v);
            }
        }
        if (array_key_exists('orwhere', $condition)) {
            foreach ($condition['orwhere'] as $k => $v) {
                $this->db->or_where($k, $v);
            }
        }
        if (array_key_exists('like', $condition)) {
            foreach ($condition['like'] as $k => $v) {
                if (!empty($v)) {
                    $this->db->like($k, $v);
                }
            }
        }
        if (array_key_exists('notlike', $condition)) {
            foreach ($condition['nolike'] as $k => $v) {
                if (!empty($v)) {
                    $this->db->not_like($k, $v);
                }
            }
        }
        if (array_key_exists('orderby', $condition)) {
            foreach ($condition['orderby'] as $k => $v) {
                $this->db->order_by($k, $v);
            }
        }
        if (array_key_exists('groupby', $condition)) {
            foreach ($condition['groupby'] as $k => $v) {
                $this->db->group_by($v);
            }
        }
        if (array_key_exists('orwhere', $condition)) {
            foreach ($condition['orwhere'] as $k => $v) {
                $this->db->or_where($k, $v);
            }
        }
        if (array_key_exists('wheresql', $condition)) {
            foreach ($condition['wheresql'] as $v) {
                $this->db->where($v);
            }
        }
        if ($tj1 == array()) {
            $query = $this->db->get();
        } else {
            $query = $this->db->get_where('', $tj1);
        }
        $rows = $query->result_array();
        return $rows;
    }

    /**
     * 分页查询
     * @args     条件array('字段'=>'值')
     * @flids      字段
     * @table      表名
     * @db         数据库
     * @condition  group order like条件
    */
    private function get_list_limit($flids = '*', $args = array(), $other = array(), $parameter = array())
    {
        $data = array();
        $pagesize = $other['rows'];         //一页条数
        $curPage  = $other['page'];         //当前页
        //$field    = empty($other['sort']) ? 'a.id' : $other['sort'];         //排序字段
        $sort     = empty($other['sort']) ? 'a.id' : 'a.'.$other['sort'];    //field for ordering
        $order    = empty($other['order'])?'desc':$other['order'];        //排序规则
        $start    = empty($other['start']) ? 1 : $other['start'];        //起始位置
        $options = array('order' => $order, 'sort' => $sort);
        foreach ($args as $k => $v) {
            if ($v ==='0' || (isset($v) && !empty($v))) {
                if (strstr($k, '.')) {
                    $options['conditions'][$k] = $v;
                } else {
                    $options['conditions']['a.'.$k] = $v;
                }
            }
        }
        $data['total'] = empty($other['total']) ? 10000 : $other['total'];
        //if ($data['total'] == -1) {//判断是否统计
        $data['total'] = $this->count_rows('*', $options, $parameter);//get total rows
        //}
        $data['rows'] = $this->search($flids, $options, $pagesize, $curPage ? ($curPage-1) * $pagesize : $start, $parameter);
        return $data;
    }
    
    /**
     * 统计总条数
     * @options      where条件
     * @parameter    高级搜索及排序
     */
    public function count_rows($flids, $options = array(), $parameter = array())
    {
        /*$this->db->select($flids);
        $this->db->from($this->tbname.' as a');
        if (array_key_exists('limit', $parameter)) {
            $this->db->limit($parameter['limit']);
        }
        $sql = $this->_query($options, $parameter, false);
        $sql = "select count(*) as total_rows from($sql) total_table";*/
        if (array_key_exists('orderby', $parameter)) {
            unset($parameter['orderby']);
        }
        if (array_key_exists('groupby', $parameter)) {
            $flids = '';
            foreach ($parameter['groupby'] as $v) {
                $flids = $flids == '' ? 'DISTINCT '. $v : ','. $v;
            }
            unset($parameter['groupby']);
        }
        $this->db->select("count({$flids}) as total_rows");
        $this->db->from($this->tbname.' as a');
        $sql = $this->_query($options, $parameter, false);
        $que = $this->db->query($sql);
        $row = $que->row_array();
        $this->db->_reset_select();
        return $row['total_rows'];
    }
    
    /**
     * 查询
     * @options      where条件
     * @param       高级搜索及排序
    */
    private function search($flids, $options = array(), $count = 20, $offset = 0, $parameter = array())
    {
        if (!is_array($options)) {
            return array();
        }
        if ($count) {
            $this->db->limit((int)$count, (int)$offset);
        }
        $this->db->select($flids);
        $this->db->from($this->tbname.' as a');
        $query = $this->_query($options, $parameter);

        $rows = array();
        foreach ($query->result_array() as $row) {
            $rows[] = $row;
        }
        return $rows;
    }
    
    /**
     * 处理SQL条件
     */
    private function _query($options = null, $parameter = array(), $execute = true)
    {
        if (array_key_exists('order_multi', $options)) {
            foreach ($options['order_multi'] as $v) {
                $this->db->order_by($v['column'], $v['dir']);
            }
        }

        if (count($parameter) != 0) {
            if (array_key_exists('join', $parameter)) {
                if (is_array($parameter['join'])) {
                    foreach ($parameter['join'] as $joinData) {
                        $this->db->join($joinData['table'], $joinData['on'], 'left');
                    }
                } else {
                    $this->db->join($parameter['join'].' as b', $parameter['on'], 'left');
                }
            }
        }
        if (!empty($options['conditions'])) {
            if (count($options['conditions']) != 0) {
                foreach ($options['conditions'] as $k => $v) {
                    if (isset($v) && !empty($v)) {
                        $this->db->where($k, $v);
                    }
                    if ($v === '0') {
                        $this->db->where($k, $v);
                    }
                }
            }
        }

        if (count($parameter) != 0) {
            if (array_key_exists('like', $parameter)) {
                foreach ($parameter['like'] as $k => $v) {
                    $like_value = $this->request($v);
                    if (!empty($v)) {
                        $this->db->like($k, $v);
                    }
                }
            }
            if (array_key_exists('notlike', $parameter)) {
                foreach ($parameter['notlike'] as $k => $v) {
                    if (!empty($v)) {
                        $this->db->not_like($k, $v);
                    }
                }
            }

            if (array_key_exists('groupby', $parameter)) {
                foreach ($parameter['groupby'] as $k => $v) {
                    $this->db->group_by($v);
                }
            }
            if (array_key_exists('orderby', $parameter)) {
                foreach ($parameter['orderby'] as $k => $v) {
                    $this->db->order_by($k, $v);
                }
            }
            if (array_key_exists('wherein', $parameter)) {
                foreach ($parameter['wherein'] as $k => $v) {
                    if ($v != '') {
                        $this->db->where_in($k, $v);
                    }
                }
            }
            if (array_key_exists('orwhere', $parameter)) {
                foreach ($parameter['orwhere'] as $k => $v) {
                    $this->db->or_where($k, $v);
                }
            }
            if (array_key_exists('wheresql', $parameter)) {
                foreach ($parameter['wheresql'] as $v) {
                    $this->db->where($v);
                }
            }
        }

        if (!empty($options['sort']) && !empty($options['order'])) {
            $this->db->order_by($options['sort'], $options['order']);
        }
        return $execute ? $this->db->get() : $this->db->_compile_select();
    }

    /**
     * 保存数据
     * @arr      保存的数据
     * @where    条件（为空既为插入）
     * @table    表名（为空取$this->tbname）
    */
    public function write($table = '', $arr = array(), $where = array())
    {
        $tb = $table ? $table : $this->tbname;
        if ($arr == array()) {
            return false;
        }
        if (array_key_exists('wherein', $where)) {
            foreach ($where['wherein'] as $k => $v) {
                if ($v != '') {
                    $this->db->where_in($k, $v);
                }
            }
            unset($where['wherein']);
        }
        if ($where != array()) {
            $this->db->where($where);
            $this->db->update($tb, $arr);
        } else {
            $this->db->insert($tb, $arr);
            $id = $this->db->insert_id();
        }
        if ($this->db->affected_rows() > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 删除
     * @where    条件
     * @table    表名
    */
    public function delete($table = '', $ids = array())
    {
        if (empty($ids)) {
            $this->status('ERROR', 'data is null');
        }
        $this->db->where_in('id', $ids);
        $this->db->delete($table);
        if ($this->db->affected_rows() > 0) {
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * 返回状态
     * @s    状态码
     * @m    状态信息
    */
    public function status($s, $m)
    {
        return array('status'=>$s, 'msg'=>$m);
    }

    /**
     * 返回状态
     * @s    状态码
     * @m    状态信息
    */
    public function returnJson($s, $m)
    {
        return json_encode(array('status'=>$s, 'msg'=>$m));
    }

    /**
     * redis分布式锁，解决并发问题或者只允许操作一次的问题
     * @param   $rk     redis的key值
     * @param   $lockTime   锁定时间（秒数），超过这个时间自动解锁，解决死锁问题
     * @return  $bool   true：正常操作，false：处于锁状态
     */
    public function fbs_lock($rk='', $lockTime=5)
    {
        $b = $this->redis_setnx($rk, $_SERVER['REQUEST_TIME']);
        if ($b) {
            $this->redis_expire($rk, $lockTime);
            return true;
        }//没锁
      //锁已超时
        return false;
    }

    /**
     * redis分布式解锁
     * @param   $rk     redis的key值
     * @return  $bool   true：正常操作，false：处于锁状态
     */
    public function fbs_unlock($rk='')
    {
        return $this->redis_del($rk);
    }

    /*redis调用的方法*/
    public function __call($method, $args = array())
    {
        if (stripos($method, 'redisP_') === 0) {
            $method = substr($method, 7);
            /* 配置自动读写分离，不需要系统自动 redis 读写分离时，请注释这部分 */
            $ro_key = ['get', 'hget', 'hmget', 'hgetall', 'hkeys', 'hlen', 'zrevrange', 'lrange', 'zrange'];
            if (in_array($method, $ro_key)) {
                if (empty($this->redis_public_ro)) {
                    $this->select_redis_db(REDIS_PUBLIC, 'public_ro');
                    //@wlog(APPPATH.'logs/'.$this->sn.'_redis_'.date('Ym').'.log', $_SERVER['REQUEST_URI'].':read-only:select_redis_db');
                }
                $this->redis = $this->redis_public_ro;
                //wlog(APPPATH.'logs/'.$this->sn.'_redis_'.date('Ym').'.log', 'read-only:'.$method.'->'.json_encode($args));
            } else {
                if (empty($this->redis_public)) {
                    $this->select_redis_db(REDIS_PUBLIC, 'public');
                    //@wlog(APPPATH.'logs/'.$this->sn.'_redis_'.date('Ym').'.log', $_SERVER['REQUEST_URI'].'enable-write:select_redis_db');
                }
                $this->redis = $this->redis_public;
                //wlog(APPPATH.'logs/'.$this->sn.'_redis_'.date('Ym').'.log', 'enable-write:'.$method.'->'.json_encode($args));
            }
            $this->redis_pre = $this->redis_public_pre;
        } elseif (stripos($method, 'redis_') === 0) {
            $method = substr($method, 6);
            $this->redis = $this->redis_private;
            $this->redis_pre = $this->sn.':';
        } else {
            die('GC_Model不存在该方法');
        }
        if (!empty($args) && !is_numeric($args[0])) {
            $args[0] = $this->redis_pre.$args[0];
        }
        return call_user_func_array(array($this->redis, $method), $args);
    }

    /**
     * 缓存会员数据根据ID找会员名
     * @param   $id     会员ID
     * @param   $text   array(username=>会员名,'level_id'=>层级ID)
     * @param   $read   true：读取，false：写操作
     * @return  array   array)_
     */
    public function user_cache($id, $text=array(), $read=true)
    {
        if (empty($id) || !is_numeric($id)) {
            return false;
        }
        /**$this->redis_select(6);
        $b=500;
        if($id<=$b){
            $k=500;
        }else{
            $k=floor($id/$b)*$b;
        }**/
        $this->redis_select(6);
        if ($read) {
            $u = $this->redis_hget('user', $id);
            if (empty($u)) {
                $this->select_db('private');
                $u = $this->get_one('id,username,level_id,agent_id', 'user', array('id'=>$id));
                if ($u!=array()) {
                    $text=json_encode(array('id'=>$u['id'],'username'=>$u['username'],'level_id'=>$u['level_id'],'agent_id'=>$u['agent_id']));
                    $this->redis_hset('user', $id, $text);
                }
                return $u;
            }
            return json_decode($u, true);
        } else {
            if (empty($text)) {
                return false;
            }
            $text=json_encode($text);
            $u = $this->redis_hset('user', $id, $text);
            return $u;
        }
        $this->redis_select(REDIS_DB);
    }

    /**
     * 缓存层级
     * @param   $id     会员ID
     * @param   $name   层级名称
     * @param   $read   true：读取，false：写操作
     * @return  string  层级名称
     */
    public function level_cache($id, $name='', $read=true)
    {
        if (empty($id) || !is_numeric($id)) {
            return false;
        }
        $this->redis_select(6);
        if ($read) {
            $u = $this->redis_hget('level', $id);
            if (empty($u)) {
                $this->select_db('private');
                $u = $this->get_one('level_name', 'level', array('id'=>$id));
                if ($u!=array()) {
                    $this->redis_hset('level', $id, $u['level_name']);
                }
                return $u['level_name'];
            }
            return $u;
        } else {
            if (empty($name)) {
                return false;
            }
            $u = $this->redis_hset('level', $id, $name);
            return $u;
        }
        $this->redis_select(REDIS_DB);
    }

    /**
     * 缓存线上支付
     * @param   $id     线上支付ID
     * @param   $name   线上支付名称
     * @param   $read   true：读取，false：写操作
     * @return  string  线上支付名称(银邦(ID:20)
     */
    public function pay_cache($id, $name='', $read=true)
    {
        if (empty($id)) {
            return false;
        }
        $this->redis_select(6);
        if ($read) {
            $u = $this->redis_hget('online_pay', $id);
            if (empty($u)) {
                $this->select_db('public');
                $u = $this->get_one('id, online_bank_name as pay_name', 'bank_online', array('id'=>$id));
                if ($u!=array()) {
                    $u['pay_name'] = $u['pay_name'].'(ID:'.$u['id'].')';
                    $this->redis_hset('online_pay', $id, $u['pay_name']);
                }
                return $u['pay_name'];
            }
            return $u;
        } else {
            if (empty($name)) {
                return false;
            }
            $u = $this->redis_hset('online_pay', $id, $name);
            return $u;
        }
        $this->redis_select(REDIS_DB);
    }

    /**
     * 获取站点配置
     *
     * @access public
     * @param String|Array $keys 获取的key 多个则数组
     * @return Array
     */
    public function get_gcset($key_arr='*')
    {
        $this->redis_select(4);
        $keys = 'sys:gc_set';
        $jsondata = $this->redis_get($keys);
        if (empty($jsondata)) {
            $this->select_db("private");
            $data = $this->get_list('key,value', 'set');
            $set_arr = [];
            foreach ($data as $key => $value) {
                $set_arr[$value['key']] = trim($value['value']);
            }
            $this->redis_set($keys, json_encode($set_arr));
            $data = $set_arr;
        } else {
            $data = json_decode($jsondata, true);
        }

        if (!empty($key_arr) && is_array($key_arr)) {
            $resu = $data;
            $data = [];
            foreach ($key_arr as $k => $v) {
                $data[$v] = empty($resu[$v])?0:$resu[$v];
            }
        }

        $this->redis_select(5);
        return $data;
    }

    /**
     * 设置站点配置，并清理缓存
     *
     * @access public
     * @param Array $keys 站点配置值
     * @return Array
     */
    public function set_gcset($key_arr2=[])
    {
        $this->redis_select(4);
        $this->select_db("private");
        $key_arr = [];
        foreach ($key_arr2 as $key => $value) {
            $key_arr[] = ['key'=>trim($key),'value'=>trim($value)];
        }
        $bool = $this->db->update_batch('set', $key_arr, 'key');
        $this->redis_del("sys:gc_set");
        $this->redis_select(5);
        return $bool;
    }

    /**
     * 
     */
    public function __destruct()
    {
        if (isset($_GET['t'])) {@wlog(APPPATH.'logs/model_res_'.date('Ym').'.log', __CLASS__.'::'.__METHOD__);}
        if ($this->db_private) {
            $this->db_private->close();
        }
        if ($this->db_public) {
            $this->db_public->close();
        }
        if ($this->db_public_w) {
            $this->db_public_w->close();
        }
        if ($this->db_card) {
            $this->db_card->close();
        }
        if ($this->db_shixun) {
            $this->db_shixun->close();
        }
        if (isset($_GET['t'])) {@wlog(APPPATH.'logs/model_res_'.date('Ym').'.log', __CLASS__.'::'.__METHOD__.' end');}
        //parent::__destruct();
    }
}

/* end file */
