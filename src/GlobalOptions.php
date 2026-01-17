<?php
namespace Doubleedesign\BasePlugin;

/**
 * This class defines and handles the side-wide/global options fields.
 *
 * @since      1.0.0
 * @package    Doublee
 * @author     Leesa Ward
 */
class GlobalOptions {

	public function __construct() {
		add_action('acf/init', array($this, 'setup_acf_global_options'), 5);
		add_action('acf/include_fields', [$this, 'register_global_settings_fields'], 5, 0);
		add_action('acf/include_fields', [$this, 'register_integrations_settings_fields'], 5, 0);
		add_action('acf/include_fields', [$this, 'register_page_behaviour_fields'], 10, 0);
		add_filter('acf/load_value/name=logo', [$this, 'load_classicpress_logo'], 10, 3);
		add_action('acf/save_post', [$this, 'save_classicpress_logo'], 10, 1);
	}

	/**
	 * Register the ACF options pages for custom settings.
	 * @return void
	 */
	public function setup_acf_global_options(): void {
		if(function_exists('acf_add_options_page')) {
			$firstWord = explode(' ', get_bloginfo('name'))[0];

			acf_add_options_page(array(
				'page_title'  => 'Global Settings and Information for ' . get_bloginfo('name'),
				'menu_title'  => get_bloginfo('name'),
				'parent_slug' => 'options-general.php',
				'menu_slug'   => 'acf-options-global-options',
				'position'    => 0,
				'capability'  => 'edit_theme_options',
			));

			acf_add_options_page(array(
				'page_title'  => 'Integrations',
				'menu_title'  => 'Integrations',
				'parent_slug' => 'options-general.php',
				'menu_slug'   => 'acf-options-site-integrations',
				'position'    => 1,
				'capability'  => 'edit_theme_options',
			));
		}
	}

