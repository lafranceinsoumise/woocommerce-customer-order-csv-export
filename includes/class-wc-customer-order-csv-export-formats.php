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
 * Customer/Order CSV Export Formats
 *
 * Defines different export formats and provides a base set of
 * data sources for the formats.
 *
 * @since 4.0.0
 */
class WC_Customer_Order_CSV_Export_Formats {


	/** @var array $formats export formats container **/
	private $formats;

	/** @var array $all_meta_keys container for export types **/
	private $all_meta_keys = array();


	/**
	 * Get column data options for the export type. This method is useful
	 * for getting a base list of columns/column data options for
	 * an export format, for example to be used in the column mapper UI.
	 *
	 * @since 4.0.0
	 * @param string $export_type Export type
	 * @return array
	 */
	public function get_column_data_options( $export_type ) {

		$options = array();

		if ( 'orders' === $export_type ) {

			$options = array(
				'order_id',
				'order_number',
				'order_number_formatted',
				'order_date',
				'status',
				'shipping_total',
				'shipping_tax_total',
				'fee_total',
				'fee_tax_total',
				'tax_total',
				'discount_total',
				'order_total',
				'refunded_total',
				'order_currency',
				'payment_method',
				'shipping_method',
				'customer_id',
				'billing_first_name',
				'billing_last_name',
				'billing_full_name',
				'billing_company',
				'billing_email',
				'billing_phone',
				'billing_address_1',
				'billing_address_2',
				'billing_postcode',
				'billing_city',
				'billing_state',
				'billing_state_code',
				'billing_country',
				'shipping_first_name',
				'shipping_last_name',
				'shipping_full_name',
				'shipping_address_1',
				'shipping_address_2',
				'shipping_postcode',
				'shipping_city',
				'shipping_state',
				'shipping_state_code',
				'shipping_country',
				'shipping_company',
				'customer_note',
				'item_id',           // order item id
				'item_product_id',
				'item_name',
				'item_sku',
				'item_quantity',
				'item_subtotal',
				'item_subtotal_tax',
				'item_total',
				'item_total_tax',
				'item_refunded',
				'item_refunded_qty',
				'item_meta',
				'line_items',
				'shipping_items',
				'fee_items',
				'tax_items',
				'coupon_items',
				'refunds',
				'order_notes',
				'download_permissions',
			);

		} elseif ( 'customers' === $export_type ) {

			$options = array(
				'customer_id',
				'first_name',
				'last_name',
				'user_login',
				'email',
				'user_pass',
				'date_registered',
				'billing_first_name',
				'billing_last_name',
				'billing_full_name',
				'billing_company',
				'billing_email',
				'billing_phone',
				'billing_address_1',
				'billing_address_2',
				'billing_postcode',
				'billing_city',
				'billing_state',
				'billing_state_code',
				'billing_country',
				'shipping_first_name',
				'shipping_last_name',
				'shipping_full_name',
				'shipping_company',
				'shipping_address_1',
				'shipping_address_2',
				'shipping_postcode',
				'shipping_city',
				'shipping_state',
				'shipping_state_code',
				'shipping_country',
				'total_spent',
				'order_count',
			);
		}

		/**
		 * Allow actors to adjust the available export column data options
		 *
		 * This filter is especially useful for providing options for custom export types
		 *
		 * @since 4.0.0
		 * @param array $options column data options
		 * @param string $export_type export type
		 */
		return apply_filters( 'wc_customer_order_csv_export_format_column_data_options', $options, $export_type );
	}


	/**
	 * Get an export format
	 *
	 * @since 4.0.0
	 * @param string $export_type Export type, such as `orders` or `customers`
	 * @param string $format Export format, such as `default` or `import`
	 * @return array|null format definition, or null if not found
	 */
	public function get_format( $export_type, $format ) {

		$definition = null;

		// On first call, load the built-in & custom formats
		if ( ! isset( $this->formats ) ) {
			$this->load_formats();
		}

		// Find the requested format
		if ( isset( $this->formats[ $export_type ] ) && isset( $this->formats[ $export_type ][ $format ] ) ) {
			$definition = $this->formats[ $export_type ][ $format ];
		}

		/**
		 * Allow actors to change the format definition
		 *
		 * @since 4.0.0
		 * @param array|null $definition Format definition, or null if not found
		 * @param string $export_type Export type, such as `orders` or `customers`
		 * @param string $format Export format, such as `default` or `import`
		 */
		return apply_filters( 'wc_customer_order_csv_export_format_definition', $definition, $export_type, $format );
	}


