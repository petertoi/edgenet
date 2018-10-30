<?php
/**
 * Reusable singleton trait.
 *
 * @package Edgenet
 * @author peter
 * @date 2018-08-29
 */

namespace Edgenet;

trait Singleton {
	/**
	 * The singleton instance.
	 *
	 * @var mixed
	 */
	private static $instance = false;

	/**
	 * Create an inaccessible constructor.
	 */
	private function __construct() {
		return false;
	}

	/**
	 * Fetch an instance of the class.
	 */
	public static function get_instance() {
		if ( false === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Forbid cloning.
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Cheatin&#8217; huh?', 'edgenet' ), '1.0' );
	}

	/**
	 * Forbid unserialization.
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Cheatin&#8217; huh?', 'edgenet' ), '1.0' );
	}
}