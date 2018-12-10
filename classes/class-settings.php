<?php
/**
 * Filename class-settings.php
 *
 * @package edgenet
 * @author  Peter Toi <peter@petertoi.com>
 */

namespace Edgenet;

use Edgenet\Item\Requirement_Set;

/**
 * Class Settings
 *
 * Wrapper for accessing Edgenet options
 *
 * @package Edgenet
 * @author  Peter Toi <peter@petertoi.com>
 * @version 1.0.0
 */
class Settings {

	const API_KEY             = 'edgenet_settings_api';
	const FIELD_MAP_KEY       = 'edgenet_settings_field_map';
	const IMPORT_KEY          = 'edgenet_settings_import';
	const REQUIREMENT_SET_KEY = 'edgenet_settings_requirement_set';

	/**
	 * API Settings
	 *
	 * @var array
	 */
	public $api;

	/**
	 * Mapping of Edgenet attributes to WordPress/WooCommerce fields.
	 *
	 * @var array
	 */
	public $field_map = [
		'post'     => [],
		'postmeta' => [],
	];

	/**
	 * Import Settings
	 *
	 * @var array
	 */
	public $import;

	/**
	 * Requirement_Set object from requirementset endpoint.
	 *
	 * @var Requirement_Set
	 */
	public $requirement_set;

	/**
	 * Cache for storing results from frequently called routines.
	 *
	 * @var array
	 */
	private $cache;

	/**
	 * Settings constructor.
	 */
	public function __construct() {
		$this->api             = get_option( self::API_KEY );
		$this->field_map       = get_option( self::FIELD_MAP_KEY );
		$this->import          = get_option( self::IMPORT_KEY );
		$this->requirement_set = get_option( self::REQUIREMENT_SET_KEY );
	}

	/**
	 * Getter for $api attributes
	 *
	 * @param string $name The name of the api key.
	 *
	 * @return mixed
	 */
	public function get_api( $name ) {
		switch ( $name ) {
			case 'username':
				if ( defined( 'EDGENET_PROD_USERNAME' ) ) {
					return EDGENET_PROD_USERNAME;
				}
				break;
			case 'secret':
				if ( defined( 'EDGENET_PROD_SECRET' ) ) {
					return EDGENET_PROD_SECRET;
				}
				break;
			case 'data_owner':
				if ( defined( 'EDGENET_DATA_OWNER' ) ) {
					return EDGENET_DATA_OWNER;
				}
				break;
			case 'recipient':
				if ( defined( 'EDGENET_RECIPIENT' ) ) {
					return EDGENET_RECIPIENT;
				}
				break;
			case 'requirement_set':
				if ( defined( 'EDGENET_REQUIREMENT_SET' ) ) {
					return EDGENET_REQUIREMENT_SET;
				}
				break;
			case 'taxonomy_id':
				if ( defined( 'EDGENET_TAXONOMY_ID' ) ) {
					return EDGENET_TAXONOMY_ID;
				}
				break;
			default:
				break;
		}

		if ( isset( $this->api[ $name ] ) ) {
			return $this->api[ $name ];
		}

		return null;
	}

	/**
	 * Setter for $api attributes
	 *
	 * @param string $name  The name of the api key.
	 * @param mixed  $value The value to set.
	 */
	public function set_api( $name, $value ) {
		$this->api[ $name ] = $value;
	}

	/**
	 * Getter for $field_map attributes
	 *
	 * @param string $name The name of the post field or postmeta key.
	 *
	 * @return mixed
	 */
	public function get_field_map( $name ) {
		if ( in_array( $name, [ 'post_title', 'post_content', 'post_excerpt' ], true ) ) {
			if ( isset( $this->field_map['post'][ $name ] ) ) {
				return $this->field_map['post'][ $name ];
			}
		} else {
			if ( isset( $this->field_map['postmeta'][ $name ] ) ) {
				return $this->field_map['postmeta'][ $name ];
			}
		}
	}

	/**
	 * Setter for $field_map attributes
	 *
	 * @param string $name  The name of the post field or postmeta key.
	 * @param mixed  $value The value to set.
	 */
	public function set_field_map( $name, $value ) {
		$this->import[ $name ] = $value;
	}

	/**
	 * Getter for $import attributes
	 *
	 * @param string $name The name of the import key.
	 *
	 * @return mixed
	 */
	public function get_import( $name ) {
		if ( isset( $this->import[ $name ] ) ) {
			return $this->import[ $name ];
		}

		return null;
	}

	/**
	 * Setter for $import attributes
	 *
	 * @param string $name  The name of the import key.
	 * @param mixed  $value The value to set.
	 */
	public function set_import( $name, $value ) {
		$this->import[ $name ] = $value;
	}

	/**
	 * Getter for $requirement_set attributes
	 *
	 * @param string $name The name of the requirement_set key.
	 *
	 * @return mixed
	 */
	public function get_requirement_set( $name ) {
		if ( isset( $this->requirement_set[ $name ] ) ) {
			return $this->requirement_set[ $name ];
		}

		return null;
	}

	/**
	 * Setter for $requirement_set attributes
	 *
	 * @param string $name  The name of the requirement_set key.
	 * @param mixed  $value The value to set.
	 */
	public function set_requirement_set( $name, $value ) {
		$this->requirement_set[ $name ] = $value;
	}

	/**
	 * Save all the settings to the database.
	 */
	public function save_all() {
		$this->save_api();
		$this->save_field_map();
		$this->save_import();
		$this->save_requirement_set();
	}

