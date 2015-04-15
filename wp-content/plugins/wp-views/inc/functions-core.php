<?php

/**
* Update messages for regular edit screens
*
* @param $messages
* @return $messages
*/

add_filter('post_updated_messages', 'wpv_post_updated_messages_filter', 9999);

function wpv_post_updated_messages_filter( $messages ) {
	global $post;

	$post_type = get_post_type();
	// DEPRECATED
	// We have now our own edit pages, so this is not fired anymore
	// Commented out in 1.7
	/*
	if ( $post_type == 'view' ) {
		$messages['view'] = array(
			0 => '', // Unused. Messages start at index 1.
			1 => __('View updated.', 'wpv-views'),
			2 => __('Custom field updated.'),
			3 => __('Custom field deleted.'),
			4 => __('View updated.', 'wpv-views'),
			5 => isset($_GET['revision']) ? sprintf( __('View restored to revision from %s', 'wpv-views'), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
			6 => __('View published.', 'wpv-views'),
			7 => __('View saved.', 'wpv-views'),
			8 => __('View submitted.', 'wpv-views'),
			9 => sprintf( __('View scheduled for: <strong>%1$s</strong>.', 'wpv-views'),
				// translators: Publish box date format, see http://php.net/date
				date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ) ),
			10 => __('View draft updated', 'wpv-views'),
			);
	}
	*/
	if ( $post_type == 'view-template' ) {
		$messages['view-template'] = array(
			0 => '', // Unused. Messages start at index 1.
			1 => __('Content template updated.', 'wpv-views'),
			2 => __('Custom field updated.'),
			3 => __('Custom field deleted.'),
			4 => __('Content template updated.', 'wpv-views'),
			/* translators: %s: date and time of the revision */
			5 => isset($_GET['revision']) ? sprintf( __('Content template restored to revision from %s', 'wpv-views'), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
			6 => __('Content template published.', 'wpv-views'),
			7 => __('Content template saved.', 'wpv-views'),
			8 => __('Content template submitted.', 'wpv-views'),
			9 => sprintf( __('Content template scheduled for: <strong>%1$s</strong>.', 'wpv-views'),
				// translators: Publish box date format, see http://php.net/date
				date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ) ),
			10 => __('Content template draft updated', 'wpv-views'),
			);
	}
	return $messages;
}

/**
*
* Function wpv_redirect_admin_listings
*
* Prevents users from accessing the natural listing pages that WordPress creates for Views and Content Templates
* and redirects them to the new listing pages
*
*/

add_action('admin_init', 'wpv_redirect_admin_listings');

function wpv_redirect_admin_listings(){
	global $pagenow;
	/* Check current admin page. */
	if ( $pagenow == 'edit.php' && isset( $_GET['post_type'] ) && $_GET['post_type'] == 'view' ) {
		wp_redirect(admin_url('/admin.php?page=views', 'http'), 301);
		exit;
	} elseif ( $pagenow == 'edit.php' && isset( $_GET['post_type'] ) && $_GET['post_type'] == 'view-template' ) {
		wp_redirect(admin_url('/admin.php?page=view-templates', 'http'), 301);
		exit;
	}
}

function wpv_render_checkboxes( $values, $selected, $name ) { // TODO only used in old Status Filter, safe to remove
	$checkboxes = '<ul>';
	foreach ( $values as $value ) {

		if ( in_array( $value, $selected ) ) {
			$checked = ' checked="checked"';
		} else {
			$checked = '';
		}
		$checkboxes .= '<li><label><input type="checkbox" name="_wpv_settings[' . $name . '][]" value="' . $value . '"' . $checked . ' />&nbsp;' . $value . '</label></li>';

	}
	$checkboxes .= '</ul>';

	return $checkboxes;
}
/*
* DEPECATED
* Commented out in 1.7

function wpv_render_filter_td( $row, $id, $name, $summary_function, $selected, $data ) { // TODO only used in old Status Filter, safe to remove

	$td = '<td><img src="' . WPV_URL . '/res/img/delete.png" onclick="on_delete_wpv_filter(\'' . $row . '\')" style="cursor: pointer" />';
	$td .= '<td class="wpv_td_filter">';
	$td .= "<div id=\"wpv-filter-" . $id . "-show\">\n";
	$td .= call_user_func($summary_function, $selected);
	$td .= "</div>\n";
	$td .= "<div id=\"wpv-filter-" . $id . "-edit\" style='display:none'>\n";

	$td .= '<fieldset>';
	$td .= '<legend><strong>' . $name . ':</strong></legend>';
	$td .= '<div>' . $data . '</div>';
	$td .= '</fieldset>';
	ob_start();
	?>
		<input class="button-primary" type="button" value="<?php echo __('OK', 'wpv-views'); ?>" name="<?php echo __('OK', 'wpv-views'); ?>" onclick="wpv_show_filter_<?php echo $id; ?>_edit_ok()"/>
		<input class="button-secondary" type="button" value="<?php echo __('Cancel', 'wpv-views'); ?>" name="<?php echo __('Cancel', 'wpv-views'); ?>" onclick="wpv_show_filter_<?php echo $id; ?>_edit_cancel()"/>
	<?php
	$td .= ob_get_clean();
	$td .= '</div></td>';

	return $td;
}
*/

/**
 * Generate default View settings or layout settings.
 *
 * This is now merely a wrapper around wpv_view_default_settings() and wpv_view_default_layout_settings().
 * Feel free to use those functions directly instead.
 *
 * @param $settings Field: 'view_settings' or 'view_layout_settings'.
 * @param $purpose Purpose of the view: 'all', 'pagination', 'slide', 'parametric' or 'full'.
 *
 * @return array Array with desired values or an empty array if invalid parameters are provided.
 *
 * @since unknown
 */
function wpv_view_defaults( $settings = 'view_settings', $purpose = 'full' ) {

	switch( $settings ) {

		case 'view_settings':
			return wpv_view_default_settings( $purpose );

		case 'view_layout_settings':
			return wpv_view_default_layout_settings( $purpose );

		default:
			return array();
	}

}


/**
 * Generate default View settings.
 *
 * Depending on a View purpose, generate default settings for a View.
 *
 * @param $purpose Purpose of the view: 'all', 'pagination', 'slide', 'parametric' or 'full'. For invalid values
 *     'full' is assumed.
 *
 * @return array Array with desired values.
 *
 * @since 1.7
 */
function wpv_view_default_settings( $purpose = 'full' ) {

	/* Set the initial values for the View settings.
	 * Note: taxonomy_type is set in wpv-section-query-type.php to use the first available taxonomy. */
	$defaults = array(
			'view-query-mode' => 'normal',
			'view_description' => '',
			'view_purpose' => 'full',
			'query_type' => array( 'posts' ),
			'taxonomy_type' => array( 'category' ),
			'roles_type' => array( 'administrator' ),
			'post_type_dont_include_current_page' => true,
			'taxonomy_hide_empty' => true,
			'taxonomy_include_non_empty_decendants'	=> true,
			'taxonomy_pad_counts' => true, // check this setting application
			'orderby' => 'post_date',
			'order'	=> 'DESC',
			'taxonomy_orderby' => 'name',
			'taxonomy_order' => 'DESC',
			'users_orderby' => 'user_login',
			'users_order' => 'ASC',
			'limit'	=> -1,
			'offset' => 0,
			'taxonomy_limit' => -1,
			'taxonomy_offset' => 0,
			'users_limit' => -1,
			'users_offset' => 0,
			'posts_per_page' => 10,
			// TODO this needs carefull review
			'pagination' => array(
					'disable',
					'mode' => 'none',
					'preload_images' => true,
					'cache_pages' => true,
					'preload_pages'	=> true,
					'pre_reach'	=> 1,
					'page_selector_control_type' => 'drop_down',
					'spinner' => 'default',
					'spinner_image'	=> WPV_URL . '/res/img/ajax-loader.gif',
					'spinner_image_uploaded' => '',
					'callback_next'	=> '' ),
			'ajax_pagination' => array(
					'disable',
					'style'	=> 'fade',
					'duration' => 500 ),
			'rollover' => array(
					'preload_images' => true,
					'posts_per_page' => 1,
					'speed'	=> 5,
					'effect' => 'fade',
					'duration' => 500 ),
			'filter_meta_html_state' => array(
					'html' => 'on',
					'css' => 'off',
					'js' => 'off',
					'img' => 'off' ),
			'filter_meta_html' => "[wpv-filter-start hide=\"false\"]\n[wpv-filter-controls][/wpv-filter-controls]\n[wpv-filter-end]",
			'filter_meta_html_css' => '',
			'filter_meta_html_js' => '',
			'layout_meta_html_state' => array(
					'html' => 'on',
					'css' => 'off',
					'js' => 'off',
					'img' => 'off' ),
			'layout_meta_html_css' => '',
			'layout_meta_html_js' => '' );

	// purpose-specific modifications
	$defaults['view_purpose'] = $purpose;

	switch( $purpose ) {

		case 'all':
			$defaults['sections-show-hide'] = array(
				'pagination' => 'off',
				'filter-extra-parametric' => 'off',
				'filter-extra' => 'off'	);
			break;

		case 'pagination':
			$defaults['pagination'][0] = 'enable'; // disable --> enable
			$defaults['pagination']['mode'] = 'paged';
			$defaults['sections-show-hide'] = array( 'limit-offset' => 'off' );
			break;

		case 'slider':
			$defaults['pagination'][0] = 'enable'; // disable --> enable
			$defaults['pagination']['mode'] = 'rollover';
			$defaults['sections-show-hide'] = array( 'limit-offset' => 'off' );
			break;

		case 'parametric':
			$defaults['sections-show-hide'] = array(
					'query-options'	=> 'off',
					'limit-offset' => 'off',
					'pagination' => 'off',
					'content-filter' => 'off' );
			break;

		case 'full':
		default:
			$defaults['sections-show-hide'] = array( );
			// This has to stay here, because we're also catching invalid $purpose values.
			$defaults['view_purpose'] = 'full';
			break;
	}
	return $defaults;
}


