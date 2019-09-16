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

defined( 'ABSPATH' ) or exit;

use SkyVerge\WooCommerce\PluginFramework\v5_4_1 as Framework;

/**
 * Customer/Order CSV Export Admin Settings Class
 *
 * Dedicated class for admin settings
 *
 * @since 4.0.0
 */
class WC_Customer_Order_CSV_Export_Admin_Settings {


	/**
	 * Setup admin settings class
	 *
	 * @since 4.0.0
	 */
	public function __construct() {

		// Render a custom test button when using woocommerce_admin_fields()
		add_action( 'woocommerce_admin_field_csv_test_button', [ $this, 'render_test_button' ] );

		add_filter( 'woocommerce_admin_settings_sanitize_option_wc_customer_order_csv_export_orders_auto_export_products', [ $this, 'mutate_select2_product_ids' ] );
	}


	/**
	 * Get sections
	 *
	 * @since 4.0.0
	 * @return array
	 */
	public function get_sections() {

		$sections = [
			'orders'    => __( 'Orders', 'woocommerce-customer-order-csv-export' ),
			'customers' => __( 'Customers', 'woocommerce-customer-order-csv-export' ),
			'coupons'   => __( 'Coupons', 'woocommerce-customer-order-csv-export' ),
		];

		/**
		 * Allow actors to change the sections for settings
		 *
		 * @since 4.0.0
		 * @param array $sections
		 */
		return apply_filters( 'wc_customer_order_csv_export_sections', $sections );
	}



	/**
	 * Output sections for settings
	 *
	 * @since 4.0.0
	 */
	public function output_sections() {

		global $current_section;

		$sections = $this->get_sections();

		if ( empty( $sections ) || 1 === sizeof( $sections ) ) {
			return;
		}

		echo '<ul class="subsubsub">';

		$section_ids = array_keys( $sections );

		foreach ( $sections as $id => $label ) {
			echo '<li><a href="' . admin_url( 'admin.php?page=wc_customer_order_csv_export&tab=settings&section=' . sanitize_title( $id ) ) . '" class="' . ( $current_section === $id ? 'current' : '' ) . '">' . $label . '</a> ' . ( end( $section_ids ) === $id ? '' : '|' ) . ' </li>';
		}

		echo '</ul><br class="clear" />';
	}


