<?php
namespace Doubleedesign\BasePlugin;

/**
 * This class defines functions to customise the WordPress admin,
 * including adding options pages, leveraging ACF etc.
 *
 * @since      1.0.0
 *
 * @package    Doublee
 *
 * @author     Leesa Ward
 */
class AdminUI {
	protected string $pluginDir;
	protected string $pluginUrl;

	public function __construct() {
		$isMustUsePlugin = PluginEntrypoint::is_must_use_plugin();
		$this->pluginDir = $isMustUsePlugin ? WP_CONTENT_DIR . '/mu-plugins/doublee-base-plugin/' : WP_CONTENT_DIR . '/plugins/doublee-base-plugin/';
		$this->pluginUrl = get_bloginfo('url') . '/wp-content/' . ($isMustUsePlugin ? 'mu-plugins' : 'plugins') . '/doublee-base-plugin/';

		// Disable ACF's post type, taxonomy, and options pages features because I code these things in via this plugin and/or client-specific plugins
		add_filter('acf/settings/enable_post_types', '__return_false');
		add_filter('acf/settings/enable_options_pages_ui', '__return_false');

		// Also Disable some core ACF fields
		add_filter('acf/get_field_types', array($this, 'disable_some_acf_fields'));

		// General admin screen customisations
		add_filter('hidden_meta_boxes', array($this, 'customise_default_hidden_metaboxes'), 10, 2);
		add_filter('default_hidden_columns', array($this, 'customise_default_hidden_columns'), 10, 2);
		add_action('admin_init', array($this, 'remove_welcome_panel'));
		add_action('wp_network_dashboard_setup', array($this, 'remove_wp_news_and_events_widget'), 20);
		add_action('wp_user_dashboard_setup', array($this, 'remove_wp_news_and_events_widget'), 20);
		add_action('wp_dashboard_setup', array($this, 'remove_wp_news_and_events_widget'), 20);
		add_action('edit_form_after_title', array($this, 'setup_after_title_meta_boxes'), 100);

		// Customise the main admin menu
		add_action('admin_menu', array($this, 'remove_unsupported_features_from_admin_menu'), 999);
		add_action('admin_menu', array($this, 'promote_menu_items'));
		add_action('admin_menu', array($this, 'rename_menu_items'));
		add_action('admin_menu', array($this, 'remove_gutenberg_menu_item'), 999);
		add_action('admin_menu', array($this, 'add_menu_section_titles'));
		add_action('admin_menu', array($this, 'move_nav_menus_to_content_section'), 999);
		add_filter('parent_file', array($this, 'fix_nav_menus_item_highlighting'), 10, 1);
		add_filter('menu_order', array($this, 'customise_admin_menu_order_and_sections'), 99);
		add_filter('custom_menu_order', '__return_true');

		// Add custom CSS and JS to the admin
		add_action('admin_enqueue_scripts', array($this, 'admin_css'));
		add_action('admin_enqueue_scripts', array($this, 'admin_js'), 50);

		// Customise selected ACF field instruction rendering
		add_filter('acf/prepare_field', [$this, 'prepare_fields_that_should_have_instructions_as_tooltips'], 11, 1);
		add_filter('acf/get_field_label', [$this, 'render_some_acf_field_instructions_as_tooltips'], 11, 3);

		// Ensure the Featured Image metabox appears high in the list by default
		add_action('do_meta_boxes', [$this, 'featured_image_metabox_position'], 10);

		// Disable unused Writing settings
		add_filter('enable_post_by_email_configuration', '__return_false');
		add_filter('enable_update_services_configuration', '__return_false');

		// Customise admin theme
		add_action('admin_init', [$this, 'register_custom_admin_color_schemes'], 2);
		add_action('admin_init', [$this, 'clear_other_admin_themes'], 3);
		add_filter('get_user_option_admin_color', [$this, 'lock_admin_color_scheme']);
		add_action('admin_body_class', [$this, 'admin_body_class']);
		add_action('admin_enqueue_scripts', [$this, 'admin_bar_css_on_front_and_back_end']);
		add_action('wp_enqueue_scripts', [$this, 'admin_bar_css_on_front_and_back_end']);

		// Login screen
		add_action('login_enqueue_scripts', [$this, 'login_logo']);
	}



	function should_apply_client_theme_in_admin(): bool {
		if(!is_admin()) {
			return false;
		}

		return apply_filters('doublee_use_client_theme_in_admin', false);
	}

