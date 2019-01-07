<?php

defined('BASEPATH') OR exit('No direct script access allowed');

include_once FCPATH.'api/core/SX_Controller.php';

class Game extends SX_Controller
{
	protected $game_api;
	protected $platform_name = 'dg';
	public function __construct()
	{
		parent::__construct();
		$this->load->library('BaseApi');
		$this->game_api = BaseApi::getinstance( $this->platform_name, 'game', $this->sxuser[ 'sn' ] );
	}

	/**
	 * 修改会员限红组
	 * @param $username 目标限红组
	 * @param $data
	 */
	public function updateLimit()
	{
		$data = $this->game_api->updateLimit( $this->sxuser[ 'merge_username' ], $this->sxuser[ 'oddtype' ] );
		if( $data[ 'codeId' ] == 0 )
		{
			$this->load->model( 'sx/dg/user_model' );
			$this->user_model->update_data( $this->sxuser[ 'merge_username' ], $this->sxuser[ 'oddtype' ] );
		}

		$this->ajax_return( [ 'codeId' => $data[ 'codeId' ] ] );
	}


	/**
	 * 重新缓存注单
	 * @param $list ["起始日期", "结束日期"]
	 * @param $data 需要重新缓存的注单的ID
	 */
	public function initTickets()
	{
		$this->sxuser[ 'list' ] = $this->sxuser[ 'list' ] ?? [];
		$this->sxuser[ 'oddtype' ] = $this->sxuser[ 'oddtype' ] ?? '';
		$data = $this->game_api->initTickets( $this->sxuser[ 'list' ], $this->sxuser[ 'oddtype' ] );
		$this->ajax_return( [ 'codeId' => $data[ 'codeId' ] ] );
	}

}