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
 * @package     WC-Customer-Order-CSV-Export/Generator
 * @author      SkyVerge
 * @copyright   Copyright (c) 2012-2017, SkyVerge, Inc.
 * @license     http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

defined( 'ABSPATH' ) or exit;

/**
 * Customer/Order CSV Export Generator
 *
 * Converts customer/order data into CSV
 *
 * @since 3.0.0
 */
class WC_Customer_Order_CSV_Export_Generator {


	/** @var string export type */
	public $export_type;

	/** @var string export format */
	public $export_format;

	/** @var array format definition */
	public $format_definition;

	/** @var string CSV delmiter */
	public $delimiter;

	/** @var string CSV enclosure */
	public $enclosure;

	/** @var array order IDs or customer IDs */
	public $ids;


	/**
	 * Initialize the generator
	 *
	 * In 4.0.0 replaced $ids param with $export_type param
	 * In 4.0.3 added back $ids as a second, optional param for backwards compatibility
	 *
	 * @since 3.0.0
	 * @param string $export_type export type, one of `orders` or `customers`
	 * @param array $ids optional. Object IDs associated with the export. Provide for export formats that
	 *                   modify headers based on the obejcts being exported (such as orders legacy import format)
	 */
	public function __construct( $export_type, $ids = null ) {

		$this->export_type = $export_type;

		$export_format = get_option( 'wc_customer_order_csv_export_' . $export_type . '_format', 'default' );

		/**
		 * Allow actors to change the export format for the given export type
		 *
		 * @since 4.0.0
		 * @param string $format
		 * @param \WC_Customer_Order_CSV_Export_Generator $this, generator instance
		 */
		$this->export_format = apply_filters( 'wc_customer_order_csv_export_format', $export_format, $this );

		// get format definition
		$this->format_definition = wc_customer_order_csv_export()->get_formats_instance()->get_format( $export_type, $this->export_format );

		/**
		 * CSV Delimiter.
		 *
		 * Filter the delimiter used for the CSV file
		 *
		 * @since 3.0.0
		 * @param string $delimiter, defaults to comma (,)
		 * @param \WC_Customer_Order_CSV_Export_Generator $this, generator instance
		 */
		$this->delimiter = apply_filters( 'wc_customer_order_csv_export_delimiter', $this->format_definition['delimiter'], $this );

		/**
		 * CSV Enclosure.
		 *
		 * Filter the enclosure used for the CSV file
		 *
		 * @since 3.0.0
		 * @param string $enclosure, defaults to double quote (")
		 * @param \WC_Customer_Order_CSV_Export_Generator $this, generator instance
		 */
		$this->enclosure = apply_filters( 'wc_customer_order_csv_export_enclosure', $this->format_definition['enclosure'], $this );

		if ( ! empty( $ids ) ) {
			$this->ids = $ids;
		}
	}


	/**
	 * Get the CSV for orders
	 *
	 * @since 3.0.0
	 * @param array $ids Order ID(s) to export
	 * @param bool $include_headers Optional. Whether to include CSV column headers in the output or not. Defaults to false
	 * @return string CSV data
	 */
	public function get_orders_csv( $ids, $include_headers = false ) {

		$stream  = fopen( 'php://output', 'w' );
		$headers = $this->get_orders_csv_headers();

		ob_start();

		if ( $include_headers ) {

			$header = $this->get_header();

			if ( null !== $header ) {
				fputs( $stream, $header );
			}
		}

		$order_data = array();

		// iterate through order IDs
		foreach ( $ids as $order_id ) {

			// get data for each order
			$data = $this->get_orders_csv_row_data( $order_id );

			// skip order/data wasn't found
			if ( empty( $data ) ) {
				continue;
			}

			$order_data[] = $data;

			// data can be an array of arrays when each line item is it's own row
			$first_element = reset( $data );

			if ( is_array( $first_element ) ) {

				// iterate through each line item row and write it
				foreach ( $data as $row ) {

					fputs( $stream, $this->get_row_csv( $row, $headers ) );
				}

			} else {

				// otherwise simply write the single order row
				fputs( $stream, $this->get_row_csv( $data, $headers ) );
			}
		}

		fclose( $stream );

		$csv = ob_get_clean();

		/**
		 * Filter the generated orders CSV
		 *
		 * @since 3.8.0
		 * @param string $csv_data The CSV data
		 * @param array $order_data An array of the order data to write to to the CSV
		 * @param array $order_ids The order ids.
		 * @param string $export_format The order export format.
		 */
		return apply_filters( 'wc_customer_order_csv_export_get_orders_csv', $csv, $order_data, $ids, $this->export_format );
	}


	/**
	 * Get the column headers for the orders CSV
	 *
	 * Note that the headers are keyed in column_key => column_name format so that plugins can control the output
	 * format using only the column headers and row data is not required to be in the exact same order, as the row data
	 * is matched on the column key
	 *
	 * @since 3.0.0
	 * @return array column headers in column_key => column_name format
	 */
	private function get_orders_csv_headers() {

		$column_headers = $this->format_definition['columns'];

		/**
		 * CSV Order Export Column Headers.
		 *
		 * Filter the column headers for the order export
		 *
		 * @since 3.0.0
		 * @param array $column_headers {
		 *     column headers in key => name format
		 *     to modify the column headers, ensure the keys match these and set your own values
		 * }
		 * @param \WC_Customer_Order_CSV_Export_Generator $this, generator instance
		 */
		return apply_filters( 'wc_customer_order_csv_export_order_headers', $column_headers, $this );
	}