	/**
	 * Returns settings array for use by output/save functions
	 *
	 * In 4.0.0 moved here from WC_Customer_Order_CSV_Export_Admin class
	 *
	 * @since 3.0.0
	 * @param string $section_id
	 * @return array
	 */
	public static function get_settings( $section_id = null ) {

		$order_statuses     = wc_get_order_statuses();
		$product_cat_terms  = get_terms( 'product_cat' );
		$product_categories = [];

		// sanity check: get_terms() may return a WP_Error
		if ( is_array( $product_cat_terms ) && ! empty( $product_cat_terms ) ) {
			foreach ( $product_cat_terms as $term ) {
				$product_categories[ $term->term_id ] = $term->name;
			}
		}

		$export_method_options = wc_customer_order_csv_export()->is_batch_processing_enabled() ? [] : wc_customer_order_csv_export()->get_methods_instance()->get_export_method_labels();
		$export_method_options = [ 'disabled' => __( 'Disabled', 'woocommerce-customer-order-csv-export' ) ] + $export_method_options;

		$ftp_security_options = [
			'none'    => __( 'None', 'woocommerce-customer-order-csv-export' ),
			'ftp_ssl' => __( 'FTP with Implicit SSL', 'woocommerce-customer-order-csv-export' ),
			'ftps'    => __( 'FTP with Explicit TLS/SSL', 'woocommerce-customer-order-csv-export' ),
			'sftp'    => __( 'SFTP (FTP over SSH)', 'woocommerce-customer-order-csv-export' )
		];

		$scheduled_descriptions = [
			'orders'    => '',
			'customers' => '',
		];

		foreach ( array_keys( $scheduled_descriptions ) as $export_type ) {

			// get the scheduled export time to display to user
			if ( $scheduled_timestamp = wp_next_scheduled( 'wc_customer_order_csv_export_auto_export_' . $export_type ) ) {
				/* translators: Placeholders: %s - date */
				$scheduled_descriptions[ $export_type ] = sprintf( __( 'The next export is scheduled on %s', 'woocommerce-customer-order-csv-export' ), '<code>' . get_date_from_gmt( date( 'Y-m-d H:i:s', $scheduled_timestamp ), wc_date_format() . ' ' . wc_time_format() ) . '</code>' );
			} else {
				$scheduled_descriptions[ $export_type ] = __( 'The export is not scheduled.', 'woocommerce-customer-order-csv-export' );
			}
		}

		$use_legacy_formats = 'yes' === get_option( 'wc_customer_order_csv_export_keep_legacy_formats' );

		$settings = [

			'orders' => [

				[
					'name' => __( 'Export Format', 'woocommerce-customer-order-csv-export' ),
					'type' => 'title',
				],

					[
						'id'       => 'orders_export_format',
						'name'     => __( 'Order Export Format', 'woocommerce-customer-order-csv-export' ),
						'desc_tip' => __( 'Default is a new format for v3.0, Import matches the Customer/Order CSV Import plugin format.', 'woocommerce-customer-order-csv-export' ),
						'type'     => 'select_with_optgroup',
						'options'  => self::get_orders_export_formats( $use_legacy_formats ),
						'default'  => 'default',
					],

					[
						'id'       => 'wc_customer_order_csv_export_orders_filename',
						'name'     => __( 'Order Export Filename', 'woocommerce-customer-order-csv-export' ),
						'desc_tip' => __( 'The filename for exported orders. Merge variables: %%timestamp%%, %%order_ids%%', 'woocommerce-customer-order-csv-export' ),
						'default'  => 'orders-export-%%timestamp%%.csv',
						'css'      => 'min-width: 300px;',
						'type'     => 'text',
					],

					[
						'id'      => 'wc_customer_order_csv_export_orders_add_note',
						'name'    => __( 'Add Order Notes', 'woocommerce-customer-order-csv-export' ),
						'desc'    => __( 'Enable to add a note to exported orders.', 'woocommerce-customer-order-csv-export' ),
						'default' => 'yes',
						'type'    => 'checkbox',
					],

					[
						'id'       => 'wc_customer_order_csv_export_enable_batch_processing',
						'name'     => __( 'Batch Processing', 'woocommerce-customer-order-csv-export' ),
						'desc'     => __( 'Use batch processing for manual exports.', 'woocommerce-customer-order-csv-export' ),
						'desc_tip' => __( 'Only enable this setting when notified that your site does not support background processing.', 'woocommerce-customer-order-csv-export' ),
						'default'  => 'no',
						'type'     => 'checkbox',
					],

				[ 'type' => 'sectionend' ],

				[
					'name' => __( 'Automated Export Settings', 'woocommerce-customer-order-csv-export' ),
					'type' => 'title'
				],

					[
						'id'       => 'wc_customer_order_csv_export_orders_auto_export_method',
						'name'     => __( 'Automatically Export Orders', 'woocommerce-customer-order-csv-export' ),
						'desc_tip' => __( 'Enable this to automatically export orders via the method & schedule selected.', 'woocommerce-customer-order-csv-export' ),
						'type'     => 'select',
						'options'  => $export_method_options,
						'default'  => 'disabled',
						'class'    => 'js-auto-export-method',
						/* translators: Placeholders: %1$s - <a> tag, %2$s - </a> tag */
						'desc'     => sprintf( __( 'Local exports are generated, then saved to the %1$sExport List%2$s for 14 days.', 'woocommerce-customer-order-csv-export' ), '<a href="' . admin_url( 'admin.php?page=wc_customer_order_csv_export&tab=export_list' ) . '">', '</a>' ),
					],

					[
						'id'       => 'wc_customer_order_csv_export_orders_auto_export_trigger',
						'name'     => __( 'Trigger Automatic Export', 'woocommerce-customer-order-csv-export' ),
						'desc_tip' => __( "Choose whether to auto-export orders on a schedule or immediately when they're paid for.", 'woocommerce-customer-order-csv-export' ),
						'type'     => 'select',
						'options'  => [
							'schedule'  => __( 'on scheduled intervals', 'woocommerce-customer-order-csv-export' ),
							'immediate' => __( 'immediately as orders are paid', 'woocommerce-customer-order-csv-export' ),
						],
						'default' => 'schedule',
						'class'   => 'js-auto-export-trigger',
					],

					[
						'id'       => 'wc_customer_order_csv_export_orders_auto_export_start_time',
						'name'     => __( 'Export Start Time', 'woocommerce-customer-order-csv-export' ),
						'desc_tip' => __( 'Any new orders will start exporting at this time.', 'woocommerce-customer-order-csv-export' ),
						/* translators: Placeholders: %s - time */
						'desc'     => sprintf( 	__( 'Local time is %s.', 'woocommerce-customer-order-csv-export' ), '<code>' . date_i18n( wc_time_format() ) . '</code>' ) . ' ' . $scheduled_descriptions['orders'],
						'default'  => '',
						'type'     => 'text',
						'css'      => 'max-width: 100px;',
						'class'    => 'js-auto-export-timepicker js-auto-export-schedule-field',
					],

					[
						'id'       => 'wc_customer_order_csv_export_orders_auto_export_interval',
						'name'     => __( 'Export Interval (in minutes)*', 'woocommerce-customer-order-csv-export' ),
						'desc_tip' => __( 'Any new orders will be exported on this schedule.', 'woocommerce-customer-order-csv-export' ),
						'desc'     => __( 'Required in order to schedule the automatic export.', 'woocommerce-customer-order-csv-export' ),
						'default'  => '30',
						'type'     => 'text',
						'css'      => 'max-width: 50px;',
						'class'    => 'js-auto-export-schedule-field',
					],

					[
						'id'                => 'wc_customer_order_csv_export_orders_auto_export_statuses',
						'name'              => __( 'Order Statuses', 'woocommerce-customer-order-csv-export' ),
						'desc_tip'          => __( 'Orders with these statuses will be included in the export.', 'woocommerce-customer-order-csv-export' ),
						'type'              => 'multiselect',
						'options'           => $order_statuses,
						'default'           => '',
						'class'             => 'wc-enhanced-select js-auto-export-schedule-field',
						'css'               => 'min-width: 250px',
						'custom_attributes' => [
							'data-placeholder' => __( 'Leave blank to export orders with any status.', 'woocommerce-customer-order-csv-export' ),
						],
					],

					[
						'id'                => 'wc_customer_order_csv_export_orders_auto_export_product_categories',
						'name'              => __( 'Product Categories', 'woocommerce-customer-order-csv-export' ),
						'desc_tip'          => __( 'Orders with products in these categories will be included in the export.', 'woocommerce-customer-order-csv-export' ),
						'type'              => 'multiselect',
						'options'           => $product_categories,
						'default'           => '',
						'class'             => 'wc-enhanced-select',
						'css'               => 'min-width: 250px',
						'custom_attributes' => [
							'data-placeholder' => __( 'Leave blank to export orders with products in any category.', 'woocommerce-customer-order-csv-export' ),
						],
					],

					[
						'id'                => 'wc_customer_order_csv_export_orders_auto_export_products',
						'name'              => __( 'Products', 'woocommerce-customer-order-csv-export' ),
						'desc_tip'          => __( 'Orders with these products will be included in the export.', 'woocommerce-customer-order-csv-export' ),
						'type'              => 'csv_product_search',
						'default'           => '',
						'class'             => 'wc-product-search',
						'css'               => 'min-width: 250px',
						'custom_attributes' => [
							'data-multiple'    => 'true',
							'data-action'      => 'woocommerce_json_search_products_and_variations',
							'data-placeholder' => __( 'Leave blank to export orders with any products.', 'woocommerce-customer-order-csv-export' ),
						],
					],

					[ 'type' => 'sectionend' ],

					[
						'id'   => 'wc_customer_order_csv_export_orders_ftp_settings',
						'name' => __( 'FTP Settings', 'woocommerce-customer-order-csv-export' ),
						'type' => 'title'
					],

					[
						'id'       => 'wc_customer_order_csv_export_orders_ftp_server',
						'name'     => __( 'Server Address', 'woocommerce-customer-order-csv-export' ),
						'desc_tip' => __( 'The address of the remote FTP server to upload to.', 'woocommerce-customer-order-csv-export' ),
						'default'  => '',
						'type'     => 'text',
						'class'    => 'js-auto-export-ftp-field',
					],

					[
						'id'       => 'wc_customer_order_csv_export_orders_ftp_username',
						'name'     => __( 'Username', 'woocommerce-customer-order-csv-export' ),
						'desc_tip' => __( 'The username for the remote FTP server.', 'woocommerce-customer-order-csv-export' ),
						'default'  => '',
						'type'     => 'text',
						'class'    => 'js-auto-export-ftp-field',
					],

					[
						'id'       => 'wc_customer_order_csv_export_orders_ftp_password',
						'name'     => __( 'Password', 'woocommerce-customer-order-csv-export' ),
						'desc_tip' => __( 'The password for the remote FTP server.', 'woocommerce-customer-order-csv-export' ),
						'default'  => '',
						'type'     => 'password',
						'class'    => 'js-auto-export-ftp-field',
					],

					[
						'id'                => 'wc_customer_order_csv_export_orders_ftp_port',
						'name'              => __( 'Port', 'woocommerce-customer-order-csv-export' ),
						'desc_tip'          => __( 'The port for the remote FTP server.', 'woocommerce-customer-order-csv-export' ),
						'default'           => '21',
						'type'              => 'number',
						'class'             => 'js-auto-export-ftp-field js-auto-export-ftp-port',
						'style'             => 'max-width: 50px;',
						'custom_attributes' => [ 'min' => 0, 'step' => 1 ],
					],

					[
						'id'       => 'wc_customer_order_csv_export_orders_ftp_path',
						'name'     => __( 'Initial Path', 'woocommerce-customer-order-csv-export' ),
						'desc_tip' => __( 'The initial path for the remote FTP server with trailing slash, but excluding leading slash.', 'woocommerce-customer-order-csv-export' ),
						'default'  => '',
						'type'     => 'text',
						'class'    => 'js-auto-export-ftp-field',
					],

					[
						'id'       => 'wc_customer_order_csv_export_orders_ftp_security',
						'name'     => __( 'Security', 'woocommerce-customer-order-csv-export' ),
						'desc_tip' => __( 'Select the security type for the remote FTP server.', 'woocommerce-customer-order-csv-export' ),
						'default'  => 'none',
						'options'  => $ftp_security_options,
						'type'     => 'select',
						'class'    => 'js-auto-export-ftp-field js-auto-export-ftp-security',
					],

					[
						'id'      => 'wc_customer_order_csv_export_orders_ftp_passive_mode',
						'name'    => __( 'Passive Mode', 'woocommerce-customer-order-csv-export' ),
						'desc'    => __( 'Enable passive mode if you are having issues connecting to FTP, especially if you see "PORT command successful" in the error log.', 'woocommerce-customer-order-csv-export' ),
						'default' => 'no',
						'type'    => 'checkbox',
						'class'   => 'js-auto-export-ftp-field',
					],

					[
						'id'          => 'wc_customer_order_csv_export_orders_ftp_test_button',
						'name'        => __( 'Test FTP', 'woocommerce-customer-order-csv-export' ),
						'method'      => 'ftp',
						'type'        => 'csv_test_button',
						'export_type' => 'orders',
						'class'       => 'js-auto-export-ftp-field js-auto-export-test-button',
					],

					[ 'type' => 'sectionend' ],

					[
						'id'   => 'wc_customer_order_csv_export_orders_http_post_settings',
						'name' => __( 'HTTP POST Settings', 'woocommerce-customer-order-csv-export' ),
						'type' => 'title'
					],

					[
						'id'       => 'wc_customer_order_csv_export_orders_http_post_url',
						'name'     => __( 'HTTP POST URL', 'woocommerce-customer-order-csv-export' ),
						'desc_tip' => __( 'Enter the URL to POST the exported CSV to.', 'woocommerce-customer-order-csv-export' ),
						'default'  => '',
						'type'     => 'text',
						'class'    => 'js-auto-export-http-post-field',
					],

					[
						'id'          => 'wc_customer_order_csv_export_orders_http_post_test_button',
						'name'        => __( 'Test HTTP POST', 'woocommerce-customer-order-csv-export' ),
						'method'      => 'http_post',
						'type'        => 'csv_test_button',
						'export_type' => 'orders',
						'class'       => 'js-auto-export-http-post-field js-auto-export-test-button',
					],

					[ 'type' => 'sectionend' ],

					[
						'id'   => 'wc_customer_order_csv_export_orders_email_settings',
						'name' => __( 'Email Settings', 'woocommerce-customer-order-csv-export' ),
						'type' => 'title'
					],

					[
						'id'       => 'wc_customer_order_csv_export_orders_email_recipients',
						'name'     => __( 'Recipient(s)', 'woocommerce-customer-order-csv-export' ),
						/* translators: Placeholders: %s - email address */
						'desc_tip' => sprintf( __( 'Enter recipients (comma separated) the exported CSV should be emailed to. Defaults to %s.', 'woocommerce-customer-order-csv-export' ), '<em>' . esc_html( get_option( 'admin_email' ) ) . '</em>' ),
						'default'  => '',
						'type'     => 'text',
						'class'    => 'js-auto-export-email-field',
					],

					[
						'id'       => 'wc_customer_order_csv_export_orders_email_subject',
						'name'     => __( 'Email Subject', 'woocommerce-customer-order-csv-export' ),
						'desc_tip' => __( 'Enter the email subject.', 'woocommerce-customer-order-csv-export' ),
						/* translators: Placeholders: %s - blog name */
						'default'  => sprintf( __( '[%s] Order CSV Export', 'woocommerce-customer-order-csv-export' ), get_option( 'blogname' ) ),
						'type'     => 'text',
						'class'    => 'js-auto-export-email-field',
					],

					[
						'id'          => 'wc_customer_order_csv_export_orders_email_test_button',
						'name'        => __( 'Test Email', 'woocommerce-customer-order-csv-export' ),
						'method'      => 'email',
						'type'        => 'csv_test_button',
						'export_type' => 'orders',
						'class'       => 'js-auto-export-email-field js-auto-export-test-button',
					],

				[ 'type' => 'sectionend' ],
			],

			'customers' => [

				[
					'name' => __( 'Export Format', 'woocommerce-customer-order-csv-export' ),
					'type' => 'title'
				],

					[
						'id'       => 'customers_export_format',
						'name'     => __( 'Customer Export Format', 'woocommerce-customer-order-csv-export' ),
						'desc_tip' => __( 'Default is a new format for v3.0, Import matches the Customer/Order CSV Import plugin format.', 'woocommerce-customer-order-csv-export' ),
						'type'     => 'select_with_optgroup',
						'options'  => self::get_customers_export_formats( $use_legacy_formats ),
						'default'  => 'default',
					],

					[
						'id'       => 'wc_customer_order_csv_export_customers_filename',
						'name'     => __( 'Customer Export Filename', 'woocommerce-customer-order-csv-export' ),
						'desc_tip' => __( 'The filename for exported customers. Merge variables: %%timestamp%%', 'woocommerce-customer-order-csv-export' ),
						'default'  => 'customers-export-%%timestamp%%.csv',
						'css'      => 'min-width: 300px;',
						'type'     => 'text',
					],

					[
						'id'       => 'wc_customer_order_csv_export_enable_batch_processing',
						'name'     => __( 'Batch Processing', 'woocommerce-customer-order-csv-export' ),
						'desc'     => __( 'Use batch processing for manual exports.', 'woocommerce-customer-order-csv-export' ),
						'desc_tip' => __( 'Only enable this setting when notified that your site does not support background processing.', 'woocommerce-customer-order-csv-export' ),
						'default'  => 'no',
						'type'     => 'checkbox',
					],

				[ 'type' => 'sectionend' ],

				[
					'name' => __( 'Automated Export Settings', 'woocommerce-customer-order-csv-export' ),
					'type' => 'title'
				],

					[
						'id'       => 'wc_customer_order_csv_export_customers_auto_export_method',
						'name'     => __( 'Automatically Export Customers', 'woocommerce-customer-order-csv-export' ),
						'desc_tip' => __( 'Enable this to automatically export customers via the method & schedule selected.', 'woocommerce-customer-order-csv-export' ),
						'type'     => 'select',
						'options'  => $export_method_options,
						'default'  => 'disabled',
						'class'    => 'js-auto-export-method',
						/* translators: Placeholders: %1$s - <a> tag, %2$s - </a> tag */
						'desc'     => sprintf( __( 'Local exports are generated, then saved to the %1$sExport List%2$s for 14 days.', 'woocommerce-customer-order-csv-export' ), '<a href="' . admin_url( 'admin.php?page=wc_customer_order_csv_export&tab=export_list' ) . '">', '</a>' ),
					],

					[
						'id'       => 'wc_customer_order_csv_export_customers_auto_export_start_time',
						'name'     => __( 'Export Start Time', 'woocommerce-customer-order-csv-export' ),
						'desc_tip' => __( 'Any new customers will start exporting at this time.', 'woocommerce-customer-order-csv-export' ),
						/* translators: Placeholders: %s - time */
						'desc'     => sprintf( 	__( 'Local time is %s.', 'woocommerce-customer-order-csv-export' ), '<code>' . date_i18n( wc_time_format() ) . '</code>' ) . ' ' . $scheduled_descriptions['customers'],
						'default'  => '',
						'type'     => 'text',
						'css'      => 'max-width: 100px;',
						'class'    => 'js-auto-export-timepicker js-auto-export-schedule-field',
					],

					[
						'id'       => 'wc_customer_order_csv_export_customers_auto_export_interval',
						'name'     => __( 'Export Interval (in minutes)*', 'woocommerce-customer-order-csv-export' ),
						'desc_tip' => __( 'Any new customers will be exported on this schedule.', 'woocommerce-customer-order-csv-export' ),
						'desc'     => __( 'Required in order to schedule the automatic export.', 'woocommerce-customer-order-csv-export' ),
						'default'  => '30',
						'type'     => 'text',
						'css'      => 'max-width: 50px;',
						'class'    => 'js-auto-export-schedule-field',
					],

					[ 'type' => 'sectionend' ],

					[
						'id'   => 'wc_customer_order_csv_export_customers_ftp_settings',
						'name' => __( 'FTP Settings', 'woocommerce-customer-order-csv-export' ),
						'type' => 'title'
					],

					[
						'id'       => 'wc_customer_order_csv_export_customers_ftp_server',
						'name'     => __( 'Server Address', 'woocommerce-customer-order-csv-export' ),
						'desc_tip' => __( 'The address of the remote FTP server to upload to.', 'woocommerce-customer-order-csv-export' ),
						'default'  => '',
						'type'     => 'text',
						'class'    => 'js-auto-export-ftp-field',
					],

					[
						'id'       => 'wc_customer_order_csv_export_customers_ftp_username',
						'name'     => __( 'Username', 'woocommerce-customer-order-csv-export' ),
						'desc_tip' => __( 'The username for the remote FTP server.', 'woocommerce-customer-order-csv-export' ),
						'default'  => '',
						'type'     => 'text',
						'class'    => 'js-auto-export-ftp-field',
					],

					[
						'id'       => 'wc_customer_order_csv_export_customers_ftp_password',
						'name'     => __( 'Password', 'woocommerce-customer-order-csv-export' ),
						'desc_tip' => __( 'The password for the remote FTP server.', 'woocommerce-customer-order-csv-export' ),
						'default'  => '',
						'type'     => 'password',
						'class'    => 'js-auto-export-ftp-field',
					],

					[
						'id'                => 'wc_customer_order_csv_export_customers_ftp_port',
						'name'              => __( 'Port', 'woocommerce-customer-order-csv-export' ),
						'desc_tip'          => __( 'The port for the remote FTP server.', 'woocommerce-customer-order-csv-export' ),
						'default'           => '21',
						'type'              => 'number',
						'class'             => 'js-auto-export-ftp-field js-auto-export-ftp-port',
						'style'             => 'max-width: 50px;',
						'custom_attributes' => [ 'min' => 0, 'step' => 1 ],
					],

					[
						'id'       => 'wc_customer_order_csv_export_customers_ftp_path',
						'name'     => __( 'Initial Path', 'woocommerce-customer-order-csv-export' ),
						'desc_tip' => __( 'The initial path for the remote FTP server with trailing slash, but excluding leading slash.', 'woocommerce-customer-order-csv-export' ),
						'default'  => '',
						'type'     => 'text',
						'class'    => 'js-auto-export-ftp-field',
					],

					[
						'id'       => 'wc_customer_order_csv_export_customers_ftp_security',
						'name'     => __( 'Security', 'woocommerce-customer-order-csv-export' ),
						'desc_tip' => __( 'Select the security type for the remote FTP server.', 'woocommerce-customer-order-csv-export' ),
						'default'  => 'none',
						'options'  => $ftp_security_options,
						'type'     => 'select',
						'class'    => 'js-auto-export-ftp-field js-auto-export-ftp-security',
					],

					[
						'id'      => 'wc_customer_order_csv_export_customers_ftp_passive_mode',
						'name'    => __( 'Passive Mode', 'woocommerce-customer-order-csv-export' ),
						'desc'    => __( 'Enable passive mode if you are having issues connecting to FTP, especially if you see "PORT command successful" in the error log.', 'woocommerce-customer-order-csv-export' ),
						'default' => 'no',
						'type'    => 'checkbox',
						'class'   => 'js-auto-export-ftp-field',
					],

					[
						'id'          => 'wc_customer_order_csv_export_customers_ftp_test_button',
						'name'        => __( 'Test FTP', 'woocommerce-customer-order-csv-export' ),
						'method'      => 'ftp',
						'type'        => 'csv_test_button',
						'export_type' => 'customers',
						'class'       => 'js-auto-export-ftp-field js-auto-export-test-button',
					],

					[ 'type' => 'sectionend' ],

					[
						'id'   => 'wc_customer_order_csv_export_customers_http_post_settings',
						'name' => __( 'HTTP POST Settings', 'woocommerce-customer-order-csv-export' ),
						'type' => 'title'
					],

					[
						'id'       => 'wc_customer_order_csv_export_customers_http_post_url',
						'name'     => __( 'HTTP POST URL', 'woocommerce-customer-order-csv-export' ),
						'desc_tip' => __( 'Enter the URL to POST the exported CSV to.', 'woocommerce-customer-order-csv-export' ),
						'default'  => '',
						'type'     => 'text',
						'class'    => 'js-auto-export-http-post-field',
					],

					[
						'id'          => 'wc_customer_order_csv_export_customers_http_post_test_button',
						'name'        => __( 'Test HTTP POST', 'woocommerce-customer-order-csv-export' ),
						'method'      => 'http_post',
						'type'        => 'csv_test_button',
						'export_type' => 'customers',
						'class'       => 'js-auto-export-http-post-field js-auto-export-test-button',
					],

					[ 'type' => 'sectionend' ],

					[
						'id'   => 'wc_customer_order_csv_export_customers_email_settings',
						'name' => __( 'Email Settings', 'woocommerce-customer-order-csv-export' ),
						'type' => 'title'
					],

					[
						'id'       => 'wc_customer_order_csv_export_customers_email_recipients',
						'name'     => __( 'Recipient(s)', 'woocommerce-customer-order-csv-export' ),
						/* translators: Placeholders: %s - email address */
						'desc_tip' => sprintf( __( 'Enter recipients (comma separated) the exported CSV should be emailed to. Defaults to %s.', 'woocommerce-customer-order-csv-export' ), '<em>' . esc_html( get_option( 'admin_email' ) ) . '</em>' ),
						'default'  => '',
						'type'     => 'text',
						'class'    => 'js-auto-export-email-field',
					],

					[
						'id'       => 'wc_customer_order_csv_export_customers_email_subject',
						'name'     => __( 'Email Subject', 'woocommerce-customer-order-csv-export' ),
						'desc_tip' => __( 'Enter the email subject.', 'woocommerce-customer-order-csv-export' ),
						/* translators: Placeholders: %s - blog name */
						'default'  => sprintf( __( '[%s] Customer CSV Export', 'woocommerce-customer-order-csv-export' ), get_option( 'blogname' ) ),
						'type'     => 'text',
						'class'    => 'js-auto-export-email-field',
					],

					[
						'id'          => 'wc_customer_order_csv_export_customers_email_test_button',
						'name'        => __( 'Test Email', 'woocommerce-customer-order-csv-export' ),
						'method'      => 'email',
						'type'        => 'csv_test_button',
						'export_type' => 'customers',
						'class'       => 'js-auto-export-email-field js-auto-export-test-button',
					],

				[ 'type' => 'sectionend' ],
			],

		];

		$settings = self::get_coupon_export_settings( $settings );

		// return all or section-specific settings
		$found_settings = $section_id ? $settings[ $section_id ] : $settings;

		/**
		 * Allow actors to add or remove settings from the CSV export settings page.
		 *
		 * In 4.0.0 renamed $tab_id arg to $section_id, moved here from
		 * WC_Customer_Order_CSV_Export_Admin class
		 *
		 * @since 3.0.6
		 * @param array $settings an array of settings for the given section
		 * @param string $section_id current section ID
		 */
		return apply_filters( 'wc_customer_order_csv_export_settings', $found_settings, $section_id );
	}


