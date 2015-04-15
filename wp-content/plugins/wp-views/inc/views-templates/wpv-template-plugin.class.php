<?php
require WPV_PATH_EMBEDDED . '/inc/views-templates/wpv-template.class.php';

class WPV_template_plugin extends WPV_template
{

    private $wpml_original_post_id;
    
    function init() {
		parent::init();
		
		add_action( 'wpv_action_views_settings_sections', array( $this, 'add_wpml_settings' ), 50 );
		add_action( 'wpv_action_views_settings_sections', array( $this, 'admin_settings' ), 60 );
		add_action( 'edit_form_after_title', array( $this, 'insert_content_template_toolset_messages' ), 5 );
		add_action( 'edit_form_after_title', array( $this, 'insert_content_template_description_section' ), 5 );
		add_action( 'edit_form_after_title', array( $this, 'insert_content_template_edit_help_box' ) );
		
		add_filter( 'wp_editor_expand', array( $this, 'disable_wp_editor_expand_for_content_templates' ), 99, 2 );
    }
	
	function insert_content_template_toolset_messages( $post ) {
		if (
			is_object( $post )
			&& isset( $post->post_type )
			&& $post->post_type == 'view-template'
		) {
			?>
			<div class="js-wpv-content-template-toolset-messages"></div>
			<?php
		}
	}
	
	function insert_content_template_description_section( $post ) {
		if (
			is_object( $post )
			&& isset( $post->post_type )
			&& $post->post_type == 'view-template'
		) {
			$wpv_content_template_decription  = get_post_meta( $post->ID, '_wpv-content-template-decription', true );
			?>
			<div class="wpv-content-template-description" style="margin-top:20px">
				<button class="js-wpv-ct-description-button button-secondary"<?php echo ( $wpv_content_template_decription != '' ) ? ' style="display:none"' : ''; ?>><?php _e( "Add description", 'wpv-views' ); ?></button>
				<div class="js-wpv-ct-description-button-div<?php echo ( $wpv_content_template_decription == '' ) ? ' hidden' : ''; ?>">
					<p><?php _e( "Describe this Content Template", 'wpv-views' ); ?></p>
					<textarea id="wpv-ct-description" class="js-wpv-ct-description" name="_wpv-content-template-decription" cols="72" rows="4" style="width: 100%"><?php echo esc_js($wpv_content_template_decription) ?></textarea>
				</div>
			</div>
			<?php
		}
	}
	
	function insert_content_template_edit_help_box( $post ) {
		if (
			is_object( $post )
			&& isset( $post->post_type )
			&& $post->post_type == 'view-template'
		) {
			global $WP_Views;
            $options = $WP_Views->get_options();
			$wpv_list_of_types = array();
			$first_p = '';
			$show_help = get_option('wpv_content_template_show_help');
            $post_types = get_post_types( array('public' => true), 'objects' );
            $taxonomies = get_taxonomies( '', 'objects' );
            $exclude_tax_slugs = array();
			$exclude_tax_slugs = apply_filters( 'wpv_admin_exclude_tax_slugs', $exclude_tax_slugs );
            foreach ( $post_types as $post_type ) {
                $type = $post_type->name;
                if ( isset( $options['views_template_for_' . $type] ) && $options['views_template_for_' . $type] == $post->ID  ) {
                    $wpv_list_of_types[] = __( 'Single ', 'wpv-views' ) . $post_type->label;
                }
                if ( isset(  $options['views_template_archive_for_' . $type] ) &&  $options['views_template_archive_for_' . $type] == $post->ID  ) {
                    $wpv_list_of_types[] = __( 'Archive ', 'wpv-views' ) . $post_type->label;
                }
            }
            foreach ( $taxonomies as $category_slug => $category ) {
                if ( in_array( $category_slug, $exclude_tax_slugs ) || ! $category->show_ui ) {
                    continue;
                }
                $type = $category->name;
                if ( isset( $options['views_template_loop_' . $type] ) && $options['views_template_loop_' . $type] == $post->ID ) {
                    $wpv_list_of_types[] = $category->label;
                }
            }
            $wpv_list_of_types = implode( ', ', $wpv_list_of_types );
            $wpv_list_of_types = preg_replace("/\, /",' and ',$wpv_list_of_types, -1);
            if ( $wpv_list_of_types != '' ) {
				$wpv_list_of_types .= '.';
				$first_p = sprintf( __( 'This Content Template will replace the content of %s', 'wpv-views' ), $wpv_list_of_types );
			} else {
				$first_p = __( 'This Content Template will replace the content of the elements you assign it to.', 'wpv-views' );
			}
			if ( $show_help == 1 ) {
				$hidden = 'false';
			} else {
				$hidden = 'true';
			}
			
			$data_def = array(
				'text'			=> '<p>'
								. $first_p
								. '</p><p>'
								. __( 'It starts empty and you should add fields to it. To add fields, click on the V icon below.', 'wpv-views' )
								. WPV_MESSAGE_SPACE_CHAR
								. __( 'You can add HTML and CSS to style the fields and design the page template.', 'wpv-views' ) 
								. '</p>',
				'close'			=> 'true',
				'hidden'		=> $hidden,
				'classname'		=> 'js-wpv-content-template-edit-help-box'
			);
			wpv_toolset_help_box( $data_def );
		}
	}
	
	/**
	* disable_wp_editor_expand_for_content_templates
	*
	* Remove the Distraction Free Writing button from the toolbar of the Content Template edit screen
	*
	* This DFW mode was added in WordPress 4.0 and the $post_type parameter was added in WordPress 4.1
	* Ref: https://developer.wordpress.org/reference/hooks/wp_editor_expand/
	* 
	* @since 1.7
	*/
	
	function disable_wp_editor_expand_for_content_templates( $return, $post_type = 'post' ) {
		if ( $post_type == 'view-template' ) {
			$return = false;
		}
		return $return;
	}

    function add_view_template_settings() {
        global $post;
        $this->wpml_original_post_id = null;
        if ( is_object($post) ){
            global $post;
            $this->initialize_if_wpml_copy();
        }
        
        ?>
        <?php // TODO: Move this code to correct JS file ?>
        <script type="text/javascript">
         <?php
            if ($this->wpml_original_post_id) {
                $original_post = get_post($this->wpml_original_post_id);
                global $pagenow, $sitepress;

            ?>
                jQuery(document).ready(function($){
                    <?php if ($pagenow == 'post-new.php') {
                        // initialize the title and body by copying from the original post.
                        ?>
                        jQuery('#title').val('<?php echo esc_js($original_post->post_title . ' - ' . $sitepress->get_display_language_name($_GET['lang'], $_GET['lang'])); ?>');
                        jQuery('#content').val(editor_decode64('<?php echo base64_encode($original_post->post_content); ?>'));
                    <?php } else {
                        // Editing an existing post
                        ?>

                    <?php }

                    ?>
                    jQuery('#views_template input[type=checkbox]').prop('disabled', 'true');
                });
            <?php }
            ?>

        </script>

        <?php
        add_meta_box( 'views_template',
                __( 'Content Template Settings', 'wpv-views' ),
                array($this, 'view_settings_meta_box'), $post->post_type,
                'side', 'high' );
        add_meta_box( 'views_template_html_extra',
                __( 'Content Template CSS and JS', 'wpv-views' ),
                array($this, 'view_settings_meta_html_extra'), $post->post_type,
                'normal', 'high' );

    }

