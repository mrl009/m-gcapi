<?php
/**
 * @模块   赔率
 * @版本   Version 1.0.0
 * @日期   2017-03-30
 * shensiming
 */

defined('BASEPATH') or exit('No direct script access allowed');


class Rate extends MY_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('rate/Rate_model','rate_model');
    }

    /******************公共方法*******************/
    /**
     * 游戏玩法
     */
    public function get_list($gid = 0)
    {
        if (empty($gid) || !is_numeric($gid)) {
            $this->return_json(E_ARGS, '无效的gid');
        }
        $rows = $this->rate_model->getproducts($gid);
        if(empty($rows)) $rows = array(array());
        $this->return_json(OK, $rows);
    }

    /**
     * 根据id修改单个赔率
     */
    public function set_id()
    {
        /* 获取id */
        $id = $this->P('setid');
        if(!$this->_is_id($id)) {
            $this->return_json(E_ARGS, 'id参数出错');
        }

        /* 获取修改值 */
        /* 获取修改值 */
        $FLAG = true;
        if(is_numeric($this->P('rate')) || !empty($this->P('rate'))) {
            $rate_arr = explode(',', $this->P('rate'));
            if($this->_array_is_number($rate_arr)) {
                $FLAG = false;
                $data['rate'] = implode(',', $rate_arr);
            } else {
                $this->return_json(E_ARGS, '参数错误');
            }
        }
        if(is_numeric($this->P('rebate'))) {
            $rebate = $this->P('rebate');
            if(is_numeric($rebate)) {
                $FLAG = false;
                $data['rebate'] = $rebate;
            } else {
                $this->return_json(E_ARGS, '参数错误');
            }
        }
        if(is_numeric($this->P('rate_min')) || !empty($this->P('rate_min'))) {
            $rate_min_arr = explode(',', $this->P('rate_min'));
            if($this->_array_is_number($rate_min_arr)) {
                $FLAG = false;
                $data['rate_min'] = implode(',', $rate_min_arr);
            } else {
                $this->return_json(E_ARGS, '参数错误');
            }
        }

        /* rate，rebate，rate_min必须一个有值 */
        if($FLAG) {
                $this->return_json(E_ARGS, '赔率，最小赔率，返水必须一个有值');
        }

        /* 修改赔率 */
        $this->rate_model->select_db('private');
        $flag = $this->rate_model->write(
                'games_products', $data, array('id'=>$id));
        $game = $this->rate_model->get_game($id);
        /* 记录此操作到日志 */
        if($flag) {
            $content = "修改单个赔率：{$game},状态：成功,id={$id}---".json_encode($data);
            $this->rate_model->add_log($content);
            $this->return_json(OK);
        } else {
            $content = "修改单个赔率：{$game},状态：失败,id={$id}".json_encode($data);
            $this->rate_model->add_log($content);
            $this->return_json(E_ARGS, '没有修改');
        }
    }

    /**
     * 根据tid修改等于tid的赔率
     */
    public function set_tid()
    {
        /* 获取tid */
        $tid = $this->P('tid');
        if(!$this->_is_id($tid)) {
            $this->return_json(E_ARGS, 'id参数出错');
        }

        /* 获取修改值 */
        $FLAG = true;
        if(is_numeric($this->P('rate'))  || !empty($this->P('rate'))) {
            $rate_arr = explode(',', $this->P('rate'));
            if($this->_array_is_number($rate_arr)) {
                $FLAG = false;
                $data['rate'] = implode(',', $rate_arr);
            } else {
                $this->return_json(E_ARGS, '参数错误');
            }
        }
        if(is_numeric($this->P('rebate'))) {
            $rebate = $this->P('rebate');
            if(is_numeric($rebate)) {
                $FLAG = false;
                $data['rebate'] = $rebate;
            } else {
                $this->return_json(E_ARGS, '参数错误');
            }
        }
        if(is_numeric($this->P('rate_min'))  || !empty($this->P('rate_min'))) {
            $rate_min_arr = explode(',', $this->P('rate_min'));
            if($this->_array_is_number($rate_min_arr)) {
                $FLAG = false;
                $data['rate_min'] = implode(',', $rate_min_arr);
            } else {
                $this->return_json(E_ARGS, '参数错误');
            }
        }
        /* rate，rebate，rate_min必须一个有值 */
        if($FLAG) {
                $this->return_json(E_ARGS, '赔率，最小赔率，返水必须一个有值');
        }

        /* 修改赔率 */
        $this->rate_model->select_db('private');
        $flag = $this->rate_model->write(
                'games_products', $data, array('tid'=>$tid));
        $game = $this->rate_model->get_game($tid, true);
        /* 记录此操作到日志 */
        if($flag) {
            $content = "修改多个赔率：{$game},状态：成功,tid={$tid}";
            $this->rate_model->add_log($content);
            $this->return_json(OK);
        } else {
            $content = "修改多个赔率：{$game},状态：成功,tid={$tid}";
            $this->rate_model->add_log($content);
            $this->return_json(E_ARGS, '没有修改');
        }
    }
    
    /**
     * 根据多个id修改赔率
     */
    public function set_all()
    {
        /* 获取id集合 */
        $ids = $this->P('setid');
        $ids = explode('|', $ids);
        if(!$this->_array_is_number($ids)) {
            $this->return_json(E_ARGS, 'id参数出错');
        }
        
        /* 获取rate集合 */
        $field_arr = array(
                    'rate'=>'赔率参数出错', 
                    'rebate'=>'返水参数出错', 
                    'rate_min'=>'最小赔率参数出错');
        foreach ($field_arr as $key => $error) {
            $$key = $this->P($key);
            if(!empty($$key)) {
                $$key = explode('|', $$key);
                if(count($ids) != count($$key))
                    $this->return_json(E_ARGS, $error);
                foreach ($$key as $key => $value) {
                    $value = explode(',', $value);
                    if(!$this->_array_is_number($value))
                        $this->return_json(E_ARGS, $error);
                    $$key[$key] = implode(',', $value);
                }
                
            }
        }
        
        /* rate，rebate，rate_min必须一个有值 */
        $flag = false;
        foreach ($field_arr as $key => $error) {
            if(!empty($$key)) {
                $flag = true;
                continue;
            }
        }
        if(!$flag)
            $this->return_json(E_ARGS, '赔率，最小赔率，返水必须一个有值');

        /* 组合 */
        foreach ($ids as $id) {
            $data[$id]['id'] = $id;
            foreach ($field_arr as $key => $error) {
                if(!empty($$key) && is_array($$key))
                    $data[$id][$key] = array_shift($$key);
            }
        }

        /* 修改赔率 */
        $this->rate_model->select_db('private');
        $flag = $this->rate_model->db->update_batch
                ('gc_games_products', $data, 'id');
        $game = $this->rate_model->get_game($ids[0]);

        /* 记录此操作到日志 */
        if($flag) {
            $content = "修改多个赔率：{$game},状态：成功,修改id="
                            .implode(',', $ids);
            $this->rate_model->add_log($content);
            $this->return_json(OK);
        } else {
            $content = "修改多个赔率：{$game},状态：失败,修改id="
                            .implode(',', $ids);
            $this->rate_model->add_log($content);
            $this->return_json(E_ARGS, '没有修改');
        }
    }

    /**
     * 初始化赔率
     */
    public function init()
    {
        /* 获取初始化的彩种id */
    	$id = $this->P('gid');
    	if(!$this->_is_id($id)) {
    		$this->return_json(E_ARGS);
    	}
        /* 根据彩种id获取初始化赔率 */
    	$data = $this->rate_model->get_init_rate($id);
    	if($data) {
            /* 修改为初始化赔率 */
            $this->rate_model->select_db('private');
    		$flag = $this->rate_model->db->update_batch
                ('gc_games_products', $data, 'id');
            /* 记录此操作到日志 */
            $this->rate_model->select_db('public');
            $game = $this->rate_model->get_one('name','games',['id'=>$id]);
            if($flag) {
                $content = "初始化赔率：{$game['name']},状态：成功";
                $this->rate_model->add_log($content);
                $this->return_json(OK);
            } else {
                $content = "初始化赔率：{$game['name']},状态：失败";
                $this->rate_model->add_log($content);
                $this->return_json(E_ARGS, '没有修改');
            }
    	}
    	$this->return_json(E_ARGS);
    }
    /*******************************************/






    
    /******************私有方法*******************/
    private function _is_id($i)
    {
        if ((int) $i && $i > 0) {
            return (int) $i;
        }
        return 0;
    }
    private function _array_is_number($data)
    {
        foreach ($data as $value) {
            if(!is_numeric($value))
                return false;
        }
        return true;
    }
    /*******************************************/
}
