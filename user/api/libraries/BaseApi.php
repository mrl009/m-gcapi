<?php

class BaseApi
{
	protected static $instances;
	/**
	 * @var token
	 */
	protected static $token;
	protected static $ci = null;
	protected static $sn;

	/**
	 * ag配置项
	 * @var string
	 */
	const AG_DEFAULT_CAGENT = 'M40_AGIN';
	const AG_DEFAULT_MD5_KEY = '5kbNeSwf3jud';
	const AG_DEFAULT_DES_KEY = 'E7LA8lXh';
	const AG_DEFAULT_URL = 'http://gi.apikk.org:81'; //备用域名 http://gi.kk89a.com:81
	const AG_GCI_URL = 'http://gci.apikk.org:81';    //备用域名 http://gci.kk89a.com:81
	protected static $is_gci = false;
    /**
     * dg配置项
     * @var string
     */
    const DG_API_KEY='9b741ee32aa0441385cbae1bbf31530a';
	/**
	 * pt配置项
	 */
	const PT_API_URL = 'http://ws-keryx2.imapi.net';
	const MERCHANTNAME = '818gamingprod';
	const MERCHANTCODE = 'MCSRaQ1uYdx07kTdPNHrRwyEYlKu8Fky';
    const LEBO_KEY='8029e4ea973f7931567959e2350ba1648fe74a4b';

	/**
	 * @var 平台名称
	 */
	protected static $platform_name;

	/**
	 * @var string 接口地址
	 */
	protected static $interface_url = [
		//'dg' => 'https://api.dg99web.com/',
		'dg' => 'http://api.dg99api.com/',
		//'lebo' => 'https://api014.leboapi.org/AServer/server.php'
        //'lebo' => 'https://apiag.jugaming.com/AServer/server.php',
        'lebo'=> 'https://lgtestapi.lgapi.co',
        'mg3'  => 'https://entservices204.totalegame.net?wsdl',
        'mg5'  => 'https://tegapi204.totalegame.net'
	];

	/**
	 * token username & token_key
	 * @var string
	 */
	protected static $token_username = '';
	protected static $token_key = '';
	/**
	 * @var bool是否校验token
	 */
	protected static $verify_token = true;

	/**
	 * 单例模式
	 *
	 * @param $platform_name 平台名称
	 * @param $class_name 类名称
	 * @param string $sn 站点id
	 * @param array $params 参数
	 * @return api_object
	 */
    public static function getinstance( $platform_name, $class_name, $sn = '', $params = [] )
    {
        $create_toeken = in_array( $platform_name, [ 'dg' ] ) ? true : false;
        self::$platform_name = $platform_name;
        static $instance;
        self::$sn = $sn;
        if(!self::$ci) self::$ci = & get_instance();
        if( $create_toeken )
        {
            self::build_token_dg( $sn );
        }
        if( ! isset($instance[ $platform_name . $class_name ]) )
        {
            $file_path = APPPATH . 'libraries/' . $platform_name . '/' . ucfirst($class_name) . 'Api.php';
            if( !file_exists( $file_path ) )
            {
                exit( $file_path . '不存在' );
            }

            require_once $file_path;
            $class_name .= 'Api';
            $instance[ $platform_name . $class_name ] = new $class_name ( $params );
        }

        return $instance[ $platform_name . $class_name ];
    }

	/**
	 * 生成token_dg
	 */
	protected static function build_token_dg( $sn )
	{
		self::get_sn_key( $sn );
//		self::$token = md5( self::$token_username . self::DG_API_KEY .'aaa');
		self::$token = md5( self::$token_username . self::$token_key );
	}

