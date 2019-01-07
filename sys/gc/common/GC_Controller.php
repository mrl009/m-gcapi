<?php
defined('BASEPATH') or exit('No direct script access allowed');
defined('CONFIG_GAMES') or include_once(BASEPATH.'gc/config/config_games.php');
defined('CONFIG_DSN') or include_once(BASEPATH.'gc/config/config_dsn.php');
defined('PUBLIC_REDIS_HOST') or include(dirname(__FILE__).'/../../../redis.php');
/**
 * Base Class
 *
 * 所有项目的基类
 *
 * @package     CodeIgniter
 * @subpackage  Libraries
 * @category    Libraries
 * @author      frank
 * @link
 */

class GC_Controller extends CI_Controller
{
    public $from_way = 5;
    public $_token = '';    /* token */
    public $_sn = '';       /* 标识：域名或 sn */
    public $_dsn = '';       /* 站点私库连接信息 */
    public $_redis_public = null;       /* 公库redis单例连接 */
    public $_redis_public_ro = null;    /* 公库redis read only 单例连接 */
    public $_redis_private = null; /* 单例模式私库redis连接 */
    public $_db_private = null; /* 单例模式实现私库db连接 */
    /* 不需要 dsn 和站点标识的接口(controller=>(action1,action2))：如 验证码 */
    public $no_dsn = [
        'login' => ['get_token_private_key', 'code'], 'settlementtest'=>['index'],
        ];

    public function __construct() /* {{{ */
    {
        parent::__construct();
        //set_error_handler('_error_handler2');
        if (!is_cli()) {
            header("Access-Control-Allow-Origin: *");
            // header('Content-Type:text/html; charset=utf-8');
            // header("Content-Type: application/json");
        }
        if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, PATCH, OPTIONS");
            header("Access-Control-Allow-Headers: Accept, Authorization, Content-Type, Pragma, Origin, Cache-Control, AuthGC, FROMWAY");
            exit;
        }
        /* 获取标识和token */
        $token = get_auth_headers(TOKEN_CODE_AUTH);
        $authcc = explode(';', $token);
        $this->_sn = $authcc[0];
        if (!empty($authcc[1])) {
            $this->_token = $authcc[1];
        }
        $this->is_repeat(); /* 重放检测 */

        $this->check_black_ip();

        $from_way = get_auth_headers('FROMWAY');
        switch ($from_way) {
            case FROM_IOS:
                $this->from_way = FROM_IOS;
                break;
            case FROM_ANDROID:
                $this->from_way = FROM_ANDROID;
                break;
            case FROM_PC:
                $this->from_way = FROM_PC;
                break;
            case FROM_WAP:
                $this->from_way = FROM_WAP;
                break;
            case FROM_H5APP:
                $this->from_way = FROM_H5APP;
                break;
            default:
                $this->from_way = FROM_UNKNOW;
                break;
        }
        $this->load->library('validate');//载入验证类

