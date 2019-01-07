<?php
defined('BASEPATH') OR exit('No direct script access allowed');

Class Ping extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    public function ping()
    {
        $this->return_json(OK, 'API连接成功');
    }
}