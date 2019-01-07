<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/* 公库 redis 配置 */
$config['socket_type'] = 'tcp'; //`tcp` or `unix`
$config['socket'] = '/var/run/redis.sock'; // in case of `unix` socket type
 $config['host'] = '127.0.0.1';
$config['password'] = '';
$config['port'] = 6379;
$config['timeout'] = 0;
$config['db'] = 8;

/*公库 redis read only 配置*/
defined('SOCKET_SERVER') OR define('SOCKET_SERVER','127.0.0.1');
defined('SOCKET_PORT') OR define('SOCKET_PORT',1883);
defined('PUBLIC_REDIS_RO_PWD') OR define('PUBLIC_REDIS_RO_PWD', '');

/* 博友无限公库 redis 配置 */
$config['wx_socket_type'] = 'tcp';
$config['wx_socket'] = '/var/run/redis.sock';
$config['wx_host'] = '127.0.0.1';
$config['wx_password'] = '';
$config['wx_port'] = 6379;
$config['wx_timeout'] = 0;
$config['wx_db'] = 8;

