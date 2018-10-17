<?php
/**
 * Filename class-cron.php
 *
 * @package ussc
 * @author  Peter Toi <peter@petertoi.com>
 */

namespace USSC_Edgenet;

/**
 * Class CRON
 *
 * Summary
 *
 * @package USSC_Edgenet
 * @author  Peter Toi <peter@petertoi.com>
 * @version 1.0.0
 */
class CRON {

	const RECURRENCE = 'hourly';

	/**
	 * CRON constructor.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'schedule_product_sync' ) );

		add_action( 'ussc_product_sync', array( $this, 'maybe_sync_products' ) );
		add_action( 'ussc_product_sync_now', array( $this, 'sync_products' ) );
	}

	/**
	 * Schedules the sync.
	 *
	 * @return void
	 */
	public function schedule_product_sync() {
		$recurrence = $this->validate_recurrence( self::RECURRENCE );
		if ( false === wp_next_scheduled( 'ussc_product_sync' ) ) {
			wp_schedule_event( current_time( 'timestamp' ), $recurrence, 'ussc_product_sync' );
		}
	}

	/**
	 * Run scheduled the sync if cron is on.
	 *
	 * @return \WP_Error|array|bool
	 */
	public function maybe_sync_products() {
		if (
			isset( edgenet()->settings->api['is_cron_active'] )
			&& 'on' === edgenet()->settings->api['is_cron_active']
		) {
			return $this->sync_products();
		}

		return false;
	}


	/**
	 * Run the sync.
	 *
	 * @return \WP_Error|array
	 */
	public function sync_products() {
		$status = [
			'import' => false,
			'sync'   => false,
		];

		$status['import'] = edgenet()->importer->import_products();

		if ( is_wp_error( $status['import'] ) ) {
			return $status;
		}

		$status['sync'] = edgenet()->importer->sync_edgenet_cat_to_product_cat();

		if ( is_wp_error( $status['sync'] ) ) {
			return $status;
		}

		return $status;
	}

	/**
	 * Validate reccurrence schedule. Ensures shedules that aren't defined can't be set.
	 *
	 * @param string $reccurrence The desired recurrence.
	 *
	 * @return string The desired recurrence, or hourly if not found.
	 */
	private function validate_recurrence( $reccurrence ) {
		$valid_recurrences = wp_get_schedules();
		if ( array_key_exists( strtolower( $reccurrence ), $valid_recurrences ) ) {
			return strtolower( $reccurrence );
		} else {
			return 'hourly';
		}
	}

}
