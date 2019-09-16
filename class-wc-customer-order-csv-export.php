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
 * # WooCommerce Customer/Order CSV Export
 *
 * ## Plugin Overview
 *
 * This plugin exports customers and orders in CSV format. Customers can be exported via
 * CSV Export > Export and are selected from orders in a selectable date range. Orders can be
 * exported in bulk from CSV Export > Export and from the Orders / Edit Order screen, as well as auto-exported
 * via FTP and HTTP POST on a recurring schedule.
 *
 * ## Class Description
 *
 * The main class for Customer/Order CSV Export. This class handles general lifecycle and setup functions, as well
 * as marking new orders as un-exported and handling the AJAX export action on the Order screen.
 *
 * ## Admin Considerations
 *
 * A 'CSV Export' sub-menu item is added under 'WooCommerce', with two tabs: 'Export' for handling bulk exports of
 * both customers and orders, and 'Settings' which define the output format for both customers and orders, as well as
 * auto-export interval & FTP/HTTP POST settings.
 *
 * An 'Export Status' column is added to the Orders list table, along with a new order action icon for downloading the order
 * to a CSV. Another order action is added to the Edit Order screen under the order actions select box.
 *
 * ## Database
 *
 * ### Options Table
 *
 * + `wc_customer_order_csv_export_orders_format` - order export format
 * + `wc_customer_order_csv_export_customers_format` - customer export format
 * + `wc_customer_order_csv_export_orders_filename` - filename used for order exports
 * + `wc_customer_order_csv_export_customers_filename` - filename used for customer exports
 * + `wc_customer_order_csv_export_orders_auto_export_method` - export method for order auto-exports, defaults to 'disabled'
 * + `wc_customer_order_csv_export_orders_auto_export_interval` - export interval for order auto-exports, in minutes
 * + `wc_customer_order_csv_export_orders_auto_export_statuses` - array of order statuses that are valid for auto-export
 * + `wc_customer_order_csv_export_orders_ftp_server` - FTP server
 * + `wc_customer_order_csv_export_orders_ftp_username` - FTP username
 * + `wc_customer_order_csv_export_orders_ftp_password` - FTP password
 * + `wc_customer_order_csv_export_orders_ftp_port` - FTP port
 * + `wc_customer_order_csv_export_orders_ftp_path` - FTP initial path
 * + `wc_customer_order_csv_export_orders_ftp_security` - type of FTP security, e.g. 'sftp'
 * + `wc_customer_order_csv_export_orders_passive_mode` - whether to enable passive mode for FTP connections
 * + `wc_customer_order_csv_export_orders_http_post_url` - the URL to POST exported CSV data to, when HTTP POST is enabled as a method
 * + `wc_customer_order_csv_export_customers_auto_export_method` - export method for customer auto-exports, defaults to 'disabled'
 * + `wc_customer_order_csv_export_customers_auto_export_interval` - export interval for customer auto-exports, in minutes
 * + `wc_customer_order_csv_export_customers_ftp_server` - FTP server
 * + `wc_customer_order_csv_export_customers_ftp_username` - FTP username
 * + `wc_customer_order_csv_export_customers_ftp_password` - FTP password
 * + `wc_customer_order_csv_export_customers_ftp_port` - FTP port
 * + `wc_customer_order_csv_export_customers_ftp_path` - FTP initial path
 * + `wc_customer_order_csv_export_customers_ftp_security` - type of FTP security, e.g. 'sftp'
 * + `wc_customer_order_csv_export_customers_passive_mode` - whether to enable passive mode for FTP connections
 * + `wc_customer_order_csv_export_customers_http_post_url` - the URL to POST exported CSV data to, when HTTP POST is enabled as a method
 * + `wc_customer_order_csv_export_version` the plugin version, set on install & upgrade
 *
 * ### Order Meta
 *
 * + `_wc_customer_order_csv_export_is_exported` - bool, indicates if an order has been auto-exported or not, set on post insert
 * + `_wc_customer_order_csv_export_customer_is_exported` - bool, indicates if the customer from an order has been auto-exported or not, set on post insert

 * ## Cron
 *
 * + `wc_customer_order_csv_export_orders_auto_export_interval` - custom interval for auto-export order action
 * + `wc_customer_order_csv_export_customers_auto_export_interval` - custom interval for auto-export customer action
 * + `wc_customer_order_csv_export_auto_export_orders` - custom hook for auto-exporting orders
 * + `wc_customer_order_csv_export_auto_export_customers` - custom hook for auto-exporting customers
 *
 */