	/**
	 * Get the order data for a single CSV row
	 *
	 * Note items are keyed according to the column header keys above so these can be modified using
	 * the provider filter without needing to worry about the array order.
	 *
	 * In 4.0.0 renamed from 'get_orders_csv_row' to 'get_orders_csv_row_data'
	 *
	 * @since 3.0.0
	 * @param int $order_id the WC_Order ID
	 * @return array|false order data in the format key => content, or false on failure
	 */
	private function get_orders_csv_row_data( $order_id ) {

		$order = wc_get_order( $order_id );

		// skip if invalid order
		if ( ! $order instanceof WC_Order ) {
			return false;
		}

		$is_json    = 'json' === $this->format_definition['items_format'];
		$line_items = $shipping_items = $fee_items = $tax_items = $coupon_items = array();

		// get line items
		foreach ( $order->get_items() as $item_id => $item ) {

			if ( $is_json ) {

				$meta           = array();
				$meta_formatted = SV_WC_Order_Compatibility::get_item_formatted_meta_data( $item, '_' );

				foreach ( $meta_formatted as $meta_key => $formatted_meta ) {
					// we need to encode quotes as escaping them will break CSV cells in JSON format
					$meta[ $formatted_meta['label'] ] = wp_strip_all_tags( str_replace( '"', '&quot;', $formatted_meta['value'] ) );
				}

			} else {

				// make sure we filter really late as to not interfere with other plugins, such as
				// Product Add-Ons
				add_filter( 'woocommerce_attribute_label',               array( $this, 'escape_reserved_meta_chars' ), 9999 );
				add_filter( 'woocommerce_order_item_display_meta_value', array( $this, 'escape_reserved_meta_chars' ), 9999 );

				if ( SV_WC_Plugin_Compatibility::is_wc_version_gte_3_1() ) {

					$meta = wc_display_item_meta( $item, array(
						'before'    => '',
						'after'     => '',
						'separator' => "\n",
						'echo'      => false,
					) );

				} else {

					$item_meta = new WC_Order_Item_Meta( $item );
					$meta      = $item_meta->display( true, true );
				}

				remove_filter( 'woocommerce_attribute_label',               array( $this, 'escape_reserved_meta_chars' ), 9999 );
				remove_filter( 'woocommerce_order_item_display_meta_value', array( $this, 'escape_reserved_meta_chars' ), 9999 );

				if ( $meta ) {

					// replace key-value sperator (': ') with our own - equals sign (=)
					$meta = str_replace( ': ', '=', wp_strip_all_tags( $meta ) );

					// remove any newlines generated by WC_Order_Item_Meta::display()
					$meta = str_replace( array( ", \r\n", ", \r", ", \n", "\r\n", "\r", "\n" ), ',', $meta );

					// remove any html entities
					$meta = preg_replace( '/\&(?:[a-z,A-Z,0-9]+|#\d+|#x[0-9a-f]+);/', '', $meta );

					// re-insert colons and newlines
					$meta = str_replace( array( '[INSERT_COLON_HERE]', '[INSERT_NEWLINE_HERE]' ), array( ':', "\n" ), $meta );
				}
			}

			// Give the product ID and SKU initial values in case they're not overwritten.
			// this means the product doesn't exist, so it could have been deleted, BUT
			// we should set the SKU to a value so CSV import could allow these orders to be imported
			$product     = SV_WC_Plugin_Compatibility::is_wc_version_gte_3_1() ? $item->get_product() : $order->get_product_from_item( $item );
			$product_id  = 0;
			$product_sku = 'unknown_product';

			// Check if the product exists.
			if ( is_object( $product ) ) {
				$product_id  = $product->get_id();
				$product_sku = $product->get_sku();
			}

			$line_item = array(
				// fields following WC API conventions (except refunded and refunded_qty), see WC_API_Orders
				'id'           => $item_id,
				// we need to encode quotes as escaping them will break CSV cells in JSON format
				'name'         => $is_json ? str_replace( '"', '&quot;', $item['name'] ) : $item['name'],
				'product_id'   => $product_id,
				'sku'          => $product_sku,
				'quantity'     => (int) $item['qty'],
				'subtotal'     => wc_format_decimal( $order->get_line_subtotal( $item ), 2 ),
				'subtotal_tax' => wc_format_decimal( $item['line_subtotal_tax'], 2 ),
				'total'        => wc_format_decimal( $order->get_line_total( $item ), 2 ),
				'total_tax'    => wc_format_decimal( $order->get_line_tax( $item ), 2 ),
				'refunded'     => wc_format_decimal( $order->get_total_refunded_for_item( $item_id ), 2 ),
				'refunded_qty' => $order->get_qty_refunded_for_item( $item_id ),
				'meta'         => $meta,
			);

			// tax data is only supported in JSON-based formats, as encoding/escaping it reliably in
			// a pipe-delimited format is quite messy
			if ( $is_json ) {
				$line_item['tax_data'] = isset( $item['line_tax_data'] ) ? maybe_unserialize( $item['line_tax_data'] ) : '';
			}

			/**
			 * CSV Order Export Line Item.
			 *
			 * Filter the individual line item entry
			 *
			 * @since 3.0.6
			 * @param array $line_item {
			 *     line item data in key => value format
			 *     the keys are for convenience and not necessarily used for exporting. Make
			 *     sure to prefix the values with the desired line item entry name
			 * }
			 *
			 * @param array $item WC order item data
			 * @param \WC_Product $product the product. May be boolean false if the product for line item doesn't exist anymore.
			 * @param \WC_Order $order the order
			 * @param \WC_Customer_Order_CSV_Export_Generator $this, generator instance
			 */
			$line_item = apply_filters( 'wc_customer_order_csv_export_order_line_item', $line_item, $item, $product, $order, $this );

			if ( ! empty( $line_item ) ) {
				$line_items[] = $is_json || 'item' === $this->format_definition['row_type'] ? $line_item : $this->pipe_delimit_item( $line_item );
			}
		}

		// get shipping items
		foreach ( $order->get_shipping_methods() as $shipping_item_id => $shipping ) {

			$shipping_item = array(
				'id'           => $shipping_item_id,
				'method_id'    => $shipping['method_id'],
				'method_title' => $shipping['name'],
				'total'        => wc_format_decimal( $shipping['cost'], 2 ),
			);

			// tax data is only supported in JSON-based formats, as encoding/escaping it reliably in
			// a pipe-delimited format is quite messy
			if ( $is_json ) {
				$shipping_item['taxes'] = isset( $shipping['taxes'] ) ? maybe_unserialize( $shipping['taxes'] ) : '';
			}

			/**
			 * CSV Order Export Shipping Line Item.
			 *
			 * Filter the individual shipping line item entry
			 *
			 * @since 4.0.0
			 * @param array $shipping_item {
			 *     line item data in key => value format
			 *     the keys are for convenience and not necessarily used for exporting. Make
			 *     sure to prefix the values with the desired shipping line item entry name
			 * }
			 *
			 * @param array $shipping WC order shipping item data
			 * @param WC_Order $order the order
			 * @param \WC_Customer_Order_CSV_Export_Generator $this, generator instance
			 */
			$shipping_item = apply_filters( 'wc_customer_order_csv_export_order_shipping_item', $shipping_item, $shipping, $order, $this );

			$shipping_items[] = $is_json ? $shipping_item : $this->pipe_delimit_item( $shipping_item );
		}

		// get fee items & total
		$fee_total = 0;
		$fee_tax_total = 0;

		foreach ( $order->get_fees() as $fee_item_id => $fee ) {

			$fee_item = array(
				'id'        => $fee_item_id,
				'title'     => $fee['name'],
				'tax_class' => ( ! empty( $fee['tax_class'] ) ) ? $fee['tax_class'] : null,
				'total'     => wc_format_decimal( $order->get_line_total( $fee ), 2 ),
				'total_tax' => wc_format_decimal( $order->get_line_tax( $fee ), 2 ),
			);

			// tax data is only supported in JSON-based formats, as encoding/escaping it reliably in
			// a pipe-delimited format is quite messy
			if ( $is_json ) {
				$fee_item['tax_data'] = isset( $fee['line_tax_data'] ) ? maybe_unserialize( $fee['line_tax_data'] ) : '';
			}

			$fee_item['taxable'] = null !== $fee_item['tax_class'];

			/**
			 * CSV Order Export Fee Line Item.
			 *
			 * Filter the individual fee line item entry
			 *
			 * @since 4.0.0
			 * @param array $fee_item {
			 *     line item data in key => value format
			 *     the keys are for convenience and not necessarily used for exporting. Make
			 *     sure to prefix the values with the desired fee line item entry name
			 * }
			 *
			 * @param array $fee WC order fee item data
			 * @param WC_Order $order the order
			 * @param \WC_Customer_Order_CSV_Export_Generator $this, generator instance
			 */
			$fee_item = apply_filters( 'wc_customer_order_csv_export_order_fee_item', $fee_item, $fee, $order, $this );

			$fee_items[] = $is_json ? $fee_item : $this->pipe_delimit_item( $fee_item );

			$fee_total     += $fee['line_total'];
			$fee_tax_total += $fee['line_tax'];
		}


		// get tax items
		foreach ( $order->get_tax_totals() as $tax_code => $tax ) {

			$tax_item = array(
				'id'       => $tax->id,
				'rate_id'  => $tax->rate_id,
				'code'     => $tax_code,
				'title'    => $tax->label,
				'total'    => wc_format_decimal( $tax->amount, 2 ),
				'compound' => (bool) $tax->is_compound,
			);

			/**
			 * CSV Order Export Tax Line Item.
			 *
			 * Filter the individual tax line item entry
			 *
			 * @since 4.0.0
			 * @param array $tax_item {
			 *     line item data in key => value format
			 *     the keys are for convenience and not necessarily used for exporting. Make
			 *     sure to prefix the values with the desired tax line item entry name
			 * }
			 *
			 * @param object $tax WC order tax item
			 * @param WC_Order $order the order
			 * @param \WC_Customer_Order_CSV_Export_Generator $this, generator instance
			 */
			$tax_item = apply_filters( 'wc_customer_order_csv_export_order_tax_item', $tax_item, $tax, $order, $this );

			$tax_items[] = $is_json ? $tax_item : $this->pipe_delimit_item( $tax_item );
		}


		// add coupons
		foreach ( $order->get_items( 'coupon' ) as $coupon_item_id => $coupon ) {

			$_coupon     = new WC_Coupon( $coupon['name'] );
			$coupon_post = get_post( SV_WC_Data_Compatibility::get_prop( $_coupon, 'id' ) );

			$coupon_item = array(
				'id'          => $coupon_item_id,
				'code'        => $coupon['name'],
				'amount'      => wc_format_decimal( $coupon['discount_amount'], 2 ),
				'description' => is_object( $coupon_post ) ? $coupon_post->post_excerpt : '',
			);

			/**
			 * CSV Order Export Coupon Line Item.
			 *
			 * Filter the individual coupon line item entry
			 *
			 * @since 4.0.0
			 * @param array $coupon_item {
			 *     line item data in key => value format
			 *     the keys are for convenience and not necessarily used for exporting. Make
			 *     sure to prefix the values with the desired refund line item entry name
			 * }
			 *
			 * @param array $coupon WC order coupon item
			 * @param WC_Order $order the order
			 * @param \WC_Customer_Order_CSV_Export_Generator $this, generator instance
			 */
			$coupon_item = apply_filters( 'wc_customer_order_csv_export_order_coupon_item', $coupon_item, $coupon, $order, $this );

			$coupon_items[] = $is_json ? $coupon_item : $this->pipe_delimit_item( $coupon_item );
		}


		// add refunds
		$refunds = array();

		foreach ( $order->get_refunds() as $refund ) {

			if ( SV_WC_Plugin_Compatibility::is_wc_version_gte_3_0() ) {

				$refund_data = array(
					'id'         => $refund->get_id(),
					'date'       => $this->format_date( $refund->get_date_created()->date( 'Y-m-d H:i:s' ) ),
					'amount'     => wc_format_decimal( $refund->get_amount(), 2 ),
					'reason'     => $refund->get_reason(),
				);

			} else {

				$refund_data = array(
					'id'         => $refund->id,
					'date'       => $this->format_date( $refund->date ),
					'amount'     => wc_format_decimal( $refund->get_refund_amount(), 2 ),
					'reason'     => $refund->get_refund_reason(),
				);
			}


			// line items data for refunds is only supported in JSON-based formats, as encoding/escaping it reliably in
			// a pipe-delimited format is quite messy
			if ( $is_json ) {

				$refunded_items = array();

				// add line items
				foreach ( $refund->get_items( array( 'line_item', 'fee', 'shipping' ) ) as $item_id => $item ) {

					$refund_amount = abs( isset( $item['line_total'] ) ? $item['line_total'] : ( isset( $item['cost'] ) ? $item['cost'] : null ) );

					// skip empty refund lines
					if ( ! $refund_amount ) {
						continue;
					}

					$refunded_item = array(
						'refunded_item_id' => $item['refunded_item_id'],
						'refund_total'     => $refund_amount,
					);

					// tax data is only supported in JSON-based formats, as encoding/escaping it reliably in
					// a pipe-delimited format is quite messy
					if ( $is_json ) {

						if ( isset( $item['taxes'] ) ) {

							// shipping items use `taxes`, with no distinction between total/subtotal
							$tax_data = maybe_unserialize( $item['taxes'] );
							$refunded_item['refund_tax'] = $tax_data['total'];

						} elseif ( isset( $item['line_tax_data'] ) ) {

							// line & fee items use `line_tax_data`, with both total and subtotal tax details
							// however, we are only interested in total tax details, as this is what is needed
							// by wc_create_refund.
							$tax_data = maybe_unserialize( $item['line_tax_data'] );
							$refunded_item['refund_tax'] = $tax_data['total'];
						}
					}

					if ( isset( $item['qty'] ) ) {
						$refunded_item['qty'] = $item['qty'];
					}

					$refunded_items[] = $refunded_item;
				}

				$refund_data['line_items'] = $refunded_items;
			}

			/**
			 * CSV Order Export Refund.
			 *
			 * Filter the individual refund entry
			 *
			 * @since 4.0.0
			 * @param array $refund {
			 *     line item data in key => value format
			 *     the keys are for convenience and not necessarily used for exporting. Make
			 *     sure to prefix the values with the desired refund entry name
			 * }
			 *
			 * @param \WC_Order_Refund $refund WC order refund instance
			 * @param WC_Order $order the order
			 * @param \WC_Customer_Order_CSV_Export_Generator $this, generator instance
			 */
			$refund_data = apply_filters( 'wc_customer_order_csv_export_order_refund_data', $refund_data, $refund, $order, $this );

			$refunds[] = $is_json ? $refund_data : $this->pipe_delimit_item( $refund_data );
		}

		$download_permissions_granted = SV_WC_Order_Compatibility::get_meta( $order, '_download_permissions_granted' );

		if ( SV_WC_Plugin_Compatibility::is_wc_version_gte_3_0() ) {
			$order_date = is_callable( array( $order->get_date_created(), 'date' ) ) ? $order->get_date_created()->date( 'Y-m-d H:i:s' ) : null;
		} else {
			$order_date = $order->order_date;
		}

		$order_data = array(
			'order_id'               => SV_WC_Order_Compatibility::get_prop( $order, 'id' ),
			'order_number_formatted' => SV_WC_Order_Compatibility::get_meta( $order, '_order_number_formatted', true ),
			'order_number'           => SV_WC_Order_Compatibility::get_meta( $order, '_order_number', true ),
			'order_date'             => $this->format_date( $order_date ),
			'status'                 => $order->get_status(),
			'shipping_total'         => $order->get_total_shipping(),
			'shipping_tax_total'     => wc_format_decimal( $order->get_shipping_tax(), 2 ),
			'fee_total'              => wc_format_decimal( $fee_total, 2 ),
			'fee_tax_total'          => wc_format_decimal( $fee_tax_total, 2 ),
			'tax_total'              => wc_format_decimal( $order->get_total_tax(), 2 ),
			'discount_total'         => wc_format_decimal( $order->get_total_discount(), 2 ),
			'order_total'            => wc_format_decimal( $order->get_total(), 2 ),
			'refunded_total'         => wc_format_decimal( $order->get_total_refunded(), 2 ),
			'order_currency'         => SV_WC_Order_Compatibility::get_prop( $order, 'currency', 'view' ),
			'payment_method'         => SV_WC_Order_Compatibility::get_prop( $order, 'payment_method' ),
			'shipping_method'        => $order->get_shipping_method(),
			'customer_id'            => $order->get_user_id(),
			'billing_first_name'     => SV_WC_Order_Compatibility::get_prop( $order, 'billing_first_name' ),
			'billing_last_name'      => SV_WC_Order_Compatibility::get_prop( $order, 'billing_last_name' ),
			'billing_full_name'      => $order->get_formatted_billing_full_name(),
			'billing_company'        => SV_WC_Order_Compatibility::get_prop( $order, 'billing_company' ),
			'billing_email'          => SV_WC_Order_Compatibility::get_prop( $order, 'billing_email' ),
			'billing_phone'          => SV_WC_Order_Compatibility::get_prop( $order, 'billing_phone' ),
			'billing_address_1'      => SV_WC_Order_Compatibility::get_prop( $order, 'billing_address_1' ),
			'billing_address_2'      => SV_WC_Order_Compatibility::get_prop( $order, 'billing_address_2' ),
			'billing_postcode'       => SV_WC_Order_Compatibility::get_prop( $order, 'billing_postcode' ),
			'billing_city'           => SV_WC_Order_Compatibility::get_prop( $order, 'billing_city' ),
			'billing_state'          => $this->get_localized_state_for_order( $order, 'billing' ),
			'billing_state_code'     => SV_WC_Order_Compatibility::get_prop( $order, 'billing_state' ),
			'billing_country'        => SV_WC_Order_Compatibility::get_prop( $order, 'billing_country' ),
			'shipping_first_name'    => SV_WC_Order_Compatibility::get_prop( $order, 'shipping_first_name' ),
			'shipping_last_name'     => SV_WC_Order_Compatibility::get_prop( $order, 'shipping_last_name' ),
			'shipping_full_name'     => $order->get_formatted_shipping_full_name(),
			'shipping_company'       => SV_WC_Order_Compatibility::get_prop( $order, 'shipping_company' ),
			'shipping_address_1'     => SV_WC_Order_Compatibility::get_prop( $order, 'shipping_address_1' ),
			'shipping_address_2'     => SV_WC_Order_Compatibility::get_prop( $order, 'shipping_address_2' ),
			'shipping_postcode'      => SV_WC_Order_Compatibility::get_prop( $order, 'shipping_postcode' ),
			'shipping_city'          => SV_WC_Order_Compatibility::get_prop( $order, 'shipping_city' ),
			'shipping_state'         => $this->get_localized_state_for_order( $order, 'shipping' ),
			'shipping_state_code'    => SV_WC_Order_Compatibility::get_prop( $order, 'shipping_state' ),
			'shipping_country'       => SV_WC_Order_Compatibility::get_prop( $order, 'shipping_country' ),
			'customer_note'          => SV_WC_Order_Compatibility::get_prop( $order, 'customer_note' ),
			'shipping_items'         => $is_json && ! empty( $shipping_items ) ? json_encode( $shipping_items ) : implode( ';', $shipping_items ),
			'fee_items'              => $is_json && ! empty( $fee_items ) ? json_encode( $fee_items ) : implode( ';', $fee_items ),
			'tax_items'              => $is_json && ! empty( $tax_items ) ? json_encode( $tax_items ) : implode( ';', $tax_items ),
			'coupon_items'           => $is_json && ! empty( $coupon_items ) ? json_encode( $coupon_items ) : implode( ';', $coupon_items ),
			'refunds'                => $is_json && ! empty( $refunds ) ? json_encode( $refunds ) : implode( ';', $refunds ),
			'order_notes'            => implode( '|', $this->get_order_notes( $order ) ),
			'download_permissions'   => $download_permissions_granted ? 1 : 0,
		);

		if ( 'item' === $this->format_definition['row_type'] ) {

			$new_order_data = array();

			foreach ( $line_items as $item ) {

				$order_data['item_id']           = $item['id'];
				$order_data['item_name']         = $item['name'];
				$order_data['item_product_id']   = $item['product_id'];
				$order_data['item_sku']          = $item['sku'];
				$order_data['item_quantity']     = $item['quantity'];
				$order_data['item_subtotal']     = $item['subtotal'];
				$order_data['item_subtotal_tax'] = $item['subtotal_tax'];
				$order_data['item_total']        = $item['total'];
				$order_data['item_total_tax']    = $item['total_tax'];
				$order_data['item_refunded']     = $item['refunded'];
				$order_data['item_refunded_qty'] = $item['refunded_qty'];
				$order_data['item_meta']         = $item['meta'];

				/**
				 * CSV Order Export Row for One Row per Item.
				 *
				 * Filter the individual row data for the order export
				 *
				 * @since 3.3.0
				 * @param array $order_data {
				 *     order data in key => value format
				 *     to modify the row data, ensure the key matches any of the header keys and set your own value
				 * }
				 * @param array $item
				 * @param \WC_Order $order WC Order object
				 * @param \WC_Customer_Order_CSV_Export_Generator $this, generator instance
				 */
				$new_order_data[] = apply_filters( 'wc_customer_order_csv_export_order_row_one_row_per_item', $order_data, $item, $order, $this );
			}

			$order_data = $new_order_data;
		} else {

			$order_data['line_items'] = $is_json ? json_encode( $line_items ) : implode( ';', $line_items );
		}

		/**
		 * CSV Order Export Row.
		 *
		 * Filter the individual row data for the order export
		 *
		 * @since 3.0.0
		 * @param array $order_data {
		 *     order data in key => value format
		 *     to modify the row data, ensure the key matches any of the header keys and set your own value
		 * }
		 * @param \WC_Order $order WC Order object
		 * @param \WC_Customer_Order_CSV_Export_Generator $this, generator instance
		 */
		return apply_filters( 'wc_customer_order_csv_export_order_row', $order_data, $order, $this );
	}


