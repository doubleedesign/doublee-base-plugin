<?php
namespace Doubleedesign\BasePlugin;

use WP_Role;
use WP_User_Query;

/**
 * This class defines functions to set up custom user roles and capabilities.
 *
 * @since      1.0.0
 * @package    Doublee
 * @author     Leesa Ward
 */
class UserRolesAndCapabilities {
	protected array $custom_roles;
	protected array $pages_to_allow_for_manage_options_lite = [];

	public function __construct() {
		$this->pages_to_allow_for_manage_options_lite = array(
			'options-general.php',
			'options-reading.php',
			'options-writing.php',
			'options-permalink.php',
			'options-privacy.php',
		);

		$this->custom_roles = array(
			array(
				'key'                     => 'editor_plus',
				'label'                   => 'Editor Plus',
				'base_role'               => 'editor',
				'additional_capabilities' => array(
					'edit_theme_options',
					'list_users',
					'edit_users',
					'promote_users',
					'delete_users'
				),
				'custom_capabilities'     => array(
					'manage_options_lite',
					'manage_forms',
					'manage_socials',
					'manage_instagram_feed_options' // this needs to be added explicitly because the plugin checks current_user_can() directly for the setup page
				)
			),
		);

		add_filter('editable_roles', array($this, 'rejig_the_role_list'));
		add_action('init', array($this, 'customise_capabilities'));
		add_action('init', array($this, 'apply_manage_forms_capability'), 20);
		add_action('init', array($this, 'apply_manage_socials_capability'), 20);
		add_filter('user_row_actions', array($this, 'restrict_user_list_actions'), 10, 2);
		add_filter('wp_list_table_class_name', array($this, 'custom_user_list_table'), 10, 2);
		add_action('current_screen', array($this, 'restrict_user_edit_screen'));
		add_filter('user_has_cap', array($this, 'selectively_override_manage_options_capability'), 10);
		add_action('admin_menu', array($this, 'fix_admin_menu_for_manage_options_lite_capability'), 20);
		add_action('admin_footer', array($this, 'hackily_disable_editing_admin_email'));
	}


	/**
	 * Function to create our custom user roles
	 *
	 * @return void
	 */
	function create_roles(): void {
		foreach($this->custom_roles as $custom_role) {
			$template_role = get_role($custom_role['base_role']);
			add_role($custom_role['key'], $custom_role['label'], $template_role->capabilities);
		}
	}


	/**
	 * Function to add/remove capabilities for custom roles
	 * @wp-hook
	 *
	 * @return void
	 */
	function customise_capabilities(): void {
		if($this->custom_roles) {
			foreach($this->custom_roles as $custom_role) {
				$the_role = get_role($custom_role['key']);
				if($the_role && $custom_role['additional_capabilities']) {
					foreach($custom_role['additional_capabilities'] as $capability) {
						$the_role->add_cap($capability);
					}
				}
				if($the_role && $custom_role['custom_capabilities']) {
					foreach($custom_role['custom_capabilities'] as $capability) {
						$the_role->add_cap($capability);
					}
				}
			}
		}

		$admin_role = get_role('administrator');
		$admin_role->add_cap('manage_forms');
		$admin_role->add_cap('manage_socials');
	}


	function get_manage_forms_capability($cap): string {
		return 'manage_forms';
	}

	function can_current_user_manage_forms(): bool {
		return current_user_can('manage_forms');
	}

	/**
	 * Use custom capability manage_forms to grant access to Ninja Forms admin stuff
	 */
	function apply_manage_forms_capability(): void {
		if(!function_exists('is_plugin_active')) {
			include_once(ABSPATH . 'wp-admin/includes/plugin.php');
		}

		if(is_plugin_active('ninja-forms/ninja-forms.php')) {

			// Parent Menu
			add_filter('ninja_forms_admin_parent_menu_capabilities', array(
				$this,
				'get_manage_forms_capability'
			));
			add_filter('ninja_forms_admin_all_forms_capabilities', array($this, 'get_manage_forms_capability'));// Forms

			// Submissions
			add_filter('ninja_forms_admin_submissions_capabilities', array(
				$this,
				'get_manage_forms_capability'
			));

			// Import/Export
			add_filter('ninja_forms_admin_import_export_capabilities', array(
				$this,
				'get_manage_forms_capability'
			));

			// New settings required as per Ninja Forms 3.6
			add_filter('ninja_forms_api_allow_get_submissions', array($this, 'can_current_user_manage_forms'), 10, 2);
			add_filter('ninja_forms_api_allow_delete_submissions', array(
				$this,
				'can_current_user_manage_forms'
			), 10, 2);
			add_filter('ninja_forms_api_allow_update_submission', array($this, 'can_current_user_manage_forms'), 10, 2);
			add_filter('ninja_forms_api_allow_handle_extra_submission', array(
				$this,
				'can_current_user_manage_forms'
			), 10, 2);
			add_filter('ninja_forms_api_allow_email_action', array($this, 'can_current_user_manage_forms'), 10, 2);
		}
	}

