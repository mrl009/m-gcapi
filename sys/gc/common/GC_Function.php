<?php

defined('BASEPATH') OR exit('No direct script access allowed');

if (!function_exists('dump')) {
    /**
     * 浏览器友好的变量输出
     * @param mixed $var 变量
     * @param boolean $echo 是否输出 默认为True 如果为false 则返回输出字符串
     * @param string $label 标签 默认为空
     * @param boolean $strict 是否严谨 默认为true
     * return void|string
     */
    function dump($var, $echo=true, $label=null, $strict=true)
    {
        $label = ($label === null) ? '' : rtrim($label) . ' ';
        if (!$strict) {
            if (ini_get('html_errors')) {
                $output = print_r($var, true);
                $output = '<pre>' . $label . htmlspecialchars($output, ENT_QUOTES) . '</pre>';
            } else {
                $output = $label . print_r($var, true);
            }
        } else {
            ob_start();
            $output = ob_get_clean();
            if (!extension_loaded('xdebug')) {
                $output = preg_replace('/\]\=\>\n(\s+)/m', '] => ', $output);
                $output = '<pre>' . $label . htmlspecialchars($output, ENT_QUOTES) . '</pre>';
            }
        }
        if ($echo) {
            echo($output);
            return null;
        }else
            return $output;
    }
}

if (!function_exists('format_time')) {
    function format_time($second)
    {
        $str = '';
        switch (true) {
            case $second < 60:
                $str = $second . '秒';
                break;
            case $second < 3600:
                $str = floor($second/60).'分';
                $str .= ($second%60).'秒';
                break;
            case $second < 86400:
                $str = floor($second/3600).'小时';
                $str .= floor($second%3600/60).'分';
                $str .= ($second%60).'秒';
                break;
            case $second >= 86400:
                $str = floor($second/86400).'天';
                $str .= floor($second%86400/3600).'小时';
                $str .= floor($second%86400%3600/60).'分';
                $str .= ($second%60).'秒';
                break;
            default:
                break;
        }
        //$str = str_replace('0秒', '', $str);
        //$str = str_replace('0分', '', $str);
        //$str = str_replace('0小时', '', $str);
        return $str;
    }
}

if (!function_exists('wlog')) {
    /**
     * @fn
     * @brief 日志记录函数
     * @param $log_file    日志文件名
     * @param $log_str    日志内容
     * @param $show        日志内容是否show出
     * @param $log_size    日志文件最大大小，默认20M
     * @return void
     */
    function wlog($log_file, $log_str, $show = false, $log_size = 20971520) /* {{{ */
    {
        ignore_user_abort(TRUE);
    
        $time = '['.date('Y-m-d H:i:s').'] ';
        if ( $show ) {
            echo $time.$log_str.((PHP_SAPI == "cli") ? "\r\n" : "<br>\r\n");
        }
        if ( empty($log_file) ) {
            $log_file = 'wlog.txt';
        }
        if ( defined('APP_LOG_PATH') ) {
            $log_file = APP_LOG_PATH.$log_file;
        }
    
        if ( !file_exists($log_file) ) { 
            $fp = fopen($log_file, 'a');
        } else if ( filesize($log_file) > $log_size ) {
            $fp = fopen($log_file, 'w');
        } else {
            $fp = fopen($log_file, 'a');
        }
    
        if ( flock($fp, LOCK_EX) ) {
            $cip = empty($_SERVER["HTTP_X_FORWARDED_FOR"]) ? (empty($_SERVER['REMOTE_ADDR']) ? '' : $_SERVER['REMOTE_ADDR']) : $_SERVER["HTTP_X_FORWARDED_FOR"];
            $log_str = $time.'['.$cip.'] '.$log_str."\r\n";
            fwrite($fp, $log_str);
            flock($fp, LOCK_UN);
        }
        fclose($fp);
    
        ignore_user_abort(FALSE);
    } /* }}} */
}

