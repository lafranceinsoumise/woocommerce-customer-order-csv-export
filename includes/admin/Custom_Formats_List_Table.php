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

use SkyVerge\WooCommerce\CSV_Export\Export_Formats\Custom_Export_Format_Definition;

defined( 'ABSPATH' ) or exit;

if ( ! class_exists( '\WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

/**
 * Customer/Order CSV Export Custom Formats List Table
 *
 * Lists custom export formats
 *
 * @since 4.7.0
 */
class Custom_Formats_List_Table extends \WP_List_Table {


	/** @var string the export type, `orders`, `customers` or `coupons` */
	private $export_type;


	/**
	 * Setups list table.
	 *
	 * @since 4.7.0
	 *
	 * @param array $args
	 */
	public function __construct( $args = [] ) {

		parent::__construct( [
			'singular' => 'custom format',
			'plural'   => 'custom formats',
			'ajax'     => false
		] );
	}


	/**
	 * Sets the export type.
	 *
	 * @since 4.7.0
	 *
	 * @param string $export_type
	 */
	public function set_export_type( $export_type ) {
		$this->export_type = $export_type;
	}


	/**
	 * Returns column titles.
	 *
	 * @since 4.7.0
	 *
	 * @return array
	 */
	public function get_columns() {

		$columns = [
			'name' => esc_html__( 'Format name', 'woocommerce-customer-order-csv-export' ),
		];

		if ( $this->export_type === 'orders' ) {
			$columns['row_type']     = esc_html__( 'Rows represent', 'woocommerce-customer-order-csv-export' );
			$columns['items_format'] = esc_html__( 'Cell format', 'woocommerce-customer-order-csv-export' );
		}

		$columns['delimiter']      = esc_html__( 'Delimiter', 'woocommerce-customer-order-csv-export' );
		$columns['format_actions'] = esc_html__( 'Actions', 'woocommerce-customer-order-csv-export' );

		/**
		 * Filters the columns in the custom formats list table.
		 *
		 * @since 4.7.0
		 *
		 * @param array $columns the custom formats list columns
		 */
		return apply_filters( 'wc_customer_order_csv_export_admin_export_custom_formats_list_columns', $columns );
	}


	/**
	 * Gets column content.
	 *
	 * @since 4.7.0
	 *
	 * @param Custom_Export_Format_Definition $custom_format custom format definition
	 * @param string $column_name the column name
	 * @return string the column content
	 */
	public function column_default( $custom_format, $column_name ) {

		switch ( $column_name ) {

			case 'name':

				$edit_url = wp_nonce_url( add_query_arg( [
					'edit_custom_format' => 1,
					'custom_format_key'  => $custom_format->get_key(),
				] ) );

				return '<a href="' . esc_url( $edit_url ) . '">' . esc_html( $custom_format->get_name() ) . '</a>';

			break;

			case 'row_type':

				$row_type_options = wc_customer_order_csv_export()->get_admin_instance()->get_custom_formats_admin_instance()->get_row_type_options();
				$row_type         = isset( $row_type_options[ $custom_format->get_row_type() ] ) ? $row_type_options[ $custom_format->get_row_type() ] : '';

				return esc_html( $row_type );

			break;

			case 'items_format':

				$items_format_options = wc_customer_order_csv_export()->get_admin_instance()->get_custom_formats_admin_instance()->get_items_format_options();
				$items_format         = isset( $items_format_options[ $custom_format->get_items_format() ] ) ? $items_format_options[ $custom_format->get_items_format() ] : '';

				return esc_html( $items_format );

			break;

			case 'delimiter':

				$delimiter_options = wc_customer_order_csv_export()->get_admin_instance()->get_custom_formats_admin_instance()->get_delimiter_options();
				$delimiter         = isset( $delimiter_options[ $custom_format->get_delimiter() ] ) ? $delimiter_options[ $custom_format->get_delimiter() ] : '';

				return esc_html( $delimiter );

			break;

			default:
				/**
				 * Allows actors adding custom columns to include their own column data.
				 *
				 * @since 4.7.0
				 *
				 * @param string $column_name the column name
				 * @param Custom_Export_Format_Definition $custom_format the custom format
				 *
				 * @param string $content the column content
				 */
				return apply_filters( 'wc_customer_order_csv_export_admin_custom_formats_list_custom_column', '', $column_name, $custom_format );
		}
	}


	/**
	 * Outputs actions column content for the given format.
	 *
	 * @since 4.7.0
	 *
	 * @param Custom_Export_Format_Definition $custom_format custom format definition
	 */
	public function column_format_actions( $custom_format ) {
		global $current_section;

		?>
		<p>
			<?php
			$actions = [];

			$delete_url        = wp_nonce_url( add_query_arg( [
				'delete_custom_format' => 1,
				'custom_format_key'    => $custom_format->get_key(),
			] ) );
			$actions['delete'] = [
				'url'    => $delete_url,
				'name'   => esc_html__( 'Delete', 'woocommerce-customer-order-csv-export' ),
				'action' => 'delete',
			];

			// check if the custom format is selected in the Settings
			$current_export_format = get_option( 'wc_customer_order_csv_export_' . $current_section . '_format', 'default' );
			if ( 'custom' === $current_export_format ) {
				$current_export_format = get_option( 'wc_customer_order_csv_export_' . $current_section . '_custom_format', 'custom' );
			}

			if ( $current_export_format === $custom_format->get_key() ) {
				$actions['delete']['class'] = [ 'disabled' ];
				$actions['delete']['tip']   = __( 'This custom format is selected in the Settings and cannot be deleted. Please switch the export format to another format first.', 'woocommerce-customer-order-csv-export' );
			}

			$edit_url        = wp_nonce_url( add_query_arg( [
				'edit_custom_format' => 1,
				'custom_format_key'  => $custom_format->get_key(),
			] ) );
			$actions['edit'] = [
				'url'    => $edit_url,
				'name'   => esc_html__( 'Manage', 'woocommerce-customer-order-csv-export' ),
				'action' => 'edit',
				'class'  => 'button-primary',
			];

			/**
			 * Allows actors to change the available actions for a custom format in Custom Formats List
			 *
			 * @since 4.7.0
			 *
			 * @param array $actions
			 * @param Custom_Export_Format_Definition $custom_format
			 */
			$actions = apply_filters( 'wc_customer_order_csv_export_admin_custom_format_actions', $actions, $custom_format );

			foreach ( $actions as $action ) {

				$classes   = isset( $action['class'] ) ? (array) $action['class'] : [];
				$classes[] = $action['action'];

				$attributes = [];

				// if the action has a tooltip set
				if ( isset( $action['tip'] ) && $action['tip'] ) {
					$classes[]           = 'tip';
					$attributes['title'] = $action['tip'];
				}

				// build the attributes
				foreach ( $attributes as $attribute => $value ) {
					$attributes[] = esc_attr( $attribute ) . '="' . esc_attr( $value ) . '"';
					unset( $attributes[ $attribute ] );
				}

				// print the button
				printf(
					in_array( 'disabled', $classes, true ) ? '<a class="button %2$s" %3$s>%4$s</a>' : '<a href="%1$s" class="button %2$s" %3$s>%4$s</a>',
					esc_url( $action['url'] ),
					implode( ' ', array_map( 'sanitize_html_class', $classes ) ),
					implode( ' ', $attributes ),
					esc_html( $action['name'] )
				);
				// print spacing
				echo '<span>&nbsp;</span>';
			}
			?>
		</p>
		<?php
	}


	/**
	 * Prepares custom formats for display.
	 *
	 * @since 4.7.0
	 */
	public function prepare_items() {

		// set column headers manually, see https://codex.wordpress.org/Class_Reference/WP_List_Table#Extended_Properties
		$columns               = $this->get_columns();
		$hidden                = [];
		$sortable              = [];
		$this->_column_headers = [ $columns, $hidden, $sortable ];

		$this->items = wc_customer_order_csv_export()->get_formats_instance()->get_custom_format_definitions( $this->export_type );
	}


	/**
	 * Returns the HTML to display when there are no custom formats.
	 * @see WP_List_Table::no_items()
	 *
	 * @since 4.7.0
	 */
	public function no_items() {
		?>
		<p><?php esc_html_e( 'Custom formats will appear here.', 'woocommerce-customer-order-csv-export' ); ?></p>
		<?php
	}


}