	/**
	 * Gets the orders export formats.
	 *
	 * @since 4.6.4
	 *
	 * @param bool $use_legacy whether to make legacy formats available
	 * @return array associative array of option keys and labels
	 */
	private static function get_orders_export_formats( $use_legacy = false ) {

		$predefined_optgroup_label = __( 'Predefined', 'woocommerce-customer-order-csv-export' );
		$custom_optgroup_label     = __( 'Custom', 'woocommerce-customer-order-csv-export' );
		$legacy_optgroup_label     = __( 'Legacy', 'woocommerce-customer-order-csv-export' );

		$export_options = [
			$predefined_optgroup_label => [],
			$custom_optgroup_label     => [],
		];

		if ( true === $use_legacy ) {
			$export_options[ $legacy_optgroup_label ] = [];
		}

		// predefined formats
		$predefined_format_keys = [
			'default',
			'default_one_row_per_item',
			'import',
		];

		foreach ( $predefined_format_keys as $predefined_format_key ) {
			$predefined_format = wc_customer_order_csv_export()->get_formats_instance()->get_format_definition( 'orders', $predefined_format_key );

			if ( null !== $predefined_format ) {
				$export_options[ $predefined_optgroup_label ][ $predefined_format_key ] = $predefined_format->get_name();
			}
		}

		// custom formats
		$custom_formats = wc_customer_order_csv_export()->get_formats_instance()->get_custom_format_definitions( 'orders' );

		foreach ( $custom_formats as $custom_format_key => $custom_format ) {
			$export_options[ $custom_optgroup_label ][ $custom_format_key ] = $custom_format->get_name();
		}

		// since v4.6.4 only legacy installations will support legacy formats
		if ( true === $use_legacy ) {

			$legacy_format_keys = [
				'legacy_import',
				'legacy_one_row_per_item',
				'legacy_single_column',
			];

			foreach ( $legacy_format_keys as $legacy_format_key ) {
				$legacy_format = wc_customer_order_csv_export()->get_formats_instance()->get_format_definition( 'orders', $legacy_format_key );

				if ( null !== $legacy_format ) {
					$export_options[ $legacy_optgroup_label ][ $legacy_format_key ] = $legacy_format->get_name();
				}
			}
		}

		// remove groups without options
		$export_options = array_filter( $export_options );

		return $export_options;
	}