	/**
	 * 生成token_lebo
	 */
	protected static function build_token_lebo( array $params )
	{
		$key = '';
		foreach ( $params as $val )
		{
			$key .= $val.'|';
		}
		//var_dump(rtrim($key,'|'));exit();
		return sha1(rtrim($key,'|'));
	}
    protected static function get_lebo_security(){
        self::$ci->load->model( 'MY_Model', 'core' );
        $lebo_security=self::$ci->core->get_sx_set('lebo_security');
        if(!$lebo_security){
            $addr = INTERFACE_URL[ self::$platform_name ].'/getKey';
            $data['token']=sha1(self::$token_username);
            $data['agent']=self::$token_username;
            $json=self::curl_post($addr,$data);
            $data=json_decode($json,true);
            if($data['code']===0&&$data['text']=='Success'){
                $lebo_security=$data['result']['security_key'];
                $expiration=strtotime($data['result']['expiration'])-time();
                $rs=self::$ci->core->update_sx_set('lebo_security',$lebo_security,$expiration);
            }
        }
        return $lebo_security;
    }
	/**
	 * 获取账户秘钥
	 * @param $sn
	 */
	protected static function get_sn_key( $sn )
	{
		self::$ci->load->model( 'sx/set_model', 'set' );
		$data = self::$ci->set->get_sn_key( self::$platform_name, $sn );
		self::$token_username = $data[ self::$platform_name . '_agentname' ];
		self::$token_key = $data[ self::$platform_name . '_agentpwd' ];
		return true;
	}

	/**
	 * 加载类库方法
	 * @param $class_name
	 */
	protected static function load( $platform_name, $class_name, array $params = [] )
	{
		//if( isset( self::$instances[ $class_name ] ) ) return self::$instances[ $class_name ];
		$file_path = APPPATH . 'libraries/' . $platform_name . '/' . ucfirst($class_name) . 'Api.php';
		if( !file_exists( $file_path ) )
		{
			exit( $file_path . '不存在' );
		}

		require_once $file_path;
		$class_name .= 'Api';
		return self::$instances[ $platform_name . $class_name ] = new $class_name ( $params );
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
			/*$header = array('CLIENT-IP:58.60.0.222','X-FORWARDED-FOR:58.60.0.255');
			curl_setopt($ch, CURLOPT_HTTPHEADER, $header);*/
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
		if( self::$platform_name == 'pt' )
		{
			curl_setopt( $ch, CURLOPT_HTTPHEADER, [
					'MERCHANTNAME:' . MERCHANTNAME,
					'merchantcode:' . MERCHANTCODE
				]
			);
		}
		#只需要设置一个秒的数量就可以
		curl_setopt( $ch, CURLOPT_TIMEOUT, $time_out );
		$data = curl_exec( $ch );
		curl_close( $ch );
		return $data;
	}

	protected static function get_class_name()
	{
		return str_replace( 'api', '', strtolower( get_called_class() ) );
	}

	/**
	 * 发送数据
	 * @param array $data
	 * @return bool|mixed
	 */
	protected static function send( array $data )
	{
		$method = 'send_' . self::$platform_name;
		return self::$method( $data );
	}

	/**
	 * 校验token
	 * @param $token
	 * @return bool
	 */
	protected static function verify_token( $token )
	{
		if( $token == self::$token )
		{
			return true;
		}

		return false;
	}

	/**
	 * @param $input  加密数据
	 * @param $method 加密方式
	 */
	protected static function encrypt( $input, $method = 'DES-ECB' )
	{
		return openssl_encrypt( $input, $method, AG_DEFAULT_DES_KEY );
	}

	/**
	 * 获取key
	 * @param $input
	 * @param $key
	 * @return string
	 */
	protected static function encrypt_md5_key( $input )
	{
		return strtolower( md5( $input . AG_DEFAULT_MD5_KEY ) );
	}

	/**
	 * @param $input  解密数据
	 * @param $method 解密方式
	 * @return string
	 */
	protected static function decrypt( $input, $method = 'DES-ECB' )
	{
		return openssl_decrypt( $input, $method, AG_DEFAULT_DES_KEY );
	}

	#构建表单
	protected static function build_form( $action )
	{

		return '<form style="display:none;" id="form1" name="form1" method="post" action=" ' . $action . ' "><script type="text/javascript">function load_submit(){document.form1.submit()}load_submit();</script>';
	}

	#将XML转为array
	protected static function xml_to_array( $xml )
	{
		#禁止引用外部xml实体
		libxml_disable_entity_loader( true );
		$values = json_decode( json_encode( simplexml_load_string( $xml, 'SimpleXMLElement', LIBXML_NOCDATA ) ), true );
		return $values;
	}

