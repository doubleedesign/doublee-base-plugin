<?php
namespace Doubleedesign\BasePlugin;
class PageBehaviour {

	public function __construct() {
		add_action('template_redirect', [$this, 'redirect_page']);
	}

	public function redirect_page(): void {
		if(class_exists('ACF')) {
			$redirect = get_field('redirect');
			if(!empty($redirect['url'])) {
				wp_redirect($redirect['url'], $redirect['type']);
				exit;
			}
		}
	}
}