if (!function_exists('get_top_domain')) {
    /** 
     * 获取顶级域名, 简洁快速版
     * 不处理 com.cn com.cc 等二级后缀的国家域名
     * 如果需要处理 2级的国家域名, 请打开代码注释
     */
    function get_top_domain($domain = 'x.com') /* {{{ */
    {
        $data = explode('.', $domain);
        $count_dot = count($data);
        // 判断是否是双后缀国家域名
        /*
        $is_2 = false;
        $domain_2 = ['com.cc','net.cc','org.cc','com.cn','net.cn','org.cn'];
        foreach ($domain_2 as $d) {
            if (strpos($domain, $d)) {
                $is_2 = true;
                break;
            }
        }
        */
        // 如果是双后缀 
        // if ($is_2 == true) {
            $top_domain = $data[$count_dot - 2].'.'.$data[$count_dot - 1];
        // } else {
        //    $top_domain = $data[$count_dot - 3].'.'.$data[$count_dot - 2].'.'.$data[$count_dot - 1];
        // }
        return $top_domain;
    } /* }}} */
}

if (!function_exists('array_make_key')) {
    /**
     * @将数据以一个值作用key。
     * @param array $arr 数据
     * @param string $key 数据的键值，数据必需要有这个键值。
     * @return array
     */
    function array_make_key($arr=array(), $key='id')
    {
        if(empty($arr) || !is_array($arr) ) {
            return $arr;
        }
        $res = array();
        foreach ($arr as $temp) {
            $res[$temp[$key]] = $temp;
        }
        return $res;
    }
}

if (!function_exists('get_ip')) {
    /**
     * 获取IP
     * @return $string
     */
    function get_ip()
        {
            $arr_ip_header = array(
                'HTTP_CDN_SRC_IP',
                'HTTP_PROXY_CLIENT_IP',
                'HTTP_WL_PROXY_CLIENT_IP',
                'HTTP_CLIENT_IP',
                'HTTP_X_FORWARDED_FOR',
                'REMOTE_ADDR',
            );
            $client_ip = 'unknown';
            foreach ($arr_ip_header as $key)
            {
                if (!empty($_SERVER[$key]) && strtolower($_SERVER[$key]) != 'unknown')
                {
                    $client_ip = $_SERVER[$key];
                    break;
                }
            }
            //去除伪造IP
            $ss = trim($client_ip);
            $arr = explode(',', $ss);
            $count = count($arr);
            if($count<=3) {
                $ip = $arr[0];
            }else if($count>=4) {
                $ip = $arr[1];
            }
            if($ip=='::1'){
                $ip = '127.0.0.1';
            }
            return trim($ip);
        }
}


if (!function_exists('token_encrypt')) {
    /**
     * 加密
     * @param string $token 需要被加密的数据
     * @param string $private_key 密钥
     * @return string
     */
    function token_encrypt($token='',$private_key='')
    {
        return base64_encode(openssl_encrypt($token, 'BF-CBC', md5($private_key), null, substr(md5($private_key), 0, 8)));
        //return base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, md5($private_key), $token, MCRYPT_MODE_CBC, md5(md5($private_key))));
    }
}

if (!function_exists('token_decrypt')) {
    /**
     * 解密
     * @param string $en_token 加密数据
     * @param string $private_key 密钥
     * @return string
     */
    function token_decrypt($en_token='',$private_key='')
    {
        return rtrim(openssl_decrypt(base64_decode($en_token), 'BF-CBC', md5($private_key), 0, substr(md5($private_key), 0, 8)));
        //return rtrim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, md5($private_key), base64_decode($en_token), MCRYPT_MODE_CBC, md5(md5($private_key))), "\0");
    }
}

if (!function_exists('rand_code')) {
    /**
     * 随便验证码
     * @param int $y_w 宽度
     * @param int $y_h 高度
     * @return string
     */
    function rand_code($y_w=0,$y_h=0)
    {
        if($y_w==0 || $y_w>200){
            $y_w=50;
        }
        if($y_h==0 || $y_h>100){
            $y_h=20;
        }
        $y_w_=intval($y_w/5);
        $y_h_=intval($y_h/1.3);
        $y_f_=intval($y_h/1.8);
        $point=$y_w/10; 
        $zs=VERIDY_CODE_LENGTH;
        $string = VERIDY_CODE_RANGE;
        $randcode = '';
        for ($i = 0 ; $i < $zs ; $i++){
            if($i==0) $randcode .=$string[mt_rand(0 , strlen($string) - 1)];
            else $randcode .=$string[mt_rand(0 , strlen($string) - 1)];
        }
        return $randcode;
    }
}

