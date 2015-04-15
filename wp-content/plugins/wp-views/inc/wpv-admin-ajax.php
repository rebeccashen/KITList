<?php

/*
* General file for all AJAX calls
* All AJAX calls used in the backend must be set here
*/

/*
* Views & WPA edit sceen
*/

/**
 * Screen options save callback function.
 *
 * @todo There may be some deprecated options, e.g. the option for layout-extra in sections-show-hide. These should be
 *     deleted in a future upgrade procedure. See following links for more information:
 *     - https://icanlocalize.basecamphq.com/projects/7393061-toolset/todo_items/193583572/comments#comment_303063628
 *     - https://icanlocalize.basecamphq.com/projects/7393061-toolset/todo_items/193583488/comments
 *
 * @since unknown
 */
add_action('wp_ajax_wpv_save_screen_options', 'wpv_save_screen_options_callback');

function wpv_save_screen_options_callback() {
	$nonce = $_POST["wpnonce"];
	if (! wp_verify_nonce($nonce, 'wpv_view_show_hide_nonce') ) die("Security check");
	$view_array = get_post_meta($_POST["id"], '_wpv_settings', true);
	if ( isset( $_POST['settings'] ) ) {
		parse_str($_POST['settings'], $settings);
		foreach ($settings as $section => $state) {
			$view_array['sections-show-hide'][$section] = $state;
		}
	}
	if ( isset( $_POST['helpboxes'] ) ) {
		parse_str($_POST['helpboxes'], $help_settings);
		foreach ($help_settings as $section => $state) {
			$view_array['metasections-hep-show-hide'][$section] = $state;
		}
	}
	if ( isset( $_POST['purpose'] ) ) {
		$view_array['view_purpose'] = $_POST['purpose'];
	}
	update_post_meta($_POST["id"], '_wpv_settings', $view_array);
	echo $_POST["id"];
	die();
}

// Title and description save callback function
// @todo unify the check for existing title and name... we just need to check once!
// @todo review that filter_input_array thing...

add_action('wp_ajax_wpv_update_title_description', 'wpv_update_title_description_callback');

function wpv_update_title_description_callback() {
	global $wpdb;
	$nonce = $_POST["wpnonce"];
	if (! wp_verify_nonce($nonce, 'wpv_view_title_description_nonce') ) die("Security check");
	$view_desc = get_post_meta($_POST["id"], '_wpv_description', true);
	$view_title = get_the_title($_POST["id"]);
	$view_slug = basename( get_permalink( $_POST["id"] ) );
	$result = true;
	$return = $_POST["id"];
	$edit = 'WordPress Archive';
	if ( isset($_POST['edit']) ){
		$edit = $_POST['edit'];
	}
	if ( !isset( $_POST["title"] ) || empty( $_POST["title"] ) ) {
		print json_encode( array('error', __( 'You can not leave the title empty.', 'wpv-views' ) ) );
		die();
	}
	if ( !isset( $_POST["slug"] ) || empty( $_POST["slug"] ) ) {
		print json_encode( array('error', __( 'You can not leave the slug empty.', 'wpv-views' ) ) );
		die();
	}
	if ( $_POST["slug"] != sanitize_title( $_POST['slug'] ) ) {
		print json_encode( array('error', __( 'The slug can only contain lowercase letters, numbers or dashes.', 'wpv-views' ) ) );
		die();
	}
	$title_check = $wpdb->get_var( 
		$wpdb->prepare( 
			"SELECT ID FROM {$wpdb->posts} WHERE ( post_title = %s OR post_name = %s ) AND post_type = 'view' AND ID != %d LIMIT 1",
			$_POST["title"],
			$_POST["title"],
			$return
		)
	);
	if ( !empty($title_check)  ){
		print json_encode( array('error', sprintf( __( 'A %s with that name already exists. Please use another name.', 'wpv-views' ), $edit )) );
		die();
	}
	$name_check = $wpdb->get_var( 
		$wpdb->prepare( 
			"SELECT ID FROM {$wpdb->posts} WHERE ( post_title = %s OR post_name = %s ) AND post_type = 'view' AND ID != %d LIMIT 1",
			$_POST["slug"],
			$_POST["slug"],
			$return
		)
	);
	if ( !empty($name_check)  ){
		print json_encode( array('error', sprintf( __( 'A %s with that slug already exists. Please use another slug.', 'wpv-views' ), $edit )) );
		die();
	}
	$value = filter_input_array(INPUT_POST, array('description' => array('filter' => FILTER_SANITIZE_STRING, 'flags' => !FILTER_FLAG_STRIP_LOW)));
	if (!isset($view_desc) || $value['description'] != $view_desc) {
		$view_desc = $value['description'];
		$result = update_post_meta($_POST["id"], '_wpv_description', $view_desc);
	}
	if ($_POST["title"] != $view_title || $_POST["slug"] != $view_slug) {
		$view = array();
		$view['ID'] = $_POST["id"];
		$view['post_title'] = $_POST["title"];
		$view['post_name'] = $_POST["slug"];
		$return = wp_update_post( $view );
	}

	echo $result ? $return : false;
	die();
}

// Loop selection save callback function - only for WPA

add_action('wp_ajax_wpv_update_loop_selection', 'wpv_update_loop_selection_callback');

function wpv_update_loop_selection_callback() {
	$nonce = $_POST["wpnonce"];
	if (! wp_verify_nonce($nonce, 'wpv_view_loop_selection_nonce') ) die("Security check");
	global $WPV_view_archive_loop;
	parse_str($_POST['form'], $form_data);
	$WPV_view_archive_loop->update_view_archive_settings($_POST["id"], $form_data);
	$loop_form = '';
	ob_start();
	render_view_loop_selection_form( $_POST['id'] );
	$loop_form = ob_get_contents();
	ob_end_clean();
	$return_result['wpv_settings_archive_loops'] = $loop_form;
	$return_result['success'] = $_POST['id'];
	echo json_encode( $return_result );
	die();
}

// Query type save callback function - only for Views

add_action('wp_ajax_wpv_update_query_type', 'wpv_update_query_type_callback');

function wpv_update_query_type_callback() {
	$nonce = $_POST["wpnonce"];
	if (! wp_verify_nonce($nonce, 'wpv_view_query_type_nonce') ) die("Security check");
	$changed = false;
	$switched = false;
	$return_result = array();
	if (!isset($_POST["post_types"])) $_POST["post_types"] = array('any');
	$view_array = get_post_meta($_POST["id"],'_wpv_settings', true);
	if (isset($view_array['query_type']) && isset($view_array['query_type'][0]) && $view_array['query_type'][0] == $_POST["query_type"]) {
		
	} else {
		$view_array['query_type'] = array($_POST["query_type"]);
		$changed = true;
		$switched = true;
	}
	if (!isset($view_array['post_type']) || $view_array['post_type'] != $_POST["post_types"]) {
		$view_array['post_type'] = $_POST["post_types"];
		$changed = true;
	}
	if (!isset($view_array['taxonomy_type']) || $view_array['taxonomy_type'] != $_POST["taxonomies"]) {
		$view_array['taxonomy_type'] = $_POST["taxonomies"];
		$changed = true;
	}
	if (!isset($view_array['roles_type']) || $view_array['roles_type'] != $_POST["users"]) {
		$view_array['roles_type'] = $_POST["users"];
		$changed = true;
	}
	if ($changed) {
		$result = update_post_meta($_POST["id"], '_wpv_settings', $view_array);
	//	echo $result ? $_POST["id"] : false;
		$return_result['success'] = $result ? $_POST["id"] : false;
	} else {
	//	echo $_POST["id"];
		$return_result['success'] = $_POST['id'];
	}
	// Filters list
	if ( $switched ) {
		$filters_list = '';
		ob_start();
		wpv_display_filters_list( $view_array['query_type'][0], $view_array );
		$filters_list = ob_get_contents();
		ob_end_clean();
		$return_result['wpv_filter_update_filters_list'] = $filters_list;
	} else {
		$return_result['wpv_filter_update_filters_list'] = 'no_change';
	}
	// Flatten Types post relationship
	$returned_post_types = $view_array['post_type'];
	$multi_post_relations = wpv_recursive_post_hierarchy( $returned_post_types );
	$flatten_post_relations = wpv_recursive_flatten_post_relationships( $multi_post_relations );
	if ( strlen( $flatten_post_relations ) > 0 ) {
		$relations_tree = wpv_get_all_post_relationship_options( $flatten_post_relations );
		$return_result['wpv_update_flatten_types_relationship_tree'] = implode( ',', $relations_tree );
	} else {
		$return_result['wpv_update_flatten_types_relationship_tree'] = 'NONE';
	}
	echo json_encode( $return_result );
	die();
}

// Query options save callback function - only for Views

add_action('wp_ajax_wpv_update_query_options', 'wpv_update_query_options_callback');

function wpv_update_query_options_callback() {
	$nonce = $_POST["wpnonce"];
	if (! wp_verify_nonce($nonce, 'wpv_view_query_options_nonce') ) die("Security check");
	$changed = false;
	$view_array = get_post_meta($_POST["id"], '_wpv_settings', true);
	if (!isset($view_array['post_type_dont_include_current_page']) || $_POST["dont"] != $view_array['post_type_dont_include_current_page']) {
		$view_array['post_type_dont_include_current_page'] = $_POST["dont"];
		$changed = true;
	}
	if (!isset($view_array['taxonomy_hide_empty']) || $_POST["hide"] != $view_array['taxonomy_hide_empty']) {
		$view_array['taxonomy_hide_empty'] = $_POST["hide"];
		$changed = true;
	}
	if (!isset($view_array['taxonomy_include_non_empty_decendants']) || $_POST["empty"] != $view_array['taxonomy_include_non_empty_decendants']) {
		$view_array['taxonomy_include_non_empty_decendants'] = $_POST["empty"];
		$changed = true;
	}
	if (!isset($view_array['taxonomy_pad_counts']) || $_POST["pad"] != $view_array['taxonomy_pad_counts']) {
		$view_array['taxonomy_pad_counts'] = $_POST["pad"];
		$changed = true;
	}
	if (!isset($view_array['users-show-current']) || $_POST["uhide"] != $view_array['users-show-current']) {
		$view_array['users-show-current'] = $_POST["uhide"];
		$changed = true;
	}
	/*if (!isset($view_array['users-show-multisite']) || $_POST["smulti"] != $view_array['users-show-multisite']) {
		$view_array['users-show-multisite'] = $_POST["smulti"];
		$changed = true;
	}*/
	if ($changed) {
		$result = update_post_meta($_POST["id"], '_wpv_settings', $view_array);
		echo $result ? $_POST["id"] : false;
	} else {
		echo $_POST["id"];
	}
	die();
}

// Sorting save callback function - only for Views

add_action('wp_ajax_wpv_update_sorting', 'wpv_update_sorting_callback');

function wpv_update_sorting_callback() {
	$nonce = $_POST["wpnonce"];
	if (! wp_verify_nonce($nonce, 'wpv_view_ordering_nonce') ) die("Security check");
	$changed = false;
	$view_array = get_post_meta($_POST["id"], '_wpv_settings', true);
	if (!isset($view_array['orderby']) || $_POST["orderby"] != $view_array['orderby']) {
		$view_array['orderby'] = $_POST["orderby"];
		$changed = true;
	}
	if (!isset($view_array['order']) || $_POST["order"] != $view_array['order']) {
		$view_array['order'] = $_POST["order"];
		$changed = true;
	}
	if (!isset($view_array['taxonomy_orderby']) || $_POST["taxonomy_orderby"] != $view_array['taxonomy_orderby']) {
		$view_array['taxonomy_orderby'] = $_POST["taxonomy_orderby"];
		$changed = true;
	}
	if (!isset($view_array['taxonomy_order']) || $_POST["taxonomy_order"] != $view_array['taxonomy_order']) {
		$view_array['taxonomy_order'] = $_POST["taxonomy_order"];
		$changed = true;
	}
	if (!isset($view_array['users_orderby']) || $_POST["users_orderby"] != $view_array['users_orderby']) {
		$view_array['users_orderby'] = $_POST["users_orderby"];
		$changed = true;
	}
	if (!isset($view_array['users_order']) || $_POST["users_order"] != $view_array['users_order']) {
		$view_array['users_order'] = $_POST["users_order"];
		$changed = true;
	}
	if ($changed) {
		$result = update_post_meta($_POST["id"], '_wpv_settings', $view_array);
		echo $result ? $_POST["id"] : false;
	} else {
		echo $_POST["id"];
	}
	die();
}

// Limit and offset save callback function - only for Views

add_action('wp_ajax_wpv_update_limit_offset', 'wpv_update_limit_offset_callback');

function wpv_update_limit_offset_callback() {
	$nonce = $_POST["wpnonce"];
	if (! wp_verify_nonce($nonce, 'wpv_view_limit_offset_nonce') ) die("Security check");
	$changed = false;
	$view_array = get_post_meta($_POST["id"], '_wpv_settings', true);
	if (!isset($view_array['limit']) || $_POST["limit"] != $view_array['limit']) {
		$view_array['limit'] = $_POST["limit"];
		$changed = true;
	}
	if (!isset($view_array['offset']) || $_POST["offset"] != $view_array['offset']) {
		$view_array['offset'] = $_POST["offset"];
		$changed = true;
	}
	if (!isset($view_array['taxonomy_limit']) || $_POST["taxonomy_limit"] != $view_array['taxonomy_limit']) {
		$view_array['taxonomy_limit'] = $_POST["taxonomy_limit"];
		$changed = true;
	}
	if (!isset($view_array['taxonomy_offset']) || $_POST["taxonomy_offset"] != $view_array['taxonomy_offset']) {
		$view_array['taxonomy_offset'] = $_POST["taxonomy_offset"];
		$changed = true;
	}
    if (!isset($view_array['users_limit']) || $_POST["users_limit"] != $view_array['users_limit']) {
        $view_array['users_limit'] = $_POST["users_limit"];
        $changed = true;
    }
    if (!isset($view_array['users_offset']) || $_POST["users_offset"] != $view_array['users_offset']) {
        $view_array['users_offset'] = $_POST["users_offset"];
        $changed = true;
    }
	if ($changed) {
		$result = update_post_meta($_POST["id"], '_wpv_settings', $view_array);
		echo $result ? $_POST["id"] : false;
	} else {
		echo $_POST["id"];
	}
	die();
}

// Pagination save callback function - only for Views

add_action('wp_ajax_wpv_update_pagination', 'wpv_update_pagination_callback');

function wpv_update_pagination_callback() {
	$nonce = $_POST["wpnonce"];
	if (! wp_verify_nonce($nonce, 'wpv_view_pagination_nonce') ) die("Security check");
	$changed = false;
	parse_str($_POST['settings'], $settings);
	$defaults = array(
		'pagination' => array(
		'preload_images' => 0,
		'cache_pages' => 0,
		'preload_pages' => 0,
		),
		'rollover' => array(
		'preload_images' => 0,
		),
	);
	$settings = wpv_parse_args_recursive($settings, $defaults);
	$view_array = get_post_meta($_POST["id"], '_wpv_settings', true);
	if ( $view_array['posts_per_page'] != $settings['posts_per_page'] ) {
		$view_array['posts_per_page'] = $settings['posts_per_page'];
		$changed = true;
	}
	if ( $view_array['pagination'] != $settings['pagination'] ) {
		$view_array['pagination'] = $settings['pagination'];
		$changed = true;
	}
	if ( $view_array['ajax_pagination'] != $settings['ajax_pagination'] ) {
		$view_array['ajax_pagination'] = $settings['ajax_pagination'];
		$changed = true;
	}
	if ( $view_array['rollover'] != $settings['rollover'] ) {
		$view_array['rollover'] = $settings['rollover'];
		$changed = true;
	}
	if ($changed) {
		$result = update_post_meta($_POST["id"], '_wpv_settings', $view_array);
		echo $result ? $_POST["id"] : false;
	} else {
		echo $_POST["id"];
	}
	die();
}

