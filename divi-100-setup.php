<?php
// Prevent file from being loaded directly
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

/**
 * Register Divi 100 main menu
 */
if ( ! function_exists( 'et_divi_100_add_menu' ) ) {
	/**
	 * Add main menu for Divi 100
	 */
	function et_divi_100_add_menu() {
		add_menu_page( 'Divi 100', 'Divi 100', 'switch_themes', 'et_divi_100_options', 'et_divi_100_options_page' );
	}
	add_action( 'admin_menu', 'et_divi_100_add_menu', 15 );

	/**
	 * Add nescesarry styling for admin.
	 * Note: wp_add_inline_style() strips content atribute's `/e` so hard coded styling is used
	 * @return void
	 */
	function et_divi_100_options_page_scripts_styles() {
		?>
		<style type="text/css">
			li.toplevel_page_et_divi_100_options .dashicons-admin-generic:before { font-family: 'ETmodules'; content: '\e625'; width: 30px !important; font-size: 30px !important; margin-top: -5px; }
		</style>
		<?php
	}
	add_action( 'admin_head', 'et_divi_100_options_page_scripts_styles', 20 ); // Make sure the priority is higher than Divi's add_menu()

	/**
	 * Welcome / main setup page
	 * @return void
	 */
	function et_divi_100_options_page() {
		?>
		<div class="wrap">
			<h2><?php _e( 'Welcome to Divi 100!', 'custom-search-fields' ); ?></h2>
			<?php
				// Epic saga of Divi 100 goes here
			?>
		</div><!-- /.wrap -->
		<?php
	}
}

if ( ! function_exists( 'et_divi_100_sanitize_alpha_color' ) ) {
	/**
	 * Sanitize hexacode or RGBA color
	 *
	 * @param string
	 * @return string|bool
	 */
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
 * Load Divi 100 class file
 */
require_once( plugin_dir_path( __FILE__ ) . 'class-divi-100-settings.php' );