/**
 * Generate default View layout settings.
 *
 * Depending on a View purpose, generate default settings for a View.
 *
 * @param $purpose Purpose of the view: 'all', 'pagination', 'slide', 'parametric' or 'full'. For invalid values
 *     'full' is assumed.
 *
 * @return array Array with desired values.
 *
 * @since 1.7
 */
function wpv_view_default_layout_settings( $purpose ) {

	// almost all of this settings are only needed to create the layout on the fly, so they are not needed here
	$defaults = array(
			'additional_js' => '',
			'layout_meta_html' =>
					"[wpv-layout-start]\n"
					. "	[wpv-items-found]\n"
					. "	<!-- wpv-loop-start -->\n"
					. "		<wpv-loop>\n"
					. "		</wpv-loop>\n"
					. "	<!-- wpv-loop-end -->\n"
					. "	[/wpv-items-found]\n"
					. "	[wpv-no-items-found]\n"
					. "		[wpml-string context=\"wpv-views\"]<strong>No items found</strong>[/wpml-string]\n"
					. "	[/wpv-no-items-found]\n"
					. "[wpv-layout-end]\n" );

	// Purpose-specific modifications
	switch( $purpose ) {

		case 'all':
		case 'pagination':
			// nothing to do here... yet
			break;

		case 'slider':
			// Generate full layout settings
			$defaults = wpv_generate_views_layout_settings(
					'unformatted',
					array(),
					array( 'render_whole_html' => true ) );
			break;

		case 'parametric':
		case 'full':
		default:
			// nothing to do here... yet
			break;
	}
	return $defaults;
}


/**
* Set default WordPress Archives settings and layout settings
*
* @param $settings field: view_settings or view_layout_settings
* @return array() with desired values
*/

