<?php if (!defined('BASEPATH')) {exit('No direct script access allowed');}


	/**
	 * 后台管理员密码加密方式
	 * @param   string   密码
	 * @return	string   加密完成的密码
	 */

	function admin_md5($pwd){
		//8.12 密码相关更改
		return md5(substr($pwd, -7,20));
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


     /**
	  * @param  array  要插入的数据
	  * @param  string 表明
	  * @return string 插入的sql语句
     */
    function  hInsert($arr,$tb){
		$str ="insert into $tb (";
		$va  = 'values ';
		$bool = true;
		foreach($arr as $k =>$v) {
			$va .= '(';
			foreach ($v as $key => $value) {
				if ($bool) {
					$str .= "$key,";
				}

				$va.= "$value,";
			}
			$va  = substr($va,0,-1);
			$va .= '), ';
			$bool = false;
		}
        return substr($str,0,-1).')'.substr($va,0,-2);

	}
    /**
	 * 线上入款数据重组
    */
    function online_z($data){
		$arr = [];
		$i=0;
		foreach ($data as $k => $v) {
			$temp = explode(',',$v['pay_code']);
			foreach ($temp as $kk => $vv) {
			    $arr[++$i]['id'] = $v['id'];
			    $arr[$i]['code']   = $vv;
			}
		}
		$temp =[];
		foreach ($arr as $k => $v) {
			$temp[]=$v['id']."-".$v['code'];
		}
		return $temp;
	}

    /**
	 * 判断查询区间2个月
	 * @param $start strinng 开始时间
	 * @param $end  str  结束时间
	 * @return   bool ture 在连个月内
    */
    function  limit_month($start,$end){
		return strtotime("+2 month",strtotime($start))>strtotime($end);
	}
/**
 * 金额中的元转为分
 * @param string $money 金额 默认是整数金额
 * @return true : false 正确返回处理后的以分单位的金额
 */
function dyuan_to_fen($money)
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
 * @param string
 * @return true : false 正确返回处理后的以元单位的金额
 */
function dfen_to_yuan($money)
{
    $string = 0;
    if(!empty($money) && is_numeric($money))
    {
        $string = round($money/100);
    }
    return $string;
}
/**
 *
 * @param $data array 待签名的数据
 * @param  data 数据
 * @param  $lk   连接符
 * @param  $lv   连接符
 * @return  $str string
 */
function arr_string($data,$lk='=',$lv='&')
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
 * 将数组的值取出 拼接成字符串
 * @param  data 数据
 * @param  t    连接符
 * @param $data
 */
function arr_value($data,$t=''){
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


