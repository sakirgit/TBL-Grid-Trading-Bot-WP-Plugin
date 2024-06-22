<?php
class KrakenAPI
{
    private $apiKey;
    private $apiSecret;

    public function __construct($apiKey, $apiSecret) {
        $this->apiKey = $apiKey;
        $this->apiSecret = $apiSecret;
    }

    private function getKrakenSignature($path, $nonce, $postdata)
    {
        $message = hash('sha256', $nonce . $postdata, true);
        $hash = hash_hmac('sha512', $path . $message, base64_decode($this->apiSecret), true);
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



    public function getTicker($pair) {
        $url = 'https://api.kraken.com/0/public/Ticker?pair=' . $pair;
        $response = wp_remote_get($url);
        if (is_wp_error($response)) {
            return false;
        }
        return json_decode(wp_remote_retrieve_body($response), true);
    }

    
    public function addOrder($pair, $type, $ordertype, $volume) {
        $params = [
            'pair' => $pair,
            'type' => $type,
            'ordertype' => $ordertype,
            'volume' => $volume,
        ];
        return $this->queryPrivate('AddOrder', $params);
    }

    public function getBalance() {
     //   return $this->queryPrivate('Balance');



        $path = '/0/private/Balance';
        $nonce = explode(' ', microtime())[1] . str_pad(explode(' ', microtime())[0] * 1000000, 6, '0', STR_PAD_LEFT);
    
        $postData = http_build_query([
            'nonce' => $nonce,
        ], '', '&');
    
        $signature = hash_hmac('sha512', $path . hash('sha256', $nonce . $postData, true), base64_decode($this->apiSecret), true);
        $headers = [
            'API-Key: ' . $this->apiKey,
            'API-Sign: ' . base64_encode($signature)
        ];
    
        $ch = curl_init('https://api.kraken.com' . $path);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        $response = curl_exec($ch);
        curl_close($ch);
    
        return json_decode($response, true);

    }

}

