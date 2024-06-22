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
        return $this->queryPrivate('Balance');
    }

}

// Thanks, Now I want to monitor the the current ballance in each second by a graph chart by using javascript. Which plugin I can use for the graph chart? I want to make a shortcode for it, and will use this shortcode in any page. 