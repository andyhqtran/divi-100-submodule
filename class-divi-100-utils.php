<?php
// Prevent file from being loaded directly
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

if ( ! class_exists( 'Divi_100_Utils' ) ) {
	/**
	 * Various Divi 100 utilities
	 */
	class Divi_100_Utils {
		protected $settings;

		/**
		 * Construct
		 *
		 * @param array saved settings
		 */
		function __construct( $settings ) {
			$this->settings = $settings;
		}

		/**
		 * Get value
		 *
		 * @param string key
		 * @param mixed default value
		 * @return mixed
		 */
		function get_value( $key, $default = '' ) {
			if ( isset( $this->settings[ $key ] ) ) {
				return $this->settings[ $key ];
			}

			return $default;
		}
	}
}