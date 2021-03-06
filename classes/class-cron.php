<?php
/**
 * Filename class-cron.php
 *
 * @package edgenet
 * @author  Peter Toi <peter@petertoi.com>
 */

namespace Edgenet;

/**
 * Class CRON
 *
 * Summary
 *
 * @package Edgenet
 * @author  Peter Toi <peter@petertoi.com>
 * @version 1.0.0
 */
class CRON {

	const RECURRENCE = 'hourly';

	/**
	 * CRON constructor.
	 */
	public function __construct() {
		add_action( 'init', [ $this, 'maybe_schedule_product_sync' ] );

		add_action( 'edgenet_forced_product_sync', [ $this, 'sync_products' ] );
		add_action( 'edgenet_scheduled_product_sync', [ $this, 'sync_products' ] );
	}

	/**
	 * Schedules or un-schedules the product sync
	 * Looks at the is_cron_enabled flag, as well as the existence of the cron event before schedule/unschedule.
	 *
	 * @return void
	 */
	public function maybe_schedule_product_sync() {
		$next_scheduled = wp_next_scheduled( 'edgenet_scheduled_product_sync' );

		if ( $this->is_cron_enabled() ) {
			if ( false === $next_scheduled ) {
				$recurrence = $this->validate_recurrence( self::RECURRENCE );
				 wp_schedule_event( current_time( 'timestamp' ), $recurrence, 'edgenet_scheduled_product_sync' );
			}
		} else {
			if ( false !== $next_scheduled ) {
				wp_unschedule_event( $next_scheduled, 'edgenet_scheduled_product_sync' );
			}
		}
	}

	/**
	 * Run the sync.
	 *
	 * @param bool $force Whether to trigger the sync regardless of the `is_cron_enabled` setting.
	 *
	 * @return \WP_Error|array
	 */
	public function sync_products( $force = false ) {
		$status = [
			'import' => false,
			'sync'   => false,
		];

		if ( ! $force && ! $this->is_cron_enabled() ) {
			$status['import'] = new \WP_Error(
				'edgenet-import-error',
				__( 'Automatic import is disabled.', 'edgenet' )
			);

			return $status;
		}

		$status['import'] = edgenet()->importer->import_products( [], $force );

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

	/**
	 * Helper function for inquiring if the Edgenet Import Cron is enabled or not.
	 *
	 * @return bool
	 */
	private function is_cron_enabled() {
		// CRON is permanently disabled
		return false;

//		$enabled = (
//			isset( edgenet()->settings->import['is_cron_enabled'] )
//			&& 'on' === edgenet()->settings->import['is_cron_enabled']
//		);
//
//		return $enabled;
	}
}
