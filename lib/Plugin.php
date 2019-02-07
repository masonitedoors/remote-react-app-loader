<?php
/**
 * The main plugin class
 *
 * @package Remote_React_App_Loader
 */

declare( strict_types = 1 );

namespace Masonite\Remote_React_App_Loader;

/**
 * The main plugin class.
 */
class Plugin {

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * Load the dependencies, define the locale, and set the hooks for the Dashboard and
	 * the public-facing side of the site.
	 */
	public function run() : void {
		if ( defined( 'REACT_APPS' ) ) {
			foreach ( REACT_APPS as $react_app ) {
				$app = new App(
					[
						'slug'               => $react_app['slug'],               // The slug to tell WordPress to stop handling so react can handle routing.
						'base_url'           => $react_app['base_url'],           // The base url for the remote react app.
						'asset_manifest_url' => $react_app['asset_manifest_url'], // The full url to the remote react app's asset-manifest.json.
						'root_id'            => $react_app['root_id'],            // The id of the root element the app mounts to.
						'scripts'            => $react_app['scripts'],            // The react app's script dependencies.
						'styles'             => $react_app['styles'],             // The react app's style dependencies.
					]
				);
				$app->init();
			}
		}
	}

	/**
	 * Get the plugin's current version.
	 */
	public static function get_version() : string {
		$version     = '';
		$path        = \plugin_dir_path( dirname( __FILE__ ) ) . 'remote-react-app-loader.php';
		$plugin_data = \get_file_data( $path, [ 'Version' => 'Version' ] );

		if ( ! empty( $plugin_data['Version'] ) ) {
			$version = $plugin_data['Version'];
		}

		return $version;
	}
}
