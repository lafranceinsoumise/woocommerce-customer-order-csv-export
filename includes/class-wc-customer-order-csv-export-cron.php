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
 * @package     WC-Customer-Order-CSV-Export/Generator
 * @author      SkyVerge
 * @copyright   Copyright (c) 2012-2016, SkyVerge, Inc.
 * @license     http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

defined( 'ABSPATH' ) or exit;

/**
 * Customer/Order CSV Export Cron Class
 *
 * Adds custom schedule and schedules the export event, as well as cleaning up
 * of expired exports.
 *
 * @since 3.0.0
 */
class WC_Customer_Order_CSV_Export_Cron {


	/**
	 * Setup hooks and filters specific to WP-cron functions
	 *
	 * @since 3.0.0
	 */
	public function __construct() {

		// Add custom schedule, e.g. every 10 minutes
		add_filter( 'cron_schedules', array( $this, 'add_auto_export_schedules' ) );

		// Schedule auto-update events if they don't exist, run in both frontend and
		// backend so events are still scheduled when an admin reactivates the plugin
		add_action( 'init', array( $this, 'add_scheduled_export' ) );

		// schedule cleanup of expired exports
		add_action( 'init', array( $this, 'schedule_export_cleanup' ) );

		// cleanup expired exports
		add_action( 'wc_customer_order_csv_export_scheduled_export_cleanup', array( $this, 'cleanup_exports' ) );

		// Trigger export + upload of non-exported orders, wp-cron fires this action
		// on the given recurring schedule
		add_action( 'wc_customer_order_csv_export_auto_export_orders',    array( $this, 'auto_export_orders' ) );
		add_action( 'wc_customer_order_csv_export_auto_export_customers', array( $this, 'auto_export_customers' ) );

		// trigger order export when an order is processed or status updates
		add_action( 'woocommerce_checkout_order_processed', array( $this, 'auto_export_order' ) );
		add_action( 'woocommerce_order_status_changed',     array( $this, 'auto_export_order' ) );
	}


	/**
	 * Check if auto-exports are enabled
	 *
	 * @since 4.0.0
	 * @param string $export_type
	 * @return bool
	 */
	public function exports_enabled( $export_type ) {
		return (bool) wc_customer_order_csv_export()->get_methods_instance()->get_auto_export_method( $export_type );
	}


	/**
	 * Check if scheduled auto-exports are enabled
	 *
	 * @since 4.0.0
	 * @param string $export_type
	 * @return bool
	 */
	public function scheduled_exports_enabled( $export_type ) {

		$exports_enabled = $this->exports_enabled( $export_type );

		if ( $exports_enabled && 'orders' === $export_type ) {
			$exports_enabled = ( 'schedule' === get_option( 'wc_customer_order_csv_export_orders_auto_export_trigger' ) );
		}

		return $exports_enabled;
	}


	/**
	 * If automatic schedule exports are enabled, add the custom interval
	 * (e.g. every 15 minutes) set on the admin settings page
	 *
	 * In 4.0.0 renamed from add_auto_export_schedule to add_auto_export_schedules
	 *
	 * @since 3.0.0
	 * @param array $schedules WP-Cron schedules array
	 * @return array $schedules now including our custom schedule
	 */
	public function add_auto_export_schedules( $schedules ) {

		foreach ( array( 'orders', 'customers' ) as $export_type ) {

			if ( $this->scheduled_exports_enabled( $export_type ) ) {

				$export_interval = get_option( 'wc_customer_order_csv_export_' . $export_type . '_auto_export_interval' );

				if ( $export_interval ) {

					$schedules[ 'wc_customer_order_csv_export_' . $export_type . '_auto_export_interval' ] = array(
						'interval' => (int) $export_interval * 60,
						'display'  => sprintf( _n(  'Every minute', 'Every %d minutes', (int) $export_interval, 'woocommerce-customer-order-csv-export' ), (int) $export_interval )
					);
				}
			}
		}

		return $schedules;
	}