// Filter Extra save callback function - only for Views

add_action('wp_ajax_wpv_update_filter_extra', 'wpv_update_filter_extra_callback');

function wpv_update_filter_extra_callback() {
	$nonce = $_POST["wpnonce"];
	if ( ! wp_verify_nonce( $nonce, 'wpv_view_filter_extra_nonce' ) ) {
		die( "Security check" );
	}
	$return_result = array();
	$view_array = get_post_meta( $_POST["id"], '_wpv_settings', true );
	if (
		! isset( $view_array['filter_meta_html'] ) 
		|| $_POST["query_val"] != $view_array['filter_meta_html']
	) {
		$view_array['filter_meta_html'] = $_POST["query_val"];
		wpv_add_controls_labels_to_translation( $_POST["query_val"], $_POST["id"] );
	}
	wpv_register_wpml_strings( $_POST["query_val"] );
	$view_array['filter_meta_html_css'] = $_POST["query_css_val"];
	$view_array['filter_meta_html_js'] = $_POST["query_js_val"];
	if ( isset( $view_array['filter_meta_html_state'] ) ) {
		unset( $view_array['filter_meta_html_state'] );
	}
	update_post_meta( $_POST["id"], '_wpv_settings', $view_array );
	$return_result['success'] = $_POST["id"];
	echo json_encode( $return_result );
	die();
}

add_action( 'wp_ajax_wpv_remove_filter_missing', 'wpv_remove_filter_missing_callback' );

function wpv_remove_filter_missing_callback() {
	$nonce = $_POST["nonce"];
	if (! wp_verify_nonce($nonce, 'wpv_view_filter_missing_delete') ) die("Security check");

	$view_array = get_post_meta( $_POST["id"], '_wpv_settings', true );
	if ( isset( $_POST['cf'] ) && is_array( $_POST['cf'] ) ) {
		foreach ( $_POST['cf'] as $field ) {
			$to_delete = array(
				'custom-field-' . $field . '_compare',
				'custom-field-' . $field . '_type',
				'custom-field-' . $field . '_value',
				'custom-field-' . $field . '_relationship'
			);
			foreach ($to_delete as $slug) {
				if ( isset( $view_array[$slug] ) ) {
					unset( $view_array[$slug] );
				}
			}
		}
	}
	if ( isset( $_POST['tax'] ) && is_array( $_POST['tax'] ) ) {
		foreach ( $_POST['tax'] as $tax_name ) {
			$to_delete = array(
					'tax_'.$tax_name.'_relationship' ,
					'taxonomy-'.$tax_name.'-attribute-url',
				//	'taxonomy-'.$tax_name.'-attribute-url-format',
				);
			foreach ($to_delete as $slug) {
				if ( isset( $view_array[$slug] ) ) {
					unset( $view_array[$slug] );
				}
			}
		}
	}
	if ( isset( $_POST['rel'] ) && is_array( $_POST['rel'] ) && !empty( $_POST['rel'] ) ) {
		$to_delete = array(
			'post_relationship_mode',
			'post_relationship_shortcode_attribute',
			'post_relationship_url_parameter',
			'post_relationship_id',
			'post_relationship_url_tree',
		);

		foreach ($to_delete as $slug) {
			if ( isset( $view_array[$slug] ) ) {
				unset( $view_array[$slug] );
			}
		}
	}
	if ( isset( $_POST['search'] ) && is_array( $_POST['search'] ) && !empty( $_POST['search'] ) ) {
		$to_delete = array(
			'search_mode',
			'post_search_value',
			'post_search_content',
		);

		foreach ($to_delete as $slug) {
			if ( isset( $view_array[$slug] ) ) {
				unset( $view_array[$slug] );
			}
		}
	}
	update_post_meta( $_POST["id"], '_wpv_settings', $view_array );
	$return_result = array();
	// Filters list
	$filters_list = '';
	ob_start();
	wpv_display_filters_list( $view_array['query_type'][0], $view_array );
	$filters_list = ob_get_contents();
	ob_end_clean();
	$return_result['wpv_filter_update_filters_list'] = $filters_list;
	$return_result['success'] = $_POST['id'];
	echo json_encode( $return_result );
	die();
}

// Layout Extra save callback function

add_action('wp_ajax_wpv_update_layout_extra', 'wpv_update_layout_extra_callback');

function wpv_update_layout_extra_callback() {
	$nonce = $_POST["wpnonce"];
	if ( ! wp_verify_nonce ( $nonce, 'wpv_view_layout_extra_nonce' ) ) {
		die( "Security check" );
	}
	
	// Get View settings and layout settings
	$view_array = get_post_meta($_POST["id"], '_wpv_settings', true);
    $view_layout_array = get_post_meta($_POST["id"], '_wpv_layout_settings', true);

    // Save the wizard settings
    if ( isset( $_POST['style'] ) ) {
        $view_layout_array['style'] = $_POST['style'];
        $view_layout_array['table_cols'] = $_POST['table_cols'];
		$view_layout_array['bootstrap_grid_cols'] = $_POST['bootstrap_grid_cols'];
		$view_layout_array['bootstrap_grid_container'] = $_POST['bootstrap_grid_container'];
		$view_layout_array['bootstrap_grid_row_class'] = $_POST['bootstrap_grid_row_class'];
		$view_layout_array['bootstrap_grid_individual'] = $_POST['bootstrap_grid_individual'];
        $view_layout_array['include_field_names'] = $_POST['include_field_names'];
        $view_layout_array['fields'] = $_POST['fields'];
        $view_layout_array['real_fields'] = $_POST['real_fields'];
        //Remove unused Content Template
        if ( 
			isset( $_POST['delete_view_loop_template'] ) 
			&& ! empty( $_POST['delete_view_loop_template'] ) 
		) {
            wp_delete_post( $_POST['delete_view_loop_template'], true );
            delete_post_meta( $_POST["id"], '_view_loop_template' );
            if ( isset( $view_layout_array['included_ct_ids'] ) ) {
                $reg_templates = array();
                $reg_templates = explode( ',', $view_layout_array['included_ct_ids'] );
                if ( in_array( $_POST['delete_view_loop_template'], $reg_templates ) ) {
                    $delete_key = array_search( $_POST['delete_view_loop_template'], $reg_templates );
					unset( $reg_templates[$delete_key] );
                    $view_layout_array['included_ct_ids'] = implode( ',', $reg_templates );
                }
            }
        }        
    }

	$view_layout_array['layout_meta_html'] = $_POST["layout_val"];
	wpv_register_wpml_strings( $_POST["layout_val"] );
	$view_array['layout_meta_html_css'] = $_POST["layout_css_val"];
	$view_array['layout_meta_html_js'] = $_POST["layout_js_val"];

	update_post_meta($_POST["id"], '_wpv_settings', $view_array);
	update_post_meta($_POST["id"], '_wpv_layout_settings', $view_layout_array);
	echo $_POST["id"];
	die();
}

// Layout Extra JS save callback function

add_action('wp_ajax_wpv_update_layout_extra_js', 'wpv_update_layout_extra_js_callback');

function wpv_update_layout_extra_js_callback() {
	$nonce = $_POST["wpnonce"];
	if (! wp_verify_nonce($nonce, 'wpv_view_layout_settings_extra_js_nonce') ) die("Security check");
	$view_array = get_post_meta($_POST["id"], '_wpv_layout_settings', true);
	if (isset($view_array['additional_js']) && $_POST["value"] == $view_array['additional_js']) {
		echo $_POST["id"];
		die();
	}
	$view_array['additional_js'] = $_POST["value"];
	$result = update_post_meta($_POST["id"], '_wpv_layout_settings', $view_array);
        echo $result ? $_POST["id"] : false;
        die();
}

// Content save callback function

add_action('wp_ajax_wpv_update_content', 'wpv_update_content_callback');

function wpv_update_content_callback() {
	$nonce = $_POST["wpnonce"];
	if (! wp_verify_nonce($nonce, 'wpv_view_content_nonce') ) die("Security check");
	$content_post = get_post($_POST["id"]);
	$content = $content_post->post_content;
	wpv_register_wpml_strings( $_POST["content"] );
	if ($_POST["content"] == $content) {
		echo $_POST["id"];
		die();
	}
	$this_post = array();
	$this_post['ID'] = $_POST["id"];
	$this_post['post_content'] = $_POST["content"];
	$result = wp_update_post( $this_post );
    echo $result ? $_POST["id"] : false;
    die();
}

/*
* Views listing screen
*/

/**
* wpv_create_view_callback
*
* View create callback function
*
* AJAX callback for the wpv_create_view action
*
* @param $_POST['wpnonce'] (string) 'wp_nonce_create_view'
* @param $_POST["title"] (string) (optional) Title for the View
* @param $_POST['kind'] (string) (optional) <normal> <archive>
* @param $_POST['purpose'] (string) (optional) <all> <pagination> <slider> <parametric> <full>
*
* @return (ID|JSON) New View ID on success or JSONed array('error'=>'error', 'error_message'=>'The error message') on fail
*
* @uses wpv_create_view
*
* @since 1.3.0
*/

add_action( 'wp_ajax_wpv_create_view', 'wpv_create_view_callback' );

function wpv_create_view_callback() {

	if ( ! wp_verify_nonce( $_POST["wpnonce"], 'wp_nonce_create_view' ) ) die("Security check");

	if ( !isset( $_POST["title"] ) || $_POST["title"] == '' ) $_POST["title"] = __('Unnamed View', 'wp-views');
    if ( !isset( $_POST["kind"] ) || $_POST["kind"] == '' ) $_POST["kind"] = 'normal';
    if ( !isset( $_POST["purpose"] ) || $_POST["purpose"] == '' ) $_POST["purpose"] = 'full';

    $args = array(
		'title' => $_POST["title"],
		'settings' => array(
			'view-query-mode' => $_POST["kind"],
			'view_purpose' => $_POST["purpose"]
		)
    );

    $response = wpv_create_view( $args );
    $result = array();

    if ( isset( $response['success'] ) ) {
		echo $response['success'];
    } else if ( isset( $response['error'] ) ) {
		$result['error'] = 'error';
		$result['error_message'] = $response['error'];
		echo json_encode( $result );
    } else {
		$result['error'] = 'error';
		$result['error_message'] = __('The View could not be created', 'wpv-views');
		echo json_encode( $result );
    }

	die();
}

// View Scan usage callback action

add_action('wp_ajax_wpv_scan_view', 'wpv_scan_view_callback');

function wpv_scan_view_callback() {
    global $wpdb, $sitepress;

    $nonce = $_POST["wpnonce"];
    if (! wp_verify_nonce($nonce, 'work_views_listing') ) die("Security check"); // @todo change this nonce

    $view = get_post($_POST["id"]);

    $list = ''; // @todo where the hell is this list used anymore?
    $list .= '<ul class="posts-list">';
    $needle = '%[wpv-view%name="%' . esc_sql($view->post_title). '%"]%';
    $needle = esc_sql( $needle );
    $needle_name = '%[wpv-view%name="%' . esc_sql($view->post_name). '%"]%';
    $needle_name = esc_sql( $needle_name );

    $trans_join = '';
    $trans_where = '';
    $trans_meta_where = '';

    if (isset($sitepress) && function_exists('icl_object_id')) {
	$current_lang_code = $sitepress->get_current_language();
	$trans_join = " JOIN {$wpdb->prefix}icl_translations t ";
	$trans_where = " AND ID = t.element_id AND t.language_code =  '{$current_lang_code}' ";
	$trans_meta_where = " AND post_id = t.element_id AND t.language_code =  '{$current_lang_code}' ";
    }

    $q = "SELECT DISTINCT * FROM {$wpdb->posts} WHERE
     ID in (SELECT DISTINCT ID FROM {$wpdb->posts} {$trans_join} WHERE ( post_content LIKE '{$needle}' OR post_content LIKE '{$needle_name}' ) AND post_type NOT IN ('revision') AND post_status='publish' {$trans_where})
     OR
     ID in (SELECT DISTINCT post_id FROM {$wpdb->postmeta} WHERE ( meta_value LIKE '{$needle}' OR meta_value LIKE '{$needle_name}' ) AND post_status='publish')";

    $res = $wpdb->get_results($q, OBJECT);

   if (!empty($res)) {
        $items = array();
        foreach ($res as $row) {
            $item = array();

            $type = get_post_type_object($row->post_type);

            $type = $type->labels->singular_name;

            $item['post_title'] = "<b>".$type.": </b>".$row->post_title;

            if ($row->post_type=='view')
                $edit_link = get_admin_url()."admin.php?page=views-editor&view_id=".$row->ID;
            else
                $edit_link = get_admin_url()."post.php?post=".$row->ID."&action=edit";

            $item['link'] = $edit_link;

            $items[] = $item;
        }
        echo json_encode($items);
    }


    die();
}

// View duplicate callback function
// @todo when duplicating, adjust the loop Template!!

add_action('wp_ajax_wpv_duplicate_this_view', 'wpv_duplicate_this_view_callback');

function wpv_duplicate_this_view_callback() {
	if (
		! isset( $_POST["wpnonce"] ) 
		|| ! wp_verify_nonce( $_POST["wpnonce"], 'wpv_duplicate_view_nonce' ) 
	) {
		die( "Security check" );
	}
	global $wpdb;
	$existing = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT ID FROM {$wpdb->posts} WHERE ( post_title = %s OR post_name = %s ) AND post_type = 'view' LIMIT 1",
			$_POST["name"],
			$_POST["name"]
		)
	);
	if ( $existing ) {
		echo 'error';
		die;
	}
	// Sanitize new View title
	$new_post_title = sanitize_text_field( $_POST["name"] );
	// Clone existing View post
	$old_post_id = $_POST["id"];
	$original_post = get_post( $old_post_id, ARRAY_A );
	$original_post['post_title'] = $new_post_title;
	unset( $original_post['ID'] );
	unset( $original_post['post_name'] );
	unset( $original_post['post_date'] );
	unset( $original_post['post_date_gmt'] );
	$new_post_id = wp_insert_post( $original_post );
	// Clone existing View postmeta
	$original_postmeta_keys = array(
		'_wpv_settings', '_wpv_layout_settings', '_wpv_description'
	);
	$original_postmeta_values = array();
	foreach ( $original_postmeta_keys as $original_postmeta_k ) {
		$original_postmeta_values[$original_postmeta_k] = get_post_meta( $old_post_id, $original_postmeta_k, true );
	}
	$has_loop_template = get_post_meta( $old_post_id, '_view_loop_template', true );
	if ( ! empty( $has_loop_template ) ) {
		// This View has a loop Template! We need to clone it and adjust the layout settings
		$clone_args = array(
			'title' => sprintf( __( 'Loop item in %s', 'wpv-views' ), $new_post_title ),
			'force' => true
		);
		$clone = wpv_clone_content_template( $has_loop_template, $clone_args );
		if ( isset ( $clone['success'] ) ) {
			update_post_meta( $new_post_id, '_view_loop_template', $clone['success'] );
			update_post_meta( $clone['success'], '_view_loop_id', $new_post_id );
			if ( isset( $original_postmeta_values['_wpv_layout_settings']['included_ct_ids'] ) ) {
				$inline_templates = explode( ',', $original_postmeta_values['_wpv_layout_settings']['included_ct_ids'] );
				foreach ( $inline_templates as $it_key => $inline_template_id ) {
					if ( $inline_template_id == $has_loop_template ) {
						$inline_templates[$it_key] = $clone['success'];
					}
				}
				$original_postmeta_values['_wpv_layout_settings']['included_ct_ids'] = implode( ',', $inline_templates );
			}
			if ( isset( $original_postmeta_values['_wpv_layout_settings']['layout_meta_html'] ) ) {
				$original_loop_template_title = get_the_title( $has_loop_template );
				$original_postmeta_values['_wpv_layout_settings']['layout_meta_html'] = str_replace(
					'view_template="' . $original_loop_template_title . '"',
					'view_template="' . $clone['title'] . '"',
					$original_postmeta_values['_wpv_layout_settings']['layout_meta_html']
				);
			}
		}
	}
	foreach ( $original_postmeta_keys as $original_postmeta_k ) {
		update_post_meta( $new_post_id, $original_postmeta_k, $original_postmeta_values[$original_postmeta_k] );
	}
	echo $_POST["id"];
	die();
}

