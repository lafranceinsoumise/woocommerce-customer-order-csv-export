<?php
/**
 * Plugin Name: WooCommerce Customer/Order CSV Export
 * Plugin URI: http://www.woothemes.com/products/ordercustomer-csv-export/
 * Description: Easily download customers & orders in CSV format and automatically export FTP or HTTP POST on a recurring schedule
 * Author: WooThemes / SkyVerge
 * Author URI: http://www.woothemes.com
 * Version: 4.1.4
 * Text Domain: woocommerce-customer-order-csv-export
 * Domain Path: /i18n/languages/
 *
 * Copyright: (c) 2012-2016 SkyVerge (info@skyverge.com)
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package   WC-Customer-Order-CSV-Export
 * @author    SkyVerge
 * @category  Export
 * @copyright Copyright (c) 2012-2016, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

defined( 'ABSPATH' ) or exit;

// Required functions
if ( ! function_exists( 'woothemes_queue_update' ) ) {
	require_once( plugin_dir_path( __FILE__ ) . 'woo-includes/woo-functions.php' );
}

// Plugin updates
woothemes_queue_update( plugin_basename( __FILE__ ), '914de15813a903c767b55445608bf290', '18652' );

// WC active check
if ( ! is_woocommerce_active() ) {
	return;
}

// Required library class
if ( ! class_exists( 'SV_WC_Framework_Bootstrap' ) ) {
	require_once( plugin_dir_path( __FILE__ ) . 'lib/skyverge/woocommerce/class-sv-wc-framework-bootstrap.php' );
}

SV_WC_Framework_Bootstrap::instance()->register_plugin( '4.5.0', __( 'WooCommerce Customer/Order CSV Export', 'woocommerce-customer-order-csv-export' ), __FILE__, 'init_woocommerce_customer_order_csv_export', array(
	'minimum_wc_version'   => '2.4.13',
	'minimum_wp_version'   => '4.4',
	'backwards_compatible' => '4.4.0',
) );

function init_woocommerce_customer_order_csv_export() {

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
class WC_Customer_Order_CSV_Export extends SV_WC_Plugin {

	/** plugin version number */
	const VERSION = '4.1.4';

	/** @var WC_Customer_Order_CSV_Export single instance of this plugin */
	protected static $instance;

	/** plugin id */
	const PLUGIN_ID = 'customer_order_csv_export';

	/** plugin text domain, DEPRECATED as of 3.11.0 */
	const TEXT_DOMAIN = 'woocommerce-customer-order-csv-export';

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

	/** @var \WC_Customer_Order_CSV_Export_Background_Export instance */
	protected $background_export;

	/** @var \WC_Customer_Order_CSV_Export_Download_Handler instance */
	protected $download_handler;

	/** @var \WC_Customer_Order_CSV_Export_Export_Handler instance */
	protected $export_handler;

	/** @var array deprecated filter mapping, old => new **/
	protected $deprecated_filters = array(
		'wc_customer_order_csv_export_admin_query_args'             => 'wc_customer_order_csv_export_query_args',
		'wc_customer_order_csv_export_admin_user_query_args'        => 'wc_customer_order_csv_export_user_query_args',
		// TODO: the following should probably be hard-deprecated, not mapped, since the filter signature
		// is different {IT 2016-06-29}
		'wc_customer_order_csv_export_auto_export_order_query_args' => 'wc_customer_order_csv_export_query_args',
		'wc_customer_order_csv_export_column_mapper_sections'       => 'wc_customer_order_csv_export_custom_format_builder_sections',
		'wc_customer_order_csv_export_column_mapping_fields'        => 'wc_customer_order_csv_export_field_mapping_fields',
		'wc_customer_order_csv_export_column_mapping_settings'      => 'wc_customer_order_csv_export_custom_format_settings',
	);


	/**
	 * Setup main plugin class
	 *
	 * @since 3.0.0
	 * @return \WC_Customer_Order_CSV_Export
	 */
	public function __construct() {

		parent::__construct( self::PLUGIN_ID, self::VERSION );

		// required files
		$this->includes();

		// Set orders as not-exported when created
		add_action( 'wp_insert_post',  array( $this, 'mark_order_not_exported' ), 10, 2 );

		// Set users as not-exported when created
		add_action( 'user_register',  array( $this, 'mark_user_not_exported' ), 1 );

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

			if ( SV_WC_Plugin_Compatibility::is_wc_subscriptions_version_gte_2_0() ) {

				// Prevent that new subscription renewal orders from being automatically marked as 'exported'
				add_filter( 'wcs_subscription_meta',                 array( $this, 'subscriptions_remove_subscription_order_meta' ), 10, 3 );
				add_filter( 'wcs_upgrade_subscription_meta_to_copy', array( $this, 'subscriptions_remove_subscription_order_meta_during_upgrade' ) );
				add_filter( 'wcs_renewal_order_meta',                array( $this, 'subscriptions_remove_renewal_order_meta' ) );
			}
		}

		// Handle renamed filters
		foreach ( $this->deprecated_filters as $new_filter ) {

			// we need to pass all the args to the filter, but there's no way to tell apply_filters()
			// to pass them all to the function (why, WP, why?), so we'll need to use an arbitary
			// value which is great enough so that it covers all our arguments
			add_filter( $new_filter, array( $this, 'map_deprecated_filter' ), 10, 10 );
		}

		// clear scheduled events on deactivation
		register_deactivation_hook( $this->get_file(), array( $this->get_cron_instance(), 'clear_scheduled_export' ) );
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

		// handles exporting files in background
		$this->background_export = $this->load_class( '/includes/class-wc-customer-order-csv-export-background-export.php', 'WC_Customer_Order_CSV_Export_Background_Export' );

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
	 * @return \WC_Customer_Order_CSV_Export_Background_Export
	 */
	public function get_background_export_instance() {
		return $this->background_export;
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


	/**
	 * Returns the admin notice handler instance
	 *
	 * TODO: remove this when the method gets fixed in framework {IT 2016-09-02}
	 *
	 * @since 4.0.5
	 */
	public function get_admin_notice_handler() {

		require_once( $this->get_framework_path() . '/class-sv-wc-admin-notice-handler.php' );

		return parent::get_admin_notice_handler();
	}


	/**
	 * Backwards compat for changing the visibility of some class instances.
	 *
	 * @TODO Remove this as part of WC 2.7 compat {IT 2016-05-19}
	 *
	 * @since 3.12.0
	 * @param string $name
	 */
	public function __get( $name ) {

		switch ( $name ) {

			case 'admin':

				/* @deprecated since 3.12.0 */
				_deprecated_function( 'wc_customer_order_csv_export()->admin', '3.12.0', 'wc_customer_order_csv_export()->get_admin_instance()' );
				return $this->get_admin_instance();

			case 'compatibility':

				/* @deprecated since 3.12.0 */
				_deprecated_function( 'wc_customer_order_csv_export()->compatibility', '3.12.0', 'wc_customer_order_csv_export()->get_compatibility_instance()' );
				return $this->get_compatibility_instance();

			case 'cron':

				/* @deprecated since 3.12.0 */
				_deprecated_function( 'wc_customer_order_csv_export()->cron', '3.12.0', 'wc_customer_order_csv_export()->get_cron_instance()' );
				return $this->get_cron_instance();
		}

		// you're probably doing it wrong
		trigger_error( 'Call to undefined property ' . __CLASS__ . '::' . $name, E_USER_ERROR );

		return null;
	}


	/**
	 * Load plugin text domain.
	 *
	 * @since 3.0.0
	 * @see SV_WC_Plugin::load_translation()
	 */
	public function load_translation() {

		load_plugin_textdomain( 'woocommerce-customer-order-csv-export', false, dirname( plugin_basename( $this->get_file() ) ) . '/i18n/languages' );
	}


	/** Admin Methods ******************************************************/


	/**
	 * Render a notice for the user to select their desired export format
	 *
	 * @since 3.4.0
	 * @see SV_WC_Plugin::add_admin_notices()
	 */
	public function add_admin_notices() {

		// show any dependency notices
		parent::add_admin_notices();

		// add notice for selecting export format
		$this->get_admin_notice_handler()->add_admin_notice(
			/* translators: Placeholders: %1$s - opening <a> tag, %2$s - closing </a> tag */
			sprintf( __( 'Thanks for installing the Customer/Order CSV Export plugin! To get started, please %1$sset your export format%2$s. ', 'woocommerce-customer-order-csv-export' ), '<a href="' . $this->get_settings_url() . '">', '</a>' ),
			'export-format-notice',
			array( 'always_show_on_settings' => false, 'notice_class' => 'updated' )
		);
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
	 * @see SV_WC_Plugin::get_plugin_name()
	 * @return string the plugin name
	 */
	public function get_plugin_name() {

		return __( 'WooCommerce Customer/Order CSV Export', 'woocommerce-customer-order-csv-export' );
	}


	/**
	 * Returns __FILE__
	 *
	 * @since 3.0.0
	 * @see SV_WC_Plugin::get_file()
	 * @return string the full path and filename of the plugin file
	 */
	protected function get_file() {

		return __FILE__;
	}


	/**
	 * Gets the plugin documentation url, which for Customer/Order CSV Export is non-standard
	 *
	 * @since 3.0.0
	 * @see SV_WC_Plugin::get_documentation_url()
	 * @return string documentation URL
	 */
	public function get_documentation_url() {

		return 'http://docs.woothemes.com/document/ordercustomer-csv-exporter/';
	}


	/**
	 * Gets the plugin support URL
	 *
	 * @since 3.10.0
	 * @see SV_WC_Plugin::get_support_url()
	 * @return string
	 */
	public function get_support_url() {
		return 'http://support.woothemes.com/';
	}


	/**
	 * Gets the URL to the settings page
	 *
	 * @since 3.0.0
	 * @see SV_WC_Plugin::is_plugin_settings()
	 * @param string $_ unused
	 * @return string URL to the settings page
	 */
	public function get_settings_url( $_ = '' ) {

		return admin_url( 'admin.php?page=wc_customer_order_csv_export&tab=settings' );
	}


	/**
	 * Returns conditional dependencies based on the FTP security selected
	 *
	 * @since 3.0.0
	 * @see SV_WC_Plugin::get_dependencies()
	 * @return array of dependencies
	 */
	protected function get_dependencies() {

		// check if FTP is one of the chosen export methods
		if ( ! in_array( 'ftp', $this->get_auto_export_methods(), true ) ) {
			return array();
		}

		$ftp_securities = $this->get_auto_export_ftp_securities();
		$dependencies   = array();

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
	 * @see SV_WC_Plugin::get_function_dependencies()
	 * @return array of dependencies
	 */
	protected function get_function_dependencies() {

		// check if FTP is one of the chosen export methods
		if ( ! in_array( 'ftp', $this->get_auto_export_methods(), true ) ) {
			return array();
		}

		$ftp_securities = $this->get_auto_export_ftp_securities();

		if ( in_array( 'ftps', $ftp_securities, true ) ) {

			return array( 'ftp_ssl_connect' );
		}

		return array();
	}


	/**
	 * Get auto export methods used by export types
	 *
	 * @since 4.0.0
	 * @return array
	 */
	private function get_auto_export_methods() {

		$export_types   = array( 'customers', 'orders' );
		$export_methods = array();

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

		$export_types = array( 'customers', 'orders' );
		$securities   = array();

		foreach ( $export_types as $export_type ) {
			$securities[] = get_option( 'wc_customer_order_csv_export_' . $export_type . '_ftp_security' );
		}

		return $securities;
	}


	/**
	 * Return deprecated/removed hooks.
	 *
	 * @since 4.0.0
	 * @see SV_WC_Plugin::get_deprecated_hooks()
	 * @return array
	 */
	protected function get_deprecated_hooks() {

		return array(
			'wc_customer_order_csv_export_generated_csv' => array(
				'version'     => '4.0.0',
				'replacement' => 'wc_customer_order_csv_export_generated_csv_row'
			),
		);
	}


	/** Lifecycle Methods ******************************************************/


	/**
	 * Install default settings
	 *
	 * @since 3.0.0
	 * @see SV_WC_Plugin::install()
	 */
	protected function install() {

		// install default settings
		require_once( $this->get_plugin_path() . '/includes/admin/class-wc-customer-order-csv-export-admin-settings.php' );

		foreach ( WC_Customer_Order_CSV_Export_Admin_Settings::get_settings() as $section => $settings ) {

			foreach ( $settings as $setting ) {

				if ( isset( $setting['default'] ) ) {

					update_option( $setting['id'], $setting['default'] );
				}
			}
		}

		// install default custom format builder settings
		require_once( $this->get_plugin_path() . '/includes/admin/class-wc-customer-order-csv-export-admin-custom-format-builder.php' );

		foreach ( WC_Customer_Order_CSV_Export_Admin_Custom_Format_Builder::get_settings() as $section => $settings ) {

			foreach ( $settings as $setting ) {

				if ( isset( $setting['default'] ) ) {

					update_option( $setting['id'], $setting['default'] );
				}
			}
		}

		self::create_files();
	}


	/**
	 * Create files/directories
	 *
	 * Based on WC_Install::create_files()
	 *
	 * @since 3.12-0-1
	 */
	private static function create_files() {

		// Install files and folders for exported files and prevent hotlinking
		$upload_dir      = wp_upload_dir();
		$download_method = get_option( 'woocommerce_file_download_method', 'force' );

		$files = array(
			array(
				'base'    => $upload_dir['basedir'] . '/csv_exports',
				'file'    => 'index.html',
				'content' => ''
			),
		);

		if ( 'redirect' !== $download_method ) {
			$files[] = array(
				'base'    => $upload_dir['basedir'] . '/csv_exports',
				'file'    => '.htaccess',
				'content' => 'deny from all'
			);
		}

		foreach ( $files as $file ) {

			if ( wp_mkdir_p( $file['base'] ) && ! file_exists( trailingslashit( $file['base'] ) . $file['file'] ) ) {

				if ( $file_handle = @fopen( trailingslashit( $file['base'] ) . $file['file'], 'w' ) ) {

					fwrite( $file_handle, $file['content'] );
					fclose( $file_handle );
				}
			}
		}
	}


	/**
	 * Upgrade to $installed_version
	 *
	 * @since 3.0.4
	 * @see SV_WC_Plugin::upgrade()
	 */
	protected function upgrade( $installed_version ) {

		// upgrade to 3.0.4
		if ( version_compare( $installed_version, '3.0.4', '<' ) ) {

			// wc_customer_order_csv_export_passive_mode > wc_customer_order_csv_export_ftp_passive_mode
			update_option( 'wc_customer_order_csv_export_ftp_passive_mode', get_option( 'wc_customer_order_csv_export_passive_mode' ) );
			delete_option( 'wc_customer_order_csv_export_passive_mode' );
		}

		// upgrate to 3.4.0
		if ( version_compare( $installed_version, '3.4.0', '<' ) ) {

			// update order statuses for 2.2+
			$order_status_options = array( 'wc_customer_order_csv_export_statuses', 'wc_customer_order_csv_export_auto_export_statuses' );

			foreach ( $order_status_options as $option ) {

				$order_statuses     = (array) get_option( $option );
				$new_order_statuses = array();

				foreach ( $order_statuses as $status ) {
					$new_order_statuses[] = 'wc-' . $status;
				}

				update_option( $option, $new_order_statuses );
			}
		}

		// upgrade to 3.12.0
		if ( version_compare( $installed_version, '3.12.0', '<' ) ) {

			if ( 'import' === get_option( 'wc_customer_order_csv_export_order_format' ) ) {
				update_option( 'wc_customer_order_csv_export_order_format', 'legacy_import' );
			}
		}

		// upgrade to 4.0.0
		if ( version_compare( $installed_version, '4.0.0', '<' ) ) {

			// install defaults for customer auto-export settings, this must be done before
			// updating renamed options, otherwise defaults will override the previously set options
			require_once( $this->get_plugin_path() . '/includes/admin/class-wc-customer-order-csv-export-admin-settings.php' );

			foreach ( WC_Customer_Order_CSV_Export_Admin_Settings::get_settings( 'customers' ) as $setting ) {

				if ( isset( $setting['default'] ) ) {

					update_option( $setting['id'], $setting['default'] );
				}
			}

			// set up csv exports folder
			self::create_files();

			// install defaults for new settings
			update_option( 'wc_customer_order_csv_export_orders_add_note', 'yes' );
			update_option( 'wc_customer_order_csv_export_orders_auto_export_trigger', 'schedule' );

			// rename settings
			$renamed_options = array(
				'wc_customer_order_csv_export_order_format'           => 'wc_customer_order_csv_export_orders_format',
				'wc_customer_order_csv_export_order_filename'         => 'wc_customer_order_csv_export_orders_filename',
				'wc_customer_order_csv_export_customer_format'        => 'wc_customer_order_csv_export_customers_format',
				'wc_customer_order_csv_export_customer_filename'      => 'wc_customer_order_csv_export_customers_filename',
				'wc_customer_order_csv_export_auto_export_method'     => 'wc_customer_order_csv_export_orders_auto_export_method',
				'wc_customer_order_csv_export_auto_export_start_time' => 'wc_customer_order_csv_export_orders_auto_export_start_time',
				'wc_customer_order_csv_export_auto_export_interval'   => 'wc_customer_order_csv_export_orders_auto_export_interval',
				'wc_customer_order_csv_export_auto_export_statuses'   => 'wc_customer_order_csv_export_orders_auto_export_statuses',
				'wc_customer_order_csv_export_ftp_server'             => 'wc_customer_order_csv_export_orders_ftp_server',
				'wc_customer_order_csv_export_ftp_username'           => 'wc_customer_order_csv_export_orders_ftp_username',
				'wc_customer_order_csv_export_ftp_password'           => 'wc_customer_order_csv_export_orders_ftp_password',
				'wc_customer_order_csv_export_ftp_port'               => 'wc_customer_order_csv_export_orders_ftp_port',
				'wc_customer_order_csv_export_ftp_path'               => 'wc_customer_order_csv_export_orders_ftp_path',
				'wc_customer_order_csv_export_ftp_security'           => 'wc_customer_order_csv_export_orders_ftp_security',
				'wc_customer_order_csv_export_ftp_passive_mode'       => 'wc_customer_order_csv_export_orders_ftp_passive_mode',
				'wc_customer_order_csv_export_http_post_url'          => 'wc_customer_order_csv_export_orders_http_post_url',
				'wc_customer_order_csv_export_email_recipients'       => 'wc_customer_order_csv_export_orders_email_recipients',
				'wc_customer_order_csv_export_email_subject'          => 'wc_customer_order_csv_export_orders_email_subject',
			);

			foreach ( $renamed_options as $old => $new ) {

				update_option( $new, get_option( $old ) );
				delete_option( $old );
			}

			// install default custom field mapping settings
			require_once( $this->get_plugin_path() . '/includes/admin/class-wc-customer-order-csv-export-admin-custom-format-builder.php' );

			foreach ( WC_Customer_Order_CSV_Export_Admin_Custom_Format_Builder::get_settings() as $section => $settings ) {

				foreach ( $settings as $setting ) {

					if ( isset( $setting['default'] ) ) {

						update_option( $setting['id'], $setting['default'] );
					}
				}
			}

			// maintain backwards compatibility with previous `default` and
			// `default_one_row_per_item` formats for tjose who use it by creating a custom
			// format based on the previous version
			$orders_format = get_option( 'wc_customer_order_csv_export_orders_format' );

			if ( in_array( $orders_format, array( 'default', 'default_one_row_per_item' ), true ) ) {

				$custom_format = $this->get_formats_instance()->get_format( 'orders', $orders_format );

				// keep order_number backwards-compatible
				$custom_format['columns']['order_number_formatted'] = 'order_number';
				unset( $custom_format['columns']['order_number'] );

				// remove refunds key
				unset( $custom_format['columns']['refunds'] );

				if ( 'default_one_row_per_item' === $orders_format ) {

					// rename 'total_tax' back to 'tax'
					$custom_format['columns']['total_tax'] = 'tax';

					// remove item-specific keys that weren't present in the old default format
					unset( $custom_format['columns']['item_id'] );
					unset( $custom_format['columns']['item_product_id'] );
					unset( $custom_format['columns']['subtotal'] );
					unset( $custom_format['columns']['subtotal_tax'] );

					update_option( 'wc_customer_order_csv_export_orders_custom_format_row_type', 'item' );

				} else {

					update_option( 'wc_customer_order_csv_export_orders_custom_format_row_type', 'order' );
				}

				$mapping = array();

				foreach ( $custom_format['columns'] as $column => $name ) {
					$mapping[] = array( 'source' => $column, 'name' => $name );
				}

				update_option( 'wc_customer_order_csv_export_orders_custom_format_delimiter', ',' );
				update_option( 'wc_customer_order_csv_export_orders_custom_format_mapping', $mapping );

				// set the current orders export format as `custom`
				update_option( 'wc_customer_order_csv_export_orders_format', 'custom' );
			}

			// handle renamed cron schedule
			if ( $start_timestamp = wp_next_scheduled( 'wc_customer_order_csv_export_auto_export_interval' ) ) {

				wp_clear_scheduled_hook( 'wc_customer_order_csv_export_auto_export_interval' );

				wp_schedule_event( $start_timestamp, 'wc_customer_order_csv_export_orders_auto_export_interval', 'wc_customer_order_csv_export_auto_export_orders' );
			}
		}
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


// fire it up!
wc_customer_order_csv_export();

} // init_woocommerce_customer_order_csv_export()
