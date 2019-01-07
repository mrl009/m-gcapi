<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');


/**
* 仿TP 获取输入参数 支持过滤和默认值
*使用方法: 和TP一致
*/
if(!function_exists('input'))
{
    function input($name='',$default='',$filter=null)
    {
        if (!empty($name) && (strpos($name,'.')))
        {
            list($method,$name) = explode('.',$name,2);
        } else {
            $method = 'param';
        }
        //参数方式 
        $input = [];
        $method = strtolower($method);
        if ('get' == $method) $input = & $_GET;
        if ('post' == $method) $input = & $_POST;
        if ('param' == $method)
        {
            $m = $_SERVER['REQUEST_METHOD'];
            if ('POST' == $m) 
            {
               $input = $_POST; 
            } elseif ('PUT' == $m) {
                parse_str(file_get_contents('php://input'), $input);
            } else {
                $input = $_GET;
            }
            unset($m);
        }
        if ('request' == $method) $input =& $_REQUEST; 
        if ('session' == $method) $input =& $_SESSION; 
        if ('cookie' == $method) $input =& $_COOKIE; 
        if ('server' == $method) $input =& $_SERVER; 
        if ('globals' == $method) $input =& $GLOBALS; 
        // 获取全部变量
        if(empty($name))
        {
            $data = $input; 
            $filters = !empty($filter) ? $filter : 'htmlspecialchars';
            if($filters)
            {
                $filters = explode(',',$filters);
                foreach($filters as $filter)
                {
                    $data = array_map_recursive($filter,$data); 
                }
            }
        // 取值操作
        } elseif(isset($input[$name])){
            $data = $input[$name];
            $filters = !empty($filter) ? $filter : 'htmlspecialchars';
            if($filters)
            {
                $filters = explode(',',$filters);
                foreach($filters as $filter)
                {
                    if(function_exists($filter))
                    {
                        $data = is_array($data) ? array_map_recursive($filter,$data) : $filter($data);
                    } else {
                        $data = filter_var($data,is_int($filter) ? $filter : filter_id($filter));
                        if(false === $data) {
                            return isset($default) ? $default : NULL;
                        }
                    }
                }
            }
        } else {
            $data = isset($default) ? $default : NULL;
        }
        return $data;
    }
}

function array_map_recursive($filter, $data) 
{
    $result = array();
    foreach ($data as $key => $val) {
        $result[$key] = is_array($val)
         ? array_map_recursive($filter, $val)
         : call_user_func($filter, $val);
    }
    return $result;
}



/**
 * WebSocket 相关操作
 */
class WsAct {
    private static $sn = '';
    private static $dsn = '';
    private static $publicRedis = null;
    private static $redis = null;

    public function __construct($sn)
    {
        self::$sn = $sn;
        self::$dsn = $this->getWsConf();
        self::$redis = $this->getWsRedis();
    }

    /**
     * 主要是操作redis key 中加入 sn
     */
    public function __call($name, $arguments)
    {
        if ( !empty($arguments[0]) ) {
            $arguments[0] = self::$sn .':'. $arguments[0];
        }

        return call_user_func_array([self::$redis, $name], $arguments);
    }

    public function getDsn(){
        return self::$dsn;
    }

    /**
     * 获取 WS 中私库的配置
     */
    private function getWsConf(){
        if ( empty(self::$publicRedis) ) {
            $dsn = parse_url(WS_REDIS_PUBLIC);
            $dsn['host'] = isset($dsn['host']) ? ($dsn['host']) : '127.0.0.1';
            $dsn['port'] = isset($dsn['port']) ? ($dsn['port']) : '6379';
            $dsn['user'] = isset($dsn['user']) ? ($dsn['user']) : '';
            $dsn['path'] = isset($dsn['path']) ? (substr($dsn['path'], 1)) : '8';
            try {
                $redis = new Redis();
                $redis->connect($dsn['host'], $dsn['port']);
                if ($dsn['user']) {
                    $redis->auth($dsn['user']);
                }
                $redis->select($dsn['path']); //默认库
                self::$publicRedis = $redis;
                @wlog(APPPATH.'logs/ws_redisP_'.date('Ym').'.log', $_SERVER["HTTP_HOST"].$_SERVER["REQUEST_URI"].':dsn:'.var_export($dsn,true));
            } catch (RedisException $e) {
                $error = $e->getMessage();
                @wlog(APPPATH.'logs/ws_redisP_'.date('Ym').'.log', $_SERVER["HTTP_HOST"].$_SERVER["REQUEST_URI"].':Error:'.$error . ':dsn' .var_export($dsn,true));
                get_instance()->return_json(E_OP_FAIL, 'WS公库:'. $error);
            }
        }
        return self::$publicRedis->hGet('dsn', self::$sn);
    }

    /**
     * 获取 WS 中站点私库redis
     */
    private function getWsRedis(){
        if (self::$redis) {
            return self::$redis;
        }
        if (empty(self::$dsn)) {
            @wlog(APPPATH.'logs/ws_redis_'.date('Ym').'.log', $_SERVER["HTTP_HOST"].$_SERVER["REQUEST_URI"].':empty ws dsn:'. self::$sn);
            get_instance()->return_json(E_OP_FAIL,'empty ws dsn:'. self::$sn);
        }
        $dsn = json_decode(self::$dsn, true);
        $dsn = parse_url($dsn['redis']);
        $dsn['host'] = isset($dsn['host']) ? ($dsn['host']) : '127.0.0.1';
        $dsn['port'] = isset($dsn['port']) ? ($dsn['port']) : '6379';
        $dsn['user'] = isset($dsn['user']) ? ($dsn['user']) : '';
        $dsn['path'] = isset($dsn['path']) ? (substr($dsn['path'], 1)) : '5';
        try {
            $redis = new Redis();
            $redis->connect($dsn['host'], $dsn['port']);
            if ($dsn['user']) {
                $redis->auth($dsn['user']);
            }
            $redis->select($dsn['path']); //默认库
            @wlog(APPPATH.'logs/ws_redis_'.date('Ym').'.log', $_SERVER["HTTP_HOST"].$_SERVER["REQUEST_URI"].',sn:'. self::$sn . ',dsn:' .var_export($dsn,true));
            return $redis;
        } catch (RedisException $e) {
            $error = $e->getMessage();
            @wlog(APPPATH.'logs/ws_redis_'.date('Ym').'.log', $_SERVER["HTTP_HOST"].$_SERVER["REQUEST_URI"].',Error:'.$error . ',sn:'. self::$sn .  ',dsn:' .var_export($dsn,true));
            get_instance()->return_json(E_OP_FAIL, 'WS私库:'. $error);
        }
    }

    /**
     * 往WS中注入消息
     */
    public function sendWs( $data = [] )
    {
        $dsn = json_decode(self::$dsn, true);
        $data['sn'] = $dsn['sn'];

        // 连接ws
        $client = stream_socket_client( 'tcp://'. $dsn['ws_text'] .'?sn='. $data['sn'] );
        if(!$client) return false;

        // 数据加密码认证
        $data['code'] = md5( json_encode($data) . md5('_pwd_12345') );
        $data = json_encode($data);
        $rs = fwrite($client, $data . PHP_EOL);
        fclose($client);
        return $rs;
    }
}