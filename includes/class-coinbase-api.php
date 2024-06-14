<?php
class CoinbaseAPI
{
    private $apiKey;

    public function __construct($apiKey)
    {
        $this->apiKey = $apiKey;
    }

    public function placeOrder($side, $price, $quantity, $productId = 'ETH-USDT')
    {
        $order = [
            'side' => $side,
            'price' => $price,
            'size' => $quantity,
            'product_id' => $productId,
        ];

        $response = $this->postOrder($order);

        if ($response) {
            error_log("Order placed: " . json_encode($response));
            return $response;
        } else {
            error_log("Failed to place order");
            return false;
        }
    }

    private function postOrder($order)
    {
        $url = 'https://api.coinbase.com/api/v3/orders';
        $body = json_encode($order);
        $response = wp_remote_post($url, [
            'body' => $body,
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->apiKey,
            ],
        ]);

        if (is_wp_error($response)) {
            return false;
        }

        return json_decode(wp_remote_retrieve_body($response), true);
    }
}
