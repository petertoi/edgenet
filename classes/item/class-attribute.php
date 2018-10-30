<?php
/**
 * Filename class-attribute.php
 *
 * @package edgenet
 * @author  Peter Toi <peter@petertoi.com>
 */

namespace Edgenet\Item;

/**
 * Class Attribute
 *
 * Summary
 *
 * @package Edgenet\Item
 * @author  Peter Toi <peter@petertoi.com>
 * @version 1.0.0
 */
class Attribute {

	const TYPE = 'Attribute';

	/**
	 * Id
	 *
	 * @var string guid.
	 */
	public $id;

	/**
	 * Description
	 *
	 * @var string
	 */
	public $description;

	/**
	 * FormatType
	 *
	 * @var string
	 */
	public $format_type;

	/**
	 * Attribute constructor.
	 *
	 * @param array $params Array representation of attribute from attribute endpoint.
	 */
	public function __construct( $params ) {
		$this->id          = $params['id'];
		$this->description = $params['Description'];
		$this->format_type = $params['FormatType'];
	}
}
