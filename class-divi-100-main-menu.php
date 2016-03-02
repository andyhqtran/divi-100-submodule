<?php
// Prevent file from being loaded directly
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

if ( ! class_exists( 'Divi_100_Main_Menu' ) ) {
	/**
	 * Constructing Divi 100 main menu page
	 */
	class Divi_100_Main_Menu {
		public static $instance;

		/**
		* Gets the instance of the class
		*/
		public static function instance(){
			if ( null === self::$instance ){
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Add main menu for Divi 100
		 */
		function add_menu() {
			add_menu_page( 'Divi 100', 'Divi 100', 'switch_themes', 'et_divi_100_options', array( $this, 'add_options_page' ) );
		}

		/**
		 * Add nescesarry styling for admin.
		 * Note: wp_add_inline_style() strips content atribute's `/e` so hard coded styling is used
		 * @return void
		 */
		function add_scripts_styles() {
			?>
			<style type="text/css">
				li.toplevel_page_et_divi_100_options .dashicons-admin-generic:before { font-family: 'ETmodules'; content: '\e625'; width: 30px !important; font-size: 30px !important; margin-top: -5px; }
			</style>
			<?php
		}

		/**
		 * Welcome / main setup page
		 * @return void
		 */
		function add_options_page() {
			?>
			<div class="wrap">
				<h2><?php _e( 'Welcome to Divi 100!', 'custom-search-fields' ); ?></h2>
				<?php
					// Epic saga of Divi 100 goes here
				?>
			</div><!-- /.wrap -->
			<?php
		}

		/**
		 * Constructing the class
		 */
		function __construct() {
			if ( is_admin() && et_divi_100_is_active() ) {
				add_action( 'admin_menu', array( $this, 'add_menu' ), 15 );
				add_action( 'admin_head', array( $this, 'add_scripts_styles' ), 20 ); // Make sure the priority is higher than Divi's add_menu()
			}
		}
	}
	Divi_100_Main_Menu::instance();
}