	/**
	 * Gets the customers export formats.
	 *
	 * @since 4.6.4
	 *
	 * @param bool $use_legacy whether to make legacy formats available
	 * @return array associative array of option keys and labels
	 */
	private static function get_customers_export_formats( $use_legacy = false ) {

		$predefined_optgroup_label = __( 'Predefined', 'woocommerce-customer-order-csv-export' );
		$custom_optgroup_label     = __( 'Custom', 'woocommerce-customer-order-csv-export' );
		$legacy_optgroup_label     = __( 'Legacy', 'woocommerce-customer-order-csv-export' );

		$export_options = [
			$predefined_optgroup_label => [],
			$custom_optgroup_label     => [],
		];

		if ( true === $use_legacy ) {
			$export_options[ $legacy_optgroup_label ] = [];
		}

		// predefined formats
		$predefined_format_keys = [
			'default',
			'import',
		];

		foreach ( $predefined_format_keys as $predefined_format_key ) {
			$predefined_format = wc_customer_order_csv_export()->get_formats_instance()->get_format_definition( 'customers', $predefined_format_key );

			if ( null !== $predefined_format ) {
				$export_options[ $predefined_optgroup_label ][ $predefined_format_key ] = $predefined_format->get_name();
			}
		}

		// custom formats
		$custom_formats = wc_customer_order_csv_export()->get_formats_instance()->get_custom_format_definitions( 'customers' );

		foreach ( $custom_formats as $custom_format_key => $custom_format ) {
			$export_options[ $custom_optgroup_label ][ $custom_format_key ] = $custom_format->get_name();
		}

		// since v4.6.4 only legacy installations will support legacy formats
		if ( true === $use_legacy ) {

			$legacy_format_keys = [
				'legacy',
			];

			foreach ( $legacy_format_keys as $legacy_format_key ) {
				$legacy_format = wc_customer_order_csv_export()->get_formats_instance()->get_format_definition( 'customers', $legacy_format_key );

				if ( null !== $legacy_format ) {
					$export_options[ $legacy_optgroup_label ][ $legacy_format_key ] = $legacy_format->get_name();
				}
			}
		}

		// remove groups without options
		$export_options = array_filter( $export_options );

		return $export_options;
	}