class WC_Customer_Order_CSV_Export extends Framework\SV_WC_Plugin {


	/** plugin version number */
	const VERSION = '4.8.1';

	/** @var WC_Customer_Order_CSV_Export single instance of this plugin */
	protected static $instance;

	/** plugin id */
	const PLUGIN_ID = 'customer_order_csv_export';

	/** @var \WC_Customer_Order_CSV_Export_Admin instance */
	protected $admin;

	/** @var \WC_Customer_Order_CSV_Export_Compatibility instance */
	protected $compatibility;

	/** @var \WC_Customer_Order_CSV_Export_Formats instance */
	protected $formats;

	/** @var \WC_Customer_Order_CSV_Export_Methods instance */
	protected $methods;

	/** @var \WC_Customer_Order_CSV_Export_Cron instance */
	protected $cron;

	/** @var \WC_Customer_Order_CSV_Export_AJAX instance */
	protected $ajax;

	/** @var \SV_WP_Background_Job_Handler instance */
	protected $background_export;

	/** @var \SV_WP_Job_Batch_Handler instance */
	protected $batch_export;

	/** @var \WC_Customer_Order_CSV_Export_Download_Handler instance */
	protected $download_handler;

	/** @var \WC_Customer_Order_CSV_Export_Handler instance */
	protected $export_handler;

	/** @var array deprecated filter mapping, old => new **/
	protected $deprecated_filters = [
		'wc_customer_order_csv_export_admin_query_args'               => 'wc_customer_order_csv_export_query_args',
		'wc_customer_order_csv_export_admin_user_query_args'          => 'wc_customer_order_csv_export_user_query_args',
		// TODO: the following should probably be hard-deprecated, not mapped, since the filter signature
		// is different {IT 2016-06-29}
		'wc_customer_order_csv_export_auto_export_order_query_args'   => 'wc_customer_order_csv_export_query_args',
		'wc_customer_order_csv_export_column_mapper_sections'         => 'wc_customer_order_csv_export_custom_formats_admin_sections',
		'wc_customer_order_csv_export_column_mapping_fields'          => 'wc_customer_order_csv_export_field_mapping_fields',
		'wc_customer_order_csv_export_column_mapping_settings'        => 'wc_customer_order_csv_export_custom_format_settings',
		'wc_customer_order_csv_export_custom_format_builder_sections' => 'wc_customer_order_csv_export_custom_formats_admin_sections',
	];


	/**
	 * Setup main plugin class
	 *
	 * @since 3.0.0
	 * @return \WC_Customer_Order_CSV_Export
	 */
	public function __construct() {

		parent::__construct(
			self::PLUGIN_ID,
			self::VERSION,
			[
				'text_domain'        => 'woocommerce-customer-order-csv-export',
				'dependencies'       => [
					'php_extensions'     => [
						'mbstring'
					]
				]
			]
		);
	}


