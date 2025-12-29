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
    // Clear any existing scheduled event first (in case of reactivation)
    wp_clear_scheduled_hook('gelo_vat_update_event');
    
    if (GELO_VAT_UPDATE_TEST_MODE) {
        $scheduled_time = strtotime('+1 minute');
        $result = wp_schedule_single_event($scheduled_time, 'gelo_vat_update_event');
        if ($result === false) {
            error_log('Gelo VAT Update ERROR: Failed to schedule test event');
        } else {
            error_log('Gelo VAT Update: Test mode activated, update scheduled for ' . date('Y-m-d H:i:s', $scheduled_time) . ' (timestamp: ' . $scheduled_time . ')');
            error_log('Gelo VAT Update: Note - WordPress cron requires a page visit after scheduled time to trigger');
        }
        return;
    }
    $scheduled_time = 1767218400; // Jan 1st 00.00.00 2026 Finnish time, not using strtotime to avoid timezone issues
    $result = wp_schedule_single_event($scheduled_time, 'gelo_vat_update_event');
    if ($result === false) {
        error_log('Gelo VAT Update ERROR: Failed to schedule event for ' . date('Y-m-d H:i:s', $scheduled_time));
    } else {
        error_log('Gelo VAT Update: Event scheduled for ' . date('Y-m-d H:i:s', $scheduled_time));
    }
}

// Helper function to check cron status (can be called manually for debugging)
function gelo_vat_update_check_cron_status() {
    $scheduled_time = wp_next_scheduled('gelo_vat_update_event');
    if ($scheduled_time === false) {
        error_log('Gelo VAT Update: No scheduled event found');
        return false;
    } else {
        $current_time = time();
        $time_until = $scheduled_time - $current_time;
        error_log('Gelo VAT Update: Event scheduled for ' . date('Y-m-d H:i:s', $scheduled_time));
        error_log('Gelo VAT Update: Current time is ' . date('Y-m-d H:i:s', $current_time));
        if ($time_until > 0) {
            error_log('Gelo VAT Update: Event will run in ' . round($time_until / 60, 1) . ' minutes');
        } else {
            error_log('Gelo VAT Update: Scheduled time has passed - event should run on next page visit');
        }
        return $scheduled_time;
    }
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
    error_log('Gelo VAT Update: Function called - Starting tax rate update from 14 to 13.5');
    
    global $wpdb;
    $prefix = $wpdb->prefix;
    $table_name = $prefix . 'woocommerce_tax_rates';
    
    // Check if table exists
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
    if (!$table_exists) {
        error_log('Gelo VAT Update ERROR: Table ' . $table_name . ' does not exist');
        return;
    }
    
    // Check how many rows match the criteria before update
    $rows_to_update = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table_name WHERE tax_rate = %f AND tax_rate_country = %s",
        14.0,
        'FI'
    ));
    error_log('Gelo VAT Update: Found ' . $rows_to_update . ' tax rate(s) with value 14.0 and country FI');
    
    // Perform the update
    $rows_affected = $wpdb->update(
        $table_name,
        array('tax_rate' => 13.5),
        array('tax_rate' => 14, 'tax_rate_country' => 'FI'),
        array('%f'),
        array('%f', '%s')
    );
    
    // Check for database errors
    if ($rows_affected === false) {
        error_log('Gelo VAT Update ERROR: Database update failed - ' . $wpdb->last_error);
        return;
    }
    
    // Log results
    if ($rows_affected === 0) {
        error_log('Gelo VAT Update WARNING: No rows were updated. This could mean:');
        error_log('  - No tax rates found with value 14.0 and country FI');
        error_log('  - Tax rates have already been updated');
        error_log('  - Tax rate values are stored differently (e.g., 14.00 vs 14.0)');
    } else {
        error_log('Gelo VAT Update SUCCESS: Updated ' . $rows_affected . ' tax rate(s) from 14 to 13.5');
    }
    
    // Clear all relevant caches after updating tax rate
    gelo_vat_update_clear_cache();
    
    remove_action('gelo_vat_update_event', 'gelo_vat_update_function');
    error_log('Gelo VAT Update: Function completed');
}
add_action('gelo_vat_update_event', 'gelo_vat_update_function');



// Plugin deactivation callback
function gelo_vat_update_deactivate() {
    wp_clear_scheduled_hook('gelo_vat_update_event');
    remove_action('gelo_vat_update_event', 'gelo_vat_update_function');  
}