	/**
	 * ag平台发送数据
	 * @param $data 发送参数
	 * @return mixed
	 */
	protected static function send_ag( $data )
	{
                wlog(APPPATH.'logs/ag/'.date('Y_m_d').'.log',__FUNCTION__. ' args: '.json_encode($data,320));


		$data_des = 'cagent=' . AG_DEFAULT_CAGENT . '/';
		foreach ( $data as $key => $value )
		{
			$data_des .= '\\\\\\\\/' . $key . '=' . $value . '/';
		}

		$data_des = trim( $data_des, '/' );
		$params = self::encrypt( $data_des );
		$key = self::encrypt_md5_key( $params );
		if( self::$is_gci )
		{
			$url = AG_GCI_URL . '/forwardGame.do?params=' . $params . '&key=' . $key;
			$res = self::build_form( $url );
			wlog(APPPATH.'logs/ag/'.date('Y_m_d').'.log','send_ag form url: '. $url);
		}
		else
		{

			$url = AG_DEFAULT_URL . '/doBusiness.do?params=' . $params . '&key=' . $key;

			$res = current( self::xml_to_array( self::curl_get( $url ) ) );
			wlog(APPPATH.'logs/ag/'.date('Y_m_d').'.log','send_ag GET '.$url. ' result: '.json_encode($res,320));
		}

		return $res;
	}

	/**
	 * dg平台发送数据
	 * @param $data 发送参数
	 * @return mixed
	 */
	protected static function send_dg( $data )
	{
		$backtrace = debug_backtrace();
		array_shift( $backtrace );
		//$class_name = self::get_class_name();
		$class_name = self::get_class_name() == 'dguser' ? 'user' : self::get_class_name();
		$method_name = $backtrace[ 2 ][ 'function' ];
		$data = array_merge( [ 'token' => self::$token ], $data );
//		$data=array_merge(['random'=>'aaa'],$data);
		if( isset( $data[ 'method' ] ) )
		{
			$method_name = $data[ 'method' ];
			unset( $data[ 'method' ] );
		}
		$data = json_encode( $data );
		$addr = INTERFACE_URL[ self::$platform_name ] . $class_name . '/'. $method_name . '/'.self::$token_username;
		$res = json_decode( self::curl_post( $addr, $data ), true );
		wlog(APPPATH.'logs/dg/'.date('Y_m_d').'.log','send_dg post '.$addr.' '.$data.' result:' . json_encode($res,320));
		if( isset( $res[ 'token' ] ) )
		{
			if( self::$verify_token )
			{
				if( !self::verify_token( $res[ 'token' ] ))
				{
					wlog(APPPATH.'logs/dg/'.date('Y_m_d').'.log','res_token: '.$res[ 'token' ].' self_token: '.self::$token );
					return false;
				}
			}
		}
		return $res;
	}

