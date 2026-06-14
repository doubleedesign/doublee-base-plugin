<?php

namespace Doubleedesign\BasePlugin;

class MustUsePluginHandler {
	public static array $mustUse = [];

	public function __construct() {
		add_action('muplugins_loaded', [$this, 'cache_mu_plugins'], 50);
		add_filter('wp_plugin_dependencies_slug', [$this, 'treat_mu_plugins_as_active_for_dependents'], 10, 2);

		// Note: Filtering get_option('active_plugins') to simply consider the MU plugins as active does not work
		// because it gets called by a bunch of things that run validation steps that we can't intercept.
		// This class works together with the PluginListTableHandler and custom plugin list display class
		// to make them appear active and in general plugin lists where it matters.
	}

	/**
	 * When the muplugins_loaded action runs, save the list of must-use plugins to a local variable for use in other functions
	 * so we're not constantly calling WP functions that may be doing more under the hood than we need every time.
	 * @return void
	 */
	public function cache_mu_plugins(): void {
		if(empty($this->mustUse)) {
			$mustUse = wp_get_mu_plugins(); // this is available earlier than get_mu_plugins()
			self::$mustUse = array_map(function($path) {
				$split = explode('/', $path);
				$file = end($split);
				$directory = str_replace('-loader.php', '', $file);

				if($directory === 'advanced-custom-fields-pro') {
					return "$directory/acf.php";
				}

				return "$directory/index.php";
			}, $mustUse);
		}
	}


	/**
	 * When checking plugin dependencies, if the slug is in the list of must-use plugins,
	 * treat it as active by returning an empty string.
	 * That's what the function that checks dependencies looks for to confirm something is active.)
	 *
	 * @param $slug
	 * @return string
	 */
	public function treat_mu_plugins_as_active_for_dependents($slug): string {
		$mu_slugs = array_map(fn($path) => explode('/', $path)[0], MustUsePluginHandler::$mustUse);

		if(in_array($slug, $mu_slugs)) {
			// Clear it so it doesn't get a false negative
			// Unfortunately this also removes the requirements from the description in the admin, but it'll have to do for now
			return '';
		}

		return $slug;
	}
}