function wpv_wordpress_archives_defaults( $settings = 'view_settings' ) {
	$defaults = array(
		'view_settings' => array(
			'view-query-mode'			=> 'archive',
			'sections-show-hide'			=> array(
									'content'		=> 'off',
								)
		),
		'view_layout_settings' => array( // almost all of this settings are only needed to create the layout on the fly, so they are not needed here
			'additional_js'				=> '',
			'layout_meta_html'			=> "[wpv-layout-start]
	[wpv-items-found]
	<!-- wpv-loop-start -->
		<wpv-loop>
		</wpv-loop>
	<!-- wpv-loop-end -->
	[/wpv-items-found]
	[wpv-no-items-found]
		[wpml-string context=\"wpv-views\"]<strong>No posts found</strong>[/wpml-string]
	[/wpv-no-items-found]
[wpv-layout-end]",
		),
	);
	return $defaults[$settings];
}


/**
 * Display pagination in admin listing pages.
 *
 * @param string $context the admin page where it will be rendered: 'views', 'view-templates' or 'view-archives'.
 * @param int $wpv_found_items
 * @param int $wpv_items_per_page
 * @param array $mod_url
*/
function wpv_admin_listing_pagination( $context = 'views', $wpv_found_items, $wpv_items_per_page = WPV_ITEMS_PER_PAGE, $mod_url = array() ) {
	$page = ( isset( $_GET["paged"] ) ) ? (int) $_GET["paged"] : 1;
	$pages_count = ceil( (int) $wpv_found_items / (int) $wpv_items_per_page );

	if ( $pages_count > 1 ) {

		$items_start = ( ( ( $page - 1 ) * (int) $wpv_items_per_page ) + 1 );
		$items_end = ( ( ( $page - 1 ) * (int) $wpv_items_per_page ) + (int) $wpv_items_per_page );

		if ( $page == $pages_count ) {
			$items_end = $wpv_found_items;
		}

		$mod_url_defaults = array(
				'orderby' => '',
				'order' => '',
				'search' => '',
				'items_per_page' => '',
				'status' => '',
				's' => '' );
		$mod_url = wp_parse_args( $mod_url, $mod_url_defaults );

		?>
		<div class="wpv-listing-pagination tablenav">
			<div class="tablenav-pages">
				<span class="displaying-num">
					<?php _e( 'Displaying ', 'wpv-views' ); echo $items_start; ?> - <?php echo $items_end; _e(' of ', 'wpv-views'); echo $wpv_found_items; ?>
				</span>

				<?php

					if ( $page > 1 ) {
						printf(
								'<a href="%s" class="wpv-filter-navigation-link">&laquo; %s</a>',
								wpv_maybe_add_query_arg(
										array(
												'page' => $context,
												'orderby' => $mod_url['orderby'],
												'order' => $mod_url['order'],
												'search' => $mod_url['search'],
												'items_per_page' => $mod_url['items_per_page'],
												'status' => $mod_url['status'],
												'paged' => $page - 1,
												's' => $mod_url['s'] ),
										admin_url( 'admin.php' ) ),
								__( 'Previous page','wpv-views' ) );
					}

					for ( $i = 1; $i <= $pages_count; $i++ ) {
						$active = 'wpv-filter-navigation-link-inactive';
						if ( $page == $i ) {
							$active = 'js-active active current';
						}

						// If this is a last page, we'll add an argument indicating that.
						$is_last_page = ( $i == $pages_count );

						printf(
								'<a href="%s" class="%s">%s</a>',
								wpv_maybe_add_query_arg(
										array(
												'page' => $context,
												'orderby' => $mod_url['orderby'],
												'order' => $mod_url['order'],
												'search' => $mod_url['search'],
												'items_per_page' => $mod_url['items_per_page'],
												'status' => $mod_url['status'],
												'paged' => $i,
												'last_page' => $is_last_page ? '1' : '',
												's' => $mod_url['s'] ),
										admin_url( 'admin.php' ) ),
								$active,
								$i );
					}

					if ( $page < $pages_count ) {

						$is_last_page = ( ( $page + 1 )  == $pages_count );

						printf(
								'<a href="%s" class="wpv-filter-navigation-link">%s &raquo;</a>',
								wpv_maybe_add_query_arg(
										array(
												'page' => $context,
												'orderby' => $mod_url['orderby'],
												'order' => $mod_url['order'],
												'search' => $mod_url['search'],
												'items_per_page' => $mod_url['items_per_page'],
												'status' => $mod_url['status'],
												'paged' => $page + 1,
												'last_page' => $is_last_page ? '1' : '',
												's' => $mod_url['s'] ),
										admin_url( 'admin.php' ) ),
								__( 'Next page','wpv-views' ) );
					}

				?>

				<?php _e( 'Items per page', 'wpv-views' ); ?>
				<select class="js-items-per-page">
					<option value="10" <?php selected( $wpv_items_per_page == '10' ); ?> >10</option>
					<option value="20" <?php selected( $wpv_items_per_page == '20' ); ?> >20</option>
					<option value="50" <?php selected( $wpv_items_per_page == '50' ); ?> >50</option>
				</select>
				<a href="#" class="js-wpv-display-all-items"><?php _e( 'Display all items', 'wpv-views' ); ?></a>

			</div><!-- .tablenav-pages -->
		</div><!-- .wpv-listing-pagination -->
	<?php } else if ( ( WPV_ITEMS_PER_PAGE != $wpv_items_per_page ) && ( $wpv_found_items > WPV_ITEMS_PER_PAGE ) ) { ?>
		<div class="wpv-listing-pagination tablenav">
			<div class="tablenav-pages">
				<a href="#" class="js-wpv-display-default-items"><?php _e('Display 20 items per page', 'wpv-views'); ?></a>
			</div><!-- .tablenav-pages -->
		</div><!-- .wpv-listing-pagination -->
	<?php }
}

// NOT needed for Views anymore DEPRECATED NOTE Layouts might find this usefull
// DEPRECATED I would delete this, check other plugins usage
function _wpv_get_all_views($view_query_mode) {
	global $wpdb, $WP_Views;
	// TODO clean this query, do not set prefix explicitely
	$q = ("SELECT ID, post_title FROM {$wpdb->posts} WHERE post_status = 'publish' AND post_type = 'view'");
	$all_views = $wpdb->get_results( $q );
	foreach( $all_views as $key => $view ) {
		$settings = $WP_Views->get_view_settings( $view->ID );
		if( $settings['view-query-mode'] != $view_query_mode ) {
			unset( $all_views[$key] );
		}
	}
	return $all_views;
}

/**
* DEPRECATED
*
* Commented out in 1.7
function _wpv_field_views_by_search($all_views, $search_term) {

//	if ( !empty( $search_term ) ) {
//		foreach($all_views as $key => $view) {
//			// check the search
//			$description = get_post_meta($view->ID, '_wpv_description', true);
//			if (strpos($description, $search_term) === FALSE && strpos($view->post_title, $search_term) === FALSE) {
//				unset($all_views[$key]);
//			}
//		}
//	}

	foreach($all_views as $key => $view) {
		$all_views[$key] = $view->ID;
	}

	$all_views = implode(',', $all_views);

	return $all_views;
}
*/

/**
* Check the existence of a kind of View NOT needed for Views anymore DEPRECATED
*
* @param $query_mode kind of View object: normal or archive
* @return boolean
*/
// DEPRECATED I would delete this, check other plugins usage
function wpv_check_items_exists( $query_mode ) {
	$all_views = _wpv_get_all_views($query_mode);

    return count( $all_views ) != 0;
}

/**
* Cleans the WordPress Media popup to be used in Views and WordPress Archives
*
* @param $strings elements to be included
* @return $strings without the unwanted sections
*/

add_filter( 'media_view_strings', 'custom_media_uploader' );

function custom_media_uploader( $strings ) {
	if ( isset( $_GET['page'] ) && ( 'view-archives-editor' == $_GET['page'] || 'views-editor' == $_GET['page'] ) ) {
		unset( $strings['createGalleryTitle'] ); //Create Gallery
	}
	return $strings;
}

/**
* Add View button and dialog
*
* @param $editor_id ID for the relevant textarea, to be set as active editor
* @param $inline TODO document this
*
* @return $strings without the unwanted sections
*/

function wpv_add_v_icon_to_codemirror( $editor_id, $inline = false ) {

    global $WP_Views;
    $view = '';
    if ( isset($_GET['view_id']) ){
        $view = $_GET['view_id'];
    }
    $is_taxonomy = false;
	$is_users = false;
    $post_hidden = '';
    $tax_hidden = ' hidden';
	$users_hidden = ' hidden';

    $meta = get_post_meta( $view, '_wpv_settings', true);
    if ( isset($meta['query_type']) && $meta['query_type'][0] == 'taxonomy'){
           $is_taxonomy = true;
           $post_hidden = ' hidden';
           $tax_hidden = '';
		   $users_hidden = ' hidden';
    }
	if ( isset($meta['query_type']) && $meta['query_type'][0] == 'users'){
           $is_users = true;
           $post_hidden = ' hidden';
           $tax_hidden = ' hidden';
		   $users_hidden = '';
    }


    $WP_Views->editor_addon = new Editor_addon('wpv-views',
            __('Insert Views Shortcodes', 'wpv-views'),
            WPV_URL . '/res/js/views_editor_plugin.js',
            WPV_URL_EMBEDDED . '/res/img/views-icon-black_16X16.png');

    if ( !$inline ){ echo '<div class="wpv-vicon-for-posts'. $post_hidden .'">';}

	if ( !$inline ){
	    add_short_codes_to_js( array('post', 'taxonomy', 'post-view'), $WP_Views->editor_addon );
	    $WP_Views->editor_addon->add_form_button('', $editor_id , true, true, true);
	}
	else{
		if ( empty($view) && isset($_POST['view_id']) ){
			$view = $_POST['view_id'];
			$meta = get_post_meta( $view, '_wpv_settings', true);
		}
		if ( !isset($meta['query_type'][0]) || ( isset($meta['query_type'][0]) && $meta['query_type'][0]=='posts' )){
			add_short_codes_to_js( array('post', 'taxonomy', 'post-view','body-view-templates-posts'), $WP_Views->editor_addon );
	    	$WP_Views->editor_addon->add_form_button('', $editor_id , true, true, true);
		}elseif( isset($meta['query_type'][0]) && $meta['query_type'][0]=='users' ){
			$WP_Views->editor_addon->add_users_form_button('', $editor_id, true);
		}
		elseif( isset($meta['query_type'][0]) && $meta['query_type'][0]=='taxonomy' ){
			remove_filter('editor_addon_menus_wpv-views', 'wpv_post_taxonomies_editor_addon_menus_wpv_views_filter', 11);
        	add_filter('editor_addon_menus_wpv-views', 'wpv_layout_taxonomy_V');
       		$WP_Views->editor_addon->add_form_button('', $editor_id, true, true, true);
        	remove_filter('editor_addon_menus_wpv-views', 'wpv_layout_taxonomy_V');
        	add_filter('editor_addon_menus_wpv-views', 'wpv_post_taxonomies_editor_addon_menus_wpv_views_filter', 11);
		}

	}

    if ( !$inline ){echo '</div>';  }

    if ( !$inline ){
        echo '<div class="wpv-vicon-for-taxonomy'. $tax_hidden .'">';
        remove_filter('editor_addon_menus_wpv-views', 'wpv_post_taxonomies_editor_addon_menus_wpv_views_filter', 11);
        add_filter('editor_addon_menus_wpv-views', 'wpv_layout_taxonomy_V');

        $WP_Views->editor_addon->add_form_button('', $editor_id, true, true, true);

        remove_filter('editor_addon_menus_wpv-views', 'wpv_layout_taxonomy_V');
        add_filter('editor_addon_menus_wpv-views', 'wpv_post_taxonomies_editor_addon_menus_wpv_views_filter', 11);
        echo '</div>';
    }
	if ( !$inline ){
        echo '<div class="wpv-vicon-for-users'. $users_hidden .'">';

        //add_filter('editor_addon_menus_wpv-views', 'wpv_layout_users_V');

        $WP_Views->editor_addon->add_users_form_button('', $editor_id, true);

        //remove_filter('editor_addon_menus_wpv-views', 'wpv_layout_users_V');
        //add_filter('editor_addon_menus_wpv-views', 'wpv_post_taxonomies_editor_addon_menus_wpv_views_filter', 11);
        echo '</div>';
    }
}

/**
 * Add usermeta V icon menu
 *
 *
 **/
function wpv_layout_users_V($menu) { // MAYBE DEPRECATED

    // remove post items and add taxonomy items.

    global $wpv_shortcodes;
    //print_r( $wpv_shortcodes );exit;
    $basic = __('Basic', 'wpv-views');
    $menu = array($basic => array());
    //print_r($menu);exit;
    /*$taxonomy = array('username',
                      'aim');

    foreach ($taxonomy as $key) {
        $menu[$basic][$wpv_shortcodes[$key][1]] = array($wpv_shortcodes[$key][1],
                                                                        $wpv_shortcodes[$key][0],
                                                                        $basic,
                                                                        '');
    }    */
    return $menu;

}

/**
* wpv_create_content_template
*
* Creates a new Content Template given a title and an optional suffix
*
* @param $title (string)
* @param $suffix (string)
* @param $force (boolean) whether to force the creation of the Template by incremental numbers added to the title in case it is already in use
* @param $content (string)
*
* @return (array) $return
*     'success' => (int) The ID of the CT created
*     'error' => (string) Error message
*     'title' => (string) The title of the CT created or the one that made this fail
*
* @since 1.7
*/

function wpv_create_content_template( $title, $suffix = '', $force = true, $content = '' ) {
    global $wpdb;
	$return = array();
	$real_suffix = '';
	if ( ! empty( $suffix ) ) {
		$real_suffix = ' - ' . $suffix;
	}
	if ( $force ) {
		$counter = 0;
		while ( $counter < 20 ) {
			$add = ' ' . $counter;
			if ( $counter == 0 ) {
				$add = '';
			}
			$template_title = $title . $real_suffix . $add;
			$existing = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT count(ID) FROM {$wpdb->posts} WHERE ( post_title = %s OR post_name = %s ) AND post_type = 'view-template' LIMIT 1",
					$template_title,
					$template_title
				)
			);
			if ( $existing <= 0 ) {
				break;
			} else {
				$counter++;
			}
		}
	} else {
		$template_title = $title . $real_suffix;
		$existing = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT count(ID) FROM {$wpdb->posts} WHERE ( post_title = %s OR post_name = %s ) AND post_type = 'view-template' LIMIT 1",
				$template_title,
				$template_title
			)
		);
		if ( $existing > 0 ) {
			$return['error'] = __( 'A Content Template with that title already exists. Please use another title.', 'wpv-views' );
			$return['title'] = $template_title;
			return $return;
		}
	}

	$template = array(
		'post_title'    => $template_title,
		'post_type'     => 'view-template',
		'post_content'  => $content,
		'post_status'   => 'publish'
	);

	$template_id = wp_insert_post( $template );
	update_post_meta( $template_id, '_wpv_view_template_mode', 'raw_mode' );
	update_post_meta( $template_id, '_wpv-content-template-decription', '' );
	$return['success'] = $template_id;
	$return['title'] = $template_title;
	return $return;
}

