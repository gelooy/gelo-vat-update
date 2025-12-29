<?php
/*
Plugin Name: Gelo VAT Update
Plugin URI: https://gelo.fi/
Description: A plugin to automatically update tax rate from 14 to 13.5 at Jan 1st 00.00.00 2026. The plugin does once-in-a-lifetime update and can be removed after the update. It does not change actual prices or tax rate names.
Version: 2.0.0
Author: Tero Lahtinen/Gelo
Author URI: https://gelo.fi
License: GPL2
*/

define('GELO_VAT_UPDATE_TEST_MODE', true); // set to true to test the plugin, in test mode runs the update after one minute

register_activation_hook(__FILE__, 'gelo_vat_update_activate');
register_deactivation_hook(__FILE__, 'gelo_vat_update_deactivate');

// Plugin activation callback
function gelo_vat_update_activate() {
    if (GELO_VAT_UPDATE_TEST_MODE) {
        wp_schedule_single_event(strtotime('+1 minute'),  'gelo_vat_update_event');
        error_log('Gelo VAT Update plugin is in test mode, update will run after one minute');
        return;
    }
    wp_schedule_single_event(1767218400,  'gelo_vat_update_event'); // 1767218400 timestamp for Jan 1st 00.00.00 2026 Finnish time, not using strtotime to avoid timezone issues
}


// Clear all relevant caches after tax rate update
function gelo_vat_update_clear_cache() {
    // Clear WooCommerce tax-related transients
    if (class_exists('WC_Cache_Helper')) {
        if (method_exists('WC_Cache_Helper', 'invalidate_cache_group')) {
            WC_Cache_Helper::invalidate_cache_group('taxes');
        }
    }
    
    // Clear WooCommerce transients (tax rates are cached as transients)
    global $wpdb;
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_wc_tax_rates%' OR option_name LIKE '_transient_timeout_wc_tax_rates%'");
    
    // Clear WordPress object cache
    if (function_exists('wp_cache_flush')) {
        wp_cache_flush();
    }
    
    // WP Rocket
    if (function_exists('rocket_clean_domain')) {
        rocket_clean_domain();
    }
    
    // W3 Total Cache
    if (function_exists('w3tc_flush_all')) {
        w3tc_flush_all();
    } elseif (function_exists('w3tc_pgcache_flush')) {
        w3tc_pgcache_flush();
    }
    
    // WP Super Cache
    if (function_exists('wp_cache_clear_cache')) {
        wp_cache_clear_cache();
    }
    
    // LiteSpeed Cache
    if (has_action('litespeed_purge_all')) {
        do_action('litespeed_purge_all');
    }
    
    // WP Fastest Cache
    if (class_exists('WpFastestCache')) {
        $wpfc = new WpFastestCache();
        if (method_exists($wpfc, 'deleteCache')) {
            $wpfc->deleteCache();
        }
    }
    
    error_log('Gelo VAT Update: Cache cleared after tax rate update');
}


// update tax rate from 14 to 13.5
function gelo_vat_update_function() {
    global $wpdb;
    $prefix = $wpdb->prefix;
    $table_name = $prefix . 'woocommerce_tax_rates';
    $wpdb->update(
        $table_name,
        array('tax_rate' => 13.5),
        array('tax_rate' => 14, 'tax_rate_country' => 'FI'),
        array('%f'),
        array('%f', '%s')
    ); 
    
    // Clear all relevant caches after updating tax rate
    gelo_vat_update_clear_cache();
    
    remove_action('gelo_vat_update_event', 'gelo_vat_update_function');  
}
add_action('gelo_vat_update_event', 'gelo_vat_update_function');



// Plugin deactivation callback
function gelo_vat_update_deactivate() {
    wp_clear_scheduled_hook('gelo_vat_update_event');
    remove_action('gelo_vat_update_event', 'gelo_vat_update_function');  
}
