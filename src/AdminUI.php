<?php
namespace Doubleedesign\BasePlugin;

use WP_Query;

/**
 * This class defines functions to customise the WordPress admin,
 * including adding options pages, leveraging ACF etc.
 * Note: Customisations specific to WooCommerce should be placed in class-woocommerce.php.
 *
 * @since      1.0.0
 *
 * @package    Doublee
 *
 * @author     Leesa Ward
 */
class AdminUI {

    public function __construct() {
        // Customise where ACF fields are loaded from and saved to
        add_filter('acf/settings/load_json', array($this, 'load_acf_fields_from_plugin'), 10);
        add_filter('manage_acf-field-group_posts_custom_column', array($this, 'show_where_acf_fields_are_loaded_from'), 100, 2);
        add_action('acf/init', array($this, 'setup_acf_global_options'), 5);
        add_action('acf/update_field_group', array($this, 'save_acf_global_options_to_plugin'), 1);

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
        add_action('admin_menu', array($this, 'promote_menu_items'));
        add_action('admin_menu', array($this, 'rename_menu_items'));
        add_action('admin_menu', array($this, 'remove_gutenberg_menu_item'), 999);
        add_action('admin_menu', array($this, 'add_menu_section_titles'));
        add_filter('menu_order', array($this, 'customise_admin_menu_order_and_sections'), 99);
        add_filter('custom_menu_order', '__return_true');

        // Add custom CSS to the admin
        add_action('admin_enqueue_scripts', array($this, 'admin_css'));

        // Customise the ACF field group list page
        add_filter('views_edit-acf-field-group', [$this, 'add_acf_field_list_tabs']);
        add_filter('query_vars', [$this, 'register_acf_field_list_query_vars']);
        add_action('pre_get_posts', [$this, 'populate_acf_field_list_tabs']);

        // Customise selected ACF field instruction rendering
        add_filter('acf/prepare_field', [$this, 'prepare_fields_that_should_have_instructions_as_tooltips'], 11, 1);
        add_filter('acf/get_field_label', [$this, 'render_some_acf_field_instructions_as_tooltips'], 11, 3);
    }

    /**
     * Enable loading JSON files of ACF fields from the plugin
     *
     * @param  $paths
     *
     * @return array
     */
    public function load_acf_fields_from_plugin($paths): array {
        $paths[] = DOUBLEE_PLUGIN_PATH . 'assets/acf-json/';

        return $paths;
    }

    /**
     * Update the Local JSON column in the ACF Field Groups admin list to show where the fields are being loaded from
     *
     * @param  $column_key
     * @param  $post_id
     *
     * @return void
     *
     * @noinspection t
     */
    public function show_where_acf_fields_are_loaded_from($column_key, $post_id): void {
        if ($column_key === 'acf-json') {
            $files = Doublee::get_acf_json_filenames();
            $post = get_post($post_id);
            $key = $post->post_name;
            if (in_array($key . '.json', $files['plugin'])) {
                echo ' in ' . Doublee::get_name();
            }
            if (in_array($key . '.json', $files['client_plugin'])) {
                echo ' in client plugin';
            }
            if (in_array($key . '.json', $files['events_plugin'])) {
                if (is_plugin_active('comet-calendar/comet-calendar.php')) {
                    echo ' in Comet Calendar plugin';
                }
                else {
                    echo ' in Events plugin';
                }
            }
            if (in_array($key . '.json', $files['parent_theme'])) {
                echo ' in ' . wp_get_theme()->parent() . ' theme';
            }
            if (in_array($key . '.json', $files['theme'])) {
                echo ' in ' . wp_get_theme()->name . ' theme';
            }
        }
    }

    /**
     * Set up Global Options page
     *
     * @return void
     */
    public function setup_acf_global_options(): void {
        if (function_exists('acf_add_options_page')) {
            $firstWord = explode(' ', get_bloginfo('name'))[0];

            acf_add_options_page(array(
                'page_title'  => 'Global Settings and Information for ' . get_bloginfo('name'),
                'menu_title'  => $firstWord . ' settings',
                'parent_slug' => 'themes.php',
                'menu_slug'   => 'acf-options-global-options',
                'position'    => 0
            ));
        }
    }

