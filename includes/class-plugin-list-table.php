<?php
class Doublee_Plugin_List_Table {

	function __construct() {
		add_action('pre_current_active_plugins', [$this, 'sort_plugins'], 20);
		add_action('option_active_plugins', [$this, 'consider_embedded_active'], 20);
		add_filter('plugin_row_meta', [$this, 'add_label_to_embedded_plugins'], 10, 3);
		add_filter('plugin_action_links', [$this, 'remove_action_links'], 10, 3);
		add_action('admin_head', [$this, 'add_embedded_plugin_row_css'], 100);
		add_filter('views_plugins', [$this, 'add_custom_plugin_filter']);
		add_filter('all_plugins', [$this, 'filter_plugins_list']);
	}

	public function sort_plugins(): void {
		global $wp_list_table;
		if (empty($wp_list_table->items)) return;

		$rows = $wp_list_table->items;

		// Get mine (by author)
		$mine = array_filter($rows, function ($row) {
			return in_array($row['Author'], ['Double-E Design', 'Leesa Ward']);
		});

		// Get embedded plugins
		$embedded = array_filter($rows, function ($row) {
			return isset($row['Embedded']) && $row['Embedded'] === true;
		});

		// Get others (plugins that are neither mine nor embedded)
		$others = array_filter($rows, function ($row) {
			return !in_array($row['Author'], ['Double-E Design', 'Leesa Ward']) &&
				(!isset($row['Embedded']) || $row['Embedded'] !== true);
		});

		// Merge the two main arrays
		$wp_list_table->items = array_merge($mine, $others);

		// Save the keys for later use
		$keys = array_keys($wp_list_table->items);

		// Add embedded plugins below their parent
		foreach ($embedded as $slug => $embedded_plugin) {
			$parent_key = array_search($embedded_plugin['EmbeddedBy'], array_column($wp_list_table->items, 'Name'));
			$insert_at = $parent_key === false ? count($wp_list_table->items) : $parent_key + 1;
			array_splice($wp_list_table->items, $insert_at, 0, [$embedded_plugin]);
			array_splice($keys, $insert_at, 0, [$slug]);

			// Replace the index keys with the original slug keys
			$wp_list_table->items = array_combine($keys, $wp_list_table->items);
		}
	}

	public function consider_embedded_active($active_plugins): array {
		$plugins = get_plugins();
		// The Embedded and EmbeddedBy keys are not available yet so we have to run with an assumption
		$plugin_keys = array_keys($plugins);
		$embedded_plugins = array_filter($plugin_keys, function ($plugin_key) use ($plugins) {
			return str_contains($plugin_key, '/vendor/');
		});

		if (empty($embedded_plugins)) return $active_plugins;

		return array_merge($active_plugins, $embedded_plugins);
	}

	public function add_label_to_embedded_plugins($plugin_meta, $plugin_file, $plugin_data) {
		if (!empty($plugin_data['Embedded'])) {
			$plugin_meta[] = sprintf(
				'<span class="embedded-label">Installed and managed by %s</span>',
				__($plugin_data['EmbeddedBy'], 'comet')
			);
		}
		return $plugin_meta;
	}

	public function remove_action_links($actions, $plugin_file, $plugin_data) {
		if (!empty($plugin_data['Embedded'])) {
			unset($actions['activate']);
			unset($actions['deactivate']);
			unset($actions['delete']);
		}
		return $actions;
	}

	public function add_embedded_plugin_row_css(): void {
		?>
		<style>
			[data-plugin*="comet-components-wp/vendor"] {
				.check-column {
					pointer-events: none;

					input[type="checkbox"] {
						display: none;
					}
				}

				.plugin-title strong {
					font-weight: 600 !important;
				}
			}
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
			.active[data-plugin*="comet-components-wp/vendor"] {
				.check-column {
					border-left-color: #845EC2 !important;
				}
				th, td {
					background: color-mix(in srgb, #555d66 10%, white);

					a {
						color: color-mix(in srgb, #555d66 90%, black);
					}
				}
			}
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
