<?php
namespace Doubleedesign\BasePlugin;

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * Development of this plugin was started using the WordPress Plugin Boilerplate Generator https://wppb.me/
 */

// If this file is called directly, abort.
if(!defined('WPINC')) {
	die;
}

/**
 * Current plugin version.
 * Rename this for your plugin and update it as you release new versions.
 */
const DOUBLEE_VERSION = '4.4.2';


/**
 * Path of plugin root folder
 */
define('DOUBLEE_PLUGIN_PATH', plugin_dir_path(__FILE__));


/**
 * The core plugin class
 *
 * @since      1.0.0
 * @package    Doublee
 * @subpackage Doublee/includes
 * @author     Leesa Ward
 */
class PluginEntrypoint {

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string $version The current version of the plugin.
	 */
	protected string $version;

	/**
	 * Set up the core functionality of the plugin in the constructor
	 * by loading the modular classes of functionality.
	 *
	 * @since    2.0.0
	 */
	public function __construct() {
		$this->version = DOUBLEE_VERSION;

		// Call the function that initialises our classes
		// and sets up some values that can be used throughout this file
		$this->load_classes();
	}


	/**
	 * Load the required dependencies for this plugin.
	 * Each time we create a class file, we need to add it and initialise it here.
	 *
	 * @return   void
	 * @since    2.0.0
	 * @access   private
	 */
	private function load_classes(): void {
		new MustUsePluginHandler();
		new UserRolesAndCapabilities();
		new WelcomeScreen();
		new AdminNotices();
		new GlobalOptions();
		new AdminUI();
		new PluginListTableHandler();
		new SEO();
		new PageBehaviour();
		new CPTIndexHandler();
		new MediaHandler();
		new NavMenuHandler();

		if(class_exists('WooCommerce')) {
			new WooCommerceHandler();
		}
	}

	public static function is_must_use_plugin(): bool {
		$mu_plugins = wp_get_mu_plugins(); // MustUsePluginHandler::$mustUse may not have been populated yet
		return array_find($mu_plugins, function($plugin) {
				return str_contains($plugin, 'doublee-base-plugin');
			}) !== null;
	}

	/**
	 * Run functions on plugin activation.
	 * Things we only want to run once - when the plugin is activated
	 * (as opposed to every time the admin initialises, for example)
	 * @return void
	 */
	public static function activate(): void {
		do_action('doublee_base_activate');
	}

	/**
	 * Run functions on plugin deactivation.
	 * NOTE: This can be a destructive operation!
	 * Basically anything done by the plugin should be reversed or adjusted to work with built-in WordPress functionality
	 * if the plugin is deactivated. However, it is important to note that often developers/administrators will
	 * deactivate a plugin temporarily to troubleshoot something and then reactivate it, so we should not do a full cleanup
	 * (such as deleting data) by default.
	 *
	 * Consider carefully whether deactivation or uninstallation is the better place to remove/undo something.
	 *
	 * @return void
	 */
	public static function deactivate(): void {
		do_action('doublee_base_deactivate');
	}


	/**
	 * Run functions on plugin uninstallation
	 * NOTE: This is for VERY destructive operations!
	 * There are some things that it is best practice to do on uninstallation,
	 * for example custom database tables created by the plugin (if we had any)
	 * should be deleted when the plugin is uninstalled from the site.
	 * Think of this as "not using it anymore" levels of cleanup.
	 *
	 * Consider carefully whether deactivation or uninstallation is the better place to remove/undo something.
	 *
	 * @return void
	 */
	public static function uninstall(): void {
		do_action('doublee_base_uninstall');
	}


	/**
	 * Function to retrieve the version number of the plugin.
	 * @wp-hook
	 *
	 * @return    string    The version number of the plugin.
	 * @since     1.0.0
	 */
	public function get_version(): string {
		return $this->version;
	}


	/**
	 * Function to retrieve the name of the plugin for use in the admin
	 * (e.g., labelling stuff)
	 *
	 * @return string   The name of the plugin
	 * @since     1.0.0
	 */
	public static function get_name(): string {
		$plugin_data = get_plugin_data(DOUBLEE_PLUGIN_PATH . 'doublee.php');

		return $plugin_data['Name'];
	}
}
