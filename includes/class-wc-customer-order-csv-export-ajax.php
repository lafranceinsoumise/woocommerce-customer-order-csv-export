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
 * Customer/Order CSV Export AJAX Handler
 *
 * @since 4.0.0
 */
class WC_Customer_Order_CSV_Export_AJAX {


	/**
	 * Initialize AJAX class instance
	 *
	 * @since 4.0.0
	 */
	public function __construct() {

		add_action( 'wp_ajax_wc_customer_order_csv_export_create_export',     [ $this, 'create_export' ] );
		add_action( 'wp_ajax_wc_customer_order_csv_export_get_export_status', [ $this, 'get_export_status' ] );

		add_action( 'wp_ajax_wc_customer_order_csv_export_select_export_format', [ $this, 'select_export_format' ] );

		// filter out grouped products from WC JSON search results
		add_filter( 'woocommerce_json_search_found_products', [ $this, 'filter_json_search_found_products' ] );

		// handle dismissed admin notices
		add_action( 'wc_customer_order_csv_export_dismiss_notice', [ $this, 'handle_dismiss_notice' ], 10, 2 );
	}


	/**
	 * Create export job
	 *
	 * @since 4.0.0
	 */
	public function create_export() {

		check_ajax_referer( 'create-export', 'security' );

		// bail out if no export type, method or query provided
		if ( empty( $_POST['export_type'] ) || empty( $_POST['export_method'] ) || empty( $_POST['export_query'] ) ) {

			wp_send_json_error( [
				'title'   => esc_html__( 'Export Failed', 'woocommerce-customer-order-csv-export' ),
				'message' => esc_html__( 'Missing export type, method or query.', 'woocommerce-customer-order-csv-export' ),
			] );
		}

		$export_type   = $_POST['export_type'];
		$export_method = $_POST['export_method'];
		$export_query  = $_POST['export_query'];

		require_once( wc_customer_order_csv_export()->get_plugin_path() . '/includes/class-wc-customer-order-csv-export-query-parser.php' );

		// `ids` in the query will take priority - it's used directly, as the export
		// input and all other query params will be ignored
		$export_ids = ! empty( $export_query['ids'] ) ? $export_query['ids'] : WC_Customer_Order_CSV_Export_Query_Parser::parse_export_query( $export_query, $export_type );

		// in case we're exporting a single order, cast as array
		$export_ids = array_filter( array_map( [ $this, 'sanitize_export_ids' ], (array) $export_ids ) );

		// nothing found to export
		if ( empty( $export_ids ) ) {

			switch ( $export_type ) {

				case 'orders':
					$message = esc_html__( 'No orders found to export', 'woocommerce-customer-order-csv-export' );
				break;

				case 'customers':
					$message = esc_html__( 'No customers found to export', 'woocommerce-customer-order-csv-export' );
				break;

				case 'coupons':
					$message = esc_html__( 'No coupons found to export.', 'woocommerce-customer-order-csv-export' );
				break;
			}

			wp_send_json_error( [
				'title'   => esc_html__( 'Nothing to Export', 'woocommerce-customer-order-csv-export' ),
				'message' => $message,
			] );
		}

		if ( 'auto_export' === $export_method ) {

			$export_method = wc_customer_order_csv_export()->get_methods_instance()->get_auto_export_method( $export_type );

			if ( ! $export_method ) {

				wp_send_json_error( [
					'title'   => esc_html__( 'Export Failed', 'woocommerce-customer-order-csv-export' ),
					'message' => esc_html__( 'Auto export method not configured.', 'woocommerce-customer-order-csv-export' ),
				] );
			}
		}

		try {

			$export = wc_customer_order_csv_export()->get_export_handler_instance()->start_export( $export_ids, [
				'type'   => $export_type,
				'method' => $export_method,
			] );

			// construct the status url
			$status_url = wp_nonce_url( admin_url( 'admin-ajax.php' ), 'get-export-status', 'security' );
			$status_url = add_query_arg( [
				'action'    => 'wc_customer_order_csv_export_get_export_status',
				'export_id' => $export->get_id(),
			], $status_url );

			wp_send_json( [
				'export_id'  => $export->get_id(),
				'method'     => $export->get_transfer_method(),
				'status'     => $export->get_status(),
				'status_url' => $status_url,
			] );

		} catch ( Framework\SV_WC_Plugin_Exception $e ) {

			wp_send_json_error( [
				'title'   => esc_html__( 'Export Failed', 'woocommerce-customer-order-csv-export' ),
				'message' => $e->getMessage(),
			] );

		}
	}


	/**
	 * Ensure export IDs are only integers. Note that customer export IDs
	 * can be either a user ID or for guests, an array in the format: [ billing email, order ID ]
	 *
	 * @since 4.3.3
	 * @param $id
	 * @return array|int
	 */
	public function sanitize_export_ids( $id ) {

		if ( is_array( $id ) ) {
			return [ wc_clean( $id[0] ), absint( $id[1] ) ];
		} else {
			return absint( $id );
		}
	}


