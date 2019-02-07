<?php
/**
 * Load the plugin's classes
 *
 * @package           Remote_React_App_Loader
 */

declare( strict_types = 1 );

spl_autoload_register(
	function ( $class ) {
		$dirs   = [
			'lib/',
		];
		$prefix = 'Masonite\\Remote_React_App_Loader\\';
		$len    = strlen( $prefix );

		foreach ( $dirs as $dir ) {
			$base_dir = \plugin_dir_path( dirname( __FILE__ ) ) . $dir;

			if ( strncmp( $prefix, $class, $len ) !== 0 ) {
				return;
			}

			$relative_class = substr( $class, $len );
			$file           = $base_dir . str_replace( '\\', '/', $relative_class ) . '.php';

			if ( file_exists( $file ) ) {
				require_once $file;
			}
		}
	}
);
