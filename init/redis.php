<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/* 公库 redis 配置 */
$config['socket_type'] = 'tcp'; //`tcp` or `unix`
$config['socket'] = '/var/run/redis.sock'; // in case of `unix` socket type
$config['host'] = '192.168.8.102';
// $config['host'] = '127.0.0.1';
$config['password'] = '';
$config['port'] = 6379;
$config['timeout'] = 0;
$config['db'] = 8;

/* 公库 read only redis 配置 */
defined('PUBLIC_REDIS_RO_HOST') OR define('PUBLIC_REDIS_RO_HOST', '192.168.1.168');
defined('PUBLIC_REDIS_RO_PORT') OR define('PUBLIC_REDIS_RO_PORT', 6379);
defined('PUBLIC_REDIS_RO_PWD') OR define('PUBLIC_REDIS_RO_PWD', '');

/*SOCKET_SERVER 配置*/
defined('SOCKET_SERVER') OR define('SOCKET_SERVER','192.168.8.102');
defined('SOCKET_PORT') OR define('SOCKET_PORT',1883);

/* WS中公库REDIS */
defined('WS_REDIS_PUBLIC') OR define('WS_REDIS_PUBLIC', 'redis://127.0.0.1:6379/8');
