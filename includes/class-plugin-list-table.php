<?php
class Doublee_Plugin_List_Table {

	function __construct() {
		add_action('admin_head', [$this, 'add_plugin_row_css'], 100);
		add_filter('views_plugins', [$this, 'add_custom_plugin_filter']);
		add_filter('all_plugins', [$this, 'filter_plugins_list']);
	}

	public function add_plugin_row_css(): void {
		?>
		<style>
			<?php
			foreach($this->get_mine() as $plugin_slug) {
				echo ".active[data-plugin*=\"$plugin_slug\"] {";
				?>
					.check-column {
						border-left-color: #845EC2 !important;
					}
					th, td {
						background: color-mix(in srgb, #845EC2 10%, white);

						a {
							color: color-mix(in srgb, #845EC2 90%, black);
						}
					}
				<?php
				echo "}";
			} ?>
		</style>
		<?php
	}

	public function add_custom_plugin_filter($views) {
		$mine = $this->get_mine();
		$count = count($mine);

		// Current filter status
		$class = (isset($_GET['plugin_status']) && $_GET['plugin_status'] === 'doubleedesign') ? 'current' : '';

		// Add new filter link
		$views['doubleedesign'] = sprintf(
			'<a href="%s" class="%s">By Double-E Design <span class="count">(%d)</span></a>',
			admin_url('plugins.php?plugin_status=doubleedesign'),
			$class,
			$count
		);

		return $views;
	}

	// Modify the plugins list based custom filters if active
	function filter_plugins_list($plugins) {
		if (!isset($_GET['plugin_status']) || $_GET['plugin_status'] !== 'doubleedesign') {
			return $plugins;
		}

		foreach ($plugins as $plugin_path => $plugin_data) {
			if(!in_array($plugin_path, $this->get_mine())) {
				unset($plugins[$plugin_path]);
			}
		}

		return $plugins;
	}

	private function get_mine(): array {
		$plugins = get_plugins();
		$mine = array_filter($plugins, function ($plugin) {
			return in_array($plugin['Author'], ['Double-E Design', 'Leesa Ward']);
		});

		return array_keys($mine);
	}
}
