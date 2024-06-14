<?php
use GuzzleHttp\Client;
class GridTradingBot
{
   private $apiKey;
   private $apiSecret;
   private $gridSize;
   private $lowerLimit;
   private $upperLimit;
   private $numGrids;
   private $client;

   public function __construct($apiKey, $apiSecret, $gridSize, $lowerLimit, $upperLimit, $numGrids)
   {
      $this->apiKey = $apiKey;
      $this->apiSecret = $apiSecret;
      $this->gridSize = $gridSize;
      $this->lowerLimit = $lowerLimit;
      $this->upperLimit = $upperLimit;
      $this->numGrids = $numGrids;
      $this->client = new Client();
   }

   public function placeOrder($side, $price, $quantity) {
      $client = new Client();
      $response = $client->post('https://api.binance.com/api/v3/order', [
          'headers' => [
              'X-MBX-APIKEY' => $this->apiKey,
          ],
          'form_params' => [
              'symbol' => 'ETHUSDT',
              'side' => $side,
              'type' => 'LIMIT',
              'timeInForce' => 'GTC',
              'quantity' => $quantity,
              'price' => $price,
          ],
      ]);
  
      return json_decode($response->getBody(), true);
  }

   public function run()
   {
      $currentPrice = $this->getEthereumPrice();
      if (!$currentPrice) {
          error_log('Failed to fetch Ethereum price from CoinGecko');
          return;
      }
  
      $gridLevels = [];
      $stepSize = ($this->upperLimit - $this->lowerLimit) / $this->numGrids;
      for ($i = 0; $i <= $this->numGrids; $i++) {
          $gridLevels[] = $this->lowerLimit + ($i * $stepSize);
      }
  
      foreach ($gridLevels as $level) {
          if ($currentPrice <= $level) {
              $this->placeOrder('BUY', $level, 0.01);
          } elseif ($currentPrice >= $level) {
              $this->placeOrder('SELL', $level, 0.01);
          }
      }
   }

   private function placeGridOrders($currentPrice)
   {
      // Calculate grid levels and place orders using $currentPrice
   }


   private function getCoinGeckoData($endpoint, $params = [])
   {
      $url = 'https://api.coingecko.com/api/v3' . $endpoint;

      try {
          $response = $this->client->request('GET', $url, [
              'headers' => [
                  'accept' => 'application/json',
                  'x-cg-pro-api-key' => $this->apiKey, // Use the API key from settings
              ],
              'query' => $params,
          ]);
          return json_decode($response->getBody(), true);
      } catch (Exception $e) {
          error_log('CoinGecko API request failed: ' . $e->getMessage());
          return false;
      }
   }

   public function get_top_gainers_losers()
   {
      $data = $this->getCoinGeckoData('/coins/top_gainers_losers', [
          'ids' => 'top_gainers'
      ]);

      if ($data && isset($data['top_gainers'])) {
          return $data['top_gainers'];
      }

      return false;
   }


   public function getBitcoinPrice()
   {
      $data = $this->getCoinGeckoData('/simple/price', [
          'ids' => 'bitcoin',
          'vs_currencies' => 'usd'
      ]);

      if ($data && isset($data['bitcoin']['usd'])) {
          return $data['bitcoin']['usd'];
      }

      return false;
   }



   public function getBitcoinPriceData() {
      $data = $this->getCoinGeckoData('/simple/price',[
        'ids' => 'bitcoin',
          'vs_currencies' => 'usd'
    ]);
  
      if ($data && isset($data['bitcoin'])) {
          return $data;
      }
  
      return false;
  }

   public function getEthereumPrice() {
      $data = $this->getCoinGeckoData('/simple/price', [
          'ids' => 'ethereum',
          'vs_currencies' => 'usd'
      ]);
  
      if ($data && isset($data['ethereum']['usd'])) {
          return $data['ethereum']['usd'];
      }
  
      return false;
  }


}
