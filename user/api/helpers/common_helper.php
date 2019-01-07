<?php if (!defined('BASEPATH')) {exit('No direct script access allowed');}

    /**
	 * 去除小数点后面不用的0 保留非0数字
    */
	function del0($s)
	{
		$s = trim(strval($s));
		if (preg_match('#^-?\d+?\.0+$#', $s)) {
			return preg_replace('#^(-?\d+?)\.0+$#','$1',$s);
		}
		if (preg_match('#^-?\d+?\.[0-9]+?0+$#', $s)) {
			return preg_replace('#^(-?\d+\.[0-9]+?)0+$#','$1',$s);
		}
		return $s;
	}
	/**
	 * 将xml转为array
	 * @param string $xml
	 * @throws WxPayException
	 * @return  array;
	 */
    function FromXml($xml)
	{
		if(!$xml){
			return false;
		}
		//将XML转为array
		//禁止引用外部xml实体
		libxml_disable_entity_loader(true);
		$data = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
		return $data;
	}
    /**
	 * 将数组的键与值用&符号隔开
	 * @param $data array 待签名的数据
	 * @return  $str string
    */
    function ToUrlParams($data,$lk='=',$lv='&')
	{
		$string = '';
        if (is_array($data))
        {
            foreach($data as $key => $val)
            {
                if (!is_array($val) && ('sign' <> $key)
                  && ("" <> $val) && (null <> $val)
                  && ("null" <> $val))
                {
                    $string .= "{$key}{$lk}{$val}{$lv}";
                }
            }
            $string = trim($string, $lv);
            return $string;
        }
        return false;
	}

	/**
	 * 输出xml字符
	 * @throws WxPayException
	 **/
    function ToXml($data)
	{
		if (!is_array($data)) {
			return false;
		}
		$xml = "<xml>";
		foreach ($data as $key=>$val)
		{
			if (is_numeric($val)){
				$xml.="<".$key.">".$val."</".$key.">";
			}else{
				$xml.="<".$key."><![CDATA[".$val."]]></".$key.">";
			}
		}
		$xml.="</xml>";
		return $xml;
	}

/**
 * 第三方支付使用
*/
function my_json_encode($input){
	if(is_string($input)){
		$text = $input;
		$text = str_replace('\\', '\\\\', $text);
		$text = str_replace(
			array("\r", "\n", "\t", "\""),
			array('\r', '\n', '\t', '\\"'),
			$text);
		return '"' . $text . '"';
	}else if(is_array($input) || is_object($input)){
		$arr = array();
		$is_obj = is_object($input) || (array_keys($input) !== range(0, count($input) - 1));
		foreach($input as $k=>$v){
			if($is_obj){
				$arr[] = json_encode($k) . ':' . json_encode($v);
			}else{
				$arr[] = json_encode($v);
			}
		}
		if($is_obj){
			return '{' . join(',', $arr) . '}';
		}else{
			return '[' . join(',', $arr) . ']';
		}
	}else{
		return $input . '';
	}
}
/**
 * 第三方支付curlpost方式发送数据返回最原始的数据
 * @param $url  发送到的地址
 * @param $data 发送的数据
 * @param  $method 请求的方法
 * @param $pay_a   伪造的支付域名
 * @return  string 网站返回数据
*/
function pay_curl($url,$data,$method,$pay_a=null)
{
	$ch = curl_init();
	if (strtolower($method) === 'get') {
		$url .="?".http_build_query($data);
	}
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
	curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	if ($pay_a) {
		curl_setopt($ch, CURLOPT_REFERER, $pay_a);
	}
	//重定向使用
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
	if (strtolower($method) == 'post') {
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS,$data);

	}
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$tmpInfo = curl_exec($ch);
	if (curl_errno($ch)) {
		$error['code'] = E_ARGS;
		$error['msg']  = curl_error($ch);
		echo json_decode($error);die;
	}
	return $tmpInfo;
}
// 生成全球唯一标识码
if( ! function_exists('create_guid') ){
    // 生成全球唯一标识码
    function create_guid() {
        if (function_exists('com_create_guid')) {
            return strtolower(str_replace('-','',substr(com_create_guid(),1,-1)));
        } else {
            $chars = md5(uniqid(mt_rand(), true));
            $uuid  = substr($chars,0,8);
            $uuid .= substr($chars,8,4);
            $uuid .= substr($chars,12,4);
            $uuid .= substr($chars,16,4);
            $uuid .= substr($chars,20,12);
            return $uuid;
        }
    }
}
//sn转成数字 暂时只支持最后一位是数字 gc0
if( ! function_exists('sn_to_num'))
{
	function sn_to_num($sn){
		$sn = strtolower($sn);
		$len=strlen($sn);
		if(preg_match('/[0-9]/', substr($sn,0,$len-1))) return $sn;

		$array=array('a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z');
		
		$num = '';
		for($i=0;$i<$len;$i++){
			$index=array_search($sn[$i],$array);
			if($index === false) { //未找到说明是数字
				$num.= $sn[$i].'0';
			}else{
				$var=sprintf("%02d", $index+1);//生成2位数，不足前面补0
				$num.=$var;
			}
		}
		return $num;
	}
}