/**
* wpv_clone_content_template
*
* API to clone a Content Template
*
* @param (int) $origin_ct_id The original CT ID
* @param (array) $args Some modifiers
*     'title' => (string) If passed, use this title instead of the original CT
*     'force => (boolean) Whether to force the creation of the clone or bail on title duplication
*
* @return (array) $clone
*     'success' => (int) The ID of the CT created
*     'error' => (string) Error message
*     'title' => (string) The title of the CT created or the one that made this fail
*
* @since 1.7
*/

function wpv_clone_content_template( $origin_ct_id, $args = array() ) {
	$args_default = array(
		'title' => false,
		'force' => false
	);
	$args = wp_parse_args( $args, $args_default );
	$original_post = get_post( $origin_ct_id, ARRAY_A );
	$cloned_title = $original_post['post_title'];
	if ( $args['title'] ) {
		$cloned_title = $args['title'];
	}
	$clone = wpv_create_content_template( $cloned_title, '', $args['force'], $original_post['post_content'] );
	if ( isset( $clone['success'] ) ) {
		$origin_ct_meta = get_post_meta( $origin_ct_id );
		foreach ( $origin_ct_meta as $key => $value ) {
			if ( 
				$key != '_edit_lock' 
				&& $key != '_view_loop_id'
			) {
				update_post_meta( $clone['success'], $key, $value[0] );
			}
		}
	}
	return $clone;
}

/**
* wpv_create_view
*
* API function to create a new View
*
* @param $args (array) set of arguments for the new View
*    'title' (string) (semi-mandatory) Title for the View
*    'settings' (array) (optional) Array compatible with the View settings to override the defaults
*    'layout_settings' (array) (optional) Array compatible with the View layout settings to override the defaults
*
* @return (array) response of the operation, one of the following
*    $return['success] = View ID
*    $return['error'] = 'Error message'
*
* @since 1.6.0
*
* @note overriding default Views settings and layout settings must provide complete data when the element is an array, because it overrides them all.
*    For example, $args['settings']['pagination'] can not override just the "postsper page" options: it must provide a complete pagination implementation.
*    This might change and be corrected in the future, keeping backwards compatibility.
*
* @todo once we create a default layout for a View, we need to make sure that:
* - the _view_loop_template postmeat is created and updated - DONE
* - the fields added to that loop Template are stored in the layout settings - PENDING
* - check how Layouts can apply this all to their Views, to create a Bootstrap loop by default - PENDING
*/

function wpv_create_view( $args ) {
	global $wpdb;
	$return = array();
	// First, set the title
	if ( !isset( $args["title"] ) || $args["title"] == '' ) {
		$args["title"] = __('Unnamed View', 'wp-views');
	}
	// Check for already existing Views with that title
	$existing = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT ID FROM {$wpdb->posts} WHERE ( post_title = %s OR post_name = %s ) AND post_type = 'view' LIMIT 1",
			$args["title"],
			$args["title"]
		)
	);
	if ( $existing ) {
		$return['error'] = __( 'A View with that name already exists. Please use another name.', 'wpv-views' );
		return $return;
	}
	// Compose the $post to be created
	$post = array(
		'post_type'	=> 'view',
		'post_title'	=> $args["title"],
		'post_status'	=> 'publish',
		'post_content'	=> "[wpv-filter-meta-html]\n[wpv-layout-meta-html]"
	);
	$id = wp_insert_post( $post );
	if ( 0 != $id ) {
		if ( !isset( $args['settings'] ) || !is_array( $args['settings'] ) ) {
			$args['settings'] = array();
		}
		if ( !isset( $args['layout_settings'] ) || !is_array( $args['layout_settings'] ) ) {
			$args['layout_settings'] = array();
		}
		if ( !isset( $args['settings']["view-query-mode"] ) ) {
			$args['settings']["view-query-mode"] = 'normal';  // TODO check if view-query-mode is needed anymore, see below
		}
		if ( !isset( $args['settings']["view_purpose"] ) ) {
			$args['settings']["view_purpose"] = 'full';
		}

		$create_loop_template = false;
		$create_loop_template_suffix = '';
		$create_loop_template_content = '';
		$create_loop_template_layout = '';

		switch ( $args['settings']["view-query-mode"] ) {
			case 'archive':
				$view_normal_defaults = wpv_wordpress_archives_defaults( 'view_settings' );
				$view_normal_layout_defaults = wpv_wordpress_archives_defaults( 'view_layout_settings' );
				break;
			case 'layouts-loop':
				$view_normal_defaults = wpv_wordpress_archives_defaults( 'view_settings' );
				$view_normal_layout_defaults = wpv_wordpress_archives_defaults( 'view_layout_settings' );
				$create_loop_template = true;
				$create_loop_template_suffix = __('loop item', 'wpv-views' );
				$create_loop_template_content = "<h1>[wpv-post-title]</h1>\n[wpv-post-body view_template=\"None\"]\n[wpv-post-featured-image]\n"
					. sprintf(__('Posted by %s on %s', 'wpv-views'), '[wpv-post-author]', '[wpv-post-date]');
				break;
			default:
				$view_normal_defaults = wpv_view_defaults( 'view_settings', $args['settings']["view_purpose"] );
				$view_normal_layout_defaults = wpv_view_defaults( 'view_layout_settings', $args['settings']["view_purpose"] );
				if ( $args['settings']["view_purpose"] == 'slider' ) {
					$create_loop_template = true;
					$create_loop_template_suffix = __('slide', 'wpv-views' );
					$create_loop_template_content = '[wpv-post-link]';
				} else if ( $args['settings']["view_purpose"] == 'bootstrap-grid' ) {
					// Deprecated in Views 1.7, keep for backwards compatibility
					$args['settings']["view_purpose"] = 'full';
				}
				break;
		}

		if ( $create_loop_template ) {
			// @todo review
			// This creates the Template, but it does not adjust the Layout Wizard settings to use it, in case someone touches it
			$template = wpv_create_content_template( $args["title"], $create_loop_template_suffix, true, $create_loop_template_content );
			if ( isset ( $template['success'] ) ) {
				$template_id = $template['success'];
				if ( isset( $template['title'] ) ) {
					$template_title = $template['title'];
				} else {
					$template_object = get_post( $template_id );
					$template_title = $template_object->post_title;
				}
				// @todo here we should create the layout acordingly to the $create_loop_template_layout value
				$view_normal_layout_defaults['layout_meta_html'] = str_replace(
					'<wpv-loop>',
					'<wpv-loop>[wpv-post-body view_template="' . $template_title . '"]',
					$view_normal_layout_defaults['layout_meta_html']
				);
				$view_normal_layout_defaults['included_ct_ids'] = $template_id;
				update_post_meta( $id, '_view_loop_template', $template_id );
				update_post_meta( $template_id, '_view_loop_id', $id );
				// @todo
				// I really hate this solution
				update_post_meta( $id, '_wpv_first_time_load', 'on' );
			}
		}

		// Override the settings with our own
		foreach ( $args['settings'] as $key => $value ) {
			$view_normal_defaults[$key] = $args['settings'][$key];
		}
		// Override the layout settings with our own
		foreach ( $args['layout_settings'] as $key => $value ) {
			$view_normal_layout_defaults[$key] = $args['layout_settings'][$key];
		}
		// Set the whole View settings
		update_post_meta($id, '_wpv_settings', $view_normal_defaults);
		update_post_meta($id, '_wpv_layout_settings', $view_normal_layout_defaults);
		$return['success'] = $id;
	} else {
		$return['error'] = __( 'The View could not be created.', 'wpv-views' );
		return $return;
	}
	return $return;
}

