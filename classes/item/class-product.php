<?php
/**
 * Filename class-product.php
 *
 * @package ussc
 * @author  Peter Toi <peter@petertoi.com>
 */

namespace USSC_Edgenet\Item;

/**
 * Class Product
 *
 * Summary
 *
 * @package USSC_Edgenet\Item
 * @author  Peter Toi <peter@petertoi.com>
 * @version 1.0.0
 */
class Product {

	const TYPE = 'Product';

	public $id;
	public $archived;
	public $archived_metadata;
	public $components;
	public $record_date;
	public $taxonomy_node_ids;
	public $last_verified_date_time;
	public $is_verified;
	public $audit_info;

	public function __construct( $params ) {
		$this->id                      = $params['id'];
		$this->archived                = $params['Archived'];
		$this->archived_metadata       = $params['ArchivedMetadata'];
		$this->components              = $params['Components'];
		$this->record_date             = $params['RecordDate'];
		$this->taxonomy_node_ids       = $params['TaxonomyNodeIds'];
		$this->last_verified_date_time = $params['LastVerifiedDateTime'];
		$this->is_verified             = $params['IsVerified'];
		$this->audit_info              = $params['AuditInfo'];
	}

	public function get_post_data( $name ) {

	}

	public function get_postmeta_data( $name ) {

	}

	public function get_attribute_value( $attribute_id, $default = '', $lang = 'en-US' ) {
		$value = $default;
		foreach ( $this->components as $component ) {
			// just incase the wriong type of component is set
			if( ! isset( $component['AttributeValues'] ) ) {

				continue;
			}
			$attributes = $component['AttributeValues'][ $lang ];
			$found      = array_filter( $attributes, function ( $attribute ) use ( $attribute_id ) {
				return $attribute['AttributeId'] === $attribute_id;
			} );
			if ( $found ) {
				$attribute = array_shift( $found );
				$value     = $attribute['Value'];
				break;
			}
		}

		return $value;

	}

	public function get_asset_value( $asset_id, $default = '', $lang = 'en-US' ) {
		$value = $default;
		foreach ( $this->components as $component ) {
			if ( isset( $component['Assets'] ) ) {
				$assets = $component['Assets'][ $lang ];
				$found  = array_filter( $assets, function ( $attribute ) use ( $asset_id ) {
					return $attribute['AttributeId'] === $asset_id;
				} );
				if ( $found ) {
					$asset = array_shift( $found );
					$value = $asset['Value'];
					break;
				}
			}
		}

		return $value;

	}
}