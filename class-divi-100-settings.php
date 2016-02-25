<?php
// Prevent file from being loaded directly
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

if ( ! class_exists( 'Divi_100_Settings' ) ) {
	/**
	 * Constructing settings page for Divi 100 plugins
	 */
	class Divi_100_Settings {

		protected $settings;
		protected $saved_values;

		function __construct( $settings ) {
			// Define settings args
			$this->settings     = wp_parse_args( $settings, $this->default_settings() );

			// Get saved value
			$saved_values       = maybe_unserialize( get_option( $this->settings['plugin_id'], array() ) );
			$this->saved_values = $saved_values && is_array( $saved_values ) ? $saved_values : array();

			// Register settings page and add admin scripts
			add_action( 'admin_menu',            array( $this, 'add_submenu' ), 30 ); // Make sure the priority is higher than Divi 100's add_menu()
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		}

		/**
		 * Define default field
		 *
		 * @return array
		 */
		function default_field() {
			return array(
				'type'                       => 'text', // text|url|select|upload
				'has_preview'                => true,
				'preview_prefix'             => 'style-',
				'preview_height'             => 182,
				'id'                         => 'field_id',
				'label'                      => __( 'Label' ),
				'placeholder'                => '',
				'description'                => false,
				'options'                    => array(
					'value' => 'label',
				),
				'sanitize_callback'          => 'sanitize_text_field',
				'button_active_text'         => __( 'Change' ),
				'button_inactive_text'       => __( 'Select' ),
				'button_remove_text'         => __( 'Remove' ),
				'media_uploader_title'       => __( 'Select Image' ),
				'media_uploader_button_text' => __( 'Use This Image' )
			);
		}

		/**
		 * Define default settings
		 *
		 * @return array
		 */
		function default_settings() {
			return array(
				'plugin_id'        => 'divi_100_plugin_id',
				'preview_dir_url'  => plugin_dir_url( __FILE__ ) . '../preview/',
				'title'            => false,
				'description'      => false,
				'fields'           => array(
					$this->default_field()
				),
				'button_save_text' => __( 'Save Changes' ),
			);
		}

		/**
		 * Get saved value
		 *
		 * @param  string value key
		 * @param  mixed  default value
		 * @return mixed
		 */
		function get_value( $key, $default = '' ) {
			if ( isset( $this->saved_values[ $key ] ) ) {
				return $this->saved_values[ $key ];
			}

			return $default;
		}

		/**
		 * Get field types that need to be verified against its defined options
		 *
		 * @return array
		 */
		function get_types_verified_against_options() {
			return array( 'select' );
		}

		/**
		 * Check whether settings has particular field type
		 *
		 * @param string field type
		 * @return bool
		 */
		function has_field_type( $type ) {
			if ( in_array( $type, wp_list_pluck( $this->settings['fields'], 'type' ) ) ) {
				return true;
			}

			return false;
		}

		/**
		 * Enqueue dashboard scripts
		 *
		 * @return void
		 */
		function enqueue_scripts() {
			if ( isset( $_GET['page'] ) && $this->settings['plugin_id'] === $_GET['page'] ) {
				$dependencies = array( 'jquery' );

				if ( $this->has_field_type( 'color' ) ) {
					$dependencies[] = 'iris';
				}

				if ( $this->has_field_type( 'upload' ) ) {
					wp_enqueue_media();
				}

				wp_enqueue_script( $this->settings['plugin_id'] . '-admin_scripts', plugin_dir_url( __FILE__ ) . 'js/admin-scripts.js', $dependencies, '0.0.1', true );
				wp_localize_script( $this->settings['plugin_id'] . '-admin_scripts', 'et_divi_100_js_params', apply_filters( 'et_divi_100_js_params', array(
					'preview_dir_url' => esc_url( $this->settings['preview_dir_url'] ),
				) ) );
			}
		}

		/**
		 * Add submenu
		 * @return void
		 */
		function add_submenu() {
			add_submenu_page(
				'et_divi_100_options',
				esc_html( $this->settings['title'] ),
				esc_html( $this->settings['title'] ),
				'switch_themes',
				$this->settings['plugin_id'],
				array( $this, 'render_settings' )
			);
		}

		/**
		 * Get saved fields based on the settings
		 *
		 * @return array
		 */
		function get_saved_fields() {
			$fields = array();
			$plugin_id = $this->settings['plugin_id'];

			if ( ! empty( $this->settings['fields'] ) ) {
				foreach ( $this->settings['fields'] as $field ) {
					$field      = wp_parse_args( $field, $this->default_field() );
					$field_id   = $field['id'];
					$field_type = $field['type'];
					$field_data = array(
						'name'              => $plugin_id . '-' . $field_id,
						'id'                => $field['id'],
						'sanitize_callback' => $field['sanitize_callback']
					);

					if ( in_array( $field['type'], $this->get_types_verified_against_options() ) ) {
						$field_data['options'] = $field['options'];
					}

					$fields[] = $field_data;

					if ( 'upload' === $field_type ) {
						$fields[] = array(
							'name'              => $plugin_id . '-' . $field_id . '-id',
							'id'                => $field['id'] . '-' . $field_id . '-id' ,
							'sanitize_callback' => 'intval'
						);
					}
				}
			}

			return $fields;
		}

		/**
		 * Render settings page and its fields
		 *
		 * @return void
		 */
		function render_settings() {
			$is_settings_updated = false;
			$plugin_id           = $this->settings['plugin_id'];
			$nonce               = "{$plugin_id}_nonce";

			// Settings saving mechanism
			if ( isset( $_POST[ $nonce ] ) ) {
				$is_settings_updated         = true;
				$is_settings_updated_success = false;

				// Verify nonce and user permission
				if ( wp_verify_nonce( $_POST[ $nonce ], $nonce ) && current_user_can( 'switch_themes' ) ) {

					$saved_fields = array();

					// Generate list that need to be saved based on given settings.
					foreach ( $this->get_saved_fields() as $saved_field_key ) {

						// Make sure that the passed input existst
						if ( ! isset( $_POST[ $saved_field_key['name'] ] ) ) {
							continue;
						}

						$input = $_POST[ $saved_field_key['name'] ];

						// Existance of options element implies that input has to be verified against options list
						if ( isset( $saved_field_key['options'] ) && ! in_array( $_POST[ $saved_field_key['name'] ], array_flip( $saved_field_key['options'] ) ) ) {
							$input = '';
						}

						// Sanitize using defined callback
						$saved_fields[ $saved_field_key['id'] ] = call_user_func( $saved_field_key['sanitize_callback'], $input );
					}

					// Update option
					update_option( $plugin_id, maybe_serialize( $saved_fields ) );

					// Update saved values value
					$this->saved_values = maybe_unserialize( get_option( $plugin_id ) );

					// Update submission status & message
					$is_settings_updated_message = __( 'Your setting has been updated.' );
					$is_settings_updated_success = true;
				} else {
					$is_settings_updated_message = __( 'Error authenticating request. Please try again.' );
				}
			}

			?>
			<div class="wrap">
				<?php if ( $this->settings['title'] ) { ?>
					<h1><?php echo esc_html( $this->settings['title'] ); ?></h1>
				<?php } ?>

				<?php if ( $is_settings_updated ) { ?>
					<div id="setting-error-settings_updated" class="updated settings-error notice is-dismissible <?php echo $is_settings_updated_success ? '' : 'error' ?>">
						<p>
							<strong><?php echo esc_html( $is_settings_updated_message ); ?></strong>
						</p>
						<button type="button" class="notice-dismiss">
							<span class="screen-reader-text"><?php _e( 'Dismiss this notice.' ); ?></span>
						</button>
					</div>
				<?php } ?>

				<form action="" method="POST">
					<?php if ( $this->settings['description'] ) { ?>
						<?php echo wpautop( $this->settings['description'] ); ?>
					<?php } ?>

					<?php if ( ! empty( $this->settings['fields'] ) ) {
						echo '<table class="form-table et-divi-100-form-table"><tbody>';

							// Loop fields
							foreach ( $this->settings['fields'] as $field ) {
								$field    = wp_parse_args( $field, $this->default_field() );
								$value_id = $field['id'];
								$field_id = "{$plugin_id}-{$value_id}";
								?>
								<tr data-type="<?php echo esc_attr( $field['type'] ); ?>">
									<th scope="row">
										<?php
											if ( $field['label'] ) {
												printf(
													'<label for="%1$s">%2$s</label>',
													esc_attr( $field_id ),
													esc_attr( $field['label'] )
												);
											}
										?>
									</th>
									<td>
										<?php
											// Display field based on its type
											switch ( $field['type'] ) {
												// Upload
												case 'upload':
													printf(
														'<input name="%1$s" id="%1$s" class="input-src" type="hidden" value="%2$s">',
														esc_attr( $field_id ),
														esc_attr( $this->get_value( $value_id ) )
													);

													printf(
														'<input name="%1$s-id" id="%1$s-id" class="input-id" type="hidden" value="%2$s">',
														esc_attr( $field_id ),
														esc_attr( $this->get_value( $value_id . '-id' ) )
													);

													printf(
														'<p>
															<button id="%1$s-button-upload" class="button button-upload" data-button-active-text="%2$s" data-button-inactive-text="%3$s" data-media-uploader-title="%5$s" data-media-uploader-button-text="%6$s">%2$s</button>
															<a href="#" id="%1$s-button-remove" class="button-remove" style="margin-left: 10px; display: none;">%4$s</a>
														</p>',
														esc_attr( $field_id ),
														esc_attr( $field['button_active_text'] ),
														esc_attr( $field['button_inactive_text'] ),
														esc_html( $field['button_remove_text'] ),
														esc_attr( $field['media_uploader_title'] ),
														esc_attr( $field['media_uploader_button_text'] )
													);

													// Print description
													$this->render_description( $field['description' ] );

													// Print preview
													$has_preview = ( $this->get_value( $value_id, false ) && $this->get_value( $value_id, false ) );

													$preview_image = $has_preview ? sprintf(
														'<img src="%1$s" style="%2$s" />',
														esc_attr( $this->get_value( $value_id ) ),
														esc_attr( 'max-width: 100%;' )
													) : '';

													printf(
														'<div class="option-preview" id="%1$s-preview" style="%2$s">%3$s</div',
														esc_attr( $field_id ),
														esc_attr( 'width: 100%; margin-top: 20px;' ),
														$preview_image
													);
													break;

												// Select
												case 'select':
													printf(
														'<select name="%1$s" id="%1$s" data-preview-prefix="%2$s" data-preview-height="%3$s">',
														esc_attr( $field_id ),
														esc_attr( $field['preview_prefix'] ),
														esc_attr( $field['preview_height'] )
													);

													if ( is_array( $field['options'] ) && ! empty( $field['options'] ) ) {
														foreach ( $field['options'] as $option_value => $option_label ) {
															printf(
																'<option value="%1$s" %3$s>%2$s</option>',
																esc_attr( $option_value ),
																esc_attr( $option_label ),
																"{$option_value}" === $this->get_value( $value_id ) ? 'selected="selected"' : ''
															);
														}
													}

													echo '</select>';

													// Print description
													$this->render_description( $field['description' ] );

													// Print preview
													if ( $field['has_preview'] ) {
														$has_preview   = $this->get_value( $value_id, false );
														$preview_style = $has_preview ? sprintf( ' min-height: %1$dpx', $field['preview_height'] ) : '';
														$preview_url   = $has_preview ? $this->settings['preview_dir_url'] . $field['preview_prefix'] . $this->get_value( $value_id ) . '.gif' : '';
														$preview_image = $has_preview ? sprintf(
															'<img src="%1$s" />',
															esc_attr( $preview_url )
														) : '';

														printf(
															'<div class="option-preview" style="margin-top: 20px;%1$s">%2$s</div><!-- .option-preview -->',
															esc_attr( $preview_style ),
															$preview_image
														);
													}

													break;

												case 'color':
													printf(
														'<input type="text" id="%1$s" name="%1$s" placeholder="%2$s" value="%3$s" class="regular-text colorpicker" readonly />',
														esc_attr( $field_id ),
														esc_attr( $field['placeholder'] ),
														esc_attr( $this->get_value( $value_id ) )
													);

													// Print description
													$this->render_description( $field['description' ] );
													break;

												// URL
												case 'url':
													printf(
														'<input type="url" id="%1$s" name="%1$s" placeholder="%2$s" value="%3$s" class="regular-text" />',
														esc_attr( $field_id ),
														esc_attr( $field['placeholder'] ),
														esc_attr( $this->get_value( $value_id ) )
													);

													// Print description
													$this->render_description( $field['description' ] );
													break;

												// Text
												default:
													printf(
														'<input type="text" id="%1$s" name="%1$s" placeholder="%2$s" value="%3$s" class="regular-text" />',
														esc_attr( $field_id ),
														esc_attr( $field['placeholder'] ),
														esc_attr( $this->get_value( $value_id ) )
													);

													// Print description
													$this->render_description( $field['description' ] );
													break;
											}
										?>
									</td>
								</tr>
								<?php
							}

						echo '</tbody></table>';

						wp_nonce_field( $nonce, $nonce );

						printf(
							'<p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="%s"></p>',
							esc_attr( $this->settings['button_save_text'] )
						);
					} ?>

				</form><!-- form -->
			</div><!-- .wrap -->
			<?php
		}

		/**
		 * Print description field
		 *
		 * @param string|bool
		 * @return void
		 */
		function render_description( $description ) {
			if ( $description ) {
				printf(
					'<p class="description">%1$s</p>',
					esc_html( $description )
				);
			}
		}
	}
}