function data_to_string($data,$lk='=',$lv='&')
{
    $string = '';
    if (is_array($data))
    {
        foreach($data as $key => $val)
        {
            if (!is_array($val))
            {
                $string .= "{$key}{$lk}{$val}{$lv}";
            }
        }
        $string = trim($string, $lv);
        return $string;
    }
    return false;
}

/**
 * 提交数据到支付接口并返回处理后数据
 * @param string  $url 提交地址 
 * @param array $data 提交的参数数组 
 * @return array $result 正确返回处理后的$result 错误返回空数组
 */
function post_pay_data($url,$data,$t='',$s='UTF-8')
{
    //初始化请求地址
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_TIMEOUT,30);
    if (!empty($t)) 
    {
        //设置头部信息
        $header = array("Content-Type:application/{$t};charset={$s}");
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    }
    if (!empty($t) && is_array($t))
    {
        //设置自定义头部信息
        curl_setopt($ch, CURLOPT_HTTPHEADER, $t);
    }
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_DNS_USE_GLOBAL_CACHE, false);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}

/**
 * 转换接口返回数据格式和编码格式
 * @param array $string 异步返回参数 默认json格式 转换成数组
 * @param 备注：部分接口返回编码格式非 UTF-8 需要转换成 UTF-8 
 * @return array $data 正确返回处理后的$data数组 错误返回空数组
 */
function string_decoding($string)
{
    if (!empty($string) && !is_array($string)
       && (false === strpos($string,'<html>'))
       && (false !== strpos($string,'{')) 
       && (false !== strpos($string,'}')))
    {
        //判断是不是UTF-8编码
        $cart = array('UTF-8','GBK','GB2312','Unicode','LATIN1','BIG5','ASCII');
        $fc = mb_detect_encoding($string,$cart);
        if ('UTF-8' <> $fc)
        {
            $string = mb_convert_encoding($string,"UTF-8",$fc);
        } 
        $string = json_decode($string,true);
    }
    return $string;
}

/**
 * 获取支付签名 md5加密方式
 * @param array $data 参与签名的参数 
 * @param string $field 代表签名的数组字段 默认sign
 * @param string $method 加密方式 默认D 大写 X 小写
 * @param string $ks 由key值组成的加密字符串 默认'&key='.$key
 * @param string 备注：$ks 默认'&key='.$key 部分接口 '&'.$key
 * @return array : 含有签名参数的 data 数组
 */
function get_pay_sign($data,$ks,$fd='sign',$md='D')
{
	if (!empty($data) && is_array($data) && !empty($ks))
	{
		ksort($data);
		//把数组参数以key=value形式拼接最后加上$ks值
    	$string = ToUrlParams($data) . $ks;
    	//拼接字符串进行MD5大写加密
    	$sign = md5($string);
    	$sign = ('D' == $md) ? strtoupper($sign) : $sign;
    	$data[$fd] = $sign;
	}
	return $data;
}

/**
 * 获取支付签名 md5加密方式
 * @param array $data 参与签名的参数 
 * @param string $p_key 参与签名的用户私钥
 * @param string $field 代表签名的数组字段 默认sign
 * @param string $sign_type 代表签名方式的数组字段 默认sign_type
 * @return array : 含有签名参数的 data 数组
 */