	/**
	 * Format an item (shipping, fee, line item) as a pipe-delimited string
	 *
	 * @since 4.0.0
	 * @param array $item
	 * @return string
	 */
	private function pipe_delimit_item( $item ) {

		$result = array();

		foreach ( $item as $key => $value ) {

			if ( is_array( $value ) ) {
				$value = ! empty( $array ) ? maybe_serialize( $value ) : '';
			}

			$result[] = $this->escape_reserved_item_chars( $key ) . ':' . $this->escape_reserved_item_chars( $value );
		}

		return implode( '|', $result );
	}


	/**
	 * Get the order notes for given order
	 *
	 * @since 3.0.0
	 * @param WC_Order $order
	 * @return array order notes
	 */
	private function get_order_notes( $order ) {

		$callback = array( 'WC_Comments', 'exclude_order_comments' );

		$args = array(
			'post_id' => SV_WC_Order_Compatibility::get_prop( $order, 'id' ),
			'approve' => 'approve',
			'type'    => 'order_note'
		);

		remove_filter( 'comments_clauses', $callback );

		$notes = get_comments( $args );

		add_filter( 'comments_clauses', $callback );

		$order_notes = array();

		foreach ( $notes as $note ) {

			$order_notes[] = str_replace( array( "\r", "\n" ), ' ', $note->comment_content );
		}

		return $order_notes;
	}


