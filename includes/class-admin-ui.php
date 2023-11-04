<?php
/**
 * This class defines functions to customise the WordPress admin,
 * including adding options pages, leveraging ACF etc.
 * Note: Customisations specific to WooCommerce should be placed in class-woocommerce.php.
 *
 * @since      2.0.0
 * @package    MyPlugin
 * @subpackage MyPlugin/includes
 * @author     Leesa Ward
 */
class MyPlugin_Admin_UI {

	public function __construct() {
		add_action('acf/update_field_group', array($this, 'save_acf_global_options_to_plugin'), 1, 1);
		add_filter('manage_acf-field-group_posts_custom_column', array($this, 'show_where_acf_fields_are_loaded_from'), 100, 2);
		add_action('acf/init', array($this, 'setup_acf_global_options'), 5);
		add_filter('acf/settings/load_json', array($this, 'load_acf_fields_from_plugin'));
		add_filter('hidden_meta_boxes', array($this, 'customise_default_hidden_metaboxes'), 10, 2);
		add_filter('default_hidden_columns', array($this, 'customise_default_hidden_columns'), 10, 2);
		add_action('admin_init', array($this, 'remove_welcome_panel'));
		add_action('admin_menu', array($this, 'add_menu_section_titles'));
		add_filter('menu_order', array($this, 'customise_admin_menu_order_and_sections'), 99);
		add_filter('custom_menu_order', array($this, 'customise_admin_menu_order_and_sections'));
		add_action('admin_enqueue_scripts', array($this, 'admin_css'));
		add_action('edit_form_after_title', array($this, 'setup_after_title_meta_boxes'), 100);
	}


	/**
	 * Enable loading JSON files of ACF fields from the plugin
	 * @param $paths
	 *
	 * @return array
	 */
	function load_acf_fields_from_plugin($paths): array {
		$paths[] = MYPLUGIN_PLUGIN_PATH . 'assets/acf-json/';

		return $paths;
	}


	/**
	 * Update the Local JSON column in the ACF Field Groups admin list to show where the fields are being loaded from
	 * @param $column_key
	 * @param $post_id
	 *
	 * @return void
	 */
	function show_where_acf_fields_are_loaded_from($column_key, $post_id): void {
		if($column_key === 'acf-json') {
			$files = MyPlugin::get_acf_json_filenames();
			$post = get_post($post_id);
			$key = $post->post_name;
			if(in_array($key.'.json', $files['plugin'])) {
				echo ' in ' . MyPlugin::get_name() . ' plugin';
			}
			if(in_array($key.'.json', $files['theme'])) {
				echo ' in ' . wp_get_theme()->name . ' theme';
			}
		}
	}


	/**
	 * Set up Global Options page
	 *
	 * @return void
	 */
	function setup_acf_global_options(): void {
		if(function_exists('acf_add_options_page')) {
			acf_add_options_page(array(
				'page_title' => 'Global Settings and Information for ' . get_bloginfo('name'),
				'menu_title' => 'Global Options',
				'menu_slug' => 'acf-options-global-options',
				'position' => 2
			));
		}
	}


	/**
	 * Save any changes to Global Options ACF fields to the JSON file in the plugin
	 * rather than the default location (the theme)
	 * @param $group
	 *
	 * @return void
	 */
	function save_acf_global_options_to_plugin($group): void {
		if($group['key'] === 'group_5876ae3e825e9') {
			starterkit::override_acf_json_save_location();
		}
	}


	/**
	 * Hide some metaboxes by default, without completely removing them
	 * (user can still override using Screen Options)
	 * @param $hidden
	 * @param $screen
	 *
	 * @return array
	 */
	function customise_default_hidden_metaboxes($hidden, $screen): array {

		if($screen->id === 'dashboard') {
			return array_merge($hidden, array('dashboard_quick_press', 'dashboard_primary'));
		}

		if($screen->id === 'page') {
			return array_merge($hidden, array('wpseo_meta', 'commentsdiv', 'revisionsdiv'));
		}

		if($screen->id === 'post') {
			return array_merge($hidden, array('wpseo_meta', 'commentsdiv', 'revisionsdiv', 'tagsdiv-post_tag'));
		}

		if(is_plugin_active('woocommerce/woocommerce.php') && $screen->id === 'product') {
			return array_merge($hidden, array('postexcerpt', 'wpseo_meta', 'commentsdiv', 'tagsdiv-product_tag', 'woocommerce-product-images'));
		}

		return $hidden;
	}


