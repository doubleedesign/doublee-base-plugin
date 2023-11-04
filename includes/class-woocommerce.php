<?php

/**
 * This class defines functions to customise WooCommerce functionality.
 *
 * @since      2.0.0
 * @package    MyPlugin
 * @subpackage MyPlugin/includes
 * @author     Leesa Ward
 */
class MyPlugin_WooCommerce {

	public function __construct() {
		add_filter('woocommerce_product_data_tabs', array($this, 'customise_product_data_tabs'), 50);
		add_action('do_meta_boxes', array($this, 'custom_meta_box_positions'));
	}


	/**
	 * Simplify the Product Data section in the admin by removing unused sections,
	 * relabelling stuff etc
	 * @param $tabs
	 *
	 * @return array
	 */
	function customise_product_data_tabs($tabs): array {
		unset($tabs['marketplace-suggestions']);

		return $tabs;
	}


	/**
	 * Move the Product Data box to the top
	 * Note: The after_title context is custom and has to be run on the edit_form_after_title hook;
	 *       at the time of writing this was done in the MyPlugin_Admin_UI class
	 * @return void
	 */
	function custom_meta_box_positions(): void {
		add_meta_box('woocommerce-product-data', __('Product data', 'woocommerce'), 'WC_Meta_Box_Product_Data::output', 'product', 'after_title', 'high');
		remove_meta_box('woocommerce-product-data', 'product', 'normal'); // remove original AFTER adding new copy
	}
}
