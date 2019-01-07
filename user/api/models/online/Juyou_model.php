
<?php
defined('BASEPATH') or exit('No direct script access allowed');
/**
 * 聚优支付接口调用
 * Created by PhpStorm.
 * User: 57207
 * Date: 2018/10/17
 * Time: 10:41
 */
include_once __DIR__.'/Publicpay_model.php';
class Juyou_model extends Publicpay_model
{
  protected $c_name ='juyou';
  protected $p_name = 'JUYOU';
  //签名参数
  protected $k = '&key=';//连接符
  protected $f = 'sign';//签名字段


  /**
   * 根据支付通道获取前端返回的发起的方式
   */
    protected function returnApiData($data)
    {
        return $this->buildForm($data,'get');

    }
  /**
   * 构造参数
   */
  protected function getPayData()
  {
      $data = $this->getBaseData();
      $ks = $this->key;
      $fd = $this->f;
      $string = data_to_string($data);
      $data[$fd] = md5($string.$ks);
      return $data;

  }

    /**
     * 构造签名参数
     */
    protected function getBaseData()
    {
        $data['parter']     = $this->merId;//商户号
        $data['type']       = $this->getType();//支付编码类型
        $data['value']      = $this->money;//金额
        if($this->code=='2'){
            $data['value']      = intval($this->money);//金额
        }
        $data['orderid']    = $this->orderNum;//订单号
        $data['callbackurl']= $this->callback;
        return $data;
    }

    /**
     * 根据code取得对应的支付类型
     */
    protected function getType(){
        switch ($this->code){
            case 1:
                return '1004';
                break;
            case 2:
                return '2099';//微信wap
                break;
            case 4:
                return '992';
                break;
            case 5:
                return '2098';//支付宝wap
                break;
            case 8:
                return '2100';//qq扫码
                break;
            case 9:
                return '2102';//京东支付
                break;
            case 12:
                return '2101';//qq wap
                break;
            case 13:
                return '2104';//京东 wap
                break;
            case 17:
                return '2103';//银联扫码
                break;
            case 22:
                return '993';//财富通
                break;
            case 25:
                return '2097';//快捷支付
                break;
            case 27 :
                return '2097';//网银wap
                break;
            default:
                return '';
                break;

        }
    }
}