    function view_settings_meta_html_extra() {
        /**
         * Add admin metabox for custom CSS and javascript
         */
        global $post;
		// @todo this should not be here at all... maybe on the other metabox, or somewhere else
        $user_id = get_current_user_id();
        $show_hightlight = get_user_meta( $user_id, 'show_highlight', true );
        if (
			! isset( $show_hightlight ) 
			|| $show_hightlight == '' ) {
            $show_hightlight = 1;
        }
		$show_help = get_option( 'wpv_content_template_show_help' );
		?>
		<input type="hidden" name="show_highlight" class="js-wpv-ct-syntax-highlight-on" value="<?php echo $show_hightlight; ?>" />
		<input type="hidden" name="show_help" class="js-wpv-ct-show-help" value="<?php echo $show_help; ?>" />
		<?php
        $template_extra_css = get_post_meta( $post->ID, '_wpv_view_template_extra_css', true );
        $template_extra_js = get_post_meta( $post->ID, '_wpv_view_template_extra_js', true );
        $template_extra_state = get_post_meta( $post->ID, '_wpv_view_template_extra_state', true );
		if ( empty( $template_extra_state ) ) {
			$template_extra_state = array();
		}
		$css_editor_state = 'off';
		$js_editor_state = 'off';
		if ( isset( $template_extra_state['css'] ) ) {
			if ( $template_extra_state['css'] === 'on' ) {
				$css_editor_state = 'on';
			}
		}
		if ( isset( $template_extra_state['js'] ) ) {
			if ( $template_extra_state['js'] === 'on' ) {
				$js_editor_state = 'on';
			}
		}
        ?>
		<input type="hidden" name="_wpv_view_template_extra_state[css]" id="js-wpv-content-template-editor-state-css" value="<?php echo $css_editor_state; ?>" />
        <input type="hidden" name="_wpv_view_template_extra_state[js]" id="js-wpv-content-template-editor-state-js" value="<?php echo $js_editor_state; ?>" />
        <p>
            <?php _e( 'Here you can modify specific CSS and javascript to be used with this Content Template.','wpv-views' ); ?>
        </p>
		<ul id="wpv-content-template-extra" class="wpv-content-template-extra js-wpv-content-template-extra">
			<li class="wpv-has-itembox-header js-wpv-content-template-extra-item js-wpv-content-template-extra-css">
				<div class="wpv-content-template-extra-header wpv-itembox-header">
					<strong>
						<?php
						_e( 'CSS editor - add custom CSS to this Content Template', 'wpv-views' );
						?>
					</strong>
					<button class="button button-secondary button-small wpv-code-editor-toggler js-wpv-code-editor-toggler" data-kind="css" data-target="content-template-css-editor">
						<i class="icon-pushpin js-wpv-textarea-full" style="<?php if ( empty( $template_extra_css ) || $css_editor_state == 'on' ) { echo ' display:none;'; } ?>"></i>
						<span class="js-wpv-text-holder">
							<?php
							if ( $css_editor_state != 'on' || empty( $template_extra_css ) ) {
								_e( 'Open CSS editor', 'wpv-views' );
							} else {
								_e( 'Close CSS editor', 'wpv-views' );
							}
							?>
						</span>
					</button>
				</div>
				<div class="code-editor content-template-css-editor wpv-code-editor-closed js-wpv-code-editor js-wpv-content-template-css-editor<?php if ( $css_editor_state != 'on' || empty( $template_extra_css ) ) { echo ' js-wpv-code-editor-closed hidden'; } ?>">
					<textarea name="_wpv_view_template_extra_css" id="_wpv_view_template_extra_css" cols="97" rows="10"><?php echo $template_extra_css; ?></textarea>
					<?php wpv_formatting_help_extra_css( '' ); ?>
				</div>
			</li>
			
			<li class="wpv-has-itembox-header js-wpv-content-template-extra-item js-wpv-content-template-extra-js">
				<div class="wpv-content-template-extra-header wpv-itembox-header">
					<strong>
						<?php
						_e( 'JS editor - add custom javascript to this Content Template', 'wpv-views' );
						?>
					</strong>
					<button class="button button-secondary button-small wpv-code-editor-toggler js-wpv-code-editor-toggler" data-kind="js" data-target="content-template-js-editor">
						<i class="icon-pushpin js-wpv-textarea-full" style="<?php if ( empty( $template_extra_js ) || $js_editor_state == 'on' ) { echo ' display:none;'; } ?>"></i>
						<span class="js-wpv-text-holder">
							<?php
							if ( $js_editor_state != 'on' || empty( $template_extra_js ) ) {
								_e( 'Open JS editor', 'wpv-views' );
							} else {
								_e( 'Close JS editor', 'wpv-views' );
							}
							?>
						</span>
					</button>
				</div>
				<div class="code-editor content-template-js-editor wpv-code-editor-closed js-wpv-code-editor js-wpv-content-template-js-editor<?php if ( $js_editor_state != 'on' || empty( $template_extra_js ) ) { echo ' js-wpv-code-editor-closed hidden'; } ?>">
					<textarea name="_wpv_view_template_extra_js" id="_wpv_view_template_extra_js" cols="97" rows="10"><?php echo $template_extra_js; ?></textarea>
					<?php wpv_formatting_help_extra_js( '' ); ?>
				</div>
			</li>
		</ul>
    <?php }

    function initialize_if_wpml_copy() {

        global $pagenow, $sitepress, $post;

        // check for the new post page that has translation params
        if ( $sitepress && $pagenow == 'post-new.php' && isset($_GET['trid']) && isset($_GET['source_lang'])) {

            // Get the original ID
            $translations = $sitepress->get_element_translations($_GET['trid'], 'view-template');
            $this->wpml_original_post_id = $translations[$_GET['source_lang']]->element_id;

            // Copy extra CSS and JS as required.
            $template_extra_css = get_post_meta( $post->ID, '_wpv_view_template_extra_css', true );
            if ($template_extra_css == '') {
                $template_extra_css = get_post_meta( $this->wpml_original_post_id, '_wpv_view_template_extra_css', true );
                update_post_meta( $post->ID, '_wpv_view_template_extra_css', $template_extra_css);
            }

            $template_extra_js = get_post_meta( $post->ID, '_wpv_view_template_extra_js', true );
            if ($template_extra_js == '') {
                $template_extra_js = get_post_meta( $this->wpml_original_post_id, '_wpv_view_template_extra_js', true );
                update_post_meta( $post->ID, '_wpv_view_template_extra_js', $template_extra_js);
            }

            $template_extra_state = get_post_meta( $post->ID, '_wpv_view_template_extra_state', true );
            if ($template_extra_state == '') {
                $template_extra_state = get_post_meta( $this->wpml_original_post_id, '_wpv_view_template_extra_state', true );
                update_post_meta( $post->ID, '_wpv_view_template_extra_state', $template_extra_state);
            }

            // Copy the description as required
            $template_description = get_post_meta( $post->ID, '_wpv-content-template-decription', true );
            if ($template_description == '') {
                $template_description = get_post_meta( $this->wpml_original_post_id, '_wpv-content-template-decription', true );
                update_post_meta( $post->ID, '_wpv-content-template-decription', $template_description);
            }

            // Copy output mode as required.
            $output_mode = get_post_meta( $post->ID, '_wpv_view_template_mode', true );
            if ( !$output_mode ) {
                $output_mode = get_post_meta( $this->wpml_original_post_id, '_wpv_view_template_mode', true );
                if ($output_mode) {
                    update_post_meta( $post->ID, '_wpv_view_template_mode', $output_mode);
                }
            }

        }

        if ( $sitepress && $pagenow == 'post.php') {

            // Get the original ID
            $trid = $sitepress->get_element_trid($post->ID, 'post_view-template');

            $translations = $sitepress->get_element_translations($trid, 'view-template');
            foreach ($translations as $lang => $details) {
                if ($details->original) {
                    if ($post->ID != $translations[$lang]->element_id) {
                        $this->wpml_original_post_id = $translations[$lang]->element_id;
                    }
                    break;
                }
            }
        }

    }