	/**
	 * Register the default fields for the Global Settings page.
	 * @return void
	 */
	public function register_global_settings_fields(): void {
		if(!function_exists('acf_add_local_field_group')) {
			return;
		}

		$field_contributors = apply_filters('doublee_global_settings_contributors', ['Double-E Design Base Plugin']);
		$contributors_html = '<ul>';
		foreach($field_contributors as $contributor) {
			$contributors_html .= '<li>' . esc_html($contributor) . '</li>';
		}
		$contributors_html .= '</ul>';
		$about_message = <<<HTML
			<p>These settings come from Double-E Design's custom plugins and are designed for use with the plugins listed below and your custom theme.</p>
			<p>The following plugins and themes have indicated that they have contributed Global Settings fields:</p>
			$contributors_html
			<p>If you deactivate or delete any of the above, the data for their fields may still remain in the database but will not be editable from this screen.</p>
			<p>Other themes and plugins may also have added options, but have not declared that.</p>
			<p>Other plugins and themes may use some of these settings, but compatibility is not guaranteed.</p>
			<hr/>
			<details>
				<summary>For developers</summary>
				<p>If you are the developer of a theme or plugin that has added fields to this screen but it is not listed, please use the <code>doublee_global_settings_contributors</code> filter to add it to the above list.</p>
				<p>If you would like to implement the fields in this screen in your own theme or plugin, first deactivate what you don't intend to use from the above list. You can then find the values of the remaining fields in the <code>wp_options</code> table and get them in code using <code>get_option()</code>.</p>
				<p>The content of the About tab comes from the Double-E Design Base Plugin. In the absence of that plugin, the Global Settings page and a subset of key fields may be registered by another plugin listed above.</p>
			</details>
		HTML;

		$default = array(
			'key'                   => 'group_doublee_global_settings',
			'title'                 => 'Global options',
			'fields'                => array(
				array(
					'key'               => 'tab--global__brand',
					'label'             => 'Brand',
					'type'              => 'tab',
					'required'          => 0,
					'conditional_logic' => 0,
					'placement'         => 'left',
					'endpoint'          => 0,
					'selected'          => 0,
					'repeatable'        => true,
				),
				array(
					'key'           => 'field_65910e95e0efe',
					'label'         => 'Logo',
					'name'          => 'logo',
					'type'          => 'image',
					'wrapper'       => array(
						'width' => '65',
					),
					'return_format' => 'id',
					'library'       => 'all',
					'preview_size'  => 'medium',
					'uploader'      => '',
					'repeatable'    => true,
				),
				array(
					'key'        => 'tab--global__contact-details',
					'label'      => 'Contact details',
					'type'       => 'tab',
					'placement'  => 'left',
					'endpoint'   => 0,
					'selected'   => 0,
					'repeatable' => true,
				),
				array(
					'key'               => 'field_60e6b78dff698',
					'label'             => 'Contact details',
					'name'              => 'contact_details',
					'aria-label'        => '',
					'type'              => 'group',
					'instructions'      => '',
					'required'          => 0,
					'conditional_logic' => 0,
					'wrapper'           => array(
						'width' => '',
						'class' => '',
						'id'    => '',
					),
					'layout'            => 'block',
					'repeatable'        => true,
					'sub_fields'        => array(
						array(
							'key'        => 'field_60e6b7a1ff699',
							'label'      => 'Phone',
							'name'       => 'phone',
							'type'       => 'text',
							'repeatable' => true,
						),
						array(
							'key'          => 'field_60e6b7b8ff69a',
							'label'        => 'Address',
							'name'         => 'address',
							'type'         => 'text',
							'instructions' => '',
							'repeatable'   => true,
						),
						array(
							'key'        => 'field_60e6b7caff69b',
							'label'      => 'Suburb',
							'name'       => 'suburb',
							'type'       => 'text',
							'repeatable' => true,
						),
						array(
							'key'        => 'field_60e6b7d1ff69c',
							'label'      => 'State',
							'name'       => 'state',
							'type'       => 'text',
							'repeatable' => true,
						),
						array(
							'key'        => 'field_60e6b7d8ff69d',
							'label'      => 'Postcode',
							'name'       => 'postcode',
							'type'       => 'text',
							'repeatable' => true,
						),
						array(
							'key'        => 'field_6636f261c4ba9',
							'label'      => 'Email',
							'name'       => 'email',
							'type'       => 'text',
							'repeatable' => true,
						),
					),
				),
				array(
					'key'           => 'field_6591102f913cb',
					'label'         => 'Social media links',
					'name'          => 'social_media_links',
					'type'          => 'repeater',
					'layout'        => 'table',
					'button_label'  => 'Add link',
					'rows_per_page' => 20,
					'repeatable'    => true,
					'sub_fields'    => array(
						array(
							'key'               => 'field_6591103c913cc',
							'label'             => 'Label',
							'name'              => 'label',
							'type'              => 'text',
							'required'          => 0,
							'conditional_logic' => 0,
							'wrapper'           => array(
								'width' => '20',
							),
							'parent_repeater'   => 'field_6591102f913cb',
							'repeatable'        => true,
						),
						array(
							'key'             => 'field_6591104b913cd',
							'label'           => 'Font awesome icon',
							'name'            => 'icon',
							'type'            => 'text',
							'wrapper'         => array(
								'width' => '30',
							),
							'parent_repeater' => 'field_6591102f913cb',
							'repeatable'      => true,
						),
						array(
							'key'             => 'field_65911055913ce',
							'label'           => 'URL',
							'name'            => 'url',
							'type'            => 'url',
							'wrapper'         => array(
								'width' => '50',
							),
							'parent_repeater' => 'field_6591102f913cb',
							'repeatable'      => true,
						),
					),
				),
				array(
					'key'        => 'tab--global__about',
					'label'      => 'About this screen',
					'type'       => 'tab',
					'placement'  => 'left',
					'endpoint'   => 0,
					'selected'   => 0,
					'repeatable' => true,
				),
				array(
					'key'        => 'field__global__about-message',
					'type'       => 'message',
					'message'    => $about_message,
					'new_lines'  => 'wpautop',
					'esc_html'   => 0,
					'repeatable' => true,
				),
			),
			'location'              => array(
				array(
					array(
						'param'    => 'options_page',
						'operator' => '==',
						'value'    => 'acf-options-global-options',
					),
				),
			),
			'menu_order'            => 0,
			'position'              => 'normal',
			'style'                 => 'default',
			'label_placement'       => 'top',
			'instruction_placement' => 'label',
			'hide_on_screen'        => '',
			'active'                => true,
			'description'           => '',
			'show_in_rest'          => 0,
			'modified'              => 1740105843,
		);

		// Allow client plugins to add fields to the global settings group
		// Note: If you use this, you should also add your plugin/theme name to the About page
		// using the doublee_global_settings_contributors filter
		$final = apply_filters('doublee_global_settings_fields', $default);

		acf_add_local_field_group($final);
	}

