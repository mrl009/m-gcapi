<?php
/**
 * @file user/api/controllers/pay/Auto_income.php
 * @brief 配合插件抓取银行流水
 * 
 * Copyright (C) 2018 yicai.tw
 * All rights reserved.
 * 
 * @package pay
 * @author Fei <feifei@xxx.com> 2018/09/04 15:24
 * 
 * $Id$
 */

defined('BASEPATH') or exit('No direct script access allowed');

class Auto_income extends GC_Controller
{
    public function __construct()
    {
        parent::__construct();
        //$this->load->model('system_model');
        $this->load->model('pay/Incompany_model', 'income_model');
    }

    /**
     * demo:
     * post: res=base64_encode('{"sn":"a01","bank":"boc","card_num":"6234***1234","list":[]}')&chksum=489252cd7d9d01267763b99131ca0097
     *  chksum = md5(base64_decode(res)+key)
     */
    public function index() /* {{{ */
    {
        $res = $this->P('res');
        $chksum = $this->P('chksum');
        $sn_key = $this->income_model->get_gcset(['google_key']);
        $key = $sn_key['google_key'];
        $res = base64_decode($res);
        $chk = md5($res.$key);
        if ($chk != $chksum) {
            wlog(APPPATH.'logs/'.$this->_sn.'_income_'.date('Ym').'.log', 'chksum error:'.$chk.' '.$res);
            $this->return_json(E_ARGS, 'chksum error.');
        }
        //wlog(APPPATH.'logs/'.$this->_sn.'_income_'.date('Ym').'.log', $res);

        $res = json_decode($res, true);
        if (!is_array($res)) {
            $this->return_json(E_ARGS, 'args error.');
        }
        if (empty($res['bank'])) {
            $this->return_json(E_ARGS, 'bank error.');
        }
        $fn = $res['bank'];
        if (method_exists($this, $fn)) {
            $ret = $this->$fn(empty($res['card_num']) ? '' : $res['card_num'], $res['list']);
        }

        $this->get_sure();
        $this->return_json(OK, $ret);
    } /* }}} */

    /**
     * 自动确认入款
     *  1.根据获取已入款数据订单,与取得的银行的数据进行对比
     *  2.比对顺序：1)用户uid,2)充值金额price,3)银行卡号截取对比
     */
    public function get_sure()
    {
        /*1.获取入款订单的信息和入款用户id*/
        $data = $this->income_model->get_paydata();
        /*2.根据取得订单与网银获取的当前用户未入账数据校验*/
        foreach ($data as $key => $value) {
            if (empty($value['uid'])) {
                $this->return_json(E_ARGS,'会员id不能为空');
            }
            if (empty($value['id'])) {
                $this->return_json(E_ARGS,'入款订单编号不能为空');
            }
            if (empty($value['bank_card_id'])) {
                $this->return_json(E_ARGS,'银行卡编号不能为空');
            }

            $card_num = $this->income_model->get_card($value);

            //获取bank_auto 表数据
            $paydata = $this->income_model->auto_list($value['name'],$value['addtime']);

            foreach ($paydata as $k=>$v) {
                //匹配金额(入款正，出款未负数) 统一保留三位小数
                $price = (float)sprintf("%.3f", $value['price']);
                $amount = (float)sprintf("%.3f", $v['pay_amount']);
                if ($price == $amount)
                {
                    //匹配银行卡号后四位
                    /*$l_4 = substr($card_num['card_num'],-4);//银行卡后四位
                    $card = substr($v['card_num'],-4);
                    if ($l_4 == $card){}*/
                        $id = $value['id'];//确认入款的订单编号
                        $aid = $v['id'];//已确认的银行数据订单
                        if ($id<=0 ||$aid <=0) {
                            $this->return_json(E_ARGS, '参数出错');
                        }
                        if (!empty($id)&& !empty($aid)) {
                            $status=2;
                            $logData['content'] = 'ID'.$id.'-确认入款';
                            $rkinCompanyLock = 'cash:lock:in:'.$id;
                            $atuoLock        = 'auto:lock:in:'.$aid;
                            $fbs = $this->income_model->fbs_lock($rkinCompanyLock);//加锁
                            $ybs = $this->income_model->fbs_lock($atuoLock);//bank_auto 表加锁
                            if (!$fbs||!$ybs) {
                                $this->return_json(E_ARGS, '数据正在处理中');
                            }
                            $remark = '系统入款';
                            $res = $this->income_model->handle_in($aid, $id, $status, $remark);
                            $this->income_model->fbs_unlock($rkinCompanyLock);//解锁
                            $this->income_model->fbs_unlock($atuoLock);//解锁
                            $this->load->model('log/Log_model');
                            if ($res['status']) {
                                $logData['content'] = $this->income_model->push_str .'id:'.$id;
                                $this->Log_model->record('0', $logData);
                                //echo 'OK';
                            } else {
                                $logData['content'] = $this->income_model->push_str. 'id:'. $id;
                                $this->Log_model->record('0', $logData);
                                //echo E_OK.$res['content'];
                            }
                        }

                }
            }
        }
    }