    function view_settings_meta_box() {

        global $post;

        $output_mode = get_post_meta( $post->ID, '_wpv_view_template_mode', true );
		$layout_loop_template_for_view_id = get_post_meta( $post->ID, '_view_loop_id', true );
		
        if ( ! $output_mode ) {
            $output_mode = 'WP_mode';
        }

        ?>

        <p>
            <label for="_wpv_view_template_mode[]"><?php _e( 'Output mode', 'wpv-views' ); ?></label>:
            <select name="_wpv_view_template_mode[]" class="wpv_content_template_output_mode">
                <option value="WP_mode"<?php echo $output_mode == 'WP_mode' ? ' selected="selected"':''; ?>>
                    <?php _e( 'Auto-insert paragraphs', 'wpv-views' ); ?>
                </option>
                <option value="raw_mode"<?php echo $output_mode == 'raw_mode' ? ' selected="selected"' : ''; ?>>
                    <?php _e( 'Manual paragraphs', 'wpv-views' ); ?>
                </option>
            </select>
            <i class="icon-question-sign js-wpv-content-template-mode-tip" title="<?php echo esc_attr( __( 'Output mode', 'wpv-views' ) ); ?>"
                data-pointer-content-firstp="<?php echo esc_attr( __( 'Automaticaly paragraph (Normal WordPress output) - add paragraphs an breaks and resolve shortcodes', 'wpv-views' ) );?>"
                data-pointer-content-secondp="<?php echo esc_attr( __( 'Manual paragraph (Raw output) - only resolve shortcodes without adding line breaks or paragraphs', 'wpv-views' ) ); ?> "
                >
            </i>
        </p>

        <?php if ($this->wpml_original_post_id) {
            ?>
            <p>
                <strong><?php _e('Note','wpv-views'); ?>:</strong>
                <?php _e('These settings are synced with the post in the origin language and you can\'t change them here. They can only be changed on the post in the original language.', 'wpv-views'); ?></p>
            <?php
        }
        ?>

        <h4><?php _e( 'Use this Content Template for:', 'wpv-views' ); ?></h4>
		<?php
		if ( ! empty( $layout_loop_template_for_view_id ) ) {
			global $WP_Views;
			$view_loop_title = get_the_title( $layout_loop_template_for_view_id );
			$view_loop_status = get_post_status( $layout_loop_template_for_view_id );
			$view_loop_type_text = __( 'View', 'wpv-views' );
			if ( $WP_Views->is_archive_view( $layout_loop_template_for_view_id ) ) {
				$view_loop_type_text = __( 'WordPress Archive', 'wpv-views' );
			}
			$layout_loop_template_notice = '';
			if ( $view_loop_status == 'publish' ) {
				$view_loop_link = get_admin_url()."admin.php?page=views-editor&view_id=" . $layout_loop_template_for_view_id;
				$layout_loop_template_notice = sprintf( __( 'This Content Template is used as the loop block for the %s <a href="%s" target="_blank">%s</a>', 'wpv-views' ), $view_loop_type_text, $view_loop_link, $view_loop_title );
			} else {
				$layout_loop_template_notice = sprintf( __( 'This Content Template is used as the loop block for the trashed %s <strong>%s</strong>', 'wpv-views' ), $view_loop_type_text, $view_loop_title );
			}
			echo '<p class="toolset-alert toolset-alert-info">'
				. $layout_loop_template_notice
				. '</p>';
		} else {
			$this->_display_sidebar_content_template_settings();
		}
    }

    function view_settings_help() { ?>
            <p>
                <a class="wpv-help-link" target="_blank" href="http://wp-types.com/documentation/user-guides/view-templates/?utm_source=viewsplugin&utm_campaign=views&utm_medium=content-template-help-link-what-is-ct&utm_term=What is a Content Template"><?php_e( 'What is a Content Template', 'wpv-views' ); ?> &raquo;</a>
            </p>
            <p>
                <a class="wpv-help-link" target="_blank" href="http://wp-types.com/documentation/user-guides/editing-view-templates/?utm_source=viewsplugin&utm_campaign=views&utm_medium=content-template-help-link-editing-instructions&utm_term=Editing instructions"><?php _e( 'Editing instructions', 'wpv-views' )?>  &raquo;</a></p>
            <p>
                <a class="wpv-help-link" target="_blank" href="http://wp-types.com/documentation/user-guides/setting-view-templates-for-single-pages/?utm_source=viewsplugin&utm_campaign=views&utm_medium=content-template-help-link-how-to-apply&utm_term=How to apply Content Templates to content"><?php_e( 'How to apply Content Templates to content', 'wpv-views' ); ?>  &raquo;</a>
            </p>
        <?php
    }

	/**
	* wpv_ct_admin_enqueue_scripts
	*
	* Properly register and enqueue scripts for Content Template edit pages
	*
	* @since 1.7
	*/
	
	function wpv_ct_admin_enqueue_scripts( $hook ) {
		
		parent::wpv_ct_admin_enqueue_scripts( $hook );
		
		if ( ( $hook == 'post.php' || $hook == 'post-new.php' ) ) {
			global $post;
			if ( 'view-template' === $post->post_type ) {
				
				// Views admin alt stylesheet, maybe we should merge...
				wp_register_style( 'views-admin-alt-css' , WPV_URL . '/res/css/wpv-views.css', array(), WPV_VERSION );
				wp_enqueue_style( 'views-admin-alt-css' );
				
				// The rest of the scripts are added in Views code to all post.php and post-new.php screens

				// CodeMirror

				wp_enqueue_script( 'views-codemirror-script' );
				wp_enqueue_script( 'views-codemirror-overlay-script' );
				wp_enqueue_script( 'views-codemirror-xml-script' );
				wp_enqueue_script( 'views-codemirror-css-script' );
				wp_enqueue_script( 'views-codemirror-js-script' );
				wp_enqueue_script( 'views-codemirror-conf-script' );
				
				// Content Template main script
				$template_id = $post->ID;
				
				$ct_edit_translations = array(
					'template_id' => $template_id,
					'template_used_on' => __("Whatever", 'wpv-views'),
					'syntax_highlight_disable' => __( 'Disable Syntax Highlighting', 'wpv-views' ),
					'syntax_highlight_enable' => __( 'Enable Syntax Highlighting', 'wpv-views' ),
					'action_save' => __( 'Save', 'wpv-views' ),
					'pointer_close' => __( 'Close', 'wpv-views' ),
					'button_css_open' => __( 'Open CSS editor', 'wpv-views' ),
					'button_css_close' => __( 'Close CSS editor', 'wpv-views' ),
					'button_js_open' => __( 'Open JS editor', 'wpv-views' ),
					'button_js_close' => __( 'Close JS editor', 'wpv-views' ),
					'save_error_title_in_use' => __( 'A Content Template with that title already exists. Please use another title.', 'wpv-views' ),
					'save_error_title_empty' => __( 'You can not save a Content Template without title. Please fill the title input.', 'wpv-views' )
				);				
				wp_enqueue_script( 'views-content-template-js' );
				wp_localize_script( 'views-content-template-js', 'wpv_ct_edit_texts', $ct_edit_translations );
				
				// WordPress 4.0 compatibility: remove all the new fancy editor enhancements that break the highlighting and toolbars
				wp_dequeue_script( 'editor-expand' );

				wp_enqueue_style( 'views-codemirror-css' );
				wp_enqueue_style( 'toolset-font-awesome' );
				wp_enqueue_style( 'views-admin-css' );
				
			}
		}
	}

    function save_post_actions( $pidd, $post ) {

        if ( 
			$post->post_type == 'view-template' 
			&& $post->post_status == 'draft'
		) {
            // force the publish state.
            global $wpdb;
			$wpdb->query( "UPDATE {$wpdb->posts} SET post_status = 'publish' WHERE ID = " . $pidd );
            $post_name = $wpdb->get_var( "SELECT post_name FROM {$wpdb->posts} WHERE ID = " . $pidd );
            if ( 
				! isset( $post_name ) 
				|| empty( $post_name ) 
			) {
                $candidate_slug = sanitize_title( $_POST['post_title'] );
				$slug = wp_unique_post_slug( $candidate_slug, $pidd, 'publish', 'view-template', 0 );
				$wpdb->query( "UPDATE {$wpdb->posts} SET post_name = '" . $slug . "' WHERE ID = " . $pidd );
            }
        }

        // Make sure it's on the Content Template editor page before saving info.
        // Otherwise inline CT editor will delete info it shouldn't.
        if ( $post->post_type == 'view-template' && isset($_POST['_wpv_view_template_mode'])) {

            update_post_meta( $pidd, '_wpv-content-template-decription', '' );
            if ( isset( $_POST['_wpv_view_template_mode'][0] ) ) {
                update_post_meta( $pidd, '_wpv_view_template_mode',
                        $_POST['_wpv_view_template_mode'][0] );

                wpv_view_template_update_field_values( $pidd );
            }
            if ( isset( $_POST['_wpv_view_template_extra_css'] ) ) {
                update_post_meta( $pidd, '_wpv_view_template_extra_css',
                        $_POST['_wpv_view_template_extra_css'] );
            }
            if ( isset( $_POST['_wpv_view_template_extra_js'] ) ) {
                update_post_meta( $pidd, '_wpv_view_template_extra_js',
                        $_POST['_wpv_view_template_extra_js'] );
            }
            $template_meta_html_state = array();
            if ( isset( $_POST['_wpv_view_template_extra_state']['css'] ) ) {
                $template_meta_html_state['css'] = $_POST['_wpv_view_template_extra_state']['css'];
            }
            if ( isset( $_POST['_wpv_view_template_extra_state']['js'] ) ) {
                $template_meta_html_state['js'] = $_POST['_wpv_view_template_extra_state']['js'];
            }
            if ( !empty( $template_meta_html_state ) ) {
                update_post_meta( $pidd, '_wpv_view_template_extra_state',
                        $template_meta_html_state );
            }
            if ( isset( $_POST['show_highlight'] ) ) {
                $user_id = get_current_user_id();
				$show_highlight = esc_attr( $_POST['show_highlight']  );
                update_user_meta( $user_id, 'show_highlight', $show_highlight );
            }
			
			if ( isset( $_POST['show_help'] ) ) {
				$show_help = esc_attr( $_POST['show_help'] );
				update_option( 'wpv_content_template_show_help', $show_help );
			}

            //Save settings toggle status
            if ( isset($_POST['_wpv_content_template_settings_toggle_single']) ){
               update_post_meta( $pidd, '_wpv_content_template_settings_toggle_single',
                        $_POST['_wpv_content_template_settings_toggle_single'] );
            }
            if ( isset($_POST['_wpv_content_template_settings_toggle_posts']) ){
               update_post_meta( $pidd, '_wpv_content_template_settings_toggle_posts',
                        $_POST['_wpv_content_template_settings_toggle_posts'] );
            }
            if ( isset($_POST['_wpv_content_template_settings_toggle_taxonomy']) ){
               update_post_meta( $pidd, '_wpv_content_template_settings_toggle_taxonomy',
                        $_POST['_wpv_content_template_settings_toggle_taxonomy'] );
            }

            if ( isset($_POST['_wpv-content-template-decription']) ){
               update_post_meta( $pidd, '_wpv-content-template-decription',
                        $_POST['_wpv-content-template-decription'] );
            }

            //Save settings
            global $WP_Views;
            $options = $WP_Views->get_options();
            $this->clear_legacy_view_settings();
            // clear all options that have this template id
            foreach ($options as $key => $value) {
                 if ($value == $pidd){
                    $options[$key] = 0;
                 }
            }
            foreach ( $_POST as $index => $value ) {
                if ( strpos( $index, 'views_template_loop_' ) === 0 ) {
                    $options[$index] = $pidd;
                }
                if ( strpos( $index, 'views_template_for_' ) === 0 ) {
                    $options[$index] = $pidd;
                }
                if ( strpos( $index, 'views_template_archive_for_' ) === 0 ) {
                    $options[$index] = $pidd;
                }
            }
            $WP_Views->save_options($options);

        }
        
        if ( $post->post_type == 'view-template' ) {
			wpv_register_wpml_strings( $post->post_content );
        }

        // pass to the base class.
        parent::save_post_actions( $pidd, $post );
    }

