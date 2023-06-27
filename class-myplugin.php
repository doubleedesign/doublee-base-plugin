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
 * Currently plugin version.
 * Rename this for your plugin and update it as you release new versions.
 */
const MYPLUGIN_VERSION = '1.0.0';


/**
 * Path of plugin root folder
 */
define("MYPLUGIN_PLUGIN_PATH", plugin_dir_path(__FILE__));


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
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin. We generally don't expect to need to edit the loader.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      WPPB_Loader $loader Maintains and registers all hooks for the plugin.
	 */
	protected WPPB_Loader $loader;


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
	 * by loading dependencies and then running functions to call the hooks etc.
	 *
	 * Each time we add a function to this file, we call it here,
	 * except for activation/deactivation/uninstallation as those are static functions called in the plugin root file.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		$this->version = MYPLUGIN_VERSION;

		// Call the function that makes our classes available
		// and sets up some values that can be used throughout this file
		$this->load_dependencies();

		// Call our other custom functions defined below
		$this->setup_admin_notices();
		$this->wp_admin_customisations();
	}

	/**
	 * Load the required dependencies for this plugin.
	 * Each time we create a class file, we need to add it here
	 * and then use it in the functions below as appropriate.
	 *
	 * @return   void
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies(): void {
		require_once MYPLUGIN_PLUGIN_PATH . '/includes/class-loader.php';
		$this->loader = new WPPB_Loader();

		require_once MYPLUGIN_PLUGIN_PATH . '/includes/class-users.php';
		self::$user_functions = new MyPlugin_Users();

		require_once MYPLUGIN_PLUGIN_PATH . '/includes/class-admin-notices.php';
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
	 * For each class or type of functionality as appropriate,
	 * we can create a function in this file to call its functions
	 * using the appropriate WordPress action hooks and filters (or action hooks and filters from another plugin).
	 * Note: Functions run on activation/deactivation/uninstallation are an exception to this approach.
	 *
	 * We call these functions in the constructor above.
	 */

	/**
	 * Call functions for adding admin notices.
	 * @return void
	 */
	private function setup_admin_notices(): void {
		$class = new MyPlugin_Admin_Notices();

		$this->loader->add_action('admin_notices', $class, 'required_plugin_notification');
	}


	/**
	 * Call functions to customise how things are displayed or are accessible in the WordPress admin.
	 *
	 * @return void
	 */
	private function wp_admin_customisations(): void {
		$this->loader->add_filter('editable_roles', self::$user_functions, 'rejig_the_role_list');
		$this->loader->add_action('init', self::$user_functions, 'customise_capabilities');
		$this->loader->add_action('admin_init', self::$user_functions, 'apply_manage_forms_capability');
		$this->loader->add_filter('user_row_actions', self::$user_functions, 'restrict_user_list_actions', 10, 2);
		$this->loader->add_filter('wp_list_table_class_name', self::$user_functions, 'custom_user_list_table', 10, 2);
		$this->loader->add_action('current_screen', self::$user_functions, 'restrict_user_edit_screen');
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
	 * Function to run the loader to execute the hooks with WordPress.
	 * This function is called from the root plugin file to, well, run the plugin.
	 *
	 * @since    1.0.0
	 */
	public function run(): void {
		$this->loader->run();
	}
}
