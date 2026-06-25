<?php

namespace Doubleedesign\BasePlugin;

class NavMenuHandler {

	public function __construct() {
		add_filter('nav_menu_meta_box_object', [$this, 'do_not_show_some_types_in_nav_menu_admin']);
	}

	/**
	 * Disable "show in nav menus" for some built-in post types and taxonomies.
	 * @param $post_type_or_taxonomy
	 * @return object|false
	 */
	public function do_not_show_some_types_in_nav_menu_admin($post_type_or_taxonomy): object|false {
		if(!$post_type_or_taxonomy) {
			return false;
		}

		if(in_array($post_type_or_taxonomy->name, ['post', 'post_tag'])) {
			return false;
		}

		return $post_type_or_taxonomy;
	}
}
