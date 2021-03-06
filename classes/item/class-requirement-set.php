<?php
/**
 * Filename class-requirement-set.php
 *
 * @package edgenet
 * @author  Peter Toi <peter@petertoi.com>
 */

namespace Edgenet\Item;


class Requirement_Set {

	const TYPE = 'RequirementSet';

	/**
	 * TargetParty
	 *
	 * @var string guid.
	 */
	public $target_party;

	/**
	 * RuleSetDefinitionId
	 *
	 * @var string guid.
	 */
	public $rule_set_definition_id;

	/**
	 * Groups
	 *
	 * @var Attribute_Group[]
	 */
	public $attribute_groups;

	/**
	 * NeedsPublish
	 *
	 * @var bool
	 */
	public $needs_publish;

	/**
	 * LastPublishDate
	 *
	 * @var string Date.
	 */
	public $last_publish_date;

	/**
	 * Name
	 *
	 * @var string
	 */
	public $name;

	/**
	 * Id
	 *
	 * @var string guid.
	 */
	public $id;

	/**
	 * DataOwner
	 *
	 * @var string guid.
	 */
	public $data_owner;

	/**
	 * Requirement_Set constructor.
	 *
	 * @param array $params Array representation of requirementset endpoint response.
	 */
	public function __construct( $params ) {
		$this->target_party           = $params['TargetParty'];
		$this->rule_set_definition_id = $params['RuleSetDefinitionId'];
		$this->needs_publish          = $params['NeedsPublish'];
		$this->last_publish_date      = $params['LastPublishedDate'];
		$this->name                   = $params['Name'];
		$this->id                     = $params['id'];
		$this->data_owner             = $params['DataOwner'];

		$this->attribute_groups = [];
		if ( ! empty( $params['Groups'] ) ) {
			foreach ( $params['Groups'] as $k => $group ) {
				$this->attribute_groups[ $k ] = new Attribute_Group( $group );
			}
		}

	}

	/**
	 * Get an Attribute by ID.
	 *
	 * @param string $attribute_id The Attribute ID.
	 *
	 * @return Attribute
	 */
	public function get_attribute_by_id( $attribute_id ) {
		$attribute = array_filter(
			$this->get_attributes(),
			function ( $attribute ) use ( $attribute_id ) {
				return $attribute->id === $attribute_id;
			}
		);

		return ( ! empty( $attribute ) )
			? array_shift( $attribute )
			: null;
	}

	/**
	 * Get all the Attributes contained across all Attibute_Groups within this Requirement_Set.
	 *
	 * @return array
	 */
	public function get_attributes() {
		$all_attributes = [];

		foreach ( $this->attribute_groups as $group ) {
			$all_attributes = array_merge( $all_attributes, $group->attributes );
		}

		$all_attributes = array_unique( $all_attributes, SORT_REGULAR );

		return $all_attributes;
	}

	/**
	 * Get all the Attributes within a specific Attribute_Group.
	 *
	 * @param string $group_id Attribute Group UID.
	 *
	 * @return array
	 */
	public function get_attributes_by_group_id( $group_id ) {
		$attributes = [];

		$result = array_filter( $this->attribute_groups, function ( $group ) use ( $group_id ) {
			return ( $group->id === $group_id );
		} );

		if ( $result ) {
			$group      = array_shift( $result );
			$attributes = array_unique( $group->attributes, SORT_REGULAR );
		}

		return $attributes;
	}

}