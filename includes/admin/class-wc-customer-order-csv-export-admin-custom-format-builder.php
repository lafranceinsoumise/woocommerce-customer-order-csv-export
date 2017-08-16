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
 * @package     WC-Customer-Order-CSV-Export/Admin
 * @author      SkyVerge
 * @copyright   Copyright (c) 2012-2017, SkyVerge, Inc.
 * @license     http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

defined( 'ABSPATH' ) or exit;

/**
 * Customer/Order CSV Export Admin Column Mapper Class
 *
 * Dedicated class for admin column mapping settings
 *
 * @since 4.0.0
 */
class WC_Customer_Order_CSV_Export_Admin_Custom_Format_Builder {


	/**
	 * Setup admin custom format mapper class
	 *
	 * @since 4.0.0
	 */
	public function __construct() {

		// Render a custom field mapper form control when using woocommerce_admin_fields()
		add_action( 'woocommerce_admin_field_wc_customer_order_csv_export_field_mapping', array( $this, 'render_field_mapping' ) );

		add_filter( 'woocommerce_admin_settings_sanitize_option_wc_customer_order_csv_export_orders_custom_format_delimiter',    array( $this, 'restore_tab_value' ), 10, 3 );
		add_filter( 'woocommerce_admin_settings_sanitize_option_wc_customer_order_csv_export_customers_custom_format_delimiter', array( $this, 'restore_tab_value' ), 10, 3 );
	}


	/**
	 * Get sections
	 *
	 * @since 4.0.0
	 * @return array
	 */
	public function get_sections() {

		$sections = array(
			'orders'    => __( 'Orders', 'woocommerce-customer-order-csv-export' ),
			'customers' => __( 'Customers', 'woocommerce-customer-order-csv-export' )
		);

		/**
		 * Allow actors to change the sections for field mapper
		 *
		 * @since 4.0.0
		 * @param array $sections
		 */
		return apply_filters( 'wc_customer_order_csv_export_custom_format_builder_sections' , $sections );
	}


