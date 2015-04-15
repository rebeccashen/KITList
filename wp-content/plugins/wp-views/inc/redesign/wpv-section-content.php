<?php

add_action('view-editor-section-extra', 'add_view_content', 10, 2);

function add_view_content($view_settings, $view_id) {
    global $views_edit_help;

    /* This section will be visible if
     * - this is a View (not WPA) edit page and
     * - the 'filter-extra' section is displayed (not hidden).
     *
     * Apparently default behaviour for sections is to be visible unless
     * $view_settings['sections-show-hide'][ $section_name ] == 'off'
     *
     * Note that the container div has class js-wpv-settings-filter-extra, which will cause it to be shown or hidden
     * simultanneously with the filter-extra section when user changes the according option Screen options.
     */ 
	$is_section_hidden = false;

	// Hide if we're not editing a View
	if( isset( $view_settings['view-query-mode'] )
		&& 'normal' != $view_settings['view-query-mode'] )
	{
		
		$is_section_hidden = true;

	// Hide if 'filter-extra' section should be hidden.
	} else if( isset( $view_settings['sections-show-hide'] )
		&& isset( $view_settings['sections-show-hide']['filter-extra'] )
		&& 'off' == $view_settings['sections-show-hide']['filter-extra'] )
	{
		$is_section_hidden = true;
	}
	$hide_class = $is_section_hidden ? 'hidden' : '';
	
	?>
	<div class="wpv-setting-container wpv-setting-container-horizontal wpv-settings-complete-output js-wpv-settings-content js-wpv-settings-filter-extra <?php echo $hide_class; ?>">

		<div class="wpv-settings-header">
			<h3>
				<?php _e( 'Filter and Loop Output Integration', 'wpv-views' ) ?>
				<i class="icon-question-sign js-display-tooltip" data-header="<?php echo $views_edit_help['complete_output']['title']; ?>" data-content="<?php echo $views_edit_help['complete_output']['content']; ?>"></i>
			</h3>
		</div>

		<div class="wpv-setting">
			<div class="js-code-editor code-editor content-editor" data-name="complete-output-editor">
				<?php
					$full_view = get_post( $view_id );
					$content = $full_view->post_content;
				?>
				<div class="code-editor-toolbar js-code-editor-toolbar">
					<ul>
						<?php
						do_action( 'wpv_views_fields_button', 'wpv_content' );
						do_action( 'wpv_cred_forms_button', 'wpv_content' );
						?>
						<li>
							<button class="button-secondary js-code-editor-toolbar-button js-wpv-media-manager" data-id="<?php echo $view_id;?>" data-content="wpv_content">
								<i class="icon-picture"></i>
								<span class="button-label"><?php _e('Media','wpv-views'); ?></span>
							</button>
						</li>
					</ul>
				</div>
				<textarea cols="30" rows="10" id="wpv_content" name="_wpv_settings[content]" autocomplete="off"><?php echo $content; ?></textarea>
				<?php
				wpv_formatting_help_combined_output();
				?>
			</div>
			<p class="update-button-wrap js-wpv-update-button-wrap">
				<span class="js-wpv-message-container"></span>
				<button data-success="<?php echo htmlentities( __('Content updated', 'wpv-views'), ENT_QUOTES ); ?>" data-unsaved="<?php echo htmlentities( __('Content not saved', 'wpv-views'), ENT_QUOTES ); ?>" data-nonce="<?php echo wp_create_nonce( 'wpv_view_content_nonce' ); ?>" class="js-wpv-content-update button-secondary" disabled="disabled"><?php _e('Update', 'wpv-views'); ?></button>
			</p>
		</div>

	</div>
<?php }