if (!function_exists('drawing')) {
    /**
     * 画图
     * @param string $randcode 画图
     * @param string $y_w 宽度
     * @return string
     */
    function drawing($randcode='0000',$y_w=0,$y_h=0,$noBc=0,$isMobile=0,$noBorder=0)
    {
        header ("content-type: image/png");
        $y_w_=intval($y_w/5);
        $y_h_=intval($y_h/1.3);
        $y_f_=intval($y_h/1.8);
        $point=$y_w/10;
        $image_x = $y_w;
        $image_y = $y_h;
        $image = imagecreate($image_x , $image_y);
        if (! $noBc) {
            $background_color = imagecolorallocate($image,mt_rand(200,255),mt_rand(0,255),mt_rand(0,255));
        } else {
            $background_color = imagecolorallocate($image,255,255,255);
        }
        if ($isMobile) {
            $font_color = imagecolorallocate($image, 251, 52, 63);
            $gray_color = imagecolorallocate($image, 245,36, 46);
        } else {
            $font_color = imagecolorallocate($image,1,1,1);
            $gray_color  = imagecolorallocate($image,0,0,0);
        }
        $fonts=array('texb.ttf','micross.ttf');
        for($i=0;$i<VERIDY_CODE_LENGTH;$i++){
            $array = array(-1,0,1);
            $p = array_rand($array);
            $an = $array[$p]*mt_rand(-15,20);
            imagettftext($image,$y_f_, $an, $i*$y_w_+$y_w_/2,$y_h_, $font_color, BASEPATH."/fonts/".$fonts[mt_rand(0,count($fonts)-1)],substr($randcode,$i,1) );
        }
        if (! $noBorder) {
            imagerectangle($image,0,0,$image_x-1, $image_y-1,$gray_color);
        }
        for($i=0;$i<$point;$i++){
            imagesetpixel($image,mt_rand(0,$image_x),mt_rand(0,$image_y),$gray_color);
        }
        imagepng($image);
        imagedestroy($image);
    }
}

if (!function_exists('get_auth_headers')) {
    /**
     * @fn
     * @brief get http headers
     * @return 
     */
    function get_auth_headers($header_key = null)
    {
        if (function_exists('apache_request_headers')) {
            /* Authorization: header */
            $headers = apache_request_headers();
            $out = array();
            foreach ($headers AS $key => $value) {
                $key = str_replace(" ", "-", ucwords(strtolower(str_replace("-", " ", $key))));
                $out[$key] = $value;
            }
        } else {
            $out = array();
            if (isset($_SERVER['CONTENT_TYPE'])) {
                $out['Content-Type'] = $_SERVER['CONTENT_TYPE'];
            }
            if (isset($_ENV['CONTENT_TYPE'])) {
                $out['Content-Type'] = $_ENV['CONTENT_TYPE'];
            }
            foreach ($_SERVER as $key => $value) {
                if (substr($key, 0, 5) == "HTTP_") {
                    $key = str_replace(" ", "-", ucwords(strtolower(str_replace("_", " ", substr($key, 5)))));
                    $out[$key] = $value;
                }
            }
        }
        if ($header_key != null) {
            $header_key = ucfirst(strtolower($header_key));
            if (isset($out[$header_key])) {
                return $out[$header_key];
            } else {
                return false;
            }
        }
        return $out;
    }


}


if (!function_exists('gid_tran')) {
    /* gid转换炉，私彩变国彩 */
    function gid_tran($gid)
    {
        $a =  array(51 => 1, 52 => 2, 56 => 6, 57 => 7, 58 => 8, 59 => 9, 60 => 10, 61 => 11, 62 => 12, 63 => 13, 65 => 15, 66 => 16,
            67 => 17, 68 => 18, 69 => 19, 70 => 20, 71 => 21, 72 => 22, 73 => 73, 74 => 74, 76 => 26, 77 => 27, 83 => 33, 84 => 34, 85 => 35, 86 => 36, 87 => 37);
        /*if(empty($a[$gid])) {
            return 0;
        }else{
            return $a[$gid];
        }*/
        return isset($a[$gid]) ? $a[$gid] : $gid;
    }
}