	/**
	 * Initializes the plugin.
	 *
	 * @internal
	 *
	 * @since 4.7.0
	 */
	public function init_plugin() {

		// required files
		$this->includes();

		// Set orders as not-exported when created
		add_action( 'wp_insert_post',  [ $this, 'mark_order_not_exported' ], 10, 2 );

		// Set users as not-exported when created
		add_action( 'user_register',  [ $this, 'mark_user_not_exported' ], 1 );

		// Admin
		if ( is_admin() ) {
			if ( ! is_ajax() ) {
				$this->admin_includes();
			} else {
				$this->ajax_includes();
			}
		}

		// Subscriptions support
		if ( $this->is_plugin_active( 'woocommerce-subscriptions.php' ) ) {

			if ( Framework\SV_WC_Plugin_Compatibility::is_wc_subscriptions_version_gte_2_0() ) {

				// Prevent that new subscription renewal orders from being automatically marked as 'exported'
				add_filter( 'wcs_subscription_meta',                 [ $this, 'subscriptions_remove_subscription_order_meta' ], 10, 3 );
				add_filter( 'wcs_upgrade_subscription_meta_to_copy', [ $this, 'subscriptions_remove_subscription_order_meta_during_upgrade' ] );
				add_filter( 'wcs_renewal_order_meta',                [ $this, 'subscriptions_remove_renewal_order_meta' ] );
			}
		}

		// Handle renamed filters
		foreach ( $this->deprecated_filters as $new_filter ) {

			// we need to pass all the args to the filter, but there's no way to tell apply_filters()
			// to pass them all to the function (why, WP, why?), so we'll need to use an arbitary
			// value which is great enough so that it covers all our arguments
			add_filter( $new_filter, [ $this, 'map_deprecated_filter' ], 10, 10 );
		}

		// clear scheduled events on deactivation
		register_deactivation_hook( $this->get_file(), [ $this->get_cron_instance(), 'clear_scheduled_export' ] );
	}


	/**
	 * Loads and initializes the plugin lifecycle handler.
	 *
	 * @since 4.7.0
	 */
	protected function init_lifecycle_handler() {

		require_once( $this->get_plugin_path() . '/includes/class-wc-customer-order-csv-export-lifecycle.php' );

		$this->lifecycle_handler = new WC_Customer_Order_CSV_Export_Lifecycle( $this );
	}


	/**
	 * Map a deprecated/renamed filter to a new one
	 *
	 * This method works by hooking into the new, renamed version of the filter
	 * and checking if any filters are hooked into the old hook. It then runs
	 * these filters and applies the data modifications in the new filter, and
	 * finally deprecates the filter using `_deprecated_function`.
	 *
	 * @since 4.0.0
	 * @return mixed
	 */
	public function map_deprecated_filter() {

		$args   = func_get_args();
		$data   = $args[0];
		$filter = current_filter();

		// check if there is a matching old filter for teh current filter
		if ( $old_filter = array_search( $filter, $this->deprecated_filters ) ) {

			// check if there are any filters added to the old filter
			if ( has_filter( $old_filter ) ) {

				// prepend old filter name to the args
				array_unshift( $args, $old_filter );

				// apply the filters attached to the old filter hook to $data
				$data = call_user_func_array( 'apply_filters', $args );

				_deprecated_function( 'The ' . $old_filter . ' filter', '4.0.0', $filter );
			}
		}

		return $data;
	}


	/**
	 * Set each new order as not exported. This is done because querying orders that have a specific meta key / value
	 * is much more reliable than querying orders that don't have a specific meta key / value AND prevents accidental
	 * export of a massive set of old orders on first run
	 *
	 * @since 3.0.0
	 * @param int $post_id new order ID
	 * @param object $post the post object
	 */
	public function mark_order_not_exported( $post_id, $post ) {

		if ( 'shop_order' === $post->post_type ) {

			// force unique, because oddly this can be invoked when changing the status of an existing order
			add_post_meta( $post_id, '_wc_customer_order_csv_export_is_exported', 0, true );
			add_post_meta( $post_id, '_wc_customer_order_csv_export_customer_is_exported', 0, true );
		}
	}


	/**
	 * Set each new user as not exported. This is done because querying users that have a specific meta key / value
	 * is much more reliable than querying users that don't have a specific meta key / value AND prevents accidental
	 * export of a massive set of old customers on first run
	 *
	 * @since 4.0.0
	 * @param int $user_id new user ID
	 * @param object $post the post object
	 */
	public function mark_user_not_exported( $user_id ) {

		add_user_meta( $user_id, '_wc_customer_order_csv_export_is_exported', 0, true );
	}