	/**
	 * lebo平台发送数据
	 * @param $data 发送参数
	 * @return mixed
	 */
	protected static function send_lebo( $data )
	{
		self::get_sn_key( self::$sn );
		if($data[ 'method' ] == 'GetBetData'){
            $timezone=date_default_timezone_get();
            date_default_timezone_set("America/New_York");
            $data['start_date']=isset($data['start_date'])?$data['start_date']:date('Y-m-d H:i:s',time()-$data['interval']*60);
            $data['end_date']=isset($data['end_date'])?$data['end_date']:date('Y-m-d H:i:s',time());
            date_default_timezone_set( $timezone);
        }
		switch( $data[ 'method' ] )
		{
			case 'UserLogin' :
			    if(empty($data['act_type']))
			    {
			        $token = self::build_token_lebo( [ trim(self::get_lebo_security(),'"'),$data[ 'username' ], self::$token_username, $data['GameType']]);
			    }else{
                    $token = self::build_token_lebo( [ trim(self::get_lebo_security(),'"'),$data['act_type'],self::$token_username,$data['GameType']]);
                }
				$data[ 'token' ] = $token;
			    $uri='authorization';
				break;
            case 'UserDetail':
                $token=self::build_token_lebo([trim(self::get_lebo_security(),'"'),$data['username'],self::$token_username]);
                $data['token']=$token;
                $uri='user';
                break;
			case 'Deposit' :
                $token=self::build_token_lebo([ trim(self::get_lebo_security(),'"'),$data['username'],$data['amount'],self::$token_username]);
                $data['token']=$token;
                $uri='deposit';
                break;
			case 'WithDrawal' :
				$token = self::build_token_lebo(  [trim(self::get_lebo_security(),'"'),$data['username'],$data['amount'],self::$token_username]);
				$uri='withDrawal';
				$data[ 'token' ] = $token;
				break;
            case 'TransferLog':
                $token=self::build_token_lebo([trim(self::get_lebo_security(),'"'),$data['serial'],self::$token_username]);
                $data['token']=$token;
                $uri='transferLog';
                break;
            case 'GetBetData':
                $token=self::build_token_lebo([trim(self::get_lebo_security(),'"'),$data['start_date'],$data['end_date'],self::$token_username]);
               $data['token']=$token;
               $uri='getDateList';
            default:
                break;
		}
		$data['agent']=self::$token_username;
		if($data[ 'method' ]=='UserLogin'){
            $data['login_type']=1;
        }
        unset($data['method']);
        $result = self::curl_post(INTERFACE_URL[ self::$platform_name ].'/'.$uri,$data);
        $result=json_decode($result,true);
		if($result['code']===0)
		{
            $result = [ 'code' => 0, 'result' => $result['result'],'text'=>$result['text']];
		}
		else
		{
			$result = [ 'code' => -10, 'text' => $result ];
		}

		return $result;
	}