	/**
	 * Save API settings to options table. Optionally, set the value of this variable.
	 *
	 * @param array|null $api (optional) API settings.
	 */
	public function save_api( $api = null ) {
		if ( isset( $api ) ) {
			$this->api = $api;
		}
		update_option( self::API_KEY, $this->api );
	}

	/**
	 * Save Field Map settings to options table. Optionally, set the value of this variable.
	 *
	 * @param array|null $field_map (optional) Field Map.
	 */
	public function save_field_map( $field_map = null ) {
		if ( isset( $field_map ) ) {
			$this->field_map = $field_map;
		}
		update_option( self::FIELD_MAP_KEY, $this->field_map );
	}

	/**
	 * Save Import settings to options table. Optionally, set the value of this variable.
	 *
	 * @param array|null $import (optional) Import settings.
	 */
	public function save_import( $import = null ) {
		if ( isset( $import ) ) {
			$this->import = $import;
		}
		update_option( self::IMPORT_KEY, $this->import );
	}

	/**
	 * Save Requirement_Set to options table. Optionally, set the value of this variable.
	 *
	 * @param array|null $requirement_set (optional) Requiremet_Set.
	 */
	public function save_requirement_set( $requirement_set = null ) {
		if ( isset( $requirement_set ) ) {
			$this->requirement_set = $requirement_set;
		}
		update_option( self::REQUIREMENT_SET_KEY, $this->requirement_set );
	}

	/**
	 * Helper function to get a list of Attibute_Groups in a format suitable
	 * for use by Template::render_select().
	 *
	 * @return array
	 */
	public function get_attribute_groups_for_select() {
		if ( empty( $this->cache[ __FUNCTION__ ] ) ) {

			$default_option = [
				'value' => '',
				'label' => __( ' - Select Attribute Group - ', 'edgenet' ),
			];

			$options = array_map( function ( $group ) {
				return [
					'value' => $group->id,
					'label' => $group->name,
				];
			}, edgenet()->settings->requirement_set->attribute_groups );

			usort( $options, function ( $a, $b ) {
				$a_label = strtoupper( $a['label'] );
				$b_label = strtoupper( $b['label'] );
				if ( $a_label === $b_label ) {
					return 0;
				}

				return ( $a_label < $b_label ) ? - 1 : 1;
			} );

			array_unshift( $options, $default_option );

			$this->cache[ __FUNCTION__ ] = $options;
		}

		return $this->cache[ __FUNCTION__ ];
	}

	/**
	 * Helper function to get a list of Attributes in a format suitable
	 * for use by Template::render_select().
	 *
	 * @return array
	 */
	public function get_attributes_for_select() {
		if ( empty( $this->cache[ __FUNCTION__ ] ) ) {
			$default_option = [
				'value' => '',
				'label' => __( ' - Select Attribute - ', 'edgenet' ),
			];

			$options = array_map( function ( $att ) {
				return [
					'value' => $att->id,
					'label' => $att->description,
				];
			}, edgenet()->settings->requirement_set->get_attributes() );

			usort( $options, function ( $a, $b ) {
				$a_label = strtoupper( $a['label'] );
				$b_label = strtoupper( $b['label'] );
				if ( $a_label === $b_label ) {
					return 0;
				}

				return ( $a_label < $b_label ) ? - 1 : 1;
			} );

			array_unshift( $options, $default_option );

			$this->cache[ __FUNCTION__ ] = $options;
		}

		return $this->cache[ __FUNCTION__ ];
	}

	/**
	 * Helper function to get a list of Users in a format suitable
	 * for use by Template::render_select().
	 *
	 * @return array
	 */
	public function get_users_for_select() {
		if ( empty( $this->cache[ __FUNCTION__ ] ) ) {
			$default_option = [
				'value' => '',
				'label' => __( ' - Select User for Import - ', 'edgenet' ),
			];

			$user_args  = [
				'role__in' => [ 'editor', 'administrator' ],
				'number'   => 100,
			];
			$user_query = new \WP_User_Query( $user_args );

			$user_results = $user_query->get_results();

			if ( $user_results ) {
				$options = array_map( function ( $user ) {
					return [
						'value' => $user->ID,
						'label' => sprintf( '%s [%s]', $user->user_login, $user->user_email ),
					];
				}, $user_results );

			}

			array_unshift( $options, $default_option );

			$this->cache[ __FUNCTION__ ] = $options;
		}

		return $this->cache[ __FUNCTION__ ];
	}

	/**
	 * Check if all the Core Settings have been set.
	 *
	 * @return bool
	 */
	public function is_core_valid() {
		$core_settings = [
			'data_owner',
			'username',
			'secret',
			'recipient',
			'requirement_set',
			'taxonomy_id',
		];

		$valid = true;

		foreach ( $core_settings as $setting ) {
			if ( empty( $this->api[ $setting ] ) ) {
				$valid = false;
			}
		}

		return $valid;
	}

	/**
	 * Check if all the required Import Settings have been set.
	 *
	 * @return bool
	 */
	public function is_import_valid() {
		$import_settings = [
			'user',
		];

		$valid = true;

		foreach ( $import_settings as $setting ) {
			if ( empty( $this->import[ $setting ] ) ) {
				$valid = false;
			}
		}

		return ( $this->is_core_valid() && $valid );
	}

	/**
	 * Check if all the Requirement Set exists.
	 *
	 * @return bool
	 */
	public function is_requirement_set_valid() {
		return ( $this->requirement_set instanceof Requirement_Set );
	}
}


