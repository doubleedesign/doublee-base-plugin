<?php
namespace Doubleedesign\BasePlugin;

use WP_Post;

/**
 * This class defines an under-the-hood custom post type to enable inclusion of CPT archives in queries.
 * For example, allowing a CPT archive to be selected like a page in the ACF post object or relationship fields.
 *
 * @since      4.1.0
 *
 * @package    Doublee
 *
 * @author     Leesa Ward
 */
class CPTIndexHandler {

	public function __construct() {
		add_action('init', [$this, 'register_cpt_index_type'], 100);
		add_action('init', [$this, 'create_cpt_indexes'], 998);
		add_action('init', [$this, 'delete_cpt_indexes'], 999);
		add_filter('post_type_link', [$this, 'use_real_index_url_for_permalinks'], 10, 2);
		add_filter('template_redirect', [$this, 'redirect_to_real_archive'], 20);
		add_filter('breadcrumbs_filter_post_types', [$this, 'no_breadcrumbs_for_cpt_indexes']);
	}

	public function register_cpt_index_type(): void {
		$labels = array(
			'name'                  => __('Indexes', 'Post Type General Name', 'doublee'),
			'singular_name'         => __('Index', 'Post Type Singular Name', 'doublee'),
			'menu_name'             => __('Indexes', 'doublee'),
			'name_admin_bar'        => __('Index', 'doublee'),
			'archives'              => __('Index Archives', 'doublee'),
			'attributes'            => __('Index Attributes', 'doublee'),
			'parent_item_colon'     => __('Parent Index:', 'doublee'),
			'all_items'             => __('All Indexes', 'doublee'),
			'add_new_item'          => __('Add New Index', 'doublee'),
			'add_new'               => __('Add New', 'doublee'),
			'new_item'              => __('New Index', 'doublee'),
			'edit_item'             => __('Edit Index', 'doublee'),
			'update_item'           => __('Update Index', 'doublee'),
			'view_item'             => __('View Index', 'doublee'),
			'view_items'            => __('View Indexes', 'doublee'),
			'search_items'          => __('Search Indexes', 'doublee'),
			'not_found'             => __('Not found', 'doublee'),
			'not_found_in_trash'    => __('Not found in Trash', 'doublee'),
			'featured_image'        => __('Featured Image', 'doublee'),
			'set_featured_image'    => __('Set featured image', 'doublee'),
			'remove_featured_image' => __('Remove featured image', 'doublee'),
			'use_featured_image'    => __('Use as featured image', 'doublee'),
			'insert_into_item'      => __('Insert into index', 'doublee'),
			'uploaded_to_this_item' => __('Uploaded to this index', 'doublee'),
			'items_list'            => __('Indexes list', 'doublee'),
			'items_list_navigation' => __('Indexes list navigation', 'doublee'),
			'filter_items_list'     => __('Filter index list', 'doublee'),
		);
		$args = array(
			'label'               => __('Index', 'doublee'),
			'description'         => __('Proxy for CPT indexes, allowing them to be accessed like post objects in certain contexts.', 'doublee'),
			'labels'              => $labels,
			'supports'            => array('title', 'thumbnail', 'excerpt'),
			'hierarchical'        => false,
			'public'              => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'menu_position'       => 20,
			'menu_icon'           => 'dashicons-editor-ul',
			'show_in_admin_bar'   => false,
			'show_in_nav_menus'   => false,
			'can_export'          => true,
			'has_archive'         => false,
			'exclude_from_search' => false,
			'publicly_queryable'  => false, // no front-end access
			'show_in_rest'        => false, // disables block editor
			'capability_type'     => 'post',
			// Disable creation and deletion of indexes via the admin UI
			'capabilities'        => array(
				'create_posts' => 'do_not_allow',
				'delete_post'  => 'do_not_allow',
				'delete_posts' => 'do_not_allow',
				'edit_post'    => 'edit_posts',
			),
		);
		register_post_type('cpt_index', $args);
	}