	/**
	 * Disable some ACF fields
	 *
	 * @param  $field_types
	 *
	 * return array
	 *
	 * @since 3.0.0
	 */
	public function disable_some_acf_fields($field_types): array {
		$disable = array(
			'Basic'    => array('password'),
            'Advanced' => array('icon_picker', 'color_picker')
        );

        foreach ($disable as $category => $fields) {
            foreach ($fields as $field) {
                unset($field_types[$category][$field]);
            }
        }

		return $field_types;
	}

	/**
	 * Hide some metaboxes by default, without completely removing them
	 * (user can still override using Screen Options)
	 *
	 * @param  $hidden
	 * @param  $screen
	 *
	 * @return array
	 */
	public function customise_default_hidden_metaboxes($hidden, $screen): array {

		if ($screen->id === 'dashboard') {
			return array_merge($hidden, array('dashboard_quick_press', 'dashboard_primary'));
		}

		if ($screen->id === 'page') {
			return array_merge($hidden, array('wpseo_meta', 'commentsdiv', 'revisionsdiv'));
		}

		if ($screen->id === 'post') {
			return array_merge($hidden, array('wpseo_meta', 'commentsdiv', 'revisionsdiv', 'tagsdiv-post_tag'));
		}

		if (is_plugin_active('woocommerce/woocommerce.php') && $screen->id === 'product') {
			return array_merge($hidden, array(
				'postexcerpt',
				'wpseo_meta',
				'commentsdiv',
				'tagsdiv-product_tag',
				'woocommerce-product-images'
			));
		}

		return $hidden;
	}

	/**
	 * Hide some admin columns by default, without completely removing them
	 * (user can still override using Screen Options unless they've been completely disabled elsewhere)
	 *
	 * @param  $hidden
	 * @param  $screen
	 *
	 * @return array
	 */
	public function customise_default_hidden_columns($hidden, $screen): array {
		$yoast = array(
			'wpseo-score',
			'wpseo-score-readability',
			'wpseo-title',
			'wpseo-metadesc',
			'wpseo-focuskw',
			'wpseo-links'
		);
		if ($screen->id === 'edit-post') {
			return array_merge($hidden, $yoast, array('post_tag'));
		}
		if ($screen->id === 'edit-page') {
			return array_merge($hidden, $yoast);
		}
		if ($screen->id === 'edit-product') {
			return array_merge($hidden, $yoast, array('product_tag', 'sku'));
		}

		return $hidden;
	}

	/**
	 * Remove the Welcome panel that appears on the dashboard after an update
	 *
	 * @return void
	 */
	public function remove_welcome_panel(): void {
		remove_action('welcome_panel', 'wp_welcome_panel');
	}

	/**
	 * Disable WordPress Events and News widget from the dashboard
	 *
	 * @return void
	 *
	 * @since 3.0.0
	 */
	public function remove_wp_news_and_events_widget(): void {
		remove_meta_box('dashboard_primary', get_current_screen(), 'side');
	}

	/**
	 * Add meta boxes added/moved to the custom 'after title' context
	 *
	 * @return void
	 */
	public function setup_after_title_meta_boxes(): void {
		global $post, $wp_meta_boxes;
		do_meta_boxes(get_current_screen(), 'after_title', $post);
	}


	public function remove_unsupported_features_from_admin_menu(): void {
		remove_submenu_page('options-general.php', 'options-connectors.php');
		remove_submenu_page('themes.php', 'font-library.php');
		remove_submenu_page('themes.php', 'site-editor.php');
	}


	/**
	 * Move some submenu items to top-level menu items
	 *
	 * @return void
	 */
	public function promote_menu_items(): void {
		if (is_plugin_active('woocommerce/woocommerce.php')) {
			remove_submenu_page('woocommerce', 'edit.php?post_type=shop_order');
			add_menu_page(
				__('Subscriptions', 'doublee'),
				'Orders',
				'manage_woocommerce',
				'edit.php?post_type=shop_order',
				'',
				'dashicons-index-card',
				0
			);

			remove_submenu_page('woocommerce', 'admin.php?page=wc-reports');
			add_menu_page(
				__('Reports', 'doublee'),
				'Sales Reports',
				'manage_woocommerce',
				'admin.php?page=wc-reports',
				'',
				'dashicons-portfolio',
				0
			);

			if (is_plugin_active('woocommerce-subscriptions/woocommerce-subscriptions.php')) {
				remove_submenu_page('woocommerce', 'edit.php?post_type=shop_subscription');
				add_menu_page(
					__('Subscriptions', 'doublee'),
					'Subscriptions',
					'manage_woocommerce',
					'edit.php?post_type=shop_subscription',
					'',
					'dashicons-update',
					0
				);
			}
		}
	}