	/**
	 * Returns settings array for use by output/save functions
	 *
	 * @since 4.0.0
	 * @param string $section_id
	 * @return array
	 */
	public static function get_settings( $section_id = null ) {

		$settings = array(

			'orders' => array(

				array(
					'name' => __( 'Format Options', 'woocommerce-customer-order-csv-export' ),
					'type' => 'title',
				),

				array(
					'id'       => 'wc_customer_order_csv_export_orders_custom_format_row_type',
					'name'     => __( 'A row represents', 'woocommerce-customer-order-csv-export' ),
					'desc_tip' => __( 'Choose whether a single row in CSV should represent a full, single order or a single line item for an order.', 'woocommerce-customer-order-csv-export' ),
					'type'     => 'select',
					'options'  => array(
						'order' => __( 'A single order', 'woocommerce-customer-order-csv-export' ),
						'item'  => __( 'A single line item', 'woocommerce-customer-order-csv-export' ),
					),
					'default' => 'order',
					'class'   => 'wc-enhanced-select js-row-type',
				),

				array(
					'id'       => 'wc_customer_order_csv_export_orders_custom_format_items_format',
					'name'     => __( 'Items columns should use', 'woocommerce-customer-order-csv-export' ),
					'desc_tip' => __( 'Choose whether items columns (line items, shipping items, fee items, etc) should be pipe-delimited or JSON-formatted.', 'woocommerce-customer-order-csv-export' ),
					'type'     => 'select',
					'options'  => array(
						'pipe_delimited' => __( 'Pipe-delimited format', 'woocommerce-customer-order-csv-export' ),
						'json'           => __( 'JSON', 'woocommerce-customer-order-csv-export' ),
					),
					'default' => 'pipe_delimited',
					'class'   => 'wc-enhanced-select js-show-if-single-order-format js-items-format',
				),

				array(
					'id'       => 'wc_customer_order_csv_export_orders_custom_format_delimiter',
					'name'     => __( 'CSV delimiter', 'woocommerce-customer-order-csv-export' ),
					'type'     => 'select',
					'options'  => array(
						","  => __( 'Comma', 'woocommerce-customer-order-csv-export' ),
						";"  => __( 'Semicolon', 'woocommerce-customer-order-csv-export' ),
						"\t" => __( 'Tab', 'woocommerce-customer-order-csv-export' ),
					),
					'default' => ',',
					'class'   => 'wc-enhanced-select js-delimiter',
				),

				array(
					'id'      => 'wc_customer_order_csv_export_orders_custom_format_include_all_meta',
					'name'    => __( 'Include all meta', 'woocommerce-customer-order-csv-export' ),
					'desc'    => __( 'Enable to include all meta in the export', 'woocommerce-customer-order-csv-export' ),
					'default' => 'no',
					'type'    => 'checkbox',
					'class'   => 'js-include-all-meta',
				),

				array( 'type' => 'sectionend' ),

				array(
					'name' => __( 'Column Mapping', 'woocommerce-customer-order-csv-export' ),
					'type' => 'title',
				),

				array(
					'id'          => 'wc_customer_order_csv_export_orders_custom_format_mapping',
					'type'        => 'wc_customer_order_csv_export_field_mapping',
					'export_type' => 'orders',
					'default'     => self::get_default_field_mapping( 'orders' ),
				),


				array( 'type' => 'sectionend' ),
			),

			'customers' => array(

				array(
					'name' => __( 'Format Options', 'woocommerce-customer-order-csv-export' ),
					'type' => 'title',
				),

				array(
					'id'       => 'wc_customer_order_csv_export_customers_custom_format_delimiter',
					'name'     => __( 'CSV delimiter', 'woocommerce-customer-order-csv-export' ),
					'type'     => 'select',
					'options'  => array(
						","  => __( 'Comma', 'woocommerce-customer-order-csv-export' ),
						";"  => __( 'Semicolon', 'woocommerce-customer-order-csv-export' ),
						"\t" => __( 'Tab', 'woocommerce-customer-order-csv-export' ),
					),
					'default' => ',',
					'class'   => 'wc-enhanced-select js-delimiter',
				),

				array(
					'id'      => 'wc_customer_order_csv_export_customers_custom_format_include_all_meta',
					'name'    => __( 'Include all meta', 'woocommerce-customer-order-csv-export' ),
					'desc'    => __( 'Enable to include all meta in the export', 'woocommerce-customer-order-csv-export' ),
					'default' => 'no',
					'type'    => 'checkbox',
					'class'   => 'js-include-all-meta',
				),

				array( 'type' => 'sectionend' ),

				array(
					'name' => __( 'Column Mapping', 'woocommerce-customer-order-csv-export' ),
					'type' => 'title',
				),

				array(
					'id'          => 'wc_customer_order_csv_export_customers_custom_format_mapping',
					'type'        => 'wc_customer_order_csv_export_field_mapping',
					'export_type' => 'customers',
					'default'     => self::get_default_field_mapping( 'customers' ),
				),


				array( 'type' => 'sectionend' ),
			),

		);

		// return all or section-specific settings
		$found_settings = $section_id ? $settings[ $section_id ] : $settings;

		/**
		 * Allow actors to add or remove settings from the CSV export field mapping settings page.
		 *
		 * @since 4.0.0
		 * @param array $settings an array of settings for the given section
		 * @param string $section_id current section ID
		 */
		return apply_filters( 'wc_customer_order_csv_export_custom_format_settings', $found_settings, $section_id );
	}


	/**
	 * Get default field mapping columms for the given export type
	 *
	 * @since 4.0.0
	 * @param string $export_type Export type
	 * @return array
	 */
	private static function get_default_field_mapping( $export_type ) {

		$default_format  = wc_customer_order_csv_export()->get_formats_instance()->get_format( $export_type, 'default' );
		$default_columns = $default_format['columns'];

		$mapping = array();

		foreach ( $default_columns as $column => $name ) {
			$mapping[] = array( 'source' => $column, 'name' => $name );
		}

		return $mapping;
	}


