<?php
/**
 * @模块   会员中心／充值记录
 * @模块   会员中心／充值详情
 * @版本   Version 1.0.0
 * @日期   2017-03-30
 * shensiming
 */

defined('BASEPATH') or exit('No direct script access allowed');

class Income_record extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('user/Income_record_model',
                            'core');
    }

    /* 筛选选项 */
    private $opts = array('rows'=>
            array(
                array('label'=>'全部',   'value'=>INCOME_OPT_ALL),
                array('label'=>'银行转账','value'=>INCOME_OPT_COMPANY),
                array('label'=>'线上入款','value'=>INCOME_OPT_ONLINE),
                array('label'=>'彩豆充值',  'value'=>INCOME_OPT_CARD),
                array('label'=>'人工存入',  'value'=>INCOME_OPT_PEOPLE)
            ));
    /* type值限制 */
    private $type_list = array(INCOME_OPT_ALL,INCOME_OPT_COMPANY,INCOME_OPT_ONLINE,INCOME_OPT_CARD,INCOME_OPT_PEOPLE);






    /******************公共方法*******************/
    /**
     * 获取列表数据
     */
    public function get_list()
    {
        // 判断哪张表
        $type = $this->G('type');
        if (!in_array($type, $this->type_list)) {
            $type = INCOME_OPT_ALL;
        }
        if (empty($this->user['id'])) {
            $this->return_json(E_ARGS, '无效用户');
        }
        
        // 精确条件
        $basic = array(
            'a.uid' => $this->user['id']);
        $time_start = strtotime($this->G('time_start'))
                    ? strtotime($this->G('time_start').' 00:00:00') : 0;
        $time_end   = strtotime($this->G('time_end'))
                    ? strtotime($this->G('time_end').' 23:59:59') : 0;

        if ($time_start < (time()-86440*60)) {
            $time_start = (time()-86440*60);
        }

        if ($type == INCOME_OPT_ALL) {
            $basic['start'] = $time_start;
            $basic['end'] = $time_end;
        } elseif ($type == INCOME_OPT_CARD) {
            //$basic['a.use_time >='] = date($time_start);
            //$basic['a.use_time <='] = date($time_end);
            //取现金流水表就可以去除对应的数据
            $basic['a.addtime >='] = $time_start;
            $basic['a.addtime <='] = $time_end;
            $basic['a.type =']     = 15;
        } else {
            $basic['a.addtime >='] = $time_start;
            $basic['a.addtime <='] = $time_end;
        }
        // 高级搜索
        $senior = array();
        // 排序分页
        $page = (int)$this->G('page') > 0 ?
                (int)$this->G('page') : 1;
        $rows = 150;
        $page   = array(
            'page'  => $page,
            'rows'  => $rows,
        );
        if ($type != INCOME_OPT_ALL) {
            $data = $this->core->get_record_list($type, $basic,
                                        $senior, $page);
        } else {
            $data = $this->core->get_record_all($basic['a.uid'],
                                        $page, $basic);
            if (!empty($data) && !empty($data['rows'])) {
                foreach ($data['rows'] as $k=>$v) {
                    $tag1[] = $v['addtime'];
                }
                array_multisort($tag1, SORT_DESC, $data['rows']);
            }
        }

        // 如果是线上入款并且status=4(风控：金额过大),改为status=1(未确认)
        foreach ($data['rows'] as $key => $value) {
            if ($value['type'] == 2 && $value['status'] == 4) {
                $data['rows'][$key]['status'] = 1;
            }
        }
        
        if ($data) {
            $this->return_json(OK, $data);
        } else {
            $this->return_json(OK, array('rows'=>array()));
        }
    }

    /**
     * 获取筛选类型
     */
    public function get_type()
    {
        $this->return_json(OK, $this->opts);
    }
    /*******************************************/
}