	/**
	 * Get the CSV for customers
	 *
	 * @since 3.0.0
	 * @param array $ids customer IDs to export. also accepts an array of arrays with billing email and
	 *                   order Ids, for guest customers: array( $user_id, array( $billing_email, $order_id ) )
	 * @param bool $include_headers optional. Whether to include CSV column headers in the output or not. Defaults to false
	 * @return string CSV data
	 */
	public function get_customers_csv( $ids, $include_headers = false ) {

		$stream  = fopen( 'php://output', 'w' );
		$headers = $this->get_customers_csv_headers();

		ob_start();

		if ( $include_headers ) {

			$header = $this->get_header();

			if ( null !== $header ) {
				fputs( $stream, $header );
			}
		}

		$customer_data = array();

		// iterate through customers
		foreach ( $ids as $customer_id ) {

			$order_id = null;

			if ( is_array( $customer_id ) ) {
				list( $customer_id, $order_id ) = $customer_id;
			}

			// get data for each customer
			$data = $this->get_customers_csv_row_data( $customer_id, $order_id );

			// skip if customer/data wasn't found
			if ( empty( $data ) ) {
				continue;
			}

			$customer_data[] = $data;

			// data can be an array of arrays when each line item is it's own row
			$first_element = reset( $data );

			if ( is_array( $first_element ) ) {

				// iterate through each line item row and write it
				foreach ( $data as $row ) {

					fputs( $stream, $this->get_row_csv( $row, $headers ) );
				}

			} else {

				// otherwise simply write the single order row
				fputs( $stream, $this->get_row_csv( $data, $headers ) );
			}
		}

		fclose( $stream );

		$csv = ob_get_clean();

		/**
		 * Filter the generated customers CSV
		 *
		 * In 4.0.0 removed the $customers param
		 *
		 * @since 3.8.0
		 * @param string $csv_data The CSV data
		 * @param array $customer_data An array of the customer data to write to to the CSV
		 * @param array $customer_ids The customer ids.
		 */
		return apply_filters( 'wc_customer_order_csv_export_get_customers_csv', $csv, $customer_data, $ids );
	}