/* ************************************************************************** *\
		WP Archive listing screen
\* ************************************************************************** */


// Add up, down or first WP Archive - popup structure

add_action('wp_ajax_wpv_create_wp_archive_button', 'wpv_create_wp_archive_button_callback');

function wpv_create_wp_archive_button_callback() {

	if (! wp_verify_nonce($_POST["wpnonce"], 'work_views_listing') ) die("Security check");

        global $WPV_view_archive_loop;
        echo $WPV_view_archive_loop->_create_view_archive_popup();
        die();
}


// Add up, down or first WP Archive callback function
// Uses the same callback as in the usage arrange mode

add_action('wp_ajax_wpv_create_archive_view', 'wp_ajax_wpv_create_usage_archive_view_callback');

// Change usage for WP Archive in name arrange - popup structure

add_action('wp_ajax_wpv_archive_change_usage_popup', 'wpv_archive_change_usage_popup_callback');

function wpv_archive_change_usage_popup_callback() {
    if (! wp_verify_nonce($_POST["wpnonce"], 'work_views_listing') ) die("Security check");

        global $WPV_view_archive_loop;

        $id = $_POST["id"];

        echo $WPV_view_archive_loop->_create_view_archive_popup($id);
        die();
}

// Change usage for Archive in name arrange callback function

add_action('wp_ajax_wpv_archive_change_usage', 'wpv_archive_change_usage_callback');

function wpv_archive_change_usage_callback() {
	global $wpdb;
	$nonce = $_POST["wpnonce"];
	if (! wp_verify_nonce($nonce, 'work_views_listing') ) die("Security check");

        global $WPV_view_archive_loop;
        parse_str($_POST['form'], $form_data);

	$archive_id = $form_data["wpv-archive-view-id"];

	$WPV_view_archive_loop->update_view_archive_settings($archive_id, $form_data);
	echo 'ok';
	die();
}




// Create WP Archive in usage arrange - popup structure

add_action('wp_ajax_wpv_create_usage_archive_view_popup', 'wpv_create_usage_archive_view_popup_callback');

function wpv_create_usage_archive_view_popup_callback() {
	$nonce = $_POST["wpnonce"];
	if ( ! wp_verify_nonce( $nonce, 'wpv_wp_archive_arrange_usage' ) ) {
		die( "Security check" );
	}
    global $WPV_view_archive_loop, $WP_Views;
    $options = $WP_Views->get_options();
    $loops = $WPV_view_archive_loop->_get_post_type_loops();
        ?>
        <div class="wpv-dialog wpv-dialog-change js-wpv-dialog-change">
                <div class="wpv-dialog-header">
                    <h2><?php _e('Name of WordPress Archive for','wpv-views'); ?> <strong><?php echo $_POST['for_whom']; ?></strong></h2>
                    <i class="icon-remove js-dialog-close"></i>
                </div>
		<form id="wpv-add-wp-archive-for-loop-form">
                <div class="wpv-dialog-content">
		<div class="hidden">
                    <?php
                        foreach($loops as $loop => $loop_name) {
                            foreach ($options as $opt_id=> $opt_name) {
				                if ('view_'.$loop == $opt_id && $opt_name !== 0) {
                                    unset($loops[$loop]);
                                    break;
                                }
                            }
                        }
                    ?>

                    <?php if (!empty($loops)) {  ?>
                        <?php foreach($loops as $loop => $loop_name) { ?>
                            <?php $checked = ( $loop_name == $_POST['for_whom'] ) ? ' checked="checked"' : ''; ?>
                                <input type="checkbox" <?php echo $checked; ?> name="wpv-view-loop-<?php echo $loop; ?>" />
                        <?php }; ?>
                    <?php } ?>

                    <?php
                    $taxonomies = get_taxonomies('', 'objects');
                    $exclude_tax_slugs = array();
			$exclude_tax_slugs = apply_filters( 'wpv_admin_exclude_tax_slugs', $exclude_tax_slugs );
                        foreach ($taxonomies as $category_slug => $category) {
                           if ( in_array( $category_slug, $exclude_tax_slugs ) ) {
                                    unset($taxonomies[$category_slug]);
                                    continue;
                            };
                            foreach ($options as $opt_id=> $opt_name) {
				if ('view_taxonomy_loop_' . $category_slug == $opt_id && $opt_name !== 0) {
                                    unset($taxonomies[$category_slug]);
                                    break;
                                };
                            };
                        };
                    ?>

                    <?php if (!empty($taxonomies)): ?>
                        <?php foreach ($taxonomies as $category_slug => $category): ?>
                            <?php
                                $name = $category->name;
                                $checked = ( $category->labels->name == $_POST['for_whom'] ) ? ' checked="checked"' : '';
                            ?>
                            <input type="checkbox" <?php echo $checked; ?> name="wpv-view-taxonomy-loop-<?php echo $name; ?>" />
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </div>

                    <p>
                        <input type="text" value="" class="js-wpv-new-archive-name wpv-new-archive-name" placeholder="<?php echo htmlentities( __('WordPress Archive name','wpv-views'), ENT_QUOTES ) ?>" name="wpv-new-archive-name">
                    </p>
                    <div class="js-wp-archive-create-error"></div>
                </div>
		</form>
                <div class="wpv-dialog-footer">
                    <button class="button-secondary js-dialog-close" type="button" name="wpv-archive-view-cancel"><?php _e('Cancel', 'wpv-views'); ?></button>
                    <button class="button-secondary js-wpv-add-wp-archive-for-loop" disabled="disabled" name="wpv-archive-view-ok" data-error="<?php echo htmlentities( __('A WordPress Archive with that name already exists. Please use another name.', 'wpv-views'), ENT_QUOTES ); ?>" data-url="<?php echo admin_url( 'admin.php?page=view-archives-editor&amp;view_id='); ?>">
                        <?php _e('Add new WordPress Archive', 'wpv-views'); ?>
                    </button>
                </div>
        </div>
    <?php die();
}

// Create WP Archive in usage arrange callback function
// @todo we need to use the API to create this, or at least *create* that API if needed

add_action('wp_ajax_wpv_create_usage_archive_view', 'wp_ajax_wpv_create_usage_archive_view_callback');

function wp_ajax_wpv_create_usage_archive_view_callback() {
	$nonce = $_POST["wpnonce"];
	if ( ! wp_verify_nonce( $nonce, 'work_views_listing' ) ) {
		die( "Security check" );
	}

	global $wpdb, $WPV_view_archive_loop;
	parse_str($_POST['form'], $form_data);

	// Create archive
	$existing = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT ID FROM {$wpdb->posts} WHERE ( post_title = %s OR post_name = %s ) AND post_type = 'view' LIMIT 1",
			$form_data["wpv-new-archive-name"],
			$form_data["wpv-new-archive-name"]
		)
	);
	if ( $existing ) {
		echo 'error';
		die();
	}
	$new_archive = array(
		'post_title'    => $form_data["wpv-new-archive-name"],
		'post_type'      => 'view',
		'post_content'  => "[wpv-layout-meta-html]",
		'post_status'   => 'publish',
		'post_author'   => get_current_user_id(),
		'comment_status' => 'closed'
	);
	$post_id = wp_insert_post($new_archive);

	$archive_defaults = wpv_wordpress_archives_defaults('view_settings');
	$archive_layout_defaults = wpv_wordpress_archives_defaults('view_layout_settings');
	update_post_meta($post_id, '_wpv_settings', $archive_defaults);
	update_post_meta($post_id, '_wpv_layout_settings', $archive_layout_defaults);

	$WPV_view_archive_loop->update_view_archive_settings($post_id, $form_data);

	echo $post_id;
	die();
}


/**
 * Render a popup to change WordPress Archive usage.
 *
 * Used in WordPress Archive listing, arranged by usage. When user accepts the change, a click event on
 * '.js-update-archive-for-loop' is raised.
 *
 * Expects following POST arguments:
 * - wpnonce: A valid wpv_wp_archive_arrange_usage nonce.
 * - id: Slug of the loop whose WPA should be changed.
 *
 * @since unknown.
 */ 
add_action('wp_ajax_wpv_show_views_for_loop', 'wpv_show_views_for_loop_callback');

function wpv_show_views_for_loop_callback() {
	global $WPV_view_archive_loop, $wpdb, $WP_Views;
	
	if ( ! wp_verify_nonce( $_POST["wpnonce"], 'wpv_wp_archive_arrange_usage' ) ) {
		die( "Security check" );
	}

	// Slug of the loop.
	$loop_key = $_POST["id"];
	
    $options = $WP_Views->get_options();
    $loops = $WPV_view_archive_loop->_get_post_type_loops();

	?>
        <div class="wpv-dialog wpv-dialog-change js-wpv-dialog-change">
            <form id="wpv-archive-view-form-for-loop">
                <div class="wpv-dialog-header">
                    <h2><?php _e( 'Select WordPress Archive For Loop', 'wpv-views' ); ?></h2>
                    <i class="icon-remove js-dialog-close"></i>
                </div>
                <div class="wpv-dialog-content">
					<h3><?php _e( 'Archive views', 'wpv-views' ); ?></h3>
                    <?php wp_nonce_field( 'wpv_view_edit_nonce', 'wpv_view_edit_nonce' ); ?>
                    <input type="hidden" value="<?php echo $loop_key; ?>" name="wpv-archive-loop-key" />

                    <?php
						/* We will slightly misuse this function, but it gives us exactly what we need:
						 * a list of published WPAs. */
						$views_pre_query_data = wpv_prepare_view_listing_query(
								array( 'archive', 'layouts-loop' ),
								'publish',
								array( 'posts.post_title' => 'post_title' ), // also give us post title
								true, // return rows from the table
								array( "posts.post_status = 'publish'" ) ); // limit mysql query only to published posts
						$views = $views_pre_query_data['rows'];

                        // ID of currently assigned view or 0.
                        $currently_assigned_view_id = isset( $options[ $loop_key ] ) ? $options[ $loop_key ] : 0;                            
					?>
					<ul>
						<li>
							<label>
								<input type="radio" name="wpv-view-loop-archive" value="0" <?php checked( 0 == $currently_assigned_view_id ); ?> />
								<?php _e( 'Don\'t use a WordPress Archive for this loop', 'wpv-views' ); ?>
							</label>
						</li>
						<?php
							foreach ( $views as $view ) {
								?>
								<li>
									<label>
										<input type="radio" <?php checked( $view->id == $currently_assigned_view_id ); ?>
												name="wpv-view-loop-archive" value="<?php echo $view->id; ?>" />
										<?php echo $view->post_title; ?>
									</label>
								</li>
								<?php
							}
						?>
					</ul>
                </div>
                <div class="wpv-dialog-footer">
                    <button class="button-secondary js-dialog-close" type="button" name="wpv-archive-view-cancel"><?php _e( 'Cancel', 'wpv-views' ); ?></button>
                    <button class="button-primary js-update-archive-for-loop" type="button" name="wpv-archive-view-ok">
                        <?php _e( 'Accept', 'wpv-views' ); ?>
                    </button>
                </div>
            </form>
        </div>
	<?php
	
    die();
}

// Change WP Archive usage in usage arrange callback function

add_action('wp_ajax_wpv_update_archive_for_view', 'wpv_update_archive_for_view_callback');

function wpv_update_archive_for_view_callback() {
	global $WP_Views;
//	global $WPV_view_archive_loop;
	if (! wp_verify_nonce($_POST["wpnonce"], 'wpv_wp_archive_arrange_usage') ) die("Security check");

	$options = $WP_Views->get_options();

	$options[$_POST["loop"]] = $_POST["selected"];
	foreach($options as $key => $value) {
		if ($value == 0) unset($options[$key]);
	}

	$WP_Views->save_options( $options );

	echo 'ok';
	die();
}

// Delete Views and WPA permanently callback function
// @note it also deletes the loop Template if needed
// TODO add different nonces for Views and for WPA

add_action('wp_ajax_wpv_delete_view_permanent', 'wpv_delete_view_permanent_callback');

function wpv_delete_view_permanent_callback() {
    global $WP_Views;
	$nonce = $_POST["wpnonce"];
	if ( ! wp_verify_nonce( $nonce, 'wpv_remove_view_permanent_nonce' ) ) {
			die("Security check");
	}
	$loop_content_template = get_post_meta( $_POST["id"], '_view_loop_template', true );
	wp_delete_post( $_POST["id"] );
	if ( ! empty( $loop_content_template ) ) {
		wp_delete_post( $loop_content_template, true );
	}
	// Clean options - when deleting WPA
	$options = $WP_Views->get_options();
	WP_Views_archive_loops::clear_options_data( $options );
	$WP_Views->save_options( $options );
	echo $_POST["id"];
	die();
}

// Change status of View and WPA callback function TODO use a more generic function name

add_action('wp_ajax_wpv_view_change_status', 'wpv_view_change_status_callback');

function wpv_view_change_status_callback(){
	$nonce = $_POST["wpnonce"];
	if ( ! (
		wp_verify_nonce($nonce, 'wpv_view_listing_actions_nonce') || // from the Views listing screen OR
		wp_verify_nonce($nonce, 'wpv_view_change_status') // from the View edit screen
	) ) die("Security check");

	if ( !isset( $_POST['newstatus'] ) ) $_POST['newstatus'] = 'publish';
	$my_post = array(
		'ID'           => $_POST["id"],
		'post_status' => $_POST['newstatus']
	);

	$return = wp_update_post( $my_post );
	if ( isset( $_POST['cleararchives'] ) ) {
		$options = get_option( 'wpv_options' );
		if ( !empty( $options ) ) {
			foreach ( $options as $option_name => $option_value ) {
				if ( strpos( $option_name, 'view_' ) === 0  && $option_value == $_POST["id"] ) {
					$options[$option_name] = 0;
				}
			}
			update_option( 'wpv_options', $options );
		}
	}
	echo $return;
	die();
}