	/**
	 * Get export job status
	 *
	 * @since 4.0.0
	 */
	public function get_export_status() {

		check_ajax_referer( 'get-export-status', 'security' );

		// Bail out if no export id is provided
		if ( empty( $_GET['export_id'] ) ) {
			return;
		}

		$export = wc_customer_order_csv_export_get_export( $_GET['export_id'] );

		try {

			if ( ! $export ) {
				throw new Framework\SV_WC_Plugin_Exception( wc_customer_order_csv_export()->get_background_export_instance()->get_export_status_message( 'not-found', $_GET['export_id'] ) );
			}

			if ( 'failed' === $export->get_status() ) {
				throw new Framework\SV_WC_Plugin_Exception( wc_customer_order_csv_export()->get_background_export_instance()->get_export_status_message( 'failed' ) );
			}

			$response = [
				'export_id'       => $export->get_id(),
				'method'          => $export->get_transfer_method(),
				'status'          => $export->get_status(),
				'transfer_status' => $export->get_transfer_status(),
			];

			if ( 'completed' === $export->get_status() ) {

				$download_url = wp_nonce_url( admin_url(), 'download-export' );

				// return the download url for the exported file
				$response['download_url'] =	add_query_arg( [
					'download_exported_csv_file' => 1,
					'export_id'                  => $response['export_id'],
				], $download_url );

				if ( 'failed' === $export->get_transfer_status() ) {
					throw new Framework\SV_WC_Plugin_Exception( wc_customer_order_csv_export()->get_background_export_instance()->get_export_status_message( 'transfer-failed' ) );
				}
			}

			wp_send_json_success( $response );

		} catch ( Framework\SV_WC_Plugin_Exception $e ) {

			$data = $export ? [ 'id' => $export->get_id() ] : [];

			$data['message'] = $e->getMessage();

			wp_send_json_error( $data );
		}
	}


	/**
	 * Selects the given export format in the settings.
	 *
	 * @internal
	 *
	 * @since 4.7.0
	 */
	public function select_export_format() {

		check_ajax_referer( 'select-export-format', 'security' );

		if ( empty( $_POST['export_type'] || empty( $_POST['export_format'] ) ) ) {
			wp_send_json_error( [ 'message' => __( 'Export type or export format parameter not provided.' ) ] );
		}

		$posted_data       = wc_clean( $_POST );
		$export_type       = $posted_data['export_type'];
		$export_format_key = $posted_data['export_format'];

		// check if the export format exists
		$custom_export_format = wc_customer_order_csv_export()->get_formats_instance()->get_format_definition( $export_type, $export_format_key );

		if ( null !== $custom_export_format ) {
			update_option( 'wc_customer_order_csv_export_' . $export_type . '_format', 'custom' );
			update_option( 'wc_customer_order_csv_export_' . $export_type . '_custom_format', $export_format_key );
		} else {
			wp_send_json_error( [ 'message' => __( 'Export format not found.' ) ] );
		}

		wp_send_json_success();
	}


	/**
	 * Remove grouped products from json search results
	 *
	 * @since 4.0.0
	 * @param array $products
	 * @return array $products
	 */
	public function filter_json_search_found_products( $products ) {

		// Remove grouped products
		if ( isset( $_REQUEST['exclude'] ) && 'wc_customer_order_csv_export_grouped_products' === $_REQUEST['exclude'] ) {
			foreach( $products as $id => $title ) {

				$product = wc_get_product( $id );

				if ( $product->is_type('grouped') ) {
					unset( $products[ $id ] );
				}
			}
		}

		return $products;
	}


	/**
	 * Handle dismissing admin notices
	 *
	 * Removes any export finished or auto-export failure notices from db
	 *
	 * @since 4.0.0
	 * @param string $message_id
	 * @param int $user_id
	 */
	public function handle_dismiss_notice( $message_id, $user_id ) {

		// user-specific notices (used for manual exports)
		if ( Framework\SV_WC_Helper::str_starts_with( $message_id, 'wc_customer_order_csv_export_finished_' ) ) {

			$parts     = explode( '_', $message_id );
			$export_id = array_pop( $parts );

			wc_customer_order_csv_export()->get_export_handler_instance()->remove_export_finished_notice( $export_id, $user_id );

		} elseif ( ! in_array( $message_id, [ 'wc_customer_order_csv_export_auto_export_failure', 'wc_customer_order_csv_export_auto_export_transfer_failure' ], true ) ) {
			return;
		}

		// auto-export failure notices
		$failure_type = Framework\SV_WC_Helper::str_ends_with( $message_id, 'transfer_failure' ) ? 'transfer' : 'export';
		$notices      = get_option( 'wc_customer_order_csv_export_failure_notices' );

		unset( $notices[ $failure_type ] );

		update_option( 'wc_customer_order_csv_export_failure_notices', $notices );

		// undismiss notice, so that if further failures happen, the notice will re-appear
		wc_customer_order_csv_export()->get_admin_notice_handler()->undismiss_notice( $message_id, $user_id );
	}

}
