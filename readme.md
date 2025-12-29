# Gelo VAT Update for Woocommerce
This plugin is designed to automatically update the tax rate from 14 to 13.5 at Jan 1st 00.00.00 2026.

## Usage
 - Install the plugin and activate it.
 - The plugin will automatically trigger the tax rate update on Jan 1st 2026.
 - After the update, the FI tax rate will be changed from 14 to 13.5.
 - Make sure that the tax has country code FI! Tax with country code * will not be updated.
 - After Jan 1st the plugin can be removed.
 - Cache clearing (if particular cache is present): Woocommerce cache related transients, object cache, WP Rocket, W3 Total Cache, WP Super cache, Litespeed Cache, WP Fastest Cache 

## Known issues
 - Uses wp scheduler, the action is actually triggered by the first page load after Jan 1st 2026
 - Only changes the tax rate value, does not change prices or tax rate name.

## Warranty
 - None. Use at your own risk.

## License
 - Public domain.
