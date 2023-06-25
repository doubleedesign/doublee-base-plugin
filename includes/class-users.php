<?php

/**
 * This class defines functions to set up custom user roles and capabilities.
 *
 * @since      1.0.0
 * @package    MyPlugin
 * @subpackage MyPlugin/includes
 * @author     Leesa Ward
 */
class MyPlugin_Users {
	protected array $custom_roles;

	public function __construct() {
		$this->custom_roles = array(
			array(
				'key' => 'editor_plus',
				'label' => 'Editor Plus',
				'base_role' => 'editor',
				'additional_capabilities' => array('edit_theme_options')
			)
		);
	}


	/**
	 * Function to create our custom user roles and assign capabilities to them
	 *
	 * @return void
	 */
	function create_roles(): void {
		foreach($this->custom_roles as $custom_role) {
			// Initial role creation
			$template_role = get_role($custom_role['base_role']);
			add_role($custom_role['key'], $custom_role['label'], $template_role->capabilities);

			// Addition of capabilities
			$the_role = get_role($custom_role['key']);
			foreach($custom_role['additional_capabilities'] as $capability) {
				$the_role->add_cap($capability);
			}
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
			$user_query = new WP_User_Query(array(
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
	 * @wp-hook
	 * @param $roles
	 *
	 * @return WP_Role[]
	 */
	function rejig_the_role_list($roles): array {
		$updated = $roles;
		uasort($updated, function($a, $b) {
			return ($a < $b) ? -1 : 1;
		});

		return array_reverse($updated);
	}

}