/**
 * Change status of multiple Views, WordPress Archives or Content Templates. Callback function.
 *
 * Following POST parameters are expected:
 * 
 * - wpnonce: A valid wpv_view_listing_actions_nonce.
 * - newstatus: New status for posts that should be updated. Default is 'publish'.
 * - ids: An array of post IDs that should be updated. Single (string or numeric) value is also accepted.
 * - cleararchives: If set to 1, assignment of givent posts (WPAs) in archive loops will be cleared.
 * 
 * Outputs '0' on failure (when one or more posts couldn't be updated) and '1' on success.
 *
 * @since 1.7
 */ 
add_action( 'wp_ajax_wpv_view_bulk_change_status', 'wpv_view_bulk_change_status_callback' );

function wpv_view_bulk_change_status_callback() {
	$nonce = $_POST["wpnonce"];
	if ( !wp_verify_nonce( $nonce, 'wpv_view_listing_actions_nonce' ) ) {
		die( "Security check" );
	}

	$new_status = isset( $_POST['newstatus'] ) ? $_POST['newstatus'] : 'publish';
	if( !isset( $_POST['ids'] ) ) {
		$post_ids = array();
	} else if( is_string( $_POST['ids'] ) ) {
		$post_ids = array( $_POST['ids'] );
	} else {
		$post_ids = $_POST['ids'];
	}

	// Update post statuses
	$is_failure = false;
	foreach( $post_ids as $post_id ) {
		$my_post = array(
				'ID' => $post_id,
				'post_status' => $new_status );
		$res = wp_update_post( $my_post );
		$is_failure = $is_failure || ( $res == 0 );
	}

	// Clear archive loop assignment, if requested
	if ( isset( $_POST['cleararchives'] ) && ( 1 == $_POST['cleararchives'] ) ) {
		$options = get_option( 'wpv_options' );
		if ( !empty( $options ) ) {
			foreach ( $options as $option_name => $option_value ) {
				if ( ( strpos( $option_name, 'view_' ) === 0 ) && in_array( $option_value, $post_ids ) ) {
					$options[ $option_name ] = 0;
				}
			}
			update_option( 'wpv_options', $options );
		}
	}

	
	echo $is_failure ? 0 : 1;
	die();
}


/**
 * Render a colorbox popup to confirm bulk Views deleting.
 *
 * This is called by deleteViewsConfirmation() in views_listing_page.js. The Popup is identified by class
 * "js-bulk-delete-views-dialog". It also contains a table with Views to be deleted and buttons to scan for their usage.
 *
 * Delete button (js-bulk-remove-view-permanent) contains data attributes "view-ids" with comma-separated list of
 * View IDs and "nonce" with a wpv_bulk_remove_view_permanent_nonce.
 *
 * @since 1.7
 */ 
add_action( 'wp_ajax_wpv_view_bulk_delete_render_popup', 'wpv_view_bulk_delete_render_popup_callback' );

function wpv_view_bulk_delete_render_popup_callback() {

	$nonce = $_POST["wpnonce"];
	if ( !wp_verify_nonce( $nonce, 'wpv_view_listing_actions_nonce' ) ) {
		die( "Security check" );
	}

	if( !isset( $_POST['ids'] ) ) {
		$post_ids = array();
	} else if( is_string( $_POST['ids'] ) ) {
		$post_ids = array( $_POST['ids'] );
	} else {
		$post_ids = $_POST['ids'];
	}

	// We only get IDs and titles
	global $wpdb;
	if( !empty( $post_ids ) ) {
		$post_id_list = implode( ', ', $post_ids );
		$views = $wpdb->get_results(
				"SELECT ID as id, post_title FROM {$wpdb->posts} WHERE post_type = 'view' AND id IN ( $post_id_list )" );
	} else {
		$views = array(); // This should never happen.
	}

	$view_count = count( $views );
	
	?>
		<div class="wpv-dialog js-bulk-delete-views-dialog">
			<div class="wpv-dialog-header">
				<h2><?php _e( 'Delete Views', 'wpv-views' ) ?></h2>
			</div>
			<div class="wpv-dialog-content">
				<p>
					<?php
						echo _n(
								'Are you sure you want to delete this View?',
								'Are you sure you want delete these Views?',
								$view_count,
								'wpv-views' );
					?>
				</p>
				<p>
					<?php
						echo _n(
								'Please use the Scan button first to be sure that it is not used anywhere.',
								'Please use Scan buttons first to be sure that they are not used anywhere.',
								$view_count,
								'wpv-views' );
					?>
				</p>
				<table class="wpv-view-table" style="width: 100%;">
					<?php
						foreach( $views as $view ) {
							?>
							<tr>
								<td><strong><?php echo $view->post_title; ?></strong></td>
								<td class="wpv-admin-listing-col-scan">
									<button class="button js-scan-button" data-view-id="<?php echo $view->id; ?>">
										<?php _e( 'Scan', 'wp-views' ); ?>
									</button>
									<span class="js-nothing-message hidden"><?php _e( 'Nothing found', 'wpv-views' ); ?></span>
								</td>
							</tr>
							<?php
						}
					?>
				</table>
			</div>
			<div class="wpv-dialog-footer">
				<button class="button js-dialog-close">
					<?php _e( 'Cancel','wpv-views' ); ?>
				</button>
				<button class="button button-primary js-bulk-remove-view-permanent"
						data-nonce="<?php echo wp_create_nonce( 'wpv_bulk_remove_view_permanent_nonce' ); ?>"
						data-view-ids="<?php echo urlencode( implode( ',', $post_ids ) ); ?>">
					<?php
						echo _n( 'Delete', 'Delete all', $view_count, 'wpv-views' );
					?>
				</button>
			</div>
		</div> <!-- .js-bulk-delete-views-dialog -->
	<?php

	die();
}



/**
 * Render a colorbox popup to confirm bulk WordPress Archives deleting.
 *
 * This is called by deleteArchivesConfirmation() in views_wordpress_archive_listing_page.js. The Popup is identified by class
 * "js-bulk-delete-archives-dialog".
 *
 * Delete button (js-bulk-remove-archives-permanent) contains data attributes "archive-ids" with comma-separated list of
 * WPA IDs and "nonce" with a wpv_bulk_remove_view_permanent_nonce.
 *
 * @since 1.7
 */ 
add_action( 'wp_ajax_wpv_archive_bulk_delete_render_popup', 'wpv_archive_bulk_delete_render_popup_callback' );

function wpv_archive_bulk_delete_render_popup_callback() {

	$nonce = $_POST["wpnonce"];
	if ( !wp_verify_nonce( $nonce, 'wpv_view_listing_actions_nonce' ) ) {
		die( "Security check" );
	}

	if( !isset( $_POST['ids'] ) ) {
		$post_ids = array();
	} else if( is_string( $_POST['ids'] ) ) {
		$post_ids = array( $_POST['ids'] );
	} else {
		$post_ids = $_POST['ids'];
	}
		
	?>
		<div class="wpv-dialog js-bulk-delete-archives-dialog">
			<div class="wpv-dialog-header">
				<h2><?php _e( 'Delete Archives', 'wpv-views' ) ?></h2>
			</div>
			<div class="wpv-dialog-content">
				<p><?php _e( 'Are you sure you want to delete selected Archives? ','wpv-views' ); ?></p>
			</div>
			<div class="wpv-dialog-footer">
				<button class="button js-dialog-close"><?php _e( 'Cancel','wpv-views' ); ?></button>
				<button class="button button-primary js-bulk-remove-archives-permanent"
						data-nonce="<?php echo wp_create_nonce( 'wpv_bulk_remove_view_permanent_nonce' ); ?>"
						data-archive-ids="<?php echo urlencode( implode( ',', $post_ids ) ); ?>">
					<?php _e( 'Delete all','wpv-views' ); ?>
				</button>
			</div>
		</div> <!-- .js-bulk-delete-archives-dialog -->
	<?php

	die();
}



/**
 * Permanently delete multiple Views or WordPress Archives. Callback function.
 *
 * Needs a wpv_bulk_remove_view_permanent_nonce to be present.
 * Also deletes associated loop Templates if any
 * 
 * Outputs 0 on failure (when one or more posts couldn't be deleted) and 1 on success.
 *
 * $since 1.7
 */ 
add_action( 'wp_ajax_wpv_bulk_delete_views_permanent', 'wpv_bulk_delete_views_permanent_callback' );

function wpv_bulk_delete_views_permanent_callback() {
	global $wpdb;
	$nonce = $_POST["wpnonce"];
	if ( ! wp_verify_nonce( $nonce, 'wpv_bulk_remove_view_permanent_nonce' ) ) {
		die( "Security check" );
	}
	if ( ! isset( $_POST['ids'] ) ) {
		$post_ids = array();
	} else if ( is_string( $_POST['ids'] ) ) {
		$post_ids = array( $_POST['ids'] );
	} else {
		$post_ids = $_POST['ids'];
	}

	$is_failure = false;
	// Delete loop Templates if any
	if ( count( $post_ids ) > 0 ) {
		$remove_loop_templates = " AND post_id IN ('" . implode( "','" , $post_ids ) . "') ";
		$remove_loop_templates_ids = $wpdb->get_col( "SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key='_view_loop_template' {$remove_loop_templates}" );
		foreach ( $remove_loop_templates_ids as $remove_template ) {
			wp_delete_post( $remove_template, true );
		}
	}
	foreach ( $post_ids as $post_id ) {
		$res = wp_delete_post( $post_id );
		$is_failure = $is_failure || ( $res == false );
	}

	// Clean options - when deleting WPA
	global $WPV_view_archive_loop, $WP_Views;
    $options = $WP_Views->get_options();
    WP_Views_archive_loops::clear_options_data($options);
    $WP_Views->save_options($options);

	echo $is_failure ? 0 : 1;
	die();
}


/**
 * Find out which WordPress Archives are used in some loops.
 *
 * For given WPA IDs output those who are used in archive loops. If there are any, also
 * generate HTML for the colorbox popup - confirmation to trash them.
 *
 * Following POST parameters are expected:
 * - wpnonce: Valid wpv_view_listing_actions_nonce.
 * - ids: An array of WPA IDs that should be checked.
 *
 * Output is a JSON representation of an array with following elements:
 * - used_wpa_ids: An array of IDs of WPAs in use.
 * - colorbox_html: If used_wpa_ids is non-empty, this contains HTML for the colorbox popup.
 *     When user confirms it, *all* of the WPAs will be trashed (not only those from used_wpa_ids).
 *     Otherwise it is an empty string.
 *
 * @since 1.7
 */ 
add_action( 'wp_ajax_wpv_archive_check_usage', 'wpv_archive_check_usage_callback' );

function wpv_archive_check_usage_callback() {

	$nonce = $_POST["wpnonce"];
	if ( !wp_verify_nonce( $nonce, 'wpv_view_listing_actions_nonce' ) ) {
		die( "Security check" );
	}

	if( !isset( $_POST['ids'] ) ) {
		$post_ids = array();
	} else if( is_string( $_POST['ids'] ) ) {
		$post_ids = array( $_POST['ids'] );
	} else {
		$post_ids = $_POST['ids'];
	}

	global $WP_Views, $WPV_view_archive_loop;
	$options = $WP_Views->get_options();
	$loops = $WPV_view_archive_loop->_get_post_type_loops();
	$taxonomies = get_taxonomies( '', 'objects' );
	$exclude_tax_slugs = array();
	$exclude_tax_slugs = apply_filters( 'wpv_admin_exclude_tax_slugs', $exclude_tax_slugs );

	// This will hold IDs of used archives.
	$archive_ids_in_use = array();

	// Check for usage in loops
	foreach ( $loops as $loop => $loop_name ) {
		if ( isset( $options[ 'view_' . $loop ] )
			&& in_array( $options[ 'view_' . $loop ], $post_ids ) )
		{
			$used_archive_id = $options[ 'view_' . $loop ];
			// Use post id for both key and value to ensure it will be present only once as value.
			$archive_ids_in_use[ $used_archive_id ] = $used_archive_id;
		}
	}

	// Check for usage in taxonomies
	foreach ( $taxonomies as $category_slug => $category ) {
		if ( in_array( $category_slug, $exclude_tax_slugs ) ) {
			continue;
		}
		// Only show taxonomies with show_ui set to TRUE
		if ( !$category->show_ui ) {
			continue;
		}
		$name = $category->name;
		if ( isset ( $options[ 'view_taxonomy_loop_' . $name ] )
			&& in_array( $options[ 'view_taxonomy_loop_' . $name ], $post_ids ) )
		{
			$used_archive_id = $options[ 'view_taxonomy_loop_' . $name ];
			$archive_ids_in_use[ $used_archive_id ] = $used_archive_id;
		}
	}

	// If there are some used archives, generate html for the colorbox confirmation popup
	if( !empty( $archive_ids_in_use ) ) {

		// We only get IDs and titles
		global $wpdb;
		$used_archive_id_list = implode( ', ', $archive_ids_in_use );
		$used_archives = $wpdb->get_results(
				"SELECT ID as id, post_title FROM {$wpdb->posts} WHERE post_type = 'view' AND id IN ( $used_archive_id_list )" );

		$used_archive_count = count( $archive_ids_in_use );
		
		ob_start();

		?>
		<div class="wpv-dialog js-bulk-trash-archives-dialog">
			<div class="wpv-dialog-header">
				<h2><?php _e( 'Trash WordPress Archives', 'wpv-views' ) ?></h2>
			</div>
			<div class="wpv-dialog-content">
				<p>
					<?php
						echo _n(
								'Are you sure you want to trash this WordPress Archive?',
								'Are you sure you want to trash these WordPress Archives?',
								$used_archive_count,
								'wpv-views' );
					?>
				</p>
				<p>
					<?php
						echo _n(
								'It is assigned to one or more archive or taxonomy loops.',
								'Some of them are assigned to archive or taxonomy loops.',
								$used_archive_count,
								'wpv-views' );
						echo '<br />';
						echo _n(
								'Trashing it will also unassign it.',
								'Trashing them will also unassign them.',
								$used_archive_count,
								'wpv-views' );
					?>
				</p>
				<ul style="list-style-type: disc; padding-left: 40px;">
					<?php
						foreach( $used_archives as $archive ) {
							?>
								<li><strong><?php echo $archive->post_title; ?></strong></li>
							<?php
						}
					?>
				</ul>
			</div>
			<div class="wpv-dialog-footer">
				<button class="button js-dialog-close">
					<?php _e( 'Cancel', 'wpv-views' ); ?>
				</button>
				<button class="button button-primary js-bulk-trash-archives-confirm"
						data-nonce="<?php echo $nonce; ?>"
						data-archive-ids="<?php echo urlencode( implode( ',', $post_ids ) ); ?>">
					<?php
						echo _n(
								'Trash',
								'Trash all',
								$used_archive_count,
								'wpv-views' );
					?>
				</button>
			</div>
		</div> <!-- .js-bulk-trash-archives-dialog -->
		<?php
		
		$colorbox_html = ob_get_contents();
		ob_end_clean();
	
	} else {
		$colorbox_html = '';
	}
	

	$response = array(
			'used_wpa_ids' => $archive_ids_in_use,
			'colorbox_html' => $colorbox_html );

	echo json_encode( $response );
	die();
}


/* ********************************************************************************\
 * Content Templates
\* ********************************************************************************/

/**
* wpv_ct_update_posts_callback
*
* Callback function for the AJAX action wp_ajax_wpv_ct_update_posts used to count dissident posts that are not using the Template assigned to its type
* This is called on the Content Templates listing screen for single usage and on the Template edit screen
*
* Added by Gen TODO check this nonce
*
* @since 1.3.0
*
* @uses wpv_count_dissident_posts_from_template
*/