	/**
	 * Includes required classes
	 *
	 * @since 3.0.0
	 */
	public function includes() {

		// Background export must be loaded all the time, because
		// otherwise background jobs simply won't work
		require_once( $this->get_framework_path() . '/utilities/class-sv-wp-async-request.php' );
		require_once( $this->get_framework_path() . '/utilities/class-sv-wp-background-job-handler.php' );
		require_once( $this->get_framework_path() . '/utilities/class-sv-wp-job-batch-handler.php' );

		// export class
		require_once( $this->get_plugin_path() . '/includes/class-wc-customer-order-csv-export-export.php' );

		// handles data storage
		require_once( $this->get_plugin_path() . '/includes/data-stores/abstract-class-wc-customer-order-csv-export-data-store.php' );
		require_once( $this->get_plugin_path() . '/includes/data-stores/class-wc-customer-order-csv-export-data-store-factory.php' );

		// export functions
		require_once( $this->get_plugin_path() . '/includes/functions/wc-customer-order-csv-export-export-functions.php' );

		// handles exporting files in background
		$this->background_export = $this->load_class( '/includes/class-wc-customer-order-csv-export-background-export.php', 'WC_Customer_Order_CSV_Export_Background_Export' );

		require_once( $this->get_plugin_path() . '/includes/class-wc-customer-order-csv-export-batch-export-handler.php' );

		// handles exporting files in batches
		$this->batch_export = new WC_Customer_Order_CSV_Export_Batch_Export_Handler( $this->background_export, $this );

		// general interface for interacting with exports
		$this->export_handler = $this->load_class( '/includes/class-wc-customer-order-csv-export-handler.php', 'WC_Customer_Order_CSV_Export_Handler' );

		// compatibility for legacy export formats and other extensions
		$this->compatibility = $this->load_class( '/includes/class-wc-customer-order-csv-export-compatibility.php', 'WC_Customer_Order_CSV_Export_Compatibility' );

		// formats definitions
		$this->formats = $this->load_class( '/includes/class-wc-customer-order-csv-export-formats.php', 'WC_Customer_Order_CSV_Export_Formats' );

		// export methods
		$this->methods = $this->load_class( '/includes/class-wc-customer-order-csv-export-methods.php', 'WC_Customer_Order_CSV_Export_Methods' );

		// handles exported file downloads
		$this->download_handler = $this->load_class( '/includes/class-wc-customer-order-csv-export-download-handler.php', 'WC_Customer_Order_CSV_Export_Download_Handler' );

		// handles scheduling and execution of automatic export / upload
		$this->cron = $this->load_class( '/includes/class-wc-customer-order-csv-export-cron.php', 'WC_Customer_Order_CSV_Export_Cron' );

		require_once( $this->get_plugin_path() . '/includes/Export_Formats/Export_Format_Definition.php' );
		require_once( $this->get_plugin_path() . '/includes/Export_Formats/Orders_Export_Format_Definition.php' );
		require_once( $this->get_plugin_path() . '/includes/Export_Formats/Custom_Export_Format_Definition.php' );
		require_once( $this->get_plugin_path() . '/includes/Export_Formats/Custom_Orders_Export_Format_Definition.php' );

		require_once( $this->get_plugin_path() . '/includes/admin/Admin_Custom_Formats.php' );
	}


	/**
	 * Loads the Admin classes
	 *
	 * @since 3.0.0
	 */
	public function admin_includes() {

		// loads the admin settings page and adds functionality to the order admin
		$this->admin = $this->load_class( '/includes/admin/class-wc-customer-order-csv-export-admin.php', 'WC_Customer_Order_CSV_Export_Admin' );

		// message handler
		$this->admin->message_handler = $this->get_message_handler();
	}


	/**
	 * Loads the AJAX classes
	 *
	 * @since 4.0.0
	 */
	public function ajax_includes() {

		$this->ajax = $this->load_class( '/includes/class-wc-customer-order-csv-export-ajax.php', 'WC_Customer_Order_CSV_Export_AJAX' );
	}


	/**
	 * Return admin class instance
	 *
	 * @since 3.12.0
	 * @return \WC_Customer_Order_CSV_Export_Admin
	 */
	public function get_admin_instance() {
		return $this->admin;
	}


	/**
	 * Return compatibility class instance
	 *
	 * @since 3.12.0
	 * @return \WC_Customer_Order_CSV_Export_Compatibility
	 */
	public function get_compatibility_instance() {
		return $this->compatibility;
	}


	/**
	 * Return formats class instance
	 *
	 * @since 4.0.0
	 * @return \WC_Customer_Order_CSV_Export_Formats
	 */
	public function get_formats_instance() {
		return $this->formats;
	}