	/**
	 * Get the column headers for the customers CSV
	 *
	 * Note that the headers are keyed in column_key => column_name format so that plugins can control the output
	 * format using only the column headers and row data is not required to be in the exact same order, as the row data
	 * is matched on the column key
	 *
	 * @since 3.0.0
	 * @return array column headers in column_key => column_name format
	 */
	public function get_customers_csv_headers() {

		$column_headers = $this->format_definition['columns'];

		/**
		 * CSV Customer Export Column Headers.
		 *
		 * Filter the column headers for the customer export
		 *
		 * @since 3.0.0
		 * @param array $column_headers {
		 *     column headers in key => name format
		 *     to modify the column headers, ensure the keys match these and set your own values
		 * }
		 * @param \WC_Customer_Order_CSV_Export_Generator $this, generator instance
		 */
		return apply_filters( 'wc_customer_order_csv_export_customer_headers', $column_headers, $this );
	}


	/**
	 * Get the customer data for a single CSV row
	 *
	 * Note items are keyed according to the column header keys above so these can be modified using
	 * the provider filter without needing to worry about the array order.
	 *
	 * In 4.0.0 renamed from 'get_customers_csv_row' to 'get_customers_csv_row_data'
	 *
	 * @since 3.0.0
	 * @param int|string $id customer id or email
	 * @param int $order_id optional, a valid order ID for the customer, if available.
	 * @return array|false customer data in the format key => content, or false on failure
	 */
	private function get_customers_csv_row_data( $id, $order_id = null ) {

		$user = is_numeric( $id ) ? get_user_by( 'id', $id ) : get_user_by( 'email', $id );

		// guest, get info from order
		if ( ! $user && is_numeric( $order_id ) ) {

			$order = wc_get_order( $order_id );

			// create blank user
			$user = new stdClass();

			if ( $order ) {

				if ( SV_WC_Plugin_Compatibility::is_wc_version_gte_3_0() ) {
					$order_date = is_callable( array( $order->get_date_created(), 'date' ) ) ? $order->get_date_created()->date( 'Y-m-d H:i:s' ) : null;
				} else {
					$order_date = $order->order_date;
				}

				// set properties on user
				$user->ID                  = 0;
				$user->first_name          = SV_WC_Order_Compatibility::get_prop( $order, 'billing_first_name' );
				$user->last_name           = SV_WC_Order_Compatibility::get_prop( $order, 'billing_last_name' );
				$user->user_login          = '';
				$user->user_email          = SV_WC_Order_Compatibility::get_prop( $order, 'billing_email' );
				$user->user_pass           = '';
				// don't format this date, it will be formatted later
				$user->user_registered     = $order_date;
				$user->billing_first_name  = SV_WC_Order_Compatibility::get_prop( $order, 'billing_first_name' );
				$user->billing_last_name   = SV_WC_Order_Compatibility::get_prop( $order, 'billing_last_name' );
				$user->billing_full_name   = $order->get_formatted_billing_full_name();
				$user->billing_company     = SV_WC_Order_Compatibility::get_prop( $order, 'billing_company' );
				$user->billing_email       = SV_WC_Order_Compatibility::get_prop( $order, 'billing_email' );
				$user->billing_phone       = SV_WC_Order_Compatibility::get_prop( $order, 'billing_phone' );
				$user->billing_address_1   = SV_WC_Order_Compatibility::get_prop( $order, 'billing_address_1' );
				$user->billing_address_2   = SV_WC_Order_Compatibility::get_prop( $order, 'billing_address_2' );
				$user->billing_postcode    = SV_WC_Order_Compatibility::get_prop( $order, 'billing_postcode' );
				$user->billing_city        = SV_WC_Order_Compatibility::get_prop( $order, 'billing_city' );
				$user->billing_state       = $this->get_localized_state_for_order( $order, 'billing' );
				$user->billing_state_code  = SV_WC_Order_Compatibility::get_prop( $order, 'billing_state' );
				$user->billing_country     = SV_WC_Order_Compatibility::get_prop( $order, 'billing_country' );
				$user->shipping_first_name = SV_WC_Order_Compatibility::get_prop( $order, 'shipping_first_name' );
				$user->shipping_last_name  = SV_WC_Order_Compatibility::get_prop( $order, 'shipping_last_name' );
				$user->shipping_full_name  = $order->get_formatted_shipping_full_name();
				$user->shipping_company    = SV_WC_Order_Compatibility::get_prop( $order, 'shipping_company' );
				$user->shipping_address_1  = SV_WC_Order_Compatibility::get_prop( $order, 'shipping_address_1' );
				$user->shipping_address_2  = SV_WC_Order_Compatibility::get_prop( $order, 'shipping_address_2' );
				$user->shipping_postcode   = SV_WC_Order_Compatibility::get_prop( $order, 'shipping_postcode' );
				$user->shipping_city       = SV_WC_Order_Compatibility::get_prop( $order, 'shipping_city' );
				$user->shipping_state      = $this->get_localized_state_for_order( $order, 'shipping' );
				$user->shipping_state_code = SV_WC_Order_Compatibility::get_prop( $order, 'shipping_state' );
				$user->shipping_country    = SV_WC_Order_Compatibility::get_prop( $order, 'shipping_country' );
			}
		}

		// user not found, skip - this can occur when an invalid customer id or email was passed in
		if ( ! $user ) {
			return false;
		}

		$customer_data = array(
			'customer_id'         => $user->ID,
			'first_name'          => $user->first_name,
			'last_name'           => $user->last_name,
			'user_login'          => $user->user_login,
			'email'               => $user->user_email,
			'user_pass'           => $user->user_pass,
			'date_registered'     => $this->format_date( $user->user_registered ),
			'billing_first_name'  => $user->billing_first_name,
			'billing_last_name'   => $user->billing_last_name,
			'billing_full_name'   => $user->billing_full_name,
			'billing_company'     => $user->billing_company,
			'billing_email'       => $user->billing_email,
			'billing_phone'       => $user->billing_phone,
			'billing_address_1'   => $user->billing_address_1,
			'billing_address_2'   => $user->billing_address_2,
			'billing_postcode'    => $user->billing_postcode,
			'billing_city'        => $user->billing_city,
			'billing_state'       => $this->get_localized_state( $user->billing_country, $user->billing_state ),
			'billing_state_code'  => $user->billing_state_code,
			'billing_country'     => $user->billing_country,
			'shipping_first_name' => $user->shipping_first_name,
			'shipping_last_name'  => $user->shipping_last_name,
			'shipping_full_name'  => $user->shipping_full_name,
			'shipping_company'    => $user->shipping_company,
			'shipping_address_1'  => $user->shipping_address_1,
			'shipping_address_2'  => $user->shipping_address_2,
			'shipping_postcode'   => $user->shipping_postcode,
			'shipping_city'       => $user->shipping_city,
			'shipping_state'      => $this->get_localized_state( $user->shipping_country, $user->shipping_state ),
			'shipping_state_code' => $user->shipping_state_code,
			'shipping_country'    => $user->shipping_country,
			'total_spent'         => wc_format_decimal( wc_get_customer_total_spent( $user->ID ), 2 ),
			'order_count'         => wc_get_customer_order_count( $user->ID ),
		);

		/**
		 * CSV Customer Export Row.
		 *
		 * Filter the individual row data for the customer export
		 *
		 * @since 3.0.0
		 * @param array $customer_data {
		 *     order data in key => value format
		 *     to modify the row data, ensure the key matches any of the header keys and set your own value
		 * }
		 * @param \WP_User|object $user WP User object, if available, an object with guest customer data otherwise
		 * @param int $order_id an order ID for the customer, if available
		 * @param \WC_Customer_Order_CSV_Export_Generator $this, generator instance
		 */
		return apply_filters( 'wc_customer_order_csv_export_customer_row', $customer_data, $user, $order_id, $this );
	}