    /**
     * demo:
     * 中国银行
     *      [{
				"transChnl": "网上银行",
				"chnlDetail": "",
				"chargeBack": false,
				"paymentDate": "2018/08/23",
				"currency": "001",
				"cashRemit": "",
				"amount": -11.000,
				"balance": 0.000,
				"businessDigest": "转账支出",
				"furInfo": "",
				"payeeAccountName": "凌云辉",
				"payeeAccountNumber": "6217***********2531",
				"paymentTime": "03:43:24"
			}, {
				"transChnl": "网上银行",
				"chnlDetail": "",
				"chargeBack": false,
				"paymentDate": "2018/08/23",
				"currency": "001",
				"cashRemit": "",
				"amount": 11.000,
				"balance": 11.000,
				"businessDigest": "转账收入",
				"furInfo": "",
				"payeeAccountName": "吴宜龙",
				"payeeAccountNumber": "6217***********1767",
				"paymentTime": "03:41:31"
			}]
     */
    public function boc($card_num = '', & $data = []) /* {{{ */
    {
        $i = 0;
        $d = ['bank'=>'boc','card_num'=>$card_num,'pay_card_name'=>'','pay_card_num'=>'','pay_channel'=>'','pay_amount'=>'','pay_time'=>'','balance'=>0,'remark'=>'','desc'=>'0','status'=>0,'created'=>time()];
        foreach ($data as $v) {
            $d['pay_card_name'] = $v['payeeAccountName'];
            $d['pay_card_num'] = $v['payeeAccountNumber'];
            $d['pay_channel'] = $v['transChnl'];
            $d['pay_amount'] = $v['amount'];
            $d['pay_time'] = $v['paymentDate'].' '.$v['paymentTime'];
            $d['balance'] = $v['balance'];
            $d['remark'] = $v['businessDigest'];
            $d['content'] = json_encode($v);
            $d['status'] = 0;
            if ($d['pay_amount'] < 0) {
                $d['status'] = 2;
            }
            
            if (empty($d['pay_card_name']) || empty($d['pay_card_num']) || $d['pay_amount'] == null) {
                $d['pay_card_name'] = $v['furInfo'];
                //continue;
            }
            //$ret = $this->income_model->db->insert('bank_auto', $d);
            $sql = $this->income_model->db->insert_string('bank_auto', $d);
            //$sql .= " ON DUPLICATE KEY UPDATE `desc`=concat(`desc`,'1')";
            $sql .= " ON DUPLICATE KEY UPDATE `desc`=`desc`+1";
            $ret = $this->income_model->db->query($sql);

            if (!$ret) {
                wlog(APPPATH.'logs/'.$this->_sn.'_income_'.date('Ym').'.log', 'duplicate data:'.$d['content']);
            } else {
                $i++;
            }
        }
        return $i;
    } /* }}} */
}