add_action('wp_ajax_wpv_ct_update_posts', 'wpv_ct_update_posts_callback');

function wpv_ct_update_posts_callback(){
    if ( !isset($_GET["wpnonce"]) || ! wp_verify_nonce($_GET["wpnonce"], 'work_view_template') ) die("Undefined Nonce.");
    global $WP_Views;
    $options = $WP_Views->get_options();
    if ( isset ($_GET['type']) && isset($_GET['tid']) ){
        $type = $_GET['type'];
        $tid = $options['views_template_for_' . $type];
    }
    else {
      return;
    }
    wpv_count_dissident_posts_from_template( $tid, $type );
	die();
}

// Unlink a Content Template for orphaned single posts types when there is no general Template asociated with that type

add_action('wp_ajax_wpv_single_unlink_template', 'wpv_single_unlink_template_callback');

function wpv_single_unlink_template_callback() {
	if ( !isset( $_POST["wpnonce"] ) || ! wp_verify_nonce( $_POST["wpnonce"], 'wpv_single_unlink_template_nonce') ) die("Undefined Nonce.");
	if ( !isset( $_POST['slug'] ) ) {
		echo __('Slug not set in the AJAX call', 'wpv-wiews');
	} else {
		global $wpdb;
		$type = $_POST['slug'];
		$posts = $wpdb->get_col( "SELECT {$wpdb->posts}.ID FROM {$wpdb->posts}  WHERE post_type='{$type}'" );
		$count = sizeof( $posts );
		if ( $count > 0 ) {
		foreach ( $posts as $post ) {
			update_post_meta( $post, '_views_template', 0 );
			}
		}
		echo 'ok';
	}
	die();
}

/*
 * Add new Content Template - popup structure
 * Added by Gen TODO check this nonce
 */

add_action('wp_ajax_wpv_ct_create_new', 'wpv_ct_create_new_callback');

function wpv_ct_create_new_callback(){
   if ( !isset($_GET["wpnonce"]) || ! wp_verify_nonce($_GET["wpnonce"], 'work_view_template') ) die("Undefined Nonce.");
   global $wpdb, $WP_Views;
   $options = $WP_Views->get_options();
   $post_types_array = wpv_get_pt_tax_array();
   $ct_title = $ct_selected = '';
   if ( isset($_GET['ct_title']) ){
       $ct_title = $_GET['ct_title'];
   }
   if ( isset($_GET['ct_selected']) ){
       $ct_selected = $_GET['ct_selected'];
   }
	$asterisk = '<span style="color:red">*</span>';
	$asterisk_explanation = __( '<span style="color:red">*</span> A different Content Template is already assigned to this item', 'wpv-views' );
   ?>
    <div class="wpv-dialog js-wpv-dialog-add-new-content-template wpv-dialog-add-new-content-template">
	        <div class="wpv-dialog-header">
	            <h2><?php _e('Add new Content Template','wpv-views'); ?></h2>
	            <i class="icon-remove js-dialog-close"></i>
	        </div>
	        <div class="wpv-dialog-content">
	            <p><strong><?php _e('What content will this template be for?','wpv-views') ?></strong></p>

                <p>
					<input id="wpv-content-template-no-use" type="checkbox" class="js-dont-assign"<?php echo $ct_selected == '' ? ' checked="checked"' : ''; ?> name="wpv-new-content-template-post-type[]" value="0" />
                	<label for="wpv-content-template-no-use"><?php _e("Don't assign to any post type",'wpv-views') ?></label>
                </p>

				<div>
					<p>
						<span class="js-wpv-content-template-open wpv-content-template-open" title="<?php echo htmlentities( __( "Click to toggle", 'wpv-views' ), ENT_QUOTES ); ?>">
							<?php echo __( 'Single pages', 'wpv-views' ); ?>:
							<i class="icon-caret-down"></i>
						</span>
					</p>
					<?php
					$single_posts = $post_types_array['single_post'];//key is views_template_for_
					$open_section = false;
					$show_asterisk_explanation = false;
					ob_start();
					if ( count( $single_posts ) > 0 ) {
						?>
						<ul>
						<?php
						foreach ( $single_posts as $s_post ) {// $s_post is an array with each element being (name, label)
							$type = $s_post[0];
							$label = $s_post[1];
							$type_current = $type_used = false;
							if ( isset( $options['views_template_for_' . $type] ) && $options['views_template_for_' . $type] != 0 ) {
								$type_used = true;
								$show_asterisk_explanation = true;
							}
							if ( 'views_template_for_' . $type == $ct_selected ) {
								$type_current = true;
								$type_used = false;
								$open_section = true;
							}
							?>
							<li>
								<input id="<?php echo 'views_template_for_' . $type; ?>" type="checkbox" name="wpv-new-content-template-post-type[]"<?php echo $type_current ? ' checked="checked"' : '';?> data-title="<?php echo esc_attr( $label ); ?>" value="<?php echo 'views_template_for_' . $type; ?>" />
								<label for="<?php echo 'views_template_for_' . $type; ?>"><?php echo $label; echo $type_used ? $asterisk : ''; ?></label>
							</li>
						<?php
						}
						?>
						</ul>
						<?php if ( $show_asterisk_explanation ) { ?>
						<span class="wpv-asterisk-explanation">
							<?php echo $asterisk_explanation; ?>
						</span>
						<?php } ?>
						<?php
					} else {
						_e( 'There are no single post types to assign Content Templates to', 'wpv-views' );
					}
					$s_content = ob_get_clean();
					?>
					<div class="js-wpv-content-template-dropdown-list wpv-content-template-dropdown-list<?php echo $open_section ? '' : ' hidden'; ?>">
						<?php echo $s_content; ?>
					</div>
					<p>
						<span class="js-wpv-content-template-open wpv-content-template-open" title="<?php echo htmlentities( __( "Click to toggle", 'wpv-views' ), ENT_QUOTES ); ?>">
							<?php echo __( 'Post type archives', 'wpv-views' ); ?>:
							<i class="icon-caret-down"></i>
						</span>
					</p>
					<?php
					$archive_posts = $post_types_array['archive_post'];//key is views_template_archive_for_
					$open_section = false;
					$show_asterisk_explanation = false;
					ob_start();
					if ( count( $archive_posts ) > 0 ) {
						?>
						<ul>
						<?php
						foreach ( $archive_posts as $s_post ) {// $s_post is an array with each element being (name, label)
							$type = $s_post[0];
							$label = $s_post[1];
							$type_current = $type_used = false;
							if ( isset( $options['views_template_archive_for_' . $type] ) && $options['views_template_archive_for_' . $type] != 0 ) {
								$type_used = true;
								$show_asterisk_explanation = true;
							}
							if ( 'views_template_archive_for_' . $type == $ct_selected ) {
								$type_current = true;
								$type_used = false;
								$open_section = true;
							}
							?>
							<li>
								<input id="<?php echo 'views_template_archive_for_' . $type; ?>" type="checkbox" name="wpv-new-content-template-post-type[]"<?php echo $type_current ? ' checked="checked"' : ''; ?> data-title="<?php echo esc_attr( $label ); ?>" value="<?php echo 'views_template_archive_for_' . $type; ?>" />
								<label for="<?php echo 'views_template_archive_for_' . $type; ?>"><?php echo $label; echo $type_used ? $asterisk : ''; ?></label>
							</li>
							<?php
						}
						?>
						</ul>
						<?php if ( $show_asterisk_explanation ) { ?>
						<span class="wpv-asterisk-explanation">
							<?php echo $asterisk_explanation; ?>
						</span>
						<?php } ?>
						<?php
					} else {
						_e( 'There are no post type archives to assign Content Templates to', 'wpv-views' );
					}
					$pta_content = ob_get_clean();
					?>
					<div class="js-wpv-content-template-dropdown-list wpv-content-template-dropdown-list<?php echo $open_section ? '' : ' hidden'; ?>">
						<?php echo $pta_content; ?>
					</div>
					<p>
						<span class="js-wpv-content-template-open wpv-content-template-open" title="<?php echo htmlentities( __( "Click to toggle", 'wpv-views' ), ENT_QUOTES ); ?>">
							<?php echo __( 'Taxonomy archives', 'wpv-views' ); ?>:
							<i class="icon-caret-down"></i>
						</span>
					</p>
					<?php
					$archive_taxes = $post_types_array['taxonomy_post'];//key is views_template_loop_
					$open_section = false;
					$show_asterisk_explanation = false;
					ob_start();
					if ( count( $archive_taxes ) > 0 ) {
						?>
						<ul>
						<?php
						foreach ( $archive_taxes as $s_post ) {// $s_post is an array with each element being (name, label)
							$type = $s_post[0];
							$label = $s_post[1];
							$type_current = $type_used = false;
							if ( isset( $options['views_template_loop_' . $type] ) && $options['views_template_loop_' . $type] != 0 ) {
								$type_used = true;
								$show_asterisk_explanation = true;
							}
							if ( 'views_template_loop_' . $type == $ct_selected ) {
								$type_current = true;
								$type_used = false;
								$open_section = true;
							}
							?>
							<li>
								<input id="<?php echo 'views_template_loop_' . $type; ?>" type="checkbox" name="wpv-new-content-template-post-type[]"<?php echo $type_current? ' checked="checked"' : '';?> data-title="<?php echo esc_attr( $label ); ?>" value="<?php echo 'views_template_loop_' . $type; ?>" />
								<label for="<?php echo 'views_template_loop_' . $type; ?>"><?php echo $label; echo $type_used ? $asterisk : ''; ?></label>
							</li>
							<?php
						}
						?>
						</ul>
						<?php if ( $show_asterisk_explanation ) { ?>
						<span class="wpv-asterisk-explanation">
							<?php echo $asterisk_explanation; ?>
						</span>
						<?php } ?>
						<?php
					} else {
						_e( 'There are no taxonomy archives to assign Content Templates to', 'wpv-views' );
					}
					$tax_content = ob_get_clean();
					?>
					<div class="js-wpv-content-template-dropdown-list wpv-content-template-dropdown-list<?php echo $open_section ? '' : ' hidden'; ?>">
						<?php echo $tax_content; ?>
					</div>
				</div>
                <p>
                	<strong><?php _e('Name this Content Template','wpv-views') ?></strong>
                </p>
	            <p>
	                <input type="text" value="<?php echo htmlentities( $ct_title, ENT_QUOTES ); ?>" class="js-wpv-new-content-template-name wpv-new-content-template-name" placeholder="<?php echo htmlentities( __('Content template name','wpv-views'), ENT_QUOTES ) ?>" name="wpv-new-content-template-name">
	            </p>
                <div class="js-wpv-error-container"></div>
	        </div> <!-- .wpv-dialog-content -->
	        <div class="wpv-dialog-footer">
	            <button class="button js-dialog-close"><?php _e('Cancel','wpv-views') ?></button>
	            <button class="button button-primary js-wpv-create-new-template"><?php _e('Create a template','wpv-views') ?></button>
	        </div>

    </div> <!-- wpv-dialog -->
    <?php
     die();
}

/*
 * Save new CT callback function
 * Added by Gen TODO check this nonce
 */

add_action('wp_ajax_wpv_ct_create_new_save', 'wpv_ct_create_new_save_callback');

function wpv_ct_create_new_save_callback(){
    if ( ! isset( $_POST["wpnonce"] ) || ! wp_verify_nonce( $_POST["wpnonce"], 'work_view_template' ) ) {
		die( "Undefined Nonce." );
	}
    global $WP_Views;
	$title = '';
	if ( isset( $_POST['title'] ) ) {
	   $title = sanitize_text_field( $_POST['title'] );
	}
	if ( empty( $title ) ) {
		print json_encode( array( 'error', __( 'You can not create a Content Template with an empty name.', 'wpv-views' ) ) );
		die();
	}
	if ( ! isset( $_POST['type'] ) ) {
		$_POST['type'] = array( 0 );
	}
	$type = $_POST['type'];
	$create_template = wpv_create_content_template( $title, '', false, '' );
	if ( isset( $create_template['error'] ) ) {
		print json_encode( array( 'error', __( 'A Content Template with that name already exists. Please use another name.', 'wpv-views' ) ) );
		die();
	}
	if ( isset( $create_template['success'] ) ) {
		if ( $type[0] != '0' ) {
			$options = $WP_Views->get_options();
			foreach ( $type as $type_to_save ) {
				$type_to_save = sanitize_text_field( $type_to_save );
				$options[ $type_to_save ] = $create_template['success'];
			}
			$WP_Views->save_options( $options );
		}
		print json_encode( array( $create_template['success'] ) );
	} else {
		print json_encode( array( 'error', __( 'An unexpected error happened.', 'wpv-views' ) ) );
	}
   die();
}

// Check if another CT with the same name already exists
// This is used in the CT edit screen, on submit
// @todo check nonce

add_action('wp_ajax_wpv_ct_check_name_exists', 'wpv_ct_check_name_exists_callback');

function wpv_ct_check_name_exists_callback() {
    if ( 
		! isset( $_POST["wpnonce"] ) 
		|| ! wp_verify_nonce( $_POST["wpnonce"], 'set_view_template' ) 
	) {
		die( "Undefined Nonce." );
	}
    global $wpdb;
	if ( 
		isset( $_POST['title'] ) 
		&& ! empty( $_POST['title'] ) 
	) {
		$title = $_POST['title'];
	} else {
		echo 'wpv_error_ct_title_empty';
		die();
	}
	$id = $_POST['id'];
	$postid = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT ID FROM {$wpdb->posts} WHERE ( post_title = %s OR post_name = %s ) AND post_type = 'view-template' AND ID != %d LIMIT 1",
			$title,
			$title,
			$id 
		) 
	);
	if ( ! empty( $postid ) ) {
	   echo 'wpv_error_ct_title_in_use';
	   die();
	}
	echo 'wpv_success';
	die();
}

// Delete CT callback function

add_action('wp_ajax_wpv_delete_ct', 'wpv_delete_ct_callback');

function wpv_delete_ct_callback(){

    if ( !isset($_POST["wpnonce"]) || ! wp_verify_nonce($_POST["wpnonce"], 'work_view_template') ) die("Undefined Nonce.");

    global $wpdb, $WP_Views;
    $options = $WP_Views->get_options();
    $tid = $_POST['id'];
    foreach ($options as $key => $value) {
         if ($value == $tid){
            $options[$key] = 0;
         }
    }
    $WP_Views->save_options( $options );
    wp_delete_post($tid);
	echo $tid;
    die();
}

//Duplicate CT callback function

add_action('wp_ajax_wpv_duplicate_ct', 'wpv_duplicate_ct_callback');

function wpv_duplicate_ct_callback() {
    global $wpdb;
    if (
		! isset( $_POST["wpnonce"] )
		|| ! wp_verify_nonce( $_POST["wpnonce"], 'work_view_template' ) 
	) {
		die( "Undefined Nonce." );
	}
	$title = '';
	if ( isset( $_POST['title'] ) ) {
	   $title = sanitize_text_field( $_POST['title'] );
	}
	if ( empty( $title ) ) {
		print json_encode( array( 'error', __( 'You can not create a Content Template with an empty name.', 'wpv-views' ) ) );
		die();
	}
	$old_post_id = $_POST["id"];
	$clone_args = array(
		'title' => $title,
		'force' => false
	);
	$create_template = wpv_clone_content_template( $old_post_id, $clone_args );
	if ( isset( $create_template['error'] ) ) {
		print json_encode( array( 'error', __( 'A Content Template with that name already exists. Please use another name.', 'wpv-views' ) ) );
	} else if ( isset( $create_template['success'] ) ) {
		print json_encode( array('ok') );
	} else {
		print json_encode( array( 'error', __( 'An unexpected error happened.', 'wpv-views' ) ) );
	}
    die();
}

