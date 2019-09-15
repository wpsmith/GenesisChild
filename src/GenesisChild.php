<?php
/**
 * Genesis Class
 *
 * Replaces the Genesis header style functions with a custom header style.
 *
 * You may copy, distribute and modify the software as long as you track changes/dates in source files.
 * Any modifications to or software including (via compiler) GPL-licensed code must also be made
 * available under the GPL along with build & install instructions.
 *
 * @package    WPS\Core
 * @author     Travis Smith <t@wpsmith.net>
 * @copyright  2015-2019 Travis Smith
 * @license    http://opensource.org/licenses/gpl-2.0.php GNU Public License v2
 * @link       https://github.com/wpsmith/WPS
 * @version    1.0.0
 * @since      0.1.0
 */

namespace WPS\WP\Themes;

use WPS\Core\Singleton;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( __NAMESPACE__ . '\GenesisChild' ) ) {
	/**
	 * Genesis Class
	 *
	 * Assists in fixing Genesis custom header styles.
	 *
	 * @package WPS\WP
	 * @author  Travis Smith <t@wpsmith.net>
	 */
	class GenesisChild extends Singleton {

		protected function __construct() {
			add_filter( 'auto_update_theme', array( $this, 'auto_update_genesis' ), 10, 2 );
			remove_action( 'genesis_meta', array( $this, 'genesis_load_stylesheet' ) );
			add_action( 'genesis_meta', array( $this, 'load_stylesheet' ) );
			parent::__construct();
		}

		/**
		 * Echo reference to the style sheet.
		 *
		 * If a child theme is active, it loads the child theme's stylesheet, otherwise, it loads the Genesis style sheet.
		 *
		 * @see genesis_enqueue_main_stylesheet() Enqueue main style sheet.
		 */
		public function load_stylesheet() {

			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_main_stylesheet' ), 5 );

		}

		/**
		 * Enqueue main style sheet.
		 *
		 * Properly enqueue the main style sheet.
		 *
		 */
		public function enqueue_main_stylesheet() {

			$suffix  = wp_scripts_get_suffix();
			$version = filemtime( get_stylesheet_directory() . "/style$suffix.css" );

			$handle  = defined( 'CHILD_THEME_NAME' ) && CHILD_THEME_NAME ? sanitize_title_with_dashes( CHILD_THEME_NAME ) : 'child-theme';
			wp_enqueue_style( $handle, get_stylesheet_directory_uri() . "/style$suffix.css", false, $version );

		}

		/**
		 * Auto update specific theme.
		 *
		 * @param bool   $update Whether to allow auto-update.
		 * @param string $item   Item slug.
		 *
		 * @return bool Whether to update plugin.
		 */
		function auto_update_genesis( $update, $item ) {
			\WPS\write_log(array( $update, $item ), 'update');
			if ( 'genesis' === $item->theme ) {
				return true;
			} else {
				return $update; // Else, use the normal API response to decide whether to update or not
			}
		}

		/**
		 * Hook into plugins_loaded.
		 */
		public function plugins_loaded() {
			add_action( 'after_setup_theme', array( $this, 'after_setup_theme' ), 99 );
		}

		/**
		 * Hook into genesis theme.
		 */
		public function after_setup_theme() {
			remove_action( 'wp_head', 'genesis_custom_header_style' );
			add_action( 'wp_head', array( $this, 'genesis_custom_header_style' ) );

			if ( ( defined( 'WP_DEBUG' ) && ! WP_DEBUG ) || ! defined( 'WP_DEBUG' ) ) {
				add_filter( 'genesis_load_deprecated', '__return_false' );
			}
		}

		/**
		 * Custom header callback.
		 *
		 * It outputs special CSS to the document head
		 * modifying the look of the header based on user input.
		 *
		 * @since 1.6.0
		 *
		 * @return void Return early if `custom-header` not supported, user specified own callback, or no options set.
		 */
		public function genesis_custom_header_style() {

			// Do nothing if custom header not supported or user specifies their own callback.
			if ( ! current_theme_supports( 'custom-header' ) || get_theme_support( 'custom-header', 'wp-head-callback' ) ) {
				return;
			}

			$output = '';

			$header_image = get_header_image();
			$text_color   = get_header_textcolor();

			// If no options set, don't waste the output. Do nothing.
			if (
				empty( $header_image ) &&
				! display_header_text() &&
				get_theme_support( 'custom-header', 'default-text-color' ) === $text_color
			) {
				return;
			}

			$header_selector = get_theme_support( 'custom-header', 'header-selector' );
			$title_selector  = genesis_html5() ? '.custom-header .site-title' : '.custom-header #title';
			$desc_selector   = genesis_html5() ? '.custom-header .site-description' : '.custom-header #description';

			// Header selector fallback.
			if ( ! $header_selector ) {
				$header_selector = genesis_html5() ? '.custom-header .site-header' : '.custom-header #header';
			}

			// Header image CSS, if exists.
			if ( $header_image ) {
				$output .= sprintf( '%s{background-image:url(%s) !important; background-repeat:no-repeat !important;}', $header_selector, esc_url( $header_image ) );
			}

			// Header text color CSS, if showing text.
			if (
				display_header_text() &&
				get_theme_support( 'custom-header', 'default-text-color' ) !== $text_color
			) {
				$output .= sprintf( '%2$s a, %2$s a:hover, %3$s { color: #%1$s !important; }', $text_color, $title_selector, $desc_selector );
			}

			if ( $output ) {
				// $output is already escaped above.
				printf( '<style type="text/css">%s</style>' . "\n", esc_html( $output ) );
			}

		}
	}
}
