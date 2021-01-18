<?php

/**
 * @Author: Humaam
 * @Date:   2021-01-18 15:29:51 WIB
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . 'vendor/autoload.php';

$Redeem = new Redeem;
$Redeem->run();

class Redeem
{
    private $apiLogin = 'http://api.ku5ut.us/sm0usy/v2/login'; // ganti api login kamu di sini
    private $cookieFileName = 'cookies.tmp';
    private $recentUsername;
    private $recentPassword;
    private $recentCode;
    private $colors;

    public function __construct()
    {
        $this->colors = new Wujunze\Colors();
    }

    public function run()
    {
        $this->recentUsername = trim(readline('username? '));
        $this->recentPassword = trim(readline('password? '));
        $this->getBilling();

        $codes = readline('code? ');
        foreach(FILE($codes, FILE_SKIP_EMPTY_LINES) as $code)
        {
            $this->recentCode = trim($code);
            $resp = $this->redeemCode();

            // var_dump($resp);
            if($resp !== NULL) {

                if('0' == $resp['ErrCode']) {
                    echo $this->colors->getColoredString('SUCCESS REDEEM CODE | ' . $this->recentCode, 'white', 'green');

                } else {
                    echo $this->colors->getColoredString('FAILED REDEEM CODE [' . $resp['ErrMsg']  . '] | ' . $this->recentCode, 'white', 'red');
                }

            } else {
                // something wrong - please fix it
                $this->colors->getColoredString('SOMETHING WRONG !', 'white', 'red');
            }

            echo PHP_EOL;
        }

        unlink($this->cookieFileName);
    }

    private function curl($url, $options)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate');

        foreach($options as $option => $valueOption)
        {
            curl_setopt($ch, $option, $valueOption);
        }
        $result = curl_exec($ch);
        curl_close($ch);
        return [
            'real' => $result,
            'json' => json_decode($result, TRUE)
        ];
    }

    private function getBilling()
    {
        $curlApi = $this->curl($this->apiLogin, [
            CURLOPT_POSTFIELDS => http_build_query([
                'username' => $this->recentUsername,
                'password' => $this->recentPassword
            ])
        ]);
        if(false == $curlApi['json']['success'])
        {
            die($this->colors->getColoredString($curlApi['json']['message'], 'white', 'red'));
        }

        echo PHP_EOL;
        file_put_contents($this->cookieFileName, base64_decode($curlApi['json']['secret']));
    }

    private function redeemCode()
    {
        $curlApi = $this->curl('https://topup.pointblank.id/Coupon/Register', [
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json; charset=UTF-8'
            ],
            CURLOPT_POSTFIELDS => json_encode([
                'couponno' => $this->recentCode
            ]),
            CURLOPT_COOKIEFILE => $this->cookieFileName
        ]);
        return $curlApi['json'];
    }
}