# Gelo VAT Update
This plugin is designed to automatically update the tax rate from 24 to 25.5 at Sep 1st 00.00.00 2024.

## Usage
 - Install the plugin and activate it in your application.
 - The plugin will automatically trigger the tax rate update on Sep 1st 2024.
 - After the update, the FI tax rate will be changed from 24 to 25.5.
 - Make sure that the tax has country code FI! Tax with country code * will not be updated.
 - After Sep 1st the plugin can be removed.

## Known issues
 - Uses wp scheduler, the action is actually triggered by the first page load after Sep 1st 00.00.00 2024 
 - No cache flushing whatsoever.
 - Only changes the tax rate value, does not change prices or tax rate name.

## Warranty
 - None. Use at your own risk.

## License
 - Public domain.