        if (!is_cli()) {
            $this->pay_domin(); //验证商城域名
        }
    } /* }}} */

    public function __destruct()
    {
        if ($this->_redis_private) {
            $this->_redis_private->close();
        }
        if ($this->_db_private) {
            $this->_redis_private->close();
        }
    }

    /**
     * 检测IP是否正常
     * @return  void
     */
    protected function check_black_ip()
    {
        return ;
    }

    /**
     * 记录异常IP
     * @return  void
     */
    protected function ip_error_record()
    {
        return ;
    }

    /**
     * @brief 防重放攻击
     *      只需要防范 在非 cli 运行模式下的 post 提交
     */
    function is_repeat() /* {{{ */
    {
        if (is_cli()) {
            return false;
        }

        if (!defined('CLIENT_IP')) {
            define('CLIENT_IP', get_ip());
        }
        $post_string = '';
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && CLIENT_IP != '127.0.0.1') {
            $post_string = http_build_query($_POST);
            //$post_string = file_get_contents('php://input');
        } else {
            return false;
        }

        $chk_string = CLIENT_IP.':'.$_SERVER['REQUEST_URI'].':'.$post_string;
        $redis_key = 'api-repeat:'.md5($chk_string);

        $this->load->model('GC_Model');
        /* 极端情况下可以使用 redis setnx 命令 */
        //$is_im = $this->GC_Model->redisP_setnx($redis_key, 1);      /* */
        //$this->GC_Model->redisP_expire($redis_key, 1);              /* x秒后过期 */
        $is_repeat = $this->GC_Model->redisP_get($redis_key);
        $this->GC_Model->redisP_setex($redis_key, 1, (int) $is_repeat + 1);             /* 2秒后过期 */
        if (($_SERVER['REQUEST_METHOD'] == 'POST' && $is_repeat) || $is_repeat > 10) {  /* POST 超过1次，GET 超过5次 */
            header('HTTP/1.1 403 fuck!');
            wlog(APPPATH.'logs/'.$this->GC_Model->sn.'_repeat_'.date('Ym').'.log', $_SERVER['REQUEST_METHOD'].' '.$_SERVER['REQUEST_URI'].' '.$post_string.' '.$is_repeat);
            return $this->return_json(E_DATA_REPEAT, '手速太快,休息一下马上回来!');
        }
        
        return false;
    } /* }}} */

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
     * api return json
     * @param   mixed $data   要返回的数据/出错消息
     * @param   int   $code   结果编辑码
     * @return void
     */
    public function return_json($code = OK, $data = array()) /* {{{ */
    {
        $result['code'] = $code;
        $result['msg'] = $this->result_code_to_name($code);
        if ($code != OK) {
            if (!empty($data) && !is_array($data)) {
                $result['msg'] = $data;
            } elseif (is_array($data)) {
                $result['data'] = $data;
            }
            echo json_encode($result, JSON_UNESCAPED_UNICODE);
            exit;
        }
        if (!empty($data)) {
            $result['data'] = $data;
        }
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
        exit;
    } /* }}} */

    /**
     * code转中文
     * @param   int   $code   结果编辑码
     * @return $string
     */
    protected function result_code_to_name($code) /* {{{ */
    {
        switch ($code) {
            case OK:
                $r = '请求成功';
                break;
            case OK_OLINE:
                $r = '操作成功,请等待审核...';
                break;
            case E_ARGS:
                $r = '参数出错';
                break;
            case E_OP_FAIL:
                $r = '操作失败';
                break;
            case E_DENY:
                $r = '拒绝访问';
                break;

            default:
                $r = '请求失败';
                break;
        }
        return $r;
    } /* }}} */

    /**
     * 推送消息
     * @param   string   $code   业务编码
     * @param   string   $msg    消息内容
     * @param   string   $order    订单号
     * @return
     */
    public function push($code, $msg, $order = null) /* {{{ */
    {
        if (empty($code) || empty($msg)) {
            return false;
        }
        $this->load->model('GC_Model');
        $sid = $this->GC_Model->sn;

        error_reporting(0);
        $this->load->library('mqtt', array('address'=>SOCKET_SERVER, 'port'=>SOCKET_PORT,'clientid'=>'MQTT CLIENT'));
        if ($this->mqtt->connect()) {
            $this->mqtt->publish('u/demo', json_encode(array('code'=>$code,'msg'=>$msg,'sid'=>$sid, 'order' => $order)), 0);
            $this->mqtt->close();
        }
    } /* }}} */

    /**
     * @return bool|mixed|string
     */
    public function up_logo_do($bool = true) /* {{{ */
    {
        if(empty($_FILES)){
            $this->return_json(E_OP_FAIL, '文件为空！');
        }
        $config['max_size'] = '2048000';
        if ($_FILES['file']['size']>=$config['max_size']) {
            $msg = '您上传的文件有'.$_FILES['logo']['size']/1024 .'KB，不要大于'.$config['max_size']/1024 . 'KB!';
            $this->return_json(E_OP_FAIL, $msg);
        }
        $upload_api = UPLOAD_URL;
        $tmpname = $_FILES['file']['name'];
        $tmpfile = $_FILES['file']['tmp_name'];
        $tmpType = $_FILES['file']['type'];
        $result_json = upload_file($upload_api, $tmpname, $tmpfile, $tmpType);//curl上传
        if ($result_json) {
            if (!$bool) {
               return $result_json;
            }
            //$result = json_decode($result_json,true);
            //$result = str_replace('\\','/',$result);
            $this->return_json(OK, $result_json);
        } else {
            $this->return_json(E_OP_FAIL, '操作失败！');
        }
    } /* }}} */

    /**
     * 支付域名跳转
    */
    public function pay_domin() /* {{{ */
    {
        $product_id  = strtolower($this->uri->segment(1));
        $product_id2 = strtolower($this->uri->segment(2));
        if (isset($this->no_dsn[$product_id]) && in_array($product_id2, $this->no_dsn[$product_id])) {
            return true;
        }
        $key = "online:domin";
        $host    = $_SERVER['HTTP_HOST'];
        $is_http = $this->is_https();
        if ($is_http) {
            $s = 'https://'.$host;
        } else {
            $s = 'http://'.$host;
        }
        $this->load->model('GC_Model');
        $domain = $this->GC_Model->redis_HGET($key, $s);

        if (empty($domain)) {
            $domainArr = $this->GC_Model->get_all('pay_domain,shopurl', 'bank_online_pay', []);
            $data = [];
            foreach ($domainArr as $value) {
                if ($value['pay_domain'] == $s) {
                    $domain = $value['shopurl'];
                }
                $data[$value['pay_domain']] = $value['shopurl'];
            }
            $this->GC_Model->redis_HMSET($key, $data);
        }

        /* 判断是否是允许访问支付域名跳转 */
        $verify = ['pay_test','callback','agentpay','fastpay'];
        $pid = strtolower($product_id);
        $pid2 = strtolower($product_id2);
        if (!empty($domain) && (!in_array($pid,$verify) 
            && ('pay_test' <> $pid2)))
        {
            header('Location:'.$domain);
            die;
        }

        /*if (empty($domain)) {
        } else {
            if (strtolower($product_id) == 'agentpay' || strtolower($product_id) == 'callback'|| strtolower($product_id2) == 'pay_test') {
            } else {
                header('Location:'.$domain);
                die;
            }
        }*/
    } /* }}} */

    /**
     * 检查是否是https协议
     */
    public function is_https() /* {{{ */
    {
        if (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off') {
            return true;
        } elseif (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
            return true;
        } elseif (!empty($_SERVER['HTTP_FRONT_END_HTTPS']) && strtolower($_SERVER['HTTP_FRONT_END_HTTPS']) !== 'off') {
            return true;
        }

        return false;
    } /* }}} */
}

/* end file */
