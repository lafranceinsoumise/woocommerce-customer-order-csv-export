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
 * @package     WC-Customer-Order-CSV-Export/Admin/Views
 * @author      SkyVerge
 * @copyright   Copyright (c) 2012-2017, SkyVerge, Inc.
 * @license     http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

defined( 'ABSPATH' ) or exit;

/**
 * Export modal templates
 *
 * @since 4.0.0
 * @version 4.0.0
 */

global $current_tab, $current_section;
?>

<script type="text/template" id="tmpl-wc-customer-order-csv-export-modal">
	<div class="wc-backbone-modal">
		<div class="wc-backbone-modal-content">
			<section class="wc-backbone-modal-main" role="main">
				<header class="wc-backbone-modal-header">
					<h1>{{{data.title}}}</h1>
					<button class="modal-close modal-close-link dashicons dashicons-no-alt">
						<span class="screen-reader-text"><?php esc_html_e( 'Close modal panel', 'woocommerce-customer-order-csv-export' ); ?></span>
					</button>
				</header>
				<article>{{{data.body}}}</article>
				<footer>
					<div class="inner">
						<button id="btn-ok" class="button button-large {{{data.button_class}}}">{{{data.action}}}</button>
					</div>
				</footer>
			</section>
		</div>
	</div>
	<div class="wc-backbone-modal-backdrop modal-close"></div>
</script>

<script type="text/template" id="tmpl-wc-customer-order-csv-export-modal-body-export-started">
	<p>
		<span class="dashicons dashicons-update wc-customer-order-csv-export-dashicons-spin"></span>
		<?php esc_html_e( 'Your data is being exported now.', 'woocommerce-customer-order-csv-export' ); ?>
		<# if ( 'download' === data.export_method ) { #>
		<?php esc_html_e(' When the export is complete, the download will start automatically.', 'woocommerce-customer-order-csv-export' ); ?>
		<# } #>
	</p>
	<p>
		<?php
			/* translators: Placeholders: %1$s - opening <a> tag, %2$s - closing </a> tag */
			printf( esc_html__( 'When completed, the exported file will also be available under %1$sExport List%2$s for the next 14 days. You can safely leave this screen and return to the Export List later.', 'woocommerce-customer-order-csv-export' ), '<a href="' . admin_url( 'admin.php?page=wc_customer_order_csv_export&tab=export_list' ) . '">', '</a>' );
		?>
	</p>
</script>

<script type="text/template" id="tmpl-wc-customer-order-csv-export-modal-body-export-completed">
	<p><span class="dashicons dashicons-yes"></span>
		<?php esc_html_e( 'Your export is ready!', 'woocommerce-customer-order-csv-export' ); ?>
		<# if ( 'download' === data.export_method ) { #>
		<?php
			/* translators: Placeholders: %1$s - opening <a> tag, %2$s - closing </a> tag */
			printf( esc_html__( '%1$sClick here%2$s if your download does not start automatically. ', 'woocommerce-customer-order-csv-export' ), '<a class="js-export-download-link" href="{{{data.download_url}}}">', '</a>' );
		?>
		<# } #>
		<?php
			/* translators: Placeholders: %1$s - opening <a> tag, %2$s - closing </a> tag */
			printf( esc_html__( 'The exported file will be available under %1$sExport List%2$s for the next 14 days.', 'woocommerce-customer-order-csv-export' ), '<a href="' . admin_url( 'admin.php?page=wc_customer_order_csv_export&tab=export_list' ) . '">', '</a>' );
		?>
	</p>
</script>

<?php if ( isset( $current_tab ) && 'custom_formats' === $current_tab ) : ?>

	<script type="text/template" id="tmpl-wc-customer-order-csv-export-modal-body-load-mapping">

		<form action="" method="post">

		<?php

			$current_section === isset( $current_section ) ? $current_section : 'orders';

			$format_options = array(
				'default'                  => __( 'Default', 'woocommerce-customer-order-csv-export' ),
				'default_one_row_per_item' => __( 'Default - One Row per Item', 'woocommerce-customer-order-csv-export' ),
				'import'                   => __( 'CSV Import', 'woocommerce-customer-order-csv-export' ),
			);

			if ( 'customers' === $current_section ) {
				unset( $format_options['default_one_row_per_item'] );
			}

			/**
			 * Allow actors to change the existing format options in load mapping modal
			 *
			 * @since 4.0.0
			 * @param array $format_options
			 * @param string $export_type
			 */
			$format_options = apply_filters( 'wc_customer_order_csv_export_load_mapping_options', $format_options, $current_section );
		?>

			<div class="wc-customer-order-csv-export-load-mapping-source-selector">
				<select name="source" id="load-mapping-source">
					<optgroup label="<?php esc_attr_e( 'Existing formats', 'woocommerce-customer-order-csv-export' ); ?>">
						<?php foreach ( $format_options as $value => $label ) : ?>
							<option value="<?php echo esc_attr( $value ); ?>"><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</optgroup>
					<option value="snippet"><?php esc_html_e( 'JSON snippet', 'woocommerce-customer-order-csv-export' ); ?></option>
				</select>

				<textarea id="load-mapping-snippet" class="large-text" rows="10" name="snippet" style="display: none" placeholder="<?php esc_attr_e( 'Insert or copy & paste mapping configuration here', 'woocommerce-customer-order-csv-export' ); ?>"></textarea>
			</div>

		</form>
	</script>

<?php endif; ?>