function get_open_sign($data,$pk,$fd='sign',$st='sign_type')
{
    if (!empty($data) && is_array($data) && !empty($pk)) 
    {
        ksort($data);
        //把数组参数以key=value形式拼接
        $string = ToUrlParams($data);
        $pk = openssl_get_privatekey($pk);
        openssl_sign($string, $sign_info, $pk, OPENSSL_ALGO_MD5);
        $data[$st] = 'RSA-S'; //签名方式
        $data[$fd] = base64_encode($sign_info);
    }
    return $data;
}

/**
 * 验证数据接口返回的签名是否正确
 * @param array $data 异步返回参数 包括sgin参数
 * @param string $field 代表签名的数组字段 默认sign
 * @param string $method 加密方式 默认D 大写 X 小写
 * @param string $ks 由key值组成的加密字符串 默认'&key='.$key
 * @param string 备注：$ks 默认'&key='.$key 部分接口 '&'.$key
 * @return true : false 正确返回true 错误返回false
 */
function verify_pay_sign($data,$ks,$fd='sign',$md='D')
{
	if(!empty($data) && is_array($data)
    && !empty($fd) && !empty($ks))
    {
    	//取出数组中签名sign项 然后去除签名sign项
    	$sign = strtoupper($data[$fd]);
    	unset($data[$fd]);
    	ksort($data);
    	//把数组参数以key=value形式拼接最后加上$ks值
    	$string = ToUrlParams($data) . $ks;
    	//拼接字符串进行MD5大写加密
    	$v_sign = strtoupper(md5($string));
    	//比较返回参数中 sign 和 本地加密的 $v_sign 是否一致
        return ($sign == $v_sign) ? 1 : 0;
    }
    return 0;
}

/**
 * 获取异步返回参数 验证签名、订单号、金额等必要参数是否存在
 * @param string $name 支付的名称 一般是首字母大写 如‘聚合付：JHF’ 
 * @param string $sf 返回的支付参数中 代表签名的字段名 默认是'sign'
 * @param string $of 返回的支付参数中 代表订单号的字段名 默认是'trade_no'
 * @param string $mf 返回的支付参数中 代表金额的字段名 默认是'moeny'
 * @param string $tf 返回的支付参数中 代表支付状态的字段名 默认是'status'
 * @return array $data 返回支付参数数组
 */
function get_return_data($name, $sf, $of, $mf, $tf='')
{
    header("Content-Type:text/html;charset=UTF-8");
    $data = [];
    //加载支付Online_model类 记录支付相关错误信息
    $m = &get_instance();
    $m->load->model('pay/Online_model','PM');
    //redis记录 异步接口返回的数据信息
    //文件流形式
    if (!empty(file_get_contents("php://input")))
    {
        $put = file_get_contents("php://input"); 
        //如果是json形式数据 转化成数组
        if (is_string($put) && (false !== strpos($put,'{')) 
            && (false !== strpos($put,'}')))
        {
            $m->PM->online_erro("{$name}_PUT_json", '数据:' . $put);
            $data = string_decoding($put);
        }
        //如果是以key=value形式数据 转化成数组
        if (is_string($put) && (false !== strpos($put,'&')) 
            && (false !== strpos($put,'=')))
        {
            $m->PM->online_erro("{$name}_PUT_put", '数据:' . $put);
            $data = urldecode($put);//解码url
            parse_str($data,$data);//转换成数组
        }
        //如果是xml格式数据 转化成数组
        if (is_string($put) && (false !== strpos($put,'xml')))
        {
            $m->PM->online_erro("{$name}_PUT_xml", '数据:' . $put);
            $data = FromXml($put);
        }
    }
    //GET,POST方式
    if (!empty($_REQUEST) && empty($data))
    {
        //如果是数组 转化成json记录数据库
        if(is_array($_REQUEST))
        {
            //数组转化成json 录入数据
            $temp = json_encode($_REQUEST,JSON_UNESCAPED_UNICODE);
            $m->PM->online_erro("{$name}_REQUEST_array", '数据:' . $temp);
            unset($temp);
            $data = $_REQUEST;
        }
        //如果json格式 记录数据 同时转化成数组 
        if (is_string($_REQUEST) && (false !== strpos($_REQUEST,'{')) 
            && (false !== strpos($_REQUEST,'}')))
        {
            $m->PM->online_erro("{$name}_REQUEST_json", '数据:' . $_REQUEST);
            //json格式数据先进行转码
            $data = string_decoding($_REQUEST);
        }
    }
    //判断是否获取到数据
    if (empty($data))
    {
        $msg = "三种方式都没获取到任何数据";
        $m->PM->online_erro("{$name}_MUST", $msg);
        exit('ERROR');
    }
    //判断是否含有必要参数（签名参数/订单号参数/订单金额参数）
    if (empty($data[$sf]) || empty($data[$of]) || empty($data[$mf])) 
    {
        $msg = "缺少必要参数：{$sf}、{$of}、{$mf}";
        $m->PM->online_erro("{$name}_MUST", $msg);
        exit('ERROR');
    }
    return $data;
}

