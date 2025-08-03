<?php
/**
 * Plugin Name: Show Product Tab as Category
 * Plugin URI: https://wordpress.org/plugins/show-product-tab-as-category
 * Description: A WooCommerce plugin to display products in a tabbed interface based on categories with search, pagination, and admin settings.
 * Version: 1.6.2
 * Author: Xian Saiful
 * Author URI: https://xiansaiful.com
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: show-product-tab-as-category
 * Domain Path: /languages
 * Requires at least: 5.2
 * Requires PHP: 7.2
 * WC requires at least: 7.0
 * WC tested up to: 9.2
 *
 * @package Show_Product_Tab_As_Category
 */

defined('ABSPATH') || exit;

/**
 * Main plugin class.
 */
class Show_Product_Tab_As_Category {

	/**
	 * Plugin settings option name.
	 *
	 * @var string
	 */
	private $settings_option = 'sptac_settings';

	/**
	 * Plugin constructor.
	 */
	public function __construct() {
		// Load translations
		add_action('init', [$this, 'load_textdomain']);

		// Declare HPOS compatibility
		add_action('before_woocommerce_init', [$this, 'declare_hpos_compatibility']);

		// Check if WooCommerce is active
		add_action('plugins_loaded', [$this, 'check_dependencies']);
		
		// Register shortcode
		add_shortcode('product_tab_category', [$this, 'render_product_tabs']);
		
		// Enqueue scripts and styles
		add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
		
		// AJAX handlers
		add_action('wp_ajax_sptac_load_products', [$this, 'ajax_load_products']);
		add_action('wp_ajax_nopriv_sptac_load_products', [$this, 'ajax_load_products']);
		
		// Admin menu
		add_action('admin_menu', [$this, 'add_admin_menu']);
		add_action('admin_init', [$this, 'register_settings']);
		
		// Enqueue admin styles
		add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_styles']);

		// Register uninstall hook
		register_uninstall_hook(__FILE__, ['Show_Product_Tab_As_Category', 'uninstall']);
	}

	/**
	 * Load plugin text domain for translations.
	 */
	public function load_textdomain() {
		load_plugin_textdomain('show-product-tab-as-category', false, dirname(plugin_basename(__FILE__)) . '/languages');
	}