	/**
	 * Output field mapper
	 *
	 * @since 4.0.0
	 * @param array $options
	 */
	public function render_field_mapping( $options ) {

		$mapping = get_option( $options['id'] );

		$mapping['__INDEX__'] = array(
			'name'     => '',
			'source'   => '',
			'meta_key' => '',
		);

		$column_options =wc_customer_order_csv_export()->get_formats_instance()->get_column_data_options( $options['export_type'] );

		?>
		<tr valign="top">
			<td class="forminp wc-customer-order-csv-export-field-mapping-container" colspan="2">

				<input type="hidden" name="<?php echo esc_attr( $options['id'] ); ?>" value="" />

				<table class="wc-customer-order-csv-export-field-mapping widefat" cellspacing="0">
					<thead>
						<tr>
							<?php
								/**
								 * Allow actors to change the column mapping fields
								 *
								 * @since 4.0.0
								 * @param array $fields
								 * @param array $options column mapper options
								 */
								$fields = apply_filters( 'wc_customer_order_csv_export_field_mapping_fields', array(
									'sort'            => '',
									// this can be anything but `check-column` due to https://core.trac.wordpress.org/changeset/38703
									'sv-check-column' => '<input type="checkbox" class="js-select-all" />',
									'name'            => esc_html__( 'Column name', 'woocommerce-customer-order-csv-export' ),
									'source'          => esc_html__( 'Data source', 'woocommerce-customer-order-csv-export' ),
								), $options );

								foreach ( $fields as $field => $label ) {
									echo '<th class="' . esc_attr( $field ) . '">' . $label . '</th>';
								}
							?>
						</tr>
					</thead>
					<tbody>
						<?php
						foreach ( $mapping as $mapping_key => $column ) {

							echo '<tr class="field-mapping field-mapping-' . esc_attr( $mapping_key ) . '">';

							foreach ( $fields as $field => $label ) {

								switch ( $field ) {

									case 'sort' :
										echo '<td width="1%" class="sort"></td>';
									break;

									case 'sv-check-column' :
										echo '<td width="1%" class="check-column">
											<input type="checkbox" class="js-select-field" />
										</td>';
									break;

									case 'name' :
										echo '<td class="name">
											<input type="text" name="' . esc_attr( $options['id'] ) . '[' . esc_attr( $mapping_key ) . '][' . esc_attr( $field ) . ']" value="' . esc_attr( $column[ $field ] ) . '" class="js-field-name" />
										</td>';
									break;

									case 'source' :

										$html_column_options = '';
										$value               = isset( $column[ $field ] ) ? $column[ $field ] : '';
										$meta_key            = 'meta'   === $value && isset( $column['meta_key'] )     ? $column['meta_key']     : '';
										$static_value        = 'static' === $value && isset( $column['static_value'] ) ? $column['static_value'] : '';

										// trick WC into thinking the hidden placeholder row select is already enhanced.
										// this will allow us to later trigger enhancing the field when a new row is added,
										// so that event bindings work
										$enhanced = '__INDEX__' === $mapping_key ? 'enhanced' : '';

										foreach ( $column_options as $option ) {
											$html_column_options .= '<option value="' . esc_attr( $option ) . '" ' . selected( $value, $option, false ) . '>' . esc_html( $option ) .  '</option>';
										}

										?>

										<td class="data">

											<select name="<?php echo esc_attr( $options['id'] ); ?>[<?php echo esc_attr( $mapping_key ); ?>][source]" class="js-field-key wc-enhanced-select-nostd <?php echo $enhanced; ?>" data-placeholder="<?php esc_attr_e( 'Select a value', 'woocommerce-customer-order-csv-export' ); ?>">
												<?php if ( SV_WC_Plugin_Compatibility::is_wc_version_lt_3_0() ) : /* select2 3.5 (supplied with WC < 3.0) requires an empty placeholder option */ ?>
												<option value=""></option>
												<?php endif; ?>
												<?php echo $html_column_options; ?>
												<option value="meta" <?php selected( 'meta', $value ); ?>><?php esc_html_e( 'Meta field...', 'woocommerce-customer-order-csv-export' ); ?></option>
												<option value="static" <?php selected( 'static', $value ); ?>><?php esc_html_e( 'Static value...', 'woocommerce-customer-order-csv-export' ); ?></option>
											</select>

											<label class="js-field-meta-key-label <?php echo ( 'meta' !== $value ? 'hide' : '' ); ?>">
												<?php esc_html_e( 'Meta key:', 'woocommerce-customer-order-csv-export' ); ?>
												<input type="text" name="<?php echo esc_attr( $options['id'] ); ?>[<?php echo esc_attr( $mapping_key ); ?>][meta_key]" value="<?php echo esc_attr( $meta_key ); ?>" class="js-field-meta-key" />
											</label>

											<label class="js-field-static-value-label <?php echo ( 'static' !== $value ? 'hide' : '' ); ?>">
												<?php esc_html_e( 'Value:', 'woocommerce-customer-order-csv-export' ); ?>
												<input type="text" name="<?php echo esc_attr( $options['id'] ); ?>[<?php echo esc_attr( $mapping_key ); ?>][static_value]" value="<?php echo esc_attr( $static_value ); ?>" class="js-field-static-value" />
											</label>

										</td>

										<?php
									break;

									default :
										/**
										 * Allow actors to provide custom fields for column mapping
										 *
										 * @since 4.0.0
										 * @param array $column
										 * @param array $key
										 * @param array $options
										 */
										do_action( 'wc_customer_order_csv_export_field_mapping_column_' . $field, $column, $mapping_key, $options );

										/** @deprecated since 4.1.0 */
										do_action( 'wc_customer_order_csv_export_column_mapping_field_' . $field, $column, $mapping_key, $options );
									break;
								}
							}

							echo '</tr>';
						}
						?>
						<tr class="no-field-mappings <?php if ( count( $mapping ) > 1 ) { echo 'hide'; } ?>">
							<td colspan="<?php echo count( $fields ); ?>">
								<?php esc_html_e( 'There are no mapped columns. Click the Add Column button below to start mapping columns.', 'woocommerce-customer-order-csv-export' ); ?>
							</td>
						</tr>
					</tbody>
					<tfoot>
						<tr>
							<td colspan="<?php echo count( $fields ); ?>">
								<a class="button js-add-field-mapping" href="#"><?php esc_html_e( 'Add column', 'woocommerce-customer-order-csv-export' ); ?></a>
								<a class="button js-remove-field-mapping <?php if ( count( $mapping ) < 2 ) { echo 'hide'; } ?>" href="#"><?php esc_html_e( 'Remove selected column(s)', 'woocommerce-customer-order-csv-export' ); ?></a>
								<a class="button js-load-mapping button-secondary" href="#"><?php esc_html_e( 'Load column mapping', 'woocommerce-customer-order-csv-export' ); ?></a>
							</td>
						</tr>
					</tfoot>
				</table>
			</td>
		</tr>
		<?php
	}


