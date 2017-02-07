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
 * needs please refer to http://docs.woothemes.com/document/ordercustomer-csv-exporter/
 *
 * @package     WC-Customer-Order-CSV-Export/Classes
 * @author      SkyVerge
 * @copyright   Copyright (c) 2012-2016, SkyVerge, Inc.
 * @license     http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

defined( 'ABSPATH' ) or exit;

/**
 * Customer/Order CSV Export Download Handler
 *
 * Based on WC_Download_Handler
 *
 * @since 4.0.0
 */
class WC_Customer_Order_CSV_Export_Download_Handler {


	/**
	 * Initialize the download handler class
	 *
	 * @since 4.0.0
	 */
	public function __construct() {

		if ( isset( $_GET['download_exported_csv_file'] ) && isset( $_GET['export_id'] ) ) {
			add_action( 'init', array( $this, 'download_exported_file' ) );
		}
	}


	/**
	 * Download an exported file
	 *
	 * @since 4.0.0
	 */
	public function download_exported_file() {

		$background_export = wc_customer_order_csv_export()->get_background_export_instance();
		$job               = $background_export->get_job( $_GET['export_id'] );

		if ( ! $job ) {
			$this->download_error( __( 'Export not found', 'woocommerce-customer-order-csv-export' ) );
		}

		$export_id      = $job->id;
		$export_handler = wc_customer_order_csv_export()->get_export_handler_instance();
		$exports_dir    = $export_handler->get_exports_dir();
		$exports_url    = $export_handler->get_exports_url();
		$file_path      = $job->file_path;
		$filename       = basename( $file_path );

		if ( false !== strpos( $filename, '?' ) ) {
			$filename = current( explode( '?', $filename ) );
		}

		// file_url points to the actual physical location for the file
		$file_url = $exports_url . '/' . $filename;

		// strip random part from filename, which is prepended to the filename and
		// separated with a dash
		$filename = substr( $filename, strpos( $filename, '-' ) + 1 );

		/**
		 * Allow actors to change the export filename before download
		 *
		 * @since 4.0.0
		 * @param string $filename
		 * @param string $export_id
		 */
		$filename = apply_filters( 'wc_customer_order_csv_export_file_download_filename', $filename, $export_id );

		/**
		 * Allow actors to change the file download method
		 *
		 * @since 4.0.0
		 * @param string $method Defaults to WooCommer4ce file download method
		 * @param string $export_id
		 */
		$file_download_method = apply_filters( 'wc_customer_order_csv_export_file_download_method', get_option( 'woocommerce_file_download_method', 'force' ), $export_id );

		// Create a temp copy of the file with filtered filename in case of redirect-based
		// downloads, as we can't provide the filename in headers. The file is only
		// stored until the next page load.
		if ( 'redirect' === $file_download_method ) {

			$export_handler->create_temp_file( $filename, $file_path, $exports_dir, true );

			// override the original file url with the temp file url
			$file_url  = $exports_url . '/' . $filename;
		}

		// add action to prevent issues in IE
		add_action( 'nocache_headers', array( 'WC_Download_Handler', 'ie_nocache_headers_fix' ) );

		// trigger download via one of the methods. WC_Download_Handler will take over from here
		do_action( 'woocommerce_download_file_' . $file_download_method, $file_url, $filename );
	}


	/**
	 * Die with an error message if the download fails.
	 *
	 * @since 4.0.0
	 * @param  string $message
	 * @param  string  $title
	 * @param  integer $status
	 */
	private function download_error( $message, $title = '', $status = 404 ) {
		wp_die( $message, $title, array( 'response' => $status ) );
	}

}