	/**
	 * Retrieves coupon export settings.
	 *
	 * @since 4.6.0
	 *
	 * @param array $settings existing settings array
	 * @return array updated settings
	 */
	public static function get_coupon_export_settings( $settings ) {

		$predefined_optgroup_label = __( 'Predefined', 'woocommerce-customer-order-csv-export' );
		$custom_optgroup_label     = __( 'Custom', 'woocommerce-customer-order-csv-export' );

		$export_options = [
			$predefined_optgroup_label => [],
			$custom_optgroup_label     => [],
		];

		// predefined formats
		$predefined_format_keys = [
			'default',
		];

		foreach ( $predefined_format_keys as $predefined_format_key ) {
			$predefined_format = wc_customer_order_csv_export()->get_formats_instance()->get_format_definition( 'coupons', $predefined_format_key );

			if ( null !== $predefined_format ) {
				$export_options[ $predefined_optgroup_label ][ $predefined_format_key ] = $predefined_format->get_name();
			}
		}

		// custom formats
		$custom_formats = wc_customer_order_csv_export()->get_formats_instance()->get_custom_format_definitions( 'coupons' );

		foreach ( $custom_formats as $custom_format_key => $custom_format ) {
			$export_options[ $custom_optgroup_label ][ $custom_format_key ] = $custom_format->get_name();
		}

		// remove groups without options
		$export_options = array_filter( $export_options );

		$settings['coupons'] = [

			[
				'name' => __( 'Export Format', 'woocommerce-customer-order-csv-export' ),
				'type' => 'title'
			],

			[
				'id'       => 'coupons_export_format',
				'name'     => __( 'Coupon Export Format', 'woocommerce-customer-order-csv-export' ),
				'desc_tip' => __( 'CSV Import matches the Customer/Order CSV Import plugin format', 'woocommerce-customer-order-csv-export' ),
				'type'     => 'select_with_optgroup',
				'options'  => $export_options,
				'default'  => 'default',
			],

			[
				'id'       => 'wc_customer_order_csv_export_coupons_filename',
				'name'     => __( 'Coupon Export Filename', 'woocommerce-customer-order-csv-export' ),
				'desc_tip' => __( 'The filename for exported coupons. Merge variables: %%timestamp%%', 'woocommerce-customer-order-csv-export' ),
				'default'  => 'coupons-export-%%timestamp%%.csv',
				'css'      => 'min-width: 300px;',
				'type'     => 'text',
			],

			[
				'id'       => 'wc_customer_order_csv_export_enable_batch_processing',
				'name'     => __( 'Batch Processing', 'woocommerce-customer-order-csv-export' ),
				'desc'     => __( 'Use batch processing for manual exports.', 'woocommerce-customer-order-csv-export' ),
				'desc_tip' => __( 'Only enable this setting when notified that your site does not support background processing.', 'woocommerce-customer-order-csv-export' ),
				'default'  => 'no',
				'type'     => 'checkbox',
			],

			[ 'type' => 'sectionend' ],
		];

		return $settings;
	}


