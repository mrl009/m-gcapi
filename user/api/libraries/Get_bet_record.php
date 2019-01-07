<?php

class Get_bet_record
{
	public $ci = null;
	public function __construct()
	{
		$this->ci = & get_instance();
	}

	/**
	 * 生成dg注单
	 */
	public function create_dg_bet_record_bak( $data, $game_api )
	{

		$this->ci->load->model( 'sx/dg/Game_order_model', 'game_order_model' );
		if( $this->ci->game_order_model->inset_order( $data[ 'list' ], 'dg' ) )
		{
			$list_id = array_column( $data[ 'list' ], 'id' );
			//返回抓取注单id集合
			$game_api->markReport( $list_id );
		}

		return true;
	}

    public function create_dg_bet_record($data, $game_api)
    {
        $this->ci->load->model('sx/dg/Game_order_model', 'game_order_model');
        $list_id = $this->ci->game_order_model->inset_order($data['list'], 'dg');
        if (!empty($list_id)) {
            $game_api->markReport($list_id);
            wlog(APPPATH . 'logs/dg/' . date('Y-m-d') . '-list_id.log', json_encode($list_id));
        }
        return true;
    }
}