/* NOTE: This function is also called from Layouts plugin */
function wpv_create_bootstrap_meta_html ($cols, $ct_title, $meta_html) {
	global $WP_Views;

	$col_num = 12 / $cols;
	$output = '';
	$row_style = '';
	$col_style = 'col-sm-';
	$body = '[wpv-post-body view_template="' . $ct_title. '"]';

	//Row style and cols class for bootstrap 2.0

	$options = $WP_Views->get_options();
	if (class_exists('WPDD_Layouts_CSSFrameworkOptions')) {
		$bootstrap_ver = WPDD_Layouts_CSSFrameworkOptions::getInstance()->get_current_framework();
		$options['wpv_bootstrap_version'] = str_replace('bootstrap-','',$bootstrap_ver);
	}else{
		//Load bootstrap version from views settings
		if ( !isset($options['wpv_bootstrap_version']) ){
			$options['wpv_bootstrap_version'] = 2;
		}
	}

	if ( $options['wpv_bootstrap_version'] == 2){
		$row_style = ' row-fluid';
		$col_style = 'span';
	}

	$output .= "   <wpv-loop wrap=\"" . $cols . "\" pad=\"true\">\n";
	$ifone = '';
	//
	if ( $cols == 1){
		$ifone = '</div>';
	}
	$output .= "         [wpv-item index=1]\n";
	$output .= "            <div class=\"row" . $row_style . "\"><div class=\"" . $col_style . $col_num . "\">" . $body . "</div>" . $ifone . "\n";
	$output .= "         [wpv-item index=other]\n";
	$output .= "            <div class=\"" . $col_style . $col_num . "\">" . $body . "</div>\n";

	if ( $cols > 1){
		$output .= "         [wpv-item index=" . $cols . "]\n";
		$output .= "            <div class=\"" . $col_style . $col_num . "\">" . $body . "</div></div>\n";
	}

	$output .= "         [wpv-item index=pad]\n";
	$output .= "            <div class=\"" . $col_style . $col_num . "\"></div>\n";
	$output .= "         [wpv-item index=pad-last]\n";
	$output .= "            </div>\n";
	$output .= "    </wpv-loop>\n";

	return preg_replace('#\<wpv-loop(.*?)\>(.*)</wpv-loop>#is', $output, $meta_html);
}


/**
 * Generate row actions div.
 *
 * Taken from the WP_List_Table WordPress core class.
 *
 * @since 1.7
 *
 * @link https://core.trac.wordpress.org/browser/tags/4.0/src//wp-admin/includes/class-wp-list-table.php#L443
 *
 * @param array $actions List of actions. Action can be an arbitrary HTML code, while key of the element will be used
 * as a class of the wrapping span tag (so it may contain multiple class names separated by space).
 * @param array $custom_attributes List of custom attributes (key-value pairs) to be added to the wrapping span tag.
 * @param bool $always_visible Whether the actions should be always visible.
 *
 * @return string HTML code of the row actions div or empty string if no actions are provided.
 */
function wpv_admin_table_row_actions( $actions, $custom_attributes = array(), $always_visible = false ) {
	$action_count = count( $actions );
	$i = 0;

	if ( !$action_count ) {
		return '';
	}

	$custom_attributes_flat = array();
	foreach( $custom_attributes as $attr => $value ) {
		$custom_attributes_flat[] = sprintf( '%s="%s"', $attr, $value );
	}
	$custom_attributes_string = implode( ' ', $custom_attributes_flat );

	$out = '<div class="' . ( $always_visible ? 'row-actions visible' : 'row-actions' ) . '">';
	foreach ( $actions as $action => $link ) {
		++$i;
		( $i == $action_count ) ? $sep = '' : $sep = ' | ';
		$out .= "<span class='$action' $custom_attributes_string>$link$sep</span>";
	}
	$out .= '</div>';

	return $out;
}


/**
 * Render controls for bulk actions on listing pages.
 *
 * Renders a select field and an Apply (submit) button in a 'bulkactions' div tag.
 *
 * @since 1.7
 *
 * @param array $actions Array of bulk actions to offer. Keys are action slugs, values are names to be shown to the user.
 * @param string $class Base name for the class attribute of rendered elements. Select field will have class
 *     "{$class}-select" and submit button "{$class}-submit".
 * @param array $submit_button_attributes An key-value array of additional attributes that will be added to the submit button.
 * @param string $position Position of bulk action fields. Usually they are rendered twice on a page, on the top and
 *     after the listing. This value is added as another class (specifically "position-{$position}") to the select and
 *     the submit button. It is used to determine the matching select field after user clicks on a submit button.
 *
 * @return Rendered HTML code.
 */
function wpv_admin_table_bulk_actions( $actions, $class, $submit_button_attributes = array(), $position = 'top' ) {
	$out = '<div class="alignleft actions bulkactions">';

	$out .= sprintf( '<select class="%s">', $class . '-select position-' . $position );
	$out .= sprintf( '<option value="-1" selected="selected">%s</option>', __( 'Bulk Actions', 'wpv-views' ) );

	foreach( $actions as $action => $label ) {
		$out .= sprintf( '<option value="%s">%s</option>', $action, $label );
	}
	$out .= '</select> ';

	$submit_button_attributes_flat = '';
	foreach( $submit_button_attributes as $attribute => $value ) {
		$submit_button_attributes_flat .= sprintf( ' %s="%s" ', $attribute, $value );
	}

	$out .= sprintf(
			'<input type="submit" value="%s" class="%s" data-position="%s" %s />',
			__( 'Apply', 'wpv-views' ),
			'button button-secondary ' . $class . '-submit',
			$position,
			$submit_button_attributes_flat );

	$out .= '</div>';
	return $out;
}


/**
 * Retrieve a modified URL with query string, omitting empty query arguments.
 *
 * Behaves exactly like add_query_arg(), except that it omits arguments with
 * value of empty string.
 *
 * @since 1.7
 *
 * @link http://codex.wordpress.org/Function_Reference/add_query_arg
 *
 * @param array $args Associative array of argument names and their values.
 * @param string $url Existing URL.
 *
 * @return New URL query string.
 */
function wpv_maybe_add_query_arg( $args, $url ) {
	foreach( $args as $key => $val ) {
		if( '' === $val ) {
			unset( $args[ $key ] );
		}
	}
	return add_query_arg( $args, $url );
}


/**
 * Optionaly render a message on a listing page.
 *
 * If a given URL parameter is present that indicates a finished action, show a message. Value of this parameter is
 * supposed to be a number of affected posts.
 *
 * If more than one post was affected, a plural message is shown, otherwise a singular one.
 * Plural message is expected to contain one "%d" placeholder for the number of affected posts.
 *
 * This function also looks for a list of affected IDs (as comma-separated values in an URL parameter) and
 * if $has_undo is true, the filter wpv_maybe_show_listing_message_undo is applied to obtain an Undo link for
 * this action.
 *
 * The message will appear below the h2 tag.
 *
 * @since 1.7
 *
 * @param string $message_name Name of the URL parameter indicating this message should be rendered.
 * @param string $text_singular Message text that will be echoed when one post was affected.
 * @param string $text_plural Message text that will be echoed when more posts were affected.
 * @param bool $has_undo Indicates whether a filter should be applied to obtain an Undo link. Default is false.
 * @param string $affected_id_arg Name of the URL parameter possibly containing IDs of affected posts.
 */