if (!function_exists('get_chunjie')) {
    /**
     * 判断当前时间是春节还是其他时间
     */
    function get_chunjie($now)
    {
        if ($now > strtotime('2017-02-15') && $now <= strtotime('2018-02-15')) {
            return 0;
        } else if ($now > strtotime('2018-02-15') && $now <= strtotime('2018-02-22')) {
            return false;
        } else if ($now > strtotime('2018-02-22') && $now <= strtotime('2019-02-04')) {
            return 0;
        } else if ($now > strtotime('2019-02-04') && $now <= strtotime('2019-02-11')) {
            return false;
        } else if ($now > strtotime('2019-02-11') && $now <= strtotime('2020-01-24')) {
            return 1;
        } else if ($now > strtotime('2020-01-24') && $now <= strtotime('2020-02-01')) {
            return false;
        } else if ($now > strtotime('2020-02-01') && $now <= strtotime('2021-02-11')) {
            return 2;
        } else if ($now > strtotime('2021-02-11') && $now <= strtotime('2021-02-18')) {
            return false;
        } else if ($now > strtotime('2021-02-18') && $now <= strtotime('2022-01-31')) {
            return 3;
        } else if ($now > strtotime('2022-01-31') && $now <= strtotime('2022-02-07')) {
            return false;
        } else if ($now > strtotime('2022-02-07') && $now <= strtotime('2023-01-21')) {
            return 4;
        } else {
            return true;
        }
    }
}

if (!function_exists('code_pay')) {
    /**
     * 根据pay_code 返回对应的支付方式
     */
     function code_pay($code)
    {
        $str = "";
        switch($code){
            case 1:
                $str = "微信";
                break;
            case 2:
                $str = "微信app";
                break;
            case 3:
                $str = "微信扫码";
                break;
            case 4:
                $str = "支付宝";
                break;
            case 5:
                $str = "支付宝app";
                break;
            case 6:
                $str = "支付宝扫码支付";
                break;
            case 7:
                $str = "网银";
                break;
            case 8:
                $str = "QQ钱包";
                break;
            case 9:
                $str = "京东钱包";
                break;
            case 10:
                $str = "百度钱包";
                break;
            case 11:
                $str = "彩豆";
                break;
            case 12:
                $str = "QQ钱包WAP";
                break;
            case 13:
                $str = "京东钱包WAP";
                break;
            case 14:
                $str = "一码付";
                break;
            case 17:
                $str = "银联";
                break;
            case 18:
                $str = "银联WAP";
                break;
            case 20:
                $str = "百度钱包WAP";
                break;
            case 22:
                $str = "财付通";
                break;
            case 23:
                $str = "财付通WAP";
                break;
            case 25:
                $str = "快捷支付";
                break;
            case 26:
                $str = "收银台";
                break;
            case 38:
                $str = "苏宁";
                break;
            case 39:
                $str = "苏宁WAP";
                break;
            case 40:
                $str = "微信条形码";
                break;
            case 41:
                $str = "支付宝条形码";
                break;
        }
        return $str;
    }
}


if(!function_exists('upload_file')){
    /**
     * 上传图片到远程服务器
     * @param $url 远程服务器api
     * @param $filename $_FILES['file']['name']
     * @param $path $_FILES['file']['tmp_name']
     * @param $type $_FILES['file']['type']
     * @return mixed
     * super
     */
    function upload_file($url,$filename,$path,$type){
        if (class_exists('\CURLFile')) {
            $data = array('file'=>(new CURLFile(realpath($path),$type,$filename)),'sid'=>1);
        }else {
            $data = array(
                'file'=>('@'.realpath($path).";type=".$type.";filename=".$filename),'sid'=>1
            );
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 跳过证书检查 url为https时使用
        //curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, true);  // 从证书中检查SSL加密算法是否存在
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true );
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $return_data = curl_exec($ch);
        curl_close($ch);
        return $return_data;
    }
}



if (!function_exists('sortArrByField')) {
    /**
     * 根据某字段对二维数组进行排序
     * @param $array 需要排序的二维数组
     * @param $field 二维数组中的列名
     * @param bool|false $desc
     * super
     */
    function sortArrByField(&$array, $field, $desc = false)
    {
        $fieldArr = array();
        foreach ($array as $k => $v) {
            $fieldArr[$k] = $v[$field];
        }
        $sort = $desc == false ? SORT_ASC : SORT_DESC;
        array_multisort($fieldArr, $sort, $array);
    }
}

