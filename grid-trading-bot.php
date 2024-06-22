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



function trigger_buy_based_on_volume() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'ticker_data';

    $volume_data = $wpdb->get_results("SELECT volume FROM $table_name ORDER BY time DESC LIMIT 1");
    $current_volume = $volume_data ? $volume_data[0]->volume : 0;

    $previous_data = $wpdb->get_results("SELECT volume FROM $table_name ORDER BY time DESC LIMIT 2");
    $previous_volume = (count($previous_data) > 1) ? $previous_data[1]->volume : 0;

    if ($previous_volume > 0 && ($current_volume / $previous_volume) >= 1.05) {
        // Trigger buy order here
    }
}

add_action('fetch_and_save_ticker_data', 'trigger_buy_based_on_volume');



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
    global $wpdb;
    $table_name = $wpdb->prefix . 'ticker_data';
    $results = $wpdb->get_results("SELECT time, price, volume FROM $table_name ORDER BY time DESC LIMIT 70");
  
    // Format time to AM/PM format with seconds
    $time_labels = array_map(function($item) {
      return date("g:i:s A", strtotime($item->time));
    }, array_reverse($results));
  
    $prices = array_reverse(array_column($results, 'price'));
    $volumes = array_reverse(array_column($results, 'volume'));
  
    ob_start(); ?>
    <canvas id="kraken-ticker-chart" style="width: 100%; height: 550px;"></canvas>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('kraken-ticker-chart').getContext('2d');
        const chart = new Chart(ctx, {
          type: 'line',
          data: {
            labels: <?php echo json_encode($time_labels); ?>,
            datasets: [{
              label: 'ETH/USDT Price',
              data: <?php echo json_encode($prices); ?>,
              borderColor: 'rgba(75, 192, 192, 1)',
              borderWidth: 1
            }, {
              label: '24h Volume',
              data: <?php echo json_encode($volumes); ?>,
              borderColor: 'rgba(192, 75, 192, 1)',
              borderWidth: 1,
              yAxisID: 'y-axis-2'
            }]
          },
          options: {
            scales: {
              x: {
                time: {
                  unit: 'second', // Display seconds on the x-axis
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
              },
              'y-axis-2': {
                type: 'linear',
                position: 'right',
                title: {
                  display: true,
                  text: 'Volume'
                }
              }
            }
          }
        });
  
        async function fetchLiveTickerData() {
          const response = await fetch('<?php echo admin_url('admin-ajax.php?action=fetch_live_ticker_data'); ?>');
          const tickerData = await response.json();
  
          if (tickerData.error) {
            console.error('Error fetching ticker:', tickerData.error);
            return;
          }
  
          const data = tickerData.data;
          const labels = [];
          const prices = [];
          const volumes = [];
  
          data.forEach(entry => {
            const time = new Date(entry.time);
            labels.push(time.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true }));
            prices.push(entry.price);
            volumes.push(entry.volume);
          });
  
          chart.data.labels = labels;
          chart.data.datasets[0].data = prices;
          chart.data.datasets[1].data = volumes;
          chart.update();
        }
  
        fetchLiveTickerData(); // Initial fetch
        setInterval(fetchLiveTickerData, 5000); // Fetch every 5 seconds
      });
    </script>
    <?php
    return ob_get_clean();
}

add_shortcode('kraken_ticker_live', 'kraken_ticker_live_shortcode');