function wpv_maybe_show_listing_message( $message_name, $text_singular, $text_plural, $has_undo = false, $affected_id_arg = 'affected' ) {

	if ( isset( $_GET[ $message_name ] ) ) {

		// Number of affected posts
		$message_value = $_GET[ $message_name ];
		// IDs of affected posts (if set)
		$affected_ids = isset( $_GET[ $affected_id_arg ] ) ? explode( ',', $_GET[ $affected_id_arg ] ) : array( );

		if( $has_undo ) {

			/**
			 * Construct an "Undo" link for a message on listing page.
			 *
			 * Resulting string will be appended after message text.
			 *
			 * @since 1.7
			 *
			 * @param string $undo_html An Undo link to be appended after the message.
			 * @param string $message_name Name of the message as it was passed to wpv_maybe_show_listing_message().
			 * @param array $affected_ids IDs of posts affected by the action.
			 */
			$undo = ' ' . apply_filters( 'wpv_maybe_show_listing_message_undo', '', $message_name, $affected_ids );

		} else {
			$undo = '';
		}

		// Choose the appropriate message text.
		if( $message_value > 1 ) {
			$text = sprintf( $text_plural, $message_value );
		} else {
			$text = $text_singular;
		}

		$text .= $undo;


		?>
		<div id="message" class="updated below-h2">
			<p><?php echo $text ?></p>
		</div>
		<?php
	}
}


/**
 * Replace occurences of a View/Content Template/WordPress Archive ID by another ID in Views' options.
 *
 * Specifically, all options starting with 'views_template_' are processed.
 *
 * @since 1.7
 *
 * @param int $replace_what The ID to be replaced.
 * @param int $replace_by New value.
 * @param mixed $options If null, Views options are obtained from global $WP_Views and also saved there afterwards.
 *     Otherwise, an array with Views options is expected and after processing it is not saved, but returned instead.
 *
 * @return Modified array of Views options if $options was provided, nothing otherwise.
 */
function wpv_replace_views_template_options( $replace_what, $replace_by, $options = null ) {
	if( null == $options ) {
		global $WP_Views;
		$options = $WP_Views->get_options();
		$save_options = true;
	} else {
		$save_options = false;
	}

	foreach ( $options as $option_name => $option_value ) {
		if ( ( strpos( $option_name, 'views_template_' ) === 0 )
			&& $option_value == $replace_what )
		{
			$options[ $option_name ] = $replace_by;
		}
	}

	if( $save_options ) {
		$WP_Views->save_options( $options );
	} else {
		return $options;
	}
}


/**
 * Modify arguments for WP_Query on listing pages when searching for a string in Views, Content Templates
 * or WordPress Archives.
 *
 * This function will search for given string in titles and descriptions. It returns a modified array of arguments
 * for the "final" query on a listing page with the "post__in" argument containing array View/CT/WPA IDs where matching
 * string was found.
 *
 * Post meta key containing description will be determined from 'post_type' argument in $wpv_args.
 *
 * @param string $s Searched string (will be sanitized).
 * @param array $wpv_args Arguments for the listing WP_Query. They must contain the 'post_type' key with value
 *     either 'view' or 'view-template'.
 *
 * @return array Modified $wpv_args for the listing query.
 *
 * @since 1.7
 */
function wpv_modify_wpquery_for_search( $s, $wpv_args ) {

	$s_param = urldecode( sanitize_text_field( $s ) );
	$results = array();

	$search_args = $wpv_args;
	$search_args['posts_per_page'] = '-1'; // return all posts
	$search_args['fields'] = 'ids'; // return only post IDs

	// First, search in post titles
	$titles_search_args = $search_args;
	$titles_search_args['s'] = $s_param;

	$query_titles = new WP_Query( $titles_search_args );
	$title_results = $query_titles->get_posts();
	if( !is_array( $title_results ) ) {
		$title_results = array();
	}

	// Now search in description.

	// Determine description meta_key based on post type.
	$description_key = '';
	switch( $wpv_args['post_type'] ) {
		// This covers both Views and WPAs.
		case 'view':
			$description_key = '_wpv_description';
			break;
		// Content templates.
		case 'view-template':
			$description_key = '_wpv-content-template-decription';
			break;
	}

	$description_search_args = $search_args;
	$description_search_args['meta_query'] = array(
			array(
				'key' => $description_key,
				'value' => $s_param,
				'compare' => 'LIKE' ) );

	$query_description = new WP_Query( $description_search_args );
	$description_results = $query_description->get_posts();
	if( !is_array( $description_results ) ) {
		$description_results = array();
	}

	// Merge results from both queries.
	$results = array_unique( array_merge( $title_results, $description_results ) );

	// Modify arguments for the final query
	if ( count( $results ) == 0 ) {
		$wpv_args['post__in'] = array( '0' );
	} else {
		$wpv_args['post__in'] = $results;
	}

	return $wpv_args;
}


/**
 * Prepare data for querying Views or WordPress Archives.
 *
 * Because Views and WPAs have the same post type and the information about this "query mode" is stored in a serialized
 * array in postmeta, we have to allways get all views (here views = posts of type "view"), parse it's settings from
 * postmeta (which is more complicated than it seems, see $WP_Views->get_view_settings()) and decide whether to
 * include it in possible results of the final query (that handles things like sorting, ordering, pagination).
 *
 * From all the possible results, we also need to count how many of them are published and trashed, because those numbers
 * also show up on listing pages.
 *
 * We can do all this with one query that will get all IDs, post status and the postmeta with serialized settings. Then,
 * based on post status and query mode, this function will produce an array of IDs of possible results.
 *
 * @param string|array $view_query_mode Query mode (kind of View object). It can be one string value or multiple values
 *     in an array. Usual values are 'normal' (for a View) or 'archive' (for a WPA), however there is also a deprecated
 *     value 'layouts-loop' for WPAs. @todo update this
 * @param string $listed_post_status Post status that is going to be queried: 'publish' or 'trash'.
 * @param array $additional_fields Optional. Additional fields to be queried from the database. Keys must be valid
 *     column names and values are their aliases. Makes sense only with $return_rows = true.
 * @param bool $return_rows Optional. If set to true, returned array will also contain the 'rows' element.
 * @param array $additional_where Optional. An array of additional conditions for the WHERE clause.
 *
 * @return array {
 *     @type int published_count Count of published posts of given query mode.
 *     @type int trashed_count Count of trashed posts of given query mode.
 *     @type int total_count Count of published+trashed posts of given query mode.
 *     @type array post__in An array of post IDs that have the right query mode and post status.
 *     @type array rows If $return_rows is true, this will contain the database results for views accepted in post__in.
 * }
 *
 * @since 1.7
 */