	/**
	 * Returns the localized state for the order.
	 *
	 * TODO: Remove once WC 3.0+ is required {MR 2017-02-23}
	 *
	 * @since 4.2.0
	 *
	 * @param \WC_Order $order
	 * @param string $type state type, either billing or shipping.
	 * @return string
	 */
	protected function get_localized_state_for_order( WC_Order $order, $type ) {

		$country = SV_WC_Order_Compatibility::get_prop( $order, "{$type}_country" );
		$state   = SV_WC_Order_Compatibility::get_prop( $order, "{$type}_state" );

		return $this->get_localized_state( $country, $state );
	}


	/**
	 * Helper to localize state names for countries with numeric state codes
	 *
	 * @since 4.1.0
	 * @param string $country the country for the current customer
	 * @param string $state the state code for the current customer
	 * @return string the localized state name
	 */
	protected function get_localized_state( $country, $state ) {

		// countries that have numeric state codes
		$countries_with_numeric_states = array(
			'JP',
			'BG',
			'CN',
			'TH',
			'TR',
		);

		// only proceed if we need to replace a numeric state code
		if ( ! in_array( $country, $countries_with_numeric_states, true ) ) {
			return $state;
		}

		$state_name = $state;

		// get a state list for states the store sells to
		$states = WC()->countries->get_states();

		if ( ! empty ( $states[ $country ] ) && isset( $states[ $country ][ $state ] ) ) {
			$state_name = $states[ $country ][ $state ];
		}

		return $state_name;
	}


