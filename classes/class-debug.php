<?php
/**
 * Filename class-debug.php
 *
 * @package ussc
 * @author  Peter Toi <peter@petertoi.com>
 */

namespace Edgenet;

/**
 * Class Debug
 *
 * Debug functions including logging.
 *
 * @package Edgenet
 * @author  Peter Toi <peter@petertoi.com>
 * @version
 */
class Debug {

	use Singleton;

	/**
	 * Debug Flags.
	 */
	const LOG_ERROR   = 1;
	const LOG_WARNING = 2;
	const LOG_NOTICE  = 4;
	const LOG_INFO    = 8;
	const LOG_ALL     = 15;

	/**
	 * Debug enabled?
	 *
	 * @var bool
	 */
	public $enabled = false;

	/**
	 * Log file name.
	 *
	 * @var string
	 */
	public $filename;

	/**
	 * Store logging bits as int.
	 *
	 * @var int
	 */
	public $flags = 0;

	/**
	 * Store indentation level.
	 *
	 * @var int
	 */
	public $indent = 0;

	/**
	 * Tracks if the log file has been written to yet.
	 *
	 * @var bool
	 */
	public $dirty = false;

	/**
	 * Enable debug mode.
	 *
	 * @param int $flags Logging flags.
	 */
	public function enable( $flags = 0 ) {
		$this->enabled = true;

		$this->flags = $flags;

		$upload_dir = wp_upload_dir();
		$dirname    = $upload_dir['basedir'] . DIRECTORY_SEPARATOR . 'edgenet';

		if ( ! file_exists( $dirname ) ) {
			wp_mkdir_p( $dirname );
		}

		$this->filename = $dirname . DIRECTORY_SEPARATOR . date( 'Ymd' ) . '.log';

		$this->info( __( 'Debugger Enabled', 'edgenet' ) );
	}

	/**
	 * Disable debug mode.
	 */
	public function disable() {
		$this->enabled = false;
		if ( isset( $this->file ) ) {
			fclose( $this->file ); // phpcs:ignore
		}
	}

	/**
	 * Writes to the log if debug is true.
	 *
	 * @param string $msg  Message to output.
	 * @param mixed  $data Data to dump.
	 *
	 * @return bool True on success.
	 */
	private function print_log( $msg, $data = null ) {
		if ( ! $this->enabled ) {
			return false;
		}

		$fp = fopen( $this->filename, 'a' ); // phpcs:ignore

		if ( ! $fp ) {
			return false;
		}

		if ( ! $this->dirty ) {
			$this->dirty = true;
			$this->print_log( str_repeat( '*', 32 ) );
		}

		$msg = date( 'H:i:s T: ' ) . $msg . PHP_EOL;

		$status = (bool) fputs( $fp, $msg );

		if ( isset( $data ) ) {
			fputs( $fp, print_r( $data, true ) ); // phpcs:ignore
		}

		fclose( $fp ); // phpcs:ignore

		return $status;
	}

	/**
	 * Log Error
	 *
	 * @param string $msg  Message to output.
	 * @param mixed  $data Data to dump.
	 *
	 * @return bool True on success.
	 */
	public function error( $msg, $data = null ) {
		if ( self::LOG_ERROR & $this->flags ) {
			return $this->print_log( $msg, $data );
		}

		return false;
	}

	/**
	 * Log Warning
	 *
	 * @param string $msg  Message to output.
	 * @param mixed  $data Data to dump.
	 *
	 * @return bool True on success.
	 */
	public function warning( $msg, $data = null ) {
		if ( self::LOG_WARNING & $this->flags ) {
			return $this->print_log( $msg, $data );
		}

		return false;
	}

	/**
	 * Log Notice
	 *
	 * @param string $msg  Message to output.
	 * @param mixed  $data Data to dump.
	 *
	 * @return bool True on success.
	 */
	public function notice( $msg, $data = null ) {
		if ( self::LOG_NOTICE & $this->flags ) {
			return $this->print_log( $msg, $data );
		}

		return false;
	}

	/**
	 * Log Info
	 *
	 * @param string $msg  Message to output.
	 * @param mixed  $data Data to dump.
	 *
	 * @return bool True on success.
	 */
	public function info( $msg, $data = null ) {
		if ( self::LOG_INFO & $this->flags ) {
			return $this->print_log( $msg, $data );
		}

		return false;
	}

}