/* 层级树 */
if ( ! function_exists('level_tree')) {
    /**
     * 层级树
     * 用法：$data = $this->level_tree($data);
     $data = array(
        array(id=1,pid=0),
        array(id=1,pid=0)
     );
     pid=0表示根节点，pid不等于0表示其他根节点的子节点，而pid都是记录id值
     所以：r_field=id， l_field=pid
     * @param array   $data     数据集
     * @param array   $root     根据节点
     * @param string  $r_field  叶子节点找到根节点字段（id）
     * @param string  $l_field  叶子节点的字段（pid）
     * @param string  $leaf     叶子集合key
     */
    function level_tree(&$data, $root=array(), 
                        $r_field='id', $l_field='pid', $leaf='child') 
    {
        if(empty($root)) 
        {
            $root = array(array($r_field=>0));
        }

        foreach ($root as $k => $v) 
        {
            foreach ($data as $kk => $vv) 
            {
                if($v[$r_field] == $vv[$l_field]) 
                {
                    $root[$k][$leaf][] = $vv;
                    unset($data[$kk]);
                }
            }
        }
        if(isset($data)) 
        {
            foreach ($root as $k => $v) 
            {
                if(isset($v[$leaf])) 
                {
                    $root[$k][$leaf] = level_tree($data, $v[$leaf], $r_field, $l_field, $leaf);
                }
            }
        }
        return $root;
    }
}

if (!function_exists('order_num')) {
    /**
     * 生成订单号
     * @param $main 主业务编号
     * @param $main_son 子业务编号
     * @return string  订单号
     */
    function order_num($main, $main_son)
    {
        $micro = substr(microtime(), 2, 4);
        return $main.bu0($main_son).substr(date('ymdHis'), 1).$micro;
    }
}

if (!function_exists('bu0')) {
    /**
     * 补0
     * @param $num 数字
     * @return string  
     */
    function bu0($num)
    {
        $num = intval($num);
        if($num<10 && $num>0){
            $num = '0'.$num;
        }
        return $num;
    }
}


if (!function_exists('_error_handler2')) {
    /**
     * Error Handler
     *
     * This is the custom error handler that is declared at the (relative)
     * top of CodeIgniter.php. The main reason we use this is to permit
     * PHP errors to be logged in our own log files since the user may
     * not have access to server logs. Since this function effectively
     * intercepts PHP errors, however, we also need to display errors
     * based on the current error_reporting level.
     * We do that with the use of a PHP error template.
     *
     * @param   int $severity
     * @param   string  $message
     * @param   string  $filepath
     * @param   int $line
     * @return  void
     */
    function _error_handler2($severity, $message, $filepath, $line)
    {
        //echo json_encode([$severity, $message, $filepath, $line]); exit;
        $record = function () use ($severity, $message, $filepath, $line) {
            $str = '错误文件：'.$filepath.'<br>';
            $str .= '错误信息：'.$message.'<br>';
            $str .= '错误位置：'.$line.'<br>';
            if (!is_cli()) {
                $str .= '_GET：'.http_build_query($_GET).'<br>';
                $str .= '_POST：'.http_build_query($_POST).'<br>';
                $str .= '_SERVER：'.http_build_query($_SERVER).'<br>';
                //$str .= '_HEADER：'.http_build_query(get_headers()).'<br>';
            }
            $ci = get_instance();
            $ci->load->model('MY_Model','core');
            $ci->core->redis_SELECT(5);
            $error = json_encode(['Body'=>$str, 
                                'Subject'=>'错误等级:'.$severity,
                                'FromName'=>'有错误信息']);
            $ci->core->redis_RPUSH('falsealarm',$error);
        };
        //$record();


        $is_error = (((E_ERROR | E_PARSE | E_COMPILE_ERROR | E_CORE_ERROR | E_USER_ERROR) & $severity) === $severity);

        // When an error occurred, set the status header to '500 Internal Server Error'
        // to indicate to the client something went wrong.
        // This can't be done within the $_error->show_php_error method because
        // it is only called when the display_errors flag is set (which isn't usually
        // the case in a production environment) or when errors are ignored because
        // they are above the error_reporting threshold.
        if ($is_error)
        {
            set_status_header(500);
        }

        // Should we ignore the error? We'll get the current error_reporting
        // level and add its bits with the severity bits to find out.
        if (($severity & error_reporting()) !== $severity)
        {
            return;
        }

        $_error =& load_class('Exceptions', 'core');
        $_error->log_exception($severity, $message, $filepath, $line);

        // Should we display the error?
        if (str_ireplace(array('off', 'none', 'no', 'false', 'null'), '', ini_get('display_errors')))
        {
            $_error->show_php_error($severity, $message, $filepath, $line);
        }

        // If the error is fatal, the execution of the script should be stopped because
        // errors can't be recovered from. Halting the script conforms with PHP's
        // default error handling. See http://www.php.net/manual/en/errorfunc.constants.php
        if ($is_error)
        {
            exit(1); // EXIT_ERROR
        }
    }

    /**
     * @param  $pwd sting 资金密码
     * @return string  加密后的密码
    */
    function bank_pwd_md5($pwd){
        //todo 8.12 资金密码
        return md5(substr($pwd, -11,18));
    }

    /**
     * 会员密码加密方式
     * @param   string   密码
     * @return	string   加密完成的密码
     */
    function user_md5($pwd){
        //todo 8.12 登录密码改动
        return md5(substr($pwd, -10,17));
    }

}