	/**
	 * Return methods class instance
	 *
	 * @since 4.0.0
	 * @return \WC_Customer_Order_CSV_Export_Methods
	 */
	public function get_methods_instance() {
		return $this->methods;
	}


	/**
	 * Return cron class instance
	 *
	 * @since 3.12.0
	 * @return \WC_Customer_Order_CSV_Export_Cron
	 */
	public function get_cron_instance() {
		return $this->cron;
	}


	/**
	 * Return ajax class instance
	 *
	 * @since 4.0.0
	 * @return \WC_Customer_Order_CSV_Export_AJAX
	 */
	public function get_ajax_instance() {
		return $this->ajax;
	}


	/**
	 * Return background export class instance
	 *
	 * @since 4.0.0
	 * @return \SV_WP_Background_Job_Handler
	 */
	public function get_background_export_instance() {
		return $this->background_export;
	}


	/**
	 * Return batch export class instance
	 *
	 * @since 4.0.0
	 * @return \SV_WP_Job_Batch_Handler
	 */
	public function get_batch_export_instance() {
		return $this->batch_export;
	}


	/**
	 * Return download handler class instance
	 *
	 * @since 4.0.0
	 * @return \WC_Customer_Order_CSV_Export_Download_Handler
	 */
	public function get_download_handler_instance() {
		return $this->download_handler;
	}


	/**
	 * Return export handler class instance
	 *
	 * @since 4.0.0
	 * @return \WC_Customer_Order_CSV_Export_Handler
	 */
	public function get_export_handler_instance() {
		return $this->export_handler;
	}


	/** Admin Methods ******************************************************/


	/**
	 * Render a notice for the user to select their desired export format
	 *
	 * @since 3.4.0
	 * @see Framework\SV_WC_Plugin::add_admin_notices()
	 */
	public function add_admin_notices() {
		global $wpdb;

		// show any dependency notices
		parent::add_admin_notices();

		// add notice for selecting export format
		$this->get_admin_notice_handler()->add_admin_notice(
		/* translators: Placeholders: %1$s - opening <a> tag, %2$s - closing </a> tag */
			sprintf( __( 'Thanks for installing the Customer/Order CSV Export plugin! To get started, please %1$sset your export format%2$s. ', 'woocommerce-customer-order-csv-export' ), '<a href="' . $this->get_settings_url() . '">', '</a>' ),
			'export-format-notice',
			[ 'always_show_on_settings' => false, 'notice_class' => 'updated' ]
		);

		if ( $this->is_plugin_settings() ) {

			$loopback_enabled = $this->get_background_export_instance()->test_connection();

			// add notice for failing loopback connections
			if ( ! $loopback_enabled ) {

				$message = sprintf(
					/* translators: Placeholders: %1$s - <strong>; %2$s - </strong>; %3$s, %5$s - <a> tags; %4$s - </a> tag */
					__( '%1$sAutomated Exports%2$s are currently unavailable because your site does not support background processing. To use automated exports, please ask your hosting company to ensure your server has %3$sloopback connections%4$s enabled, or switch to a %5$srecommended hosting provider%4$s.', 'woocommerce-customer-order-csv-export' ),
					'<strong>',
					'</strong>',
					'<a href="https://docs.woocommerce.com/document/ordercustomer-csv-export/#faq-loopback" target="_blank">',
					'</a>',
					'<a href="https://www.skyverge.com/upgrading-php-versions/#recommended-hosts" target="_blank">'
				);

				// check $_POST to see if we've updated settings, but batch processing isn't included (meaning it's off)
				if ( ! $this->is_batch_processing_enabled() || ( isset( $_POST['wc_customer_order_csv_export_orders_format'] ) && ! isset( $_POST['wc_customer_order_csv_export_enable_batch_processing'] ) ) ) {
					$message .= ' ' . sprintf(
						/* translators: Placeholders: %1$s - <strong>; %2$s - </strong> */
						__( 'In the meantime, you can process manual exports by enabling the %1$sBatch Processing%2$s setting.', 'woocommerce-customer-order-csv-export' ),
						'<strong>', '</strong>'
					);
				}

				$this->get_admin_notice_handler()->add_admin_notice(
					$message,
					'export-loopback-notice',
					[ 'notice_class' => 'error' ]
				);
			}

			// add notice when batch processing blocks automatic exporting
			if ( $loopback_enabled && $this->is_batch_processing_enabled() ) {

				$message = sprintf(
					/* translators: Placeholders: %1$s - <strong>; %2$s - </strong>; %3$s, %5$s - <a> tags; %4$s - </a> tag */
					__( '%1$sAutomated Exports%2$s are currently unavailable because batch processing is enabled. To use automated exports, please disable batch processing and ensure your server has %3$sloopback connections%4$s enabled.', 'woocommerce-customer-order-csv-export' ),
					'<strong>',
					'</strong>',
					'<a href="https://docs.woocommerce.com/document/ordercustomer-csv-export/#faq-loopback" target="_blank">',
					'</a>'
				);

				$this->get_admin_notice_handler()->add_admin_notice(
					$message,
					'export-no-automatic-notice',
					[ 'notice_class' => 'error' ]
				);
			}
		}

		// add notice for mysqli requirement
		if ( ( $this->is_export_page() || $this->is_export_list_page() ) && ! $wpdb->dbh instanceof mysqli ) {

			$message = sprintf(
			/* translators: Placeholders: %1$s - <a> tag; %2$s - </a> tag */
				__( 'Heads up! Your exports may consume more memory and take longer than usual unless mysqli is installed and enabled on your site. %1$sLearn More%2$s', 'woocommerce-customer-order-csv-export' ),
				'<a href="https://docs.woocommerce.com/document/ordercustomer-csv-export/#mysqli-streaming" target="_blank">',
				'</a>'
			);

			$this->get_admin_notice_handler()->add_admin_notice(
				$message,
				'mysqli-not-found-notice',
				[ 'dismissible' => false, 'notice_class' => 'error' ]
			);
		}
	}