	/**
	 * Get export formats for the given export type
	 *
	 * @since 4.0.0
	 * @param string $export_type Export type, such as `orders` or `customers`
	 * @return array
	 */
	public function get_formats( $export_type ) {

		if ( ! isset( $this->formats ) ) {
			$this->load_formats();
		}

		$formats = array();

		// make sure each format is filtered
		if ( ! empty( $this->formats[ $export_type ] ) ) {

			foreach ( array_keys( $this->formats[ $export_type ] ) as $format_key ) {

				$formats[ $format_key ] = $this->get_format( $export_type, $format_key );
			}
		}

		return $formats;
	}


	/**
	 * Constructor
	 *
	 * Initializes the built-in formats and loads the custom
	 * format to memory
	 *
	 * @since 4.0.0
	 */
	private function load_formats() {

		$this->formats = array(
			'orders'    => array(),
			'customers' => array(),
		);

		// 'default' format

		$this->formats['orders']['default'] = array(
			'delimiter'    => ',',
			'enclosure'    => '"',
			'row_type'     => 'order',
			'items_format' => 'pipe_delimited',
			'columns'      => array(
				'order_id'               => 'order_id',
				'order_number'           => 'order_number',
				'order_number_formatted' => 'order_number_formatted',
				'order_date'             => 'date',
				'status'                 => 'status',
				'shipping_total'         => 'shipping_total',
				'shipping_tax_total'     => 'shipping_tax_total',
				'fee_total'              => 'fee_total',
				'fee_tax_total'          => 'fee_tax_total',
				'tax_total'              => 'tax_total',
				'discount_total'         => 'discount_total',
				'order_total'            => 'order_total',
				'refunded_total'         => 'refunded_total',
				'order_currency'         => 'order_currency',
				'payment_method'         => 'payment_method',
				'shipping_method'        => 'shipping_method',
				'customer_id'            => 'customer_id',
				'billing_first_name'     => 'billing_first_name',
				'billing_last_name'      => 'billing_last_name',
				'billing_company'        => 'billing_company',
				'billing_email'          => 'billing_email',
				'billing_phone'          => 'billing_phone',
				'billing_address_1'      => 'billing_address_1',
				'billing_address_2'      => 'billing_address_2',
				'billing_postcode'       => 'billing_postcode',
				'billing_city'           => 'billing_city',
				'billing_state'          => 'billing_state',
				'billing_country'        => 'billing_country',
				'shipping_first_name'    => 'shipping_first_name',
				'shipping_last_name'     => 'shipping_last_name',
				'shipping_address_1'     => 'shipping_address_1',
				'shipping_address_2'     => 'shipping_address_2',
				'shipping_postcode'      => 'shipping_postcode',
				'shipping_city'          => 'shipping_city',
				'shipping_state'         => 'shipping_state',
				'shipping_country'       => 'shipping_country',
				'shipping_company'       => 'shipping_company',
				'customer_note'          => 'customer_note',
				'line_items'             => 'line_items',
				'shipping_items'         => 'shipping_items',
				'fee_items'              => 'fee_items',
				'tax_items'              => 'tax_items',
				'coupon_items'           => 'coupon_items',
				'refunds'                => 'refunds',
				'order_notes'            => 'order_notes',
				'download_permissions'   => 'download_permissions_granted',
			),
		);


		// 'default_one_row_per_item' format

		$this->formats['orders']['default_one_row_per_item'] = array(
			'delimiter'    => ',',
			'enclosure'    => '"',
			'row_type'     => 'item',
			'items_format' => 'pipe_delimited',
			'columns'      => array(
				'order_id'             => 'order_id',
				'order_number'         => 'order_number',
				'order_date'           => 'date',
				'status'               => 'status',
				'shipping_total'       => 'shipping_total',
				'shipping_tax_total'   => 'shipping_tax_total',
				'fee_total'            => 'fee_total',
				'fee_tax_total'        => 'fee_tax_total',
				'tax_total'            => 'tax_total',
				'discount_total'       => 'discount_total',
				'order_total'          => 'order_total',
				'refunded_total'       => 'refunded_total',
				'order_currency'       => 'order_currency',
				'payment_method'       => 'payment_method',
				'shipping_method'      => 'shipping_method',
				'customer_id'          => 'customer_id',
				'billing_first_name'   => 'billing_first_name',
				'billing_last_name'    => 'billing_last_name',
				'billing_company'      => 'billing_company',
				'billing_email'        => 'billing_email',
				'billing_phone'        => 'billing_phone',
				'billing_address_1'    => 'billing_address_1',
				'billing_address_2'    => 'billing_address_2',
				'billing_postcode'     => 'billing_postcode',
				'billing_city'         => 'billing_city',
				'billing_state'        => 'billing_state',
				'billing_country'      => 'billing_country',
				'shipping_first_name'  => 'shipping_first_name',
				'shipping_last_name'   => 'shipping_last_name',
				'shipping_address_1'   => 'shipping_address_1',
				'shipping_address_2'   => 'shipping_address_2',
				'shipping_postcode'    => 'shipping_postcode',
				'shipping_city'        => 'shipping_city',
				'shipping_state'       => 'shipping_state',
				'shipping_country'     => 'shipping_country',
				'shipping_company'     => 'shipping_company',
				'customer_note'        => 'customer_note',
				'item_id'              => 'item_id',
				'item_product_id'      => 'item_product_id',
				'item_name'            => 'item_name',
				'item_sku'             => 'item_sku',
				'item_quantity'        => 'item_quantity',
				'item_subtotal'        => 'item_subtotal',
				'item_subtotal_tax'    => 'item_subtotal_tax',
				'item_total'           => 'item_total',
				'item_total_tax'       => 'item_total_tax',
				'item_refunded'        => 'item_refunded',
				'item_refunded_qty'    => 'item_refunded_qty',
				'item_meta'            => 'item_meta',
				'shipping_items'       => 'shipping_items',
				'fee_items'            => 'fee_items',
				'tax_items'            => 'tax_items',
				'coupon_items'         => 'coupon_items',
				'order_notes'          => 'order_notes',
				'download_permissions' => 'download_permissions_granted',
			),
		);


		// 'import' format, based on 'default'

		$this->formats['orders']['import'] = array(
			'delimiter'    => ',',
			'enclosure'    => '"',
			'row_type'     => 'order',
			'items_format' => 'json',
			'columns' => array(
				'order_id'                  => 'id',
				'order_number'              => 'order_number',
				'order_number_formatted'    => 'order_number_formatted',
				'order_date'                => 'created_at',
				'status'                    => 'status',
				'order_total'               => 'total',
				'shipping_total'            => 'total_shipping',
				'tax_total'                 => 'cart_tax',
				'shipping_tax_total'        => 'shipping_tax',
				'discount_total'            => 'total_discount',
				'refunded_total'            => 'total_refunded',
				'payment_method'            => 'payment_method',
				'order_currency'            => 'currency',
				'customer_id'               => 'customer_user',
				'billing_first_name'        => 'billing_first_name',
				'billing_last_name'         => 'billing_last_name',
				'billing_email'             => 'billing_email',
				'billing_phone'             => 'billing_phone',
				'billing_address_1'         => 'billing_address_1',
				'billing_address_2'         => 'billing_address_2',
				'billing_postcode'          => 'billing_postcode',
				'billing_city'              => 'billing_city',
				'billing_state'             => 'billing_state',
				'billing_country'           => 'billing_country',
				'billing_company'           => 'billing_company',
				'shipping_first_name'       => 'shipping_first_name',
				'shipping_last_name'        => 'shipping_last_name',
				'shipping_address_1'        => 'shipping_address_1',
				'shipping_address_2'        => 'shipping_address_2',
				'shipping_postcode'         => 'shipping_postcode',
				'shipping_city'             => 'shipping_city',
				'shipping_state'            => 'shipping_state',
				'shipping_country'          => 'shipping_country',
				'shipping_company'          => 'shipping_company',
				'customer_note'             => 'note',
				'line_items'                => 'line_items',
				'shipping_items'            => 'shipping_lines',
				'tax_items'                 => 'tax_lines',
				'fee_items'                 => 'fee_lines',
				'coupon_items'              => 'coupon_lines',
				'refunds'                   => 'refunds',
				'order_notes'               => 'order_notes',
				'download_permissions'      => 'download_permissions_granted',
			),
		);


		// 'legacy_import', also based on 'default'

		$this->formats['orders']['legacy_import'] = array(
			'delimiter'    => ',',
			'enclosure'    => '"',
			'row_type'     => 'order',
			'items_format' => 'pipe_delimited',
			'columns' => array(
				'order_id'               => 'order_id',
				'order_number_formatted' => 'order_number_formatted',
				'order_number'           => 'order_number',
				'order_date'             => 'date',
				'status'                 => 'status',
				'shipping_total'         => 'order_shipping',
				'shipping_tax_total'     => 'order_shipping_tax',
				'fee_total'              => 'order_fees',
				'fee_tax_total'          => 'order_fee_tax',
				'tax_total'              => 'order_tax',
				'discount_total'         => 'discount_total',
				'order_total'            => 'order_total',
				'payment_method'         => 'payment_method',
				'shipping_method'        => 'shipping_method',
				'customer_id'            => 'customer_user',
				'billing_first_name'     => 'billing_first_name',
				'billing_last_name'      => 'billing_last_name',
				'billing_email'          => 'billing_email',
				'billing_phone'          => 'billing_phone',
				'billing_address_1'      => 'billing_address_1',
				'billing_address_2'      => 'billing_address_2',
				'billing_postcode'       => 'billing_postcode',
				'billing_city'           => 'billing_city',
				'billing_state'          => 'billing_state',
				'billing_country'        => 'billing_country',
				'billing_company'        => 'billing_company',
				'shipping_first_name'    => 'shipping_first_name',
				'shipping_last_name'     => 'shipping_last_name',
				'shipping_address_1'     => 'shipping_address_1',
				'shipping_address_2'     => 'shipping_address_2',
				'shipping_postcode'      => 'shipping_postcode',
				'shipping_city'          => 'shipping_city',
				'shipping_state'         => 'shipping_state',
				'shipping_country'       => 'shipping_country',
				'shipping_company'       => 'shipping_company',
				'customer_note'          => 'customer_note',
				'order_item_[i]'         => 'order_item_[i]', // will be replaced with order_item_1, order_item_2 etc
				'order_notes'            => 'order_notes',
				'download_permissions'   => 'download_permissions_granted',
				'shipping_method_[i]'    => 'shipping_method_[i]', // will be replaced with shipping_method, shipping_method_2 etc
			),
		);


		// 'legacy_single_column' format

		$this->formats['orders']['legacy_single_column'] = array(
			'delimiter'    => ',',
			'enclosure'    => '"',
			'row_type'     => 'order',
			'items_format' => 'pipe_delimited',
			'columns' => array(
				'order_id'             => __( 'Order ID', 'woocommerce-customer-order-csv-export' ),
				'order_date'           => __( 'Date', 'woocommerce-customer-order-csv-export' ),
				'status'               => __( 'Order Status', 'woocommerce-customer-order-csv-export' ),
				'shipping_total'       => __( 'Shipping', 'woocommerce-customer-order-csv-export' ),
				'shipping_tax_total'   => __( 'Shipping Tax', 'woocommerce-customer-order-csv-export' ),
				'fee_total'            => __( 'Fees', 'woocommerce-customer-order-csv-export' ),
				'fee_tax_total'        => __( 'Fee Tax', 'woocommerce-customer-order-csv-export' ),
				'tax_total'            => __( 'Tax', 'woocommerce-customer-order-csv-export' ),
				'discount_total'       => __( 'Discount Total', 'woocommerce-customer-order-csv-export' ),
				'order_total'          => __( 'Order Total', 'woocommerce-customer-order-csv-export' ),
				'payment_method'       => __( 'Payment Method', 'woocommerce-customer-order-csv-export' ),
				'shipping_method'      => __( 'Shipping Method', 'woocommerce-customer-order-csv-export' ),
				'billing_first_name'   => __( 'Billing First Name', 'woocommerce-customer-order-csv-export' ),
				'billing_last_name'    => __( 'Billing Last Name', 'woocommerce-customer-order-csv-export' ),
				'billing_email'        => __( 'Billing Email', 'woocommerce-customer-order-csv-export' ),
				'billing_phone'        => __( 'Billing Phone', 'woocommerce-customer-order-csv-export' ),
				'billing_address_1'    => __( 'Billing Address 1', 'woocommerce-customer-order-csv-export' ),
				'billing_address_2'    => __( 'Billing Address 2', 'woocommerce-customer-order-csv-export' ),
				'billing_postcode'     => __( 'Billing Post code', 'woocommerce-customer-order-csv-export' ),
				'billing_city'         => __( 'Billing City', 'woocommerce-customer-order-csv-export' ),
				'billing_state'        => __( 'Billing State', 'woocommerce-customer-order-csv-export' ),
				'billing_country'      => __( 'Billing Country', 'woocommerce-customer-order-csv-export' ),
				'billing_company'      => __( 'Billing Company', 'woocommerce-customer-order-csv-export' ),
				'shipping_first_name'  => __( 'Shipping First Name', 'woocommerce-customer-order-csv-export' ),
				'shipping_last_name'   => __( 'Shipping Last Name', 'woocommerce-customer-order-csv-export' ),
				'shipping_address_1'   => __( 'Shipping Address 1', 'woocommerce-customer-order-csv-export' ),
				'shipping_address_2'   => __( 'Shipping Address 2', 'woocommerce-customer-order-csv-export' ),
				'shipping_postcode'    => __( 'Shipping Post code', 'woocommerce-customer-order-csv-export' ),
				'shipping_city'        => __( 'Shipping City', 'woocommerce-customer-order-csv-export' ),
				'shipping_state'       => __( 'Shipping State', 'woocommerce-customer-order-csv-export' ),
				'shipping_country'     => __( 'Shipping Country', 'woocommerce-customer-order-csv-export' ),
				'shipping_company'     => __( 'Shipping Company', 'woocommerce-customer-order-csv-export' ),
				'customer_note'        => __( 'Customer Note', 'woocommerce-customer-order-csv-export' ),
				'order_items'          => __( 'Order Items', 'woocommerce-customer-order-csv-export' ),
				'download_permissions' => __( 'Download Permissions Granted', 'woocommerce-customer-order-csv-export' ),
				'order_notes'          => __( 'Order Notes', 'woocommerce-customer-order-csv-export' ),
				'coupon_items'         => __( 'Coupons', 'woocommerce-customer-order-csv-export' ),
			),
		);


		// 'legacy_one_row_per_item' format, based on legacy format

		$this->formats['orders']['legacy_one_row_per_item'] = wp_parse_args( array(), $this->formats['orders']['legacy_single_column'] );

		unset( $this->formats['orders']['legacy_one_row_per_item']['row_type'] );
		unset( $this->formats['orders']['legacy_one_row_per_item']['columns']['order_items'] );

		$this->formats['orders']['legacy_one_row_per_item']['row_type'] = 'item';
		$this->formats['orders']['legacy_one_row_per_item']['columns']  = SV_WC_Helper::array_insert_after( $this->formats['orders']['legacy_one_row_per_item']['columns'], 'customer_note', array(
			'line_item_sku'       => __( 'Item SKU', 'woocommerce-customer-order-csv-export' ),
			'line_item_name'      => __( 'Item Name', 'woocommerce-customer-order-csv-export' ),
			'line_item_variation' => __( 'Item Variation', 'woocommerce-customer-order-csv-export' ),
			'line_item_amount'    => __( 'Item Amount', 'woocommerce-customer-order-csv-export' ),
			'line_item_price'     => __( 'Row Price', 'woocommerce-customer-order-csv-export' ),
		) );


		// 'custom' order format

		$this->formats['orders']['custom'] = array(
			'delimiter'    => get_option( 'wc_customer_order_csv_export_orders_custom_format_delimiter', ',' ),
			'enclosure'    => '"',
			'row_type'     => get_option( 'wc_customer_order_csv_export_orders_custom_format_row_type', 'order' ),
			'items_format' => get_option( 'wc_customer_order_csv_export_orders_custom_format_items_format', 'pipe_delimited' ),
			'columns'      => $this->get_custom_column_mapping( 'orders' ),
		);



		// Define customers export formats

		$this->formats['customers']['default'] = array(
			'delimiter' => ',',
			'enclosure' => '"',
			'columns'   => array(
				'customer_id'         => 'customer_id',
				'first_name'          => 'first_name',
				'last_name'           => 'last_name',
				'email'               => 'email',
				'date_registered'     => 'date_registered',
				'billing_first_name'  => 'billing_first_name',
				'billing_last_name'   => 'billing_last_name',
				'billing_company'     => 'billing_company',
				'billing_email'       => 'billing_email',
				'billing_phone'       => 'billing_phone',
				'billing_address_1'   => 'billing_address_1',
				'billing_address_2'   => 'billing_address_2',
				'billing_postcode'    => 'billing_postcode',
				'billing_city'        => 'billing_city',
				'billing_state'       => 'billing_state',
				'billing_country'     => 'billing_country',
				'shipping_first_name' => 'shipping_first_name',
				'shipping_last_name'  => 'shipping_last_name',
				'shipping_company'    => 'shipping_company',
				'shipping_address_1'  => 'shipping_address_1',
				'shipping_address_2'  => 'shipping_address_2',
				'shipping_postcode'   => 'shipping_postcode',
				'shipping_city'       => 'shipping_city',
				'shipping_state'      => 'shipping_state',
				'shipping_country'    => 'shipping_country',
			),
		);

		$this->formats['customers']['import'] = array(
			'delimiter' => ',',
			'enclosure' => '"',
			'columns'   => array(
				'user_login'          => 'username',
				'email'               => 'email',
				'user_pass'           => 'password',
				'date_registered'     => 'date_registered',
				'billing_first_name'  => 'billing_first_name',
				'billing_last_name'   => 'billing_last_name',
				'billing_company'     => 'billing_company',
				'billing_address_1'   => 'billing_address_1',
				'billing_address_2'   => 'billing_address_2',
				'billing_city'        => 'billing_city',
				'billing_state'       => 'billing_state',
				'billing_postcode'    => 'billing_postcode',
				'billing_country'     => 'billing_country',
				'billing_email'       => 'billing_email',
				'billing_phone'       => 'billing_phone',
				'shipping_first_name' => 'shipping_first_name',
				'shipping_last_name'  => 'shipping_last_name',
				'shipping_company'    => 'shipping_company',
				'shipping_address_1'  => 'shipping_address_1',
				'shipping_address_2'  => 'shipping_address_2',
				'shipping_city'       => 'shipping_city',
				'shipping_state'      => 'shipping_state',
				'shipping_postcode'   => 'shipping_postcode',
				'shipping_country'    => 'shipping_country',
			),
		);

		$this->formats['customers']['legacy'] = array(
			'delimiter' => ',',
			'enclosure' => '"',
			'columns'   => array(
				'customer_id'        => __( 'ID', 'woocommerce-customer-order-csv-export' ),
				'billing_first_name' => __( 'First Name', 'woocommerce-customer-order-csv-export' ),
				'billing_last_name'  => __( 'Last Name', 'woocommerce-customer-order-csv-export' ),
				'billing_email'      => __( 'Email', 'woocommerce-customer-order-csv-export' ),
				'billing_phone'      => __( 'Phone', 'woocommerce-customer-order-csv-export' ),
				'billing_address_1'  => __( 'Address', 'woocommerce-customer-order-csv-export' ),
				'billing_address_2'  => __( 'Address 2', 'woocommerce-customer-order-csv-export' ),
				'billing_postcode'   => __( 'Post code', 'woocommerce-customer-order-csv-export' ),
				'billing_city'       => __( 'City', 'woocommerce-customer-order-csv-export' ),
				'billing_state'      => __( 'State', 'woocommerce-customer-order-csv-export' ),
				'billing_country'    => __( 'Country', 'woocommerce-customer-order-csv-export' ),
				'billing_company'    => __( 'Company', 'woocommerce-customer-order-csv-export' ),
			),
		);

		$this->formats['customers']['custom'] = array(
			'delimiter' => get_option( 'wc_customer_order_csv_export_customers_custom_format_delimiter', ',' ),
			'enclosure' => '"',
			'columns'   => $this->get_custom_column_mapping( 'customers' ),
		);
	}


