<?php

add_action('view-editor-section-layout', 'add_view_layout_template', 40, 4);

function add_view_layout_template( $view_settings, $view_layout_settings, $view_id, $user_id ) {
    global $views_edit_help;
	$dismissed_pointers = get_user_meta( $user_id, '_wpv_dismissed_pointers', true );
	if ( ! is_array( $dismissed_pointers ) || empty( $dismissed_pointers ) ) {
		$dismissed_pointers = array();
	}
	$dismissed_dialogs = get_user_meta( $user_id, '_wpv_dismissed_dialogs', true );
	if ( ! is_array( $dismissed_dialogs ) || empty( $dismissed_dialogs ) ) {
		$dismissed_dialogs = array();
	}
    wp_nonce_field( 'wpv-ct-inline-edit', 'wpv-ct-inline-edit' );
	wp_nonce_field( 'wpv_inline_content_template', 'wpv_inline_content_template' );
    $templates = array();
    $valid_templates = array();
    $first_time = get_post_meta( $view_id, '_wpv_first_time_load', true );
    if ( isset( $view_layout_settings['included_ct_ids'] ) ) {
        $templates = explode( ',', $view_layout_settings['included_ct_ids'] );
        $valid_templates = $templates;
    }
    if ( count( $templates ) > 0 ) {
		$attached_templates = count( $templates );
        //for ( $i=0; $i<$attached_templates; $i++ ) {
		foreach ( $templates as $key => $template_id ) {
			if ( is_numeric( $template_id ) ) {
				$template_post = get_post( $template_id );
				if ( 
					is_object( $template_post )
					&& $template_post->post_status  == 'publish'
					&& $template_post->post_type == 'view-template' 
				) {
				} else {
					unset( $valid_templates[$key] ); // remove Templates that might have been deleted or are missing
				}
            } else {
				unset( $valid_templates[$key] ); // remove Templates that might have been deleted or are missing
            }
        }
        if ( count( $templates ) != count( $valid_templates ) ) {
			$view_layout_settings['included_ct_ids'] = implode( ',', $valid_templates );
			update_post_meta( $view_id, '_wpv_layout_settings', $view_layout_settings );
        }
    }
    ?>
	<div id="attached-content-templates" class="wpv-settings-templates wpv-setting-container wpv-setting-container-horizontal wpv-settings-layout-markup js-wpv-settings-inline-templates"<?php echo ( count( $valid_templates ) < 1 ) ? ' style="display:none;"':'' ?>>
		<div class="wpv-settings-header">
			<h3><?php _e('Templates for this View', 'wpv-views') ?>
				<i class="icon-question-sign js-display-tooltip" data-header="<?php echo $views_edit_help['templates_for_view']['title']; ?>" data-content="<?php echo $views_edit_help['templates_for_view']['content']; ?>"></i>
			</h3>
		</div>
		<?php
		if ( $first_time == 'on') {
			$purpose = $view_settings['view_purpose'];
			if ( $purpose == 'slider' ) {
				wpv_get_view_ct_slider_introduction_data();
			}
		}
		?>

		<div class="js-wpv-content-template-view-list wpv-content-template-view-list wpv-setting">
			<ul class="wpv-inline-content-template-listing js-wpv-inline-content-template-listing">
				<?php
				if ( count( $valid_templates ) > 0 ) {
					$opened = false;
					if ( count( $valid_templates ) == 1 ) {
						$opened = true;
					}
					foreach ( $valid_templates as $valid_ct_id ) {
						// This is cached so it is OK to do that again
						$valid_ct_post = get_post( $valid_ct_id );
						wpv_list_view_ct_item( $valid_ct_post, $valid_ct_id, $view_id, $opened );
					}
				}
				?>
			</ul>
			<div class="js-wpv-content-template-section-errors"></div>
		</div>		
	</div>
	
	<!-- @todo: move this to the view-editor-section-hidden action -->
	<div id="js-wpv-inline-content-templates-dialogs" class="popup-window-container">
	
		<!-- Colorbox dialogs -->
		
		<?php
		$dismissed_classname = '';
		if ( isset( $dismissed_dialogs['remove-content-template-from-view'] ) ) {
			$dismissed_classname = ' js-wpv-dialog-dismissed';
		}
		?>
		
		<div class="wpv-dialog js-wpv-dialog-remove-content-template-from-view<?php echo $dismissed_classname; ?>">
            <div class="wpv-dialog-header">
                <h2><?php _e('Remove the Content Template from the view','wpv-views') ?></h2>
                <i class="icon-remove js-dialog-close"></i>
            </div>
            <div class="wpv-dialog-content">
                <p>
                    <?php _e("This will remove the link between your view and the Content Template.  The Content Template will not be deleted.") ?>
                </p>
				<p>
					<label for="wpv-dettach-inline-content-template-dismiss">
						<input type="checkbox" id="wpv-dettach-inline-content-template-dismiss" class="js-wpv-dettach-inline-content-template-dismiss" />
						<?php _e("Don't show this message again",'wpv-views') ?>
					</label>
            	</p>
            </div>
            <div class="wpv-dialog-footer">
                <button class="button js-dialog-close"><?php _e('Cancel','wpv-views') ?></button>
                <button class="button button-primary js-wpv-remove-template-from-view"><?php _e('Remove','wpv-views') ?></button>
            </div>
		</div>
		
		<!-- Pointers -->
		
		<?php
		$dismissed_classname = '';
		if ( isset( $dismissed_pointers['inserted-inline-content-template'] ) ) {
			$dismissed_classname = ' js-wpv-pointer-dismissed';
		}
		?>
		<div class="js-wpv-inserted-inline-content-template-pointer<?php echo $dismissed_classname; ?>">
			<h3><?php _e( 'Content Template inserted in the layout', 'wpv-views' ); ?></h3>
			<p>
				<?php
				_e('A Content Template works like a subroutine.', 'wpv-views');
				echo WPV_MESSAGE_SPACE_CHAR;
				_e('You can edit its content in one place and use it in several places in the View.', 'wpv-views');
				?>
			</p>
			<p>
				<label>
					<input type="checkbox" class="js-wpv-dismiss-pointer" data-pointer="inserted-inline-content-template" id="wpv-dismiss-inserted-inline-content-template-pointer" />
					<?php _e( 'Don\'t show this again', 'wpv-views' ); ?>
				</label>
			</p>
		</div>
	
	
	</div><!-- end of .popup-window-container -->
<?php 
delete_post_meta( $view_id, '_wpv_first_time_load' );
}

