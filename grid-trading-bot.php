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
   echo '<p>Current Bitcoin Price: $' . esc_html($currentPrice) . '</p>';

   echo '<pre>'; 
 //  print_r($bot->getBitcoinPriceData()); 
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


