<?php
/**
 * Remote React App Loader plugin for WordPress
 *
 * @package           Remote_React_App_Loader
 *
 * @wordpress-plugin
 * Plugin Name:       Remote React App Loader
 * Description:       Easily load react apps inside of WordPress with your theme's header & footer.
 * Version:           0.1.0
 * Author:            Masonite
 * Author URI:        https://www.masonite.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       remote-react-app-loader
 */

declare( strict_types = 1 );

// If this file is called directly, abort.
defined( 'WPINC' ) || die();

// Autoload the plugin classes.
require_once __DIR__ . '/inc/autoload.php';

// Begin execution of the plugin.
\add_action(
	'init',
	function () {
		$check = new \Masonite\Remote_React_App_Loader\Requirements_Checker(
			[
				'title' => 'Remote React App Loader',
				'php'   => '7.1',
				'wp'    => '5.0',
				'file'  => __FILE__,
			]
		);
		if ( $check->passes() ) {
			$plugin = new \Masonite\Remote_React_App_Loader\Plugin();
			$plugin->run();
		}
	}
);