	/**
	 * Render a test button
	 *
	 * In 4.0.0 moved here from WC_Customer_Order_CSV_Export_Admin class
	 *
	 * @since 3.0.0
	 * @param array $field
	 */
	public function render_test_button( $field ) {

		$settings_exist = wc_customer_order_csv_export()->get_methods_instance()->method_settings_exist( $field['method'], $field['export_type'] );
		$name           = $field['name'];
		$atts           = [ 'data-method' => $field['method'] ];
		$classes        = array_merge( [ 'secondary' ], explode( ' ', $field['class'] ) );
		$button_type    = implode( ' ', $classes );

		// disable text button and change name if required
		if ( ! $settings_exist ) {
			$name = __( 'Please save your settings before testing', 'woocommerce-customer-order-csv-export' );
			$atts['disabled'] = 'disabled';
		}

		?>
			<tr valign="top">
				<th scope="row" class="titledesc">Test</th>
				<td class="forminp">
					<?php submit_button( $name, $button_type, $field['id'], true, $atts ); ?>
				</td>
			</tr>
		<?php
	}


	/**
	 * Show Settings page
	 *
	 * @since 4.0.0
	 */
	public function output() {

		global $current_section;

		// default to orders section
		if ( ! $current_section ) {
			$current_section = 'orders';
		}

		$this->output_sections();

		$settings = self::get_settings( $current_section );

		// load export format
		$format_option       = 'wc_customer_order_csv_export_' . $current_section . '_format';
		$format_select_value = get_option( $format_option, 'default' );

		// load custom export format
		if ( 'custom' === $format_select_value ) {
			$custom_format_option = 'wc_customer_order_csv_export_' . $current_section . '_custom_format';
			$format_select_value  = get_option( $custom_format_option, 'custom' );
		}

		foreach ( $settings as $key => $setting ) {

			if ( isset( $setting['id'] ) && $current_section . '_export_format' === $setting['id'] ) {
				$settings[ $key ]['default'] = $format_select_value;
				break;
			}
		}

		// render settings fields
		woocommerce_admin_fields( $settings );

		wp_nonce_field( __FILE__ );
		submit_button( __( 'Save settings', 'woocommerce-customer-order-csv-export' ) );
	}