	/**
	 * Output sections for field mapper
	 *
	 * @since 4.0.0
	 */
	public function output_sections() {

		global $current_section;

		$sections = $this->get_sections();

		if ( empty( $sections ) || 1 === sizeof( $sections ) ) {
			return;
		}

		echo '<ul class="subsubsub">';

		$section_ids = array_keys( $sections );

		foreach ( $sections as $id => $label ) {
			echo '<li><a href="' . admin_url( 'admin.php?page=wc_customer_order_csv_export&tab=custom_formats&section=' . sanitize_title( $id ) ) . '" class="' . ( $current_section === $id ? 'current' : '' ) . '">' . esc_html( $label ) . '</a> ' . ( end( $section_ids ) === $id ? '' : '|' ) . ' </li>';
		}

		echo '</ul><br class="clear" />';
	}


	/**
	 * Output the export format definitions as JSON for the given export type
	 *
	 * @since 4.0.0
	 * @param string $export_type
	 */
	public function output_formats_json( $export_type ) {

		$formats = wc_customer_order_csv_export()->get_formats_instance()->get_formats( $export_type );

		if ( empty( $formats ) ) {
			return;
		}

		wc_enqueue_js( 'wc_customer_order_csv_export_admin.export_formats = ' . json_encode( $formats ) . ';' );
	}


	/**
	 * Show column mapping page
	 *
	 * @since 4.0.0
	 */
	public function output() {

		global $current_section;

		// default to orders section
		if ( ! $current_section ) {
			$current_section = 'orders';
		}

		$this->output_sections();

		// render settings fields
		woocommerce_admin_fields( self::get_settings( $current_section ) );

		// output JSON settings for formats (used for loading column mapping from existing formats)
		$this->output_formats_json( $current_section );

		wp_nonce_field( __FILE__ );
		submit_button( __( 'Save', 'woocommerce-customer-order-csv-export' ) );
	}


	/**
	 * Save column mapping
	 *
	 * @since 4.0.0
	 */
	public function save() {

		global $current_section;

		// default to orders section
		if ( ! $current_section ) {
			$current_section = 'orders';
		}

		// security check
		if ( ! wp_verify_nonce( $_POST['_wpnonce'], __FILE__ ) ) {

			wp_die( __( 'Action failed. Please refresh the page and retry.', 'woocommerce-customer-order-csv-export' ) );
		}

		woocommerce_update_options( self::get_settings( $current_section ) );

		wc_customer_order_csv_export()->get_message_handler()->add_message( __( 'Your column settings have been saved.', 'woocommerce-customer-order-csv-export' ) );

	}


	/**
	 * Restore tab as the option value for CSV delimiter
	 *
	 * WC_Admin_Settings::save_fields() strips all tabs from option values
	 * by default, so we need to restore it manually, as in this case,
	 * a single tab is welcome.
	 *
	 * @since 4.0.5
	 * @param mixed $value
	 * @param string $option
	 * @param mixed $raw_value
	 *
	 * @return mixed
	 */
	public function restore_tab_value( $value, $option, $raw_value ) {

		if ( "\t" === $raw_value && '' === $value ) {
			$value = $raw_value;
		}

		return $value;
	}


}