	/** Subscriptions compatibility *******************************************/


	/**
	 * Don't copy _wc_customer_order_csv_export_is_exported meta
	 * to renewal orders from the WC_Subscription object.
	 * Generally the subscription object should not have any order-specific meta.
	 *
	 * The WC_Subscription object shouldn't have this meta set, but up until 3.10.3
	 * this plugin didn't exclude its meta from copying to WC_Subscription the
	 * object during the upgrade to Subscriptions 2.0.
	 *
	 * @since 3.10.3
	 * @param array $order_meta order meta to copy
	 * @return array
	 */
	public function subscriptions_remove_renewal_order_meta( $order_meta ) {

		foreach ( $order_meta as $index => $meta ) {

			if ( '_wc_customer_order_csv_export_is_exported' === $meta['meta_key'] ) {
				unset( $order_meta[ $index ] );
			}
		}

		return $order_meta;
	}


	/**
	 * Remove _wc_customer_order_csv_export_is_exported meta
	 * when creating a subscription object from an order at checkout.
	 *
	 * @since 3.10.3
	 * @param array $order_meta meta on order
	 * @param WC_Subscription $to_order order meta is being copied to
	 * @param WC_Order $from_order order meta is being copied from
	 * @return array
	 */
	public function subscriptions_remove_subscription_order_meta( $order_meta, $to_order, $from_order ) {

		// only when copying from an order to a subscription
		if ( $to_order instanceof WC_Subscription && $from_order instanceof WC_Order ) {

			foreach ( $order_meta as $index => $meta ) {

				if ( '_wc_customer_order_csv_export_is_exported' === $meta['meta_key'] ) {
					unset( $order_meta[ $index ] );
				}
			}
		}

		return $order_meta;
	}


	/**
	 * Don't copy over _wc_customer_order_csv_export_is_exported meta
	 * during the upgrade from WooCommerce Subscriptions v1.5 to v2.0
	 *
	 * @since 3.10.3
	 * @param array $order_meta meta to copy
	 * @return array
	 */
	public function subscriptions_remove_subscription_order_meta_during_upgrade( $order_meta ) {

		if ( isset( $order_meta['_wc_customer_order_csv_export_is_exported'] ) ) {
			unset( $order_meta['_wc_customer_order_csv_export_is_exported'] );
		}

		return $order_meta;
	}