	/**
	 * If automatic scheduled exports are enabled, add the event if not already scheduled
	 *
	 * This performs a `do_action( 'wc_customer_order_csv_export_auto_export_orders' )`
	 * on our custom schedule
	 *
	 * @since 3.0.0
	 */
	public function add_scheduled_export() {

		foreach ( array( 'orders', 'customers' ) as $export_type ) {

			if ( $this->scheduled_exports_enabled( $export_type ) ) {

				// Schedule export
				if ( ! wp_next_scheduled( 'wc_customer_order_csv_export_auto_export_' . $export_type ) ) {

					$start_time = get_option( 'wc_customer_order_csv_export_' . $export_type . '_auto_export_start_time' );
					$curr_time  = current_time( 'timestamp' );

					if ( $start_time ) {

						if ( $curr_time > strtotime( 'today ' . $start_time, $curr_time ) ) {

							$start_timestamp = strtotime( 'tomorrow ' . $start_time, $curr_time ) - ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS );

						} else {

							$start_timestamp = strtotime( 'today ' . $start_time, $curr_time ) - ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS );
						}

					} else {

						$export_interval = get_option( 'wc_customer_order_csv_export_' . $export_type . '_auto_export_interval' );
						$start_timestamp = strtotime( "now +{$export_interval} minutes" );
					}

					wp_schedule_event( $start_timestamp, 'wc_customer_order_csv_export_' . $export_type . '_auto_export_interval', 'wc_customer_order_csv_export_auto_export_' . $export_type );
				}
			}
		}
	}


	/**
	 * Exports any non-exported orders and performs the chosen action
	 * (upload, HTTP POST, email)
	 *
	 * @since 3.0.0
	 */
	public function auto_export_orders() {

		$export_method = get_option( 'wc_customer_order_csv_export_orders_auto_export_method' );

		if ( ! $export_method ) {
			return;
		}

		/**
		 * Allow actors to adujst whether only new orders should be included in auto-exports or not
		 *
		 * @since 4.0.0
		 * @param bool $new_only defaults to true
		 */
		$export_new_orders_only = apply_filters( 'wc_customer_order_csv_export_auto_export_new_orders_only', true );

		require_once( wc_customer_order_csv_export()->get_plugin_path() . '/includes/class-wc-customer-order-csv-export-query-parser.php' );

		$order_ids = WC_Customer_Order_CSV_Export_Query_Parser::parse_orders_export_query( array(
			'statuses'           => get_option( 'wc_customer_order_csv_export_orders_auto_export_statuses' ),
			'products'           => get_option( 'wc_customer_order_csv_export_orders_auto_export_products' ),
			'product_categories' => get_option( 'wc_customer_order_csv_export_orders_auto_export_product_categories' ),
			'not_exported'       => $export_new_orders_only,
		) );

		if ( ! empty( $order_ids ) ) {

			try {

				wc_customer_order_csv_export()->get_export_handler_instance()->start_export( $order_ids, array(
					'type'       => 'orders',
					'method'     => $export_method,
					'invocation' => 'auto',
				) );

			} catch ( SV_WC_Plugin_Exception $e ) {

				// log errors
				/* translators: Placeholders: %s - error message */
				wc_customer_order_csv_export()->log( sprintf( esc_html__( 'Scheduled orders export failed: %s', 'woocommerce-customer-order-csv-export' ), $e->getMessage() ) );
			}
		}

	}


	/**
	 * Exports any non-exported orders and performs the chosen action
	 * (upload, HTTP POST, email)
	 *
	 * @since 4.0.0
	 */
	public function auto_export_customers() {

		$export_method = get_option( 'wc_customer_order_csv_export_customers_auto_export_method' );

		if ( ! $export_method ) {
			return;
		}

		/**
		 * Allow actors to adjust whether only new customers should be included in auto-exports or not
		 *
		 * @since 4.0.0
		 * @param bool $new_only defaults to true
		 */
		$export_new_customers_only = apply_filters( 'wc_customer_order_csv_export_auto_export_new_customers_only', true );

		require_once( wc_customer_order_csv_export()->get_plugin_path() . '/includes/class-wc-customer-order-csv-export-query-parser.php' );

		$customers = WC_Customer_Order_CSV_Export_Query_Parser::parse_customers_export_query( array(
			'not_exported' => $export_new_customers_only,
		) );

		if ( ! empty( $customers ) ) {

			try {

				wc_customer_order_csv_export()->get_export_handler_instance()->start_export( $customers, array(
					'type'       => 'customers',
					'method'     => $export_method,
					'invocation' => 'auto',
				) );

			} catch ( SV_WC_Plugin_Exception $e ) {

				// log errors
				/* translators: Placeholders: %s - error message */
				wc_customer_order_csv_export()->log( sprintf( esc_html__( 'Scheduled customers export failed: %s', 'woocommerce-customer-order-csv-export' ), $e->getMessage() ) );

				// Notify the admin that exports are failing
				$failure_notices = get_option( 'wc_customer_order_csv_export_failure_notices', array() );

				$failure_notices['export'] = array( 'multiple_failures' => true );

				update_option( 'wc_customer_order_csv_export_failure_notices', $failure_notices );

			}
		}
	}


	/**
	 * Exports a single order when immediate auto-exports are enabled
	 *
	 * @since 4.0.0
	 * @param int $order_id Order ID to export
	 */
	public function auto_export_order( $order_id ) {

		if ( ! $this->exports_enabled( 'orders' ) || 'immediate' !== get_option( 'wc_customer_order_csv_export_orders_auto_export_trigger' ) ) {
			return;
		}

		// filter order based on status and other filtering options
		$order = wc_get_order( $order_id );

		// no order found or order not paid
		if ( ! $order || ! $order->is_paid() ) {
			return;
		}

		$product_ids        = get_option( 'wc_customer_order_csv_export_orders_auto_export_products' );
		$product_categories = get_option( 'wc_customer_order_csv_export_orders_auto_export_product_categories' );

		$export_handler = wc_customer_order_csv_export()->get_export_handler_instance();

		require_once( wc_customer_order_csv_export()->get_plugin_path() . '/includes/class-wc-customer-order-csv-export-query-parser.php' );

		// bail out if order does not contain required products
		if ( ! empty( $product_ids ) ) {

			$order_ids = WC_Customer_Order_CSV_Export_Query_Parser::filter_orders_containing_products( array( $order_id ), $product_ids );

			if ( empty( $order_ids ) ) {
				return;
			}
		}

		// bail out if order does not contain products in required categories
		if ( ! empty( $product_categories ) ) {

			$order_ids = WC_Customer_Order_CSV_Export_Query_Parser::filter_orders_containing_product_categories( array( $order_id ), $product_categories );

			if ( empty( $order_ids ) ) {
				return;
			}
		}

		try {

			// whoa, we got here! kick it off!
			$export_handler->start_export( $order_id, array(
				'type'       => 'orders',
				'method'     => get_option( 'wc_customer_order_csv_export_orders_auto_export_method' ),
				'invocation' => 'auto',
			) );

		} catch ( SV_WC_Plugin_Exception $e ) {

			// log errors
			/* translators: Placeholders: %s - error message */
			wc_customer_order_csv_export()->log( sprintf( esc_html__( 'Automatic order export failed: %s', 'woocommerce-customer-order-csv-export' ), $e->getMessage() ) );
		}
	}


	/**
	 * Clear scheduled events upon deactivation
	 *
	 * @since 3.1.0
	 */
	public function clear_scheduled_export() {

		wp_clear_scheduled_hook( 'wc_customer_order_csv_export_auto_export_orders' );
		wp_clear_scheduled_hook( 'wc_customer_order_csv_export_auto_export_customers' );
	}




	/**
	 * Schedule once-daily cleanup of old export jobs
	 *
	 * @since 4.0.0
	 */
	public function schedule_export_cleanup() {

		if ( ! wp_next_scheduled( 'wc_customer_order_csv_export_scheduled_export_cleanup' ) ) {

			wp_schedule_event( strtotime( 'tomorrow +15 minutes' ), 'daily', 'wc_customer_order_csv_export_scheduled_export_cleanup' );
		}
	}


	/**
	 * Clean up (remove) exports older than 14 days
	 *
	 * @since 4.0.0
	 */
	public function cleanup_exports() {

		wc_customer_order_csv_export()->get_export_handler_instance()->remove_expired_exports();
	}


}
