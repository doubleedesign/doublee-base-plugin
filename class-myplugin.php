<?php

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
if (!defined('WPINC')) {
	die;
}

/**
 * Current plugin version.
 * Rename this for your plugin and update it as you release new versions.
 */
const MYPLUGIN_VERSION = '1.1.0';


/**
 * Path of plugin root folder
 */
define('MYPLUGIN_PLUGIN_PATH', plugin_dir_path(__FILE__));


/**
 * The core plugin class
 *
 * @since      1.0.0
 * @package    MyPlugin
 * @subpackage MyPlugin/includes
 * @author     Leesa Ward
 */
class MyPlugin {

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string $version The current version of the plugin.
	 */
	protected string $version;


	/**
	 * Variables to store instances of our custom classes
	 * This is one option of how to create an instance of a class, in this case using the same instance in multiple places.
	 * Instances can also be created as-needed within functions below,
	 * so it is not mandatory to do this for every custom class we create;
	 * we can make an informed decision each time about the best way/place to use a class.
	 * Considerations include:
	 * - Do we need to use it multiple times or just once?
	 * - Does it make sense to use the same instance, or should we have a new instance/object each time?
	 * - Does one method "break" something?
	 * - Is one method more efficient than another?
	 * - Which way might be clearer and easier to understand? Are there any downsides to the "easier" way?
	 */
	private static MyPlugin_Users $user_functions;


	/**
	 * Set up the core functionality of the plugin in the constructor
	 * by loading the modular classes of functionality.
	 *
	 * @since    2.0.0
	 */
	public function __construct() {
		$this->version = MYPLUGIN_VERSION;

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
		require_once MYPLUGIN_PLUGIN_PATH . '/includes/class-users.php';
		self::$user_functions = new MyPlugin_Users();

		require_once MYPLUGIN_PLUGIN_PATH . '/includes/class-admin-notices.php';
		new MyPlugin_Admin_Notices();

		require_once MYPLUGIN_PLUGIN_PATH . '/includes/class-admin-ui.php';
		new MyPlugin_Admin_UI();

		require_once MYPLUGIN_PLUGIN_PATH . '/includes/class-seo.php';
		new MyPlugin_SEO();

		if (class_exists('WooCommerce')) {
			require_once MYPLUGIN_PLUGIN_PATH . '/includes/class-woocommerce.php';
			new MyPlugin_WooCommerce();
		}
	}


	/**
	 * Run functions on plugin activation.
	 * Things we only want to run once - when the plugin is activated
	 * (as opposed to every time the admin initialises, for example)
	 * @return void
	 */
	public static function activate(): void {
		self::$user_functions->create_roles();
		self::$user_functions->reassign_users_roles();
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
		self::$user_functions->delete_roles();
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
		self::$user_functions->revert_users_roles(true);
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
		$plugin_data = get_plugin_data(MYPLUGIN_PLUGIN_PATH . 'myplugin.php');

		return $plugin_data['Name'];
	}


	/**
	 * Shared utility function to conditionally change the ACF JSON save location
	 * - to be used for field groups relevant to CPTs, taxonomies, etc. introduced by this plugin
	 * - when called, must be wrapped in a relevant conditional to identify the group to save to the plugin
	 * @return void
	 */
	public static function override_acf_json_save_location(): void {
		// remove this filter so it will not affect other groups
		remove_filter('acf/settings/save_json', 'override_acf_json_save_location', 400);

		add_filter('acf/settings/save_json', function ($path) {
			// remove this filter so it will not affect other groups
			remove_filter('acf/settings/save_json', 'override_acf_json_save_location', 400);

			// override save path in this case
			return MYPLUGIN_PLUGIN_PATH . 'assets/acf-json';
		}, 9999);
	}


	/**
	 * Utility function to get lists of filenames where ACF JSON files are stored in the plugin and theme
	 * @return array
	 */
	public static function get_acf_json_filenames(): array {
		$in_events_plugin = array();
		$in_plugin = scandir(MYPLUGIN_PLUGIN_PATH . 'assets/acf-json/');
		$in_parent_theme = scandir(get_template_directory() . '/acf-json/');
		$in_theme = scandir(get_stylesheet_directory() . '/acf-json/');

		if (class_exists('Doublee_Events') && defined('DOUBLEE_EVENTS_PLUGIN_PATH')) {
			$in_events_plugin = scandir(DOUBLEE_EVENTS_PLUGIN_PATH . 'assets/acf-json/');
		}

		return array(
			'plugin'        => array_values(array_filter($in_plugin, fn($item) => str_contains($item, '.json'))),
			'parent_theme'  => array_values(array_filter($in_parent_theme, fn($item) => str_contains($item, '.json'))),
			'theme'         => array_values(array_filter($in_theme, fn($item) => str_contains($item, '.json'))),
			'events_plugin' => class_exists('Doublee_Events') ? array_values(array_filter($in_events_plugin, fn($item) => str_contains($item, '.json'))) : array()
		);
	}

}