	/**
	 * Save settings or perform a test export
	 *
	 * @since 4.0.0
	 */
	public function save() {

		global $current_section;

		// default to orders section
		if ( ! $current_section ) {
			$current_section = 'orders';
		}

		// security check
		if ( ! wp_verify_nonce( $_POST['_wpnonce'], __FILE__ ) ) {

			wp_die( __( 'Action failed. Please refresh the page and retry.', 'woocommerce-customer-order-csv-export' ) );
		}

		if ( isset( $_POST['wc_customer_order_csv_export_test_method'] ) ) {

			// process test
			$export_handler = wc_customer_order_csv_export()->get_export_handler_instance();

			$result = $export_handler->test_export_via( $_POST['wc_customer_order_csv_export_test_method'], $current_section );

			if ( 'error' === $result[1] ) {
				wc_customer_order_csv_export()->get_message_handler()->add_error( $result[0] );
			} else {
				wc_customer_order_csv_export()->get_message_handler()->add_message( $result[0] );
			}


		} else {

			$orig_schedule_signature = $this->get_auto_export_schedule_signature( $current_section );

			// make sure export filenames are always set, otherwise bad things are going to happen
			$filename_option = 'wc_customer_order_csv_export_' . $current_section . '_filename';

			if ( isset( $_POST[ $filename_option ] ) && empty( $_POST[ $filename_option ] ) ) {
				$_POST[ $filename_option ] = $this->get_default_option_value( $current_section, $filename_option );
			}

			$settings = self::get_settings( $current_section );

			// save custom export format into 2 separate options
			$select_id            = $current_section . '_export_format';
			$format_option        = 'wc_customer_order_csv_export_' . $current_section . '_format';
			$custom_format_option = 'wc_customer_order_csv_export_' . $current_section . '_custom_format';

			if ( isset( $_POST[ $select_id ] ) ) {

				if ( strpos( $_POST[ $select_id ], 'custom' ) !== false ) {
					update_option( $format_option, 'custom' );
					update_option( $custom_format_option, $_POST[ $select_id ] );
				} else {
					update_option( $format_option, $_POST[ $select_id ] );
					update_option( $custom_format_option, '' );
				}
			}

			foreach ( $settings as $key => $setting ) {
				if ( isset( $setting['id'] ) && $select_id === $setting['id'] ) {
					unset( $settings[ $key ] );
					break;
				}
			}

			// save settings
			woocommerce_update_options( $settings );

			// clear scheduled export event if scheduled exports disabled or export interval and/or start time were changed
			if ( ! wc_customer_order_csv_export()->get_cron_instance()->scheduled_exports_enabled( $current_section ) || $orig_schedule_signature !== $this->get_auto_export_schedule_signature( $current_section ) ) {

				// note this resets the next scheduled execution time to the time options were saved + the interval
				wp_clear_scheduled_hook( 'wc_customer_order_csv_export_auto_export_' . $current_section );
			}

			wc_customer_order_csv_export()->get_message_handler()->add_message( __( 'Your settings have been saved.', 'woocommerce-customer-order-csv-export' ) );
		}
	}


