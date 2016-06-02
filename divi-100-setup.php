<?php
// Prevent file from being loaded directly
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

/**
 * Check plugin status. Activate only if current theme is Divi
 *
 * @return bool
 */
if ( ! function_exists( 'et_divi_100_is_active' ) ) {
	function et_divi_100_is_active() {
		$current_theme = wp_get_theme();

		if ( 'Divi' === $current_theme->get( 'Name' ) || 'Divi' === $current_theme->get( 'Template' ) ) {
			return true;
		}

		return false;
	}
}

/**
 * Get registered Divi 100 settings
 *
 * @return array
 */
if ( ! function_exists( 'et_divi_100_settings' ) ) {
	function et_divi_100_settings() {
		return apply_filters( 'et_divi_100_settings', array() );
	}
}

/**
 * Get plugin slug which has the most updated Divi 100 setup dir
 *
 * @return string
 */
if ( ! function_exists( 'et_divi_100_get_most_updated_plugin_slug' ) ) {
	function et_divi_100_get_most_updated_plugin_slug() {
		// Get Divi 100 settings
		$plugins = et_divi_100_settings();

		// Pluck the version number
		$versions = wp_list_pluck( $plugins, 'plugin_version' );

		// Sort from latest to oldest
		arsort( $versions );

		// Get the latest version's plugin slug
		$latest_version_slug = current( array_keys( $versions ) );

		return apply_filters( 'et_divi_100_get_most_updated_plugin_slug', $latest_version_slug );
	}
}

/**
 * Get latest Divi 100 setup dir path based on Divi 100 settings
 *
 * @return string of latest Divi 100's setup dir path
 */
if ( ! function_exists( 'et_divi_100_get_setup_dir_path' ) ) {
	function et_divi_100_get_setup_dir_path() {
		// Get Divi 100 settings
		$plugins = et_divi_100_settings();

		// Get the latest version's plugin slug
		$latest_version_slug = et_divi_100_get_most_updated_plugin_slug();

		// Check whether latest version's setup dir path exist
		$is_setup_exist = ( $latest_version_slug && isset( $plugins[ $latest_version_slug ] ) && $plugins[ $latest_version_slug ]['plugin_dir_path'] );

		// Return latest version's plugin dir path
		return $is_setup_exist ? $plugins[ $latest_version_slug ]['plugin_dir_path'] . 'divi-100-setup/' : plugin_dir_path( __FILE__ );
	}
}

/**
 * Sanitize hexacode or RGBA color
 *
 * @param string
 * @return string|bool
 */
if ( ! function_exists( 'et_divi_100_sanitize_alpha_color' ) ) {
	function et_divi_100_sanitize_alpha_color( $color ) {
		// Trim unneeded whitespace
		$color = str_replace( ' ', '', $color );

		// If this is hex color, validate and return it
		if ( 1 === preg_match( '|^#([A-Fa-f0-9]{3}){1,2}$|', $color ) ) {
			return $color;
		}

		// If this is rgb, validate and return it
		elseif ( 'rgb(' === substr( $color, 0, 4 ) ) {
			sscanf( $color, 'rgb(%d,%d,%d)', $red, $green, $blue );

			if ( ( $red >= 0 && $red <= 255 ) &&
				 ( $green >= 0 && $green <= 255 ) &&
				 ( $blue >= 0 && $blue <= 255 )
				) {
				return "rgb({$red},{$green},{$blue})";
			}
		}

		// If this is rgba, validate and return it
		elseif ( 'rgba(' === substr( $color, 0, 5 ) ) {
			sscanf( $color, 'rgba(%d,%d,%d,%f)', $red, $green, $blue, $alpha );

			if ( ( $red >= 0 && $red <= 255 ) &&
				 ( $green >= 0 && $green <= 255 ) &&
				 ( $blue >= 0 && $blue <= 255 ) &&
				   $alpha >= 0 && $alpha <= 1
				) {
				return "rgba({$red},{$green},{$blue},{$alpha})";
			}
		}

		return false;
	}
}

/**
 * Sanitize toggle value
 *
 * @param string
 * @return string
 */
if ( ! function_exists( 'et_divi_100_sanitize_toggle' ) ) {
	function et_divi_100_sanitize_toggle( $toggle, $default = 'off' ) {
		$valid_values = array( 'on', 'off' );

		if ( ! in_array( $toggle, $valid_values ) ) {
			return $default;
		} else {
			return $toggle;
		}
	}
}

/**
 * Load Divi 100 settings class file
 */
require_once( et_divi_100_get_setup_dir_path() . 'class-divi-100-settings.php' );

/**
 * Load Divi 100 utils class file
 */
require_once( et_divi_100_get_setup_dir_path() . 'class-divi-100-utils.php' );