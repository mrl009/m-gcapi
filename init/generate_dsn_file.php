<?php
defined('BASEPATH') OR define('BASEPATH',dirname(__DIR__));
// 配置redis公库配置文件的绝对路径 以 '.php' 结尾
$pub_redis_config_path = BASEPATH . DIRECTORY_SEPARATOR . 'redis.php';
//$pub_redis_config_path = 'D:\\git\\gcapi\\redis.php';
// 配置dsn文件存储的路径 以 '/' 结尾
$dsn_file_save_path = BASEPATH . DIRECTORY_SEPARATOR . 'config_dsn' . DIRECTORY_SEPARATOR;
//$dsn_file_save_path = 'D:\\git\\gcapi\\config_dsn\\';

if (PHP_SAPI !== 'cli') {
    exit('Fuck U');
}
if (empty($pub_redis_config_path) || empty($dsn_file_save_path)) {
    exit("缺少配置参数\n");
}
if (!file_exists($pub_redis_config_path)) {
    exit("Redis配置文件不存在\n");
}
if (!is_dir($dsn_file_save_path)) {
    exit("DSN文件存储路径不正确\n");
}
if (!is_writable($dsn_file_save_path)) {
    exit("DSN文件存储路径不可写\n");
}

ini_set('display_errors', 1);
ini_set('memory_limit', '512M');

include_once "$pub_redis_config_path";

$redis = new Redis();
$redis->connect($config['host'], $config['port']);
$redis->select($config['db']);
$dsns = $redis->hGetAll('dsn');

$head = <<<EOT
<?php
\$config['dsn'] = [
EOT;
$files = [];
foreach ($dsns as $k => $v) {
    $file = substr(trim($k),0,3);
    if (!isset($files[$file])) {
        $files[$file] = $head;
    }
    $files[$file] .= PHP_EOL . '    \'' . $k . '\' => \'' . $v . '\',';
}
array_walk($files,function (&$v){
    $v = rtrim($v, ',');
    $v .= PHP_EOL . '];';
});
foreach ($files as $file => $content) {
    $name = $dsn_file_save_path . $file . '_dsn.php';
    file_put_contents($name,$content);
}