	/**
	 * Get custom export type column mapping
	 *
	 * @since 4.0.0
	 * @param string $export_type
	 * @return array
	 */
	private function get_custom_column_mapping( $export_type ) {

		$columns = array();

		$mapping = get_option( 'wc_customer_order_csv_export_' . $export_type . '_custom_format_mapping' );

		if ( ! empty( $mapping ) ) {
			foreach ( $mapping as $column ) {

				if ( empty( $column['source'] ) ) {
					continue;
				}

				$key = $column['source'];

				if ( 'meta' === $column['source'] ) {
					$key .= ':' . $column['meta_key'];
				}

				$columns[ $key ] = $column['name'];
			}
		}

		// Include all meta
		if ( 'yes' === get_option( 'wc_customer_order_csv_export_' . $export_type . '_custom_format_include_all_meta' ) ) {

			$all_meta = $this->get_all_meta_keys( $export_type );

			if ( ! empty( $all_meta ) ) {

				foreach ( $all_meta as $meta_key ) {

					// make sure this meta has not already been manually set
					foreach ( $mapping as $column  ) {

						if ( ! empty( $column['source'] ) && 'meta' === $column['source'] && $meta_key === $column['meta_key'] ) {
							continue 2;
						}
					}

					$columns[ 'meta:' . $meta_key ] = 'meta:' . $meta_key;
				}
			}
		}

		return $columns;
	}