	function get_manage_socials_capability($cap): string {
		return 'manage_socials';
	}

	function can_current_user_manage_socials(): bool {
		return current_user_can('manage_socials');
	}

	function apply_manage_socials_capability(): void {
		if(function_exists('sb_instagram_feed_init')) {
			add_filter('manage_instagram_feed_options', array($this, 'get_manage_socials_capability'), 10, 1);
			add_filter('sbi_settings_pages_capability', array($this, 'get_manage_socials_capability'), 10, 1);
		}
		if(defined('CFF_PLUGIN_DIR')) {
			add_filter('cff_settings_pages_capability', array($this, 'get_manage_socials_capability'), 10, 1);
		}
	}


	/**
	 * Function to remove the roles we created
	 *
	 * Intended for use upon plugin deactivation, this reverts users with custom roles to the base role,
	 * but upon reactivation their custom role will be reinstated (unless the plugin has been uninstalled as well)
	 *
	 * @return void
	 */
	function delete_roles(): void {
		// Revert users with custom roles to the associated base roles
		$this->revert_users_roles(false);

		// Remove the roles from WordPress
		foreach($this->custom_roles as $custom_role) {
			wp_roles()->remove_role($custom_role['key']);
		}
	}


	/**
	 * Function to reassign custom roles to users
	 *
	 * Intended for use on plugin reactivation, after revert_users_roles has been run with $permanently set to false,
	 * leaving a 'dangling' capability with the same name as the role
	 *
	 * @return void
	 */
	function reassign_users_roles(): void {
		foreach($this->custom_roles as $custom_role) {
			$user_query = new \WP_User_Query(array(
				'capability' => $custom_role['key']
			));
			foreach($user_query->results as $user) {
				$user->add_role($custom_role['key']);
				$user->remove_role($custom_role['base_role']);
			}
		}
	}


	/**
	 * Function to revert users' roles to a built-in one if they had one of our custom roles
	 * and remove "dangling" or "leftover" capabilities ($wp_roles->remove_cap doesn't meet our need here
	 * because the role is removed upon deactivation; but a capability by the same name persists)
	 *
	 * Intended to be run upon plugin uninstallation as a complete "cleanup",
	 * i.e. restore users to how they would be if our plugin was never there
	 * THIS IS A DESTRUCTIVE OPERATION, USE WITH CARE!
	 *
	 * @param bool $permanently
	 *
	 * @return void
	 */
	function revert_users_roles(bool $permanently): void {
		foreach($this->custom_roles as $custom_role) {

			// Query to get users who had this custom role
			// Even though the role is deleted upon plugin deactivation, this query still works
			// (I assume because of the dangling/leftover capability that we're about to remove if $permanently = true)
			$user_query = new WP_User_Query(array(
				'role' => $custom_role['key']
			));

			// Loop through the found users
			foreach($user_query->results as $user) {

				// Revert them to the base role of their custom role
				$user->add_role($custom_role['base_role']);

				// Ensure additional and custom capabilities are removed
				foreach($custom_role['additional_capabilities'] as $capability) {
					$user->remove_cap($capability);
				}
				foreach($custom_role['custom_capabilities'] as $capability) {
					$user->remove_cap($capability);
				}

				// Lastly, remove what's left over from our custom role if applicable
				// (intended for plugin uninstallation)
				if($permanently) {
					$user->remove_role($custom_role['key']);
					$user->remove_cap($custom_role['key']);
				}
			}
		}
	}


	/**
	 * Function to be used to reorder the list of roles in the WordPress admin
	 * and filter some out in certain locations
	 * @wp-hook
	 *
	 * @param $roles
	 *
	 * @return WP_Role[]
	 */
	function rejig_the_role_list($roles): array {
		if(!is_admin()) {
			return $roles;
		}

		$updated = $roles;
		uasort($updated, function($a, $b) {
			return ($a < $b) ? -1 : 1;
		});

		if(!current_user_can('administrator')) {
			unset($updated['administrator']);
		}

		global $pagenow;
		// Only allow new user default role to be set to subscriber or customer
		if($pagenow === 'options-general.php') {
			foreach($updated as $key => $role) {
				if(!in_array($key, array('subscriber', 'customer'))) {
					unset($updated[$key]);
				}
			}
		}

		return array_reverse($updated);
	}


	/**
	 * Function to customise output of the users list table by overriding functions in the built-in one
	 * Used for restricting ability for non-admins to edit admin accounts while still being able to view them
	 * To be run on the wp_list_table_class_name filter
	 * @wp-hook
	 *
	 * @param $class_name
	 * @param $args
	 *
	 * @return string
	 */
	function custom_user_list_table($class_name, $args): string {
		if(class_exists('WP_Users_List_Table') && $args['screen']->id === 'users') {
			include('UsersListTable.php');
			$class_name = 'UsersListTable';
		}

		return $class_name;
	}