    /**
     * If the post has a Content template
     * add an Content template edit link to post.
     */
    function edit_post_link( $link, $post_id ) {
		$options = get_option('wpv_options');
		if ( !isset($options['wpv_show_edit_view_link']) ){
			$options['wpv_show_edit_view_link'] = 1;	
		}
		if ( $options['wpv_show_edit_view_link'] == 1){
			$template_selected = get_post_meta( $post_id, '_views_template', true );

			if ( !current_user_can( 'manage_options' ) )
				return $link;

			if ( $template_selected ) {
				remove_filter( 'edit_post_link', array($this, 'edit_post_link'), 10,
						2 );

				ob_start();

				edit_post_link( __( 'Edit content template', 'wpv-views' ) . ' "' . get_the_title( $template_selected ) . '" ',
						'', '', $template_selected );

				$template_edit_link = ob_get_clean();

				$template_edit_link = apply_filters( 'wpv_edit_view_link', $template_edit_link );

				if ( isset( $template_edit_link ) && !empty( $template_edit_link ) ) {
					$link = $link . ' ' . $template_edit_link;
				}

				add_filter( 'edit_post_link', array($this, 'edit_post_link'), 10, 2 );
			}
		}
        return $link;
    }

    /**
     * Ajax function to set the current Content template to posts of a type
     * set in $_POST['type']
     *
     */
    function set_view_template_callback() {
        global $wpdb;

        if ( empty( $_POST ) || !wp_verify_nonce( 'set_view_template',
                        $_POST['wpnonce'] ) ) {

            $view_template_id = $_POST['view_template_id'];
            $type = $_POST['type'];

           // list($join, $cond) = $this->_get_wpml_sql( $type, $_POST['lang'] );
           wpv_update_dissident_posts_from_template( $view_template_id, $type);
        }
        
        die(); // this is required to return a proper result
    }

    function clear_legacy_view_settings() {
        global $wpdb;

        $wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_key='_views_template_new_type'" );
    }

    function legacy_view_settings( $options ) {
        global $wpdb;

        $view_tempates_new = $wpdb->get_results( "SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key='_views_template_new_type'" );

        foreach ( $view_tempates_new as $template_for_new ) {
            $value = unserialize( $template_for_new->meta_value );
            if ( $value ) {
                foreach ( $value as $type => $status ) {
                    if ( $status ) {
                        $options['views_template_for_' . $type] = $template_for_new->post_id;
                    }
                }
            }
        }


        return $options;
    }

    function admin_settings( $options ) {
        global $wpdb, $WP_Views;

        $items_found = array();

        $options = $this->legacy_view_settings( $options );

        if ( !isset( $options['wpv-theme-function'] ) ) {
            $options['wpv-theme-function'] = '';
        }
        if ( !isset( $options['wpv-theme-function-debug'] ) ) {
            $options['wpv-theme-function-debug'] = false;
        }

        ?>

        <div class="wpv-setting-container">
            <div class="wpv-settings-header">
                <h3><?php _e( 'Theme support for Content Templates','wpv-views' ); ?></h3>
            </div>
            <div class="wpv-setting">
                <p>
                    <?php _e( "Content Templates modify the content when called from",'wpv-views' ); ?> <a href="http://codex.wordpress.org/Function_Reference/the_content">the_content</a>
                    <?php _e( "function. Some themes don't use",'wpv-views' ); ?>  <a href="http://codex.wordpress.org/Function_Reference/the_content">the_content</a>
                    <?php _e( "function but define their own function.",'wpv-views' ); ?>
                </p>
                <div class="js-debug-settings-form">
                    <p>
                        <?php _e( "If Content Templates don't work with your theme then you can enter the name of the function your theme uses here:", 'wpv-views' ); ?>
                    </p>
                    <input type="text" name="wpv-theme-function" value="<?php echo $options['wpv-theme-function']; ?>" />
                    <p>
                        <?php _e( "Don't know the name of your theme function?", 'wpv-views' ); ?>
                    </p>
                    <p>
                        <?php $checked = $options['wpv-theme-function-debug'] ? ' checked="checked"' : ''; ?>
                        <label>
                            <input type="checkbox" name="wpv-theme-function-debug" value="1" <?php echo $checked; ?> />
                            <?php _e( "Enable debugging and go to a page that should display a Content Template and Views will display the call function name.", 'wpv-views' ); ?>
                        </label>
                    </p>
                    <?php
                        wp_nonce_field( 'wpv_view_templates_theme_support', 'wpv_view_templates_theme_support' );
                    ?>
                </div>

                <p class="update-button-wrap">
                    <span class="js-debug-update-message toolset-alert toolset-alert-success hidden">
                        <?php _e( 'Settings saved', 'wpv-views' ); ?>
                    </span>
                    <span class="js-debug-spinner spinner hidden"></span>
                    <button class="js-save-debug-settings button-secondary" disabled="disabled">
                        <?php _e( 'Save', 'wpv-views' ); ?>
                    </button>
                </p>

            </div>
        </div>

        <?php
    }