/**
 * 金额中的元转为分 
 * @param string $money 金额 默认有2位小数点 如10.24
 * @param string 备注 特殊数字处理精度不够 
 * @param string 备注 $money =10.12 直接*100 为1011.99999999999
 * @return true : false 正确返回处理后的以分单位的金额
 */
function yuan_to_fen($money)
{
	$string = 0;
	if(!empty($money) && is_numeric($money))
	{
		$string = intval(round($money*100));
	} 
	return $string;
}

/**
 * 金额中的分转为元
 * @param string $money 金额 默认是整数金额
 * @param string 备注 特殊数字处理精度不够 
 * @param string 备注 $money =10.12 直接*100 为1011.99999999999
 * @return true : false 正确返回处理后的以元单位的金额
 */
function fen_to_yuan($money)
{
    $string = 0;
    if(!empty($money) && is_numeric($money))
    {
        $string = sprintf("%.3f", ($money/100));
    } 
    return $string;
}

/**
 * HmacMd5方法加密
 * @param $data 需要签名得参数
 * @param $key 签名密钥
 *
 * @return string
 */
function HmacMd5($data,$key)
{
// RFC 2104 HMAC implementation for php.
// Creates an md5 HMAC.
// Eliminates the need to install mhash to compute a HMAC
// Hacked by Lance Rushing(NOTE: Hacked means written)

//需要配置环境支持iconv，否则中文参数不能正常处理
    $key = iconv("GB2312","UTF-8",$key);
    $data = iconv("GB2312","UTF-8",$data);

    $b = 64; // byte length for md5
    if (strlen($key) > $b) {
        $key = pack("H*",md5($key));
    }
    $key = str_pad($key, $b, chr(0x00));
    $ipad = str_pad('', $b, chr(0x36));
    $opad = str_pad('', $b, chr(0x5c));
    $k_ipad = $key ^ $ipad ;
    $k_opad = $key ^ $opad;

    return md5($k_opad . pack("H*",md5($k_ipad . $data)));
}

/**
 * 将数组的值取出 拼接成字符串
 * @param  data 数据
 * @param  t    连接符
 * @param $data
 */
function data_value($data,$t=''){
    $string = '';
    if(is_array($data)){
        foreach ($data as $key=>$val){
            if(!is_array($val)){
              $string .= $val.$t;
            }
        }
    }
    $string = trim($string,$t);
    return $string;
}

/**
 * 对RSA加密格式公钥和私钥进行 64位一换行的对称处理
 * @param $publicKey 原始加密公钥
 * @param $privateKey 原始加密私钥
 *
 * @return mixed
 */
 function loadPubPriKey($publicKey='',$privateKey='') {

    $publicKey = str_replace([
        '-----BEGIN RSA PUBLIC KEY-----',
        '-----BEGIN PUBLIC KEY-----',
        '-----END RSA PUBLIC KEY-----',
        '-----END PUBLIC KEY-----'], '', $publicKey);
    $privateKey = str_replace([
        '-----BEGIN RSA PRIVATE KEY-----',
        '-----BEGIN PRIVATE KEY-----',
        '-----END RSA PRIVATE KEY-----',
        '-----END PRIVATE KEY-----'], '', $privateKey);
    $publicKey = trim($publicKey);
    $publicKey = wordwrap($publicKey, 64, "\n", true);
    $privateKey = trim($privateKey);
    $privateKey = wordwrap($privateKey, 64, "\n", true);
    $publicKey = "-----BEGIN PUBLIC KEY-----\n" . $publicKey . "\n-----END PUBLIC KEY-----";
    $privateKey = "-----BEGIN PRIVATE KEY-----\n" . $privateKey . "\n-----END PRIVATE KEY-----";

    $load['publicKey'] = $publicKey;
    $load['privateKey'] = $privateKey;
     return $load;

}
