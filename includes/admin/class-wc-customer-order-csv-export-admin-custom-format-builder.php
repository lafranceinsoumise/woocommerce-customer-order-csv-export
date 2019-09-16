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
use SkyVerge\WooCommerce\CSV_Export\Export_Formats\Custom_Export_Format_Definition;
use SkyVerge\WooCommerce\CSV_Export\Export_Formats\Custom_Orders_Export_Format_Definition;

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
		add_action( 'woocommerce_admin_field_wc_customer_order_csv_export_field_mapping', [ $this, 'render_field_mapping' ] );
	}

	/**
	 * Gets sections.
	 *
	 * @since 4.0.0
	 * @deprecated 4.7.0
	 *
	 * @return array
	 */
	public function get_sections() {

		_deprecated_function( __METHOD__,
			'4.7.0',
			'wc_customer_order_csv_export()->get_admin_instance()->get_custom_formats_admin_instance()->get_sections()'
		);

		return wc_customer_order_csv_export()->get_admin_instance()->get_custom_formats_admin_instance()->get_sections();
	}


	/**
	 * Returns settings array for use by output/save functions
	 *
	 * @since 4.0.0
	 *
	 * @param string $section_id
	 * @param Custom_Export_Format_Definition $export_format format for editing
	 * @return array
	 */
	public static function get_settings( $section_id = null, $export_format = null ) {

		$settings = [

			'orders' => [

				[
					'name' => __( 'Format Options', 'woocommerce-customer-order-csv-export' ),
					'type' => 'title',
				],

				[
					'id'                => 'name',
					'name'              => __( 'Name', 'woocommerce-customer-order-csv-export' ),
					'type'              => 'text',
					'default'           => $export_format ? $export_format->get_name() : '',
					'class'             => 'js-name',
					'custom_attributes' => [
						'required' => '',
					],
				],

				[
					'id'       => 'row_type',
					'name'     => __( 'A row represents', 'woocommerce-customer-order-csv-export' ),
					'desc_tip' => __( 'Choose whether a single row in CSV should represent a full, single order or a single line item for an order.', 'woocommerce-customer-order-csv-export' ),
					'type'     => 'select',
					'options'  => wc_customer_order_csv_export()->get_admin_instance()->get_custom_formats_admin_instance()->get_row_type_options(),
					'default'  => $export_format ? $export_format->get_row_type() : 'order',
					'class'    => 'wc-enhanced-select js-row-type',
				],

				[
					'id'       => 'items_format',
					'name'     => __( 'Items columns should use', 'woocommerce-customer-order-csv-export' ),
					'desc_tip' => __( 'Choose whether items columns (line items, shipping items, fee items, etc) should be pipe-delimited or JSON-formatted.', 'woocommerce-customer-order-csv-export' ),
					'type'     => 'select',
					'options'  => wc_customer_order_csv_export()->get_admin_instance()->get_custom_formats_admin_instance()->get_items_format_options(),
					'default'  => $export_format ? $export_format->get_items_format() : 'pipe_delimited',
					'class'    => 'wc-enhanced-select js-show-if-single-order-format js-items-format',
				],

				[
					'id'      => 'delimiter',
					'name'    => __( 'CSV delimiter', 'woocommerce-customer-order-csv-export' ),
					'type'    => 'select',
					'options' => wc_customer_order_csv_export()->get_admin_instance()->get_custom_formats_admin_instance()->get_delimiter_options(),
					'default' => $export_format ? $export_format->get_delimiter() : ',',
					'class'   => 'wc-enhanced-select js-delimiter',
				],

				[
					'id'      => 'include_all_meta',
					'name'    => __( 'Include all meta', 'woocommerce-customer-order-csv-export' ),
					'desc'    => __( 'Enable to include all meta in the export', 'woocommerce-customer-order-csv-export' ),
					'default' => $export_format && $export_format->get_include_all_meta() ? 'yes' : 'no',
					'type'    => 'checkbox',
					'class'   => 'js-include-all-meta',
				],

				[ 'type' => 'sectionend' ],

				[
					'name' => __( 'Column Mapping', 'woocommerce-customer-order-csv-export' ),
					'type' => 'title',
				],

				[
					'id'          => 'mapping',
					'type'        => 'wc_customer_order_csv_export_field_mapping',
					'export_type' => 'orders',
					'default'     => $export_format ? $export_format->get_mapping() : [],
				],


				[ 'type' => 'sectionend' ],
			],

			'customers' => [

				[
					'name' => __( 'Format Options', 'woocommerce-customer-order-csv-export' ),
					'type' => 'title',
				],

				[
					'id'                => 'name',
					'name'              => __( 'Name', 'woocommerce-customer-order-csv-export' ),
					'type'              => 'text',
					'default'           => $export_format ? $export_format->get_name() : '',
					'class'             => 'js-name',
					'custom_attributes' => [
						'required' => '',
					],
				],

				[
					'id'      => 'delimiter',
					'name'    => __( 'CSV delimiter', 'woocommerce-customer-order-csv-export' ),
					'type'    => 'select',
					'options' => wc_customer_order_csv_export()->get_admin_instance()->get_custom_formats_admin_instance()->get_delimiter_options(),
					'default' => $export_format ? $export_format->get_delimiter() : ',',
					'class'   => 'wc-enhanced-select js-delimiter',
				],

				[
					'id'      => 'include_all_meta',
					'name'    => __( 'Include all meta', 'woocommerce-customer-order-csv-export' ),
					'desc'    => __( 'Enable to include all meta in the export', 'woocommerce-customer-order-csv-export' ),
					'default' => $export_format && $export_format->get_include_all_meta() ? 'yes' : 'no',
					'type'    => 'checkbox',
					'class'   => 'js-include-all-meta',
				],

				[ 'type' => 'sectionend' ],

				[
					'name' => __( 'Column Mapping', 'woocommerce-customer-order-csv-export' ),
					'type' => 'title',
				],

				[
					'id'          => 'mapping',
					'type'        => 'wc_customer_order_csv_export_field_mapping',
					'export_type' => 'customers',
					'default'     => $export_format ? $export_format->get_mapping() : [],
				],


				[ 'type' => 'sectionend' ],
			],

			'coupons' => [

				[
					'name' => __( 'Format Options', 'woocommerce-customer-order-csv-export' ),
					'type' => 'title',
				],

				[
					'id'                => 'name',
					'name'              => __( 'Name', 'woocommerce-customer-order-csv-export' ),
					'type'              => 'text',
					'default'           => $export_format ? $export_format->get_name() : '',
					'class'             => 'js-name',
					'custom_attributes' => [
						'required' => '',
					],
				],

				[
					'id'      => 'delimiter',
					'name'    => __( 'CSV delimiter', 'woocommerce-customer-order-csv-export' ),
					'type'    => 'select',
					'options' => wc_customer_order_csv_export()->get_admin_instance()->get_custom_formats_admin_instance()->get_delimiter_options(),
					'default' => $export_format ? $export_format->get_delimiter() : ',',
					'class'   => 'wc-enhanced-select js-delimiter',
				],

				[
					'id'      => 'include_all_meta',
					'name'    => __( 'Include all meta', 'woocommerce-customer-order-csv-export' ),
					'desc'    => __( 'Enable to include all meta in the export', 'woocommerce-customer-order-csv-export' ),
					'default' => $export_format && $export_format->get_include_all_meta() ? 'yes' : 'no',
					'type'    => 'checkbox',
					'class'   => 'js-include-all-meta',
				],

				[ 'type' => 'sectionend' ],

				[
					'name' => __( 'Column Mapping', 'woocommerce-customer-order-csv-export' ),
					'type' => 'title',
				],

				[
					'id'          => 'mapping',
					'type'        => 'wc_customer_order_csv_export_field_mapping',
					'export_type' => 'coupons',
					'default'     => $export_format ? $export_format->get_mapping() : [],
				],


				[ 'type' => 'sectionend' ],

			]
		];

		// return all or section-specific settings
		$found_settings = $section_id ? $settings[ $section_id ] : $settings;

		/**
		 * Allow actors to add or remove settings from the CSV export field mapping settings page.
		 *
		 * @since 4.0.0
		 *
		 * @param array $settings an array of settings for the given section
		 * @param string $section_id current section ID
		 */
		return apply_filters( 'wc_customer_order_csv_export_custom_format_settings', $found_settings, $section_id );
	}


	/**
	 * Output field mapper
	 *
	 * @since 4.0.0
	 * @param array $options
	 */
	public function render_field_mapping( $options ) {

		$mapping = $options['default'];

		$mapping = array_merge( $mapping, [
			'__INDEX__' => [
				'name'     => '',
				'source'   => '',
				'meta_key' => '',
			],
		] );

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
								$fields = apply_filters( 'wc_customer_order_csv_export_field_mapping_fields', [
									'sort'            => '',
									// this can be anything but `check-column` due to https://core.trac.wordpress.org/changeset/38703
									'sv-check-column' => '<input type="checkbox" class="js-select-all" />',
									'name'            => esc_html__( 'Column name', 'woocommerce-customer-order-csv-export' ),
									'source'          => esc_html__( 'Data source', 'woocommerce-customer-order-csv-export' ),
								], $options );

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
	 * Outputs sections for field mapper.
	 *
	 * @since 4.0.0
	 * @deprecated 4.7.0
	 */
	public function output_sections() {

		_deprecated_function( 'wc_customer_order_csv_export()->get_admin_instance()->get_custom_format_builder_instance()->output_sections()',
			'4.7.0',
			'wc_customer_order_csv_export()->get_admin_instance()->get_custom_formats_admin_instance()->output_sections()'
		);

		wc_customer_order_csv_export()->get_admin_instance()->get_custom_formats_admin_instance()->output_sections();
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

		$export_format = null;
		if ( isset( $_GET['custom_format_key'] ) ) {

			if ( isset( $_GET['show_select_modal'] ) ) {
				// show select custom format modal after adding a new format
				$this->select_custom_format_modal( $_GET['custom_format_key'] );
			}

			// load format for editing
			$export_format_key = $_GET['custom_format_key'];
			$export_format     = wc_customer_order_csv_export()->get_formats_instance()->get_format_definition( $current_section, $export_format_key );
		} else {
			// show load mapping modal automatically
			wc_enqueue_js( 'wc_customer_order_csv_export_admin.display_load_mapping_modal = true;' );
		}

		// render settings fields
		woocommerce_admin_fields( self::get_settings( $current_section, $export_format ) );

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

		$format_key = $this->save_custom_format( self::get_settings( $current_section ) );

		$message = __( 'Your custom format has been saved. ', 'woocommerce-customer-order-csv-export' );

		// add a link to the custom formats list to the message
		$url = wc_customer_order_csv_export()->get_admin_instance()->get_custom_formats_admin_instance()->get_custom_formats_table_url();
		/* translators: %1$s - opening <a> tag, %2$s - closing </a> tag */
		$message .= sprintf( esc_html__( 'Return to the %1$scustom formats list%2$s.', 'woocommerce-customer-order-csv-export' ), '<a href="' . esc_url( $url ) . '">', '</a>' );

		wc_customer_order_csv_export()->get_message_handler()->add_message( $message );

		// if adding, redirect to the edit page for the newly created format
		if ( ! isset( $_GET['custom_format_key'] ) ) {

			$edit_url = add_query_arg( [
				'edit_custom_format' => 1,
				'show_select_modal'  => 1,
				'custom_format_key'  => $format_key,
			], remove_query_arg( [
					'add_custom_format'
				] )
			);

			wp_safe_redirect( $edit_url );

		} else {

			// show select custom format modal, if not selected
			$this->select_custom_format_modal( $format_key );

			// show message if not redirecting
			wc_customer_order_csv_export()->get_message_handler()->show_messages( [
				'capabilities' => [
					'manage_woocommerce_csv_exports',
				],
			] );
		}
	}


	/**
	 * Saves current custom format.
	 *
	 * @see WC_Admin_Settings::save_fields()
	 *
	 * @since 4.7.0
	 *
	 * @param array $settings current section settings
	 * @return string|bool
	 */
	private function save_custom_format( $settings ) {
		global $current_section;

		$data = $_POST;

		if ( empty( $data ) ) {
			return false;
		}

		// parse settings into attributes
		$attributes = [];

		foreach ( $settings as $setting ) {

			if ( empty( $setting['id'] ) ) {
				continue;
			}

			$raw_value = isset( $data[ $setting['id'] ] ) ? $data[ $setting['id'] ] : null;

			// format the value based on type
			switch ( $setting['type'] ) {

				case 'checkbox':
					$value = ( '1' === $raw_value || 'yes' === $raw_value );
				break;

				case 'textarea':
					$value = wp_kses_post( trim( $raw_value ) );
				break;

				case 'multiselect':
				case 'multi_select_countries':
					$value = array_filter( array_map( 'wc_clean', (array) $raw_value ) );
				break;

				case 'image_width':

					$value = [];

					if ( isset( $raw_value['width'] ) ) {
						$value['width']  = wc_clean( $raw_value['width'] );
						$value['height'] = wc_clean( $raw_value['height'] );
						$value['crop']   = isset( $raw_value['crop'] ) ? 1 : 0;
					} else {
						$value['width']  = $setting['default']['width'];
						$value['height'] = $setting['default']['height'];
						$value['crop']   = $setting['default']['crop'];
					}

				break;

				case 'select':

					$allowed_values = empty( $setting['options'] ) ? [] : array_map( 'strval', array_keys( $setting['options'] ) );

					if ( empty( $setting['default'] ) && empty( $allowed_values ) ) {
						$value = null;
						break;
					}
					$default = ( empty( $option['default'] ) ? $allowed_values[0] : $option['default'] );
					$value   = in_array( $raw_value, $allowed_values, true ) ? $raw_value : $default;

				break;

				case 'relative_date_selector':
					$value = wc_parse_relative_date_option( $raw_value );
				break;

				default:
					$value = wc_clean( $raw_value );
				break;
			}

			if ( null === $value ) {
				continue;
			}

			$attributes[ $setting['id'] ] = $value;
		}

		if ( isset( $_GET['custom_format_key'] ) ) {
			// get key from format being edited
			$attributes['key'] = $_GET['custom_format_key'];
		}

		switch ( $current_section ) {

			case 'orders':
				$export_format = new Custom_Orders_Export_Format_Definition( $attributes );
			break;

			case 'customers':
			case 'coupons':
			default:
				$attributes['export_type'] = $current_section;
				$export_format             = new Custom_Export_Format_Definition( $attributes );
			break;
		}

		wc_customer_order_csv_export()->get_formats_instance()->save_custom_format( $current_section, $export_format );

		return $export_format->get_key();
	}


	/**
	 * Shows select custom format modal, if not selected.
	 *
	 * @since 4.7.0
	 *
	 * @param string $format_key opened custom format
	 */
	private function select_custom_format_modal( $format_key ) {

		global $current_section;

		// check if the format is already selected
		$current_export_format = get_option( 'wc_customer_order_csv_export_' . $current_section . '_format', 'default' );
		if ( 'custom' === $current_export_format ) {
			$current_export_format = get_option( 'wc_customer_order_csv_export_' . $current_section . '_custom_format', 'custom' );
		}

		if ( $format_key !== $current_export_format ) {
			// show select custom format modal
			wc_enqueue_js( 'wc_customer_order_csv_export_admin.custom_format_key="' . $format_key . '";' );
			wc_enqueue_js( 'wc_customer_order_csv_export_admin.display_select_custom_format_modal = true;' );
		}
	}


}
