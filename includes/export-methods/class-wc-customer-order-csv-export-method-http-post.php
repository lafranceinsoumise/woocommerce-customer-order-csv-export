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
 * @package     WC-Customer-Order-CSV-Export/Export-Methods/HTTP-POST
 * @author      SkyVerge
 * @copyright   Copyright (c) 2012-2016, SkyVerge, Inc.
 * @license     http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

defined( 'ABSPATH' ) or exit;

/**
 * Export HTTP POST Class
 *
 * Simple wrapper for wp_remote_post() to POST exported data to remote URLs
 *
 * @since 3.0.0
 */
class WC_Customer_Order_CSV_Export_Method_HTTP_POST implements WC_Customer_Order_CSV_Export_Method {


	/** @var string MIME Content Type */
	private $content_type;

	/** @var string HTTP POST Url */
	private $http_post_url;


	/**
	 * Initialize the export method
	 *
	 * @since 4.0.0
	 * @param array $args {
	 *     An array of arguments.
	 *
	 *     @type string $content_type MIME Content-Type for the file
	 *     @type string $http_post_url URL to POST data to
	 * }
	 */
	 public function __construct( $args ) {

		$this->content_type  = $args['content_type'];
		$this->http_post_url = $args['http_post_url'];
	}


	/**
	 * Performs an HTTP POST to the specified URL with the exported data
	 *
	 * @since 3.0.0
	 * @param string $file_path pathj to file to be posted
	 * @throws SV_WC_Plugin_Exception WP HTTP error handling
	 * @return bool whether the HTTP POST was successful or not
	 */
	public function perform_action( $file_path ) {

		if ( empty( $file_path ) ) {
			throw new SV_WC_Plugin_Exception( __( 'Missing file path', 'woocommerce-customer-order-csv-export' ) );
		}

		/**
		 * Allow actors to modify HTTP POST args
		 *
		 * @since 3.0.0
		 * @param array $args
		 */
		$args = apply_filters( 'wc_customer_order_csv_export_http_post_args', array(
			'timeout'     => 60,
			'redirection' => 0,
			'httpversion' => '1.0',
			'sslverify'   => true,
			'blocking'    => true,
			'headers'     => array(
				'accept'       => $this->content_type,
				'content-type' => $this->content_type,
			),
			'body'        => file_get_contents( $file_path ),
			'cookies'     => array(),
			'user-agent'  => "WordPress " . $GLOBALS['wp_version'],
		) );

		$result = wp_safe_remote_post( $this->http_post_url, $args );

		// check for errors
		if ( is_wp_error( $result ) ) {

			throw new SV_WC_Plugin_Exception( $result->get_error_message() );
		}

		/**
		 * Allow actors to adjust whether the HTTP POST was a success or not
		 *
		 * By default a 200 (OK) or 201 (Created) status will indicate success.
		 *
		 * @since 4.0.0
		 * @param bool $success whether the request was successful or not
		 * @param array $result full wp_remote_post() result
		 */
		return apply_filters( 'wc_customer_order_csv_export_http_post_success', in_array( $result['response']['code'], array( 200, 201 ) ), $result );
	}

}
