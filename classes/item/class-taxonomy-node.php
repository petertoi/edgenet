<?php
/**
 * Filename class-taxonomy-node.php
 *
 * @package edgenet
 * @author  Peter Toi <peter@petertoi.com>
 */

namespace Edgenet\Item;

/**
 * Class Taxonomy_Node
 *
 * Summary
 *
 * @package Edgenet\Item
 * @author  Peter Toi <peter@petertoi.com>
 * @version 1.0.0
 */
class Taxonomy_Node {

	const TYPE = 'TaxonomyNode';

	/**
	 * Id
	 *
	 * @var string guid.
	 */
	public $id;

	/**
	 * TaxonomyId
	 *
	 * @var string guid.
	 */
	public $taxonomy_id;

	/**
	 * Description
	 *
	 * @var string
	 */
	public $description;

	/**
	 * ParentId
	 *
	 * @var string guid.
	 */
	public $parent_id;

	/**
	 * Attributes
	 *
	 * @var array Array of attributes in this Taxonomy. Not in Attribute form.
	 */
	public $attributes;

	/**
	 * Taxonomy_Node constructor.
	 *
	 * @param array $params Array representation of Taxonomy Node from taxonomy endpoint.
	 */
	public function __construct( $params ) {
		$this->id          = $params['id'];
		$this->taxonomy_id = $params['TaxonomyId'];
		$this->description = $params['Description'];
		$this->parent_id   = $params['ParentId'];
		$this->attributes  = ( isset( $params['Attributes'] ) )
			? $params['Attributes']
			: [];

	}
}