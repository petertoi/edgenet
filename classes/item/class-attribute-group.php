<?php
/**
 * Filename class-attribute-group.php
 *
 * @package ussc
 * @author  Peter Toi <peter@petertoi.com>
 */

namespace USSC_Edgenet\Item;

/**
 * Class Attribute_Group
 *
 * Summary
 *
 * @package USSC_Edgenet\Item
 * @author  Peter Toi <peter@petertoi.com>
 * @version
 */
class Attribute_Group {

	/**
	 * Id
	 *
	 * @var string guid.
	 */
	public $id;

	/**
	 * Name
	 *
	 * @var string
	 */
	public $name;

	/**
	 * Attributes
	 *
	 * @var Attribute[]\array
	 */
	public $attributes;

	/**
	 * IsCategoryGroup
	 *
	 * @var bool
	 */
	public $is_category_group;

	/**
	 * Attribute_Group constructor.
	 *
	 * @param array $params Attribute Group array from requirementset endpoint.
	 */
	public function __construct( $params ) {
		$this->id                = $params['id'];
		$this->name              = $params['Name'];
		$this->is_category_group = $params['IsCategoryGroup'];

		$this->attributes = [];
		if ( ! empty( $params['Attributes'] ) ) {
			foreach ( $params['Attributes'] as $k => $attribute ) {
				if ( isset( $attribute['Type'] ) && Attribute::TYPE === $attribute['Type'] ) {
					$this->attributes[ $k ] = new Attribute( $attribute );
				} else {
					$this->attributes[ $k ] = $attribute;
				}
			}
		}
	}

	/**
	 * Get all the IDs of the enclosed $attributes.
	 *
	 * @return array Attribute IDs
	 */
	public function get_attribute_ids() {
		$ids = [];
		foreach ( $this->attributes as $attribute ) {
			if ( $attribute instanceof Attribute ) {
				$ids[] = $attribute->id;
			} elseif ( is_array( $attribute ) && isset( $attribute['AttributeId'] ) ) {
				$ids[] = $attribute['AttributeId'];
			}
		}

		return $ids;
	}
}