function wpv_list_view_ct_item( $template, $ct_id, $view_id, $opened = false ) {
    ?>
    <li id="wpv-ct-listing-<?php echo $ct_id?>" class="js-wpv-ct-listing js-wpv-ct-listing-show js-wpv-ct-listing-<?php echo $ct_id?> layout-html-editor" data-id="<?php echo $ct_id?>" data-viewid="<?php echo $view_id?>">
        <span class="wpv-inline-content-template-title" style="display:block;">
			<button class="button button-secondary button-small js-wpv-content-template-open wpv-content-template-open" data-target="<?php echo $ct_id?>" data-viewid="<?php echo $view_id?>">
				<i class="js-wpv-open-close-arrow icon-caret-<?php if ( $opened ) { echo 'up'; } else { echo 'down'; } ?>"> </i>
            </button>
			<strong><?php echo $template->post_title; ?></strong>
			<span class="wpv-inline-content-template-action-buttons">
				<button class="button button-secondary button-small js-wpv-ct-update-inline js-wpv-ct-update-inline-<?php echo $ct_id; ?>" disabled="disabled" data-unsaved="<?php echo htmlentities( __('Not saved', 'wpv-views'), ENT_QUOTES ); ?>" data-id="<?php echo $ct_id; ?>"><?php _e('Update','wpv-views'); ?></button>
				<button class="button button-secondary button-small js-wpv-ct-remove-from-view"><i class="icon-remove"></i> <?php _e('Remove','wpv-views'); ?></button>
			</span>
		</span>
        <div class="js-wpv-ct-inline-edit wpv-ct-inline-edit wpv-ct-inline-edit js-wpv-inline-editor-container-<?php echo $ct_id?> <?php if ( ! $opened ) { echo 'hidden'; } ?>" data-template-id="<?php echo $ct_id?>">
			<?php if ( $opened ) { ?>
			<div class="code-editor-toolbar js-code-editor-toolbar">
			   <ul class="js-wpv-v-icon js-wpv-v-icon-<?php echo $ct_id; ?>">
					<?php
					do_action( 'wpv_views_fields_button', 'wpv-ct-inline-editor-' . $ct_id );
					do_action( 'wpv_cred_forms_button', 'wpv-ct-inline-editor-' . $ct_id );
					?>
					<li>
						<button class="button-secondary js-code-editor-toolbar-button js-wpv-media-manager" data-id="<?php echo $ct_id; ?>" data-content="wpv-ct-inline-editor-<?php echo $ct_id; ?>">
							<i class="icon-picture"></i>
							<span class="button-label"><?php _e('Media','wpv-views'); ?></span>
						</button>
					</li>
			   </ul>
			</div>
			<textarea name="name" rows="10" class="js-wpv-ct-inline-editor-textarea" id="wpv-ct-inline-editor-<?php echo $ct_id; ?>" data-id="<?php echo $ct_id; ?>"><?php echo $template->post_content; ?></textarea>
			<?php
			wpv_formatting_help_inline_content_template( $template );
			?>
			<?php } ?>
		</div>
		<?php 
		if ( $opened ) {
		?>
		<script type="text/javascript">
			jQuery(document).ready(function($) {
				if ( typeof window["wpv_ct_inline_editor_<?php echo $ct_id; ?>"] === "undefined" ) {
					window["wpv_ct_inline_editor_<?php echo $ct_id; ?>"] = icl_editor.codemirror('wpv-ct-inline-editor-<?php echo $ct_id; ?>', true);
					window["wpv_ct_inline_editor_val_<?php echo $ct_id; ?>"] = window["wpv_ct_inline_editor_<?php echo $ct_id; ?>"].getValue();
					
					var wpv_inline_editor_qt = quicktags( { id: 'wpv-ct-inline-editor-<?php echo $ct_id; ?>', buttons: 'strong,em,link,block,del,ins,img,ul,ol,li,code,close' } );
					WPV_Toolset.CodeMirror_instance['wpv_ct_inline_editor_<?php echo $ct_id; ?>'] = window["wpv_ct_inline_editor_<?php echo $ct_id; ?>"];
					WPV_Toolset.add_qt_editor_buttons( wpv_inline_editor_qt, WPV_Toolset.CodeMirror_instance['wpv_ct_inline_editor_<?php echo $ct_id; ?>'] );
					
					window["wpv_ct_inline_editor_<?php echo $ct_id; ?>"].on('change', function() {
						if( window["wpv_ct_inline_editor_val_<?php echo $ct_id; ?>"] !=  window["wpv_ct_inline_editor_<?php echo $ct_id; ?>"].getValue()){
							$('.js-wpv-ct-update-inline-<?php echo $ct_id; ?>')
								.removeClass('button-secondary')
								.addClass('button-primary js-wpv-section-unsaved')
								.prop( 'disabled', false );
							setConfirmUnload( true );
						}
						else{
							$('.js-wpv-ct-update-inline-<?php echo $ct_id; ?>')
								.removeClass('button-primary js-wpv-section-unsaved')
								.addClass('button-secondary')
								.prop( 'disabled', true );
							$('.js-wpv-ct-update-inline-<?php echo $ct_id; ?>').parent().find('.toolset-alert-error').remove();
							if ($('.js-wpv-section-unsaved').length < 1) {
								setConfirmUnload(false);
							}
						}
					});
				}
			});

		</script>
		<?php } ?>
	</li>
    <?php
}