    function add_wpml_settings() {
        global $sitepress, $WP_Views; ?>

        <?php if ($sitepress): ?>

            <div class="wpv-setting-container">

                <div class="wpv-settings-header">
                    <h3><?php _e( 'Translating with WPML', 'wpv-views' ); ?></h3>
                </div>

                <div class="wpv-setting">

                    <?php if (defined('WPML_ST_VERSION')): ?>

                        <p><?php _e('Congratulations! You are running Views and WPML with the String Translation module, so you can easily translate everything.', 'wpv-views'); ?></p>
                        <p><?php _e('To translate static texts, wrap them in <strong>[wpml-string][/wpml-string]</strong> shortcodes.', 'wpv-views'); ?></p>

                    <?php else: ?>

                        <p>
                            <?php _e('You are running Views and WPML, but missing the String Translation module.', 'wpv-views'); ?>
                            <a href="http://wpml.org/download/wpml-string-translation/"><?php _e('The String Translation', 'wpv-views'); ?></a>
                            <?php _e('allows translating static texts in your Views and Content Templates.', 'wpv-views'); ?>
                        </p>

                    <?php endif; ?>

                    <?php $translatable_docs = array_keys($sitepress->get_translatable_documents()); ?>

                    <p><?php _e('How would you like to translate Content Templates?', 'wpv-views'); ?></p>
                    <ul class="js-wpml-settings-form">
                        <li>
                            <label>
                                <input type="radio" name="wpv-content-template-translation" value="0" <?php if(!in_array('view-template', $translatable_docs)) {echo ' checked="checked"';} ?>/> <?php _e('Use the same Content Templates for all languages', 'wpv-views'); ?>
                            </label>
                        </li>
                        <li>
                            <label>
                                <input type="radio" name="wpv-content-template-translation" value="1" <?php if(in_array('view-template', $translatable_docs)) {echo ' checked="checked"';} ?>/> <?php _e('Create different Content Templates for each language', 'wpv-views'); ?>
                            </label>
                        </li>
                        <?php wp_nonce_field( 'wpv_wpml_settings_nonce', 'wpv_wpml_settings_nonce' ); ?>
                    </ul>

                    <p>
                        <?php _e('Need help?', 'wpv-views'); ?> <a href="http://wp-types.com/documentation/multilingual-sites-with-types-and-views/?utm_source=viewsplugin&utm_campaign=views&utm_medium=edit-content-template-wpml-and-views-help&utm_term=Translating Views and Content Templates with WPML#3" target="_blank"> <?php _e('Translating Views and Content Templates with WPML', 'wpv-views'); ?> &raquo; </a>
                    </p>

                    <p class="update-button-wrap">
                        <span class="js-wpml-update-message hidden toolset-alert toolset-alert-success">
                            <?php _e( 'Settings saved', 'wpv-views' ); ?>
                        </span>
                        <span class="js-wpml-spinner spinner hidden"></span>
                        <button class="js-save-wpml-settings button-secondary" disabled="disabled">
                            <?php _e( 'Save', 'wpv-views' ); ?>
                        </button>
                    </p>

                </div>

            </div>
        <?php endif; ?>
    <?php
    }

	function wpv_save_wpml_settings() {

        if ( ! wp_verify_nonce( $_POST['wpv_wpml_settings_nonce'], 'wpv_wpml_settings_nonce' ) ) die("Security check");

        global $sitepress;

        $iclsettings['custom_posts_sync_option']['view-template'] = @intval($_POST['wpv-content-template-translation']);
        if(@intval($_POST['wpv-content-template-translation'])){
            $sitepress->verify_post_translations('view-template');
        }

        if(!empty($iclsettings)){
            $sitepress->save_settings($iclsettings);
        }

        echo 'ok';

        die();
    }

    function _ajax_get_post_type_loop_summary() {
        global $WP_Views;

        if ( wp_verify_nonce( $_POST['wpv_post_type_view_template_loop_nonce'],
                        'wpv_post_type_view_template_loop_nonce' ) ) {
            $options = $WP_Views->get_options();
            $options = $this->submit( $options );

            $WP_Views->save_options( $options );

            $this->_display_post_type_loop_summary( $options );
        }
        die();

    }

    function _ajax_get_post_type_loop_edit() {
        global $WP_Views;

        if ( wp_verify_nonce( $_POST['wpv_post_type_view_template_loop_nonce'],
                        'wpv_post_type_view_template_loop_nonce' ) ) {
            $options = $WP_Views->get_options();

            $new_options = $this->submit( $options );

            $WP_Views->save_options( $new_options );

            // determined what has changed so we can highlight anything that
            // might need updating.
            $post_types = get_post_types( array('public' => true), 'objects' );
            $changed_types = array();
            foreach ( $post_types as $post_type ) {
                $type = $post_type->name;
                if ( !isset( $options['views_template_for_' . $type] ) ) {
                    $options['views_template_for_' . $type] = 0;
                }
                if ( !isset( $new_options['views_template_for_' . $type] ) ) {
                    $new_options['views_template_for_' . $type] = 0;
                }

                if ( $options['views_template_for_' . $type] != $new_options['views_template_for_' . $type] ) {
                    $changed_types[] = $type;
                }
            }

            $this->_display_post_type_loop_admin( $new_options, $changed_types );
        }
        die();

    }

    function _display_post_type_loop_summary( $options ) {

        $post_types = get_post_types( array('public' => true), 'objects' );
        $view_templates = $this->get_view_template_titles();

        ?>
        <div id="wpv-view-template-post-type-summary">

            <p>
                <strong><?php _e( 'For single:', 'wpv-views' ); ?></strong>
            </p>

            <?php
                $selected = '';

                foreach ( $post_types as $post_type ) {
                    $type = $post_type->name;
                    if ( !isset( $options['views_template_for_' . $type] ) ) {
                        $options['views_template_for_' . $type] = 0;
                    }
                    if ( $options['views_template_for_' . $type] > 0 ) {
                        $selected .= '<li type=square">' . sprintf( __( '%s using "%s"', 'wpv-views' ),
                        $post_type->labels->name,
                        $view_templates[$options['views_template_for_' . $type]] ) . '</li>';
                    }
                }

                if ( $selected == '' ) {
                    $selected = __( 'There are no Content Templates being used for single post types.','wpv-views' );
                } else {
                    $selected = '<ul>' . $selected . '</ul>';
                }

                echo '<div>' . $selected . '</div>';

                ?>

                <p>
                    <strong><?php _e( 'For archive loop:', 'wpv-views' ); ?></strong>
                </p>

            <?php
                $selected = '';

                foreach ( $post_types as $post_type ) {
                    $type = $post_type->name;
                    if ( !isset( $options['views_template_archive_for_' . $type] ) ) {
                        $options['views_template_archive_for_' . $type] = 0;
                    }
                    if ( $options['views_template_archive_for_' . $type] > 0 ) {
                        $selected .= '<li>' . sprintf( __( '%s using "%s"','wpv-views' ), $post_type->labels->name, $view_templates[$options['views_template_archive_for_' . $type]] ) . '</li>';
                    }
                }

                if ( $selected == '' ) {
                    $selected = __( 'There are no Content Templates being used for post types in taxonomy archive loops.','wpv-views' );
                } else {
                    $selected = '<ul>' . $selected . '</ul>';
                }

                echo '<div>' . $selected . '</div>';

            ?>

            <input class="button-secondary" type="button" value="<?php _e( 'Edit', 'wpv-views' ); ?>" name="view_template_post_type_loop_edit" onclick="wpv_view_template_post_type_loop_edit();"/>
        </div>

        <?php
    }