	/**
	 * Register the default fields for the Integrations settings page.
	 * @return void
	 */
	public function register_integrations_settings_fields(): void {
		if(!function_exists('acf_add_local_field_group')) {
			return;
		}

		// Check if client has a Google Maps API key already stored, and keep the field if so; if not don't include it by default
		$google_maps_api_key = get_option('options_google_maps_api_key', '');
		$include_google_maps_api_field = apply_filters('doublee_include_google_maps_api_key_global_setting', ($google_maps_api_key !== ''));
		$google_maps_api_field = $include_google_maps_api_field ? array(
			'key'               => 'field_integration_google_maps_api_key',
			'label'             => 'Google Maps API key',
			'name'              => 'google_maps_api_key',
			'type'              => 'text',
			'conditional_logic' => 0,
			'allow_in_bindings' => 0,
		) : array();

		$third_party_settings_pages = [];
		if(is_plugin_active('instagram-feed-pro/instagram-feed.php')) {
			$third_party_settings_pages['Instagram Feed'] = admin_url('?page=sb-instagram-feed');
		}
		$third_party_links = apply_filters('doublee_integrations_settings_third_party_settings_links', $third_party_settings_pages);
		$third_party_links_html = '<ul class="third-party-integration-settings-links">';
		foreach($third_party_links as $name => $url) {
			$third_party_links_html .= '<li><a href="' . esc_url($url) . '">' . esc_html($name) . ' settings</a> <span class="dashicons dashicons-arrow-right-alt"></span></li>';
		}
		$third_party_links_html .= '</ul>';

		$field_contributors = apply_filters('doublee_integrations_settings_contributors', ['Double-E Design Base Plugin']);
		$contributors_html = '<ul>';
		foreach($field_contributors as $contributor) {
			$contributors_html .= '<li>' . esc_html($contributor) . '</li>';
		}
		$contributors_html .= '</ul>';
		$about_message = <<<HTML
			<p>These settings come from Double-E Design's custom plugins and are designed for use with the plugins listed below and your custom theme.</p>
			<p>The following plugins and themes have indicated that they have contributed Integrations Settings fields:</p>
			$contributors_html
			<p>If you deactivate or delete any of the above, the data for their fields may still remain in the database but will not be editable from this screen.</p>
			<p>Other themes and plugins may also have added options, but have not declared that.</p>
			<p>Other plugins and themes may use some of these settings, but compatibility is not guaranteed.</p>
			<hr/>
			<details>
				<summary>For developers</summary>
				<p>If you are the developer of a theme or plugin that has added fields to this screen but it is not listed, please use the <code>doublee_integrations_settings_contributors</code> filter to add it to the above list.</p>
				<p>If you would like to implement the fields in this screen in your own theme or plugin, first deactivate what you don't intend to use from the above list. You can then find the values of the remaining fields in the <code>wp_options</code> table and get them in code using <code>get_option()</code>.</p>
				<p>The content of the About tab comes from the Double-E Design Base Plugin. In the absence of that plugin, the Global Settings page and a subset of key fields may be registered by another plugin listed above.</p>
			</details>
		HTML;

		$default = array(
			'key'                   => 'group_doublee_integrations_settings',
			'title'                 => 'Integrations settings',
			'fields'                => array(
				array(
					'key'       => 'tab--integrations__assets',
					'label'     => 'Assets',
					'type'      => 'tab',
					'placement' => 'left',
					'endpoint'  => 0,
					'selected'  => 0,
				),
				array(
					'key'       => 'tab--integrations__main',
					'label'     => 'Other integrations',
					'type'      => 'tab',
					'placement' => 'left',
					'endpoint'  => 0,
					'selected'  => 0,
				),
				$google_maps_api_field,
				array(
					'key'      => 'field_integration_third_party_settings_links',
					'type'     => 'message',
					'message'  => $third_party_links_html,
					'esc_html' => 0,
					'selected' => 0,
				),
				array(
					'key'        => 'tab--integrations__about',
					'label'      => 'About this screen',
					'type'       => 'tab',
					'placement'  => 'left',
					'endpoint'   => 0,
					'selected'   => 0,
					'repeatable' => true,
				),
				array(
					'key'        => 'field__integrations__about-message',
					'type'       => 'message',
					'message'    => $about_message,
					'new_lines'  => 'wpautop',
					'esc_html'   => 0,
					'repeatable' => true,
				)
			),
			'location'              => array(
				array(
					array(
						'param'    => 'options_page',
						'operator' => '==',
						'value'    => 'acf-options-site-integrations',
					),
				),
			),
			'menu_order'            => 0,
			'position'              => 'normal',
			'style'                 => 'default',
			'label_placement'       => 'top',
			'instruction_placement' => 'label',
			'active'                => true,
			'show_in_rest'          => 0,
			'modified'              => 1768614690
		);

		// Allow client plugins to add fields to the integrations settings group
		// Note: If you use this, you should also add your plugin/theme name to the About page
		// using the doublee_integrations_settings_contributors filter
		$final = apply_filters('doublee_integrations_settings_fields', $default);

		acf_add_local_field_group($final);
	}

