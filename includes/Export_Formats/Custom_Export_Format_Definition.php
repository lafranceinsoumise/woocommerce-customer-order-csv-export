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
 * Custom Export Format Definition Class
 *
 * @since 4.7.0
 */
class Custom_Export_Format_Definition extends Export_Format_Definition {


	/** @var array column mapping */
	private $mapping;

	/** @var bool include all meta as columns */
	private $include_all_meta;


	/**
	 * Initializes the custom export format definition.
	 *
	 * @since 4.7.0
	 *
	 * @param array $args {
	 *     An array of arguments.
	 *
	 *     @type string $key format key
	 *     @type string $name format name
	 *     @type array $mapping column mapping
	 *     @type boolean $include_all_meta include all meta as columns
	 *     @type string $delimiter column delimiter optional
	 *     @type string $enclosure column enclosure optional
	 *     @type string $export_type export type, one of `orders`, `customers` or `coupons` optional
	 * }
	 */
	public function __construct( $args ) {

		parent::__construct( $args );

		$this->mapping          = $args['mapping'];
		$this->include_all_meta = $args['include_all_meta'];
	}


	/**
	 * Gets column mapping.
	 *
	 * @since 4.7.0
	 *
	 * @return array
	 */
	public function get_mapping() {
		return $this->mapping;
	}


	/**
	 * Gets include all meta.
	 *
	 * @since 4.7.0
	 *
	 * @return bool
	 */
	public function get_include_all_meta() {
		return $this->include_all_meta;
	}


	/**
	 * Gets columns from mapping and include_all_meta.
	 *
	 * @since 4.7.0
	 *
	 * @return array
	 */
	public function get_columns() {

		if ( ! empty( $this->columns ) ) {
			return $this->columns;
		}

		$columns = [];

		if ( ! empty( $this->mapping ) ) {

			foreach ( $this->mapping as $column ) {

				if ( empty( $column['source'] ) ) {
					continue;
				}

				$key = $column['source'];

				if ( 'meta' === $column['source'] ) {
					$key .= ':' . $column['meta_key'];
				} elseif ( 'static' === $column['source'] ) {
					$key = $column['name'];
				}

				$columns[ $key ] = $column['name'];
			}
		}

		// Include all meta
		if ( $this->include_all_meta ) {

			$all_meta = wc_customer_order_csv_export()->get_formats_instance()->get_all_meta_keys( $this->export_type );

			if ( ! empty( $all_meta ) ) {

				foreach ( $all_meta as $meta_key ) {

					// make sure this meta has not already been manually set
					foreach ( $this->mapping as $column ) {

						if ( ! empty( $column['source'] ) && 'meta' === $column['source'] && $meta_key === $column['meta_key'] ) {
							continue 2;
						}
					}

					$columns[ 'meta:' . $meta_key ] = 'meta:' . $meta_key;
				}
			}
		}

		$this->columns = $columns;

		return $this->columns;
	}


	/**
	 * Returns an array definition, for compatibility.
	 *
	 * @since 4.7.0
	 *
	 * @return array
	 */
	public function to_array() {

		$array = parent::to_array();

		$array['mapping']          = $this->get_mapping();
		$array['include_all_meta'] = $this->get_include_all_meta();
		$array['columns']          = $this->get_columns();

		return $array;
	}


}
