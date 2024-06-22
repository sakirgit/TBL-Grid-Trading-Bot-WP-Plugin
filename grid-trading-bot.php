<?php
/*
Plugin Name: Grid Trading Bot
Description: A Bitcoin futures grid trading bot.
Version: 1.0
Author: Your Name
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Load Composer autoloader
require_once(plugin_dir_path(__FILE__) . 'vendor/autoload.php');

// Include necessary files
require_once(plugin_dir_path(__FILE__) . 'includes/class-grid-trading-bot.php');
require_once(plugin_dir_path(__FILE__) . 'includes/class-coinbase-api.php');
require_once(plugin_dir_path(__FILE__) . 'includes/class-kraken-api.php');

// Hook into WordPress
add_action('admin_menu', 'gtb_add_admin_menu');

function gtb_add_admin_menu() {
    add_menu_page('Grid Trading Bot', 'Grid Trading Bot', 'manage_options', 'grid-trading-bot', 'gtb_settings_page');
}
/* ===================================================================== */
function gtb_settings_page() {
   if (isset($_POST['save_settings'])) {
       update_option('gtb_api_key', sanitize_text_field($_POST['api_key']));
       if( isset($_POST['api_secret']) ) {
            update_option('gtb_api_secret', sanitize_text_field($_POST['api_secret']));
       }
       update_option('gtb_grid_size', intval($_POST['grid_size']));
       update_option('gtb_lower_limit', floatval($_POST['lower_limit']));
       update_option('gtb_upper_limit', floatval($_POST['upper_limit']));
       update_option('gtb_num_grids', intval($_POST['num_grids']));
       
       update_option('coinbase_api_key', sanitize_text_field($_POST['coinbase_api_key']));
       update_option('kraken_api_key', sanitize_text_field($_POST['kraken_api_key']));
        update_option('kraken_api_secret', sanitize_text_field($_POST['kraken_api_secret']));
       echo '<div class="updated"><p>Settings saved.</p></div>';
   }

   $apiKey = get_option('gtb_api_key');
   $apiSecret = get_option('gtb_api_secret');
   $gridSize = get_option('gtb_grid_size');
   $lowerLimit = get_option('gtb_lower_limit');
   $upperLimit = get_option('gtb_upper_limit');
   $numGrids = get_option('gtb_num_grids');

   $coinbase_api_key = get_option('coinbase_api_key');
   $kraken_api_key = get_option('kraken_api_key');
   $kraken_apiSecret = get_option('kraken_api_secret');

   $bot = new GridTradingBot($apiKey, $apiSecret, $gridSize, $lowerLimit, $upperLimit, $numGrids);
   $currentPrice = $bot->getBitcoinPrice();

   echo '<div class="wrap"><h1>Grid Trading Bot Settings</h1>';
//   echo '<p>Current Bitcoin Price: $' . esc_html($currentPrice) . '</p>';

   $kraken = new KrakenAPI(get_option('kraken_api_key'), get_option('kraken_api_secret'));
   $balance = $kraken->getBalance();


   $tickerData = $kraken->getTicker('ETHUSDT');


   if ($tickerData && isset($tickerData['result']['ETHUSDT']['v'][1])) {
      $volume = floatval($tickerData['result']['ETHUSDT']['v'][1]);
      $threshold = $volume * 0.05;
      print_r($volume);
      echo '<br>';
      print_r($threshold);
      echo '<br>';

      if ($volume >= $threshold) {
          $orderResponse = $kraken->addOrder('ETHUSDT', 'buy', 'limit', 0.01); // Example values
          echo '<div class="updated"><p>Buy order placed: </p></div>';
          print_r($orderResponse);
          echo '<div class="updated"><p>Volume: </p></div>';
      } else {
          echo '<div class="updated"><p>Volume is below the threshold.</p></div>';
      }
  } else {
    echo '<div class="updated"><p>Failed to fetch ticker data.</p></div>';
  }


  echo '<pre>'; 
  echo print_r($tickerData);
  echo '</pre>';




   echo '<pre>'; 
 //  print_r($bot->getBitcoinPriceData()); 
   echo '<p>Kraken Balance: ' . esc_html(print_r($balance, true)) . '</p>';
   echo '</pre>';

   echo '<form method="post">';
   echo '<table class="form-table">
           <tr><th>Kraken API Key</th><td><input type="text" name="kraken_api_key" class="regular-text" value="' . esc_attr($kraken_api_key) . '"></td></tr>
           <tr><th>Kraken API Secret</th><td><input type="text" name="kraken_api_secret" class="regular-text" value="' . esc_attr($kraken_apiSecret) . '"></td></tr>
           <tr><th></th></tr>
           <tr><th>CoinGecko API Key</th><td><input type="text" name="api_key" class="regular-text" value="' . esc_attr($apiKey) . '"></td></tr>
           <tr><th>API Secret</th><td><input type="text" name="api_secret" class="regular-text" value="' . esc_attr($apiSecret) . '" disabled></td></tr>
           <tr><th>Grid Size</th><td><input type="number" name="grid_size" class="regular-text" value="' . esc_attr($gridSize) . '"></td></tr>
           <tr><th>Lower Limit</th><td><input type="number" name="lower_limit" class="regular-text" value="' . esc_attr($lowerLimit) . '"></td></tr>
           <tr><th>Upper Limit</th><td><input type="number" name="upper_limit" class="regular-text" value="' . esc_attr($upperLimit) . '"></td></tr>
           <tr><th>Number of Grids</th><td><input type="number" name="num_grids" class="regular-text" value="' . esc_attr($numGrids) . '"></td></tr>
           <tr><th></th></tr>
           <tr><th>Coinbase API Key</th><td><input type="text" name="coinbase_api_key" class="regular-text" value="' . esc_attr($coinbase_api_key) . '"></td></tr>
         </table>';
   echo '<input type="submit" name="save_settings" value="Save Settings" class="button button-primary">';
   echo '</form></div>';
   
}


