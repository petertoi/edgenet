<?php
/**
 * Filename class-settings.php
 *
 * @package ussc
 * @author  Peter Toi <peter@petertoi.com>
 */

namespace USSC_Edgenet;

use USSC_Edgenet\Item\Requirement_Set;

/**
 * Class Settings
 *
 * Wrapper for accessing USSC_Edgenet options
 *
 * @package USSC_Edgenet
 * @author  Peter Toi <peter@petertoi.com>
 * @version 1.0.0
 */
class Settings {

	const API_KEY             = 'edgenet_settings_api';
	const FIELD_MAP_KEY       = 'edgenet_settings_field_map';
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
		$this->requirement_set = get_option( self::REQUIREMENT_SET_KEY );
	}

	/**
	 * Magic Getter for $field_map
	 *
	 * @param string $name The name of the post field or postmeta key.
	 *
	 * @return mixed
	 */
	public function __get( $name ) {
		if ( in_array( $name, [ 'post_title', 'post_content', 'post_excerpt' ], true ) ) {
			if ( isset( $this->field_map['post'][ $name ] ) ) {
				return $this->field_map['post'][ $name ];
			}
		} else {
			if ( isset( $this->field_map['postmeta'][ $name ] ) ) {
				return $this->field_map['postmeta'][ $name ];
			}
		}

		return;
	}

	/**
	 * Magic Setter for $field_map
	 *
	 * @param string $name  The name of the post field or postmeta key.
	 * @param mixed  $value The value to set.
	 */
	public function __set( $name, $value ) {
		$this->field_map[ $name ] = $value;
	}

	/**
	 * Save all the settings to the database.
	 */
	public function save_all() {
		$this->save_api();
		$this->save_field_map();
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
				'label' => __( ' - Select Attribute Group - ', 'ussc' ),
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
				'label' => __( ' - Select Attribute - ', 'ussc' ),
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
				'label' => __( ' - Select User for Import - ', 'ussc' ),
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
			'requirement_set',
			'taxonomy_id',
			'import_user',
		];

		$valid = true;

		foreach ( $core_settings as $setting ) {
			if ( ! isset( $this->api[ $setting ] ) ) {
				$valid = false;
			}
		}

		return $valid;
	}

	/**
	 * Check if all the Requirement Set exists.
	 *
	 * @return bool
	 */
	public function is_requirement_set_valid() {
		return ( isset( $this->requirement_set ) );
	}
}