    /**
     * Save any changes to Global Options ACF fields to the JSON file in the plugin
     * rather than the default location (the theme)
     *
     * @param  $group
     *
     * @return void
     */
    public function save_acf_global_options_to_plugin($group): void {
        if ($group['key'] === 'group_5876ae3e825e9' || $group['key'] == 'group_67bac5d4bed29' || $group['key'] == 'group_67ca2ef6a0243') {
            Doublee::override_acf_json_save_location();
        }
    }

    /**
     * Disable some ACF fields
     *
     * @since 3.0.0
     *
     * @param  $field_types
     *
     * return array
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
     * @since 3.0.0
     *
     * @return void
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

    /**
     * Move some submenu items to top-level menu items
     *
     * @return void
     */
    public function promote_menu_items(): void {
        if (is_plugin_active('woocommerce/woocommerce.php')) {
            remove_submenu_page('woocommerce', 'edit.php?post_type=shop_order');
            add_menu_page(
                __('Subscriptions', 'starterkit'),
                'Orders',
                'manage_woocommerce',
                'edit.php?post_type=shop_order',
                '',
                'dashicons-index-card',
                0
            );

            remove_submenu_page('woocommerce', 'admin.php?page=wc-reports');
            add_menu_page(
                __('Reports', 'starterkit'),
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
                    __('Subscriptions', 'starterkit'),
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

        foreach ($menu as $index => $item) {
            if ($item[0] === 'Users') {
                $menu[$index][0] = 'User Accounts';
            }
            if ($item[0] === 'WooCommerce') {
                $menu[$index][0] = 'Shop Settings';
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
        if (is_plugin_active('ninja-forms/ninja-forms.php')) {
            add_menu_page(
                __('Enquiries', 'starterkit'),
                'Enquiries',
                'manage_forms',
                'section-title-enquiries',
                '',
                'dashicons-admin-comments',
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

        $cpt_links = array_map(function($cpt) {
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
            'index.php', // Dashboard
            'googlesitekit-splash',
            'googlesitekit-dashboard',

            // Content
            'section-title-content',
            'edit.php', // Posts
            'edit.php?post_type=page', // Pages
            'shared-content', // From Comet Components for ACF plugin
            'site-editor.php?path=/patterns', // Shared Blocks (Patterns relabelled and added to the main menu by Comet Components)
            'upload.php', // Media
            'edit-comments.php',

            // Users
            'section-title-people',
            'users.php',

            // Config
            'section-title-config',
            'acf-options-global-options',
            'woocommerce',
            'options-general.php', // Settings
            'themes.php', // Appearance
            'plugins.php',
            'edit.php?post_type=acf-field-group', // Advanced Custom Fields
            'tools.php',
            'wpseo_dashboard', // Yoast SEO
        );

        $after_pages = array_search('edit.php?post_type=page', $base) + 1;
        $updated = array_merge(
            array_slice($base, 0, $after_pages),
            $cpt_links,
            array_slice($base, $after_pages)
        );

        if (is_plugin_active('ninja-forms/ninja-forms.php')) {
            $before_users = array_search('users.php', $updated) - 1;
            $updated = array_merge(
                array_slice($updated, 0, $before_users),
                $ninja_forms,
                array_slice($updated, $before_users)
            );
        }

        if (is_plugin_active('woocommerce/woocommerce.php')) {
            if (is_plugin_active('ninja-forms/ninja-forms.php')) {
                $after_ninja_forms = array_search('ninja-forms', $updated) + 1;
                $updated = array_merge(
                    array_slice($updated, 0, $after_ninja_forms),
                    $woocommerce,
                    array_slice($updated, $after_ninja_forms)
                );
            }
            else {
                $before_users = array_search('users.php', $updated) - 1;
                $updated = array_merge(
                    array_slice($updated, 0, $before_users),
                    $woocommerce,
                    array_slice($updated, $before_users)
                );
            }
        }

        return $updated;
    }

    /**
     * Add custom CSS to the admin for stuff added by the plugin
     * (the starterkit theme also adds an admin stylesheet)
     *
     * @return void
     */
    public function admin_css(): void {
        wp_enqueue_style('doublee-plugin-admin', '/wp-content/plugins/doublee-base-plugin/assets/admin-styles.css');
    }

    /**
     * Add custom tabs to ACF field list
     * including adding post meta to use for the query (kinda a hacky place to do that but meh)
     *
     * @param  $views
     *
     * @return mixed
     */
    public function add_acf_field_list_tabs($views): mixed {
        $counts = array();
        $query = new WP_Query(array(
            'post_type' => array('acf-field-group'),
        ));

        // Expand the field group content into an array to access the relevant data
        $field_groups = array_map(function($field_group) {
            return acf_get_field_group($field_group->ID);
        }, $query->posts);

        //		// Also get the fields from the plugin, which are somehow not saved as posts
        // TODO: Dunno how to handle this because they aren't posts I can add meta to, apparently...
        //		$assumed_plugin_filename = wp_get_theme()->get('TextDomain');
        //		$assumed_plugin_folder = "$assumed_plugin_filename-plugin";
        //		$assumed_path_constant = strtoupper($assumed_plugin_filename) . '_PLUGIN_PATH';
        //		if (is_plugin_active("$assumed_plugin_folder/$assumed_plugin_filename.php")) {
        //			$in_plugin = array_diff(scandir(constant($assumed_path_constant) . 'assets/acf-json/'), ['..', '.']);
        //			if (!empty($in_plugin)) {
        //				foreach ($in_plugin as $file) {
        //					$field_groups[] = acf_get_fields(str_replace('.json', '', $file));
        //				}
        //			}
        //		}

        // Get a list of all the locations
        $locations = array_filter(array_unique(self::array_flatten(array_map(function($field_group) {
            if (!empty($field_group['location'])) {
                return array_map(function($location) {
                    if (isset($location[0]['param']) && $location[0]['param'] === 'block' && $location[0]['operator'] === '==') {
                        return 'block_settings';
                    }
                    else if (isset($location[0]['param']) && $location[0]['param'] === 'post_type' && $location[0]['operator'] === '==') {
                        return $location[0]['value'];
                    }

                    return '';
                }, $field_group['location']);
            }

            return [];
        }, $field_groups))));

        // Add them to the $counts array to be used in the tabs
        foreach ($locations as $location) {
            $counts[$location] = 0;
        }

        // Add the location(s) as post meta for the field groups
        array_walk($field_groups, function($field_group) use (&$counts) {
            if (!empty($field_group['location'])) {
                array_walk($field_group['location'], function($locations) use (&$counts, $field_group) {
                    foreach ($locations as $location) {
                        if (isset($location['param']) && $location['param'] === 'block') {
                            update_post_meta($field_group['ID'], 'location', 'block_settings');
                            $counts['block_settings']++;
                        }
                        else if (isset($location['param']) && $location['param'] === 'post_type') {
                            update_post_meta($field_group['ID'], 'location', $location['value']);
                            $counts[$location['value']]++;
                        }
                    }
                });
            }
        });

        // Add their tabs
        foreach ($locations as $location) {
            $views[$location] = sprintf(
                '<a href="%s" class="%s">%s</a>',
                add_query_arg('location', $location, admin_url('edit.php?post_type=acf-field-group')),
                acf_maybe_get_GET('location') === $location ? 'current' : '',
                sprintf(_n(
                    '%s <span class="count">(%s)</span>',
                    '%s <span class="count">(%s)</span>',
                    $counts[$location],
                    'starterkit'
                ), ucfirst(str_replace('_', ' ', $location)), number_format_i18n($counts[$location]))
            );
        }

        return $views;
    }

    /**
     * Register the custom query vars to be used by the custom ACF field list tabs
     *
     * @param  $vars
     *
     * @return mixed
     */
    public function register_acf_field_list_query_vars($vars): mixed {
        $vars[] = 'location';

        return $vars;
    }

    /**
     * Return the correct results for the custom ACF field list tabs
     *
     * @param  $query
     *
     * @return mixed
     */
    public function populate_acf_field_list_tabs($query): mixed {
        if (is_admin() && $query->is_main_query()) {
            if (isset($query->query['post_type']) && $query->query['post_type'] == 'acf-field-group' && isset($query->query_vars['location'])) {
                $query->set('meta_query', array(
                    array(
                        'key'     => 'location',
                        'value'   => acf_sanitize_request_args($query->query_vars['location']),
                        'compare' => '='
                    )
                ));
            }
        }

        return $query;
    }

    /**
     * Utility function to flatten a multidimensional array
     *
     * @param  $array
     * @param  array  $flatArray
     *
     * @return array|mixed
     */
    public static function array_flatten($array, array &$flatArray = []): mixed {
        foreach ($array as $element) {
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
        return in_array($field['label'], ['Redirect', 'Open in new tab']);
    }
}