/**
* wpv_assign_ct_to_view_callback
*
* Dialog to assign a Content Template as an inline one to a View, created by the event of clicking on the Content Template button in the Layout toolbar
*
* As we need to update the list of already assigned Content Templates along with the one of existing but not assigned, we need to do this on an AJAX call
*
* @since unknown
*/

add_action('wp_ajax_wpv_assign_ct_to_view', 'wpv_assign_ct_to_view_callback');

function wpv_assign_ct_to_view_callback() {
    if (
		! isset( $_POST["wpnonce"] )
		|| (
			! wp_verify_nonce( $_POST["wpnonce"], 'wpv_inline_content_template' ) 
			&& ! wp_verify_nonce( $_POST["wpnonce"], 'wpv-ct-inline-edit' )
		)	// Keep this for backwards compat and also for Layouts
	) {
		die( "Undefined Nonce." );
	}
	if ( ! isset( $_POST['view_id'] ) ) {
		die();
	}
	global $wpdb;
	$view_id = $_POST['view_id'];
	$layout_settings = get_post_meta( $view_id, '_wpv_layout_settings', true);
	$assigned_templates = array();
	if ( isset( $layout_settings['included_ct_ids'] ) && $layout_settings['included_ct_ids'] != '' ) {
		$assigned_templates = explode( ',', $layout_settings['included_ct_ids'] );
	}
	?>
	<div class="wpv-dialog js-wpv-dialog-add-new-content-template">
		<div class="wpv-dialog-header">
			<h2><?php _e('Assign a Content Template to this View','wpv-views') ?></h2>
			<i class="icon-remove js-dialog-close"></i>
		</div>
		<div class="wpv-dialog-content">
			<p>
				<?php
				_e( 'Use Content Templates as chunks of content that will be repeated in each element of the View loop.', 'wpv-views' );
				?>
			</p>
			<?php
			$not_in = '';
			$view_loop_template_key = '_view_loop_template';
			$not_in_array = $wpdb->get_col( 
				$wpdb->prepare( 
					"SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key = %s ORDER BY post_id",
					$view_loop_template_key
				)
			); 
			$query_args = array(
				'post_type' => 'view-template',
				'orderby' => 'title', 
				'order' => 'ASC',
				'posts_per_page' => '-1'
			);
			if ( count( $assigned_templates ) > 0 ) {
			?>
				<h4><?php _e( 'This View has some Content Templates already assigned', 'wpv-views' ); ?></h4>
				<div style="margin-left:20px;">
					<input type="radio" name="wpv-ct-type" value="already" class="js-wpv-inline-template-type" id="js-wpv-ct-type-existing-asigned">
					<label for="js-wpv-ct-type-existing-asigned"><?php _e( 'Insert a Content Template already assigned into the View', 'wpv-views' ) ?></label>
					<div class="js-wpv-assign-ct-already" style="margin-left:20px;">
						<select class="js-wpv-inline-template-assigned-select" id="js-wpv-ct-add-id-assigned">
							<option value="0"><?php _e( 'Select a Content Template','wpv-views' ) ?>&hellip;</option>
							<?php
							foreach ( $assigned_templates as $assigned_temp ) {
							 if ( is_numeric( $assigned_temp ) ) {
								// This is cached so it is OK to load the whole post
								$template_post = get_post( $assigned_temp );
								if ( 
									is_object( $template_post ) 
									&& $template_post->post_status  == 'publish'
									&& $template_post->post_type  == 'view-template'
								) {
									$not_in_array[] =  $template_post->ID;
									echo '<option value="' . $template_post->ID . '">' . $template_post->post_title . '</option>';
								}
							 }
							}
							?>
						</select>
					</div>
				</div>
				<h4><?php _e( 'Assign other Content Template to the View', 'wpv-views' ); ?></h4>
			<?php
			} else {
			?>
				<h4><?php _e( 'Assign a Content Template to the View', 'wpv-views' ); ?></h4>
			<?php
			}
			// @todo transform this in a suggest text input
			// limit the query to just one, as we are OK with just that
			// also, it should return just IDs for performance
			if ( ! empty( $not_in_array ) ) {
				$not_in = implode( ',', $not_in_array );
				$query_args['exclude'] = $not_in;
			}
			$query = get_posts( $query_args );
			if ( count( $query ) > 0 ) {
			?>
				<div style="margin:0 0 10px 20px;">
					<input type="radio" name="wpv-ct-type" class="js-wpv-inline-template-type" value="existing" id="js-wpv-ct-type-existing">
					<label for="js-wpv-ct-type-existing"><?php _e( 'Assign an existing Content template to this View','wpv-views' ) ?></label>
					<div class="js-wpv-assign-ct-existing" style="margin-left:20px;">
						<select class="js-wpv-inline-template-existing-select" id="js-wpv-ct-add-id">
							<option value="0"><?php _e( 'Select a Content Template','wpv-views' ) ?>&hellip;</option>
							<?php
							foreach( $query as $temp_post ) {
                                echo '<option value="' . $temp_post->ID.'">' . $temp_post->post_title .'</option>';
							}
							?>
						</select>
					</div>
				</div>
			<?php
			}
			?>
			<div style="margin:0 0 10px 20px;">
				<input type="radio" name="wpv-ct-type" class="js-wpv-inline-template-type" value="new" id="js-wpv-ct-type-new">
				<label for="js-wpv-ct-type-new"><?php _e('Create a new Content Template and assign it to this View','wpv-views') ?></label>
				<div style="margin-left:20px;" class="js-wpv-assign-ct-new">
					<input type="text" class="js-wpv-inline-template-new-name" id="js-wpv-ct-type-new-name" placeholder="<?php echo esc_attr( __( 'Type a name', 'wpv-views' ) ); ?>">
					<div class="js-wpv-add-new-ct-name-error-container"></div>
				</div>
			</div>
			<div class="js-wpv-inline-template-insert" id="js-wpv-add-to-editor-line" style="margin:10px 0 10px 20px;">
				<hr />
				<input type="checkbox" class="js-wpv-add-to-editor-check" name="wpv-ct-add-to-editor" id="js-wpv-ct-add-to-editor-btn" checked="checked">
				<label for="js-wpv-ct-add-to-editor-btn"><?php _e('Insert the Content Template shortcode to editor','wpv-views') ?></label>
			</div>
		</div>
		<div class="wpv-dialog-footer">
			<button class="button button-secondary js-dialog-close"><?php _e('Cancel','wpv-views') ?></button>
			<button class="button button-primary js-wpv-assign-inline-content-template"><?php _e('Assign Content Template','wpv-views') ?></button>
		</div>
	</div>
	<?php
    die();
}

/**
* wpv_remove_content_template_from_view_callback
*
* Removes a Content Template from the list of inline Templates of a View
*
* @since unknown
*/

add_action('wp_ajax_wpv_remove_content_template_from_view', 'wpv_remove_content_template_from_view_callback');

function wpv_remove_content_template_from_view_callback() {
    if (
		! isset( $_POST["wpnonce"] )
		|| (
			! wp_verify_nonce( $_POST["wpnonce"], 'wpv_inline_content_template' ) 
			&& ! wp_verify_nonce( $_POST["wpnonce"], 'wpv-ct-inline-edit' )
		)	// Keep this for backwards compat and also for Layouts
	) {
		die( "Undefined Nonce." );
	}
    $view_id = $_POST['view_id'];
    $template_id = $_POST['template_id'];
    $meta = get_post_meta( $view_id, '_wpv_layout_settings', true);
    $templates = '';
    if ( isset( $meta['included_ct_ids'] ) ) {
		$reg_templates = explode( ',', $meta['included_ct_ids'] );
		if ( in_array( $template_id, $reg_templates ) ) {
			$id_key = array_search( $template_id, $reg_templates );
			unset( $reg_templates[$id_key] );
		}
		$templates = implode( ',', $reg_templates );
    }
    $meta['included_ct_ids'] = $templates;
	update_post_meta($view_id, '_wpv_layout_settings', $meta );
	if ( isset( $_POST['dismiss'] ) && $_POST['dismiss'] == 'true' ) {
		wpv_dismiss_dialog( 'remove-content-template-from-view' );
	}
    echo 'wp_success';

    die();
}

// Change CT usage - popup structure TODO review this nonces

add_action('wp_ajax_ct_change_types', 'ct_change_types_callback');

function ct_change_types_callback(){
   if ( !isset( $_GET["wpnonce"] ) || ! wp_verify_nonce($_GET["wpnonce"], 'work_view_template') ) die( "Undefined nonce" );
   global $wpdb, $WP_Views;
   $options = $WP_Views->get_options();
   $post_types_array = wpv_get_pt_tax_array();
   $id = $_GET['id'];
   $asterisk = '<span style="color:red;">*</span>';
   $asterisk_explanation = __( '<span style="color:red">*</span> A different Content Template is already assigned to this item', 'wpv-views' );
	?>
    <div class="wpv-dialog js-wpv-dialog-add-new-content-template wpv-dialog-add-new-content-template">
        <form method="" id="wpv-add-new-content-template-form">
        <div class="wpv-dialog-header">
            <h2><?php _e('Change Types','wpv-views') ?></h2>
            <i class="icon-remove js-dialog-close"></i>
        </div>
        <div class="wpv-dialog-content">
            <p><?php _e('What content will this template be for?','wpv-views') ?></p>
            <div>
                <p>
					<span class="js-wpv-content-template-open wpv-content-template-open" title="<?php echo htmlentities( __( "Click to toggle", 'wpv-views' ), ENT_QUOTES ); ?>">
						<?php echo __( 'Single pages', 'wpv-views' ); ?>:
						<i class="icon-caret-down"></i>
					</span>
				</p>
                <?php
                $single_posts = $post_types_array['single_post'];//key is views_template_for_
                $open_section = false;
                $show_asterisk_explanation = false;
                ob_start();
                if ( count( $single_posts ) > 0 ) {
					?>
					<ul>
					<?php
					foreach ( $single_posts as $s_post ) {// $s_post is an array with each element being (name, label)
						$type = $s_post[0];
						$label = $s_post[1];
						$type_current = $type_used = false;
						if ( isset( $options['views_template_for_' . $type] ) && $options['views_template_for_' . $type] != 0 ) {
							$type_used = true;
							$show_asterisk_explanation = true;
						}
						if ( isset( $options['views_template_for_' . $type] ) && $options['views_template_for_' . $type] == $id ) {
							$type_current = true;
							$type_used = false;
							$open_section = true;
						}
						?>
						<li>
							<input id="<?php echo 'views_template_for_' . $type; ?>" type="checkbox" name="wpv-new-content-template-post-type[]"<?php echo $type_current? ' checked="checked"' : '';?> data-title="<?php echo esc_attr( $label ); ?>" value="<?php echo 'views_template_for_' . $type; ?>" />
							<label for="<?php echo 'views_template_for_' . $type; ?>"><?php echo $label; echo $type_used ? $asterisk : ''; ?></label>
						</li>
					<?php
					}
					?>
					</ul>
					<?php if ( $show_asterisk_explanation ) { ?>
					<span class="wpv-asterisk-explanation">
						<?php echo $asterisk_explanation; ?>
					</span>
					<?php } ?>
					<?php
                } else {
					_e( 'There are no single post types to assign Content Templates to', 'wpv-views' );
                }
                $s_content = ob_get_clean();
                ?>
                <div class="js-wpv-content-template-dropdown-list wpv-content-template-dropdown-list<?php echo $open_section ? '' : ' hidden'; ?>">
					<?php echo $s_content; ?>
                </div>
                <p>
					<span class="js-wpv-content-template-open wpv-content-template-open" title="<?php echo htmlentities( __( "Click to toggle", 'wpv-views' ), ENT_QUOTES ); ?>">
						<?php echo __( 'Post type archives', 'wpv-views' ); ?>:
						<i class="icon-caret-down"></i>
					</span>
				</p>
                <?php
                $archive_posts = $post_types_array['archive_post'];//key is views_template_archive_for_
                $open_section = false;
                $show_asterisk_explanation = false;
                ob_start();
                if ( count( $archive_posts ) > 0 ) {
					?>
					<ul>
					<?php
					foreach ( $archive_posts as $s_post ) {// $s_post is an array with each element being (name, label)
						$type = $s_post[0];
						$label = $s_post[1];
						$type_current = $type_used = false;
						if ( isset( $options['views_template_archive_for_' . $type] ) && $options['views_template_archive_for_' . $type] != 0 ) {
							$type_used = true;
							$show_asterisk_explanation = true;
						}
						if ( isset( $options['views_template_archive_for_' . $type] ) && $options['views_template_archive_for_' . $type] == $id ) {
							$type_current = true;
							$type_used = false;
							$open_section = true;
						}
						?>
						<li>
							<input id="<?php echo 'views_template_archive_for_' . $type; ?>" type="checkbox" name="wpv-new-content-template-post-type[]"<?php echo $type_current ? ' checked="checked"' : ''; ?> data-title="<?php echo esc_attr( $label ); ?>" value="<?php echo 'views_template_archive_for_' . $type; ?>" />
                            <label for="<?php echo 'views_template_archive_for_' . $type; ?>"><?php echo $label; echo $type_used ? $asterisk : ''; ?></label>
                        </li>
						<?php
					}
					?>
					</ul>
					<?php if ( $show_asterisk_explanation ) { ?>
					<span class="wpv-asterisk-explanation">
						<?php echo $asterisk_explanation; ?>
					</span>
					<?php } ?>
					<?php
                } else {
					_e( 'There are no post type archives to assign Content Templates to', 'wpv-views' );
                }
                $pta_content = ob_get_clean();
                ?>
				<div class="js-wpv-content-template-dropdown-list wpv-content-template-dropdown-list<?php echo $open_section ? '' : ' hidden'; ?>">
					<?php echo $pta_content; ?>
				</div>
				<p>
					<span class="js-wpv-content-template-open wpv-content-template-open" title="<?php echo htmlentities( __( "Click to toggle", 'wpv-views' ), ENT_QUOTES ); ?>">
						<?php echo __( 'Taxonomy archives', 'wpv-views' ); ?>:
						<i class="icon-caret-down"></i>
					</span>
				</p>
                <?php
                $archive_taxes = $post_types_array['taxonomy_post'];//key is views_template_loop_
                $open_section = false;
                $show_asterisk_explanation = false;
                ob_start();
                if ( count( $archive_taxes ) > 0 ) {
					?>
					<ul>
					<?php
					foreach ( $archive_taxes as $s_post ) {// $s_post is an array with each element being (name, label)
						$type = $s_post[0];
						$label = $s_post[1];
						$type_current = $type_used = false;
						if ( isset( $options['views_template_loop_' . $type] ) && $options['views_template_loop_' . $type] != 0 ) {
							$type_used = true;
							$show_asterisk_explanation = true;
						}
						if ( isset( $options['views_template_loop_' . $type] ) && $options['views_template_loop_' . $type] == $id ) {
							$type_current = true;
							$type_used = false;
							$open_section = true;
						}
						?>
						<li>
							<input id="<?php echo 'views_template_loop_' . $type; ?>" type="checkbox" name="wpv-new-content-template-post-type[]"<?php echo $type_current? ' checked="checked"' : '';?> data-title="<?php echo esc_attr( $label ); ?>" value="<?php echo 'views_template_loop_' . $type; ?>" />
                            <label for="<?php echo 'views_template_loop_' . $type; ?>"><?php echo $label; echo $type_used ? $asterisk : ''; ?></label>
                        </li>
                        <?php
					}
					?>
					</ul>
					<?php if ( $show_asterisk_explanation ) { ?>
					<span class="wpv-asterisk-explanation">
						<?php echo $asterisk_explanation; ?>
					</span>
					<?php } ?>
					<?php
				} else {
					_e( 'There are no taxonomy archives to assign Content Templates to', 'wpv-views' );
				}
				$tax_content = ob_get_clean();
				?>
				<div class="js-wpv-content-template-dropdown-list wpv-content-template-dropdown-list<?php echo $open_section ? '' : ' hidden'; ?>">
					<?php echo $tax_content; ?>
				</div>
            </div>
        </div>
        <div class="wpv-dialog-footer">
            <button class="button js-dialog-close"><?php _e('Cancel','wpv-views') ?></button>
            <button class="button button-primary js-ct-change-type-process" data-id="<?php echo $id; ?>"><?php _e('Change','wpv-views') ?></button>
        </div>
        </form>
		</div>
    <?php
     die();
}

