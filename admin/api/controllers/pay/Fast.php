<?php
/**
 * 代付直通車 接口
 * User: lqh6249
 * Date: 1970/01/01
 * Time: 00:01
 */
defined('BASEPATH') or exit('No direct script access allowed');

class Fast extends GC_Controller
{
    private $tb = 'bank_fast_pay'; //設置本次操作的數據表(不加前綴)

    public function __construct() 
    {
        parent::__construct();
        $this->load->helper('interactive');
        $this->load->model('pay/Fast_model','FM');
    }

    public function get_fast_list()
    {
        //设置分页排序等参数
        $where2 = array(
            'orderby' => ['re_order'=>'desc','id'=>'desc']
        );
        $parms = array(
            'page' => input('param.page',1,'intval'),
            'rows' => input('param.rows',50,'intval')
        );
        //展示在列表中的字段參數
        $field = 'id,platform_name,merch,min_amount,max_amount';
        $field .= ',block_amount,fixed_amount,re_order,status';
        $data = $this->FM->get_list($field,$this->tb,[],$where2,$parms);
        $this->return_json(OK, $data);
    }

    public function get_fast_info()
    {
        $id = input('param.id',0,'intval');
        if (empty($id)) $this->return_json(E_ARGS,'Parameter is null');
        $where['id'] = $id;
        $info = $this->FM->get_one('*',$this->tb,$where);
        //標題提示信息轉化
        if (!empty($info['describe']))
        {
            $temp = json_decode($info['describe'],true);
            unset($info['describe']);
            $info['title'] = isset($temp['title']) ? $temp['title'] : '';
            $info['prompt'] = isset($temp['prompt']) ? $temp['prompt'] : '';
        }
        $this->return_json(OK,$info);
    }

    //保存接入方平台信息
    public function fast_save()
    {
        //接受參數
        $parms = input('param.');
        $id = input('param.id',0,'intval');
        if (empty($parms))
        {
            $this->return_json(E_ARGS,'Parameter is null');
        }
        //獲取構造的數據
        $save_data = $this->get_fast_data();
        //編輯信息
        if (!empty($id))
        {
            $where['id'] = $id;
            $result = $this->FM->write($this->tb,$save_data,$where);
        //添加信息
        } else {
           //添加信息時,系統生成商戶信息
           $merch_data = $this->FM->get_merch_info();
           $save_data = array_merge($save_data,$merch_data);
           $result = $this->FM->write($this->tb,$save_data);
        }
        //返回提示信息
        if ($result) 
        {
            $this->return_json(OK,'执行成功');
        } else {
            $this->return_json(E_ARGS,'没有数据被修改');
        }
    }

    //刪除接入平台信息
    public function fast_delete()
    {
        //获取参数并对参数进行默认设置
        $id = input('param.id');
        if (empty($id)) $this->return_json(E_ARGS,'Parameter is null');
        $id = explode(',', $id);
        //获取即将删除的数据 
        $where['wherein'] = ['id' => $id];
        //执行删除数据库操作
        $result = $this->FM->delete($this->tb, $id);
        if ($result)
        {
            $this->return_json(OK,'执行成功');
        } else {
            $this->return_json(E_ARGS,'执行失败');
        }
    }

    public function get_fasts()
    {
        //展示在列表中的字段參數
        $field = 'id,platform_name as name';
        $data = $this->FM->get_list($field,$this->tb);
        array_unshift($data,array('id'=>0,'name'=>'全部'));
        $this->return_json(OK, $data);
    }

    //接受來自於表單的參數
    private function get_fast_data()
    {
        //獲取參數
        $parms = input('param.');
        $title = input('param.title','','trim');
        $prompt = input('param.prompt','','trim');
        //$parms = array_filter($parms);
        //構造備註信息參數
        if (!empty($title) || !empty($prompt))
        {
            $temp['title'] = $title;
            $temp['prompt'] = $prompt;
            $parms['describe'] = json_encode($temp,320);
            unset($parms['title']);
            unset($parms['prompt']);
        }
        if (empty($parms['pay_code']))
        {
            $parms['pay_code'] = '1,2,4,5,7';
        }
        //過濾掉由系統生成的參數(用戶不得修改)
        unset($parms['id']);
        unset($parms['merch']);
        unset($parms['pay_key']);
        unset($parms['pay_private_key']);
        unset($parms['pay_public_key']);
        return $parms;
    }
}
