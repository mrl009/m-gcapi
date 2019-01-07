<?php
defined('BASEPATH') OR exit('No direct script access allowed');

include_once FCPATH . 'api/core/SX_Controller.php';
class Redirect extends SX_Controller
{


    public function __construct()
    {
        parent::__construct();

    }

    public function redirect()
    {
        // $id   = substr( $this->input->get( 'id', TRUE ), 5 );
        //$type = $this->input->get( 'type', TRUE );
        //	$id = substr($this->sxuser['id'], 5);
        //$type = $this->sxuser['type'];
        //$id = $_GET[ 'id' ] ? substr( $_GET[ 'id' ], 5 ) : 0;
        //$type = $_GET[ 'type' ] ? $_GET[ 'type' ] : '';
//		$id && $type || exit( 'invalid arguments!' );
//		$this->load->model( 'sx/User_model', 'user_model' );
//		$user = $this->user_model->getUserById($id, 'pt');

        $user[ 'type' ] = 'wap';
        $user[ 'username' ] = 'gc0zhufei1';
        $user[ 'password' ] = 'd2f6ac27f4c86ca622553e0acdc1ec0c';
        //print_r($user);
        $this->load->view('pt/redirect', $user );
    }

}