    function _display_post_type_loop_admin( $options, $changed_types = array() ) {
        global $wpdb;

        $items_found = array();

        $post_types = get_post_types( array('public' => true), 'objects' );

        ?>

        <div id="wpv-view-template-post-type-edit" class="hidden">

            <?php
            wp_nonce_field( 'wpv_post_type_view_template_loop_nonce',
                    'wpv_post_type_view_template_loop_nonce' );

            ?>

            <table class="widefat" style="width:auto;">
                <thead>
                    <tr>
                        <th><?php _e( 'Post Types' ); ?></th>
                        <th><?php _e( 'Use this Content Template (Single)', 'wpv-views' ); ?></th>
                        <th><?php _e( 'Usage', 'wpv-views' ); ?></th>
                        <th><?php _e( 'Use this Content Template (Archive loop)', 'wpv-views' ); ?></th>
                    </tr>
                </thead>

                <tbody>
                    <?php
                    foreach ( $post_types as $post_type ) {
                        $type = $post_type->name;

                        ?>
                        <tr>
                            <td><?php echo $type; ?></td>
                            <td>
                                <?php
                                if ( !isset( $options['views_template_for_' . $type] ) ) {
                                    $options['views_template_for_' . $type] = 0;
                                }
                                $template = $this->get_view_template_select_box( '',
                                        $options['views_template_for_' . $type] );
                                $template = str_replace( 'name="views_template" id="views_template"',
                                        'name="views_template_for_' . $type . '" id="views_template_for_' . $type . '"',
                                        $template );
                                echo $template;
                                // add a preview button
                                // preview the latest post of this type.
                                list($join, $cond) = $this->_get_wpml_sql( $type );
                                $post_id = $wpdb->get_var( "SELECT MAX({$wpdb->posts}.ID) FROM {$wpdb->posts} {$join} WHERE post_type='{$type}' AND post_status in ('publish') {$cond}" );
                                if ( $post_id ) {
                                    $link = get_permalink( $post_id );

                                    ?>
                                    <a id="views_template_for_preview_<?php echo $type ?>" class="button" target="_blank" href="<?php echo $link; ?>" ><?php
                    _e( 'Preview', 'wpv-views' );

                                    ?></a>
                <?php
            }

            ?>

                            </td>
                            <td>
                                <?php
                                list($join, $cond) = $this->_get_wpml_sql( $type );
                                $posts = $wpdb->get_col( "SELECT {$wpdb->posts}.ID FROM {$wpdb->posts} {$join} WHERE post_type='{$type}' {$cond}" );

                                $count = sizeof( $posts );
                                if ( $count > 0 ) {
                                    $posts = "'" . implode( "','", $posts ) . "'";


                                    $set_count = $wpdb->get_var( "SELECT COUNT(post_id) FROM {$wpdb->postmeta} WHERE meta_key='_views_template' AND meta_value='{$options['views_template_for_' . $type]}' AND post_id IN ({$posts})" );
                                    if ( $set_count != $count && ($set_count == 0 || $options['views_template_for_' . $type] == 0) ) {
                                        echo '<div id="wpv_diff_template_' . $type . '">';
                                        echo '<p id="wpv_diff_' . $type . '">';
                                        echo sprintf( __( '%d %ss use a different template:',
                                                        'wpv-views' ),
                                                abs( $count - $set_count ),
                                                $type );
                                        if ( in_array( $type, $changed_types ) ) {
                                            echo ' <input type="button" id="wpv_update_now_' . $type . '" class="button-primary wpv-update-now" value="' . esc_html( sprintf( __( 'Update all %ss now',
                                                                    'wpv-views' ),
                                                            $type ) ) . '" />';
                                        } else {
                                            echo ' <input type="button" id="wpv_update_now_' . $type . '" class="button-secondary wpv-update-now" value="' . esc_html( sprintf( __( 'Update all %ss now',
                                                                    'wpv-views' ),
                                                            $type ) ) . '" />';
                                        }
                                        echo '<img id="wpv_update_loading_' . $type . '" src="' . WPV_URL . '/res/img/ajax-loader.gif" width="16" height="16" style="display:none" alt="loading" />';
                                        echo '</p>';
                                        echo '<p id="wpv_updated_' . $type . '" style="display:none">';
                                        echo sprintf( __( '<span id="%s">%d</span> %ss have updated to use this template.',
                                                        'wpv-views' ),
                                                'wpv_updated_count_' . $type,
                                                $count - $set_count, $type );
                                        echo '</p>';
                                        echo '</div>';
                                        $items_found[] = $type;
                                    } else {
                                        echo '<p>' . sprintf( __( 'All %s are using this template',
                                                        'wpv-views' ),
                                                $post_type->labels->name ) . '</p>';
                                    }
                                } else {
                                    echo '<p>' . sprintf( __( 'There are no %s',
                                                    'wpv-views' ),
                                            $post_type->labels->name ) . '</p>';
                                }

                                ?>
                            </td>
                            <td>
                                <?php
                                if ( !isset( $options['views_template_archive_for_' . $type] ) ) {
                                    $options['views_template_archive_for_' . $type] = 0;
                                }
                                $template = $this->get_view_template_select_box( '',
                                        $options['views_template_archive_for_' . $type] );
                                $template = str_replace( 'name="views_template" id="views_template"',
                                        'name="views_template_archive_for_' . $type . '" id="views_template_archive_for_' . $type . '"',
                                        $template );
                                echo $template;

                                ?>

                            </td>
                        </tr>
                <?php
            }

            ?>
                </tbody>
            </table>

            <input class="button-primary" type="button" value="<?php
           echo __( 'Save', 'wpv-views' );

           ?>" name="view_template_post_type_loop_save" onclick="wpv_view_template_post_type_loop_save();"/>
            <img id="wpv_save_view_template_post_type_loop_spinner" src="<?php echo WPV_URL; ?>/res/img/ajax-loader.gif" width="16" height="16" style="display:none" alt="loading" />

            <input class="button-secondary" type="button" value="<?php
           echo __( 'Cancel', 'wpv-views' );

            ?>" name="view_template_post_types_loop_cancel" onclick="wpv_view_template_post_type_loop_cancel();"/>

        </div>

        <?php
        if ( sizeof( $items_found ) > 0 ) {

            wp_nonce_field( 'set_view_template', 'set_view_template' );

            // we need to add some javascript

            ?>
            <script type="text/javascript" >
            <?php
            $lang = '';
            global $sitepress;
            if ( isset( $sitepress ) ) {
                $lang = $sitepress->get_current_language();
            }

            foreach ( $items_found as $type ) {

                ?>

                        jQuery('#wpv_update_now_<?php echo $type; ?>').click(function() {
                            jQuery('#wpv_update_loading_<?php echo $type; ?>').show();
                            var data = {
                                action : 'set_view_template',
                                view_template_id : '<?php echo $options['views_template_for_' . $type]; ?>',
                                wpnonce : jQuery('#set_view_template').attr('value'),
                                type : '<?php echo $type; ?>',
                                lang : '<?php echo $lang; ?>'
                            };

                            jQuery.post(ajaxurl, data, function(response) {
                                jQuery('#wpv_updated_count_<?php echo $type; ?>').html(response);
                                jQuery('#wpv_updated_<?php echo $type; ?>').fadeIn();
                                jQuery('#wpv_diff_<?php echo $type; ?>').hide();
                            });
                        })

                <?php
            }

            ?>
            </script>
            <?php
        }

    }

    function _ajax_get_taxonomy_loop_summary() {
        global $WP_Views;

        if ( wp_verify_nonce( $_POST['wpv_taxonomy_view_template_loop_nonce'],
                        'wpv_taxonomy_view_template_loop_nonce' ) ) {
            $options = $WP_Views->get_options();
            $options = $this->submit( $options );

            $WP_Views->save_options( $options );

            $this->_display_taxonomy_loop_summary( $options );
        }
        die();
    }

    function _display_taxonomy_loop_summary( $options ) {
        $view_templates = $this->get_view_template_titles();

        $selected = '';
        $taxonomies = get_taxonomies( '', 'objects' );
        $exclude_tax_slugs = array();
	$exclude_tax_slugs = apply_filters( 'wpv_admin_exclude_tax_slugs', $exclude_tax_slugs );
        foreach ( $taxonomies as $category_slug => $category ) {
            if ( in_array( $category_slug, $exclude_tax_slugs ) ) {
                continue;
            }
            if ( !$category->show_ui ) {
			continue; // Only show taxonomies with show_ui set to TRUE
		}
            $name = $category->name;
            if ( isset( $options['views_template_loop_' . $name] ) && $options['views_template_loop_' . $name] > 0 ) {
                $selected .= '<li type=square style="margin:0;">' . sprintf( __( '%s using "%s"',
                                        'wpv-views' ), $category->labels->name,
                                $view_templates[$options['views_template_loop_' . $name]] ) . '</li>';
            }
        }

        if ( $selected == '' ) {
            $selected = __( 'There are no Content Templates being used for Taxonomy archive loops.',
                            'wpv-views' ) . '<br />';
        } else {
            $selected = '<ul style="margin-left:20px">' . $selected . '</ul>';
        }

        ?>

        <div id="wpv-view-template-taxonomy-summary" style="margin-left:20px;">

        <?php echo $selected; ?>

            <input class="button-secondary" type="button" value="<?php
        echo __( 'Edit', 'wpv-views' );

        ?>" name="view_template_taxonomy_loop_edit" onclick="wpv_view_template_taxonomy_loop_edit();"/>
        </div>
        <?php
    }

