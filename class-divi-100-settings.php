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
		protected $has_most_updated_setup;

		function __construct( $settings ) {
			// Define settings args
			$this->settings     = wp_parse_args( $settings, $this->default_settings() );

			// Get saved value
			$saved_values       = maybe_unserialize( get_option( $this->settings['plugin_id'], array() ) );
			$this->saved_values = $saved_values && is_array( $saved_values ) ? $saved_values : array();

			// Has the most updated setup flag
			$this->has_most_updated_setup = et_divi_100_get_most_updated_plugin_slug() === $this->settings['plugin_slug'];
			$submenu_priority = $this->has_most_updated_setup ? 5 : 10;

			// Register settings page and add admin scripts
			add_action( 'admin_menu',            array( $this, 'add_menu' ), 30 ); // Make sure the priority is higher than Divi 100's add_menu()
			add_action( 'divi_100_submenu',      array( $this, 'add_submenu' ), $submenu_priority );
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
				'media_uploader_button_text' => __( 'Use This Image' ),
				'default'                    => '#888888',
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
				'plugin_slug'      => 'plugin_slug',
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
		 * Add nescesarry styling for admin.
		 * Note: wp_add_inline_style() strips content atribute's `/e` so hard coded styling is used
		 * @return void
		 */
		function add_main_menu_scripts_styles() {
			?>
			<style type="text/css">
				li.toplevel_page_et_divi_100_options .dashicons-admin-generic:before { font-family: 'ETmodules'; content: '\e625'; width: 30px !important; font-size: 30px !important; margin-top: -5px; }
			</style>
			<?php
		}

		/**
		 * Enqueue dashboard scripts
		 *
		 * @return void
		 */
		function enqueue_scripts() {
			if ( isset( $_GET['page'] ) && ( $this->settings['plugin_id'] === $_GET['page'] || ( 'et_divi_100_options' === $_GET['page'] && $this->has_most_updated_setup ) ) ) {
				$dependencies = array( 'jquery' );

				if ( $this->has_field_type( 'color' ) ) {
					$dependencies[] = 'iris';
				}

				if ( $this->has_field_type( 'upload' ) ) {
					wp_enqueue_media();
				}

				wp_enqueue_style( $this->settings['plugin_id'] . '-admin_style', plugin_dir_url( __FILE__ ) . '/css/admin-style.css', array(), '0.0.1' );
				wp_enqueue_script( $this->settings['plugin_id'] . '-admin_scripts', plugin_dir_url( __FILE__ ) . 'js/admin-scripts.js', $dependencies, '0.0.1', true );
				wp_localize_script( $this->settings['plugin_id'] . '-admin_scripts', 'et_divi_100_js_params', apply_filters( 'et_divi_100_js_params', array(
					'preview_dir_url' => esc_url( $this->settings['preview_dir_url'] ),
					'help_label' => esc_html__( 'Help' ),
				) ) );
			}
		}

		/**
		 * Add menu
		 * @return void
		 */
		function add_menu() {
			if ( $this->has_most_updated_setup ) {
				add_menu_page( 'Divi 100', 'Divi 100', 'switch_themes', 'et_divi_100_options', array( $this, 'render_settings' ) );

				add_action( 'admin_head', array( $this, 'add_main_menu_scripts_styles' ), 20 ); // Make sure the priority is higher than Divi's add_menu()

				do_action( 'divi_100_submenu' );
			}
		}

		/**
		 * Add submenu
		 * @return void
		 */
		function add_submenu() {
			if ( $this->has_most_updated_setup ) {
				add_submenu_page(
					'et_divi_100_options',
					esc_html( $this->settings['title'] ),
					esc_html( $this->settings['title'] ),
					'switch_themes',
					'et_divi_100_options',
					array( $this, 'render_settings' )
				);
			} else {
				add_submenu_page(
					'et_divi_100_options',
					esc_html( $this->settings['title'] ),
					esc_html( $this->settings['title'] ),
					'switch_themes',
					$this->settings['plugin_id'],
					array( $this, 'render_settings' )
				);
			}
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

			<div id="wrapper" class="et-divi-100-form">
				<div id="panel-wrap">
					<?php if ( $is_settings_updated ) { ?>
						<div id="setting-error-settings_updated" class="updated settings-error notice is-dismissible <?php echo $is_settings_updated_success ? '' : 'error' ?>" style="margin: 0 0 25px 0;">
							<p>
								<strong><?php echo esc_html( $is_settings_updated_message ); ?></strong>
							</p>
							<button type="button" class="notice-dismiss">
								<span class="screen-reader-text"><?php _e( 'Dismiss this notice.' ); ?></span>
							</button>
						</div>
					<?php } ?>

					<div id="epanel-top">
						<button class="save-button" id="epanel-save-top">Save Changes</button>
					</div>

					<?php // if ( $this->settings['description'] ) { ?>
						<?php // echo wpautop( $this->settings['description'] ); ?>
					<?php // } ?>

					<form action="" method="POST">
						<div id="epanel-wrapper">
							<div id="epanel">
								<div id="epanel-content-wrap">
									<div id="epanel-content">
										<div id="epanel-header">
											<?php if ( $this->settings['title'] ) { ?>
												<h1 id="epanel-title"><?php echo esc_html( $this->settings['title'] ); ?></h1>
											<?php } ?>
										</div><!-- #wrap-general.content-div -->
										<div id="wrap-general" class="content-div">
											<ul class="idTabs">
												<li class="ui-tabs-active">
													<a href="#general-1"><?php _e( 'General' ); ?></a>
												</li>
											</ul><!-- .idTabs -->
											<div id="general-1" class="tab-content">
												<?php
													if ( ! empty( $this->settings['fields'] ) ) {
														// Loop fields
														foreach ( $this->settings['fields'] as $field ) {
															$field    = wp_parse_args( $field, $this->default_field() );
															$value_id = $field['id'];
															$field_id = "{$plugin_id}-{$value_id}";
															?>

															<div class="epanel-box" data-type="<?php echo esc_attr( $field['type'] ); ?>">
																<div class="box-title">
																	<?php
																		if ( $field['label'] ) {
																			printf(
																				'<h3>%1$s</h3>',
																				esc_attr( $field['label'] )
																			);
																		}

																		if ( $field['description' ]  ) {
																			printf(
																				'<div class="box-descr"><p>%1$s</p></div><!-- .box-descr -->',
																				esc_attr( $field['description'] )
																			);
																		}
																	?>
																</div><!-- .box-title -->
																<div class="box-content">
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
																						<button id="%1$s-button-upload" class="button button-upload" data-button-active-text="%2$s" data-button-inactive-text="%3$s" data-media-uploader-title="%5$s" data-media-uploader-button-text="%6$s" style="margin: 0;">%2$s</button>
																						<a href="#" id="%1$s-button-remove" class="button-remove" style="margin-left: 20px; display: none; height: 40px; line-height: 40px; color: #C1C1C1;">%4$s</a>
																					</p>',
																					esc_attr( $field_id ),
																					esc_attr( $field['button_active_text'] ),
																					esc_attr( $field['button_inactive_text'] ),
																					esc_html( $field['button_remove_text'] ),
																					esc_attr( $field['media_uploader_title'] ),
																					esc_attr( $field['media_uploader_button_text'] )
																				);

																				// Print preview
																				$has_preview = ( $this->get_value( $value_id, false ) && $this->get_value( $value_id, false ) );

																				$preview_image = $has_preview ? sprintf(
																					'<img src="%1$s" style="%2$s" />',
																					esc_attr( $this->get_value( $value_id ) ),
																					esc_attr( 'max-width: 100%;' )
																				) : '';

																				printf(
																					'<div class="option-preview" id="%1$s-preview" style="%2$s">%3$s</div>',
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

																				// Print preview
																				if ( $field['has_preview'] ) {
																					$has_preview   = $this->get_value( $value_id, false );
																					$preview_style = $has_preview ? sprintf( ' min-height: %1$dpx', $field['preview_height'] ) : '';
																					$preview_url   = $has_preview ? $this->settings['preview_dir_url'] . $field['preview_prefix'] . $this->get_value( $value_id ) . '.gif' : '';
																					$preview_image = $has_preview ? sprintf(
																						'<img src="%1$s" />',
																						esc_url( $preview_url )
																					) : '';

																					printf(
																						'<div class="option-preview" style="margin-top: 20px;%1$s">%2$s</div><!-- .option-preview -->',
																						esc_attr( $preview_style ),
																						$preview_image
																					);
																				}

																				break;

																			// Toggle
																			case 'toggle':
																				printf(
																					'<select name="%1$s" id="%1$s" data-preview-prefix="%2$s" data-preview-height="%3$s" class="et-pb-toggle-select">',
																					esc_attr( $field_id ),
																					esc_attr( $field['preview_prefix'] ),
																					esc_attr( $field['preview_height'] )
																				);

																				$toggle_options = array( 'off', 'on' );

																				$selected_value = et_divi_100_sanitize_toggle( $this->get_value( $value_id ) );

																				foreach ( $toggle_options as $option_value ) {
																					printf(
																						'<option value="%1$s" %2$s>%1$s</option>',
																						esc_attr( $option_value ),
																						"{$option_value}" === $selected_value ? 'selected="selected"' : ''
																					);
																				}

																				echo '</select>';

																				echo sprintf(
																					'<div class="et_pb_yes_no_button et_pb_%1$s_state" style="max-width: 195px;">
																						<span class="et_pb_value_text et_pb_on_value">%2$s</span>
																						<span class="et_pb_button_slider"></span>
																						<span class="et_pb_value_text et_pb_off_value">%3$s</span>
																					</div>',
																					esc_attr( $selected_value ),
																					esc_html__( 'Enable' ),
																					esc_html__( 'Disable' )
																				);

																				echo '</select>';

																				break;

																			case 'color':
																				printf(
																					'<button class="reset-color" data-for="%1$s">%2$s</button>',
																					esc_attr( $field_id ),
																					esc_html__( 'Reset Color' )
																				);

																				printf(
																					'<input type="text" id="%1$s" name="%1$s" placeholder="%2$s" value="%3$s" class="regular-text colorpicker" data-default="%4$s" />',
																					esc_attr( $field_id ),
																					esc_attr( $field['placeholder'] ),
																					esc_attr( $this->get_value( $value_id ) ),
																					esc_attr( $field['default'] )
																				);

																				break;

																			// URL
																			case 'url':
																				printf(
																					'<input type="text" id="%1$s" name="%1$s" placeholder="%2$s" value="%3$s" />',
																					esc_attr( $field_id ),
																					esc_attr( $field['placeholder'] ),
																					esc_attr( $this->get_value( $value_id ) )
																				);
																				break;

																			// Text
																			default:
																				printf(
																					'<input type="text" id="%1$s" name="%1$s" placeholder="%2$s" value="%3$s" />',
																					esc_attr( $field_id ),
																					esc_attr( $field['placeholder'] ),
																					esc_attr( $this->get_value( $value_id ) )
																				);
																				break;
																		}
																	?>
																</div><!-- .box-content -->
																<span class="box-description"></span>
															</div><!-- .epanel-box -->
															<?php
														}
													}
												?>
											</div> <!-- #general-1.tab-content -->

										</div><!-- #epanel-header -->
									</div><!-- #epanel-content -->
								</div><!-- #epanel-content-wrap -->
							</div><!-- #epanel -->
						</div><!-- #epanel-wrapper -->

						<div id="epanel-bottom">
							<?php
								// Print nonce
								wp_nonce_field( $nonce, $nonce );

								// Print submit button
								printf(
									'<button class="save-button" name="save" id="epanel-save">%s</button>',
									esc_attr( $this->settings['button_save_text'] )
								);
							?>
						</div><!-- #epanel-bottom -->
					</form>

				</div><!-- #panel-wrap -->
			</div><!-- #wrapper -->
			<?php
		}
	}
}