	/**
	 * Remove action links in user table for non-admins with user editing/deletion capabilities
	 * To be run on user_row_actions filter
	 * @wp-hook
	 *
	 * @param $actions
	 * @param $user_object
	 *
	 * @return array
	 */
	function restrict_user_list_actions($actions, $user_object): array {
		if(!current_user_can('administrator') && in_array('administrator', $user_object->roles)) {
			unset($actions['edit']);
			unset($actions['delete']);
			unset($actions['resetpassword']);
		}

		return $actions;
	}


	/**
	 * Restrict the user editing screen so users who can see admins in the list can't edit their profiles
	 *
	 * @param $current_screen
	 *
	 * @return void
	 */
	function restrict_user_edit_screen($current_screen): void {
		if($current_screen->id === 'user-edit' && !current_user_can('administrator')) {
			$user_id = $_REQUEST['user_id'];
			$user = get_user_by('id', $user_id);

			if(in_array('administrator', $user->roles)) {
				wp_die(__("<p>You don't have sufficient permissions to edit this user.</p><p><a class='button button-primary' href='/wp-admin/users.php'>Go back</a></p>"),
					403);
			}
		}
	}

	/**
	 * Allow Editor Plus users to access specific settings that are normally restricted to users with the manage_options capability,
	 * without actually giving them that capability site-wide.
	 * @param $allcaps
	 * @return mixed
	 */
	function selectively_override_manage_options_capability($allcaps) {
		if(!is_admin()) {
			return $allcaps;
		}

		// Bail early if the user already has manage_options
		if(isset($allcaps['manage_options']) && $allcaps['manage_options'] == true) {
			return $allcaps;
		}

		// Check if current user has "manage_options_lite" capability (i.e. is Editor Plus) before proceeding,
		if(!isset($allcaps['manage_options_lite']) && $allcaps['manage_options_lite'] != true) {
			return $allcaps;
		}

		// Temporarily grant manage_options capability on specific settings pages
		global $pagenow;
		if(in_array($pagenow, $this->pages_to_allow_for_manage_options_lite)) {
			$allcaps['manage_options'] = true;
		}

		return $allcaps;
	}

	/**
	 * Using the user_has_cap filter to selectively grant manage_options capability means they have it for the entire page including the menu while on that page,
	 * but not when not on that page. This presents two problems:
	 *      - Access to menu items they shouldn't see when on those pages, and
	 *      - no access to menu items with the overridden capability when not on those pages.
	 * Here, we fix that at the menu level.
	 *
	 * @return void
	 */
	function fix_admin_menu_for_manage_options_lite_capability() {
		// Bail early if the user is an admin (checking manage_options here doesn't work because of the overrides that have already happened at this point)
		if(current_user_can('administrator')) {
			return;
		}
		// ...or if they do not have manage_options_lite
		if(!current_user_can('manage_options_lite')) {
			return;
		}

		global $menu, $submenu;

		foreach($menu as $index => $item) {
			$capability = $item[1];
			$path = $item[2];
			// Temporarily increase the required capability for other menu items to prevent access if the user does not have that capability
			if(!in_array($path, $this->pages_to_allow_for_manage_options_lite) && $capability == 'manage_options') {
				$menu[$index][1] = 'install_plugins'; // an arbitrary capability they most likely don't have
			}
			// ...except options-general.php which we need to explicitly allow
			if($path == 'options-general.php' && $capability == 'manage_options') {
				$menu[$index][1] = 'manage_options_lite';
			}
		}

		// Loop through all the submenus and fix any other instances of menu items with manage_options capability
		foreach($submenu as $parent_slug => $submenus) {
			foreach($submenus as $index => $item) {
				$capability = $item[1];
				$path = $item[2];
				// If the item uses a different capability and the user already has access, leave it alone
				if($capability != 'manage_options' && current_user_can($capability)) {
					continue;
				}
				if(in_array($path, $this->pages_to_allow_for_manage_options_lite)) {
					$submenu[$parent_slug][$index][1] = 'manage_options_lite';
				}
				// Temporarily increase the required capability for other submenu items to prevent access if the user does not have that capability
				else if($capability == 'manage_options') {
					$submenu[$parent_slug][$index][1] = 'install_plugins'; // an arbitrary capability they most likely don't have
				}
			}
		}

		// FIXME: The submenus still aren't totally right because if the user is not on an options-general page, options-general.php does not show up in $submenu,
		// so the only items visible are the top-level "General Settings" and my custom options pages which use a different capability.
	}

	/**
	 * If non-admin user has been granted access to the General Settings, there's no server-side way to disable changing the admin email,
	 * so JavaScript on load it is.
	 * @return void
	 */
	function hackily_disable_editing_admin_email() {
		if(current_user_can('administrator')) {
			return;
		}

		echo "<script>
			document.addEventListener('DOMContentLoaded', function() {
				const emailField = document.getElementById('new_admin_email');
				if (emailField) {
					emailField.setAttribute('readonly', 'readonly');
					emailField.setAttribute('disabled', true);
				}
			});
		</script>";
	}
}
