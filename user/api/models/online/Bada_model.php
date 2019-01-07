
<?php
defined('BASEPATH')or exit('No such ');
/**
 * Created by PhpStorm.
 * User: 57207
 * Date: 2018/9/21
 * Time: 10:07
 */
include_once __DIR__.'/Publicpay_model.php';
class Bada_model extends Publicpay_model
{
    protected $c_name = 'bada';
    protected $p_name = 'BADA';
    protected $sign   = 'sign';
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 获取前端返回数据 部分第三方支付不一样
     * @param array
     * @return array
     */
    protected function returnApiData($data)
    {
        return $this->buildForm($data);
    }

    /**
     * 构造支付参数+sign值
     * @return array
     */
    protected function getPayData()
    {
        //构造基本参数
        $data = $this->getBaseData();
        //构造签名参数
        $data = $this->Versign($data);
        $data['requesttype'] = 'pro';
        $data['hrefbackurl'] = $this->returnUrl;
        $data['memberId'] = $this->user['id'];
        return $data;
    }

    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {
        $data['version'] = '3.0';
        $data['method'] = 'pay';
        $data['partner'] = $this->merId;
        $data['banktype'] = $this->getPayType();
        $data['paymoney'] = $this->money;
        $data['ordernumber'] = $this->orderNum;
        $data['timestamp'] = $this->getMillisecond();
        $data['callbackurl'] = $this->callback;
        return $data;
    }

    /**
     * 根据code值获取支付方式
     * @param string code
     * @return string 支付方式 参数
     */
    private function getPayType()
    {
        switch ($this->code)
        {
            case 1:
            case 2:
                return 'WECHAT';//微信扫码、WAP
                break;
            case 4:
            case 5:
                return 'ALIPAY';//支付宝扫码、WAP
                break;
            default:
                return 'ALIPAY';//微信扫码
                break;
        }
    }

    /**签名验证
     * @param $data
     */
    public function Versign($data)
    {
        //把数组参数以key=value形式拼接
        $string = ToUrlParams($data).$this->key;
        //转换成大写md5加密
        $data[$this->sign] = strtolower(md5($string));
        return $data;
    }

    private function getMillisecond() {
        list($t1, $t2) = explode(' ', microtime());
        return (float)sprintf('%.0f',(floatval($t1)+floatval($t2))*1000);
    }
}