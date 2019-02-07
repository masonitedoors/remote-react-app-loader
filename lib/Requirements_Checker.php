<?php
/**
 * Check environemtal requirements for the plugin
 *
 * @package Remote_React_App_Loader
 */

declare( strict_types = 1 );

namespace Masonite\Remote_React_App_Loader;

// phpcs:disable Squiz.Commenting.FunctionComment.Missing

/**
 * Check environemtal requirements for the plugin.
 *
 * @link https://markjaquith.wordpress.com/2018/02/19/handling-old-wordpress-and-php-versions-in-your-plugin/
 */
class Requirements_Checker {

	/**
	 * The plugin's title used for admin notices.
	 *
	 * @var string
	 */
	private $title = '';

	/**
	 * The minimum required PHP version.
	 *
	 * @var string
	 */
	private $php = '7.1';

	/**
	 * The mimimum required WordPress version.
	 *
	 * @var string
	 */
	private $wp = '5.0';

	/**
	 * The main file.
	 *
	 * @var string
	 */
	private $file;

	public function __construct( $args ) {
		foreach ( [ 'title', 'php', 'wp', 'file' ] as $setting ) {
			if ( isset( $args[ $setting ] ) ) {
				$this->$setting = $args[ $setting ];
			}
		}
	}

	public function passes() {
		$passes = $this->php_passes() && $this->wp_passes();
		if ( ! $passes ) {
			add_action( 'admin_notices', [ $this, 'deactivate' ] );
		}
		return $passes;
	}

	public function deactivate() {
		if ( isset( $this->file ) ) {
			deactivate_plugins( plugin_basename( $this->file ) );
		}
	}

	private function php_passes() {
		if ( $this->php_at_least( $this->php ) ) {
			return true;
		} else {
			add_action( 'admin_notices', [ $this, 'php_version_notice' ] );
			return false;
		}
	}

	private static function php_at_least( $min_version ) {
		return version_compare( phpversion(), $min_version, '>=' );
	}

	public function php_version_notice() {
		echo '<div class="error">';
		echo '<p>The &#8220;' . esc_html( $this->title ) . '&#8221; plugin cannot run on PHP versions older than ' . esc_html( $this->php ) . '. Please contact your host and ask them to upgrade.</p>';
		echo '</div>';
	}

	private function wp_passes() {
		if ( $this->wp_at_least( $this->wp ) ) {
			return true;
		} else {
			add_action( 'admin_notices', [ $this, 'wp_version_notice' ] );
			return false;
		}
	}

	private static function wp_at_least( $min_version ) {
		return version_compare( get_bloginfo( 'version' ), $min_version, '>=' );
	}

	public function wp_version_notice() {
		echo '<div class="error">';
		echo '<p>The &#8220;' . esc_html( $this->title ) . '&#8221; plugin cannot run on WordPress versions older than ' . esc_html( $this->wp ) . '. Please update WordPress.</p>';
		echo '</div>';
	}
}
