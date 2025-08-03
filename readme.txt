=== Show Product Tab as Category ===
Contributors: developersaiful
Tags: woocommerce, product tabs, categories, shortcode, e-commerce, hpos
Requires at least: 5.2
Tested up to: 6.6
Stable tag: 1.6.2
Requires PHP: 7.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Display WooCommerce products in a tabbed interface based on categories with search, pagination, and admin settings. Fully compatible with WooCommerce High-Performance Order Storage (HPOS).

== Description ==

Show Product Tab as Category is a WooCommerce plugin that allows you to display products in a tabbed interface, organized by categories. It includes a search bar, pagination, and an admin settings page to customize the display. Use the `[product_tab_category]` shortcode to add this feature to any page or post.

**Features:**
- Display products in tabs for "All Products" and specific categories.
- AJAX-powered search to filter products within categories.
- Pagination for navigating through multiple pages of products.
- Admin settings to configure:
  - Number of products per page.
  - Maximum number of categories to display.
  - Specific categories to show (via checkboxes).
  - Option to show/hide the search bar.
- Compatible with WooCommerce High-Performance Order Storage (HPOS).

This plugin is fully translatable, follows WordPress coding standards, and is optimized for performance with WooCommerce stores.

== Installation ==

1. Upload the `show-product-tab-as-category` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to `Product Tabs` in the WordPress admin menu to configure settings.
4. Use the shortcode `[product_tab_category]` in any page or post to display the tabbed product interface.

== Frequently Asked Questions ==

= How do I use the plugin? =

Add the `[product_tab_category]` shortcode to any page or post. Configure settings under the `Product Tabs` menu in the WordPress admin panel.

= Can I customize the number of products displayed? =

Yes, go to `Product Tabs` in the admin panel and set the "Products Per Page" option.

= Can I choose specific categories to display? =

Yes, use the "Select Specific Categories" checkboxes in the admin settings to choose which categories to show.

= Is the plugin compatible with WooCommerce High-Performance Order Storage (HPOS)? =

Yes, as of version 1.6.1, the plugin is fully compatible with WooCommerce HPOS.

= Does the plugin support translations? =

Yes, the plugin is fully translatable. Translation files can be placed in the `languages` folder.

== Screenshots ==

1. Frontend view of the tabbed product interface with search and pagination.
2. Admin settings page for configuring the plugin.

== Changelog ==

= 1.6.2 =
* Fixed AJAX issues with category tabs and search bar not working.
* Added debugging output for AJAX requests.
* Added debounced search input to improve performance.

= 1.6.1 =
* Added compatibility with WooCommerce High-Performance Order Storage (HPOS).
* Improved pagination logic to use HPOS-compatible product queries.
* Added translatable strings for JavaScript loading and error messages.

= 1.6.0 =
* Added products per page setting in admin panel.
* Added pagination for product display.
* Made plugin WordPress.org compliant with internationalization, proper licensing, and uninstall hook.

= 1.5.0 =
* Added shortcode display on admin settings page.

= 1.4.0 =
* Replaced multi-select dropdown with checkboxes for selecting categories.

= 1.3.0 =
* Added admin settings page for configuring category limit, specific categories, and search bar visibility.

= 1.2.0 =
* Added search bar functionality.

= 1.1.0 =
* Improved error handling and dependency checks.

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.6.2 =
This version fixes AJAX issues with category tabs and search bar, adds debugging output, and improves search performance with debouncing. Update to ensure proper functionality.

== License ==

This plugin is licensed under the GPL-2.0-or-later license.