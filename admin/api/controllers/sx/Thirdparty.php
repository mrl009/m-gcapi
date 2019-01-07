<?php
/**
 * @模块   视讯第三方
 * @版本   Version 1.0.0
 * @日期   2017-09-11
 * super
 */
defined('BASEPATH') or exit('No direct script access allowed');
class Thirdparty extends MY_Controller{
    protected static $ky_agentname;
    protected static $ky_deskey;
    protected static $ky_md5_key;
    public function __construct()
    {
        parent::__construct();
        $this->load->model('sx/Shixun_model', 'sx');
        $this->sort_sn = $this->get_sn();
        $sn_data=$this->sx->get_sx_set(array('sn'=>$this->get_sn()));
        self::$ky_agentname=$sn_data['ky_agentname'];
        self::$ky_deskey=$sn_data['ky_deskey'];
        self::$ky_md5_key=$sn_data['ky_md5_key'];
    }
    public function get_user_sx_credit()
    {
        $sx_set=$this->sx->get_sx_set(array('sn'=>$this->get_sn()));
        return $sx_set;
    }
    /****************************开元棋牌start********************************/
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
     * 获取当前时间的毫秒
     */
    public function getMillisecond() {
        list($t1, $t2) = explode(' ', microtime());
        return $t2 .  ceil( ($t1 * 1000) );
    }
    /**
     * 加密
     * @str  字符串
     */
    public function encrypt_ky($str,$password,$method = 'aes-128-ecb') {
        return openssl_encrypt($str,$method,$password);
    }
    /**
     *  获得调用api的返回参数
     * @param $parm  需要加密字符串
     */
    public function  get_api_data($data,$type = 1){
        $request_url = '';
        foreach ( $data as $key => $val )
        {
            $request_url .= $key . '=' . $val . '&';
        }
        $request_url = rtrim( $request_url, '&' );
        $url = $this->get_ky_url($request_url,$type);
        $res = $this->curl_get( $url );
        return json_decode( $res, true );
    }
    /****************************开元棋牌end*********************************/

    /******************************公共方法start******************************************/
    /**
     * GET请求
     * @param string $url 请求地址
     * @param string $data 请求参数
     * @return string
     */
    protected static function curl_get( $url, $time_out = 30 )
    {
        $ch = curl_init( $url );
        curl_setopt( $ch, CURLOPT_HEADER, 0 );
        curl_setopt( $ch, CURLOPT_USERAGENT,"Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/37.0.2062.120 Safari/537.36" );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 1 );
        curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false ); #不验证证书
        curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, false ); #不验证证书
        #只需要设置一个秒的数量就可以
        curl_setopt( $ch, CURLOPT_TIMEOUT, $time_out );
        $data = curl_exec( $ch );
        curl_close( $ch );
        return $data;
    }
    /**
     * curl_post请求
     * @param $url
     * @param $data
     * @param int $time_out
     * @return mixed
     */
    protected static function curl_post( $url, $data, $method = 'POST', $time_out = 30 )
    {
        $ch = curl_init();
        curl_setopt( $ch, CURLOPT_URL, $url );
        curl_setopt( $ch, CURLOPT_POST, 1 );
        curl_setopt( $ch, CURLOPT_POSTFIELDS, $data );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false ); #不验证证书
        curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, false ); #不验证证书
        curl_setopt( $ch, CURLOPT_TIMEOUT, $time_out );
        if( self::$platform_name == 'pt' )
        {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            curl_setopt( $ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json; charset=utf-8',
                'Content-Length: ' . strlen( $data ),
                'MERCHANTNAME:' . MERCHANTNAME,
                'merchantcode:' . MERCHANTCODE,
            ]);
        }
        elseif( self::$platform_name == 'mg' )
        {
            curl_setopt( $ch, CURLOPT_HTTPHEADER, [
                    'Content-Type: text/xml; charset=utf-8',
                    'Content-Length: ' . strlen( $data ),
                ]
            );
        }
        elseif(self::$platform_name == 'lebo'){
            curl_setopt( $ch, CURLOPT_HTTPHEADER, [
                ]
            );
        }else{
            curl_setopt( $ch, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/json',
                    'Content-Length: ' . strlen( $data ),
                ]
            );
        }

        $response = curl_exec( $ch );
        curl_close( $ch );
        return $response;
    }
    /*******************************公共方法end*******************************************/
}