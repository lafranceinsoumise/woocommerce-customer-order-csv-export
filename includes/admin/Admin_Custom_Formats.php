<?php
/**
 * WooCommerce Customer/Order CSV Export
 *
 * This source file is subject to the GNU General Public License v3.0
 * that is bundled with this package in the file license.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.html
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@skyverge.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade WooCommerce Customer/Order CSV Export to newer
 * versions in the future. If you wish to customize WooCommerce Customer/Order CSV Export for your
 * needs please refer to http://docs.woocommerce.com/document/ordercustomer-csv-exporter/
 *
 * @author      SkyVerge
 * @copyright   Copyright (c) 2015-2019, SkyVerge, Inc.
 * @license     http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

namespace SkyVerge\WooCommerce\CSV_Export\Admin;

defined( 'ABSPATH' ) or exit;

/**
 * Customer/Order CSV Export Admin Custom Formats
 *
 * Parent class for custom formats tab in admin page
 *
 * @since 4.7.0
 */
class Admin_Custom_Formats {


	/** @var string the current action, `list`, `add`, `edit` or `delete` */
	private $action;

	/** @var Custom_Formats_List_Table instance */
	private $custom_formats_list_table;

	/** @var \WC_Customer_Order_CSV_Export_Admin_Custom_Format_Builder instance */
	private $custom_format_builder;


	/**
	 * Sets up the custom formats admin class.
	 *
	 * @since 4.7.0
	 */
	public function __construct() {

		if ( isset( $_GET['custom_format_key'] ) ) {

			if ( isset( $_GET['edit_custom_format'] ) ) {
				$this->action = 'edit';
			}

			if ( isset( $_GET['delete_custom_format'] ) ) {
				$this->action = 'delete';
			}

		} elseif ( isset( $_GET['add_custom_format'] ) ) {
			$this->action = 'add';
		} else {
			$this->action = 'list';
		}
	}


	/**
	 * Gets the custom formats list table class instance.
	 *
	 * @since 4.7.0
	 *
	 * @return Custom_Formats_List_Table instance
	 */
	public function get_custom_formats_list_table_instance() {

		if ( ! isset( $this->custom_formats_list_table ) ) {
			$this->custom_formats_list_table = wc_customer_order_csv_export()->load_class( '/includes/admin/Custom_Formats_List_Table.php', 'SkyVerge\WooCommerce\CSV_Export\Admin\Custom_Formats_List_Table' );
		}

		return $this->custom_formats_list_table;
	}


	/**
	 * Get the custom format builder class instance
	 *
	 * @since 4.7.0
	 *
	 * @return \WC_Customer_Order_CSV_Export_Admin_Custom_Format_Builder instance
	 */
	public function get_custom_format_builder_instance() {

		if ( ! isset( $this->custom_format_builder ) ) {

			$this->custom_format_builder = wc_customer_order_csv_export()->load_class( '/includes/admin/class-wc-customer-order-csv-export-admin-custom-format-builder.php', 'WC_Customer_Order_CSV_Export_Admin_Custom_Format_Builder' );
		}

		return $this->custom_format_builder;
	}


	/**
	 * Gets sections.
	 *
	 * @since 4.7.0
	 *
	 * @return array
	 */
	public function get_sections() {

		$sections = [
			'orders'    => __( 'Orders', 'woocommerce-customer-order-csv-export' ),
			'customers' => __( 'Customers', 'woocommerce-customer-order-csv-export' ),
			'coupons'   => __( 'Coupons', 'woocommerce-customer-order-csv-export' ),
		];

		/**
		 * Allows actors to change the sections for the custom formats admin page.
		 *
		 * @since 4.7.0
		 *
		 * @param array $sections
		 */
		return apply_filters( 'wc_customer_order_csv_export_custom_formats_admin_sections', $sections );
	}


	/**
	 * Outputs sections for the custom formats admin page.
	 *
	 * @since 4.7.0
	 */
	public function output_sections() {
		global $current_section;

		$sections = $this->get_sections();

		if ( empty( $sections ) || 1 === count( $sections ) ) {
			return;
		}

		echo '<ul class="subsubsub">';

		$section_ids = array_keys( $sections );

		foreach ( $sections as $id => $label ) {
			echo '<li><a href="' . admin_url( 'admin.php?page=wc_customer_order_csv_export&tab=custom_formats&section=' . sanitize_title( $id ) ) . '" class="' . ( $current_section === $id ? 'current' : '' ) . '">' . esc_html( $label ) . '</a> ' . ( end( $section_ids ) === $id ? '' : '|' ) . ' </li>';
		}

		echo '</ul><br class="clear" />';
	}


