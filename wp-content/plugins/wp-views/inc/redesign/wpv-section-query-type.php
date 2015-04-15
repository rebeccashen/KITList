<?php

/**
* wpv_show_hide_content_selector
*
* We can enable this to hide the Content Selection section
*
* @param $sections (array) sections on the editor screen
*
* @return $sections
*
* @since unknown
*/

// add_filter('wpv_sections_query_show_hide', 'wpv_show_hide_content_selector', 1,1);

function wpv_show_hide_content_selector($sections) {
	$sections['content-selection'] = array(
		'name'		=> __('Content Selection', 'wpv-views'),
		);
	return $sections;
}

/**
* add_view_content_selection
*
* Creates the Content Selection section in the edit screen
*
* @param $view_settings
* @param $view_id
*
* @uses $views_edit_help (global)
*
* @since unknown
*/

add_action('view-editor-section-query', 'add_view_content_selection_section', 10, 2);

function add_view_content_selection_section($view_settings, $view_id) {
    global $views_edit_help;
	$hide = '';
	if (isset($view_settings['sections-show-hide']) && isset($view_settings['sections-show-hide']['content-selection']) && 'off' == $view_settings['sections-show-hide']['content-selection']) {
		$hide = ' hidden';
	}?>
	<div class="wpv-setting-container wpv-settings-content-selection js-wpv-no-lock js-wpv-settings-content-selection<?php echo $hide; ?>">
		<div class="wpv-settings-header">
			<h3>
				<?php _e('Content Selection', 'wpv-views' ) ?>
				<i class="icon-question-sign js-display-tooltip" data-header="<?php echo $views_edit_help['content_section']['title']; ?>" data-content="<?php echo $views_edit_help['content_section']['content']; ?>"></i>
			</h3>
		</div>
		<div class="wpv-setting js-wpv-setting">
			<ul>
				<?php if (!isset( $view_settings['query_type'] ) ) $view_settings['query_type'][0] = 'posts'; ?>
				<li style="margin-bottom:20px">
					<?php _e('This View will display:', 'wpv-views'); ?>
					<?php $checked = $view_settings['query_type'][0]=='posts' ? ' checked="checked"' : ''; ?>
					<input type="radio" style="margin-left:15px" name="_wpv_settings[query_type][]" id="wpv-settings-cs-query-type-posts" class="js-wpv-query-type" value="posts"<?php echo $checked; ?> autocomplete="off" /><label for="wpv-settings-cs-query-type-posts"><?php _e('Post types','wpv-views') ?></label>
					<?php $checked = $view_settings['query_type'][0]=='taxonomy' ? ' checked="checked"' : ''; ?>
					<input type="radio" style="margin-left:15px" name="_wpv_settings[query_type][]" id="wpv-settings-cs-query-type-taxonomy" class="js-wpv-query-type" value="taxonomy"<?php echo $checked; ?> autocomplete="off" /><label for="wpv-settings-cs-query-type-taxonomy"><?php _e('Taxonomy','wpv-views') ?></label>
					<?php $checked = $view_settings['query_type'][0]=='users' ? ' checked="checked"' : ''; ?>
					<input type="radio" style="margin-left:15px" name="_wpv_settings[query_type][]" id="wpv-settings-cs-query-type-users" class="js-wpv-query-type" value="users"<?php echo $checked; ?> autocomplete="off" /><label for="wpv-settings-cs-query-type-users"><?php _e('Users','wpv-views') ?></label>
				</li>
				<li>
					<ul class="js-wpv-settings-query-type-posts wpv-settings-query-type-posts wpv-advanced-setting wpv-mightlong-list<?php echo $view_settings['query_type'][0]!='posts' ? ' hidden' : ''; ?>">
						<?php
						// Store children post types in an array
						$relationships = get_option( 'wpcf_post_relationship', array() );
						$types_children = array();
						if ( is_array( $relationships ) ) {
							foreach ( $relationships as $has => $belongs ) {
								$types_children = array_merge( $types_children, array_keys( $belongs ) );
							}
						}
						$post_types = get_post_types( array( 'public' => true ), 'objects');
						if ( ! isset( $view_settings['post_type'] ) ) $view_settings['post_type'] = array();
						foreach ( $view_settings['post_type'] as $type ) {
							if ( ! isset( $post_types[$type] ) ) {
								unset( $view_settings['post_type'][$type] );
							}
						}
						foreach ( $post_types as $p ) {
						?>
							<li><!-- review the use of $p->name here -->
								<?php
								$checked = in_array( $p->name, $view_settings['post_type'] ) ? ' checked="checked"' : '';
								$is_types_child = in_array( $p->name, $types_children ) ? 'yes' : 'no';
								$is_hierarchical = $p->hierarchical ? 'yes' : 'no';
								?>
								<input type="checkbox" id="wpv-settings-post-type-<?php echo $p->name ?>" name="_wpv_settings[post_type][]" data-typeschild="<?php echo $is_types_child; ?>" data-hierarchical="<?php echo $is_hierarchical; ?>" class="js-wpv-query-post-type" value="<?php echo $p->name ?>"<?php echo $checked; ?> autocomplete="off" />
								<label for="wpv-settings-post-type-<?php echo $p->name ?>"><?php echo $p->labels->name ?></label>
							</li>
						<?php 
						}
						?>
					</ul>
					<ul class="wpv-settings-query-type-taxonomy wpv-advanced-setting wpv-mightlong-list<?php echo $view_settings['query_type'][0]!='taxonomy' ? ' hidden' : ''; ?>">
						<?php $taxonomies = get_taxonomies('', 'objects');
						$exclude_tax_slugs = array();
						$exclude_tax_slugs = apply_filters( 'wpv_admin_exclude_tax_slugs', $exclude_tax_slugs );
						if ( !isset( $view_settings['taxonomy_type'] ) ) $view_settings['taxonomy_type']= array();
						foreach($view_settings['taxonomy_type'] as $type) {
							if (!isset($taxonomies[$type])) {
								unset($view_settings['taxonomy_type'][$type]);
								}
						}
						?>
						<?php foreach( $taxonomies as $tax_slug => $tax ) { ?>
							<?php
							if ( in_array($tax_slug, $exclude_tax_slugs ) ) {
								continue; // Take out taxonomies that are in our compatibility black list
							}
							if ( !$tax->show_ui ) {
								continue; // Only show taxonomies with show_ui set to TRUE
							}
							?>
							<?php 
							if (sizeof($view_settings['taxonomy_type']) == 0) { // we need to check at least the first available taxonomy if no one is set
								$view_settings['taxonomy_type'][] = $tax->name;
							}
							$checked = @in_array($tax->name, $view_settings['taxonomy_type']) ? ' checked="checked"' : '';
							$is_tax_hierarchical = $tax->hierarchical ? 'yes' : 'no';
							?>
							<li>
								<input type="radio" id="wpv-settings-post-taxonomy-<?php echo $tax->name ?>" name="_wpv_settings[taxonomy_type][]" data-hierarchical="<?php echo $is_tax_hierarchical; ?>" class="js-wpv-query-taxonomy-type" value="<?php echo $tax->name ?>"<?php echo $checked; ?> autocomplete="off" />
								<label for="wpv-settings-post-taxonomy-<?php echo $tax->name ?>"><?php echo $tax->labels->name ?></label>
							</li>
						<?php } ?>
					</ul>
					<ul class="wpv-settings-query-type-users wpv-advanced-setting wpv-mightlong-list<?php echo $view_settings['query_type'][0]!='users' ? ' hidden' : ''; ?>">
						<?php global $wp_roles;
						if ( !isset( $view_settings['roles_type'] ) ) $view_settings['roles_type']= array('administrator');
						foreach( $wp_roles->role_names as $role => $name ) :?>
							<?php 
							$checked = @in_array($role, $view_settings['roles_type']) ? ' checked="checked"' : ''; ?>
						<li>
							<input type="radio" id="wpv-settings-post-users-<?php echo $role; ?>" name="_wpv_settings[roles_type][]" class="js-wpv-query-users-type" value="<?php echo $role; ?>"<?php echo $checked; ?> autocomplete="off" />
							<label for="wpv-settings-post-users-<?php echo $role; ?>"><?php echo $name; ?></label>
						</li>
						<?php endforeach; ?>
						<li>
							<?php $checked = @in_array( 'any', $view_settings['roles_type'] ) ? ' checked="checked"' : ''; ?>
							<input type="radio" id="wpv-settings-post-users-any-role" name="_wpv_settings[roles_type][]" class="js-wpv-query-users-type" value="any"<?php echo $checked; ?> autocomplete="off" />
							<label for="wpv-settings-post-users-any-role"><?php _e( 'Any role', 'wpv-views' ); ?></label>
						</li>
					</ul>
				</li>
			</ul>
			<?php
			$multi_post_relations = wpv_recursive_post_hierarchy( $view_settings['post_type'] );
			$flatten_post_relations = wpv_recursive_flatten_post_relationships( $multi_post_relations );
			$relations_tree = wpv_get_all_post_relationship_options( $flatten_post_relations );
			$flatten_relations_tree = implode( ',', $relations_tree );
			?>
			<input type="hidden" class="js-flatten-types-relation-tree" value="<?php echo $flatten_relations_tree; ?>" data-nonce="<?php echo wp_create_nonce( 'wpv_view_filter_dependant_parametric_search' ); ?>" autocomplete="off" />
		</div>
		<span class="update-action-wrap auto-update js-wpv-content-section-action-wrap js-wpv-update-action-wrap">
			<span class="js-wpv-message-container"></span>
			<input type="hidden" data-success="<?php echo htmlentities( __('Updated', 'wpv-views'), ENT_QUOTES ); ?>" data-unsaved="<?php echo htmlentities( __('Not saved', 'wpv-views'), ENT_QUOTES ); ?>" data-nonce="<?php echo wp_create_nonce( 'wpv_view_query_type_nonce' ); ?>" class="js-wpv-query-type-update" />
		</span>
	</div>
	<div class="toolset-alert toolset-alert-lock js-wpv-content-selection-mandatory-warning hidden">
		<p>
			<?php _e( 'You need to select what content to load with this View before you can continue designing the output.', 'wpv-views' ); ?>
		</p>
	</div>
<?php }

/**
* wpv_query_type_summary_filter
*
* Returns the query type part when building the summary for a View
*
* @param $summary
* @param $post_id
* @param $view_settings
*
* @returns (string) $summary
*
* @uses wpv_get_query_type_summary
*
* @since 1.6.0
*/

add_filter('wpv-view-get-content-summary', 'wpv_query_type_summary_filter', 5, 3);

function wpv_query_type_summary_filter($summary, $post_id, $view_settings) {
	$summary .= wpv_get_query_type_summary( $view_settings );
	return $summary;
}
