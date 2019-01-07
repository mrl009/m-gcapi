<?php
class RSA
{
    private $privateKey='';//私钥（用于用户解密）
    private $publicKey='';//公钥（用于用户加密，服务端即第三方数据解密）
    public $error='';

    public function __construct($params){
        extract($params);
        $this->loadPubPriKey($publicKey,$privateKey);
    }

    protected function loadPubPriKey(&$publicKey,&$privateKey) {
        $publicKey = str_replace([
            '-----BEGIN RSA PUBLIC KEY-----',
            '-----BEGIN PUBLIC KEY-----',
            '-----END RSA PUBLIC KEY-----',
            '-----END PUBLIC KEY-----'], '', $publicKey);
        $privateKey = str_replace([
            '-----BEGIN RSA PRIVATE KEY-----',
            '-----BEGIN PRIVATE KEY-----',
            '-----END RSA PRIVATE KEY-----',
            '-----END PRIVATE KEY-----'], '', $privateKey);
        $publicKey = trim($publicKey);
        $publicKey = wordwrap($publicKey, 64, "\n", true);
        $privateKey = trim($privateKey);
        $privateKey = wordwrap($privateKey, 64, "\n", true);
        $publicKey = "-----BEGIN PUBLIC KEY-----\n" . $publicKey . "\n-----END PUBLIC KEY-----";
        $privateKey = "-----BEGIN PRIVATE KEY-----\n" . $privateKey . "\n-----END PRIVATE KEY-----";
        $this->publicKey = openssl_pkey_get_public($publicKey);
        $this->privateKey = openssl_pkey_get_private($privateKey);
        if (false === $this->publicKey) {
            $this->error .= 'Invalid PublicKey ';
        }
        if (false === $this->privateKey) {
            $this->error .= 'Invalid PrivateKey ';
        }
        if ($this->error) {
            @wlog(APPPATH . "logs/agentpay/rsa_error_" . date('Ym') . '.log', '[publicKey] ' . PHP_EOL . $publicKey . PHP_EOL .'[privateKey] ' . PHP_EOL . $privateKey . PHP_EOL .'[error] ' . $this->error);
        }
    }



    /**
     * 私钥加密
     * @param 原始数据 $data
     * @return 密文结果 string
     */
    public function encryptByPrivateKey($data) {
        openssl_private_encrypt($data,$encrypted,$this->privateKey,OPENSSL_PKCS1_PADDING);
        $encrypted = base64_encode($encrypted);//加密后的内容通常含有特殊字符，需要编码转换下，在网络间通过url传输时要注意base64编码是否是url安全的
        return $encrypted;
    }

    /**
     * 私钥解密
     * @param 密文数据 $data
     * @return 原文数据结果 string
     */
    public function decryptByPrivateKey($data){
        $data = base64_decode($data);
        $crypto = '';
        foreach (str_split($data, 128) as $chunk) {
            openssl_private_decrypt($chunk, $decryptData, $this->privateKey);
            $crypto .= $decryptData;
        }
        return $crypto;
    }

    /**
     * 私钥签名
     * @param unknown $data
     * @return string
     */
    public function signByPrivateKey($data){
        openssl_sign($data, $signature, $this->privateKey);
        $encrypted = base64_encode($signature);//加密后的内容通常含有特殊字符，需要编码转换下，在网络间通过url传输时要注意base64编码是否是url安全的
        return $encrypted;
    }


    /**
     * 公钥加密
     * @param 原文数据 $data
     * @return 加密结果 string
     */
    public function encryptByPublicKey($data) {

        $maxlength=117;
        $output='';
        while($data){
            $input = substr($data,0,$maxlength);
            $data = substr($data,$maxlength);
            $ok= openssl_public_encrypt($input,$encrypted,$this->publicKey);
            if (false === $ok) {
                return false;
            }
            $output .= $encrypted;
        }
        return base64_encode($output);
    }

    /**
     * 公钥解密
     * @param 密文数据 $data
     * @return 原文结果 string
     */
    public function decryptByPublicKey($data) {
        $data = base64_decode($data);
        $ok = openssl_public_decrypt($data,$decrypted,$this->publicKey);
        if (false === $ok) {
            return false;
        }
        return $decrypted;
    }

    /**
     * 公钥验签
     * @param string $data
     * @param string $sign
     * @return bool
     */
    public function verifyByPublicKey($data,$sign){
        $sign = base64_decode($sign);
        return openssl_verify($data, $sign, $this->publicKey);
    }

    public function __destruct(){
        openssl_free_key($this->privateKey);
        openssl_free_key($this->publicKey);
    }
}