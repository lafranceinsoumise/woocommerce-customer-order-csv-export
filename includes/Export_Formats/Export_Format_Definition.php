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

namespace SkyVerge\WooCommerce\CSV_Export\Export_Formats;

defined( 'ABSPATH' ) or exit;

/**
 * Export Format Definition Class
 *
 * @since 4.7.0
 */
class Export_Format_Definition {


	/** @var string key */
	private $key;

	/** @var string name */
	private $name;

	/** @var string delimiter */
	private $delimiter;

	/** @var string enclosure */
	private $enclosure;

	/** @var string export type, one of `orders`, `customers` or `coupons` */
	protected $export_type;

	/** @var array columns */
	protected $columns;


	/**
	 * Initializes the export format definition.
	 *
	 * @since 4.7.0
	 *
	 * @param array $args {
	 *     An array of arguments.
	 *
	 *     @type string $name format name
	 *     @type string $key format key optional
	 *     @type string $delimiter column delimiter optional
	 *     @type string $enclosure column enclosure optional
	 *     @type string $export_type export type, one of `orders`, `customers` or `coupons` optional
	 *     @type array $columns header columns optional
	 * }
	 */
	public function __construct( $args ) {

		$this->name        = $args['name'];
		$this->delimiter   = ! empty( $args['delimiter'] ) ? $args['delimiter'] : ',';
		$this->enclosure   = ! empty( $args['enclosure'] ) ? $args['enclosure'] : '"';
		$this->export_type = ! empty( $args['export_type'] ) ? $args['export_type'] : 'orders';
		$this->key         = ! empty( $args['key'] ) ? $args['key'] : self::generate_unique_format_key( $this->export_type, $this->name );
		$this->columns     = ! empty( $args['columns'] ) ? $args['columns'] : [];
	}


	/**
	 * Gets the format key.
	 *
	 * @since 4.7.0
	 *
	 * @return string
	 */
	public function get_key() {
		return $this->key;
	}


	/**
	 * Gets the format name.
	 *
	 * @since 4.7.0
	 *
	 * @return string
	 */
	public function get_name() {
		return $this->name;
	}


	/**
	 * Gets the CSV delimiter.
	 *
	 * @since 4.7.0
	 *
	 * @return string
	 */
	public function get_delimiter() {
		return $this->delimiter;
	}


	/**
	 * Gets the enclosure.
	 *
	 * @since 4.7.0
	 *
	 * @return string
	 */
	public function get_enclosure() {
		return $this->enclosure;
	}


	/**
	 * Gets the export type (`orders`, `customers` or `coupons`).
	 *
	 * @since 4.7.0
	 *
	 * @return string
	 */
	public function get_export_type() {
		return $this->export_type;
	}


	/**
	 * Gets columns.
	 *
	 * @since 4.7.0
	 *
	 * @return array
	 */
	public function get_columns() {
		return $this->columns;
	}


	/**
	 * Gets row type (only used by Orders export formats).
	 *
	 * @since 4.7.0
	 */
	public function get_row_type() {
		return null;
	}


	/**
	 * Gets the items format (only used by Orders export formats).
	 *
	 * @since 4.7.0
	 */
	public function get_items_format() {
		return null;
	}


	/**
	 * Gets column mapping (only used by Custom export formats).
	 *
	 * @since 4.7.0
	 *
	 * @return array
	 */
	public function get_mapping() {
		return [];
	}


	/**
	 * Gets include all meta (only used by Custom export formats).
	 *
	 * @since 4.7.0
	 *
	 * @return bool
	 */
	public function get_include_all_meta() {
		return false;
	}


	/**
	 * Returns an array definition, for compatibility.
	 *
	 * @since 4.7.0
	 *
	 * @return array
	 */
	public function to_array() {
		return [
			'key'       => $this->get_key(),
			'name'      => $this->get_name(),
			'delimiter' => $this->get_delimiter(),
			'enclosure' => $this->get_enclosure(),
			'columns'   => $this->get_columns(),
		];
	}


	/**
	 * Generates a unique key, based on the format name.
	 *
	 * @since 4.7.0
	 *
	 * @param string $export_type
	 * @param string $format_name
	 * @return string
	 */
	public static function generate_unique_format_key( $export_type, $format_name ) {

		$possible_key = 'custom-' . sanitize_title( $format_name );

		// check if the key is already used and increment as needed
		while ( null !== wc_customer_order_csv_export()->get_formats_instance()->get_format_definition( $export_type, $possible_key ) ) {
			$possible_key = self::increment_format_key( $possible_key );
		}

		return $possible_key;
	}


	/**
	 * Increments format key.
	 *
	 * @since 4.7.0
	 *
	 * @param string key
	 * @return string
	 */
	private static function increment_format_key( $key ) {

		$i = 1;

		// check if the key already ends in "-number"
		$position = strpos( $key, '-' );

		if ( $position !== false ) {
			$suffix = substr( $key, $position + 1 );

			if ( is_numeric( $suffix ) ) {
				// remove suffix from key
				$key = substr( $key, 0, $position );
				// get suffix numeric value
				$i = $suffix + 1;
			}
		}

		return $key . '-' . $i;
	}


}