    function _display_taxonomy_loop_admin( $options ) {
        global $wpdb;

        ?>

        <div id="wpv-view-template-taxonomy-edit" style="margin-left:20px;display:none;">

        <?php
        wp_nonce_field( 'wpv_taxonomy_view_template_loop_nonce',
                'wpv_taxonomy_view_template_loop_nonce' );

        ?>

            <table class="widefat" style="width:auto;">
                <thead>
                    <tr>
                        <th><?php _e( 'Loop' ); ?></th>
                        <th><?php
                    _e( 'Use this Content Template', 'wpv-views' );

        ?></th>
                    </tr>
                </thead>

                <tbody>

                    <?php
                    $taxonomies = get_taxonomies( '', 'objects' );
                    $exclude_tax_slugs = array();
			$exclude_tax_slugs = apply_filters( 'wpv_admin_exclude_tax_slugs', $exclude_tax_slugs );
                    foreach ( $taxonomies as $category_slug => $category ) {
                        if ( in_array( $category_slug, $exclude_tax_slugs ) ) {
                            continue;
                        }
                        if ( !$category->show_ui ) {
				continue; // Only show taxonomies with show_ui set to TRUE
			}
                        $name = $category->name;

                        ?>
                        <tr>
                            <td><?php echo $name; ?></td>
                            <td>
                                <?php
                                if ( !isset( $options['views_template_loop_' . $name] ) ) {
                                    $options['views_template_loop_' . $name] = '0';
                                }
                                $template = $this->get_view_template_select_box( '',
                                        $options['views_template_loop_' . $name] );
                                $template = str_replace( 'name="views_template" id="views_template"',
                                        'name="views_template_loop_' . $name . '" id="views_template_loop_' . $name . '"',
                                        $template );
                                echo $template;

                                $most_popular_term = $wpdb->get_var( "SELECT term_id FROM {$wpdb->term_taxonomy} WHERE taxonomy = '{$name}' AND count = (SELECT MAX(count) FROM {$wpdb->term_taxonomy} WHERE taxonomy = '{$name}')" );
                                if ( $most_popular_term ) {
                                    $link = get_term_link( intval( $most_popular_term ),
                                            $name );

                                    ?>
                                    <a id="views_template_loop_preview_<?php echo $name ?>" class="button" target="_blank" href="<?php echo $link; ?>" ><?php
                    _e( 'Preview', 'wpv-views' );

                                    ?></a>
                            <?php
                        }

                        ?>
                            </td>
                        </tr>
                <?php
            }

            ?>
                </tbody>
            </table>

            <input class="button-primary" type="button" value="<?php
            echo __( 'Save', 'wpv-views' );

            ?>" name="view_template_taxonomy_loop_save" onclick="wpv_view_template_taxonomy_loop_save();"/>
            <img id="wpv_save_view_template_taxonomy_loop_spinner" src="<?php echo WPV_URL; ?>/res/img/ajax-loader.gif" width="16" height="16" style="display:none" alt="loading" />

            <input class="button-secondary" type="button" value="<?php
            echo __( 'Cancel', 'wpv-views' );

            ?>" name="view_template_taxonomy_loop_cancel" onclick="wpv_view_template_taxonomy_loop_cancel();"/>

        </div>

        <?php
    }

    function submit( $options ) {
        $this->clear_legacy_view_settings();

        foreach ( $_POST as $index => $value ) {
            if ( strpos( $index, 'views_template_loop_' ) === 0 ) {
                $options[$index] = $value;
            }
            if ( strpos( $index, 'views_template_for_' ) === 0 ) {
                $options[$index] = $value;
            }
            if ( strpos( $index, 'views_template_archive_for_' ) === 0 ) {
                $options[$index] = $value;
            }
        }

        if ( isset( $_POST['wpv-theme-function'] ) ) {
            $options['wpv-theme-function'] = $_POST['wpv-theme-function'];
            $options['wpv-theme-function-debug'] = isset( $_POST['wpv-theme-function-debug'] ) && $_POST['wpv-theme-function-debug'];
        }

        return $options;
    }

    function hide_view_template_author() {
        global $pagenow, $post;
        if ( ($pagenow == 'post-new.php' && isset( $_GET['post_type'] ) && $_GET['post_type'] == 'view-template') ||
                ($pagenow == 'post.php' && isset( $_GET['action'] ) && $_GET['action'] == 'edit') ) {

            $post_type = $post->post_type;

            if ( $pagenow == 'post.php' && $post_type != 'view-template' ) {
                return;
            }

            ?>
            <script type="text/javascript">
                jQuery('#authordiv').hide();
            </script>
            <?php
        }

    }

    function show_admin_messages() {
        global $pagenow, $post;

        if ( $pagenow == 'post.php' && isset( $_GET['action'] ) && $_GET['action'] == 'edit' ) {

            $post_type = $post->post_type;

            if ( $pagenow == 'post.php' && $post_type != 'view-template' ) {
                return;
            }

            $open_tags = substr_count( $post->post_content, '[types' );
            $close_tags = substr_count( $post->post_content, '[/types' );
            if ( $close_tags < $open_tags ) {
                echo '<div id="message" class="error">';
                echo sprintf( __( '<strong>This template includes single-ended shortcodes</strong>. Please close all shortcodes to avoid processing errors. %sRead more%s',
                                'wpv-views' ),
                        '<a href="http://wp-types.com/faq/why-do-types-shortcodes-have-to-be-closed/?utm_source=viewsplugin&utm_campaign=views&utm_medium=edit-content-template-not-closed-shortcodes-message&utm_term=Read more" target="_blank">',
                        ' &raquo;</a>' );
                echo '</div>';
            }
        }
    }

    function disable_rich_edit_for_views( $state ) {
        global $pagenow, $post;
        if ( $state ) {
            if ( $pagenow == 'post.php' && isset( $_GET['action'] ) && $_GET['action'] == 'edit' ) {

                if ( isset( $post ) && isset( $post->post_type ) ) {
					$post_type = $post->post_type;
				} else if ( isset( $_GET['post'] ) && is_numeric( $_GET['post'] ) ) {
					$post_id = intval( $_GET['post'] );
					$post_type = get_post_type( $post_id );
				}
                if ( $post_type != 'view-template' && $post_type != 'view' ) {
                    return $state;
                }
                $state = 0;
            }

            if ( $pagenow == 'post-new.php' && isset( $_GET['post_type'] ) && ($_GET['post_type'] == 'view-template' || $_GET['post_type'] == 'view') ) {
                $state = 0;
            }
        }
        return $state;
    }