function enqueue_chartjs() {
    wp_enqueue_script('chartjs', 'https://cdn.jsdelivr.net/npm/chart.js', array(), null, true);
}
add_action('wp_enqueue_scripts', 'enqueue_chartjs');
/*
function kraken_balance_shortcode() {
    ob_start(); ?>
    <canvas id="kraken-balance-chart" style="width: 1000px; height: 700px;"></canvas>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns@3.0.0"></script>
    <script>
        async function fetchBalance_async() {
            const response = await fetch('<?php echo admin_url('admin-ajax.php?action=fetch_kraken_balance'); ?>');
            const balanceData = await response.json();
            if (balanceData.error) {
                console.log('chartcccc');
                console.error('Error fetching balance:', balanceData.error);
                return;
            }

            // Assuming 'USDT' is the key for USD balance
            const usdBalance = balanceData.balance.result['USDT'] ? parseFloat(balanceData.balance.result['USDT']) : 0;
            
            // Update the chart
            if (chart) {
                
                const now = new Date();
                const shortTime = now.toLocaleTimeString([], {hour: '2-digit', minute: '2-digit'});
                chart.data.labels.push(shortTime);
                chart.data.datasets[0].data.push(usdBalance);

                // Keep only the last 10 data points
                if (chart.data.labels.length > 10) {
                    chart.data.labels.shift();
                    chart.data.datasets[0].data.shift();
                }

                chart.update();
            }
        }


        async function fetchInitialBalance() {
            const response = await fetch('<?php echo admin_url('admin-ajax.php?action=fetch_kraken_balance'); ?>');
            const balanceData = await response.json();

            if (balanceData.error) {
                console.error('Error fetching initial balance:', balanceData.error);
                return { labels: [], data: [] };
            }

        //    console.log("balanceData.balance.result['USDT']", balanceData.data.balance.result['USDT']);

            const usdBalance = balanceData.data.balance.result['USDT'] ? parseFloat(balanceData.data.balance.result['USDT']) : 0;
            return { labels: [new Date().toLocaleTimeString()], data: [usdBalance] };
        }

        async function fetchBalance() {
            const response = await fetch('<?php echo admin_url('admin-ajax.php?action=fetch_kraken_balance'); ?>');
            const balanceData = await response.json();

            if (balanceData.error) {
                console.error('Error fetching balance:', balanceData.error);
                return;
            }

            const usdBalance = balanceData.data.balance.result['USDT'] ? parseFloat(balanceData.data.balance.result['USDT']) : 0;

            if (chart) {
                const now = new Date();
                chart.data.labels.push(now.toLocaleTimeString());
                chart.data.datasets[0].data.push(usdBalance);

                if (chart.data.labels.length > 10) {
                    chart.data.labels.shift();
                    chart.data.datasets[0].data.shift();
                }

                chart.update();
            }
        }


        let chart;
        document.addEventListener('DOMContentLoaded', async function() {
            const initialData = await fetchInitialBalance();
            console.log('initialData',initialData);
            const ctx = document.getElementById('kraken-balance-chart').getContext('2d');
            chart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: [new Date().toLocaleTimeString()], // Time labels
                    datasets: [{
                        label: 'USD Balance',
                        data: initialData.data, // Balance data
                        borderColor: 'rgba(75, 192, 192, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    scales: {
                        x: {
                            title: {
                                display: true,
                                text: 'Time'
                            },
                            type: 'time',
                            time: {
                                unit: 'minute'
                            }
                        },
                        y: {
                            title: {
                                display: true,
                                text: 'Balance (USD)'
                            }
                        }
                    }
                }
            });

            fetchBalance();
            setInterval(fetchBalance, 3000); // Fetch balance every second
        });
    </script>
    <?php
    return ob_get_clean();
}

add_shortcode('kraken_balance', 'kraken_balance_shortcode');
*/


