<?php

/**
 * This class defines functions to add admin messages.
 *
 * @since      1.0.0
 * @package    MyPlugin
 * @subpackage MyPlugin/includes
 * @author     Leesa Ward
 */
class MyPlugin_Admin_Notices {

	/**
	 * The admin notice for if required plugins are missing
	 * @wp-hook
	 *
	 * @return void
	 */
	function required_plugin_notification(): void {
		/**
		 * Add required plugin checks and admin messages here
		 * Example:
		 if (!is_plugin_active('ninja-forms/ninja-forms.php')) {
			echo '<div class="notice notice-error"><p>MyPlugin requires the Ninja Forms plugin for full functionality. Without it, some features may be missing or not work as expected.</p></div>';
		 }
		 */
	}

}
