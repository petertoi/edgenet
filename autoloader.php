<?php
/**
 * Autoloader.
 *
 * @package USSC_Edgenet
 * @author  Peter Toi <peter@petertoi.com>
 */

/**
 * Autoload Classes
 *
 * Pattern: USSC_Edgenet\My_Module\My_Class_Name -> classes/my-module/class-my-class-name.php.
 *
 * @throws \Exception Function isn't callable.
 */
try {
	spl_autoload_register( function ( $class ) {
		$namespace = 'USSC_Edgenet';

		$base_dir = __DIR__ . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR;

		$allowed_file_prefixes = [
			'class',
			'trait',
		];

		$tokens = explode( '\\', $class );

		// Check if the class is a member of this package.
		if ( array_shift( $tokens ) !== $namespace ) {
			// Fail if not a member.
			return false;
		}

		$tokens = array_map( function ( $token ) {
			$token = strtolower( $token );
			$token = str_replace( '_', '-', $token );

			return $token;
		}, $tokens );

		$file = array_pop( $tokens );

		$path = ( count( $tokens ) )
			? join( DIRECTORY_SEPARATOR, $tokens ) . DIRECTORY_SEPARATOR
			: '';

		foreach ( $allowed_file_prefixes as $file_prefix ) {
			$filepath = "${base_dir}${path}${file_prefix}-${file}.php";
			if ( file_exists( $filepath ) ) {
				require $filepath;
			}
		}

		return false;
	} );
} catch ( \Exception $e ) {
	die( $e->getMessage() );
}