	/**
	 * Register the page behaviour fields for applicable post types.
	 * @return void
	 */
	public function register_page_behaviour_fields(): void {
		if(!function_exists('acf_add_local_field_group')) {
			return;
		}

		acf_add_local_field_group(array(
			'key'                   => 'group_67ca2ef6a0243',
			'title'                 => 'Page behaviour',
			'fields'                => array(
				array(
					'key'               => 'field_67ca2ef660ce2',
					'label'             => 'Redirect',
					'name'              => 'redirect',
					'aria-label'        => '',
					'type'              => 'group',
					'instructions'      => 'When a visitor comes to this page, redirect them to another page. Useful for including a page link in section navigation that you want to go to another website, or for ensuring that users go to the right place after content has been moved but they might arrive via the old link.',
					'required'          => 0,
					'conditional_logic' => 0,
					'layout'            => 'block',
					'sub_fields'        => array(
						array(
							'key'   => 'field_67ca2f2a60ce3',
							'label' => 'URL',
							'name'  => 'url',
							'type'  => 'url',
						),
						array(
							'key'               => 'field_67ca2f3060ce4',
							'label'             => 'Type',
							'name'              => 'type',
							'type'              => 'select',
							'choices'           => array(
								301 => '301 (Permanent)',
								302 => '302 (Temporary)',
							),
							'default_value'     => false,
							'return_format'     => 'value',
							'multiple'          => 0,
							'allow_null'        => 0,
							'allow_in_bindings' => 0,
							'ui'                => 0,
							'ajax'              => 0,
							'create_options'    => 0,
							'save_options'      => 0,
						),
						array(
							'key'          => 'field_67ca30aeb35cc',
							'label'        => 'Open in new tab',
							'name'         => 'open_in_new_tab',
							'type'         => 'true_false',
							'instructions' => 'Only applies to links to the page within this site that account for it. If users put the URL directly into their browser, they will be redirected within that tab.',
							'ui'           => 1,
						),
					),
				),
			),
			'location'              => array_map(function($post_type) {
				return array(
					array(
						'param'    => 'post_type',
						'operator' => '==',
						'value'    => $post_type,
					),
				);
			}, apply_filters('doublee_enable_page_behaviour_options_for_post_types', ['page'])),
			'menu_order'            => 100,
			'position'              => 'side',
			'style'                 => 'default',
			'label_placement'       => 'top',
			'instruction_placement' => 'label',
			'hide_on_screen'        => '',
			'active'                => true,
			'description'           => '',
			'show_in_rest'          => 0,
			'display_title'         => '',
		));
	}


	public function load_classicpress_logo($value, $post_id, $field) {
		// If we're in ClassicPress, sync the logo field it provides to the ACF field on load
		if(function_exists('classicpress_version') && get_option('login_custom_image_state')) {
			$image_id = get_option('login_custom_image_id');
			if($image_id) {
				$value = $image_id;
			}
		}

		return $value;
	}

	public function save_classicpress_logo($post_id): void {
		// If we're in ClassicPress and the logo is updated from the ACF options field, sync the change to the ClassicPress setting
		if($post_id === 'options' && function_exists('classicpress_version')) {
			if(isset($_POST['acf']['field_65910e95e0efe'])) {
				$image_id = intval($_POST['acf']['field_65910e95e0efe']);
				update_option('login_custom_image_state', ($image_id ? 1 : 0));
				update_option('login_custom_image_id', $image_id);
			}
		}
	}
}