	/**
	 * Hide some admin columns by default, without completely removing them
	 * (user can still override using Screen Options unless they've been completely disabled elsewhere)
	 * @param $hidden
	 * @param $screen
	 *
	 * @return array
	 */
	function customise_default_hidden_columns($hidden, $screen): array {
		$yoast = array('wpseo-score', 'wpseo-score-readability', 'wpseo-title', 'wpseo-metadesc', 'wpseo-focuskw', 'wpseo-links');
		if($screen->id === 'edit-post') {
			return array_merge($hidden, $yoast, array('post_tag'));
		}
		if($screen->id === 'edit-page') {
			return array_merge($hidden, $yoast);
		}
		if($screen->id === 'edit-product') {
			return array_merge($hidden, $yoast, array('product_tag', 'sku'));
		}

		return $hidden;
	}


	/**
	 * Remove the Welcome panel that appears on the dashboard after an update
	 * @return void
	 */
	function remove_welcome_panel(): void {
		remove_action('welcome_panel', 'wp_welcome_panel');
	}


	/**
	 * Add section titles to the admin menu
	 * Note: The positions are set to 0 and then overridden in the below ordering function
	 *
	 * @return void
	 */
	function add_menu_section_titles(): void {
		add_menu_page(
			__('Content', 'starterkit'),
			'Content',
			'edit_posts',
			'section-title-content',
			'',
			'dashicons-welcome-write-blog',
			0
		);
		if (is_plugin_active('woocommerce/woocommerce.php')) {
			add_menu_page(
				__('Shop', 'woocommerce'),
				'Shop',
				'edit_posts',
				'section-title-shop',
				'',
				'dashicons-groups',
				0
			);
		}
		add_menu_page(
			__('People', 'starterkit'),
			'People',
			'list_users',
			'section-title-people',
			'',
			'dashicons-groups',
			0
		);
		add_menu_page(
			__('Configuration', 'starterkit'),
			'Configuration',
			'edit_theme_options',
			'section-title-config',
			'',
			'dashicons-admin-settings',
			0
		);
	}


	/**
	 * Customise the menu order and sectioning
	 * @param $menu_order
	 *
	 * @return string[]|true
	 */
	function customise_admin_menu_order_and_sections($menu_order): array|bool {
		if (!$menu_order) {
			return true;
		}

		return array(
			'index.php', // Dashboard

			// Content
			'section-title-content',
			'edit.php', // Posts
			'edit.php?post_type=page',
			'upload.php', // Media
			'edit-comments.php',

			// Shop
			'section-title-shop',
			'edit.php?post_type=product',
			'edit.php?post_type=event_ticket', // WooCommerce Box Office
			'woocommerce',
			'woocommerce-marketing',
			'wc-admin&path=/analytics/overview',

			// Enquiries
			'section-title-enquiries',
			'ninja-forms',

			// People
			'section-title-people',
			'users.php',

			// Config
			'section-title-config',
			'acf-options-global-options',
			'options-general.php', // Settings
			'themes.php', // Appearance
			'plugins.php',
			'edit.php?post_type=acf-field-group', // Advanced Custom Fields
			'tools.php',
			'wpseo_dashboard', // Yoast SEO
		);
	}


	/**
	 * Add custom CSS to the admin for stuff added by the plugin
	 * (the starterkit theme also adds an admin stylesheet)
	 *
	 * @return void
	 */
	function admin_css(): void {
		wp_enqueue_style('starterkit-plugin-admin', '/wp-content/plugins/doublee-plugin-framework/assets/admin-styles.css');
	}


	/**
	 * Add meta boxes added/moved to the custom 'after title' context
	 *
	 * @return void
	 */
	function setup_after_title_meta_boxes(): void {
		global $post, $wp_meta_boxes;
		do_meta_boxes(get_current_screen(), 'after_title', $post);
	}
}
