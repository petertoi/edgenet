<?php
/**
 * ussc.
 * User: Paul
 * Date: 2018-10-12
 *
 */

namespace USSC_Edgenet;


if ( ! defined( 'WPINC' ) ) {
	die;
}

class Schedule {

	const RECURRENCE = 'hourly';

	public function __construct() {

		add_action( 'init', array( $this, 'schedule_product_sync' ) );

		add_action( 'ussc_product_sync', array( $this, 'product_sync' ) );
		add_action( 'ussc_product_sync_now', array( $this, 'product_sync' ) );

	}

	/**
	 * Schedules the events
	 *
	 * @access public
	 * @since 1.0.0
	 * @return void
	 */
	public function schedule_product_sync() {

		$recurrence = $this->validate_recurrence( self::RECURRENCE );
		if ( false === wp_next_scheduled( 'ussc_product_sync' ) ) {
			wp_schedule_event( current_time( 'timestamp' ), $recurrence, 'ussc_product_sync' );
		}
	}


	/**
	 * run the importing tasks
	 *
	 * @return void|\WP_Error
	 */
	public function product_sync(){

		// Check if we're already in the process of importing.
		$import_active = get_transient( edgenet()->importer->META_IMPORT_MUTEX );

		if ( $import_active ) {
			return new \WP_Error(
				'ussc-edgenet-import-error',
				__( 'Another import is still underway. Please try again later.', 'ussc' )
			);
		}

		edgenet()->importer->import_products();

		edgenet()->importer->sync_edgenet_cat_to_product_cat();

		return;
	}

	private function validate_recurrence( $reccurrence ) {
		$valid_recurrences = wp_get_schedules();
		if ( array_key_exists( strtolower( $reccurrence ), $valid_recurrences ) ) {
			return strtolower( $reccurrence );
		} else {
			return 'hourly';
		}
	}

}
