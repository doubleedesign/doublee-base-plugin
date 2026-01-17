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
}
