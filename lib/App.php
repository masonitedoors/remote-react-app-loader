<?php
/**
 * The main class for loading the remote react apps
 *
 * @package Remote_React_App_Loader
 */

declare( strict_types = 1 );

namespace Masonite\Remote_React_App_Loader;

/**
 * The main class for loading the remote react apps.
 */
class App {

	/**
	 * The user role needed to access the react app.
	 *
	 * @var string
	 */
	private $role;

	/**
	 * The slug to tell WordPress to stop handling so react can handle routing.
	 *
	 * @var string
	 */
	private $slug;

	/**
	 * The base url for the remote react app.
	 *
	 * @var string
	 */
	private $base_url;

	/**
	 * The full url to the remote react app's asset-manifest.json.
	 *
	 * @var string
	 */
	private $asset_manifest_url;

	/**
	 * The id of the root element the app mounts to.
	 *
	 * @var string
	 */
	private $id;

	/**
	 * The react app's script dependencies.
	 *
	 * @var array
	 */
	private $scripts;

	/**
	 * The react app's style dependencies.
	 *
	 * @var array
	 */
	private $styles;

	/**
	 * The WordPress query variable for the react app.
	 *
	 * @var string
	 */
	private $app_query_var;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param array $args               The class' options:
	 *              $role               TThe user role needed to access the react app.
	 *              $slug               The slug to tell WordPress to stop handling so react can handle routing.
	 *              $base_url           The base url for the remote react app.
	 *              $asset_manifest_url The full url to the remote react app's asset-manifest.json.
	 *              $root_id            The id of the root element the app mounts to.
	 *              $scripts            The react app's script dependencies.
	 *              $styles             The react app's style dependencies.
	 */
	public function __construct( array $args ) {
		$this->role               = $args['role'];
		$this->slug               = $args['slug'];
		$this->base_url           = $args['base_url'];
		$this->asset_manifest_url = $args['asset_manifest_url'];
		$this->id                 = $args['root_id'];
		$this->scripts            = $args['scripts'];
		$this->styles             = $args['styles'];
		$this->app_query_var      = 'react_app_' . $this->slug;
	}

	/**
	 * Initialize the react app.
	 */
	public function init() : void {
		$this->generate_page();
		$this->disable_wp_rewrite();
	}

	/**
	 * Create the virtual page the react app with live within.
	 */
	public function generate_page() : void {

		add_filter(
			'generate_rewrite_rules',
			function ( $wp_rewrite ) {
				$wp_rewrite->rules = array_merge(
					[ $this->slug . '/?$' => 'index.php?' . $this->app_query_var . '=1' ],
					$wp_rewrite->rules
				);
			}
		);

		add_filter(
			'query_vars',
			function( $query_vars ) {
				$query_vars[] = $this->app_query_var;
				return $query_vars;
			}
		);

		add_action(
			'template_redirect',
			function() {
				$query_var = intval( get_query_var( $this->app_query_var ) );
				if ( $query_var ) {
					$this->app_handler();
					die;
				}
			}
		);
	}

	/**
	 * Prevent WordPress from thinking that react app routes are separate WordPress pages.
	 * This means when using a shortcode in a page, you will no longer be able to have any children page/posts permalinks.
	 */
	public function disable_wp_rewrite() : void {
		add_rewrite_rule(
			'^' . $this->slug . '/(.*)$',
			'index.php?' . $this->app_query_var . '=1',
			'top'
		);
	}

	/**
	 * Check if the user is allowed to view this application.
	 *
	 * @param string $required_role The user role needed to access the react app.
	 */
	public static function user_is_allowed_access( $required_role ) : bool {
		$user = wp_get_current_user();

		if ( in_array( $required_role, (array) $user->roles ) ) {
			return true;
		}

		return false;
	}

	/**
	 * The callback for the react app shortcode.
	 */
	public function app_handler() : void {
		// If access is not allowed, redirect to homepage.
		if ( ! self::user_is_allowed_access( $this->role ) ) {
			wp_safe_redirect( home_url() );
			exit;
		}

		$assets_list = self::get_remote_assets( $this->asset_manifest_url );
		$assets      = self::filter_assets_list( $assets_list );

		// Ensure react & react-dom are dependencies.
		$scripts = array_merge( $this->scripts, [ 'react', 'react-dom' ] );

		// Make runtime / bundle first up.
		uksort(
			$assets,
			function ( $asset_path ) {
				if ( strstr( $asset_path, 'runtime' ) || strstr( $asset_path, 'bundle' ) ) {
					return -1;
				}
				return 1;
			}
		);

		// There will be at most one JS and one CSS file in vanilla Create React App manifests.
		$has_css = false;
		foreach ( $assets as $asset_path ) {
			$is_js      = preg_match( '/\.js$/', $asset_path );
			$is_css     = preg_match( '/\.css$/', $asset_path );
			$is_runtime = preg_match( '/(runtime|bundle)/', $asset_path );

			if ( ! $is_js && ! $is_css ) {
				// Assets such as source maps and images are also listed; ignore these.
				continue;
			}

			// Set a dynamic handle as we can have more than one JS entry point.
			// Treats the runtime file as primary to make setting dependencies easier.
			$handle = $this->id . ( $is_runtime ? '' : '-' . \sanitize_key( basename( $asset_path ) ) );

			if ( $is_js ) {
				wp_enqueue_script(
					$handle,
					self::get_asset_uri( $this->base_url, $asset_path ),
					$scripts,
					null,
					true
				);
			} elseif ( $is_css ) {
				$has_css = true;
				wp_enqueue_style(
					$handle,
					self::get_asset_uri( $this->base_url, $asset_path ),
					$this->styles,
					null
				);
			}
		}

		// Ensure CSS dependencies are always loaded.
		if ( ! $has_css ) {
			wp_register_style(
				$this->id,
				null,
				$this->styles,
				null
			);
			wp_enqueue_style( $this->id );
		}

		if ( ! empty( $assets ) ) {
			get_header();
			echo '<div id="' . esc_html( $this->id ) . '"></div>';
			get_footer();
		}

	}

	/**
	 * Get remote asset.
	 *
	 * @param string $asset_manifest_url The full url to the react app's asset-manifest.json.
	 */
	public static function get_remote_assets( string $asset_manifest_url ) : ?array {
		$request = wp_remote_get( $asset_manifest_url );

		if ( is_wp_error( $request ) ) {
			return null;
		}

		$body   = wp_remote_retrieve_body( $request );
		$assets = json_decode( $body, true );

		return $assets;
	}

	/**
	 * Filter the assets to remove all async chunks, the service worker and precache manifest.
	 *
	 * @param array $assets The assets.
	 */
	public static function filter_assets_list( array $assets ) : array {
		return array_filter(
			$assets,
			function ( $asset_path ) {
				return ! preg_match( '/precache-manifest|service-worker/', $asset_path );
			}
		);
	}

	/**
	 * Return web URIs or convert relative filesystem paths to absolute paths.
	 *
	 * @param string $base_url   A base URL to prepend to relative bundle URIs.
	 * @param string $asset_path A relative filesystem path or full resource URI.
	 */
	public static function get_asset_uri( string $base_url, string $asset_path ) : string {
		if ( strpos( $asset_path, '://' ) !== false ) {
			return $asset_path;
		}

		return trailingslashit( $base_url ) . ltrim( $asset_path, '/' );
	}

}