	/**
	 * Get all meta keys for the given export type
	 *
	 * @since 4.0.0
	 * @param string $export_type
	 * @return array
	 */
	public function get_all_meta_keys( $export_type ) {

		if ( ! isset( $this->all_meta_keys[ $export_type ] ) ) {

			$meta_keys = array();

			if ( 'customers' === $export_type ) {
				$meta_keys = $this->get_user_meta_keys();
			} elseif ( 'orders' === $export_type ) {
				$meta_keys = $this->get_post_meta_keys( 'shop_order' );
			}

			// exclude meta with dedicated columns from all meta
			foreach ( $meta_keys as $key => $meta_key ) {

				if ( $this->meta_has_dedicated_column( $meta_key, $export_type ) ) {
					unset( $meta_keys[ $key ] );
				}
			}

			/**
			 * Allow actors to adjust the returned meta keys for an export type
			 *
			 * This filter is useful for providing meta keys for custom export types.
			 *
			 * @since 4.0.0
			 * @param array $meta_keys
			 * @param string $export_type
			 */
			$this->all_meta_keys[ $export_type ] = apply_filters( 'wc_customer_order_csv_export_all_meta_keys', $meta_keys, $export_type );
		}

		return $this->all_meta_keys[ $export_type ];
	}


	/**
	 * Get a list of all the meta keys for a post type. This includes all public, private,
	 * used, no-longer used etc. They will be sorted once fetched.
	 *
	 * @since 4.0.0
	 * @param string $post_type Optional. Defaults to `shop_order`
	 * @return array
	 */
	public function get_post_meta_keys( $post_type = 'shop_order' ) {

		global $wpdb;

		$meta = $wpdb->get_col( $wpdb->prepare( "
			SELECT DISTINCT pm.meta_key
			FROM {$wpdb->postmeta} AS pm
			LEFT JOIN {$wpdb->posts} AS p ON p.ID = pm.post_id
			WHERE p.post_type = %s
		", $post_type ) );

		sort( $meta );

		return $meta;
	}


	/**
	 * Get a list of all the meta keys for users. They will be sorted once fetched.
	 *
	 * @since 4.0.0
	 * @return array
	 */
	public function get_user_meta_keys() {

		global $wpdb;

		$meta = $wpdb->get_col( "SELECT DISTINCT meta_key FROM {$wpdb->usermeta}" );

		sort( $meta );

		return $meta;
	}


	/**
	 * Check if a meta key for an export type has a dedicated column
	 *
	 * @since 4.0.0
	 * @param string $meta_key
	 * @param string $export_type
	 * @return bool
	 */
	private function meta_has_dedicated_column( $meta_key, $export_type ) {

		$has_dedicated_column = false;

		if ( 'orders' === $export_type ) {

			$dedicated_order_columns = array( '_customer_user', '_order_shipping', '_order_shipping_tax', '_download_permissions_granted' );
			$has_dedicated_column    = in_array( $meta_key, $dedicated_order_columns, true );
		}

		if ( ! $has_dedicated_column ) {

			$columns              = $this->get_column_data_options( $export_type );
			$_meta_key            = SV_WC_Helper::str_starts_with( $meta_key, '_' ) ? substr( $meta_key, 1 ) : $meta_key;
			$has_dedicated_column = ! empty( $columns ) && in_array( $_meta_key, $columns, true );
		}

		/**
		 * Allow actors to adjust whether a meta key has a dedicated column or not
		 *
		 * This affects whether the meta key is included in custom export formats
		 * with `include all meta` checked or not. Meta keys having dedicated columns
		 * are excluded from the export, as the value will be present in the dedicated column.
		 *
		 * @since 4.0.0
		 * @param bool $has_dedicated_column
		 * @param string $meta_key
		 * @param string $export_type
		 */
		return apply_filters( 'wc_customer_order_csv_export_meta_has_dedicated_column', $has_dedicated_column, $meta_key, $export_type );
	}

}