	/**
	 * Shows custom formats list table.
	 *
	 * @since 4.7.0
	 */
	private function render_custom_format_list_table() {
		global $current_section;

		// permissions check
		if ( ! current_user_can( 'manage_woocommerce_csv_exports' ) ) {
			return;
		}

		$titles = [
			'orders'    => __( 'Orders Custom Export Formats', 'woocommerce-customer-order-csv-export' ),
			'customers' => __( 'Customers Custom Export Formats', 'woocommerce-customer-order-csv-export' ),
			'coupons'   => __( 'Coupons Custom Export Formats', 'woocommerce-customer-order-csv-export' ),
		];

		echo '<div id="custom-formats">';
		echo '<h2>' . esc_html( $titles[ $current_section ] ) . '</h2>';

		// instantiate extended list table
		$custom_format_list_table = $this->get_custom_formats_list_table_instance();
		$custom_format_list_table->set_export_type( $current_section );

		// prepare and display the list table
		$custom_format_list_table->prepare_items();
		$custom_format_list_table->display();

		// add button
		$add_url = wp_nonce_url( add_query_arg( [
			'add_custom_format' => 1,
		] ) );
		$action  = [
			'url'    => $add_url,
			'name'   => esc_html__( 'Add new format', 'woocommerce-customer-order-csv-export' ),
			'action' => 'add',
		];

		printf( '<a class="button button-primary tips %1$s" href="%2$s" data-tip="%3$s">%4$s</a>', esc_attr( $action['action'] ), esc_url( $action['url'] ), esc_attr( $action['name'] ), esc_attr( $action['name'] ) );

		echo '</div>';
	}


	/**
	 * Shows custom formats admin page.
	 *
	 * @since 4.7.0
	 */
	public function output() {
		global $current_section;

		// default to orders section
		if ( ! $current_section ) {
			$current_section = 'orders';
		}

		$this->output_sections();

		// save custom format
		if ( ! empty( $_POST ) ) {
			$this->get_custom_format_builder_instance()->save();
		}

		switch ( $this->action ) {

			case 'add':
			case 'edit':
				$this->get_custom_format_builder_instance()->output();
			break;

			case 'delete':
				if ( isset( $_GET['custom_format_key'] ) ) {
					wc_customer_order_csv_export()->get_formats_instance()->delete_custom_format( $current_section, $_GET['custom_format_key'] );
					$this->render_custom_format_list_table();
				}
			break;

			case 'list':
			default:
				$this->render_custom_format_list_table();
			break;
		}
	}


	/**
	 * Gets the custom formats list table URL.
	 *
	 * @since 4.7.0
	 *
	 * @return string
	 */
	public function get_custom_formats_table_url() {
		global $current_section;

		return add_query_arg( [
			'page'    => 'wc_customer_order_csv_export',
			'tab'     => 'custom_formats',
			'section' => $current_section,
		], admin_url( 'admin.php' ) );
	}


	/**
	 * Gets the delimiter options.
	 *
	 * @return array
	 * @since 4.7.0
	 *
	 */
	public function get_delimiter_options() {

		return [
			","  => __( 'Comma', 'woocommerce-customer-order-csv-export' ),
			";"  => __( 'Semicolon', 'woocommerce-customer-order-csv-export' ),
			"\t" => __( 'Tab', 'woocommerce-customer-order-csv-export' ),
		];
	}


	/**
	 * Gets the row type options.
	 *
	 * @return array
	 * @since 4.7.0
	 *
	 */
	public function get_row_type_options() {

		return [
			'order' => __( 'A single order', 'woocommerce-customer-order-csv-export' ),
			'item'  => __( 'A single line item', 'woocommerce-customer-order-csv-export' ),
		];
	}


	/**
	 * Gets the items format options.
	 *
	 * @return array
	 * @since 4.7.0
	 *
	 */
	public function get_items_format_options() {

		return [
			'pipe_delimited' => __( 'Pipe-delimited format', 'woocommerce-customer-order-csv-export' ),
			'json'           => __( 'JSON', 'woocommerce-customer-order-csv-export' ),
		];
	}


}