	/**
	* _display_sidebar_content_template_settings
	*
	* Show the checkboxes groups for Single, CPT Archives and Taxonomy Archives in the Content Template edit screen metabox
	*
	* @since unknown
	*/
    function _display_sidebar_content_template_settings() {
        global $WP_Views, $post, $wpdb, $pagenow;

        $toggle_taxonomy = $toggle_posts = 0;
        $toggle_single = 1;
        $id = -1;
        $asterisk = ' <span style="color:red;">*</span>';
        $show_asterisk_explanation = false;
        $options = $WP_Views->get_options();
        $post_types = get_post_types( array('public' => true), 'objects' );
        $taxonomies = get_taxonomies( '', 'objects' );
        $exclude_tax_slugs = array();
		$exclude_tax_slugs = apply_filters( 'wpv_admin_exclude_tax_slugs', $exclude_tax_slugs );
        wp_nonce_field( 'set_view_template', 'set_view_template' );
	
        if ( isset( $_GET['toggle'] ) ) {
             $toggle = explode( ',', $_GET['toggle'] );
             $toggle_taxonomy = $toggle[2];
             $toggle_posts = $toggle[1];
             $toggle_single = $toggle[0];
        } else if ( isset( $post ) && $pagenow != 'post-new.php' ) {
            if ( $this->wpml_original_post_id ) {
                $id = $this->wpml_original_post_id;
            } else {
                $id = $post->ID;
            }
            $template_options = get_post_meta( $id );
            // NOTE we are not exporting those _wpv_content_template_settings_toggle_* settings
            if ( isset( $template_options['_wpv_content_template_settings_toggle_single'] ) && isset( $template_options['_wpv_content_template_settings_toggle_single'][0] ) ) {
                $toggle_single = $template_options['_wpv_content_template_settings_toggle_single'][0];
            } else {
                $toggle_single = 0;
                foreach ( $options as $key => $val ) {
					if ( strpos( $key, 'views_template_for_' ) === 0 && $options[$key] == $id ) {
						$toggle_single = 1;
						break;
					}
                }
            }
            if ( isset( $template_options['_wpv_content_template_settings_toggle_posts'] ) ){
                $toggle_posts = $template_options['_wpv_content_template_settings_toggle_posts'][0];
            } else {
                $toggle_posts = 0;
                foreach ( $options as $key => $val ) {
					if ( strpos( $key, 'views_template_archive_for_' ) === 0 && $options[$key] == $id ) {
						$toggle_posts = 1;
						break;
					}
                }
            }
            if ( isset($template_options['_wpv_content_template_settings_toggle_taxonomy']) ){
                $toggle_taxonomy = $template_options['_wpv_content_template_settings_toggle_taxonomy'][0];
            } else {
                $toggle_taxonomy = 0;
				foreach ( $options as $key => $val ) {
					if ( strpos( $key, 'views_template_loop_' ) === 0 && $options[$key] == $id ) {
						$toggle_taxonomy = 1;
						break;
					}
                }
            }
        }
        $ct_selected = '';
        if ( isset( $_GET['ct_selected'] ) ) {
            $ct_selected = $_GET['ct_selected'];
        }
        ?>
        <p>
            <span class="js-wpv-content-template-open wpv-content-template-open" title="<?php echo esc_attr( __( "Click to toggle", 'wpv-views' ) ); ?>">
                <?php _e('Single pages:','wpv-views'); ?>
                <i class="icon-caret-down"></i>
            </span>
        </p>
        <div class="js-wpv-content-template-dropdown-list wpv-content-template-dropdown-list<?php echo $toggle_single == 0 ? ' hidden' : ''; ?>" id="wpv-content-template-dropdown-single-list">
            <ul>
                <li class="hidden">
                    <input type="hidden" name="_wpv_content_template_settings_toggle_single" value="<?php echo $toggle_single; ?>">
                </li>
              <?php
                 foreach ( $post_types as $post_type ) {
                    $type = $post_type->name;
                    if ( !isset( $options['views_template_for_' . $type] ) ) {
                         $options['views_template_for_' . $type] = 0;
                    }
					if ( !in_array( $options['views_template_for_' . $type], array( 0, $id ) ) ) {
						$show_asterisk_explanation = true;
					}
                    ?>
                    <li>
                        <input type="checkbox" value="1" id="views_template_for_<?php echo $type;?>" name="views_template_for_<?php echo $type;?>" class="js-wpv-check-for-icon"<?php echo ( $options['views_template_for_' . $type] == $id || 'views_template_for_' . $type == $ct_selected ) ? ' checked="checked"':''?>>
                        <label for="views_template_for_<?php echo $type;?>"><?php  echo $post_type->label; echo ( !in_array( $options['views_template_for_' . $type], array( 0, $id ) ) ) ? $asterisk : ''; ?></label>
                    <?php
                        if ( $options['views_template_for_' . $type] == $id ) {
                            $posts = $wpdb->get_col( "SELECT {$wpdb->posts}.ID FROM {$wpdb->posts} WHERE post_type='{$type}' AND post_status!='auto-draft'" );
                            $count = sizeof( $posts );
                            if ( $count > 0 ) {
                                $posts = "'" . implode( "','", $posts ) . "'";
                                $set_count = $wpdb->get_var( "SELECT COUNT(post_id) FROM {$wpdb->postmeta} WHERE
                                meta_key='_views_template' AND meta_value='{$options['views_template_for_' . $type]}'
                                AND post_id IN ({$posts})" );
                                if ( ( $count - $set_count ) > 0 && !$this->wpml_original_post_id){
                                    ?>
                                    <a class="button button-leveled button-small icon-warning-sign js-wpv-content-template-alert" id="wpv-content-template-alert-link-<?php echo $type;?>"
                                    data-type="<?php echo $type;?>" data-tid="<?php echo $id?>"
                                    href="<?php echo admin_url('admin-ajax.php'); ?>?action=wpv_ct_update_posts&amp;type=<?php echo $type;?>&amp;tid=<?php echo $id?>&amp;wpnonce=<?php echo wp_create_nonce( 'work_view_template' )?>">
                                        <?php echo sprintf( __( 'Bind %u %s ', 'wpv-views' ), $count - $set_count, $post_type->label ); ?>
                                    </a>
                                    <?php
                                }
                            }
                        }
                    ?>
                    </li>
                    <?php
                 }
              ?>
            </ul>
        </div>
		<div class="popup-window-container">

			<div class="wpv-dialog js-wpv-dialog-ct-apply-success">
				<div class="wpv-dialog-header">
					<h2><?php _e('Templates updated','wpv-views'); ?></h2>
				</div>
				<div class="wpv-dialog-content">
					<p><?php echo sprintf( __( 'All %ss were successfully updated',
													'wpv-views' ),
											'<span class="js-wpv-ct-type-updated"></span>'); ?></p>
				</div>
				<div class="wpv-dialog-footer">
					<button class="button-secondary js-dialog-close"><?php _e('Cancel','wpv-views'); ?></button>
				</div>
			</div>
        </div>

        <p>
            <span class="js-wpv-content-template-open wpv-content-template-open" title="<?php echo esc_attr( __( "Click to toggle", 'wpv-views' ) ); ?>">
                <?php _e('Post archives:','wpv-views'); ?>
                <i class="icon-caret-down"></i>
            </span>
        </p>
        <div class="js-wpv-content-template-dropdown-list wpv-content-template-dropdown-list<?php echo $toggle_posts == 0 ? ' hidden' : ''; ?>" id="wpv-content-template-dropdown-posts-list">
            <ul>
                <li class="hidden">
                    <input type="hidden" name="_wpv_content_template_settings_toggle_posts" value="<?php echo $toggle_posts?>">
                </li>
                  <?php
                  $custom_post_types_exist = false;
                     foreach ( $post_types as $post_type ) {
                        $type = $post_type->name;
                        if (!in_array($post_type->name, array('post', 'page', 'attachment')) && $post_type->has_archive) {
							if ( !isset( $options['views_template_archive_for_' . $type] ) ) {
								$options['views_template_archive_for_' . $type] = 0;
							}
						$custom_post_types_exist = true;
						if ( !in_array( $options['views_template_archive_for_' . $type], array( 0, $id ) ) ) {
							$show_asterisk_explanation = true;
						}
				?>
				<li>
					<input type="checkbox" value="1" id="views_template_archive_for_<?php echo $type;?>" name="views_template_archive_for_<?php echo $type;?>"<?php echo ( $options['views_template_archive_for_' . $type] == $id || 'views_template_archive_for_' . $type == $ct_selected ) ? ' checked="checked"':''?>>
					<label for="views_template_archive_for_<?php echo $type;?>"><?php  echo $post_type->label; echo ( !in_array( $options['views_template_archive_for_' . $type], array( 0, $id ) ) ) ? $asterisk : ''; ?></label>
				</li>
                        <?php }
                     }
                     if ( !$custom_post_types_exist ) { ?>
				<li><?php _e('There are no custom post types archives', 'wpv-views'); ?></li>
                     <?php }
                  ?>
              </ul>
        </div>

        <p>
            <span class="js-wpv-content-template-open wpv-content-template-open" title="<?php echo esc_attr( __( "Click to toggle", 'wpv-views' ) ); ?>">
                <?php _e('Taxonomy archives:','wpv-views'); ?>
                <i class="icon-caret-down"></i>
            </span>
        </p>
        <div class="js-wpv-content-template-dropdown-list wpv-content-template-dropdown-list<?php echo $toggle_taxonomy == 0 ? ' hidden': '' ;?>">
            <ul>
                <li class="hidden">
                    <input type="hidden" name="_wpv_content_template_settings_toggle_taxonomy" value="<?php echo $toggle_taxonomy?>">
                </li>
                  <?php
					$selected = '';
					foreach ( $taxonomies as $category_slug => $category ) {
						if ( in_array( $category_slug, $exclude_tax_slugs ) ) {
							continue;
						}
						if ( !$category->show_ui ) {
							continue; // Only show taxonomies with show_ui set to TRUE
						}
						$type = $category->name;
						if ( !isset( $options['views_template_loop_' . $type] ) ) {
							$options['views_template_loop_' . $type] = 0;
						}
						if ( !in_array( $options['views_template_loop_' . $type], array( 0, $id ) ) ) {
							$show_asterisk_explanation = true;
						}
                    ?>
				<li>
					<input type="checkbox" value="1" id="views_template_loop_<?php echo $type;?>" name="views_template_loop_<?php echo $type;?>"<?php echo ( $options['views_template_loop_' . $type] == $id || 'views_template_loop_' . $type == $ct_selected ) ? ' checked="checked"':''?>>
					<label for="views_template_loop_<?php echo $type;?>"><?php  echo $category->labels->name; echo ( !in_array( $options['views_template_loop_' . $type], array( 0, $id ) ) ) ? $asterisk : ''; ?></label>
				</li>
				<?php }
                ?>
            </ul>
        </div>
        <?php if ( $show_asterisk_explanation ) { ?>
        <hr />
		<span><?php _e( '<span style="color:red">*</span> A different Content Template is already assigned to this item', 'wpv-views' ); ?></span>
		<?php } ?>
        <?php
    }
}

/**
 * Update custom fields array for Content template on save
 * @param unknown_type $pidd post ID
 * @param unknown_type $post post reference
 */
function wpv_view_template_update_field_values( $pidd, $post = null ) {
    if ( $post == null ) {
        $post = get_post( $pidd );
    }
    $content = $post->post_content;
    $shortcode_expression = "/\\[(wpv-|types).*?\\]/i";

    // search for shortcodes
    $counts = preg_match_all( $shortcode_expression, $content, $matches );

    // iterate 0-level shortcode elements
    if ( $counts > 0 ) {
        $_wpv_view_template_fields = serialize( $matches[0] );
        update_post_meta( $pidd, '_wpv_view_template_fields',
                $_wpv_view_template_fields );
    }
}

