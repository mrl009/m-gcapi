<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * 车票管理
 *
 * @file        user/api/controllers/ios/ticket
 * @package     user/api/controllers/ios
 * @author      ssm
 * @version     v1.0 2017/07/12
 * @created 	2017/07/1
 */
class Car_rental extends GC_Controller
{
	public function get_rental_info( $city = '', $json = true )
	{
		$rental_city = $city !== '' ? $city : $this->G( 'rental_city' );
		$carjson_map = [ '东莞' => [ 1 ], '中山' => [ 1 ], '佛山' => [ 1, 2, 3 ], '广州' => [ 1, 2 ], '惠州' => [ 1 ], '深圳' => [ 1, 2 ] ];
		if( !isset( $rental_city ) )
		{
			$this->return_json( -1, '未开通的城市！');
		}

		$data = [];
		$file_paht = APPPATH . 'controllers/ios/json/car_rental/';
		foreach ( $carjson_map[ $rental_city  ] as $val )
		{
			$json_file_paht = $file_paht . $rental_city . $val . '.json';
			if( file_exists( $json_file_paht ) )
			{
				if( $json )
				{
					$data[] = json_decode( file_get_contents( $json_file_paht ), true );
				}
				else
				{
					$data[] = file_get_contents( $json_file_paht );
				}
			}
		}

		if( $json )
		{
			$this->return_json( OK,$data );
		}
		else
		{
			return $data;
		}
	}

	public function order()
	{
		$uid =  $this->G('uid' );
		$key = 'ios:car_rental_car:' . $uid;
		$method = $this->G( 'method' );
		$this->load->model('MY_Model', 'M');
		$this->M->redisP_select(REDIS_PUBLIC);

		if( $method == 'add' )
		{	$pid =  $this->G( 'pid' );
			$left_date = $this->G( 'leftDate' );
			$right_date =  $this->G('rightDate' );

			$pid && $left_date && $right_date && $uid || exit( '参数错误' );


			$order_num = $this->order_num( $pid );
			$order = [
				'pid' => $pid,
				'leftDate' => $left_date,
				'rigtDate' => $right_date,
				'order_num' => $order_num
			];

			$this->M->redis_lpush( $key, json_encode( $order ) );
			$this->return_json( OK, [ 'order_num' => $order_num ] );
		}
		else if( $method == 'get' )
		{
			$car_data = $this->get_rental_info( '佛山', false );
			$data = $this->M->redis_lrange( $key, 0, 10000 );
			foreach ( $data as $k => $v )
			{
				$data[ $k ] = json_decode( $v, true );
				foreach ( $car_data as $key => $val )
				{
					$val = json_decode( $val, true );
					foreach ( $val[ 'plist' ] as $vv )
					{
						if( $vv[ 'pid' ] == $data[ $k ][ 'pid' ] )
						{
							$data[ $k ][ 'car_info' ] = $vv;
						}
					}
				}
			}
			
			$this->return_json( OK, $data );
		}
	}

	public function order_num( $pid ) /* {{{ */
	{
		$micro = substr(microtime(), 2, 4);
		$order_num = $pid.substr(date('ymdHis'), 1).$micro;
		return $order_num;
	}
}