<?php
namespace Doubleedesign\BasePlugin;
use WP_Plugins_List_Table;
use DOMDocument;

class Doublee_Plugin_List_Table extends WP_Plugins_List_Table {
	private static array $muLoaders = [];

	public function __construct($args = []) {
		parent::__construct($args);
		self::$muLoaders = array_map(function($path) {
			$name = explode('/', $path)[0];
			return "$name-loader.php";
		}, MustUsePluginHandler::$mustUse);
	}

	public function single_row($item): void {
		ob_start();
		parent::single_row($item);
		$default_output = ob_get_clean();

		// Parse the default HTML and make the necessary modifications
		// to have MU plugins appear active and without checkboxes
		$dom = new DOMDocument();
		$dom->loadHTML($default_output);
		$rows = $dom->getElementsByTagName('tr');
		foreach ($rows as $row) {
			$plugin_name = $row->getAttribute('data-plugin');
			if (in_array($plugin_name, self::$muLoaders)) {
				$this->modify_row_for_mu_plugin($row);
			}
		}

		try {
			echo $dom->saveHTML();
		}
		catch (\Exception $e) {
			// If something goes wrong with the DOM manipulation, fall back to the default output
			echo $default_output;
		}
	}

	private function modify_row_for_mu_plugin(&$row): void {
		$row->setAttribute('class', 'active');
		$headerCells = $row->getElementsByTagName('th');
		foreach($headerCells as $cell) {
			$class = $cell->getAttribute('class');
			if(str_contains($class, 'check-column')) {
				$input = $cell->getElementsByTagName('input')->item(0);
				if($input) {
					$input->setAttribute('disabled', 'disabled');
				}
			}
		}

	}
}
