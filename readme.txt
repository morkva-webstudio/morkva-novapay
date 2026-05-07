=== morkva NovaPay ===
Contributors: bandido, dpmine
Tags: woocommerce, novapay
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 0.2.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

NovaPay payment gateway for WooCommerce. Supports HPOS, Block Checkout, postback verification, and a hardened thank-you sync.

== Description ==

morkva NovaPay integrates the NovaPay Internet Acquiring API as a WooCommerce payment method. After clicking Pay at checkout the customer is redirected to NovaPay's hosted page, and is automatically returned to the order-received page once the transaction is finalized.

= Features =

* Classic checkout and WooCommerce Blocks (Cart / Checkout) support
* HPOS (High-Performance Order Storage) compatibility
* Sandbox and production environments — toggle from a single checkbox
* Status sync fallback on the order-received page — protects against missed or delayed postbacks
* Auto-redirect from NovaPay back to the shop with configurable delay (default 3s) for cleaner analytics attribution
* Order status mapping: `paid` → processing/completed, `holded` → on-hold, `failed` → failed, `voided` / `expired` → cancelled
* Order meta box on the order edit screen showing: session ID, terminal, processing result, RRN, approval code, masked card PAN, card type, issuer bank
* Card, NovaPay wallet, Apple Pay and Google Pay supported through NovaPay's hosted checkout
* Phone normalization for Ukrainian numbers (`+380…`, `380…`, `80…`, `0…`, or 9-digit local)
* Developer filters: `mrkv_novapay_client_phone`, `mrkv_novapay_api_base_url`
* Full request / response and postback logging via `WC_Logger` (WooCommerce → Status → Logs, source `morkva-novapay`)

= Requirements =

* WooCommerce 6.5 or later
* PHP 7.4 or later, OpenSSL extension enabled
* A NovaPay merchant account with API credentials (merchant ID, RSA private key, NovaPay public key)

== Installation ==

1. Upload the `morkva-novapay` folder to `/wp-content/plugins/`, or install the ZIP via the WordPress admin.
2. Activate the plugin through the **Plugins** screen.
3. Go to **WooCommerce → Settings → Payments → NovaPay by morkva**.
4. Enable the gateway, paste your Merchant ID, merchant private key (PEM) and the NovaPay public key (PEM).
5. Copy the **Postback URL** shown in the settings and paste it into your NovaPay merchant cabinet as the callback endpoint.
6. (Optional) Enable **Test mode** while integrating with the sandbox environment.

For more context see our [documentation page](https://docs.morkva.co.ua/uk/plugins/nalashtuvannia-plaghina-morkva-nova-pay).

= For developers =

Plugin Documentation: https://morkva.helpcrunch.com/knowledge-base/uk/articles/173

== Frequently Asked Questions ==

= How do I obtain merchant credentials? =
Through your NovaPay merchant account. The sandbox environment credentials with test keys provided in the NovaPay API documentation.

= What's the difference between Test mode on and off? =
With Test mode on, API requests go to `https://api-qecom.novapay.ua` (sandbox). With it off, requests go to `https://api-ecom.novapay.ua` (production). If your assigned production host differs, override it via the `mrkv_novapay_api_base_url` filter.

= Does the plugin support refunds? =
Not in this release. Refunds are planned.

= What happens if the postback is delayed or lost? =
On the order-received page the plugin queries NovaPay's `get-status` endpoint and updates the order accordingly, so the status is synced even when the server-to-server postback is delayed, blocked, or lost.

== Changelog ==

= 0.2.0 =
* Full Internet Acquiring integration: create-session, add-payment, get-status, postback handling
* HPOS and Block Checkout compatibility declared
* Sandbox / production environment switch
* Postback v3 signature verification (RSA-SHA256 over raw body, header `x-sign-v2`)
* Order meta box with payment details (RRN, approval, card, terminal, processing result)
* Status sync fallback on the order-received page
* Configurable auto-redirect delay
* Ukrainian phone normalization
* Developer filters for phone formatting and API base URL

= 0.1.0 =
* Initial release