	/**
	 * Get a list of custom post types that should have indexes created.
	 * Can be customised in themes and plugins using the 'doublee_indexable_custom_post_types' filter.
	 *
	 * @return array Array of WP_Post_Type objects for indexable CPTs.
	 */
	public function get_indexable_post_types(): array {
		$post_types = get_post_types(['publicly_queryable' => true, '_builtin' => false], 'objects');

		$include = apply_filters('doublee_indexable_custom_post_types', array_keys($post_types ?? []));

		if(empty($include)) {
			return [];
		}

		// Filter the default list
		$filtered = array_filter($post_types, function($def, $key) use ($include) {
			return in_array($key, $include);
		}, ARRAY_FILTER_USE_BOTH);

		// Ensure any CPTs added to the $include list by a plugin or theme are added
		$missing = array_diff($include, array_keys($filtered));
		foreach($missing as $cpt) {
			$def = get_post_type_object($cpt);
			if($def) {
				$filtered[$cpt] = $def;
			}
			else {
				error_log("CPTIndexHandler: get_indexable_post_types - post type '$cpt' does not exist when trying to include it as indexable.");
			}
		}

		return $filtered;
	}

	/**
	 * Create an "index" post object for each indexable custom post type.
	 * NOTE: Index title is editable in the admin so should not be used for any logic.
	 *
	 * @return void
	 */
	public function create_cpt_indexes(): void {
		$post_types = $this->get_indexable_post_types();

		$existing_indexes = get_posts(array('post_type' => 'cpt_index', 'posts_per_page' => -1)) ?? [];
		$existing_indexes = array_map(fn($post) => get_post_meta($post->ID, 'indexed_post_type', true), $existing_indexes);
		$existing_indexes = array_filter($existing_indexes); // filter out any null values

		foreach($post_types as $key => $post_type) {
			$index_slug = strtolower($post_type->labels->archives ?? $post_type->label ?? $post_type->name);

			if(!in_array($key, $existing_indexes)) {
				wp_insert_post(array(
					'post_title'  => $post_type->labels->archive ?? $post_type->label ?? $post_type->name,
					'post_name'   => $index_slug,
					'post_type'   => 'cpt_index',
					'post_status' => 'publish',
					'meta_input'  => array(
						'indexed_post_type' => $post_type->name,
					),
				));
			}
		}
	}

	/**
	 * Delete any "index" post objects for CPTs that are no longer indexable.
	 * NOTE: Index title is editable in the admin so should not be used for any logic.
	 *
	 * @return void
	 */
	public function delete_cpt_indexes(): void {
		$post_types = $this->get_indexable_post_types();
		$indexes = get_posts(array(
			'post_type'      => 'cpt_index',
			'posts_per_page' => -1,
			'post_status'    => 'any',
		));

		foreach($indexes as $index) {
			$indexed_type = get_post_meta($index->ID, 'indexed_post_type', true);
			if(!in_array($indexed_type, array_keys($post_types))) {
				wp_delete_post($index->ID, true);
			}
		}
	}

	/**
	 * Make the result of get_the_permalink() for a CPT index return the real archive URL for that CPT,
	 * not that of the index object (which intentionally has no front-end experience).
	 *
	 * @param string $url
	 * @param WP_Post $post
	 * @return string
	 */
	public function use_real_index_url_for_permalinks(string $url, WP_Post $post): string {
		if($post->post_type !== 'cpt_index') {
			return $url;
		}

		$indexed_type = get_post_meta($post->ID, 'indexed_post_type', true);
		if($indexed_type) {
			$archive_link = get_post_type_archive_link($indexed_type);
			if($archive_link) {
				return $archive_link;
			}
		}

		return $url;
	}

	/**
	 * Redirect any front-end requests for a CPT index to the real archive URL for that CPT.
	 * Note: Because the CPT is not publicly queryable, this doesn't actually do anything out of the box, but is here as a fallback.
	 *
	 * @return void
	 */
	public function redirect_to_real_archive(): void {
		$queried_object = get_queried_object();
		if(!$queried_object) {
			return;
		}
		if(!$queried_object instanceof WP_Post) {
			return;
		}
		if($queried_object->post_type !== 'cpt_index') {
			return;
		}

		$indexed_type = get_post_meta($queried_object->ID, 'indexed_post_type', true);
		if($indexed_type) {
			$archive_link = get_post_type_archive_link($indexed_type);
			if($archive_link) {
				wp_redirect($archive_link, 301);
				exit;
			}
		}
	}

	/**
	 * Remove the breadcrumb options in the admin for this CPT because they wouldn't be used anywhere.
	 * @param array $breadcrumbable_post_types
	 * @return array
	 */
	public function no_breadcrumbs_for_cpt_indexes(array $breadcrumbable_post_types): array {
		unset($breadcrumbable_post_types['cpt_index']);

		return $breadcrumbable_post_types;
	}

}