if (!function_exists('stript_float')) {
    /**
     * 去掉多余的小数点
     *
     * @param Array $data 数据
     * @param Integer $bit 保留几位
     * @return Array 格式化后的数据
     */
    function stript_float($data = [], $bit=3)
    {
        foreach ($data as $key => $value) {
            if (is_numeric($value) && strpos($value, '.') != false) {
                $data[$key] = (float)sprintf("%0.{$bit}f", $value);
            } elseif (is_array($value)) {
                $data[$key] = stript_float($value);
            }
        }
        return $data;
    }
}

if (!function_exists('uname_hide')) {
    /**
     * 隐藏用户名 用*替换
     *
     * @param string $username
     * @return string 隐藏后的用户名
     */
    function uname_hide($username)
    {
        if (!is_string($username)) {
            return $username;
        }
        if ( strlen($username) < 4 ) {
            return $username;
        } elseif ( strlen($username) === 4 ) {
            $str = $username{0} . $username{1} . "**";
        } elseif ( strlen($username) === 5 ) {
            $str = $username{0} . $username{1} . "***";
        } elseif ( strlen($username) === 6 ) {
            $str = $username{0} . $username{1} . "***" . $username{-1};
        } else {
            $str = $username{0} . $username{1} . "***" . $username{-1};
        }
        return $str;
    }
}


if (!function_exists('key_sort')) {
    /**
     *@desc 定义一个方法将数组进行排序
     *@param$arrays 要排序的数组
     *@param $sort_key要排序的键值
     *@param$sort_order 默认按降序牌
     *@param$sort_type默认按数字排列
     **/
    function   key_sort($arrays,$sort_key,$sort_order=SORT_DESC,$sort_type=SORT_NUMERIC)
    {

            if(is_array($arrays)){
                        foreach ($arrays as $array){
                            if(is_array($array)){
                                $key_arrays[] = $array[$sort_key];
                            }else{
                                return false;
                            }
                        }
            }else{
                        return false;
            }
            array_multisort($key_arrays,$sort_order,$sort_type,$arrays);
            return $arrays;
    }
}


if (!function_exists('get_dsn')) {
    /**
     *@desc 定义一个方法获取dsn
     *@param $key dsn的key
     *@param $suffix dsn文件后缀
     *@return String | NULL
     **/
    function get_dsn($key, $suffix = '_dsn.php')
    {
        if (defined('CONFIG_DSN') && defined('DSN_FILE_PATH')) {
            $file  = DSN_FILE_PATH . substr(trim($key),0,3) . $suffix;
            if (file_exists($file)) {
                include "$file";
                if (isset($config['dsn'][$key])) {
                    return $config['dsn'][$key];
                }
            }
        }
        //@wlog(APPPATH.'logs/dsn_file_'.date('Ym').'.log', $_SERVER["HTTP_HOST"].$_SERVER["REQUEST_URI"].':empty key:'.$key);
        $CI = &get_instance();
        if (empty($CI->_redis_public)) {
            $CI->load->driver('cache', array('adapter' => 'redis'));
            $CI->_redis_public = $CI->cache;
        }
        $CI->_redis_public->select(REDIS_PUBLIC);
        return $CI->_redis_public->hget('dsn', $key);
    }
}


