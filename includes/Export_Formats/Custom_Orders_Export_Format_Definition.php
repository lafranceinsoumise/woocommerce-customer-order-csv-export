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
 * Custom Orders Export Format Definition Class
 *
 * @since 4.7.0
 */
class Custom_Orders_Export_Format_Definition extends Custom_Export_Format_Definition {


	/** @var string row type, one of `order` or `item` */
	private $row_type;

	/** @var string items format, one of `pipe_delimited` or `json` */
	private $items_format;


	/**
	 * Initializes the custom orders export format definition.
	 *
	 * @since 4.7.0
	 *
	 * @param array $args {
	 *     An array of arguments.
	 *
	 * 	   @type string $key format key
	 *     @type string $name format name
	 *     @type string $delimiter column delimiter optional
	 *     @type string $enclosure column enclosure optional
	 *     @type array $mapping column mapping
	 *     @type boolean $include_all_meta include all meta as columns
	 *     @type string $row_type row type, one of one of `order` or `item` optional
	 *     @type string $items_format items format, one of one of `pipe_delimited` or `json` optional
	 * }
	 */
	public function __construct( $args ) {

		parent::__construct( $args );

		$this->export_type  = 'orders';
		$this->row_type     = ! empty( $args['row_type'] ) ? $args['row_type'] : 'order';
		$this->items_format = ! empty( $args['items_format'] ) ? $args['items_format'] : 'pipe_delimited';
	}


	/**
	 * Gets the row type (`order` or `item`).
	 *
	 * @since 4.7.0
	 *
	 * @return string
	 */
	public function get_row_type() {
		return $this->row_type;
	}


	/**
	 * Gets the items format (`pipe_delimited` or `json`).
	 *
	 * @since 4.7.0
	 *
	 * @return string
	 */
	public function get_items_format() {
		return $this->items_format;
	}


	/**
	 * Returns an array definition, for compatibility.
	 *
	 * @since 4.7.0
	 *
	 * @return array
	 */
	public function to_array() {

		return array_merge( parent::to_array(), [
			'row_type'     => $this->get_row_type(),
			'items_format' => $this->get_items_format(),
		] );
	}


}
