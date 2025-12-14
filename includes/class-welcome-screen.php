<?php
/**
 * This class customises the WordPress dashboard welcome screen.
 *
 * @since      3.1.0
 * @package    Doublee
 * @author     Leesa Ward
 */
class Doublee_Welcome_Screen {

	public function __construct() {
		add_action('admin_init', [$this, 'remove_default_welcome_panel']);
		add_action('admin_init', [$this, 'remove_default_metaboxes'], 5);
		add_action('welcome_panel', [$this, 'custom_dashboard_welcome_panel']);

		add_action('admin_init', [$this, 'always_show_welcome_panel'], 1);
		add_action('user_register', [$this, 'ensure_welcome_for_new_user']);
	}

	/**
	 * Disable the WP dashboard welcome panel because it may promote unsupported features
	 * @return void
	 */
	public function remove_default_welcome_panel(): void {
		remove_action('welcome_panel', 'wp_welcome_panel');
	}

	/**
	 * Remove default dashboard metaboxes because they are often unrepresentative of the site's features (e.g., summary only shows posts and pages)
	 * or are just not useful to clients (e.g., WordPress News), and I'd rather they focus on the custom welcome panel content.
	 * @return void
	 */
	public function remove_default_metaboxes(): void {
		remove_meta_box('dashboard_primary', 'dashboard', 'side'); // WordPress News
		remove_meta_box('dashboard_quick_press', 'dashboard', 'side'); // Quick draft
		remove_meta_box('dashboard_right_now', 'dashboard', 'normal'); // At a Glance
		remove_meta_box('dashboard_activity', 'dashboard', 'normal'); // Activity
		remove_meta_Box('dashboard_site_health', 'dashboard', 'normal'); // Site Health Status
	}

	/**
	 * Custom welcome panel content
	 * @return void
	 */
	public function custom_dashboard_welcome_panel(): void {
		$current_user = wp_get_current_user();
		$username = $current_user->user_firstname ? $current_user->user_firstname : $current_user->user_login;

		// Get all public post types except attachments, to create links to them.
		// Client themes/plugins can modify this list via the 'doublee_welcome_screen_post_types' filter
		// (e.g., remove the link to create/edit Posts for clients who don't really blog)
		$post_types = apply_filters(
			'doublee_welcome_screen_post_types',
			array_filter(get_post_types(['public' => true], 'objects'), function($pt) {
				return !in_array($pt->name, ['attachment']);
			})
		);

		$primary_links = [];
		$secondary_links = [];

		if(current_user_can('edit_posts')) {
			foreach($post_types as $post_type) {
				$primary_links[] = [
					'label' => sprintf(
						'Create or edit %s %s',
						(preg_match('/^[aeiou]/i', $post_type->labels->singular_name)) ? 'an' : 'a',
						$post_type->labels->singular_name
					),
					'url'   => admin_url('edit.php?post_type=' . $post_type->name),
					'icon'  => $post_type->menu_icon,
				];
			}
		}

		if(class_exists('Ninja_Forms') && current_user_can('manage_forms')) {
			$primary_links[] = [
				'label' => 'Check form submissions',
				'url'   => admin_url('admin.php?page=nf-submissions'),
				'icon'  => 'dashicons-feedback',
			];
		}

		if(current_user_can('edit_theme_options')) {
			$secondary_links[] = [
				'label' => 'Manage navigation menus',
				'url'   => admin_url('nav-menus.php'),
				'icon'  => 'dashicons-menu',
			];
		}

		$site_name = get_bloginfo('name');
		$secondary_links[] = [
			'label' => "Update $site_name's contact information",
			'url'   => admin_url('themes.php?page=acf-options-global-options'),
			'icon'  => 'dashicons-email',
		];
		$secondary_links[] = [
			'label' => 'Change your password',
			'url'   => admin_url('profile.php'),
			'icon'  => 'dashicons-lock',
		];
		if(current_user_can('list_users')) {
			$secondary_links[] = [
				'label' => 'Manage user accounts',
				'url'   => admin_url('users.php'),
				'icon'  => 'dashicons-admin-users',
			];
		}

		$primary_links = apply_filters('doublee_welcome_screen_primary_links', $primary_links);
		$secondary_links = apply_filters('doublee_welcome_screen_secondary_links', $secondary_links);

		$primary_links_html = '';
		foreach($primary_links as $link) {
			$icon_html = $link['icon'] ? sprintf('<span class="dashicons %s"></span> ', esc_attr($link['icon'])) : '';
			$primary_links_html .= sprintf(
				'<li><a href="%s" class="button button-primary button-large">%s%s</a></li>',
				esc_url($link['url']),
				$icon_html,
				esc_html($link['label'])
			);
		}

		$secondary_links_html = '';
		foreach($secondary_links as $link) {
			$icon_html = $link['icon'] ? sprintf('<span class="dashicons %s"></span> ', esc_attr($link['icon'])) : '';
			$secondary_links_html .= sprintf(
				'<li><a href="%s">%s%s</a></li>',
				esc_url($link['url']),
				$icon_html,
				esc_html($link['label'])
			);
		}

		echo <<<HTML
		<section class="welcome-panel__content">
			<header class="welcome-panel__content__header">
				<h2>Welcome, $username!</h2>
				<p class="lead">What would you like to do today?</p>
			</header>
			<div class="welcome-panel__content__body">
				<ul class="welcome-panel-list welcome-panel-list--primary">
					$primary_links_html
				</ul>
				<ul class="welcome-panel-list welcome-panel-list--secondary">
					$secondary_links_html
				</ul>
			</div>
		</section>
		HTML;
	}

	public function always_show_welcome_panel(): void {
		$user_id = get_current_user_id();
		if ($user_id && get_user_meta($user_id, 'show_welcome_panel', true) !== '1') {
			update_user_meta($user_id, 'show_welcome_panel', 1);
		}
	}

	public function ensure_welcome_for_new_user(int $user_id): void {
		update_user_meta($user_id, 'show_welcome_panel', 1);
	}
}
