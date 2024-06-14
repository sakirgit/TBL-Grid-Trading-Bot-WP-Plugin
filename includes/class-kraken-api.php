<?php
class KrakenAPI
{
    private $apiKey;
    private $apiSecret;

    public function __construct($apiKey, $apiSecret)
    {
        $this->apiKey = $apiKey;
        $this->apiSecret = $apiSecret;
    }

    private function getKrakenSignature($path, $nonce, $postdata)
    {
        $message = $nonce . $postdata;
        $hash = hash_hmac('sha512', $path . hash('sha256', $message, true), base64_decode($this->apiSecret), true);
        return base64_encode($hash);
    }

    public function queryPrivate($method, $params = [])
    {
        $url = 'https://api.kraken.com/0/private/' . $method;
        $nonce = explode(' ', microtime())[1] . '000';
        $params['nonce'] = $nonce;

        $postdata = http_build_query($params, '', '&');
        $signature = $this->getKrakenSignature('/0/private/' . $method, $nonce, $postdata);

        $headers = [
            'API-Key' => $this->apiKey,
            'API-Sign' => $signature,
            'Content-Type' => 'application/x-www-form-urlencoded'
        ];

        $response = wp_remote_post($url, [
            'headers' => $headers,
            'body' => $postdata,
        ]);

        if (is_wp_error($response)) {
            return false;
        }

        return json_decode(wp_remote_retrieve_body($response), true);
    }
}
?>