	/**
	 * Helper to run all dates through a formatting filter for easy format changes.
	 *
	 * @since 4.3.4
	 *
	 * @param string $date the current date output
	 * @return string the formatted date output
	 */
	private function format_date( $date ) {

		/**
		 * Allows actors to adjust the format of all dates in the export file.
		 *
		 * @since 4.3.4
		 *
		 * @param string $date the formatted date
		 * @param \WC_Customer_Order_CSV_Export_Generator $generator the generator instance
		 */
		return apply_filters( 'wc_customer_order_csv_export_format_date', $date, $this );
	}


	/**
	 * Escape reserved meta chars in a string (commas and equals signs)
	 *
	 * Will also replace colons and newlines with a placeholder, which should
	 * be replaced later with actual characters.
	 *
	 * @since 3.12.0
	 * @param string $input Input string
	 * @return string
	 */
	public function escape_reserved_meta_chars( $input ) {

		// commas delimit meta fields, equals sign delimits key-value pairs,
		// colons need to be replaced with a placeholder so that we can safely
		// replace the key-value separator (colon + space) with our own (equals sign)
		$input = str_replace(
			array( '=',  ',',  ':' ),
			array( '\=', '\,', '[INSERT_COLON_HERE]' ),
			$input
		);

		// newlines are legal in CSV, but we want to remove the newlines generated
		// by WC_Order_Item_Meta::display(), so we replace them with a placeholder temporarily
		return str_replace( array( "\r\n", "\r", "\n" ), '[INSERT_NEWLINE_HERE]', $input );
	}