//Change CT usage callback function TODO check this nonce

add_action('wp_ajax_ct_change_types_process', 'ct_change_types_process_callback');

function ct_change_types_process_callback(){

    if ( !isset($_POST["wpnonce"]) || ! wp_verify_nonce($_POST["wpnonce"], 'work_view_template') ) die("Undefined Nonce.");

    global $wpdb, $WP_Views;
    $options = $WP_Views->get_options();
        $id = $_POST["view_template_id"];
        if ( isset($_POST['type']) ){
            $type = $_POST['type'];
        }else{
            $type = array();
        }
        foreach ($options as $key => $value) {
             if ($value == $id){
                $options[$key] = 0;
             }
        }
        for ($i=0;$i<count($type);$i++){
                 $options[$type[$i]] = $id;
        }
        $WP_Views->save_options( $options );

        echo 'ok';

    die();
}

// Change CT action - popup structure TODO check nonce, check header texts

add_action('wp_ajax_ct_change_types_pt', 'ct_change_types_pt_callback');

function ct_change_types_pt_callback(){
    if ( !isset($_GET["wpnonce"]) || ! wp_verify_nonce($_GET["wpnonce"], 'work_view_template') ) die("Undefined Nonce.");
    global $wpdb, $WP_Views;
    $query = new WP_Query('post_type=view-template&posts_per_page=-1');
    $sort = $_GET['sort'];
    $post_type = $_GET['pt'];
    $no_type = __('Don’t use any Content Template for this Post Type','wpv-views');
    $head_text = __('Change Post Type','wpv-views');
    if ( isset($_GET['msg']) && $_GET['msg'] == '2'){
        $no_type = __('Don’t use any Content Template for this Taxonomy','wpv-views');
        $head_text = __('Change Taxonomy','wpv-views');
    }
    $options = $WP_Views->get_options();
    ?>
    <div class="wpv-dialog js-wpv-dialog-add-new-content-template wpv-dialog-add-new-content-template">
        <form method="" id="wpv-add-new-content-template-form">
        <div class="wpv-dialog-header">
            <h2><?php echo $head_text ?></h2>
            <i class="icon-remove js-dialog-close"></i>
        </div>
        <div class="wpv-dialog-content">
            <div><?php // echo '<pre>';print_r($query);echo '</pre>'; ?>
                <ul>
                <li><label>
                    <input type="radio" name="wpv-new-post-type-content-template" value="0" />
                     <?php echo $no_type; ?>
                     </label>
                </li>
                <?php
                while ($query->have_posts()) :

                    $query->the_post();
                    $id = get_the_id();
                    $current = '';
                    if ( isset($options[$post_type]) && $id == $options[$post_type] ){
                        $current = ' checked="checked"';
                    }
                   ?>
                     <li>
                            <label>
                                <input type="radio" name="wpv-new-post-type-content-template" <?php echo $current;?> value="<?php echo $id;?>" />
                                <?php the_title();?>
                            </label>
                     </li>
                    <?php

                endwhile; ?>
                    </ul>
           </div>

        </div>
        <div class="wpv-dialog-footer">
            <button class="button js-dialog-close"><?php _e('Cancel','wpv-views') ?></button>
            <button class="button button-primary js-ct-change-types-pt-process" data-pt="<?php echo $post_type?>" data-sort="<?php echo $sort?>"><?php _e('Change','wpv-views') ?></button>
        </div>
        </div>
    <?php
    die();
}

// Change CT action callback function TODO check nonces

add_action('wp_ajax_ct_change_types_pt_process', 'ct_change_types_pt_process_callback');

function ct_change_types_pt_process_callback(){

    if ( !isset($_POST["wpnonce"]) || ! wp_verify_nonce($_POST["wpnonce"], 'work_view_template') ) die("Undefined Nonce.");
    global $wpdb, $WP_Views;
        $options = $WP_Views->get_options();
        $pt = $_POST["pt"];
        $sort = $_POST['sort'];
        if ( isset($_POST['value']) ){
            $value = $_POST['value'];
        }
        else{
            $value = 0;
        }
        $options[$pt] = $value;

        $WP_Views->save_options( $options );
        $out = wpv_admin_menu_content_template_listing_by_type_row( $sort );
        echo $out;

    die();
}

// Assign new Content template to view
// This is used when creating a CT inside the Output section of a View

add_action('wp_ajax_wpv_add_view_template', 'wpv_add_view_template_callback');

function wpv_add_view_template_callback() {
    global $wpdb;
    //add new content template
    if (
		! isset( $_POST["wpnonce"] )
		|| (
			! wp_verify_nonce( $_POST["wpnonce"], 'wpv_inline_content_template' ) 
			&& ! wp_verify_nonce( $_POST["wpnonce"], 'wpv-ct-inline-edit' )
		)	// Keep this for backwards compatibility and also for Layouts
	) {
		die( "Undefined Nonce." );
	}
	if ( ! isset( $_POST['view_id'] ) ) {
		echo 'error';
		die();
	}
	$ct_post_id = 0;
	$view_id = sanitize_text_field( $_POST['view_id'] );
    if ( isset( $_POST['template_name'] ) ) {
        // We need to create a new Content Template based on the POSTed template_name
		$template_name = sanitize_text_field( $_POST['template_name'] );
		$response = wpv_create_content_template( $template_name, '', false, '' );
		if ( isset( $response['error'] ) ) {
			// Another Content Template with that title or name already exists
			echo 'error_name';
			die();
		} else if ( isset( $response['success'] ) ) {
			// Everything went well
			$ct_post_id = $response['success'];
		}
    } else if ( isset( $_POST['template_id'] ) ) {
       $ct_post_id = sanitize_text_field( $_POST['template_id'] );
    }
    $ct_post_object = get_post( $ct_post_id );
    if ( ! is_object( $ct_post_object ) ) {
        echo 'error';
		die();
    }
    $meta = get_post_meta( $view_id, '_wpv_layout_settings', true );
    $reg_templates = array();
    if ( isset( $meta['included_ct_ids'] ) ) {
        $reg_templates = explode( ',', $meta['included_ct_ids'] );
	}
	if ( in_array( $ct_post_id, $reg_templates ) ) {
		// The Content Template was already on the inline list
		echo 'wp_success';
	} else {
		// Add the Content Template to the inline list and save it
		$reg_templates[] = $ct_post_id;
        $meta['included_ct_ids'] = implode( ',', $reg_templates );
        update_post_meta( $view_id, '_wpv_layout_settings', $meta );
        wpv_list_view_ct_item( $ct_post_object, $ct_post_id, $view_id, true );
	}
    die();
}

/**
* wpv_ct_update_inline_callback
*
* Updates one inline Content Template in a layout section of a View or WPA
*
* @since unknown
*/

add_action( 'wp_ajax_wpv_ct_update_inline', 'wpv_ct_update_inline_callback' );

function wpv_ct_update_inline_callback() {
    if (
		! isset( $_POST["wpnonce"] )
		|| (
			! wp_verify_nonce( $_POST["wpnonce"], 'wpv_inline_content_template' ) 
			&& ! wp_verify_nonce( $_POST["wpnonce"], 'wpv-ct-inline-edit' )
		)	// Keep this for backwards compat and also for Layouts
	) {
		die( "Undefined Nonce." );
	}
    $my_post = array();
    $my_post['ID'] = $_POST['ct_id'];
    $my_post['post_content'] = $_POST['ct_value'];
	if ( isset( $_POST['ct_title'] ) ) {
		$my_post['post_title'] = $_POST['ct_title'];
	}
    $result = wp_update_post( $my_post );
	echo $result;
    die();
}

// Response when updating all posts to use a given CT - popup structure TODO localize!!!! and check nonce
// TODO seems that this is called in a colorbox callback, but BUT is executes the delete... TODO review this all

add_action('wp_ajax_set_view_template_listing', 'set_view_template_listing_callback');

function set_view_template_listing_callback() {
    if ( !isset($_POST["wpnonce"]) || ! wp_verify_nonce($_POST["wpnonce"], 'work_view_template') ) die("Undefined Nonce.");
    $view_template_id = $_POST['view_template_id'];
    $type = $_POST['type'];
    wpv_update_dissident_posts_from_template( $view_template_id, $type);
    die();
}

// Load CT editor (inline - inside View editor page) TODO check nonce and, god's sake, error handling

/**
* wpv_ct_loader_inline_callback
*
* Load a Content Template in the View or WPA layout section
*
* Displays the textarea with toolbars, and optionally the formatting instructions
*
* @note used by Layouts too
*
* @since unknown
*/

add_action('wp_ajax_wpv_ct_loader_inline', 'wpv_ct_loader_inline_callback');

function wpv_ct_loader_inline_callback() {
    if (
		! isset( $_POST["wpnonce"] )
		|| (
			! wp_verify_nonce( $_POST["wpnonce"], 'wpv_inline_content_template' ) 
			&& ! wp_verify_nonce( $_POST["wpnonce"], 'wpv-ct-inline-edit' )
		)	// Keep this for backwards compat and also for Layouts
	) {
		die( "Undefined Nonce." );
	}
	// @todo check why the hell this is here
    do_action('views_ct_inline_editor');
	if ( ! isset( $_POST['id'] ) ) {
		echo 'error';
		die();
	}
    $template = get_post( $_POST['id'] );
    // @todo check what the hell is that constant
	// This is for the CRED button and icon!!
	define("CT_INLINE", "1");
    if ( 
		is_object( $template ) 
		&& isset( $template->ID ) 
		&& isset( $template->post_type ) 
		&& $template->post_type == 'view-template'
	) {
        $ct_id = $template->ID;
    ?>
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
		<textarea name="name" rows="10" class="js-wpv-ct-inline-editor-txtarea" data-id="<?php echo $ct_id; ?>" id="wpv-ct-inline-editor-<?php echo $ct_id; ?>"><?php echo $template->post_content; ?></textarea>
		<?php
		if ( isset( $_POST['include_instructions'] ) ) {
			if ( $_POST['include_instructions'] == 'inline_content_template' ) {
				wpv_formatting_help_inline_content_template( $template );
			}
			if ( $_POST['include_instructions'] == 'layouts_content_cell' ) {
				wpv_formatting_help_layouts_content_template_cell( $template );
			}
		}
		?>
    <?php
    } else {
       echo 'error';
    }
    die();
}


add_action('wp_ajax_wpv_content_template_move_to_trash', 'wpv_content_template_move_to_trash_callback');

/** Move CT to trash or show message.
 *
 * Prints a JSON array. If CT is not in use, it is trashed and first element of the array is "move" and second one
 * contains ID of the CT. Otherwise first element is "show" and second contains HTML for the colorbox dialog that should
 * be shown.
 */
function wpv_content_template_move_to_trash_callback() {
    $nonce = $_POST["wpnonce"];
	if ( ! (
		wp_verify_nonce($nonce, 'wpv_view_listing_actions_nonce')
	) ) die("Security check");

	if ( isset($_POST['id']) ){
		$ct_id = $_POST['id'];
	}else{
		echo 'Error: no content template ID';
		die();
	}
	global $wpdb;

	$posts_count = $wpdb->get_var( $wpdb->prepare( "select count(posts.ID) from {$wpdb->posts} as posts,{$wpdb->postmeta} as postmeta WHERE postmeta.meta_key='_views_template' AND postmeta.meta_value='%s' AND postmeta.post_id=posts.ID", $ct_id ) );
	if ( $posts_count == 0 ){
		if ( !isset( $_POST['newstatus'] ) ) $_POST['newstatus'] = 'publish';
		$my_post = array(
			'ID'           => $ct_id,
			'post_status' => 'trash'
		);
		// TODO $return is never used; should it be?
		$return = wp_update_post( $my_post );

		wpv_replace_views_template_options( $ct_id, 0 );
		
		$out = array( 'move', $ct_id );
	}else{
		$template_list = $wpdb->get_results( $wpdb->prepare( "SELECT ID, post_title FROM $wpdb->posts WHERE post_status = 'publish' ".
		"AND post_type='view-template' AND ID!=%s", $ct_id ));
		ob_start();
		?>
		<div class="wpv-dialog js-wpv-dialog-add-new-content-template wpv-dialog-add-new-content-template">
        <form method="" id="wpv-add-new-content-template-form">
        <div class="wpv-dialog-header">
            <h2><?php _e( 'Content template in use', 'wpv-views' )?></h2>
            <i class="icon-remove js-dialog-close"></i>
        </div>
        <div class="wpv-dialog-content wpv-dialog-trash-ct">
            <div>
            	<p>
            		<?php echo sprintf( _n('1 item', '%s items', $posts_count, 'wpv-views'), $posts_count ) . __( ' use this content template. What do you want to do?', 'wpv-views' ); ?>
            	</p>
                <ul>
                <?php if ( count($template_list) > 0 ){?>
                <li><label>
                    <input type="radio" name="wpv-content-template-replace-to" class="js-wpv-existing-posts-ct-replace-to js-wpv-existing-posts-ct-replace-to-selected-ct" value="0" id="wpv-content-template-replace-to" />
                    <?php _e( 'Choose a different content template for them: ', 'wpv-views' )?>
                    </label>
                    <select name="wpv-ct-list-for-replace" class="js-wpv-ct-list-for-replace" id="wpv-ct-list-for-replace">
                    	<option value=''><?php _e( 'Select Content Template', 'wpv-views' )?> </option>
                    <?php
						foreach( $template_list as $temp_post ) :
							echo '<option value="'.$temp_post->ID.'">'. $temp_post->post_title .'</option>';
                        endforeach;
					?></select>

                </li>
                <?php }?>
                <li><label>
                    <input type="radio" name="wpv-content-template-replace-to" class="js-wpv-existing-posts-ct-replace-to" value="1" />
                    <?php _e( 'Don\'t use any content template for these items', 'wpv-views' )?>
                    </label>
                </li>
                    </ul>
           </div>

        </div>
        <div class="wpv-dialog-footer">
            <button class="button js-dialog-close"><?php _e('Cancel','wpv-views') ?></button>
            <button class="button button-primary js-ct-replace-usage" data-ct_id="<?php echo $ct_id;?>"><?php _e('Change','wpv-views') ?></button>
        </div>
        </div>
        <?php
        $popup_content = ob_get_contents();
		ob_end_clean();
		$out = array('show', $popup_content);
	}



	wp_reset_postdata();
	echo json_encode( $out );
    die();
}