function wpv_prepare_view_listing_query( $view_query_mode, $listed_post_status,
		$additional_fields = array(), $return_rows = false, $additional_where = array() ) {
	global $wpdb, $WP_Views;

	// Build a string for SELECT from default and additional fields.
	$select = array(
			'ID AS id',
			'posts.post_status AS post_status',
			'postmeta.meta_value AS view_settings' );
	foreach( $additional_fields as $field => $alias ) {
		$select[] = "$field AS $alias";
	}
	$select_string = implode( ', ', $select );

	// Build a string for WHERE from default and additional conditions.
	$where = array(
			"posts.post_type = 'view'",
			"post_status IN ( 'publish', 'trash' )" );
	$where = array_merge( $where, $additional_where );
	$where_string = implode( ' AND ', $where );

	/* Queries rows with post id, status, value of _wpv_settings meta (or null if it doesn't exist, notice the LEFT JOIN)
	 * and additional fields. */
	$query = "SELECT {$select_string}
			FROM {$wpdb->posts} AS posts
				LEFT JOIN {$wpdb->postmeta} AS postmeta
				ON ( posts.ID = postmeta.post_id AND postmeta.meta_key = '_wpv_settings' )
			WHERE ( {$where_string} )";
	$views = $wpdb->get_results( $query );

	$published_count = 0;
	$trashed_count = 0;
	$post_in = array();

	// This will hold rows from the database if $return_rows is true.
	$rows = array();

	if( !is_array( $view_query_mode ) ) {
		$view_query_mode = array( $view_query_mode );
	}

	/* For each result we need to determine if it's a View or a WPA. If it's what we want, decide by
	 * it's post_status which counter to increment and whether to include into post__in (that means possible result
	 * in the final listing query). */
	foreach( $views as $view ) {

		// Prepare the value of _wpv_settings postmeta in the same way get_post_meta( ..., ..., true ) would.
		// If we don't get a value that makes sense, we just fall back to what would get_view_settings() do.
		$meta_value = ( null == $view->view_settings ) ? null: maybe_unserialize( $view->view_settings );

		// Get View settings without touching database again
		$view_settings = $WP_Views->get_view_settings( $view->id, array(), $meta_value );

		// It is the right kind of View?
		if ( in_array( $view_settings['view-query-mode'], $view_query_mode ) ) {

			// Update counters
			if( 'publish' == $view->post_status ) {
				++$published_count;
			} else {
				// Now post_status can be only 'trash' because of the condition in mysql query
				++$trashed_count;
			}

			if( $listed_post_status == $view->post_status ) {
				// This is a possible result of the final listing query
				$post_in[] = $view->id;
				if( $return_rows ) {
					$rows[] = $view;
				}
			}
		}
	}

	// If there are no results, we don't want any post to match anything in post__in.
	if( count( $post_in ) == 0 ) {
		$post_in[] = 0;
	}

	$ret = array(
			'published_count' => $published_count,
			'trashed_count' => $trashed_count,
			'total_count' => $published_count + $trashed_count,
			'post__in' => $post_in );
	if( $return_rows ) {
		$ret['rows'] = $rows;
	}

	return $ret;
}


/**
 * Generate default layout settings for a View, based on chosen layout style
 *
 * @param string $layout_style Loop output style name, which must be one of the following values:
 *     - table
 *     - bootstrap-grid
 *     - table_of_fields
 *     - ordered_list
 *     - un_ordered_list
 *     - unformatted
 *
 * @param array $fields (
 *         Array of definitions of fields that will be present in the layout. If an element is not present, empty
 *         string is used instead.
 *
 *         @type string $prefix Prefix, text before shortcode.
 *         @type string $shortcode The shortcode ('[shortcode]').
 *         @type string $suffix Text after shortcode.
 *         @type string $field_name Field name.
 *         @type string $header_name Header name.
 *         @type string $row_title Row title <TH>.
 *     )
 *
 * @param array $args(
 *         Additional arguments.
 *
 *         @type bool $include_field_names If the layout style is table_of_fields, determines whether the rendered
 *             layout will contain table header with field names. Optional. Default is true.
 *         @type int $tab_column_count Number of columns for the bootstrap-grid layout. Optional. Default is 1.
 *         @type int $bootstrap_column_count Number of columns for the table layout. Optional. Default is 1.
 *         @type int $bootstrap_version Version of Bootstrap. Mandatory for bootstrap-grid layout style, irrelephant
 *             otherwise. Must be 2 or 3.
 *         @type bool $add_container Argument for bootstrap-grid layout style. If true, enclose rendered html in a
 *             container div. Optional. Default is false.
 *         @type bool $add_row_class Argument for bootstrap-grid layout style. If true, a "row" class will be added to
 *             elements representing rows. For Bootstrap 3 it is added anyway. Optional. Default is false.
 *         @type bool $render_individual_columns Argument for bootstrap-grid layout style. If true, a wpv-item shortcode
 *             will be rendered for each singular column. Optional. Default is false.
 *         @type bool $render_whole_html If true, whole layout_meta_html value is rendered, otherwise only the wpv-loop
 *             and it's content. Optional. Default is false.
 *     )
 *
 * @return  null|array Layout settings for a View (see below) or null on error.
 *     array(
 * TODO comment properly
 *         @type string $style
 *         @type string $layout_meta_html
 *         @type int $table_cols
 *         @type int $bootstrap_grid_cols
 *         @type string $bootstrap_grid_container '1' or ''
 *         @type string $bootstrap_grid_row_class '1' or ''
 *         @type string $bootstrap_grid_individual '1' or ''
 *         @type string $include_field_names '1' or ''
 *         @type array $fields
 *         @type array $real_fields
 *     )
 *
 * @since 1.7
 *
 * @note on the render functions, replace spaces with tabs
 */
function wpv_generate_views_layout_settings( $layout_style, $fields, $args ) {

	// Default values for arguments
	$args = wp_parse_args(
			$args,
			array(
					'include_field_names' => true,
					'tab_column_count' => 1,
					'bootstrap_column_count' => 1,
					'bootstrap_version' => 'undefined',
					'add_container' => false,
					'add_row_class' => false,
					'render_individual_columns' => false,
					'render_whole_html' => false ) );
	extract( $args );

	// Results
	$layout_settings = array(
			'style' => $layout_style,  // this will be valid value, or we'll return null later
			'additional_js'	=> '' );

	// Ensure all field keys are present for all fields.
	$fields_normalized = array();
	$field_defaults = array(
			'prefix' => '',
			'shortcode' => '',
			'suffix' => '',
			'field_name' => '',
			'header_name' => '',
			'row_title' => '' );
	foreach( $fields as $field ) {
		$fields_normalized[] = wp_parse_args( $field, $field_defaults );
	}
	$fields = $fields_normalized;

	// Render layout HTML
	switch( $layout_style ) {
		case 'table':
			$layout_meta_html = wpv_render_table_layout( $fields, $args );
			break;
		case 'bootstrap-grid':
			$layout_meta_html = wpv_render_bootstrap_grid_layout( $fields, $args );
			break;
		case 'table_of_fields':
			$layout_meta_html = wpv_render_table_of_fields_layout( $fields, $args );
			break;
		case 'ordered_list':
			$layout_meta_html = wpv_render_list_layout( $fields, 'ol' );
			break;
		case 'un_ordered_list':
			$layout_meta_html = wpv_render_list_layout( $fields, 'ul' );
			break;
		case 'unformatted':
			$layout_meta_html = wpv_render_unformatted_layout( $fields );
			break;
		default:
			// Invalid layout style
			echo "Invalid layout style";
			return null;
	}
	// If rendering has failed, we fail too.
	if( null == $layout_meta_html ) {
		echo "layout_meta_html";
		return null;
	}

	// Are we rendering the whole layout_meta_html or only the wpv-loop?
	if( $render_whole_html ) {
		$layout_meta_html = sprintf(
				"[wpv-layout-start]\n"
				. "	[wpv-items-found]\n"
				. "	<!-- wpv-loop-start -->\n"
				. "%s"
				. "	<!-- wpv-loop-end -->\n"
				. "	[/wpv-items-found]\n"
				. "	[wpv-no-items-found]\n"
				. "		[wpml-string context=\"wpv-views\"]<strong>No items found</strong>[/wpml-string]\n"
				. "	[/wpv-no-items-found]\n"
				. "[wpv-layout-end]\n",
				$layout_meta_html );
	}

	$layout_settings['layout_meta_html'] = $layout_meta_html;

	// Pass other layout settings in the same way as in wpv_update_layout_extra_callback().

	// Only one value makes sense, but both are always stored...
	$layout_settings['table_cols'] = $tab_column_count;
	$layout_settings['bootstrap_grid_cols']  = $bootstrap_column_count;

	// These are '1' for true or '' for false (not sure if e.g. 0 can be passed instead, better leave it as it was).
	$layout_settings['bootstrap_grid_container'] = $add_container ? '1' : '';
	$layout_settings['bootstrap_grid_row_class'] = $add_row_class ? '1' : '';
	$layout_settings['bootstrap_grid_individual'] = $render_individual_columns ? '1' : '';
	$layout_settings['include_field_names'] = $include_field_names ? '1' : '';

	/* The 'fields' element is originally constructed in wpv_layout_wizard_convert_settings() with a comment
	 * saying just "Compatibility". TODO it would be nice to explain why is this needed (compatibility with what?). */
	$fields_compatible = array();
    $field_index = 0;
    foreach ( $fields as $field ) {
        $fields_compatible[ 'prefix_' . $field_index ] = '';

        $shortcode = stripslashes( $field['shortcode'] );

        if ( preg_match( '/\[types.*?field=\"(.*?)\"/', $shortcode, $matched ) ) {
            $fields_compatible[ 'name_' . $field_index ] = 'types-field';
            $fields_compatible[ 'types_field_name_' . $field_index ] = $matched[1];
            $fields_compatible[ 'types_field_data_' . $field_index ] = $shortcode;
        } else {
            $fields_compatible[ 'name_' . $field_index ] = trim( $shortcode, '[]');
            $fields_compatible[ 'types_field_name_' . $field_index ] = '';
            $fields_compatible[ 'types_field_data_' . $field_index ] = '';
        }

        $fields_compatible[ 'row_title_' . $field_index ] = $field['field_name'];
        $fields_compatible[ 'suffix_' . $field_index ] = '';

        ++$field_index;
    }
	$layout_settings['fields'] = $fields_compatible;

    // 'real_fields' will be an array of field shortcodes
    $field_shortcodes = array();
    foreach( $fields as $field ) {
		$field_shortcodes[] = stripslashes( $field['shortcode'] );
	}
    $layout_settings['real_fields'] = $field_shortcodes;

	return $layout_settings;
}


