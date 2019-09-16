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
					<?php if ( ! wc_customer_order_csv_export()->is_batch_processing_enabled() ) : ?>
						<button class="modal-close modal-close-link dashicons dashicons-no-alt">
							<span class="screen-reader-text"><?php esc_html_e( 'Close modal panel', 'woocommerce-customer-order-csv-export' ); ?></span>
						</button>
					<?php endif; ?>
				</header>
				<article>{{{data.body}}}</article>
				<footer>
					<div class="inner">
						<# if ( data.cancel ) { #>
						<button id="btn-cancel" class="button button-large modal-close">{{{data.cancel}}}</button>
						<# } #>
						<# if ( data.action ) { #>
							<button id="btn-ok" class="button button-large {{{data.button_class}}}">{{{data.action}}}</button>
						<# } #>
					</div>
				</footer>
			</section>
		</div>
	</div>
	<div class="wc-backbone-modal-backdrop modal-close"></div>
</script>

<script type="text/template" id="tmpl-wc-customer-order-csv-export-modal-body-export-started">

	<?php $batch_enabled = wc_customer_order_csv_export()->is_batch_processing_enabled(); ?>

	<?php if ( $batch_enabled ) : ?>
		<section>
			<progress class="wc-customer-order-csv-export-progress" max="100" value="0"></progress>
		</section>
	<?php endif; ?>

	<p>
		<span class="dashicons dashicons-update wc-customer-order-csv-export-dashicons-spin"></span>
		<?php esc_html_e( 'Your data is being exported now.', 'woocommerce-customer-order-csv-export' ); ?>
		<# if ( 'download' === data.export_method ) { #>
		<?php esc_html_e(' When the export is complete, the download will start automatically.', 'woocommerce-customer-order-csv-export' ); ?>
		<# } #>
	</p>

	<?php if ( $batch_enabled ) : ?>
		<p class="batch-warning">
			<?php esc_html_e(' Do not navigate away from this page or use the back button until the export is complete.', 'woocommerce-customer-order-csv-export' ); ?>
		</p>
	<?php endif; ?>

	<p>
		<?php
			/* translators: Placeholders: %1$s - opening <a> tag, %2$s - closing </a> tag */
			printf( esc_html__( 'When completed, the exported file will also be available under %1$sExport List%2$s for the next 14 days.', 'woocommerce-customer-order-csv-export' ), '<a href="' . admin_url( 'admin.php?page=wc_customer_order_csv_export&tab=export_list' ) . '">', '</a>' );

			if ( ! $batch_enabled ) :

				/* translators: Placeholders: %1$s - opening <a> tag, %2$s - closing </a> tag */
				esc_html_e( ' You can safely leave this screen and return to the Export List later.', 'woocommerce-customer-order-csv-export' );

			endif;
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

			$format_options = [
				'default'                  => __( 'Default', 'woocommerce-customer-order-csv-export' ),
				'default_one_row_per_item' => __( 'Default - One Row per Item', 'woocommerce-customer-order-csv-export' ),
				'import'                   => __( 'CSV Import', 'woocommerce-customer-order-csv-export' ),
			];

			if ( 'customers' === $current_section || 'coupons' === $current_section ) {
				unset( $format_options['default_one_row_per_item'] );
			}

			if ( 'coupons' === $current_section ) {
				/**
				 * There is no `import` format, only `default`.
				 * And the default's name is "CSV Import".
				 * @see \WC_Customer_Order_CSV_Export_Formats::load_formats()
				 */
				$format_options['default'] = $format_options['import'];
				unset( $format_options['import'] );
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
					<option value="empty"><?php esc_html_e( 'Build my own', 'woocommerce-customer-order-csv-export' ); ?></option>
				</select>

				<textarea id="load-mapping-snippet" class="large-text" rows="10" name="snippet" style="display: none" placeholder="<?php esc_attr_e( 'Insert or copy & paste mapping configuration here', 'woocommerce-customer-order-csv-export' ); ?>"></textarea>
			</div>

		</form>
	</script>

	<script type="text/template" id="tmpl-wc-customer-order-csv-export-modal-body-select-custom-format">

		<form action="" method="post">
			<?php
			$current_section === isset( $current_section ) ? $current_section : 'orders';
			$saved_message = __( 'Your custom format has been saved!', 'woocommerce-customer-order-csv-export' );
			$use_messages  = [
				'orders'    => __( 'Would you like to use this format for Orders exports now?', 'woocommerce-customer-order-csv-export' ),
				'customers' => __( 'Would you like to use this format for Customers exports now?', 'woocommerce-customer-order-csv-export' ),
				'coupons'   => __( 'Would you like to use this format for Coupons exports now?', 'woocommerce-customer-order-csv-export' ),
			];
			$message       = $saved_message . ' ' . $use_messages[ $current_section ];
			?>
			<p><?php echo esc_html( $message ); ?></p>
			<input type="hidden" id="export-type" name="export_type" value="<?php echo esc_attr( $current_section ); ?>">
			<input type="hidden" id="custom-format" name="format" value="{{{data.custom_export_format}}}">
		</form>
	</script>

<?php endif; ?>
