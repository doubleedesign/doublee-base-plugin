<?php
namespace Doubleedesign\BasePlugin;

/**
 * This class defines basic SEO functionality for sensible defaults in the absence of an SEO plugin
 * ...and customisations for some SEO plugins.
 *
 * @since      1.0.0
 * @package    Doublee
 * @author     Leesa Ward
 */
class SEO {

	public function __construct() {
		add_filter('wp_title', [$this, 'basic_seo_title'], 10, 2);
		add_filter('the_seo_framework_pre_get_document_title', [$this, 'fix_archive_titles'], 10, 2);
		add_action('plugins_loaded', [$this, 'customise_seo_framework_webmaster_settings'], 20);
		add_filter('the_seo_framework_default_site_options', [$this, 'set_default_seo_framework_options']);
		// Disable Homepage Settings in the central SEO Framework settings - just use the settings on the page itself
		add_filter('the_seo_framework_home_metabox', '__return_false');
		add_action('plugins_loaded', [$this, 'customise_seo_framework_handling_of_cpt_archives']);
	}

	/**
	 * Creates a nicely formatted and more specific title element text
	 * for output in head of document, based on current view.
	 *
	 * @param string $title Default title text for current view.
	 * @param string $sep Optional separator.
	 *
	 * @return string Filtered title.
	 */
	function basic_seo_title($title, $sep): string {
		global $paged, $page;
		$override_sep = "|";

		if(is_feed()) {
			return $title;
		}

		if(is_home()) {
			if(defined(PAGE_FOR_POSTS)) {
				$title = get_the_title(PAGE_FOR_POSTS);
			} else {
				$title = get_bloginfo('name');
			}
		}

		if(is_page()) {
			$title = get_the_title();
		}

		if(is_post_type_archive()) {
			$queried_object = get_queried_object();
			$title = $queried_object->label;
		}

		if(is_singular()) {
			$title = get_the_title();
		}

		// Add site name
		$name = get_bloginfo('name');
		$title = "$title $override_sep $name";

		// Add the site description for the home/front page.
		$site_description = get_bloginfo('description', 'display');
		if($site_description && (is_home() || is_front_page())) {
			$title = "$title $override_sep $site_description";
		}

		// Add a page number if necessary.
		if($paged >= 2 || $page >= 2) {
			$title = "$title $override_sep " . sprintf(__('Page %s', 'starterkit'), max($paged, $page));
		}

		return $title;
	}

	/**
	 * Override the Archive: prefix when The SEO Framework is active (it doesn't have an admin option for this)
	 * @param $title
	 * @param $id
	 * @return string
	 */
	function fix_archive_titles($title, $id = null): string {
		if(is_post_type_archive() && str_starts_with($title, 'Archives: ')) {
			return str_replace('Archives: ', '', $title);
		}

		return $title;
	}

	function customise_seo_framework_webmaster_settings(): void {
		if(!defined('THE_SEO_FRAMEWORK_PRESENT')) return;

		// Opt-out of some of the webmaster tools verification fields
		// Note: You can disable the Webmaster box entirely using the the_seo_framework_webmaster_metabox filter
		add_filter('the_seo_framework_webmaster_fields', function($fields) {
			unset($fields['pinterest']);
			unset($fields['yandex']);
			unset($fields['baidu']);
			unset($fields['bing']);

			if(is_plugin_active('google-site-kit/google-site-kit.php')) {
				unset($fields['google']);
			}

			return $fields;
		});

		// Clear existing values from the database
		$settings = get_option(\THE_SEO_FRAMEWORK_SITE_OPTIONS);
		$settings['pint_verification'] = '';
		$settings['yandex_verification'] = '';
		$settings['baidu_verification'] = '';
		if(is_plugin_active('google-site-kit/google-site-kit.php')) {
			$settings['google_verification'] = '';
		}
		update_option(\THE_SEO_FRAMEWORK_SITE_OPTIONS, $settings);
	}

	function set_default_seo_framework_options($options): array {
		$options['display_list_edit_options'] = false;
		$options['display_user_edit_options'] = false;
		$options['display_seo_bar_tables'] = false;
		$options['oembed_scripts'] = false;
		$options['social_title_rem_additions'] = false;

		if(class_exists('Doubleedesign\Comet\Core\Config') && class_exists('Doubleedesign\Comet\Core\ColorUtils')) {
			$colours = (new \Doubleedesign\Comet\Core\ColorUtils)->get_theme_colour_values();
			$options['theme_color'] = $colours['primary'] ?? '';
		}

		return $options;
	}

	function customise_seo_framework_handling_of_cpt_archives(): void {
		// If a CPT has an "index" (introduced by this plugin) and that index has a custom redirect set,
		// remove the CPT from The SEO Framework's CPT Archive metabox found in its main settings.
		// If this leaves no CPTs, it will disable the metabox entirely.
		// TODO: Have the SEO metabox enabled on CPT Indexes, and have those values used for the CPT archive SEO metadata
		// instead of editing CPT archive metadata in the general SEO settings area.
		add_filter('the_seo_framework_public_post_type_archives', function($public_post_types) {
			foreach($public_post_types as $post_type) {
				if(CPTIndexHandler::post_type_has_custom_redirect($post_type)) {
					unset($public_post_types[$post_type]);
				}
			}

			return $public_post_types;
		});
	}
}