/**
 * Initiate a bulk trash action.
 *
 * For given content templates, find out how many posts use them. If no template is used in any post,
 * trash them right away. If one or more templates is being used, render HTML for a colorbox dialog
 * (wpv-dialog-bulk-replace-content-template) that will ask user to decide how to replace those templates.
 * See js events on '.js-ct-bulk-replace-usage' for further information.
 *
 * Expected POST parameters:
 * - ids: A non-empty array of content template IDs.
 * - wpnonce: A valid wpv_view_listing_actions_nonce.
 *
 * Response is an JSON object containing following properties:
 * - all_ids: Array of all content templates that were/should be trashed.
 * - action: 'trashed' if CTs have been trashed, 'confirm' if a popup should be shown before that.
 * - popup_content: If action is 'confirm', this will contain the HTML of the popup.
 * 
 * @since 1.7
 */ 
add_action( 'wp_ajax_wpv_bulk_content_templates_move_to_trash', 'wpv_bulk_content_templates_move_to_trash_callback' );

function wpv_bulk_content_templates_move_to_trash_callback() {
    $nonce = $_POST["wpnonce"];
	if ( ! wp_verify_nonce( $nonce, 'wpv_view_listing_actions_nonce' ) ) {
		die( "Security check" );
	}

	if( !isset( $_POST['ids'] ) ) {
		// We don't allow empty input
		die( "Error: No Content Templates given." );
	} else if( is_string( $_POST['ids'] ) ) {
		$ct_ids = array( $_POST['ids'] );
	} else {
		$ct_ids = $_POST['ids'];
	}

	$result = array(
			'all_ids' => $ct_ids );

	// Determine which templates are currently in use.
	global $wpdb;

	// This will hold information about used templates indexed by their IDs
	$used_templates = array();
	foreach( $ct_ids as $template_id ) {
		// TODO this probably counts drafts, autosaves, etc. Is that desired?
		$using_posts_count = $wpdb->get_var( $wpdb->prepare(
				"SELECT DISTINCT COUNT( posts.ID )
				FROM {$wpdb->posts} AS posts, {$wpdb->postmeta} AS postmeta
				WHERE postmeta.meta_key = '_views_template'
					AND postmeta.meta_value = %s
					AND postmeta.post_id = posts.ID",
				$template_id ) );

		if( $using_posts_count > 0 ) {
			$template_title = $wpdb->get_var( $wpdb->prepare(
					"SELECT post_title FROM {$wpdb->posts} WHERE ID = %d",
					$template_id ) );
			$used_templates[ $template_id ] = array(
					'title' => $template_title,
					'usage_count' => $using_posts_count );
		}
	}

	if( empty( $used_templates ) ) {
		// No template is used, we can trash them all.
		$result['action'] = 'trashed';

		global $WP_Views;
		$options = $WP_Views->get_options();

		foreach( $ct_ids as $template_id ) {

			// Trash the template
			$my_post = array(
					'ID' => $template_id,
					'post_status' => 'trash' );
			wp_update_post( $my_post );

			// Remove references to trashed template from Views options
			$options = wpv_replace_views_template_options( $template_id, 0, $options );
		}

		$WP_Views->save_options( $options );
		
	} else {
		// One or more templates are in use, we need to show a confirmation.
		$result['action'] = 'confirm';

		// Get list of templates that can be used as a replacement for the trashed ones.
		$templates_to_trash = implode( ', ', $ct_ids );
		$template_list = $wpdb->get_results( 
			"SELECT ID, post_title
			FROM {$wpdb->posts}
			WHERE post_status = 'publish'
				AND post_type = 'view-template'
				AND ID NOT IN ( " . $templates_to_trash . " )"  );

		// Render popup content.
		ob_start();

		?>
		<div class="wpv-dialog js-wpv-dialog-bulk-replace-content-template wpv-dialog-bulk-replace-content-template">
			<div class="wpv-dialog-header">
				<h2><?php _e( 'Content templates in use', 'wpv-views' )?></h2>
				<i class="icon-remove js-dialog-close"></i>
			</div>
			<div class="wpv-dialog-content wpv-dialog-bulk-trash-cts">
				<p><?php _e( 'These content templates are in use. What do you want to do?', 'wpv-views' ); ?></p>
				<?php

					// Show a div with options for each used template.
					foreach( $used_templates as $template_id => $template_info ) {
						$template_title = $template_info['title'];
						$template_usage_count = $template_info['usage_count'];

						?>
						<div>
							<?php
								printf(
										'<p><strong>%s</strong> (%s %s)</p>',
										$template_title,
										__( 'used by', 'wpv-view' ),
										sprintf( _n( '1 item', '%s items', $template_usage_count, 'wpv-views' ), $template_usage_count ) );
							?>
							<ul>
								<?php
									/* Show an option to replace this template with another one, if there are some left.
									 * Radio buttons are grouped by name: "wpv-content-template-replace-{$template_id}-to".
									 * Select field for replacement template is identified as "wpv-ct-list-for-replace-{$template_id}"
									 *
									 * Submit button contains attributes 'data-ct-ids' with all Content Template IDs that should be trashed
									 * and 'data-replace-ids' with those that should be replaced. */ 
									if( !empty( $template_list ) ) {
										?>
										<li>
											<label>
												<?php
													printf(
															'<input type="radio" name="wpv-content-template-replace-%d-to"
																class="js-wpv-bulk-existing-posts-ct-replace-to js-wpv-bulk-existing-posts-ct-replace-to-selected-ct"
																value="different_template" id="wpv-content-template-replace-to" />',
															$template_id );
													_e( 'Choose a different content template for them: ', 'wpv-views' );
												?>
											</label>
											<?php
												printf( '<select class="js-wpv-bulk-ct-list-for-replace" id="wpv-ct-list-for-replace-%d" data-template-id="%d">', $template_id, $template_id );
												printf( '<option value="">%s</option>', __( 'Select Content Template', 'wpv-views' ) );
												foreach( $template_list as $temp_post ) {
													printf( '<option value="%s">%s</option>', $temp_post->ID, $temp_post->post_title );
												}
												printf( '</select>' );
											?>
										</li>
										<?php
									}
								?>
								<li>
									<label>
										<?php
											printf( '<input type="radio" name="wpv-content-template-replace-%d-to"
														class="js-wpv-bulk-existing-posts-ct-replace-to" value="no_template" />',
													$template_id );
											_e( 'Don\'t use any content template for these items', 'wpv-views' );
										?>
									</label>
								</li>
							</ul>
						</div>
						<?php
					}
				?>
			</div>
			<div class="wpv-dialog-footer">
				<button class="button js-dialog-close"><?php _e( 'Cancel', 'wpv-views' ); ?></button>
				<?php
					// Submit button with IDs of all templates to trash and templates that are being used somewhere.
					printf(
							'<button class="button button-primary js-ct-bulk-replace-usage"
								data-ct-ids="%s" data-replace-ids="%s" data-nonce="%s">%s</button>',
							urlencode( implode( ',', $ct_ids ) ),
							urlencode( implode( ',', array_keys( $used_templates ) ) ),
							$nonce,
							__( 'Change', 'wpv-views' ) );
				?>
			</div>
		</div>
		<?php

		$result['popup_content'] = ob_get_contents();
		ob_end_clean();
	}

	echo json_encode( $result );
    die();
}




// Change CT usage before move to trash
add_action('wp_ajax_wpv_ct_move_with_replace', 'wpv_ct_move_with_replace_callback');

function wpv_ct_move_with_replace_callback() {
    global $wpdb;
    $nonce = $_POST["wpnonce"];
	if ( ! (
		wp_verify_nonce($nonce, 'wpv_view_listing_actions_nonce')
	) ) die("Security check");
	if ( isset($_POST['id']) ){
		$ct_id = $_POST['id'];
	}else{
		echo 'Error: no content template ID';
		die();
	}
	if ( $_POST['replace_to'] == 0){
		$replace = $replace_option = $_POST['replace_ct'];
	}else{
		$replace = 0;
		$replace_option = 0;
	}
	$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->postmeta} SET meta_value='%s' WHERE meta_key='_views_template' AND meta_value='$ct_id'", $replace ) );

	wpv_replace_views_template_options( $ct_id, $replace_option );

	$my_post = array(
		'ID'           => $ct_id,
		'post_status' => 'trash'
	);
	wp_update_post( $my_post );
	print $ct_id;
    die();
}



/**
 * Replace content templates that are being used by some posts and trash all given content templates (which may be a
 * superset of those being used).
 *
 * Expected POST parameters:
 * - ids: an array of IDs of all templates that should be trashed
 * - toreplace: dtto, templates that should be replaced
 * - replacements: dtto, replacement templates (same lenght and order as toreplace)
 * - wpnonce: A valid wpv_view_listing_actions_nonce.
 *
 * Content templates from 'toreplace' used in posts (and post types) will be replaced by 'replacements'. Zero values
 * in 'replacements' are interpreted as "no template". Then, all templates from 'ids' will be trashed.
 *
 * Outputs '1' on success.
 *
 * @since 1.7
 */ 
add_action( 'wp_ajax_wpv_ct_bulk_trash_with_replace', 'wpv_ct_bulk_trash_with_replace_callback' );

function wpv_ct_bulk_trash_with_replace_callback() {
    global $wpdb;
    $nonce = $_POST["wpnonce"];
	if ( !wp_verify_nonce( $nonce, 'wpv_view_listing_actions_nonce' ) ) {
		die( "Security check" );
	}

	if( !isset( $_POST['ids'] ) ) {
		// Don't allow empty input
		die( "Error: no content template IDs given." );
	} else if( is_string( $_POST['ids'] ) ) {
		$ct_ids = array( $_POST['ids'] );
	} else {
		$ct_ids = $_POST['ids'];
	}

	if( isset( $_POST['replacements'] ) && isset( $_POST['toreplace'] )
		&& is_array( $_POST['replacements'] ) && is_array( $_POST['toreplace'] ) ){
		/* This will hold template IDs as keys and IDs of their replacements as values. Value 0 indicates
		 * 'don't use any content template'. */
		$replacements = array();

		$replacement_count = count( $_POST['replacements'] );
		for( $i = 0; $i < $replacement_count; ++$i ) {
			$replacements[ $_POST['toreplace'][ $i ] ] = $_POST['replacements'][ $i ];
		}
	} else {
		die( "Error: invalid input (replacements)." );
	}

	global $WP_Views;
    $options = $WP_Views->get_options();

	// Replace content templates as requested
	global $wpdb;
	foreach( $replacements as $original_template_id => $replacement_template_id ) {
		$changed_rows = $wpdb->query( $wpdb->prepare(
				"UPDATE {$wpdb->postmeta}
				SET meta_value = %s
				WHERE meta_key = '_views_template'
					AND meta_value = %s",
				$replacement_template_id,
				$original_template_id ) );

		$options = wpv_replace_views_template_options( $original_template_id, $replacement_template_id, $options );
	}

	// Now trash all requested templates
	foreach( $ct_ids as $template_id ) {
		$my_post = array(
				'ID' => $template_id,
				'post_status' => 'trash' );
		wp_update_post( $my_post );

		// Remove references to trashed template from Views options
		$options = wpv_replace_views_template_options( $template_id, 0, $options );
	}

	$WP_Views->save_options( $options );
	
	// Success.
	echo '1';
    die();
}



/**
 * Count posts where given Content Templates are used.
 *
 * For given array of Content Template IDs it calculates in how many posts is each template used.
 * Outputs a JSON representation of an array where keys are CT IDs and values are post counts.
 * This array also allways contains an element "0" with the sum of all post counts.
 *
 * Expected POST parameters:
 * - wpnonce: A valid wpv_view_listing_actions_nonce.
 * - ids: An array of CT IDs
 *
 * @since 1.7
 */ 
add_action( 'wp_ajax_wpv_ct_bulk_count_usage', 'wpv_ct_bulk_count_usage_callback' );

function wpv_ct_bulk_count_usage_callback() {
	$nonce = $_POST["wpnonce"];
	if ( !wp_verify_nonce( $nonce, 'wpv_view_listing_actions_nonce' ) ) {
		die( "Security check" );
	}

	if( !isset( $_POST['ids'] ) ) {
		$ct_ids = array();
	} else if( is_string( $_POST['ids'] ) ) {
		$ct_ids = array( $_POST['ids'] );
	} else {
		$ct_ids = $_POST['ids'];
	}

	global $wpdb;

	$usage_results = array();
	$total_usage = 0;
	
	foreach( $ct_ids as $ct_id ) {
		$assigned_count = $wpdb->get_var( $wpdb->prepare(
				"SELECT COUNT(post_id)
				FROM {$wpdb->postmeta} JOIN {$wpdb->posts} p
				WHERE meta_key='_views_template'
					AND meta_value = %d
					AND post_id = p.ID
					AND p.post_status NOT IN  ('auto-draft')
					AND p.post_type != 'revision'",
				$ct_id ) );
		$usage_results[ $ct_id ] = $assigned_count;
		$total_usage += $assigned_count;
	}

	$usage_results[0] = $total_usage;

	echo json_encode( $usage_results );
	die();
}


/**
 * Bulk delete Content Templates.
 *
 * Expects following POST parameters:
 * - wpnonce: A valid wpv_view_listing_actions_nonce.
 * - ids: An array of CT IDs to be deleted.
 *
 * Deletes templates and removes all occurences of their IDs from Views options.
 *
 * Outputs '1' on success.
 *
 * @since 1.7
 */ 
add_action( 'wp_ajax_wpv_ct_bulk_delete', 'wpv_ct_bulk_delete_callback' );

function wpv_ct_bulk_delete_callback() {

	$nonce = $_POST["wpnonce"];
	if ( !wp_verify_nonce( $nonce, 'wpv_view_listing_actions_nonce' ) ) {
		die( "Security check" );
	}

	if( !isset( $_POST['ids'] ) ) {
		$ct_ids = array();
	} else if( is_string( $_POST['ids'] ) ) {
		$ct_ids = array( $_POST['ids'] );
	} else {
		$ct_ids = $_POST['ids'];
	}

    global $WP_Views;
    $options = $WP_Views->get_options();

    foreach( $ct_ids as $ct_id ) {
		$options = wpv_replace_views_template_options( $ct_id, 0, $options );
		wp_delete_post( $ct_id );
	}
	
	$WP_Views->save_options( $options );

	echo '1';
    die();
}

/**
* wpv_dismiss_pointer_callback
*
* Dismiss pointers created with Views, when needed
*
* @since 1.7
*
* @todo this needs a nonce, the earlier the better
*/

add_action( 'wp_ajax_wpv_dismiss_pointer', 'wpv_dismiss_pointer_callback' );

function wpv_dismiss_pointer_callback() {
	if ( ! isset( $_POST['name'] ) ) {
		echo 'wpv_failure';
		die();
	}
	$pointer = sanitize_key( $_POST['name'] );
	if ( empty( $pointer ) ) {
		echo 'wpv_failure';
		die();
	}
	wpv_dismiss_pointer( $pointer );
	echo 'wpv_success';
	die();
}