	/** Helper Methods ******************************************************/


	/**
	 * Main Customer/Order CSV Export Instance, ensures only one instance is/can be loaded
	 *
	 * @since 3.9.0
	 * @see wc_customer_order_csv_export()
	 * @return WC_Customer_Order_CSV_Export
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}


	/**
	 * Returns the plugin name, localized
	 *
	 * @since 3.0.0
	 * @see Framework\SV_WC_Plugin::get_plugin_name()
	 * @return string the plugin name
	 */
	public function get_plugin_name() {

		return __( 'WooCommerce Customer/Order CSV Export', 'woocommerce-customer-order-csv-export' );
	}


	/**
	 * Returns __FILE__
	 *
	 * @since 3.0.0
	 * @see Framework\SV_WC_Plugin::get_file()
	 * @return string the full path and filename of the plugin file
	 */
	protected function get_file() {

		return __FILE__;
	}


	/**
	 * Gets the plugin documentation url, which for Customer/Order CSV Export is non-standard
	 *
	 * @since 3.0.0
	 * @see Framework\SV_WC_Plugin::get_documentation_url()
	 * @return string documentation URL
	 */
	public function get_documentation_url() {

		return 'http://docs.woocommerce.com/document/ordercustomer-csv-exporter/';
	}


	/**
	 * Gets the plugin support URL
	 *
	 * @since 3.10.0
	 * @see Framework\SV_WC_Plugin::get_support_url()
	 * @return string
	 */
	public function get_support_url() {
		return 'https://woocommerce.com/my-account/marketplace-ticket-form/';
	}


	/**
	 * Gets the URL to the settings page
	 *
	 * @since 3.0.0
	 * @see Framework\SV_WC_Plugin::is_plugin_settings()
	 * @param null|string $_ unused
	 * @return string URL to the settings page
	 */
	public function get_settings_url( $_ = null ) {

		return admin_url( 'admin.php?page=wc_customer_order_csv_export&tab=settings' );
	}


	/**
	 * Determines if the current page is the plugin settings page.*
	 *
	 * @since 4.4.0
	 *
	 * @return bool
	 */
	public function is_plugin_settings() {

		return is_admin() && 'wc_customer_order_csv_export' === Framework\SV_WC_Helper::get_request( 'page' ) && 'settings' === Framework\SV_WC_Helper::get_request( 'tab' );
	}


	/**
	 * Determines if the current page is the export list page.
	 *
	 * @since 4.5.0
	 *
	 * @return bool
	 */
	public function is_export_page() {

		return is_admin() && 'wc_customer_order_csv_export' === Framework\SV_WC_Helper::get_request( 'page' ) && 'export_list' === Framework\SV_WC_Helper::get_request( 'tab' );
	}


	/**
	 * Determines if the current page is the new export page.
	 *
	 * @since 4.5.0
	 *
	 * @return bool
	 */
	public function is_export_list_page() {

		return is_admin() && 'wc_customer_order_csv_export' === Framework\SV_WC_Helper::get_request( 'page' ) && 'export' === Framework\SV_WC_Helper::get_request( 'tab' );
	}


	/**
	 * Returns conditional dependencies based on the FTP security selected
	 *
	 * @since 3.0.0
	 * @see Framework\SV_WC_Plugin::get_dependencies()
	 * @return array of dependencies
	 */
	protected function get_dependencies() {

		// check if FTP is one of the chosen export methods
		if ( ! in_array( 'ftp', $this->get_auto_export_methods(), true ) ) {
			return [];
		}

		$ftp_securities = $this->get_auto_export_ftp_securities();
		$dependencies   = [];

		if ( in_array( 'sftp', $ftp_securities, true ) ) {

			$dependencies[] = 'ssh2';
		}

		if ( in_array( 'ftp_ssl', $ftp_securities, true ) ) {

			$dependencies[] = 'curl';
		}

		if ( in_array( 'ftps', $ftp_securities, true ) ) {

			$dependencies[] = 'ftp';
			$dependencies[] = 'openssl';
		}

		return $dependencies;
	}


