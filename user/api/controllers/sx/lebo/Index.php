<?php

if ( !defined( 'BASEPATH' ) )
{
	exit('No direct script access allowed');
}

include_once FCPATH.'api/core/SX_Controller.php';

class User extends SX_Controller
{
	protected $api = 'https://lgtestapi.lgapi.co';
	public function __construct()
	{
		parent::__construct();
	}
	/**
	*获取 securityKey
	**/
	public function security_key() {
		
	}
}