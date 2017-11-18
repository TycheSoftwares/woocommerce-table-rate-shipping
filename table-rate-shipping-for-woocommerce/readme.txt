=== WooCommerce Table Rate Shipping by Mangohour  ===
Contributors: mangohour
Donate link: https://mangohour.com/plugins/woocommerce-table-rate-shipping?utm_source=wp-plugin-repo&utm_medium=table-rate-shipping-for-woocommerce&utm_campaign=donate-link
Tags: country, weight, cart total, price, woocommerce, table rate, shipping, calculator
Requires at least: 3.8
Tested up to: 4.5
Stable tag: trunk

Calculate shipping costs based on destination, weight and/or cart total. Supports unlimited country groups and rates.

== Description ==

This WooCommerce plugin is for anyone who needs to calculate shipping costs based on destination, weight and/or cart total. 

= Features =
* Unlimited shipping zones (country groups)
* Unlimited rates
* Tier your shipping costs based on cart weight, or cart sub-total
* Handling fees

= Premium Features =
* State-based shipping zones
* ZIP/postcode/postal code shipping zones
* Shipping classes for products, allowing complex rules
* Delivery options (e.g. “Standard” or “Express” delivery)
* Quantity based rates
* Per item/kg/lbs costs
* Advanced tax/VAT support
* Percentage costs
* Easy backups
* Priority support

[Upgrade to premium version](https://mangohour.com/plugins/woocommerce-table-rate-shipping?utm_source=wp-plugin-repo&utm_medium=table-rate-shipping-for-woocommerce&utm_campaign=readme-link)

= Requirements =

This plugin is compatible with WooCommerce 2.1 or above.

= Translations =

Please visit our [localization project](https://poeditor.com/join/project/L4mPf0WmZR) if you would like to help translate the plugin.

= Feedback and Questions =

Please post any issues or questions to our [support forum](https://wordpress.org/support/plugin/table-rate-shipping-for-woocommerce). If you find this plugin useful, please consider supporting our work by [upgrading to premium](https://mangohour.com/plugins/woocommerce-table-rate-shipping?utm_source=wp-plugin-repo&utm_medium=table-rate-shipping-for-woocommerce&utm_campaign=readme-link) or [leaving a review](https://wordpress.org/support/view/plugin-reviews/table-rate-shipping-for-woocommerce).

== Installation ==

1. Upload the `table-rate-shipping-for-woocommerce` directory to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Activate the shipping method by going to WooCommerce > Settings > Shipping > Table Rate
4. Tick 'Enable this shipping method'
5. Configure your shipping rates and hit 'Save changes' when finished

After adding a shipping zone (country group), remember to hit 'Save changes' before proceeding to use it in a table rate.

== Frequently Asked Questions ==

= What currencies / weight units are supported? =

The plugin is compatible with any WooCommerce supported currency/weight unit.

= How do I translate the plugin? =

Please join our [translation project](https://poeditor.com/join/project/L4mPf0WmZR) if you would like to help us translate the plugin. 

= How does the 'Everywhere Else' zone work? =

When a table rate uses 'Everywhere Else' as its zone, the table rate will only be applicable if the destination country does not appear in any defined shipping zone.

= I just want to calculate shipping costs based on destination, can I do this? =

Yes, just create a table rate using the zone, enter '0' for min and '*' for max. This will capture all carts destined for that shipping zone. '*' can be used in any max column to indicate there is no upper limit.

= What if multiple table rates are applicable for a cart?  =

The cheapest applicable rate will be chosen automatically for the customer.

= I receive an 'Action failed' error after adding over 100 rates?  =

The error is due to a limit imposed by your server setup with regards to how much data you can send. Find your `php.ini` file and increase the setting called `max_input_vars` to at least 5000. 

== Screenshots ==

1. Settings screen
2. Cart example, using shown settings

== Changelog ==

= 1.2.1 =
* Added filters to improve compatibility with other plugins.

= 1.2.0 =
* Multiple packages support.
* Included .pot file for translations.
* Interface tweaks.

= 1.1.0 =
* Improved compatibility with WooCommerce 2.3.
* Multi-site support.
* Interface tweaks.
* Performance improvements.

= 1.0.2 =
* Fixed tax calculation bug.

= 1.0.1 =
* Improved readme.txt formatting.

= 1.0.0 =
* First release.