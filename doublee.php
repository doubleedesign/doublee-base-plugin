<?php
/**
 * Plugin name: Double-E Design Base Plugin
 * Description: Customisations and common custom functionality for Double-E Design websites.
 *
 * Author:      		Double-E Design
 * Author URI:  		https://www.doubleedesign.com.au
 * Version:     		3.0.0
 * Requires at least: 	6.3.2
 * Requires PHP: 		8.1.9
 * Text Domain: 		doublee
 * Requires plugins: 	advanced-custom-fields-pro
 *
 * @package Doublee
 */

define ('PAGE_FOR_POSTS', get_option('page_for_posts'));

// Load the plugin files
require_once('class-doublee.php');

/**
 * Create activation and deactivation hooks and functions, so we can do things
 * when the plugin is activated, deactivated, or uninstalled.
 * These need to be in this plugin root file to work, so to run our plugin's functions from within its
 * classes, we simply call a function (from the plugin class) inside the function that needs to be here.
 * @return void
 */
function activate_doublee(): void {
	Doublee::activate();
}
function deactivate_doublee(): void {
	Doublee::deactivate();
}
function uninstall_doublee(): void {
	Doublee::uninstall();
}
register_activation_hook(__FILE__, 'activate_doublee');
register_deactivation_hook(__FILE__, 'deactivate_doublee');
register_uninstall_hook(__FILE__, 'uninstall_doublee');


// Load and run the rest of the plugin
new Doublee();
