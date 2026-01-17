<?php
/**
 * Plugin name: Double-E Design Base Plugin
 * Description: Customisations and common custom functionality for Double-E Design websites.
 *
 * Author:              Double-E Design
 * Author URI:          https://www.doubleedesign.com.au
 * Version:             4.1.1
 * Requires PHP:        8.3
 * Text Domain:         doublee
 * Requires plugins:    advanced-custom-fields-pro
 *
 * @package Doubleedesign\BasePlugin
 */

use Doubleedesign\BasePlugin\PluginEntrypoint;

define('PAGE_FOR_POSTS', get_option('page_for_posts'));

/**
 * Load Composer autoloader
 */
require_once plugin_dir_path(__FILE__) . 'vendor/autoload.php';

/**
 * Initialize the core plugin class
 */
new PluginEntrypoint();


/**
 * Create activation and deactivation hooks and functions, so we can do things
 * when the plugin is activated, deactivated, or uninstalled.
 * These need to be in this plugin root file to work, so to run our plugin's functions from within its
 * classes, we simply call a function (from the plugin class) inside the function that needs to be here.
 * @return void
 */
function activate_doublee(): void {
	PluginEntrypoint::activate();
}
function deactivate_doublee(): void {
	PluginEntrypoint::deactivate();
}
function uninstall_doublee(): void {
	PluginEntrypoint::uninstall();
}
register_activation_hook(__FILE__, 'activate_doublee');
register_deactivation_hook(__FILE__, 'deactivate_doublee');
register_uninstall_hook(__FILE__, 'uninstall_doublee');

/**
 * Enqueue styles and scripts to make xdebug output more readable for admins in local environments
 * Note: WP_ENVIRONMENT_TYPE is a constant defined in wp-config.php
 *
 * @return void
 */
function doublee_make_xdebug_pretty(): void {
	if(defined('WP_ENVIRONMENT_TYPE') && WP_ENVIRONMENT_TYPE === 'local' && current_user_can('administrator')) {
		wp_enqueue_style(
			'xdebug-styles',
			'/wp-content/plugins/doublee-base-plugin/assets/xdebug-styles.css',
			[],
			'1.0.0'
		);

		wp_enqueue_style('highlight-code', 'https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.8.0/styles/github.min.css', [], '11.8.0'); // base theme
		wp_enqueue_script('highlight-js', 'https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.8.0/highlight.min.js', [], '11.8.0');
		wp_enqueue_script('highlight-php', 'https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.8.0/languages/php.min.js', [], '11.8.0');
		wp_enqueue_script('xdebug-markup', '/wp-content/plugins/doublee-base-plugin/assets/xdebug-markup.js', [], '1.0.0');
	}
}
//add_action('wp_enqueue_scripts', 'doublee_make_xdebug_pretty');
//add_action('admin_enqueue_scripts', 'doublee_make_xdebug_pretty');