function fetch_kraken_balance() {
    // Your code to fetch the balance from Kraken API
    $kraken = new KrakenAPI(get_option('kraken_api_key'), get_option('kraken_api_secret'));
    $balance = $kraken->getTicker("XBTUSD"); // Assume getBalance() returns the balance

    if ($balance) {
        wp_send_json_success(['balance' => $balance]);
    } else {
        wp_send_json_error('Failed to fetch balance');
    }
}
add_action('wp_ajax_fetch_kraken_balance', 'fetch_kraken_balance');
add_action('wp_ajax_nopriv_fetch_kraken_balance', 'fetch_kraken_balance');


/* ================================================================================================= */
/* ================================================================================================= */



function kraken_ticker_shortcode() {
    ob_start(); ?>
    <canvas id="kraken-ticker-chart" style="width: 100%; height: 400px;"></canvas>
    <div id="kraken-ticker"></div>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns"></script>
    <script>
      async function fetchInitialTickerData() {
        const response = await fetch('<?php echo admin_url('admin-ajax.php?action=fetch_kraken_ticker&nonce=' . wp_create_nonce('fetch_kraken_ticker')); ?>');
        const data = await response.json();
  
        if (data.error) {
          console.error('Error fetching initial data:', data.error);
          return { labels: [], data: [], ticker: {} };
        }
  
        const ticker = data.ticker;
  
        return { labels: [new Date().toLocaleTimeString()], data: [parseFloat(ticker.result.ETHUSDT.v[0])], ticker: ticker };
      }
  
      async function fetchTickerData() {
        const response = await fetch('<?php echo admin_url('admin-ajax.php?action=fetch_kraken_ticker&nonce=' . wp_create_nonce('fetch_kraken_ticker')); ?>');
        const data = await response.json();
  
        if (data.error) {
          console.error('Error fetching data:', data.error);
          return;
        }
  
        const ticker = data.ticker;
        const tickerPrice = parseFloat(ticker.result.ETHUSDT.v[0]);
  
        if (chart) {
          const now = new Date();
          chart.data.labels.push(now.toLocaleTimeString());
          chart.data.datasets[0].data.push(tickerPrice);
  
          if (chart.data.labels.length > 50) {
            chart.data.labels.shift();
            chart.data.datasets[0].data.shift();
          }
  
          chart.update();
        }
  
        // Update ticker data
        document.getElementById('kraken-ticker').innerHTML = `
          <strong>ETH/USDT:</strong> ${ticker.result.ETHUSDT.v[0]} USD
        `;
      }
  
      let chart;
      document.addEventListener('DOMContentLoaded', async function() {
        const initialData = await fetchInitialTickerData();
  
        const ctx = document.getElementById('kraken-ticker-chart').getContext('2d');
        chart = new Chart(ctx, {
          type: 'line',
          data: {
            labels: initialData.labels,
            datasets: [{
              label: 'ETH/USDT Price',
              data: initialData.data,
              borderColor: 'rgba(75, 192, 192, 1)',
              borderWidth: 1
            }]
          },
          options: {
            scales: {
              x: {
                time: {
                  unit: 'second'
                },
                title: {
                  display: true,
                  text: 'Time'
                }
              },
              y: {
                title: {
                  display: true,
                  text: 'Price (USD)'
                }
              }
            }
          }
        });
  
        // Display initial ticker data
        document.getElementById('kraken-ticker').innerHTML = `
          <strong>ETH/USDT:</strong> ${initialData.ticker.result.ETHUSDT.v[0]} USD
        `;
  
        fetchTickerData();
        setInterval(fetchTickerData, 5000);
      });
    </script>
    <?php
    return ob_get_clean();
  }

add_shortcode('kraken_ticker', 'kraken_ticker_shortcode');

function fetch_kraken_ticker() {
    $kraken = new KrakenAPI(get_option('kraken_api_key'), get_option('kraken_api_secret'));
    $ticker = $kraken->getTicker('ETHUSDT');
    
    $response = [
        'ticker' => $ticker
    ];
    
    wp_send_json($response);
}
add_action('wp_ajax_fetch_kraken_ticker', 'fetch_kraken_ticker');
add_action('wp_ajax_nopriv_fetch_kraken_ticker', 'fetch_kraken_ticker');





/* ================================================================================================= */
/* ============= Add a function to fetch and save ticker data to the database: ===================== */
/* ================================================================================================= */
function create_ticker_data_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'ticker_data';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        time datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        price float NOT NULL,
        volume float NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
register_activation_hook(__FILE__, 'create_ticker_data_table');

