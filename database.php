<?php

$active_group = 'public';
$query_builder = TRUE;
$hostname = 'localhost';

$db['public'] = array(
    'dsn' => 'mysql:dbname=gc_base;host=' . $hostname,
    'hostname' => $hostname,
    'database' => 'gc_base',
    'username' => 'root',
    'password' => '12345678',
    'dbdriver' => 'mysqli',
    'dbprefix' => 'gc_',
    'pconnect' => FALSE,
    'db_debug' => (ENVIRONMENT !== 'production'),
    'cache_on' => FALSE,
    'cachedir' => '',
    'char_set' => 'utf8',
    'dbcollat' => 'utf8_general_ci',
    'swap_pre' => '',
    'encrypt' => FALSE,
    'compress' => FALSE,
    'stricton' => FALSE,
    'failover' => array(),
    'save_queries' => TRUE
);

$db['public_w'] = array(
    'dsn' => 'mysql:dbname=gc_base;host=' . $hostname,
    'hostname' => $hostname,
    'database' => 'gc_base',
    'username' => 'root',
    'password' => '12345678',
    'dbdriver' => 'mysqli',
    'dbprefix' => 'gc_',
    'pconnect' => FALSE,
    'db_debug' => (ENVIRONMENT !== 'production'),
    'cache_on' => FALSE,
    'cachedir' => '',
    'char_set' => 'utf8',
    'dbcollat' => 'utf8_general_ci',
    'swap_pre' => '',
    'encrypt' => FALSE,
    'compress' => FALSE,
    'stricton' => FALSE,
    'failover' => array(),
    'save_queries' => TRUE
);

$db['shixun'] = array(
    'dsn' => 'mysql:dbname=gc_sx;host=' . $hostname,
    'username' => 'root',
    'password' => '12345678',
    'dbdriver' => 'pdo',
    'dbprefix' => 'gc_',
    'pconnect' => FALSE,
    'db_debug' => (ENVIRONMENT !== 'production'),
    'cache_on' => FALSE,
    'cachedir' => '',
    'char_set' => 'utf8',
    'dbcollat' => 'utf8_general_ci',
    'swap_pre' => '',
    'encrypt' => FALSE,
    'compress' => FALSE,
    'stricton' => FALSE,
    'failover' => array(),
    'save_queries' => TRUE
);

$db['shixun_w'] = array(
    'dsn' => 'mysql:dbname=gc_sx;host=' . $hostname,
    'username' => 'root',
    'password' => '12345678',
    'dbdriver' => 'pdo',
    'dbprefix' => 'gc_',
    'pconnect' => FALSE,
    'db_debug' => (ENVIRONMENT !== 'production'),
    'cache_on' => FALSE,
    'cachedir' => '',
    'char_set' => 'utf8',
    'dbcollat' => 'utf8_general_ci',
    'swap_pre' => '',
    'encrypt' => FALSE,
    'compress' => FALSE,
    'stricton' => FALSE,
    'failover' => array(),
    'save_queries' => TRUE
);
