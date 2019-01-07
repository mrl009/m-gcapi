<?php
if (!defined('BASEPATH')) {
    exit('No direct access allowed.');
}


class SX_Model extends MY_Model
{
    public $db_shixun = null;
    public $db_shixun_w = null;
    public $sx_redis = null;
    public $config = null;
    protected static $_default_config = array(
        'socket_type' => 'tcp',
        'host' => '127.0.0.1',
        'password' => NULL,
        'port' => 6379,
        'timeout' => 0
    );

    public function __construct()
    {
        parent::__construct();
    }

    public function init($key = '')
    {
        $this->load_config();
        if (in_array($key, WX_DSN)) {
            if (!$this->sx_redis) {
                $this->sx_redis = new Redis();
                $this->sx_redis->connect($this->config['wx_host'], $this->config['wx_port']);
                $this->sx_redis->select(REDIS_LONG);
            }
        } else {
            parent::init($key);
        }
        return true;
    }

    private function load_config()
    {
        $controller = &get_instance();
        if ($controller->config->load('redis', TRUE, TRUE)) {
            $this->config = array_merge(self::$_default_config, $controller->config->item('redis'));
        } else {
            $this->config = self::$_default_config;
        }
    }
}