	/**
	 * Escape reserved item chars in a string (semicolons, colons and pipes)
	 *
	 * @since 3.12.0
	 * @param string $input Input string
	 * @return string
	 */
	private function escape_reserved_item_chars( $input ) {

		// colons separate key-value pairs, pipes separate fields/properties,
		// and semicolons separate line items themselves
		return str_replace(
			array( ':',  '|',  ';' ),
			array( '\:', '\|', '\;' ),
			$input
		);
	}


	/**
	 * Escape leading equals, plus, minus and @ signs with a single quote to
	 * prevent CSV injections
	 *
	 * @since 4.0.0
	 * @see http://www.contextis.com/resources/blog/comma-separated-vulnerabilities/
	 * @param string $value Potentially unsafe value
	 * @return string Value with any leading special characters escaped
	 */
	private function escape_cell_formulas( $value ) {

		$untrusted = SV_WC_Helper::str_starts_with( $value, '=' ) ||
		             SV_WC_Helper::str_starts_with( $value, '+' ) ||
		             SV_WC_Helper::str_starts_with( $value, '-' ) ||
		             SV_WC_Helper::str_starts_with( $value, '@' );

		if ( $untrusted ) {
			$value = "'" . $value;
		}

		return $value;
	}


	/**
	 * Get the CSV row for the given row data
	 *
	 * This is abstracted so the provided data can be matched to the CSV headers
	 * set and the CSV delimiter and enclosure can be controlled from a single method
	 *
	 * @since 3.11.3
	 * @param array $row_data Row data
	 * @param array $headers CSV column headers
	 * @return string generated CSV row
	 */
	private function get_row_csv( $row_data, $headers ) {

		if ( empty( $row_data ) ) {
			return '';
		}

		$data = array();

		foreach ( $headers as $header_key => $_ ) {

			if ( ! isset( $row_data[ $header_key ] ) ) {
				$row_data[ $header_key ] = '';
			}

			$value = $row_data[ $header_key ];
			$value = $this->escape_cell_formulas( $value );

			$data[] = $value;
		}

		/**
		 * Allow actors to change the generated CSV row
		 *
		 * Actors may return null to remove the generated row from the final
		 * output completely. In other cases, careful attention must be paid to
		 * not remove the line ending characters from the generated CSV.
		 *
		 * @since 4.0.0
		 * @param string $csv Generated CSV for the object (customer, order)
		 * @param array $data Input data used to generate the CSV
		 * @param \WC_Customer_Order_CSV_Export_Generator $this - generator class instance
		 */
		return apply_filters( 'wc_customer_order_csv_export_generated_csv_row', $this->array_to_csv_row( $data ), $data, $this );
	}


	/**
	 * Take an array of data and return it as a CSV-formatted string
	 *
	 * @since 4.0.0
	 * @param array $data
	 * @return string
	 */
	private function array_to_csv_row( $data ) {

		ob_start();

		$stream = fopen( 'php://output', 'w' );

		fputcsv( $stream, $data, $this->delimiter, $this->enclosure );

		fclose( $stream );

		return ob_get_clean();
	}


	/**
	 * Get CSV header row
	 *
	 * @since 4.0.0
	 * @return string
	 */
	public function get_header() {

		$header = '';

		switch ( $this->export_type ) {

			case 'orders':
				$headers = $this->get_orders_csv_headers();
			break;

			case 'customers':
				$headers = $this->get_customers_csv_headers();
			break;

			default:

				/**
				 * Allow actors to provide header data for unknown export types
				 *
				 * @since 4.0.0
				 * @param array $headers
				 */
				$headers = apply_filters( 'wc_customer_order_csv_export_' . $this->export_type . '_headers', array() );
			break;
		}

		/**
		 * CSV BOM (Byte order mark).
		 *
		 * Enable adding a BOM to the exported CSV
		 *
		 * In 4.0.0 added $export_type param, moved from __construct() to get_header()
		 *
		 * @since 3.0.0
		 * @param bool $enable_bom true to add the BOM, false otherwise. Defaults to false.
		 * @param string $export_type Export type, either `orders`, `customers` or a custom type
		 */
		if ( apply_filters( 'wc_customer_order_csv_export_enable_bom', false, $this, $this->export_type ) ) {

			$header .= ( chr(0xEF) . chr(0xBB) . chr(0xBF) );
		}

		if ( empty( $headers ) ) {
			return $header;
		}

		return $header . $this->get_row_csv( $headers, $headers );
	}


	/**
	 * Get output for the provided export type
	 *
	 * @since 4.0.0
	 * @param array $ids
	 * @return string
	 */
	public function get_output( $ids ) {

		switch ( $this->export_type ) {

			case 'orders':
				return $this->get_orders_csv( $ids );

			case 'customers':
				return $this->get_customers_csv( $ids );

			default:
				/**
				 * Allow actors to provide output for custom export types
				 *
				 * @since 4.0.0
				 * @param string $output defaults to empty string
				 * @param array $ids object IDs to export
				 * @param string $export_format export format, if any
				 */
				return apply_filters( 'wc_customer_order_csv_export_get_' . $this->export_type . '_csv', '', $ids, $this->export_format );
		}
	}


}