	/**
	 * Declare compatibility with WooCommerce HPOS.
	 */
	public function declare_hpos_compatibility() {
		if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
		}
	}

	/**
	 * Check for WooCommerce dependency.
	 */
	public function check_dependencies() {
		if (!class_exists('WooCommerce')) {
			add_action('admin_notices', function() {
				?>
				<div class="error">
					<p><?php esc_html_e('Show Product Tab as Category requires WooCommerce to be installed and activated.', 'show-product-tab-as-category'); ?></p>
				</div>
				<?php
			});
			return false;
		}
		return true;
	}

	/**
	 * Enqueue frontend scripts and styles.
	 */
	public function enqueue_assets() {
		global $post;
		if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'product_tab_category')) {
			wp_enqueue_script('jquery');
			
			wp_enqueue_style(
				'sptac-styles',
				plugin_dir_url(__FILE__) . 'assets/css/sptac-styles.css',
				[],
				'1.2'
			);
			
			wp_enqueue_script(
				'sptac-scripts',
				plugin_dir_url(__FILE__) . 'assets/js/sptac-scripts.js',
				['jquery'],
				'1.2.1',
				true
			);
			
			wp_localize_script('sptac-scripts', 'sptac_ajax', [
				'ajax_url' => admin_url('admin-ajax.php'),
				'nonce' => wp_create_nonce('sptac_nonce'),
				'i18n' => [
					'loading' => esc_html__('Loading...', 'show-product-tab-as-category'),
					'error_loading' => esc_html__('Error loading products.', 'show-product-tab-as-category'),
				],
			]);
		}
	}

	/**
	 * Enqueue admin styles.
	 *
	 * @param string $hook The current admin page.
	 */
	public function enqueue_admin_styles($hook) {
		if ($hook !== 'toplevel_page_sptac_settings') {
			return;
		}
		wp_enqueue_style(
			'sptac-admin-styles',
			plugin_dir_url(__FILE__) . 'assets/css/sptac-admin-styles.css',
			[],
			'1.3'
		);
	}

	/**
	 * Add admin menu.
	 */
	public function add_admin_menu() {
		add_menu_page(
			__('Product Tab Settings', 'show-product-tab-as-category'),
			__('Product Tabs', 'show-product-tab-as-category'),
			'manage_options',
			'sptac_settings',
			[$this, 'render_admin_page'],
			'dashicons-category',
			58
		);
	}

	/**
	 * Register settings.
	 */
	public function register_settings() {
		register_setting('sptac_settings_group', $this->settings_option, [
			'sanitize_callback' => [$this, 'sanitize_settings'],
			'default' => [
				'products_per_page' => 12,
				'category_limit' => 10,
				'selected_categories' => [],
				'show_search' => 1,
			]
		]);

		add_settings_section(
			'sptac_main_section',
			__('Tab Display Settings', 'show-product-tab-as-category'),
			function() {
				echo '<p>' . esc_html__('Configure how product categories and the search bar are displayed on the frontend.', 'show-product-tab-as-category') . '</p>';
			},
			'sptac_settings'
		);

		add_settings_field(
			'products_per_page',
			__('Products Per Page', 'show-product-tab-as-category'),
			[$this, 'render_products_per_page_field'],
			'sptac_settings',
			'sptac_main_section'
		);

		add_settings_field(
			'category_limit',
			__('Number of Categories to Show', 'show-product-tab-as-category'),
			[$this, 'render_category_limit_field'],
			'sptac_settings',
			'sptac_main_section'
		);

		add_settings_field(
			'selected_categories',
			__('Select Specific Categories', 'show-product-tab-as-category'),
			[$this, 'render_selected_categories_field'],
			'sptac_settings',
			'sptac_main_section'
		);

		add_settings_field(
			'show_search',
			__('Show Search Bar', 'show-product-tab-as-category'),
			[$this, 'render_show_search_field'],
			'sptac_settings',
			'sptac_main_section'
		);
	}

	/**
	 * Sanitize settings.
	 *
	 * @param array $input Input settings.
	 * @return array Sanitized settings.
	 */
	public function sanitize_settings($input) {
		$sanitized = [];
		$sanitized['products_per_page'] = absint($input['products_per_page'] ?? 12);
		$sanitized['category_limit'] = absint($input['category_limit'] ?? 10);
		$sanitized['selected_categories'] = isset($input['selected_categories']) ? array_map('sanitize_text_field', (array)$input['selected_categories']) : [];
		$sanitized['show_search'] = isset($input['show_search']) ? 1 : 0;
		return $sanitized;
	}

	/**
	 * Render products per page field.
	 */
	public function render_products_per_page_field() {
		$options = get_option($this->settings_option, ['products_per_page' => 12]);
		$value = isset($options['products_per_page']) ? $options['products_per_page'] : 12;
		?>
		<input type="number" name="<?php echo esc_attr($this->settings_option); ?>[products_per_page]" value="<?php echo esc_attr($value); ?>" min="1" />
		<p class="description"><?php esc_html_e('Set the number of products to display per page.', 'show-product-tab-as-category'); ?></p>
		<?php
	}

	/**
	 * Render category limit field.
	 */
	public function render_category_limit_field() {
		$options = get_option($this->settings_option, ['category_limit' => 10]);
		$value = isset($options['category_limit']) ? $options['category_limit'] : 10;
		?>
		<input type="number" name="<?php echo esc_attr($this->settings_option); ?>[category_limit]" value="<?php echo esc_attr($value); ?>" min="1" />
		<p class="description"><?php esc_html_e('Set the maximum number of categories to display (excluding "All Products").', 'show-product-tab-as-category'); ?></p>
		<?php
	}

	/**
	 * Render selected categories field with checkboxes.
	 */
	public function render_selected_categories_field() {
		$options = get_option($this->settings_option, []);
		$selected_categories = isset($options['selected_categories']) ? (array)$options['selected_categories'] : [];
		$categories = get_terms([
			'taxonomy' => 'product_cat',
			'hide_empty' => false,
		]);
		?>
		<div class="sptac-category-checkboxes">
			<?php if (!empty($categories) && !is_wp_error($categories)): ?>
				<?php foreach ($categories as $category): ?>
					<label>
						<input type="checkbox" 
							name="<?php echo esc_attr($this->settings_option); ?>[selected_categories][]" 
							value="<?php echo esc_attr($category->slug); ?>" 
							<?php checked(in_array($category->slug, $selected_categories)); ?> />
						<?php echo esc_html($category->name); ?>
					</label><br />
				<?php endforeach; ?>
			<?php else: ?>
				<p><?php esc_html_e('No categories found. Please add product categories in WooCommerce.', 'show-product-tab-as-category'); ?></p>
			<?php endif; ?>
		</div>
		<p class="description"><?php esc_html_e('Select the categories to display. Leave all unchecked to show all categories (up to the limit above).', 'show-product-tab-as-category'); ?></p>
		<?php
	}

	/**
	 * Render show search field.
	 */
	public function render_show_search_field() {
		$options = get_option($this->settings_option, []);
		$show_search = isset($options['show_search']) ? $options['show_search'] : 1;
		?>
		<input type="checkbox" name="<?php echo esc_attr($this->settings_option); ?>[show_search]" value="1" <?php checked($show_search, 1); ?> />
		<p class="description"><?php esc_html_e('Check to display the search bar below the category tabs.', 'show-product-tab-as-category'); ?></p>
		<?php
	}

	/**
	 * Render admin settings page.
	 */
	public function render_admin_page() {
		?>
		<div class="wrap sptac-admin-wrap">
			<h1><?php esc_html_e('Product Tab as Category Settings', 'show-product-tab-as-category'); ?></h1>
			<div class="sptac-shortcode-display">
				<p><strong><?php esc_html_e('Shortcode:', 'show-product-tab-as-category'); ?></strong> <code>[product_tab_category]</code></p>
				<p class="description"><?php esc_html_e('Copy and paste this shortcode into any page or post to display the product tabs.', 'show-product-tab-as-category'); ?></p>
			</div>
			<form method="post" action="options.php">
				<?php
				settings_fields('sptac_settings_group');
				do_settings_sections('sptac_settings');
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Shortcode callback to render the tabs, search, and products.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string Shortcode output.
	 */
	public function render_product_tabs($atts) {
		$options = get_option($this->settings_option, [
			'products_per_page' => 12,
			'category_limit' => 10,
			'selected_categories' => [],
			'show_search' => 1
		]);

		$atts = shortcode_atts([
			'products_per_page' => $options['products_per_page'],
		], $atts, 'product_tab_category');

		if (!class_exists('WooCommerce')) {
			return '<p><strong>' . esc_html__('Error:', 'show-product-tab-as-category') . '</strong> ' . esc_html__('WooCommerce is not active. Please install and activate WooCommerce.', 'show-product-tab-as-category') . '</p>';
		}

		$category_args = [
			'taxonomy' => 'product_cat',
			'hide_empty' => true,
		];

		if (!empty($options['selected_categories'])) {
			$category_args['slug'] = $options['selected_categories'];
		}

		$category_args['number'] = absint($options['category_limit']);

		$categories = get_terms($category_args);

		if (is_wp_error($categories)) {
			return '<p><strong>' . esc_html__('Error:', 'show-product-tab-as-category') . '</strong> ' . esc_html__('Failed to load product categories. Please check WooCommerce settings.', 'show-product-tab-as-category') . '</p>';
		}

		if (empty($categories)) {
			return '<p>' . esc_html__('No product categories found. Please add some product categories in WooCommerce.', 'show-product-tab-as-category') . '</p>';
		}

		ob_start();
		?>
		<div class="sptac-container" data-products-per-page="<?php echo esc_attr($atts['products_per_page']); ?>">
			<ul class="sptac-tabs">
				<li class="sptac-tab active" data-category="all"><?php esc_html_e('All Products', 'show-product-tab-as-category'); ?></li>
				<?php foreach ($categories as $category): ?>
					<li class="sptac-tab" data-category="<?php echo esc_attr($category->slug); ?>">
						<?php echo esc_html($category->name); ?>
					</li>
				<?php endforeach; ?>
			</ul>
			<?php if ($options['show_search']): ?>
				<div class="sptac-search">
					<input type="text" class="sptac-search-input" placeholder="<?php esc_attr_e('Search products...', 'show-product-tab-as-category'); ?>" />
				</div>
			<?php endif; ?>
			<div class="sptac-products">
				<?php
				$products = wc_get_products([
					'limit' => $atts['products_per_page'],
					'status' => 'publish',
					'page' => 1,
				]);

				$count_args = [
					'limit' => -1,
					'status' => 'publish',
					'return' => 'ids',
				];
				$total_products = count(wc_get_products($count_args));
				$total_pages = ceil($total_products / $atts['products_per_page']);

				if (!empty($products)) {
					echo '<div class="sptac-products-grid">';
					foreach ($products as $product) {
						echo '<div class="sptac-product">';
						echo '<a href="' . esc_url($product->get_permalink()) . '">';
						echo $product->get_image('woocommerce_thumbnail');
						echo '<h3>' . esc_html($product->get_name()) . '</h3>';
						echo '<span class="price">' . wp_kses_post($product->get_price_html()) . '</span>';
						echo '</a>';
						echo '</div>';
					}
					echo '</div>';

					if ($total_pages > 1) {
						echo '<div class="sptac-pagination">';
						for ($i = 1; $i <= $total_pages; $i++) {
							$active_class = $i === 1 ? ' active' : '';
							echo '<a href="#" class="sptac-page' . esc_attr($active_class) . '" data-page="' . esc_attr($i) . '">' . esc_html($i) . '</a>';
						}
						echo '</div>';
					}
				} else {
					echo '<p>' . esc_html__('No products found. Please add some products in WooCommerce.', 'show-product-tab-as-category') . '</p>';
				}
				?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * AJAX handler for loading products.
	 */
	public function ajax_load_products() {
		// Verify nonce
		if (!check_ajax_referer('sptac_nonce', 'nonce', false)) {
			wp_send_json_error(['message' => __('Invalid nonce.', 'show-product-tab-as-category')]);
		}

		$category = isset($_POST['category']) ? sanitize_text_field(wp_unslash($_POST['category'])) : 'all';
		$search = isset($_POST['search']) ? sanitize_text_field(wp_unslash($_POST['search'])) : '';
		$products_per_page = isset($_POST['products_per_page']) ? absint($_POST['products_per_page']) : 12;
		$page = isset($_POST['page']) ? absint($_POST['page']) : 1;

		$args = [
			'limit' => $products_per_page,
			'status' => 'publish',
			'page' => $page,
		];

		if ($category !== 'all') {
			$args['category'] = [$category];
		}

		if (!empty($search)) {
			$args['s'] = $search;
		}

		$products = wc_get_products($args);

		// Get total products for pagination
		$count_args = [
			'limit' => -1,
			'status' => 'publish',
			'return' => 'ids',
		];
		if ($category !== 'all') {
			$count_args['category'] = [$category];
		}
		if (!empty($search)) {
			$count_args['s'] = $search;
		}
		$total_products = count(wc_get_products($count_args));
		$total_pages = ceil($total_products / $products_per_page);

		$output = '';
		if (!empty($products)) {
			$output .= '<div class="sptac-products-grid">';
			foreach ($products as $product) {
				$output .= '<div class="sptac-product">';
				$output .= '<a href="' . esc_url($product->get_permalink()) . '">';
				$output .= $product->get_image('woocommerce_thumbnail');
				$output .= '<h3>' . esc_html($product->get_name()) . '</h3>';
				$output .= '<span class="price">' . wp_kses_post($product->get_price_html()) . '</span>';
				$output .= '</a>';
				$output .= '</div>';
			}
			$output .= '</div>';

			if ($total_pages > 1) {
				$output .= '<div class="sptac-pagination">';
				for ($i = 1; $i <= $total_pages; $i++) {
					$active_class = $i === $page ? ' active' : '';
					$output .= '<a href="#" class="sptac-page' . esc_attr($active_class) . '" data-page="' . esc_attr($i) . '">' . esc_html($i) . '</a>';
				}
				$output .= '</div>';
			}
		} else {
			$output .= '<p>' . esc_html__('No products found.', 'show-product-tab-as-category') . '</p>';
		}

		wp_send_json_success([
			'data' => $output,
			'debug' => [
				'category' => $category,
				'search' => $search,
				'page' => $page,
				'products_per_page' => $products_per_page,
				'total_products' => $total_products,
				'total_pages' => $total_pages,
			]
		]);
	}

	/**
	 * Clean up on plugin uninstall.
	 */
	public static function uninstall() {
		delete_option('sptac_settings');
	}
}

// Initialize the plugin
new Show_Product_Tab_As_Category();
?>