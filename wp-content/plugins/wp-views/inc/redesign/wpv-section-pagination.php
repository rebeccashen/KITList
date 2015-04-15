<?php

/**
* wpv_show_hide_pagination
*
* We can enable this to hide the Pagination section
*
* @param $sections (array) sections on the editor screen
*
* @return $sections
*
* @since unknown
*/

add_filter('wpv_sections_filter_show_hide', 'wpv_show_hide_pagination', 1,1);

function wpv_show_hide_pagination($sections) {
	$sections['pagination'] = array(
		'name'		=> __('Pagination and Sliders Settings', 'wpv-views'),
		);
	return $sections;
}

/**
* add_view_pagination
*
* Creates the pagination section in the edit screen
*
* @param $view_settings
* @param $view_id
*
* @uses $views_edit_help (global)
*
* @since unknown
*/

add_action('view-editor-section-filter', 'add_view_pagination', 10, 2);

function add_view_pagination($view_settings, $view_id) { //TODO review that default values are set before we display any of this
    global $views_edit_help;
	$rollover_effects = array(
		'fade' => __('Fade', 'wpv-views'),
	//	'fadefast' => __('Fade fast', 'wpv-views'),
	//	'fadeslow' => __('Fade slow', 'wpv-views'),
		'slideleft' => __('Slide Left', 'wpv-views'),
		'slideright' => __('Slide Right', 'wpv-views'),
		'slideup' => __('Slide Up', 'wpv-views'),
		'slidedown' => __('Slide Down', 'wpv-views'),
	);
	$ajax_effects = array(
		'fade' => __('Fade', 'wpv-views'),
		'fadefast' => __('Fade fast', 'wpv-views'),
		'fadeslow' => __('Fade slow', 'wpv-views'),
		'slideh' => __('Slide horizontally', 'wpv-views'),
		'slidev' => __('Slide vertically', 'wpv-views'),
	);
	$hide = '';
	if (isset($view_settings['sections-show-hide']) && isset($view_settings['sections-show-hide']['pagination']) && 'off' == $view_settings['sections-show-hide']['pagination']) {
		$hide = ' hidden';
	}?>
	<div class="wpv-setting-container wpv-settings-pagination js-wpv-settings-pagination<?php echo $hide; ?>">
		<div class="wpv-settings-header">
			<h3>
				<?php _e( 'Pagination and Sliders Settings', 'wpv-views' ) ?>
				<i class="icon-question-sign js-display-tooltip" data-header="<?php echo $views_edit_help['pagination_and_sliders_settings']['title']; ?>" data-content="<?php echo $views_edit_help['pagination_and_sliders_settings']['content']; ?>"></i>
			</h3>
		</div>
		<div class="wpv-setting js-wpv-setting">
			<form class="js-pagination-settings-form">
				<?php
				if ( !isset( $view_settings['pagination'][0] ) ) $view_settings['pagination'][0] = 'disable';
				if ( !isset( $view_settings['pagination']['mode'] ) ) $view_settings['pagination']['mode'] = 'none';
				?>
				<input type="hidden" class="js-pagination-zero" name="pagination[]" value="<?php echo $view_settings['pagination'][0]; ?>" autocomplete="off" />
				<ul>
					<li>
						<?php $checked = $view_settings['pagination'][0]=='disable' ? ' checked="checked"' : ''; ?>
						<input type="radio" id="wpv-settings-no-pagination" class="js-wpv-pagination-mode" name="pagination[mode]" value="none"<?php echo $checked; ?> autocomplete="off" />
						<label for="wpv-settings-no-pagination"><strong><?php _e('No pagination','wpv-views') ?></strong></label>
						<span class="helper-text"><?php _e('All query results will display.','wpv-views') ?></span>
					</li>
					<li>
						<?php $checked = ( $view_settings['pagination'][0]=='enable' && $view_settings['pagination']['mode']=='paged' ) ? ' checked="checked"' : ''; ?>
						<input type="radio" id="wpv-settings-manual-pagination" class="js-wpv-pagination-mode" name="pagination[mode]" value="paged"<?php echo $checked; ?> autocomplete="off" />
						<label for="wpv-settings-manual-pagination"><strong><?php _e( 'Pagination enabled with manual transition', 'wpv-views' ) ?></strong></label>
						<span class="helper-text"><?php _e( 'The query results will display in pages, which visitors will switch.', 'wpv-views' ) ?></span>
					</li>
					<li>
						<?php $checked = $view_settings['pagination']['mode']=='rollover' ? ' checked="checked"' : ''; ?>
						<input type="radio" id="wpv-settings-ajax-pagination" class="js-wpv-pagination-mode" name="pagination[mode]" value="rollover"<?php echo $checked; ?> autocomplete="off" />
						<label for="wpv-settings-ajax-pagination"><strong><?php _e( 'Pagination enabled with automatic transition', 'wpv-views' ) ?></strong></label>
						<span class="helper-text"><?php _e( 'The query results will display in pages, which will switch automatically (good for sliders).', 'wpv-views' ) ?></span>
					</li>
				</ul>

				<div class="wpv-advanced-setting wpv-pagination-options-box">

					<h3 class="wpv-pagination-paged"><?php _e('Options for manual pagination','wpv-views'); ?></h3>
					<ul class="wpv-pagination-paged">
						<li>
							<label><?php _e('Number of items per page:', 'wpv-views')?></label>
							<select name="posts_per_page" autocomplete="off">
								<?php if ( !isset( $view_settings['posts_per_page'] ) ) $view_settings['posts_per_page'] = '10'; ?>
								<?php
								for($i = 1; $i < 50; $i++) {
									$selected = $view_settings['posts_per_page']==(string)$i ? ' selected="selected"' : '';
									echo '<option value="' . $i . '"' . $selected . '>'. $i . '</option>';
								}
								?>
							</select>
						</li>
						<li>
							<?php $checked = ( isset( $view_settings['ajax_pagination'][0] ) && $view_settings['ajax_pagination'][0] == 'disable') ? ' checked="checked"' : ''; ?>
							<input type="radio" id="wpv-settings-ajax-pagination-disabled" class="js-wpv-ajax_pagination" value="disable" name="ajax_pagination[]"<?php echo $checked; ?> autocomplete="off" />
							<label for="wpv-settings-ajax-pagination-disabled"><?php _e('Pagination updates the entire page', 'wpv-views'); ?></label>
						</li>
						<li>
							<?php $checked = ( isset( $view_settings['ajax_pagination'][0] ) && $view_settings['ajax_pagination'][0] == 'enable') ? ' checked="checked"' : ''; ?>
							<input type="radio" id="wpv-settings-ajax-pagination-enabled" class="js-wpv-ajax_pagination" value="enable" name="ajax_pagination[]"<?php echo $checked; ?> autocomplete="off" />
							<label for="wpv-settings-ajax-pagination-enabled"><?php _e('Pagination updates only the view (use AJAX)', 'wpv-views'); ?></label>
						</li>
					</ul>

					<ul class="wpv-pagination-paged-ajax" style="margin-bottom:0;">
						<li>
							<p>
								<label><?php _e('Transition effect:', 'wpv-views')?></label>
								<select name="ajax_pagination[style]" autocomplete="off">
									<?php if ( !isset( $view_settings['ajax_pagination']['style'] ) ) $view_settings['ajax_pagination']['style'] = 'fade'; ?>
									<option value="fade"<?php if ($view_settings['ajax_pagination']['style'] == 'fade' || $view_settings['ajax_pagination']['style'] == 'fadefast' || $view_settings['ajax_pagination']['style'] == 'fadeslow') { echo ' selected="selected"'; } ?>><?php _e('Fade',  'wpv-views'); ?></option>
									<option value="slideh"<?php if ($view_settings['ajax_pagination']['style'] == 'slideh') { echo ' selected="selected"'; } ?>><?php _e('Slide horizontally',  'wpv-views'); ?></option>
									<option value="slidev"<?php if ($view_settings['ajax_pagination']['style'] == 'slidev') { echo ' selected="selected"'; } ?>><?php _e('Slide vertically',  'wpv-views'); ?></option>
								</select>

								<label>
									<?php _e('with duration',  'wpv-views'); ?>
									<?php if ( !isset( $view_settings['ajax_pagination']['duration'] ) ) $view_settings['ajax_pagination']['duration'] = 500;
										if ($view_settings['ajax_pagination']['style'] == 'fadefast') $view_settings['ajax_pagination']['duration'] = 1;
										if ($view_settings['ajax_pagination']['style'] == 'fadeslow') $view_settings['ajax_pagination']['duration'] = 1500;
									?>
									<input type="text" class="transition-duration" name="ajax_pagination[duration]" value="<?php echo $view_settings['ajax_pagination']['duration']; ?>" size="5" autocomplete="off" />
								</label>
								<?php _e('miliseconds', 'wpv-views'); ?>
								<span class="duration-error" style="color:red;display:none;">&larr; <?php _e('Please add a numeric value', 'wpv-views'); ?></span>
							</p>
							<p>
								<button class="js-pagination-advanced button-secondary" type="button" data-closed="<?php echo esc_attr( __( 'Advanced options', 'wpv-views' ) ); ?>" data-opened="<?php echo esc_attr( __( 'Close advanced options', 'wpv-views' ) ); ?>" data-section="ajax_pagination" data-state="closed"><?php _e( 'Advanced options', 'wpv-views' ); ?></button>
							</p>
						</li>
						<li class="wpv-pagination-advanced hidden">
							<h4><?php _e( 'Cache and preload', 'wpv-views' ); ?></h4>
							<?php $checked = (isset($view_settings['pagination']['preload_images']) && $view_settings['pagination']['preload_images']) ? ' checked="checked"' : '';?>
							<label>
								<input type="checkbox" name="pagination[preload_images]" value="1"<?php echo $checked; ?> autocomplete="off" />
								<?php _e('Preload images before transition',  'wpv-views'); ?>
							</label>
						</li>
					</ul>

					<h3 class="wpv-pagination-rollover"><?php _e('Options for automatic pagination', 'wpv-views')?></h3>
					<ul class="wpv-pagination-rollover" style="margin-bottom:0;">
						<li>
							<label for="rollover[posts_per_page]"><?php _e('Number of items per page:', 'wpv-views'); ?></label>
							<select name="rollover[posts_per_page]" autocomplete="off">
								<?php if ( !isset( $view_settings['rollover']['posts_per_page'] ) ) $view_settings['rollover']['posts_per_page'] = '10'; ?>
								<?php
								for($i = 1; $i < 50; $i++) {
									$selected = $view_settings['rollover']['posts_per_page']==(string)$i ? ' selected="selected"' : '';
									echo '<option value="' . $i . '"' . $selected . '>'. $i . '</option>';
								}
								?>
							</select>
						</li>
						<li>
							<label><?php _e('Show each page for:', 'wpv-views')?></label>
							<select name="rollover[speed]" autocomplete="off">
								<?php if ( !isset( $view_settings['rollover']['speed'] ) ) $view_settings['rollover']['speed'] = '5'; ?>
								<?php
								for($i = 1; $i < 20; $i++) {
									$selected = $view_settings['rollover']['speed']==(string)$i ? ' selected="selected"' : '';
									echo '<option value="' . $i . '"' . $selected . '>'. $i . '</option>';
								}
								?>
							</select>&nbsp;<?php _e('seconds', 'wpv-views')?>
						</li>
						<li>
							<label><?php _e('Transition effect:', 'wpv-views')?></label>
							<select name="rollover[effect]" autocomplete="off">
								<?php
								if ( !isset( $view_settings['rollover']['effect'] ) ) $view_settings['rollover']['effect'] = 'fade';
								foreach($rollover_effects as $i => $title) {
									$selected = $view_settings['rollover']['effect']==(string)$i ? ' selected="selected"' : '';
									echo '<option value="' . $i . '"' . $selected . '>'. $title . '</option>';
								}
								?>
							</select>
							<label><?php _e('with duration',  'wpv-views'); ?></label>
								<?php if ( !isset( $view_settings['rollover']['duration'] ) ) $view_settings['rollover']['duration'] = 500;
								?>
								<input type="text" class="transition-duration" name="rollover[duration]" value="<?php echo $view_settings['rollover']['duration']; ?>" size="5" autocomplete="off" />
							<?php _e('miliseconds', 'wpv-views'); ?>
							<span class="duration-error" style="color:red;display:none;"><?php _e(' <- Please add a numeric value', 'wpv-views'); ?></span>
							<p>
								<button class="js-pagination-advanced button-secondary" type="button" data-closed="<?php _e( 'Advanced options', 'wpv-views' ) ?>" data-opened="<?php _e( 'Close advanced options', 'wpv-views' ) ?>" data-section="rollover" data-state="closed"><?php _e( 'Advanced options', 'wpv-views' ) ?></button>
							</p>
						</li>
						<li class="wpv-pagination-advanced hidden">
							<h4><?php _e( 'Cache and preload', 'wpv-views' ); ?></h4>
							<?php $checked = (isset($view_settings['rollover']['preload_images']) && $view_settings['rollover']['preload_images']) ? ' checked="checked"' : '';?>
							<label>
								<input type="checkbox" name="rollover[preload_images]" value="1"<?php echo $checked; ?> autocomplete="off" />
								<?php _e('Preload images before transition',  'wpv-views'); ?>
							</label>
						</li>
					</ul>

					<ul class="wpv-pagination-paged wpv-pagination-rollover wpv-pagination-shared wpv-pagination-advanced hidden" style="padding-bottom:10px;">
						<li>
							<?php $checked = (isset($view_settings['pagination']['cache_pages']) && $view_settings['pagination']['cache_pages']) ? ' checked="checked"' : '';?>
							<p>
								<label>
									<input type="checkbox" name="pagination[cache_pages]" value="1"<?php echo $checked; ?> autocomplete="off" />
									<?php _e('Cache pages',  'wpv-views'); ?>
								</label>
							</p>
						</li>
						<li>
							<?php $checked = (isset($view_settings['pagination']['preload_pages']) && $view_settings['pagination']['preload_pages']) ? ' checked="checked"' : '';?>
							<p>
								<label>
									<input type="checkbox" name="pagination[preload_pages]" value="1"<?php echo $checked; ?> autocomplete="off" />
									<?php _e('Pre-load the next and previous pages - avoids loading delays when users move between pages',  'wpv-views'); ?>
								</label>
							</p>

							<p>
								<label><?php _e('Pages to pre-load: ',  'wpv-views'); ?></label>
								<select name="pagination[pre_reach]" autocomplete="off">
								<?php if ( !isset( $view_settings['pagination']['pre_reach'] ) ) $view_settings['pagination']['pre_reach'] = 1;
									for($i = 1; $i < 20; $i++) {
										$selected = $view_settings['pagination']['pre_reach']== $i ? ' selected="selected"' : '';
										echo '<option value="' . $i . '"' . $selected . '>'. $i . '</option>';
									}
									?>
								</select>
							</p>
						</li>
						<li>
							<h4><?php _e('Spinners',  'wpv-views'); ?></h4>
							<?php $checked = ( isset($view_settings['pagination']['spinner']) && $view_settings['pagination']['spinner'] == 'default' ) ? ' checked="checked"' : '';?>
							<ul>
								<li>
									<label>
										<input type="radio" class="js-wpv-pagination-spinner" name="pagination[spinner]" value="default"<?php echo $checked; ?> autocomplete="off" />
										<?php _e('Spinner graphics from Views', 'wpv-views'); ?>
									</label>
									<ul id="wpv-spinner-default" class="wpv-spinner-selection wpv-mightlong-list wpv-setting-extra js-wpv-pagination-spinner-default">
									<?php
									if ( isset( $view_settings['pagination']['spinner_image'] ) ) {
										$spinner_image = $view_settings['pagination']['spinner_image'];
									} else {
										$spinner_image = '';
									}
									$available_spinners = array();
									$available_spinners = apply_filters( 'wpv_admin_available_spinners', $available_spinners );
									foreach ( $available_spinners as $av_spinner ) {
									?>
										<li>
											<label>
												<input type="radio" name="pagination[spinner_image]" value="<?php echo $av_spinner['url']; ?>"<?php if ( $spinner_image == $av_spinner['url'] ) { echo ' checked="checked"'; } ?> autocomplete="off" />
												<img style="background-color: #FFFFFF;" src="<?php echo $av_spinner['url']; ?>" title="<?php echo esc_attr( $av_spinner['title'] ); ?>" />
											</label>
										</li>
									<?php } ?>
									</ul>
								</li>
								<?php $checked = ( isset($view_settings['pagination']['spinner']) && $view_settings['pagination']['spinner'] == 'uploaded' ) ? ' checked="checked"' : '';?>
								<li>
									<label>
										<input type="radio" class="js-wpv-pagination-spinner" name="pagination[spinner]" value="uploaded"<?php echo $checked; ?> autocomplete="off" />
										<?php _e('My custom spinner graphics', 'wpv-views'); ?>
									</label>
									<p id="wpv-spinner-uploaded" class="wpv-spinner-selection js-wpv-pagination-spinner-uploaded">
										<input id="wpv-pagination-spinner-image" class="js-wpv-pagination-spinner-image" type="text" name="pagination[spinner_image_uploaded]" value="<?php echo isset( $view_settings['pagination']['spinner_image_uploaded'] ) ? $view_settings['pagination']['spinner_image_uploaded'] : ''; ?>" autocomplete="off" />
										<button class="button-secondary js-code-editor-toolbar-button js-wpv-media-manager" data-content="wpv-pagination-spinner-image" data-id="<?php echo $view_id; ?>"><?php _e('Upload Image', 'wpv-views'); ?></button>
										<?php 
										$spinner_image_uploaded = '';
										if ( isset( $view_settings['pagination']['spinner_image_uploaded'] ) && ! empty( $view_settings['pagination']['spinner_image_uploaded'] ) ) {
											$spinner_image_uploaded = $view_settings['pagination']['spinner_image_uploaded'];
										}
										?>
										<img id="wpv-pagination-spinner-image-preview" class="js-wpv-pagination-spinner-image-preview" src="<?php echo $spinner_image_uploaded; ?>" height="16"<?php if ( empty( $spinner_image_uploaded ) ) { echo ' style="display:none;"'; } ?> />
									</p>
								</li>
								<li>
								<?php $checked = ( isset($view_settings['pagination']['spinner']) &&  $view_settings['pagination']['spinner'] == 'no' ) ? ' checked="checked"' : '';?>
									<label>
										<input type="radio" class="js-wpv-pagination-spinner" name="pagination[spinner]" value="no"<?php echo $checked; ?> autocomplete="off" />
										<?php _e('No spinner graphics', 'wpv-views'); ?>
									</label>
								</li>
							</ul>
						</li>
						<li>
							<h4><?php _e('Callback function', 'wpv-views'); ?></h4>
							<p><?php _e('Javascript function to execute after the pagination transition has been completed:', 'wpv-views'); ?></p>
							<ul><?php // TODO add a callback to execute before the pagination starts ?>
								<li>
									<input id="wpv-pagination-callback-next" class="js-wpv-pagination-callback-next" type="text" name="pagination[callback_next]" value="<?php echo isset( $view_settings['pagination']['callback_next'] ) ? $view_settings['pagination']['callback_next'] : ''; ?>" autocomplete="off" />
								</li>
							</ul>
						</li>
					</ul>
				</div> <!-- .ggwpv-pagination-options-box -->

			</form>
			<div class="js-wpv-toolset-messages"></div>
		</div>
		<span class="update-button-wrap auto-update js-wpv-update-action-wrap">
			<span class="js-wpv-message-container"></span>
			<input type="hidden" data-success="<?php echo htmlentities( __('Updated', 'wpv-views'), ENT_QUOTES ); ?>" data-unsaved="<?php echo htmlentities( __('Not saved', 'wpv-views'), ENT_QUOTES ); ?>" data-nonce="<?php echo wp_create_nonce( 'wpv_view_pagination_nonce' ); ?>" class="js-wpv-pagination-update" />
		</span>

	</div>
	
<?php }

/**
* wpv_pagination_summary_filter
*
* Returns the pagination part when building the summary for a View
*
* @param $summary
* @param $post_id
* @param $view_settings
*
* @returns (string) $summary
*
* @uses wpv_get_pagination_summary
*
* @since unknown
*/

add_filter( 'wpv-view-get-content-summary', 'wpv_pagination_summary_filter', 6, 3 );

function wpv_pagination_summary_filter($summary, $post_id, $view_settings) {
	$summary .= wpv_get_pagination_summary( $view_settings );
    return $summary;
}
