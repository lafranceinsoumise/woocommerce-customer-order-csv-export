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
	public function __construct( $args = [] ) {

		parent::__construct( [
			'singular' => 'export',
			'plural'   => 'exports',
			'ajax'     => false
		] );

		$this->statuses = [
			'queued'     => esc_html__( 'Queued', 'woocommerce-customer-order-csv-export' ),
			'processing' => esc_html__( 'Processing', 'woocommerce-customer-order-csv-export' ),
			'completed'  => esc_html__( 'Completed', 'woocommerce-customer-order-csv-export' ),
			'failed'     => esc_html__( 'Failed', 'woocommerce-customer-order-csv-export' ),
			'paused'     => esc_html__( 'Paused', 'woocommerce-customer-order-csv-export' )
		];
	}


	/**
	 * Set column titles
	 *
	 * @since 4.0.0
	 * @return array
	 */
	public function get_columns() {

		$columns = [
			'cb'              => '<input type="checkbox" />',
			'export_status'   => '<span class="status_head tips" data-tip="' . esc_attr__( 'Export Status', 'woocommerce-customer-order-csv-export' ) . '">' . esc_attr__( 'Export Status', 'woocommerce-customer-order-csv-export' ) . '</span>',
			'transfer_status' => '<span class="transfer_status_head tips" data-tip="' . esc_attr__( 'Transfer Status', 'woocommerce-customer-order-csv-export' ) . '">' . esc_attr__( 'Transfer Status', 'woocommerce-customer-order-csv-export' ) . '</span>',
			'export_type'     => esc_html__( 'Type', 'woocommerce-customer-order-csv-export' ),
			'invocation'      => esc_html__( 'Invocation', 'woocommerce-customer-order-csv-export' ),
			'filename'        => esc_html__( 'File name', 'woocommerce-customer-order-csv-export' ),
			'export_date'     => esc_html__( 'Date', 'woocommerce-customer-order-csv-export' ),
			'file_actions'    => esc_html__( 'Actions', 'woocommerce-customer-order-csv-export' ),
		];

		$auto_exports_enabled = false;

		foreach ( [ 'orders', 'customers', 'coupons' ] as $export_type ) {

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

		/**
		 * Filters the columns in the export list table.
		 *
		 * @since 4.4.5
		 *
		 * @param array $columns the export list columns
		 * @param bool $auto_exports_enabled true if automated exports are enabled
		 */
		return apply_filters( 'wc_customer_order_csv_export_admin_export_list_columns', $columns, $auto_exports_enabled );
	}


	/**
	 * Gets column content.
	 *
	 * @since 4.0.0
	 *
	 * @param object $export_job the export job object
	 * @param string $column_name the column name
	 * @return string the column content
	 */
	public function column_default( $export_job, $column_name ) {

		$export = wc_customer_order_csv_export_get_export( $export_job );

		if ( ! $export ) {
			return '';
		}

		switch ( $column_name ) {

			case 'export_status':

				$status = 'processing' === $export->get_status() && wc_customer_order_csv_export()->is_batch_processing_enabled() ? 'paused' : $export->get_status();

				$label = $this->statuses[ $status ];

				return sprintf( '<mark class="%1$s tips" data-tip="%2$s">%3$s</mark>', sanitize_key( $status ), $label, $label );

			break;

			case 'transfer_status':

				if ( ! $export->get_transfer_status() ) {

					return __( 'N/A', 'woocommerce-customer-order-csv-export' );

				} else {

					$label = $this->statuses[ $export->get_transfer_status() ];
					return sprintf( '<mark class="%1$s tips" data-tip="%2$s">%3$s</mark>', sanitize_key( $export->get_transfer_status() ), $label, $label );
				}

			break;

			case 'invocation':

				return 'auto' === $export->get_invocation() ? esc_html__( 'Auto', 'woocommerce-customer-order-csv-export' ) : esc_html__( 'Manual', 'woocommerce-customer-order-csv-export' );

			break;

			case 'filename':

				return esc_html( $export->get_filename() );

			break;

			case 'export_type':

				if ( 'orders' === $export->get_type() ) {

					return esc_html__( 'Orders', 'woocommerce-customer-order-csv-export' );

				} elseif ( 'customers' === $export->get_type() ) {

					return esc_html__( 'Customers', 'woocommerce-customer-order-csv-export' );

				} elseif ( 'coupons' === $export->get_type() ) {

					return esc_html__( 'Coupons', 'woocommerce-customer-order-csv-export' );
				}
			break;

			case 'export_date':
				return date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $export->get_created_at() ) );
			break;

			default:

				/**
				 * Allow actors adding custom columns to include their own column data.
				 *
				 * @since 4.4.5
				 *
				 * @param string $content the column content
				 * @param string $column_name the column name
				 * @param \stdClass $export the export job
				 */
				return apply_filters( 'wc_customer_order_csv_export_admin_export_list_custom_column', '', $column_name, $export );
		}
	}


	/**
	 * Outputs actions column content for the given export.
	 *
	 * @since 4.0.0
	 *
	 * @param object $export export job object
	 */
	public function column_file_actions( $export ) {

		$export = wc_customer_order_csv_export_get_export( $export );

		if ( ! $export ) {
			return;
		}

		?><p>
			<?php
				$actions = [];

				if ( 'completed' === $export->get_status() ) {

					$download_url = wp_nonce_url( admin_url(), 'download-export' );

					$download_url = add_query_arg( [
						'download_exported_csv_file' => 1,
						'export_id'                  => $export->get_id(),
					], $download_url );

					$actions['download'] = [
						'url'    => $download_url,
						'name'   => esc_html__( 'Download', 'woocommerce-customer-order-csv-export' ),
						'action' => 'download'
					];

					if ( $auto_export_method = wc_customer_order_csv_export()->get_methods_instance()->get_auto_export_method( $export->get_type() ) ) {

						if ( 'local' !== $auto_export_method ) {

							$label = wc_customer_order_csv_export()->get_methods_instance()->get_export_method_label( $auto_export_method );

							$transfer_url = wp_nonce_url( admin_url(), 'transfer-export' );
							$transfer_url = add_query_arg( [
								'transfer_csv_export' => 1,
								'export_id'           => $export->get_id(),
							], $transfer_url );

							$actions['transfer'] = [
								'url'    => $transfer_url,
								/* translators: Placeholders: %s - via [method], full example: Send via Email */
								'name'   => sprintf( esc_html__( 'Send %s', 'woocommerce-customer-order-csv-export' ), $label ),
								'action' => 'email' === $auto_export_method ? 'email' : 'transfer',
							];
						}
					}

				} elseif ( 'processing' === $export->get_status() && wc_customer_order_csv_export()->is_batch_processing_enabled() ) {

					$actions['resume'] = [
						'name'   => __( 'Resume', 'woocommerce-customer-order-csv-export' ),
						'action' => 'resume',
						'url'    => '#',
					];
				}

				$delete_url = wp_nonce_url( admin_url(), 'delete-export' );
				$delete_url = add_query_arg( [
					'delete_csv_export' => 1,
					'export_id'         => $export->get_id(),
				], $delete_url );

				$done = in_array( $export->get_status(), [ 'completed', 'failed' ], true );

				$actions['delete'] = [
					'url'    => $delete_url,
					'name'   => $done ? esc_html__( 'Delete', 'woocommerce-customer-order-csv-export' ) : esc_html__( 'Cancel', 'woocommerce-customer-order-csv-export' ),
					'action' => $done ? 'delete' : 'cancel',
				];

				/**
				 * Allow actors to change the available actions for an export in Exports List
				 *
				 * @since 4.0.0
				 * @param array $actions
				 * @param stdClass $export
				 */
				$actions = apply_filters( 'wc_customer_order_csv_export_admin_export_actions', $actions, $export );

				foreach ( $actions as $action ) {
					printf( '<a class="button tips %1$s" href="%2$s" data-tip="%3$s" data-export-id="%4$s">%5$s</a>', esc_attr( $action['action'] ), esc_url( $action['url'] ), esc_attr( $action['name'] ), esc_attr( $export->get_id() ), esc_attr( $action['name'] ) );
				}
			?>
		</p><?php

	}


	/**
	 * Handles the checkbox column output.
	 *
	 * @since 4.0.0
	 *
	 * @param object $export export job object
	 */
	public function column_cb( $export ) {

		$export = wc_customer_order_csv_export_get_export( $export );

		if ( ! $export ) {
			return;
		}

		if ( current_user_can( 'manage_woocommerce_csv_exports' ) ) : ?>
			<label
                    class="screen-reader-text"
                    for="cb-select-<?php echo sanitize_html_class( $export->get_id() ); ?>"
            ><?php esc_html_e( 'Select export' ); ?></label>
			<input
                    id="cb-select-<?php echo sanitize_html_class( $export->get_id() ); ?>"
                    type="checkbox"
                    name="export[]"
                    value="<?php echo esc_attr( $export->get_id() ); ?>"
            />
			<div class="locked-indicator"></div>
		<?php endif;
	}


	/**
	 * Prepare exported files for display
	 *
	 * @since 4.0.0
	 */
	public function prepare_items() {

		// set column headers manually, see https://codex.wordpress.org/Class_Reference/WP_List_Table#Extended_Properties
		$columns               = $this->get_columns();
		$hidden                = [];
		$sortable              = [];
		$this->_column_headers = [ $columns, $hidden, $sortable ];

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

		return [
			'delete' => esc_html__( 'Delete', 'woocommerce-customer-order-csv-export' ),
		];
	}

}
