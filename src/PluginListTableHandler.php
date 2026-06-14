<?php
namespace Doubleedesign\BasePlugin;

class PluginListTableHandler {
	function __construct() {
		add_filter('plugins_list', [$this, 'show_mu_plugins_in_list']);
		add_filter('plugin_action_links', [$this, 'fix_actions_for_mu_plugins_in_main_list'], 11, 4);
		add_filter('wp_list_table_class_name', [$this, 'load_custom_class_for_plugin_table_display'], 10, 2);
	}

	function load_custom_class_for_plugin_table_display($class_name, $args) {
		if($class_name === 'WP_Plugins_List_Table') {
			return Doublee_Plugin_List_Table::class;
		}

		return $class_name;
	}

	public function show_mu_plugins_in_list($plugins) {
		$plugins['all'] = array_merge($plugins['all'], $plugins['mustuse']);
		$plugins['active'] = array_merge($plugins['active'], $plugins['mustuse']);

		return $plugins;
	}

	/**
	 * When must-use plugins are shown in the "All" and "Active" lists,
	 * they should not show actions like activate, deactivate, or update
	 * @param $actions
	 * @param $plugin_file
	 * @param $plugin_data
	 * @param $context
	 *
	 * @return array
	 */
	public function fix_actions_for_mu_plugins_in_main_list($actions, $plugin_file, $plugin_data, $context): array {
		$mustUse = get_mu_plugins();
		if(in_array($plugin_file, array_keys($mustUse))) {
			$actions = [];
		}

		return $actions;
	}
}
