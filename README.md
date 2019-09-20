# Claudio Sanches - MundiPagg for WooCommerce #
**Contributors:** [claudiosanches](https://profiles.wordpress.org/claudiosanches)  
**Donate link:** https://claudiosanches.com/doacoes/  
**Tags:** woocommerce, payment gateway  
**Requires at least:** 4.0  
**Tested up to:** 5.2  
**Stable tag:** 2.2.0  
**License:** GPLv2 or later  
**License URI:** http://www.gnu.org/licenses/gpl-2.0.html  

Adds MundiPagg gateway to your WooCommerce store

## Description ##

This plugin adds [MundiPagg](http://www.mundipagg.com.br/) gateway to your WooCommerce store.

This plugin was developed without any incentive from MundiPagg. We developed this plugin based on the [MundiPagg official documentation](http://docs.mundipagg.com/).

Note that this plugin still uses the MundiPagg SOAP service and will be updated to the new REST API when possible.

### Compatibility ###

- [WooCommerce](https://wordpress.org/plugins/woocommerce/) 2.3 or later (yes, this includes support for 2.6).
- [WooCommerce Extra Checkout Fields for Brazil](http://wordpress.org/plugins/woocommerce-extra-checkout-fields-for-brazil/).

### Install Process: ###

Check our [installation guide](http://wordpress.org/plugins/woocommerce-mundipagg/installation/).

### Questions? ###

- First of all, make sure if your question has already been answered in our [FAQ](http://wordpress.org/plugins/woocommerce-mundipagg/faq/).
- Still have question? Create a topic in your [support forum](http://wordpress.org/support/plugin/woocommerce-mundipagg).
- Found a bug? Report in our [GitHub page](https://github.com/claudiosmweb/woocommerce-mundipagg/issues).

Usually I don't have time to reply support topics, so be patient.

### Contribute ###

You can contribute to the source code in our [GitHub](https://github.com/claudiosmweb/woocommerce-mundipagg) page.

### Things should be done in the next versions ###

- Integrate with the new MundiPagg REST API.
- Allow payments with multiple credit cards.
- Integration with WooCommerce Subscriptions.

## Installation ##

- Upload plugin files to your plugins folder or install using WordPress built-in "Add New Plugin" installer.
- Activate the plugin.

### Requirements: ###

- A [MundiPagg](http://www.mundipagg.com.br/) account.
- Installed [WooCommerce](http://wordpress.org/plugins/woocommerce/) 2.3 or later (better 2.5).
- Installed the latest version of [WooCommerce Extra Checkout Fields for Brazil](http://wordpress.org/plugins/woocommerce-extra-checkout-fields-for-brazil/)
- [SOAP](php.net/manual/book.soap.php) installed in your server.
- And an SSL certificate and the "Force secure checkout" option enabled.

### MundiPagg Settings: ###

You need contact MundiPagg to register the return URL:

	http://example.com/?wc-mundipagg-return

Kind of obvious... But you need to change `example.com` for your domain!

### Plugin Settings: ###

Once the plugin is installed you need to go to "WooCommerce" > "Settings" > "Checkout" > "MundiPagg - Banking Ticket" or "MundiPagg - Credit Card" and check the enable the gateway and fill the options.

Do not forget about the Merchant Key option, required to plugin work and you can find it contacting the MundiPagg.

Now your store is ready to receive payments from MundiPagg.

## Frequently Asked Questions ##

### What is the plugin license? ###

* This plugin is released under a GPL license.

### What is needed to use this plugin? ###

- [WooCommerce](http://wordpress.org/plugins/woocommerce/) version 2.3 or latter installed and active.
- The latest version of [WooCommerce Extra Checkout Fields for Brazil](http://wordpress.org/plugins/woocommerce-extra-checkout-fields-for-brazil/) installed and active.
- An account on [MundiPagg](http://www.mundipagg.com.br/).
- Get your **Merchant Key** from MundiPagg.
- Set a notification page in MundiPagg.
- SOAP installed in your server.
- An SSL certificate.
- Enabled the "Force secure checkout" option in "WooCommerce" -> "Settings" -> "Checkout".

See more details in the [installation guide](http://wordpress.org/plugins/woocommerce-mundipagg/installation/).

### This plugins uses the new MundiPagg REST API? ###

Not yet, but we plan to integrate with the new REST API soon, probably by August 2016...

### The order was paid and got the status of "processing" and not as "complete"... There's something wrong? ###

Nop! In fact, this means that the plugin is working like expected.

All payment gateways in Woocommerce should change order status to "processing" when an order is paid and never change to "complete", because you should use the "complete" status just only after shipped your order.

If you are working with downloadable products you should turn on the "Grant access to downloadable products after payment" option in "WooCommerce" > "Settings" > "Products" > "Downloadable Products" page.

### Still having problems? ###

Turn on the "Debug Log" option, try make a payment again, then get your log file and paste the content in [pastebin.com](http://pastebin.com/) and start a [support forum topic](http://wordpress.org/support/plugin/woocommerce-mundipagg) with your pastebin link.

## Screenshots ##

### 1. Banking ticket settings. ###
![Banking ticket settings.](http://ps.w.org/woocommerce-mundipagg/assets/screenshot-1.png)

### 2. Credit card settings. ###
![Credit card settings.](http://ps.w.org/woocommerce-mundipagg/assets/screenshot-2.png)

### 3. Example of checkout using the Storefront theme. ###
![Example of checkout using the Storefront theme.](http://ps.w.org/woocommerce-mundipagg/assets/screenshot-3.png)


## Changelog ##

### 2.2.0 - 2019/09/20 ###

* Change plugin's name from "WooCommerce MundiPagg" to "Claudio Sanches - MundiPagg for WooCommerce".

### 2.1.2 - 2017/05/31 ###

* Included "OK" into IPN response (thanks to [J. Roque Junior](https://github.com/jroqueweb)).

### 2.1.1 - 2016/03/12 ###

* Added alert about SSL requirements on the transaction environment.

### 2.1.0 - 2016/02/15 ###

* Add support for WooCommerce 2.4 and 2.5.
* First public version.

## Upgrade Notice ##

### 2.2.2 ###

* Change plugin's name from "WooCommerce MundiPagg" to "Claudio Sanches - MundiPagg for WooCommerce".