	/**
	 * Rename some menu items
	 *
	 * @return void
	 */
	public function rename_menu_items(): void {
		global $menu, $submenu;

		foreach($menu as $index => $item) {
			if ($item[0] === 'Users') {
				$menu[$index][0] = 'User Accounts';
			}
			if ($item[0] === 'WooCommerce') {
				$menu[$index][0] = 'Shop';
			}
			if ($item[0] === 'ACF') {
				$menu[$index][0] = 'Custom Fields';
			}
			if ($item[0] === 'Settings') {
				$menu[$index][0] = 'General Settings';
			}
		}
	}

	/**
	 * If the Gutenberg plugin is installed and active (possible due to features/fixes not in core yet),
	 * don't show the admin menu
	 *
	 * @return void
	 */
	public function remove_gutenberg_menu_item(): void {
		remove_menu_page('gutenberg');
	}

	/**
	 * Add section titles to the admin menu
	 * Note: The positions are set to 0 and then overridden in the below ordering function
	 *
	 * @return void
	 */
	public function add_menu_section_titles(): void {
		add_menu_page(
			__('Content', 'doublee'),
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
				apply_filters('doublee_admin_menu_shop_title', 'Shop'),
				'edit_posts',
				'section-title-shop',
				'',
				'dashicons-groups',
				0
			);
		}
		if (is_plugin_active('ninja-forms/ninja-forms.php')) {
			add_menu_page(
				__('Enquiries', 'doublee'),
				'Enquiries',
				'manage_forms',
				'section-title-enquiries',
				'',
				'dashicons-admin-comments',
				0
			);
		}
		add_menu_page(
			__('People', 'doublee'),
			'People',
			'list_users',
			'section-title-people',
			'',
			'dashicons-groups',
			0
		);
		add_menu_page(
			__('Configuration', 'doublee'),
			'Configuration',
			'edit_theme_options',
			'section-title-config',
			'',
			'dashicons-admin-settings',
			0
		);
	}

	public function move_nav_menus_to_content_section(): void {
		add_menu_page(
			__('Menus', 'doublee'),
			'Menus',
			'edit_theme_options',
			'nav-menus.php',
			'',
			'dashicons-menu',
			0
		);

		remove_submenu_page('themes.php', 'nav-menus.php');
	}

	public function fix_nav_menus_item_highlighting($parent_file): ?string {
		global $current_screen;

		if ($current_screen->id === 'nav-menus') {
			return null;
		}

		return $parent_file;
	}

	/**
	 * Customise the menu order and sectioning
	 *
	 * @param  $menu_order
	 *
	 * @return string[]|true
	 */
	public function customise_admin_menu_order_and_sections($menu_order): array|bool {
		if (!$menu_order) {
			return true;
		}

		$cpts = array_filter(get_post_types(), function($post_type) {
			return !str_starts_with($post_type, 'wp_')
				&& !str_starts_with($post_type, 'acf-')
				&& !str_starts_with($post_type, 'shop_')
				&& !in_array($post_type, array('post', 'page', 'product', 'attachment', 'revision', 'nav_menu_item', 'custom_css', 'customize_changeset', 'oembed_cache', 'user_request'));
		}, ARRAY_FILTER_USE_KEY);

		$cpts = array_map(function($cpt) {
			return "edit.php?post_type=$cpt";
		}, array_keys($cpts));

		$woocommerce = array(
			'section-title-shop',
			'edit.php?post_type=product',
			'edit.php?post_type=shop_order',
			'edit.php?post_type=shop_subscription', // WooCommerce Subscriptions
			'edit.php?post_type=event_ticket', // WooCommerce Box Office
			'admin.php?page=wc-reports',
			'woocommerce-marketing',
			'wc-admin&path=/analytics/overview'
		);

		$ninja_forms = array(
			'section-title-enquiries',
			'ninja-forms',
		);

		$base = array(
			'top'     => [
				'index.php', // Dashboard
				'googlesitekit-splash',
				'googlesitekit-dashboard',
			],
			'content' => [
				'section-title-content',
				'edit.php', // Posts
				'edit.php?post_type=page', // Pages
				...$cpts,
				'shared-content', // From Comet Components for ACF plugin
				'upload.php', // Media
				'edit-comments.php',
				'nav-menus.php'
			],
			'forms'   => $ninja_forms,
			'shop'    => $woocommerce,
			'users'   => [
				'section-title-people',
				'users.php',
			],
			'config'  => [
				'section-title-config',
				'acf-options-global-options',
				'options-general.php', // Settings
				'woocommerce',
				'themes.php', // Appearance
				'wpseo_dashboard', // Yoast SEO
				'theseoframework-settings', // The SEO Framework
				'plugins.php',
				'edit.php?post_type=acf-field-group', // Advanced Custom Fields
				'tools.php',
			]
		);

		return self::array_flatten(apply_filters('doublee_admin_menu_order_and_sections', $base));
	}

	/**
	 * Add custom CSS to the admin for stuff added by the plugin
	 * @return void
     */
    public function admin_css(): void {
        wp_enqueue_style('doublee-plugin-admin', $this->pluginUrl . 'assets/admin-styles.css', [], DOUBLEE_VERSION);

	    $current_screen = get_current_screen();
	    if(method_exists($current_screen, 'is_block_editor') && $current_screen->is_block_editor()) {
		    wp_enqueue_style('doublee-acf-drag-handle', $this->pluginUrl . 'assets/acf-drag-handle.css', [], DOUBLEE_VERSION);
	    }
    }

	public function admin_js(): void {
		$current_screen = get_current_screen();
		if(method_exists($current_screen, 'is_block_editor') && $current_screen->is_block_editor()) {
			wp_enqueue_script('doublee-acf-drag-handle', $this->pluginUrl . '/assets/dist/acf-drag-handle.dist.js', [], DOUBLEE_VERSION, true);
		}
	}

	/**
	 * Utility function to flatten a multidimensional array
	 *
	 * @param  $array
	 * @param array $flatArray
	 *
	 * @return array|mixed
	 */
	public static function array_flatten($array, array &$flatArray = []): mixed {
		foreach($array as $element) {
			if (is_array($element)) {
				// If the element is an array, recursively call the function
				self::array_flatten($element, $flatArray);
			}
			else {
				// If the element is not an array, add it to the result array
				$flatArray[] = $element;
			}
		}

		return $flatArray;
	}

	/**
	 * ACF does not have a filter to allow us to remove the instructions from the DOM,
	 * and I hate hacking such things with display:none or removing from the DOM on the client side with JS.
	 * This workaround moves the instructions into a custom field
	 * (which we then use in our custom label rendering function to render an icon + tooltip instead of the usual instruction markup).
	 *
	 * @param  $field
	 *
	 * @return array
	 */
	public function prepare_fields_that_should_have_instructions_as_tooltips($field): array {
		if ($this->should_render_instructions_as_tooltips($field) && $field['instructions']) {
			$field['tooltip'] = $field['instructions'];
			$field['instructions'] = '';
		}

		return $field;
	}

	public function render_some_acf_field_instructions_as_tooltips($label, $field, $context): string {
		if ($this->should_render_instructions_as_tooltips($field) && isset($field['tooltip'])) {
			// Note: Something is stripping tabindex from non-interactive elements like <span> in the admin, so we have to use a <button>
			// type="button" to make it focusable and accessible, without it submitting the form.
			return <<<HTML
				{$label}
				<button type="button" class="acf-js-tooltip" title="{$field['tooltip']}">
					<span class="dashicons dashicons-editor-help"></span>
					<span class="screen-reader-text" role="tooltip">{$field['tooltip']}</span>
				</button>
				HTML;
		}

		return $label;
	}

	protected function should_render_instructions_as_tooltips($field): bool {
		return in_array($field['label'], ['Redirect', 'Open in new tab', 'Display heading']);
	}

	/**
	 * Ensure the Featured Image metabox appears directly below the Publish metabox by default
	 *
	 * @return void
	 */
	public function featured_image_metabox_position(): void {
		global $current_screen;
		if (!isset($current_screen->post_type)) {
			return;
		}

		if ($current_screen->is_block_editor()) {
			return;
		}

		if (post_type_supports($current_screen->post_type, 'thumbnail')) {
			$post_type = $current_screen->post_type;
			$post_type_object = get_post_type_object($current_screen->post_type);
			$label = $post_type_object->labels->featured_image ?? __('Featured Image', 'doublee');

			remove_meta_box('submitdiv', $post_type, 'side');
			remove_meta_box('postimagediv', $post_type, 'side');

			add_meta_box('submitdiv', __('Publish', 'doublee'), 'post_submit_meta_box', $post_type, 'side', 'high');
			add_meta_box('postimagediv', $label, 'post_thumbnail_meta_box', $post_type, 'side', 'high');
		}
	}

	public function register_custom_admin_color_schemes(): void {
		if(!$this->should_apply_client_theme_in_admin()) {
			return;
		}

		// Check that the theme's colours file exists first
		if (!file_exists(get_stylesheet_directory() . '/colours.css')) {
			return;
		}

		$active_theme = wp_get_theme();
		$name = $active_theme->get('Name');
		$slug = $active_theme->get('TextDomain');

		if(!file_exists($this->pluginDir . 'assets/admin-theme.css')) {
			return;
		}

		wp_admin_css_color(
			"{$slug}-admin-theme",
			$name,
			$this->pluginUrl. 'assets/admin-theme.css',
			array("var(--color-primary)", "var(--color-secondary)", "var(--color-accent)", "var(--color-dark)", "var(--color-light)"),
			array(
				'base' => "var(--color-light)",
				'focus'   => '#fff',
				'current' => '#fff',
			)
		);
	}

	public function clear_other_admin_themes(): void {
		if(!$this->should_apply_client_theme_in_admin()) {
			return;
		}

		// remove_action for the function that registers the default themes wasn't working at the time of writing, nor is there a way to filter them
		global $_wp_admin_css_colors;
		$active_theme = wp_get_theme();
		$slug = $active_theme->get('TextDomain') . "-admin-theme";

		if(in_array($slug, array_keys($_wp_admin_css_colors))) {
			foreach($_wp_admin_css_colors as $key => $color_scheme) {
				if ($key !== $slug) {
					unset($_wp_admin_css_colors[$key]);
				}
			}
		}
	}

	public function lock_admin_color_scheme($theme): string {
		if(!$this->should_apply_client_theme_in_admin()) {
			return $theme;
		}

		global $_wp_admin_css_colors;
		$active_theme = wp_get_theme();
		$slug = $active_theme->get('TextDomain') . "-admin-theme";

		if(in_array($slug, array_keys($_wp_admin_css_colors))) {
			return $slug;
		}

		return 'modern';
	}

	public function admin_body_class($body_class): string {
		if(!$this->should_apply_client_theme_in_admin()) {
			return $body_class;
		}

		return "$body_class admin-doubleedesign";
	}

	public function admin_bar_css_on_front_and_back_end(): void {
		wp_enqueue_style('theme-admin-bar', $this->pluginUrl . 'assets/admin-bar.css', [], DOUBLEE_VERSION);
	}

	public function login_logo(): void {
		wp_enqueue_style( 'theme-colours', get_stylesheet_directory_uri() . '/colours.css' );
		wp_enqueue_style( 'theme-fonts', get_stylesheet_directory_uri() . '/fonts.css' );
		wp_enqueue_style( 'theme-login', get_stylesheet_directory_uri() . '/login-page.css' );

		$custom_logo_id = get_option('options_logo');
		if ($custom_logo_id) {
			$logo = wp_get_attachment_image_src($custom_logo_id, 'full');
			$use_dark_bg = apply_filters('doublee_use_dark_bg_on_login_screen', false);
			?>
			<style>
				body.login.wp-core-ui {
					font-family: var(--font-family-body, system-ui), system-ui, -apple-system, BlinkMacSystemFont, sans-serif;

					<?php if($use_dark_bg) { ?>
						background-color: var(--color-dark) !important;
					<?php } ?>
				}

				#login h1 a {
					width: 75%;
					min-height: 80px;
					background-image: url('<?php echo $logo[0]; ?>') !important;
					padding-bottom: 0 !important;
					background-size: contain !important;
					background-position: center bottom;
				}

				#login #nav a, #login #backtoblog a {
					text-decoration: underline;
					text-decoration-color: transparent;
					transition: all 0.3s ease;

					<?php if($use_dark_bg) { ?>
						color: white !important;
					<?php } ?>

					&:hover, &:focus {
						text-decoration-color: currentColor;
					}
				}

				#wp-submit {
					background: var(--color-primary);
					border: var(--color-primary);
					transition: all 0.3s ease;

					&:hover, &:focus {
						background: color-mix(in srgb, var(--color-primary) 80%, black);
					}
				}
			</style>
		<?php }
	}
}