	/**
	 * pt平台发送数据
	 * @param $data 发送参数
	 * @return mixed
	 */
	protected static function send_pt( $data )
	{
		list( $request_way, $method ) = explode( ':', $data[ 'method' ] );
		unset( $data[ 'method' ] );
		if( $request_way == 'post' || $request_way == 'put' )
		{
			$request_url = PT_API_URL . '/' . $method;
			$data = json_encode( $data );
			$res = self::curl_post( $request_url, $data, $request_way );
			wlog(APPPATH.'logs/pt/'.date('Y_m_d').'.log','send_pt '.strtoupper($request_way).' '. $request_url .'  data: '. $data .'  result: '.$res );
		}
		else
		{
			$request_url = PT_API_URL . '/' . $method . '/';
			foreach ( $data as $key => $val )
			{
				$request_url .= $key . '/' . $val . '/';
			}

			$request_url = rtrim( $request_url, '/' );
			$res = self::curl_get( $request_url );
			wlog(APPPATH.'logs/pt/'.date('Y_m_d').'.log','send_pt '.strtoupper($request_way).' '. $request_url .' result: '.$res);
		}
		return json_decode( $res, true );
	}
	/**
	 * mg平台发送数据
	 * @param $data 发送参数
	 * @return mixed
	 */
	protected static function send_mg( $data )
	{
		if($data['type'] === 3) { // 项目三中的API#
			wlog(APPPATH.'logs/mg/'.date('Y_m_d').'.log','send_mg pass by API#3');
			$sessionGUID = self::get_session_guid();
			if( isset($sessionGUID) && $sessionGUID )
			{
				$url = INTERFACE_URL['mg3'];
				$client = new SoapClient($url, array('compression' => SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP));
				//$this->load->library('session');
				$xml = '
	            <AgentSession xmlns="https://entservices.totalegame.net">
	                <SessionGUID>' . $sessionGUID . '</SessionGUID>
	                <IPAddress>' . $_SERVER['REMOTE_ADDR'] . '</IPAddress>
	            </AgentSession>
	        	';
	        	$xmlvar = new SoapVar($xml, XSD_ANYXML);
	        	$header = new SoapHeader('https://entservices.totalegame.net', 'AgentSession', $xmlvar);
	        	$client->__setSoapHeaders($header);
	        	
	        	$method = $data['method']; unset($data['method'],$data['type']);
	        	wlog(APPPATH.'logs/mg/'.date('Y_m_d').'.log','MG API#3 '.$method.' data : '. json_encode($data,320));

				try {
					$result = $client->$method( $data );
	    		} 
	    		catch (Exception $e) {    			
	    			die('method: ' . $method . ' failed: '. $e->getMessage() );

		    	}
		    	/*ob_start();
		    	$tmp = ob_get_contents();
		    	ob_end_clean();*/
		    	wlog(APPPATH.'logs/mg/'.date('Y_m_d').'.log','API#3 '.$method. ' return: '.json_encode($result,320));
		    		    	
			}else {
				wlog(APPPATH.'logs/mg/'.date('Y_m_d').'.log','get_session_guid failed !');
			}
			return json_decode( json_encode($result), true );
		} else { // 项目五API
			wlog(APPPATH.'logs/mg/'.date('Y_m_d').'.log','send_mg pass by API#5');
			/*self::$ci->load->config( 'global' );
			$mg = self::$ci->config->item( 'mg' );*/
            self::$ci->load->model( 'sx/set_model', 'set' );
            $mg = self::$ci->set->get_sn_key( self::$platform_name, self::$sn );
			$url = INTERFACE_URL['mg5'].'/'.$data['method'];
			
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			unset($data['method'], $data['type']);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array(
				'Content-Type: application/json',
                //'Authorization: Basic '. base64_encode($mg['agentname'].":".$mg['agentpwd']),
                'Content-Length: ' . strlen( json_encode($data) )

			));
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
			curl_setopt($ch, CURLOPT_USERPWD, $mg['mg_agentname'].':'.$mg['mg_agentpwd']);
			curl_setopt($ch, CURLOPT_TIMEOUT, 30);
			curl_setopt($ch, CURLOPT_POST , TRUE);
			
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
			$result = curl_exec($ch);
			wlog(APPPATH.'logs/mg/'.date('Y_m_d').'.log','MG API#5 '.$url.' post: '.json_encode($data,320).' result: ' . $result );
			curl_close($ch);
			return json_decode($result,true);
		}	

	}
	/*
	 * 得到游戏商认证session
	 * mg有项目3和项目5,见文档 接口url分别有两个
	 */
	private static function get_session_guid()
	{
        self::$ci->load->model( 'MY_Model', 'core' );
		$url = INTERFACE_URL['mg3'];
        self::$ci->load->model( 'sx/set_model', 'set' );
        $session_guid=self::$ci->core->get_sx_set('mg_guid');
        //var_dump($session_guid);exit();
         $rs=self::$ci->core->update_sx_set('mg_guid',trim($session_guid,'"'),3600);
         $session_guid=trim($session_guid,'"') ;
         //var_dump($session_guid);exit();
        if($session_guid)
        {
            return trim($session_guid,'"') ;
        }else{
            $mg = self::$ci->set->get_sn_key( self::$platform_name, self::$sn );
            $client = new SoapClient($url, array('compression' => SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP));
            try {
                $result = $client->IsAuthenticate(
                    array(
                        'loginName' => $mg['mg_agentname'],
                        'pinCode'   => $mg['mg_agentpwd']
                    )
                );
            }
            catch (Exception $e) {
                die('get Authenticate failed!');
            }
            if ($result->IsAuthenticateResult->ErrorCode == 0)
            {
                self::$ci->load->library('session');
                self::$ci->session->set_userdata([
                    'SessionGUID' => $result->IsAuthenticateResult->SessionGUID,
                    'login'       => $mg['mg_agentname']
                ]);
                self::$ci->core->update_sx_set('mg_guid',$result->IsAuthenticateResult->SessionGUID,3600);
                wlog(APPPATH.'logs/mg/'.date('Y_m_d').'.log','get_session_guid: ' . $result->IsAuthenticateResult->SessionGUID);
            }else {
                wlog(APPPATH.'logs/mg/'.date('Y_m_d').'.log','get_session_guid: ' . json_encode( $result->IsAuthenticateResult ));
            }
            return $result->IsAuthenticateResult->SessionGUID;
        }

	}
}
