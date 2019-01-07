<?php
/**
 * 大厅设置 接口
 * User: lqh6249
 * Date: 1970/01/01
 * Time: 00:01
 */
defined('BASEPATH') or exit('No direct script access allowed');

class Activity extends MY_Controller
{
    static $wsAct = '';  
    public function __construct()
    {
        parent::__construct();
        $this->load->helper('interactive');
        $this->load->model('interactive/Set_config_model','SC');
        self::$wsAct = new WsAct($this->SC->sn);
    }

    /**
     * 获取互动大厅运行状态和全局禁言设置
     */
    public function get_hddt_set()
    {
        //获取互动大厅全局禁言设置
        $sdata = $this->get_gc_config();
        $this->return_json(OK, $sdata);
    }

    //开启/禁用 互动大厅 全局禁言
    public function save_set()
    {
        $key = input("param.key",'','trim');
        $value = input("param.value",1,'intval');
        if (empty($key)) $this->return_json(E_ARGS,'invalid parameter');
        //执行更新操作
        $where['key'] = $key;
        $save_data['value'] = $value;
        $result = $this->SC->write('gc_set',$save_data,$where);
        if ($result) 
        {
            //数据库保存执行结果以后 redis数据同步更新
            $redis_key = 'is_all_silence';
            $sdata = $this->get_gc_config();
            //$sdata = json_encode($sdata,320);
            self::$wsAct->set($redis_key, $sdata['is_all_silence']);
            //返回执行结果
            $this->return_json(OK,'执行成功');
        } else {
            $this->return_json(E_ARGS,'没有数据被修改');
        }
    }

    /**
     * 更换计划软件接口的code
     */
    public function get_plan_code(){
        $update = input("param.update",0,'intval');
        $code = self::$wsAct->get('plan_code');
        if ( empty($code) || $update ) {
            $code = md5('' .  time() . rand(100, 999));
            self::$wsAct->set('plan_code', $code);
        }
        $this->return_json(OK, ['code' => $code]);
    }

    // 发布软件计划
    public function send_plan()
    {
        //$code = input("param.code",0,'trim');
        echo '';
    }

    /*
     * @获取数据表gc_set 配置信息
     * @ $is_all 是否获取全部 0 只获取禁言设置 1 获取全部
     */
    private function get_gc_config($is_all=0)
    {
        $sdata = [];
        $where = [];
        //默认获取 禁言设置信息
        if (empty($is_all)) $where['key'] = 'is_all_silence';
        $data = $this->SC->get_list('key,value','gc_set',$where);
        foreach($data as $key => $val)
        {
            $sdata[$val['key']] = $val['value'];
        }
        unset($data);
        return $sdata;
    }
}