// Register custom cron schedule
function custom_cron_schedules($schedules) {
    $schedules['every_five_seconds'] = array(
        'interval' => 5,
        'display' => __('Every 5 Seconds')
    );
    return $schedules;
}
add_filter('cron_schedules', 'custom_cron_schedules');

// Schedule the cron event
function schedule_ticker_cron() {
    if (!wp_next_scheduled('fetch_ticker_data_event')) {
        wp_schedule_event(time(), 'every_five_seconds', 'fetch_ticker_data_event');
    }
}
add_action('wp', 'schedule_ticker_cron');

// Unschedule the cron event on plugin deactivation
function unschedule_ticker_cron() {
    $timestamp = wp_next_scheduled('fetch_ticker_data_event');
    if ($timestamp) {
        wp_unschedule_event($timestamp, 'fetch_ticker_data_event');
    }
}
register_deactivation_hook(__FILE__, 'unschedule_ticker_cron');

// Fetch and save ticker data
function fetch_and_save_ticker_data() {
    // Ensure the KrakenAPI class is loaded
    if (!class_exists('KrakenAPI')) {
        require_once plugin_dir_path(__FILE__) . 'includes/class-kraken-api.php';
    }

    // Get API key and secret from options
    $kraken_api_key = get_option('kraken_api_key');
    $kraken_api_secret = get_option('kraken_api_secret');

    // Create a new instance of the KrakenAPI class
    $kraken = new KrakenAPI($kraken_api_key, $kraken_api_secret);

    // Fetch ticker data
    $ticker_data = $kraken->getTicker('ETHUSDT');

    /*
    if (!is_wp_error($ticker_data) && isset($ticker_data['result']['ETHUSDT']['v'][0])) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ticker_data';
        $price = floatval($ticker_data['result']['ETHUSDT']['v'][0]);

        // Insert the ticker data into the database
        $wpdb->insert(
            $table_name,
            array(
                'time' => current_time('mysql'),
                'price' => $price
            )
        );
    } else {
        error_log('Failed to fetch ticker data.');
    }
    */
    if (isset($ticker_data['result']['ETHUSDT'])) {
        $price = $ticker_data['result']['ETHUSDT']['c'][0];
        $volume = $ticker_data['result']['ETHUSDT']['v'][1]; // 24h volume

        global $wpdb;
        $table_name = $wpdb->prefix . 'ticker_data';
        $wpdb->insert($table_name, [
            'time' => current_time('mysql'),
            'price' => $price,
            'volume' => $volume
        ]);
    }
}
add_action('fetch_ticker_data_event', 'fetch_and_save_ticker_data');

// Fetch data for the live chart
function fetch_live_ticker_data() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'ticker_data';

    $data = $wpdb->get_results("SELECT * FROM $table_name ORDER BY time DESC LIMIT 70", ARRAY_A);

    if ($data) {
        wp_send_json_success(array_reverse($data)); // Reversing to get chronological order
    } else {
        wp_send_json_error('No data found');
    }
}
add_action('wp_ajax_fetch_live_ticker_data', 'fetch_live_ticker_data');
add_action('wp_ajax_nopriv_fetch_live_ticker_data', 'fetch_live_ticker_data');


// Shortcode function for displaying the live chart
function kraken_ticker_live_shortcode() {
    
    ob_start(); ?>
    <canvas id="kraken-ticker-chart" style="width: 100%; height: 500px;"></canvas>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        async function fetchLiveTickerData() {
            const response = await fetch('<?php echo admin_url('admin-ajax.php?action=fetch_live_ticker_data'); ?>');
            const tickerData = await response.json();

            if (tickerData.success) {
                // Initialize the chart with the fetched ticker data
                const labels = tickerData.data.map(item => new Date(item.time).toLocaleTimeString());
                const prices = tickerData.data.map(item => item.price);

                if (chart) {
                    chart.data.labels = labels;
                    chart.data.datasets[0].data = prices;
                    chart.update();
                }
            } else {
                console.error('Error fetching ticker data:', tickerData.data);
            }
        }

        let chart;
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('kraken-ticker-chart').getContext('2d');
            chart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: [], // Time labels
                    datasets: [{
                        label: 'ETH/USDT Price',
                        data: [], // Price data
                        borderColor: 'rgba(75, 192, 192, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    scales: {
                        x: {
                            time: {
                                unit: 'second'
                            },
                            title: {
                                display: true,
                                text: 'Time'
                            }
                        },
                        y: {
                            title: {
                                display: true,
                                text: 'Price (USDT)'
                            }
                        }
                    }
                }
            });

            fetchLiveTickerData();
            setInterval(fetchLiveTickerData, 5000); // Fetch live ticker data every 5 seconds
        });
    </script>
    <?php
    return ob_get_clean();
}

add_shortcode('kraken_ticker_live', 'kraken_ticker_live_shortcode');