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
 * @package     WC-Customer-Order-CSV-Export/Admin
 * @author      SkyVerge
 * @copyright   Copyright (c) 2012-2016, SkyVerge, Inc.
 * @license     http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

defined( 'ABSPATH' ) or exit;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

/**
 * Customer/Order CSV Export List Table
 *
 * Lists recently exported files
 *
 * @since 4.0.0
 */
 class WC_Customer_Order_CSV_Export_List_Table extends WP_List_Table {


	/** @var array associative array of translated export status labels */
	private $statuses;


	/**
	 * Constructor - setup list table
	 *
	 * @since 4.0.0
	 * @param array $args
	 * @return \WC_Customer_Order_CSV_Export_List_Table
	 */
	public function __construct( $args = array() ) {

		parent::__construct( array(
			'singular' => 'export',
			'plural'   => 'exports',
			'ajax'     => false
		) );

		$this->statuses = array(
			'queued'     => esc_html__( 'Queued', 'woocommerce-customer-order-csv-export' ),
			'processing' => esc_html__( 'Processing', 'woocommerce-customer-order-csv-export' ),
			'completed'  => esc_html__( 'Completed', 'woocommerce-customer-order-csv-export' ),
			'failed'     => esc_html__( 'Failed', 'woocommerce-customer-order-csv-export' ),
		);
	}


	/**
	 * Set column titles
	 *
	 * @since 4.0.0
	 * @return array
	 */
	public function get_columns() {

		$columns = array(
			'cb'              => '<input type="checkbox" />',
			'export_status'   => '<span class="status_head tips" data-tip="' . esc_attr__( 'Export Status', 'woocommerce-customer-order-csv-export' ) . '">' . esc_attr__( 'Export Status', 'woocommerce-customer-order-csv-export' ) . '</span>',
			'transfer_status' => '<span class="transfer_status_head tips" data-tip="' . esc_attr__( 'Transfer Status', 'woocommerce-customer-order-csv-export' ) . '">' . esc_attr__( 'Transfer Status', 'woocommerce-customer-order-csv-export' ) . '</span>',
			'export_type'     => esc_html__( 'Type', 'woocommerce-customer-order-csv-export' ),
			'invocation'      => esc_html__( 'Invocation', 'woocommerce-customer-order-csv-export' ),
			'filename'        => esc_html__( 'File name', 'woocommerce-customer-order-csv-export' ),
			'export_date'     => esc_html__( 'Date', 'woocommerce-customer-order-csv-export' ),
			'file_actions'    => esc_html__( 'Actions', 'woocommerce-customer-order-csv-export' ),
		);

		$auto_exports_enabled = false;

		foreach ( array( 'orders', 'customers' ) as $export_type ) {

			if ( $auto_export_method = wc_customer_order_csv_export()->get_methods_instance()->get_auto_export_method( $export_type ) ) {

				if ( 'local' !== $auto_export_method ) {
					$auto_exports_enabled = true;
					break;
				}
			}
		}

		// hide transfer status column if no auto exports have been configured
		if ( ! $auto_exports_enabled ) {
			unset( $columns['transfer_status'] );
		}

		return $columns;
	}


	/**
	 * Get column content
	 *
	 * @since 4.0.0
	 * @param stdClass $export
	 * @param string $column_name
	 * @return array
	 */
	public function column_default( $export, $column_name ) {

		switch ( $column_name ) {

			case 'export_status':

				$label = $this->statuses[ $export->status ];
				return sprintf( '<mark class="%1$s tips" data-tip="%2$s">%3$s</mark>', sanitize_key( $export->status ), $label, $label );

			break;

			case 'transfer_status':

				if ( ! $export->transfer_status ) {

					return __( 'N/A', 'woocommerce-customer-order-csv-export' );

				} else {

					$label = $this->statuses[ $export->transfer_status ];
					return sprintf( '<mark class="%1$s tips" data-tip="%2$s">%3$s</mark>', sanitize_key( $export->transfer_status ), $label, $label );
				}

			break;

			case 'invocation':

				return 'auto' === $export->invocation ? esc_html__( 'Auto', 'woocommerce-customer-order-csv-export' ) : esc_html__( 'Manual', 'woocommerce-customer-order-csv-export' );

			break;

			case 'filename':

				$filename = basename( $export->file_path );

				// strip random part from filename, which is prepended to the filename and
				// separated with a dash
				$filename = substr( $filename, strpos( $filename, '-' ) + 1 );

				return $filename;
			break;

			case 'export_type':

				if ( 'orders' === $export->type ) {

					return esc_html__( 'Orders', 'woocommerce-customer-order-csv-export' );

				} elseif ( 'customers' === $export->type ) {

					return esc_html__( 'Customers', 'woocommerce-customer-order-csv-export' );
				}
			break;

			case 'export_date':
				return date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $export->created_at ) );
			break;
		}
	}


	/**
	 * Output actions column content for the given export
	 *
	 * @since 4.0.0
	 * @param stdClass $export
	 */
	public function column_file_actions( $export ) {

		?><p>
			<?php
				$actions = array();

				if ( 'completed' === $export->status ) {

					$download_url = add_query_arg( array(
						'download_exported_csv_file' => 1,
						'export_id'                  => $export->id,
					), admin_url() );

					$actions['download'] = array(
						'url'    => $download_url,
						'name'   => esc_html__( 'Download', 'woocommerce-customer-order-csv-export' ),
						'action' => 'download'
					);

					if ( $auto_export_method = wc_customer_order_csv_export()->get_methods_instance()->get_auto_export_method( $export->type ) ) {

						if ( 'local' !== $auto_export_method ) {

							$label = wc_customer_order_csv_export()->get_methods_instance()->get_export_method_label( $auto_export_method );

							$transfer_url = wp_nonce_url( admin_url(), 'transfer-export' );
							$transfer_url = add_query_arg( array(
								'transfer_csv_export' => 1,
								'export_id'           => $export->id,
							), $transfer_url );

							$actions['transfer'] = array(
								'url'    => $transfer_url,
								/* translators: Placeholders: %s - via [method], full example: Send via Email */
								'name'   => sprintf( esc_html__( 'Send %s', 'woocommerce-customer-order-csv-export' ), $label ),
								'action' => 'email' === $auto_export_method ? 'email' : 'transfer',
							);
						}
					}

				}

				$delete_url = wp_nonce_url( admin_url(), 'delete-export' );
				$delete_url = add_query_arg( array(
					'delete_csv_export' => 1,
					'export_id'         => $export->id,
				), $delete_url );

				$done = in_array( $export->status, array( 'completed', 'failed' ), true );

				$actions['delete'] = array(
					'url'    => $delete_url,
					'name'   => $done ? esc_html__( 'Delete', 'woocommerce-customer-order-csv-export' ) : esc_html__( 'Cancel', 'woocommerce-customer-order-csv-export' ),
					'action' => $done ? 'delete' : 'cancel',
				);

				/**
				 * Allow actors to change the available actions for an export in Exports List
				 *
				 * @since 4.0.0
				 * @param array $actions
				 * @param stdClass $export
				 */
				$actions = apply_filters( 'wc_customer_order_csv_export_admin_export_actions', $actions, $export );

				foreach ( $actions as $action ) {
					printf( '<a class="button tips %1$s" href="%2$s" data-tip="%3$s">%4$s</a>', esc_attr( $action['action'] ), esc_url( $action['url'] ), esc_attr( $action['name'] ), esc_attr( $action['name'] ) );
				}
			?>
		</p><?php

	}


	/**
	 * Handles the checkbox column output.
	 *
	 * @since 4.0.0
	 * @param stdClass $export
	 */
	public function column_cb( $export ) {

		if ( current_user_can( 'manage_woocommerce' ) ) : ?>
			<label class="screen-reader-text" for="cb-select-<?php echo sanitize_html_class( $export->id ); ?>"><?php esc_html_e( 'Select export' ); ?></label>
			<input id="cb-select-<?php echo sanitize_html_class( $export->id ); ?>" type="checkbox" name="export[]" value="<?php echo esc_attr( $export->id ); ?>" />
			<div class="locked-indicator"></div>
		<?php endif;
	}


	/**
	 * Prepare exported files for display
	 *
	 * @since 4.0.0
	 */
	public function prepare_items() {

		global $wpdb;

		// set column headers manually, see https://codex.wordpress.org/Class_Reference/WP_List_Table#Extended_Properties
		$columns               = $this->get_columns();
		$hidden                = array();
		$sortable              = array();
		$this->_column_headers = array( $columns, $hidden, $sortable );

		$this->items = wc_customer_order_csv_export()->get_export_handler_instance()->get_exports();
	}


	/**
	 * The HTML to display when there are no exported files
	 *
	 * @see WP_List_Table::no_items()
	 * @since 4.0.0
	 */
	public function no_items() {
		?>
		<p><?php esc_html_e( 'Exported files will appear here. Files are stored for 14 days after the export.', 'woocommerce-shipwire' ); ?></p>
		<?php
	}


	/**
	 * Get an associative array ( option_name => option_title ) with the list
	 * of bulk actions available on this table.
	 *
	 * @since 4.0.0
	 * @return array
	 */
	protected function get_bulk_actions() {

		return array(
			'delete' => esc_html__( 'Delete', 'woocommerce-customer-order-csv-export' ),
		);
	}

}
