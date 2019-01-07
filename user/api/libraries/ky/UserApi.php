<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

include_once APPPATH . 'libraries/'.'BaseApi.php';
class UserApi extends BaseApi
{
    protected static $ky_agentname;
    protected static $ky_deskey;
    protected static $ky_md5_key;

    /**
     * 加密
     * @str  字符串
     */
    public function encrypt_ky($str,$password,$method = 'aes-128-ecb') {
        return openssl_encrypt($str,$method,$password);
    }


    /**
     * 获取当前时间的毫秒
     */
    public function getMillisecond() {
        list($t1, $t2) = explode(' ', microtime());
        return $t2 .  ceil( ($t1 * 1000) );
    }

    /**
     *  获得加密后的url
     * @param $parm  需要加密字符串
     */
    public function  get_ky_url($param,$type=1){
        $timestamp = str_pad($this->getMillisecond(),13,0);//时间戳
        $param = urlencode($this->encrypt_ky($param,self::$ky_deskey));//参数加密字符串
        $key = md5 (self::$ky_agentname.$timestamp.self::$ky_md5_key);//MD5校验字符串
        if($type==1){
            return  KYQP_API_URL.'?agent='.self::$ky_agentname.'&timestamp='.$timestamp.'&param='.$param.'&key='.$key;
        }else{
            return  KYQP_ORDER_URL.'?agent='.self::$ky_agentname.'&timestamp='.$timestamp.'&param='.$param.'&key='.$key;
        }
    }

    

    /**
     *  获得调用api的返回参数
     * @param $parm  需要加密字符串
     */
    public function  get_api_data($data,$type = 1,$sn){
        if(!self::$ci) self::$ci = & get_instance();
        self::$ci->load->model( 'sx/set_model', 'set' );
        $sn_data = self::$ci->set->get_ky_key( $sn );
        self::$ky_agentname=$sn_data['ky_agentname'];
        self::$ky_deskey=$sn_data['ky_deskey'];
        self::$ky_md5_key=$sn_data['ky_md5_key'];
        $request_url = '';
        foreach ( $data as $key => $val )
        {
            $request_url .= $key . '=' . $val . '&';
        }
        $request_url = rtrim( $request_url, '&' );
        $url = $this->get_ky_url($request_url,$type);
        $res = BaseApi::curl_get( $url );
        wlog(APPPATH.'logs/ky/'.date('Y_m_d').'.log','send_ky '. $url .' result: '.$res);
        return json_decode( $res, true );
    }

    /**
     *  获得订单的id
     * @param $parm  需要加密字符串
     */
    public function getOrderId($sn){
        if(!self::$ci) self::$ci = & get_instance();
        self::$ci->load->model( 'sx/set_model', 'set' );
        $sn_data = self::$ci->set->get_ky_key( $sn );
        self::$ky_agentname=$sn_data['ky_agentname'];
        self::$ky_deskey=$sn_data['ky_deskey'];
        self::$ky_md5_key=$sn_data['ky_md5_key'];
        list($usec, $sec) = explode(" ", microtime());
        $msec=round($usec*1000);
        return self::$ky_agentname.date("YmdHis").$msec;
    }

}