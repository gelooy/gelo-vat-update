<?php
/*
Plugin Name: Gelo VAT Update
Plugin URI: https://gelo.fi/
Description: A plugin to automatically update tax rate from 24 to 25.5 at Sep 1st 00.00.00 2024. The plugin does once-in-a-lifetime update and changes updates FI tax with value 24 to 25.5
Version: 1.0.1
Author: Tero Lahtinen/Gelo
Author URI: https://gelo.fi
License: GPL2
*/

define('GELO_VAT_UPDATE_TEST_MODE', false); // set to true to test the plugin, in test mode runs the update after one minute

register_activation_hook(__FILE__, 'gelo_vat_update_activate');
register_deactivation_hook(__FILE__, 'gelo_vat_update_deactivate');

// Plugin activation callback
function gelo_vat_update_activate() {
    add_action('gelo_vat_update_event', 'gelo_vat_update_function');
    if (GELO_VAT_UPDATE_TEST_MODE) {
        wp_schedule_single_event(strtotime('+1 minute'),  'gelo_vat_update_event');
        error_log('Gelo VAT Update plugin is in test mode, update will run after one minute');
        return;
    }
    wp_schedule_single_event(1725138000,  'gelo_vat_update_event'); // 1725138000 timestamp for Sep 1st 00.00.00 2024 Finnish time, not using strtotime to avoid timezone issues
}


// update tax rate from 24 to 25.5
function gelo_vat_update_function() {
    global $wpdb;
    $prefix = $wpdb->prefix;
    $table_name = $prefix . 'woocommerce_tax_rates';
    $wpdb->update(
        $table_name,
        array('tax_rate' => 25.5),
        array('tax_rate' => 24, 'tax_rate_country' => 'FI'),
        array('%f'),
        array('%f', '%s')
    ); 
    remove_action('gelo_vat_update_event', 'gelo_vat_update_function');  
}
add_action('gelo_vat_update_event', 'gelo_vat_update_function');



// Plugin deactivation callback
function gelo_vat_update_deactivate() {
    wp_clear_scheduled_hook('gelo_vat_update_event');
    remove_action('gelo_vat_update_event', 'gelo_vat_update_function');  
}
