jQuery(document).ready(function ($) {
	var $form  = $('.et-divi-100-form-table');

	// Loop each option
	$form.find('tr').each(function(){
		var $field = $(this),
			type = $field.attr('data-type'),
			$input,
			et_divi_100_file_frame;

		switch( type ) {
			case "color":
				$input = $field.find('input.colorpicker');

				// Setup colorpicker
				$input.iris({
					hide : false,
					width : 350,
					palettes : true
				});

				break;
			case "select":
				$input = $field.find( 'select' );

				// Update preview whenever select is changed
				$input.change( function() {
					var $select          = $(this),
						preview_prefix   = $select.attr( 'data-preview-prefix' ),
						$selected_option = $select.find('option:selected'),
						selected_value   = $selected_option.val(),
						preview_height   = parseInt( $input.attr('data-preview-height') ),
						preview_file     = preview_prefix + selected_value,
						$preview_wrapper = $select.parents('td').find('.option-preview'),
						$preview;

					if( selected_value !== '' ) {
						$preview = $('<img />', {
							src : et_divi_100_js_params.preview_dir_url + preview_file + '.gif'
						});

						$preview_wrapper.css({ 'minHeight' : preview_height }).html( $preview );
					} else {
						$preview_wrapper.css({ 'minHeight' : '' }).empty();
					}
				});
				break;
			case "upload":
				var $input_src = $field.find('.input-src'),
					$input_id = $field.find('.input-id'),
					$button_upload = $field.find('.button-upload'),
					$button_remove = $field.find('.button-remove'),
					button_upload_active_text = $button_upload.attr('data-button-active-text'),
					button_upload_inactive_text = $button_upload.attr('data-button-inactive-text'),
					media_uploader_title = $button_upload.attr('data-media-uploader-title'),
					media_uploader_button_text = $button_upload.attr('data-media-uploader-button-text'),
					$preview = $field.find('.option-preview');

				// Check background image status
				if ( $input_src.val() !== '' ) {
					$button_upload.text( button_upload_active_text);
					$button_remove.show();
				}

				// Upload background image button
				$button_upload.on( 'click', function( event ){
					event.preventDefault();

					// If the media frame already exists, reopen it.
					if ( et_divi_100_file_frame ) {

						// Open frame
						et_divi_100_file_frame.open();

						return;
					} else {

						// Create the media frame.
						et_divi_100_file_frame = wp.media.frames.et_divi_100_file_frame = wp.media({
							title: media_uploader_title,
							button: {
								text: media_uploader_button_text,
							},
							multiple: false,
							library : {
								type: 'image'
							}
						});

						// When an image is selected, run a callback.
						et_divi_100_file_frame.on( 'select', function() {
							// We set multiple to false so only get one image from the uploader
							attachment = et_divi_100_file_frame.state().get('selection').first().toJSON();

							// Update input fields
							$input_src.val( attachment.url );

							$input_id.val( attachment.id );

							// Update Previewer
							$preview.html( $( '<img />', {
								src : attachment.url,
								style : 'max-width: 100%;'
							} ) );

							// Update button text
							$button_upload.text( button_upload_active_text );
							$button_remove.show();
						});

						// Finally, open the modal
						et_divi_100_file_frame.open();
					}
				});

				// Remove background image
				$button_remove.on( 'click', function( event ) {
					event.preventDefault();

					// Remove input
					$input_src.val('');
					$input_id.val('');

					// Remove preview
					$preview.empty();

					// Update button text
					$button_upload.text( button_upload_inactive_text );
					$button_remove.hide();
				});
				break;
		}
	})
});