	/**
	 * Returns conditional function dependencies based on the FTP security selected
	 *
	 * @since 3.1.0
	 * @see Framework\SV_WC_Plugin::get_function_dependencies()
	 * @return array of dependencies
	 */
	protected function get_function_dependencies() {

		// check if FTP is one of the chosen export methods
		if ( ! in_array( 'ftp', $this->get_auto_export_methods(), true ) ) {
			return [];
		}

		$ftp_securities = $this->get_auto_export_ftp_securities();

		if ( in_array( 'ftps', $ftp_securities, true ) ) {

			return [ 'ftp_ssl_connect' ];
		}

		return [];
	}


	/**
	 * Get auto export methods used by export types
	 *
	 * @since 4.0.0
	 * @return array
	 */
	private function get_auto_export_methods() {

		$export_types   = [ 'customers', 'orders' ];
		$export_methods = [];

		foreach ( $export_types as $export_type ) {
			$export_methods[] = get_option( 'wc_customer_order_csv_export_' . $export_type . '_auto_export_method' );
		}

		return $export_methods;
	}


	/**
	 * Get auto export methods used by export types
	 *
	 * @since 4.0.0
	 * @return array
	 */
	private function get_auto_export_ftp_securities() {

		$export_types = [ 'customers', 'orders' ];
		$securities   = [];

		foreach ( $export_types as $export_type ) {
			$securities[] = get_option( 'wc_customer_order_csv_export_' . $export_type . '_ftp_security' );
		}

		return $securities;
	}


	/**
	 * Return deprecated/removed hooks.
	 *
	 * @since 4.0.0
	 * @see Framework\SV_WC_Plugin::get_deprecated_hooks()
	 * @return array
	 */
	protected function get_deprecated_hooks() {

		return [
			'wc_customer_order_csv_export_generated_csv' => [
				'version'     => '4.0.0',
				'replacement' => 'wc_customer_order_csv_export_generated_csv_row'
			],
		];
	}


	/**
	 * Determines if batch processing is enabled.
	 *
	 * @since 4.4.0
	 *
	 * @return bool
	 */
	public function is_batch_processing_enabled() {

		// account for changes while saving settings
		if ( isset( $_POST['wc_customer_order_csv_export_enable_batch_processing'] ) ) {
			return (bool) $_POST['wc_customer_order_csv_export_enable_batch_processing'];
		}

		return 'yes' === get_option( 'wc_customer_order_csv_export_enable_batch_processing', 'no' );
	}


	/**
	 * Determines if the option to export coupons is enabled.
	 *
	 * // TODO: Remove by 08-2020 or 5.0 {JB 2019-08-08}
	 *
	 * @since 4.6.0
	 * @deprecated since 4.8.0
	 *
	 * @return bool
	 */
	public function is_coupon_export_enabled() {

		_deprecated_function(
			'wc_customer_order_csv_export()->is_coupon_export_enabled()',
			'4.8.0'
		);

		return true;
	}


	/** Lifecycle Methods ******************************************************/


	/**
	 * Installs default settings.
	 *
	 * @see \SV_WC_Plugin::install()
	 *
	 * @since 3.0.0
	 */
	protected function install() {

		require_once( $this->get_plugin_path() . '/includes/class-wc-customer-order-csv-export-lifecycle.php' );

		WC_Customer_Order_CSV_Export_Lifecycle::install();
	}


	/**
	 * Upgrades to $installed_version.
	 *
	 * @see \SV_WC_Plugin::upgrade()
	 *
	 * @since 3.0.4
	 */
	protected function upgrade( $installed_version ) {

		require_once( $this->get_plugin_path() . '/includes/class-wc-customer-order-csv-export-lifecycle.php' );

		WC_Customer_Order_CSV_Export_Lifecycle::upgrade( $installed_version );
	}


} // end \WC_Customer_Order_CSV_Export class


/**
 * Returns the One True Instance of Customer/Order CSV Export
 *
 * @since 3.9.0
 * @return WC_Customer_Order_CSV_Export instance of Customer/Order CSV Export main class
 */
function wc_customer_order_csv_export() {
	return WC_Customer_Order_CSV_Export::instance();
}