/**
 * Render unformatted View layout.
 *
 * @see wpv_generate_views_layout_settings()
 *
 * @param array $fields Array of fields to be used inside this layout.
 *
 * @return string Layout code.
 *
 * @since 1.7
 */
function wpv_render_unformatted_layout( $fields ) {
	$body = '';
	foreach( $fields as $field ) {
		$body .= $field['prefix'] . $field['shortcode'] . $field['suffix'];
	}
	if ( ! empty( $body ) ) {
		$body .= "\n";
	}

	$output =
			"   <wpv-loop>\n" .
			"      {$body}" .
            "   </wpv-loop>\n";

	return $output;
}


/**
 * Render List View layout.
 *
 * @see wpv_generate_views_layout_settings()
 *
 * @param array $fields Array of fields to be used inside this layout.
 * @param string $list_type Type of the list. Can be 'ul' for unordered list or 'ol' for ordered list. Defaults to 'ul'.
 *
 * @return string Layout code.
 *
 * @since 1.7
 */
function wpv_render_list_layout( $fields, $list_type = 'ul' ) {
	$body = '';
	foreach( $fields as $field ) {
		$body .= $field['prefix'] . $field['shortcode'] . $field['suffix'];
	}

	$list_type = ( 'ol' == $list_type ) ? 'ol' : 'ul';

	$output =
			"   <{$list_type}>\n" .
			"      <wpv-loop>\n".
			"         <li>{$body}</li>\n" .
			"      </wpv-loop>\n" .
			"   </{$list_type}>\n";

	return $output;
}


/**
 * Render Table View layout.
 *
 * @see wpv_generate_views_layout_settings()
 *
 * @param array $fields Array of fields to be used inside this layout.
 * @param array $args Additional arguments. This method requires: 'include_field_names'.
 *
 * @return string Layout code.
 *
 * @since 1.7
 */
function wpv_render_table_of_fields_layout( $fields, $args = array() ) {

	// Optionally render table header with field names.
	$thead = '';
	if ( $args['include_field_names'] ) {
		$thead .= "            <thead><tr>\n";
		foreach( $fields as $field ) {
			$thead .= "               <th>[wpv-heading name=\"{$field['header_name']}\"]{$field['row_title']}[/wpv-heading]</th>\n";
		}
		$thead .= "            </tr></thead>\n";
	}

	// Table body
	$body = '';
	foreach( $fields as $field ) {
		$body .= "               <td>{$field['prefix']}{$field['shortcode']}{$field['suffix']}</td>\n";
	}

	$output =
			"   <table width=\"100%\">\n" .
			$thead .
			"      <tbody>\n" .
			"      <wpv-loop>\n" .
			"            <tr>\n" .
			$body .
			"            </tr>\n" .
			"      </wpv-loop>\n" .
			"      </tbody>\n" .
			"   </table>\n";

	return $output;
}


/**
 * Render Table-based grid View layout.
 *
 * @see wpv_generate_views_layout_settings()
 *
 * @param array $fields Array of fields to be used inside this layout.
 *
 * @return string Layout code.
 *
 * @since 1.7
 */
function wpv_render_table_layout( $fields, $args ) {

	$body = '';
	foreach( $fields as $field ) {
		$body .= $field['prefix'] . $field['shortcode'] . $field['suffix'];
	}

	$output =
			"   <table width=\"100%\">\n" .
			"      <wpv-loop wrap=\"{$args['tab_column_count']}\" pad=\"true\">\n" .
			"         [wpv-item index=1]\n" .
			"            <tr><td>{$body}</td>\n" .
			"         [wpv-item index=other]\n" .
			"            <td>{$body}</td>\n" .
			"         [wpv-item index={$args['tab_column_count']}]\n" .
			"            <td>{$body}</td></tr>\n" .
			"         [wpv-item index=pad]\n" .
			"            <td></td>\n" .
			"         [wpv-item index=pad-last]\n" .
			"            <td></td></tr>\n" .
			"      </wpv-loop>\n" .
			"   </table>\n";

	return $output;
}


/**
 * Render Bootstrap grid View layout.
 *
 * @see wpv_generate_views_layout_settings()
 *
 * @param array $fields Array of fields to be used inside this layout.
 * @param array $args Additional arguments. This method requires: bootstrap_column_count, bootstrap_version, add_container,
 *     add_row_class, render_individual_columns.
 *
 * @return string|null Layout code or null on error (invalid arguments).
 *
 * @since 1.7
 */
function wpv_render_bootstrap_grid_layout( $fields, $args ) {

	// bootstrap_column_count, bootstrap_version, add_container, add_row_class, render_individual_columns
	extract( $args );
	$column_count = $bootstrap_column_count;

	// Fail if we don't have valid bootstrap version
	if( !in_array( $bootstrap_version, array( 2, 3 ) ) ) {
		return null;
	}

	$body = '';
	foreach( $fields as $field ) {
		$body .= $field['prefix'] . $field['shortcode'] . $field['suffix'];
	}

	// Prevent division by zero
	if( $column_count < 1 ) {
		return null;
	}

	// TODO somebody please explain what this value means (why 12?)
	$column_offset = 12 / $column_count;

	$output = '';

	// Row style and cols class for bootstrap 2
	$row_style = ( $bootstrap_version == 2 ) ? ' row-fluid' : '';
	$col_style = ( $bootstrap_version == 2 ) ? 'span' : 'col-sm-';
	$col_class = $col_style . $column_offset;

	// Add row class (optional for bootstrap 2)
	$row_class = ( $add_row_class || ( 3 == $bootstrap_version ) ) ? 'row' : '';

	if( $add_container ) {
		$output .= "   <div class=\"container\">\n";
	}

	$output .= "   <wpv-loop wrap=\"{$column_count}\" pad=\"true\">\n";

	// TODO what does this mean?
	$ifone = ( 1 == $column_count ) ? '</div>' : '';

	if( $render_individual_columns ) {
		// Render items for each column.
		$output .=
				"         [wpv-item index=1]\n" .
				"            <div class=\"{$row_class} {$row_style}\"><div class=\"{$col_class}\">{$body}</div>{$ifone}\n";
		for( $i = 2; $i < $column_count; ++$i ) {
			$output .=
					"         [wpv-item index={$i}]\n" .
					"           <div class=\"{$col_class}\">{$body}</div>\n";
		}
	} else {
		// Render compact HTML
		$output .=
				"         [wpv-item index=1]\n" .
				"            <div class=\"{$row} {$row_style}\"><div class=\"{$col_class}\">{$body}</div>{$ifone}\n" .
				"         [wpv-item index=other]\n" .
				"            <div class=\"{$col_class}\">{$body}</div>\n";
	}

	// Render item for last column.
	if ( $column_count > 1) {
		$output .=
				"         [wpv-item index={$column_count}]\n" .
				"            <div class=\"{$col_class}\">{$body}</div></div>\n";
	}

	// Padding items
	$output .=
			"         [wpv-item index=pad]\n" .
			"            <div class=\"{$col_class}\"></div>\n" .
			"         [wpv-item index=pad-last]\n" .
			"            </div>\n" .
			"    </wpv-loop>\n";

	if ( $add_container ) {
		$output .= "    </div>\n";
	}

	return $output;
}