	/**
	 * Mutate the select2 v4 values (introduced in WC 3.0) from an array of
	 * product IDs into a comma-separated string.
	 *
	 * @since 4.2.0
	 * @param string|array $value parsed value from $_POST
	 * @return string
	 */
	public function mutate_select2_product_ids( $value ) {

		return implode( ',', array_map( 'absint', (array) $value ) );
	}


	/**
	 * Get the currently configured auto export schedule signature
	 *
	 * Helper method to get the concatenated export start time and interval,
	 * used for testing if the schedule has been changed.
	 *
	 * @since 4.0.0
	 * @param string $export_type
	 * @return string
	 */
	private function get_auto_export_schedule_signature( $export_type ) {

		return get_option( 'wc_customer_order_csv_export_' . $export_type . '_auto_export_start_time' ) . get_option( 'wc_customer_order_csv_export_' . $export_type . '_auto_export_interval' );
	}


	/**
	 * Get default option value
	 *
	 * @since 4.0.0
	 * @param string $section
	 * @param string $option_id
	 * @return mixed|null null if no default value
	 */
	private function get_default_option_value( $section, $option_id ) {

		$settings      = self::get_settings( $section );
		$default_value = null;

		foreach ( $settings as $setting ) {

			if ( isset( $setting['id'] ) && $setting['id'] === $option_id ) {

				$default_value = isset( $setting['default'] ) ? $setting['default'] : null;
				break;
			}
		}

		return $default_value;
	}


}
