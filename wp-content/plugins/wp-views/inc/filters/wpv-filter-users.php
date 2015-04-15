<?php

/**
* Users filter
*
* @package Views
*
* @since unknown
*/

WPV_Users_Filter::on_load();

/**
* WPV_Users_Filter
*
* Views Users Filter Class
*
* @since 1.7.0
*/

class WPV_Users_Filter {

    static function on_load() {
        add_action( 'init', array( 'WPV_Users_Filter', 'init' ) );
		add_action( 'admin_init', array( 'WPV_Users_Filter', 'admin_init' ) );
    }

    static function init() {
		
    }
	
	static function admin_init() {
		// Register filter in lists and dialogs
		add_action( 'wpv_add_users_filter_list_item', array( 'WPV_Users_Filter', 'wpv_add_filter_users_list_item' ), 1, 1 );
		add_filter( 'wpv_users_filters_add_filter', array( 'WPV_Users_Filter', 'wpv_filters_add_filter_users' ), 1, 1 );
		// AJAX calbacks
		add_action( 'wp_ajax_wpv_filter_users_update', array( 'WPV_Users_Filter', 'wpv_filter_users_update_callback' ) );
			// TODO This might not be needed here, maybe for summary filter
			add_action( 'wp_ajax_wpv_filter_users_sumary_update', array( 'WPV_Users_Filter', 'wpv_filter_users_sumary_update_callback' ) );
		add_action( 'wp_ajax_wpv_filter_users_delete', array( 'WPV_Users_Filter', 'wpv_filter_users_delete_callback' ) );
		add_filter( 'wpv-view-get-summary', array( 'WPV_Users_Filter', 'wpv_users_summary_filter' ), 5, 3 );
		add_action( 'wp_ajax_wpv_suggest_users', array( 'WPV_Users_Filter', 'wpv_suggest_users' ) );
		add_action( 'wp_ajax_nopriv_wpv_suggest_users', array( 'WPV_Users_Filter', 'wpv_suggest_users' ) );
		// Register scripts
		add_action( 'admin_enqueue_scripts', array( 'WPV_Users_Filter','admin_enqueue_scripts' ), 20 );
	}
	
	/**
	* admin_enqueue_scripts
	*
	* Register the needed script for this filter
	*
	* @since 1.7
	*/
	
	static function admin_enqueue_scripts( $hook ) {
		wp_register_script( 'views-filter-users-js', ( WPV_URL . "/res/js/redesign/views_filter_users.js" ), array( 'suggest', 'views-filters-js'), WPV_VERSION, true );
		if ( isset( $_GET['page'] ) && $_GET['page'] == 'views-editor' ) {
			wp_enqueue_script( 'views-filter-users-js' );
		}
	}
	
	/**
	* wpv_filters_add_filter_users
	*
	* Register the users filter in the popup dialog
	*
	* @param $filters
	*
	* @since unknown
	*/
	
	static function wpv_filters_add_filter_users( $filters ) {
		$filters['users'] = array(
			'name' => __( 'Specific users', 'wpv-views' ),
			'present' => 'users_mode',
			'callback' => array( 'WPV_Users_Filter', 'wpv_add_new_filter_users_list_item' )
		);
		return $filters;
	}

	/**
	* wpv_add_new_filter_users_list_item
	*
	* Register the users filter in the filters list
	*
	* @param $taxonomy_type array
	*
	* @since unknown
	*/

	static function wpv_add_new_filter_users_list_item() {
		$args = array(
			'users_mode' => array( 'this_user' )
		);
		WPV_Users_Filter::wpv_add_filter_users_list_item( $args );
	}
	
	/**
	* wpv_add_filter_users_list_item
	*
	* Render users filter item in the filters list
	*
	* @param $view_settings
	*
	* @since unknown
	*/

	static function wpv_add_filter_users_list_item( $view_settings ) {
		if ( isset( $view_settings['users_mode'][0] ) ) {
			$li = WPV_Users_Filter::wpv_get_list_item_ui_users( $view_settings );
			WPV_Filter_Item::simple_filter_list_item( 'users', 'users', 'users', __( 'Users filter', 'wpv-views' ), $li );
		}
	}
	
	/**
	* wpv_get_list_item_ui_users
	*
	* Render users filter item content in the filters list
	*
	* @param $view_settings
	*
	* @since unknown
	*/

	static function wpv_get_list_item_ui_users( $view_settings = array() ) {
		if ( isset( $view_settings['users_mode'] ) && is_array( $view_settings['users_mode'] ) ) {
			$view_settings['users_mode'] = $view_settings['users_mode'][0];
		}
		ob_start();
		?>
		<p class='wpv-filter-users-edit-summary js-wpv-filter-summary js-wpv-filter-users-summary'>
			<?php echo wpv_get_filter_users_summary_txt( $view_settings ); ?>
		</p>
		<?php
		WPV_Filter_Item::simple_filter_list_item_buttons( 'users', 'wpv_filter_users_update', wp_create_nonce( 'wpv_view_filter_users_nonce' ), 'wpv_filter_users_delete', wp_create_nonce( 'wpv_view_filter_users_delete_nonce' ) );
		?>
		<div id="wpv-filter-users-edit" class="wpv-filter-users-edit wpv-filter-edit js-wpv-filter-edit">
			<div id="wpv-filter-users" class="js-wpv-filter-options js-wpv-filter-users-options">
				<?php WPV_Users_Filter::wpv_render_users_options( $view_settings ); ?>
			</div>
			<div class="js-wpv-filter-toolset-messages"></div>
		</div>
		<?php
		$res = ob_get_clean();
		return $res;
	}

	/**
	* wpv_filter_users_update_callback
	*
	* Update users filter callback
	*
	* @since unknown
	*/

	static function wpv_filter_users_update_callback() {
		$nonce = $_POST["wpnonce"];
		if ( ! wp_verify_nonce( $nonce, 'wpv_view_filter_users_nonce' ) ) {
			die( "Security check" );
		}
		if ( empty( $_POST['filter_options'] ) ) {
			echo $_POST['id'];
			die();
		}
		parse_str( $_POST['filter_options'], $filter_users );
		$change = false;
		$view_array = get_post_meta( $_POST["id"], '_wpv_settings', true );
		if ( ! isset( $filter_users['users_name'] ) || '' == $filter_users['users_name'] ) {
			$filter_users['users_name'] = '';
			$filter_users['users_id'] = 0;
		}
		$settings_to_check = array(
			'users_query_in',
			'users_mode',
			'users_name',
			'users_id',
			'users_url_type',
			'users_url',
			'users_shortcode_type',
			'users_shortcode'
		);
		foreach ( $settings_to_check as $set ) {
			if ( ! isset( $view_array[$set] ) || $filter_users[$set] != $view_array[$set] ) {
				$change = true;
				$view_array[$set] = $filter_users[$set];
			}
		}
		if ( $change ) {
			$result = update_post_meta( $_POST["id"], '_wpv_settings', $view_array );
		}
		$filter_users['users_mode'] = $filter_users['users_mode'][0];
		echo wpv_get_filter_users_summary_txt( $filter_users );
		die();
	}

	static function wpv_filter_users_sumary_update_callback() {
		$nonce = $_POST["wpnonce"];
		if ( ! wp_verify_nonce( $nonce, 'wpv_view_filter_users_nonce' ) ) {
			die( "Security check" );
		}
		parse_str( $_POST['filter_users'], $filter_users );
		$filter_users['users_mode'] = $filter_users['users_mode'][0];
		echo wpv_get_filter_users_summary_txt( $filter_users );
		die();
	}
	
	/**
	* wpv_filter_users_delete_callback
	*
	* Delete users filter callback
	*
	* @since unknown
	*/

	static function wpv_filter_users_delete_callback() {
		$nonce = $_POST["wpnonce"];
		if ( ! wp_verify_nonce( $nonce, 'wpv_view_filter_users_delete_nonce' ) ) {
			die( "Security check" );
		}
		$view_array = get_post_meta( $_POST["id"], '_wpv_settings', true );
		$settings_to_check = array(
			'users_query_in',
			'users_mode',
			'users_name',
			'users_id',
			'users_url_type',
			'users_url',
			'users_shortcode_type',
			'users_shortcode'
		);
		foreach ( $settings_to_check as $index ) {
			if ( isset( $view_array[$index] ) ) {
				unset( $view_array[$index] );
			}
		}
		update_post_meta( $_POST["id"], '_wpv_settings', $view_array );
		echo $_POST['id'];
		die();
	}
	
	/**
	* wpv_users_summary_filter
	
	* Show the users filter on the View summary
	*
	* @since unknown
	*/

	static function wpv_users_summary_filter( $summary, $post_id, $view_settings ) {
		if( isset( $view_settings['query_type'] ) && $view_settings['query_type'][0] == 'users' && isset( $view_settings['users_mode'] ) ) {
			$view_settings['users_mode'] = $view_settings['users_mode'][0];
			$result = wpv_get_filter_users_summary_txt( $view_settings, true );
			if ( $result != '' && $summary != '' ) {
				$summary .= '<br />';
			}
			$summary .= $result;
		}
		return $summary;
	}
	
	/**
	* wpv_render_taxonomy_term_options
	*
	* Render users filter options
	*
	* @param $view_settings
	*
	* @since unknown
	*/

	static function wpv_render_users_options( $view_settings = array() ) {
		global $wpdb; // TODO this global seems not used anymore
		$defaults = array(
			'users_query_in' => 'include',
			'users_mode' => 'this_user',
			'users_name' =>'',
			'users_id' => 0,
			'users_url' => 'users-filter',
			'users_url_type' => '',
			'users_shortcode' => 'users',
			'users_shortcode_type' => ''
		);
		$view_settings = wp_parse_args( $view_settings, $defaults );
		$view_id = '';
		// data-viewid is not used anuwhere, so...
		//----------
		if ( isset($_GET['view_id']) ){
			$view_id = $_GET['view_id'];
		}
		if ( isset($_POST['view_id']) ){
			$view_id = $_POST['view_id'];
		}
		//----------
		?>
		<h4><?php  _e( 'Include or exclude users', 'wpv-views' ); ?></h4>
		<ul class="wpv-filter-options-set">
			<li>
				<input type="radio" id="users-query-in-include" name="users_query_in" <?php checked( $view_settings['users_query_in'], 'include' ); ?> class="users-query-in js-wpv-users-query-in" value="include" />
				<label for="users-query-in-include"><?php echo __('Only list users who met the filter criteria', 'wpv-views'); ?></label>
			</li>
			<li>
				<input type="radio" id="users-query-in-exclude" name="users_query_in" <?php checked( $view_settings['users_query_in'], 'exclude' ); ?> class="users-query-in js-wpv-users-query-in" value="exclude" />
				<label for="users-query-in-exclude"><?php echo __('List all users but the ones who met the criteria', 'wpv-views'); ?></label>
			</li>
		</ul>
		<!--	<ul>
			<?php //$radio_name = $edit ? '_wpv_settings[users_mode][]' : 'users_mode[]' ?>
			<li>
				<?php //$checked = $view_settings['users_query_in'] == 'include' ? 'checked="checked"' : ''; ?>
				<label>
					<input type="radio" name="users_query_in" value="include" <?php //echo $checked; ?> />
					<?php //_e('Specific users list', 'wpv-views'); ?>
				</label>
			</li>
				<?php //$checked = $view_settings['users_query_in'] == 'exclude' ? 'checked="checked"' : ''; ?>
				<label>
					<input type="radio" name="users_query_in" value="exclude" <?php //echo $checked; ?> />
					<?php //_e('All users except a specific list of users', 'wpv-views'); ?>
				</label>
			</li>
		</ul>-->
		<h4><?php  _e( 'Criteria to filter', 'wpv-views' ); ?></h4>
		<ul class="wpv-filter-options-set">
			<li>
				<input type="radio" id="users-mode-this-user" name="users_mode[]" value="this_user" <?php checked( $view_settings['users_mode'], 'this_user' ); ?> />
				<label for="users-mode-this-user"><?php _e('Users with this display name ', 'wpv-views'); ?></label>
				<input id="wpv_users_name" class="users_suggest js-users-suggest" type='hidden' name="users_name" value="<?php echo $view_settings['users_name']; ?>" size="15" />
				<input id="wpv_users" class="users_suggest_id js-users-suggest-id" type='text' name="users_id" value="<?php echo $view_settings['users_id']; ?>" size="10" />
			</li>
			<li>
				<input type="radio" id="users-mode-by-url" name="users_mode[]" value="by_url" <?php checked( $view_settings['users_mode'], 'by_url' ); ?> />
				<label for="users-mode-by-url"><?php _e('Users with ', 'wpv-views'); ?></label>
				<select id="wpv_users_url_type" name="users_url_type">
					<?php
					$selected_type = $view_settings['users_url_type'] == 'id' ? ' selected="selected"' : '';
					echo '<option value="id"' . $selected_type . '>' . __('ID', 'wpv-views') . '</option>';
					$selected_type = $view_settings['users_url_type'] == 'username' ? ' selected="selected"' : '';
					echo '<option value="username"' . $selected_type . '>' . __('username', 'wpv-views') . '</option>';
					?>
				</select>
				<label for="users-url"><?php _e(' set by this URL parameter: ', 'wpv-views'); ?></label>
				<input type='text' id="users-url" class="js-wpv-filter-users-url js-wpv-filter-validate" data-type="url" data-class="js-wpv-filter-users-url" name="users_url" value="<?php echo $view_settings['users_url']; ?>" size="10" />
			</li>
			<li>
				<input type="radio" id="users-mode-shortcode" name="users_mode[]" value="shortcode" <?php checked( $view_settings['users_mode'], 'shortcode' ); ?>>
				<label for="users-mode-shortcode"><?php _e('Users with ', 'wpv-views'); ?></label>
				<select id="wpv_users_shortcode_type" name="users_shortcode_type">
				<?php
				$selected_type = $view_settings['users_shortcode_type'] == 'id' ? ' selected="selected"' : '';
				echo '<option value="id"' . $selected_type . '>' . __('ID', 'wpv-views') . '</option>';
				$selected_type = $view_settings['users_shortcode_type'] == 'username' ? ' selected="selected"' : '';
				echo '<option value="username"' . $selected_type . '>' . __('username', 'wpv-views') . '</option>';
				?>
				</select>
				<label for="users-shortcode"><?php _e(' set by this View shortcode attribute: ', 'wpv-views'); ?></label>
				<input type='text' id="users-shortcode" class="js-wpv-filter-users-shortcode js-wpv-filter-validate" data-type="shortcode" data-class="js-wpv-filter-users-shortcode" name="users_shortcode" value="<?php echo $view_settings['users_shortcode']; ?>" size="10" />
			</li>
		</ul>
		<?php
			$users = array();
			$ids = explode( ',', $view_settings['users_id']);
					if ( count( $ids ) !== 0){

						$names = explode( ',', $view_settings['users_name']);
						for ( $i=0; $i<count($ids); $i++){
							if ($ids[$i] != 0){
							$users[] =array('id'=>$ids[$i],'name'=>$names[$i]);
							}
						}

			}

		?>
		<input type="hidden" value="" class="js-wpv-user-suggest-values" data-hinttext="<?php _e('Type for search users', 'wpv-views'); ?>..."
		data-noresult="<?php _e('No users matched your criteria', 'wpv-views'); ?>"
		data-search="<?php _e('Searching', 'wpv-views'); ?>..."
		data-viewid="<?php echo $view_id;?>"
		data-users = '<?php echo json_encode($users)?>'
		/>
		<div class="filter-helper js-wpv-users-helper"></div>
		<?php
	}
	
	/**
	* wpv_suggest_users
	*
	* Suggest users
	*
	* @since unknown
	*/
	
	static function wpv_suggest_users() {
		global $wpdb; // TODO this global is not needed anymore, it seems
		$_view_settings = get_post_meta( $_GET['view_id'], '_wpv_settings', true );
		$query_type = 'administrator';
		if ( isset( $_view_settings['roles_type'][0] ) ) {
			$query_type = $_view_settings['roles_type'][0];
		}
		$user = '*'.wpv_esc_like( $_REQUEST['q'] ).'*';
		$response = array();
		$args = array(
			'search'         => $user,
			'search_columns' => array( 'user_login', 'user_email' ),
			'number' => 20
		);
		if ( $query_type != 'any' ) {
			$args['role'] = $query_type;
		}
		$user_query = new WP_User_Query( $args );
		if ( ! empty( $user_query->results ) ) {
			foreach ( $user_query->results as $user ) {
				$response[] = array('id'=> $user->ID, 'name'=> $user->display_name );
			}
		}
		$json_response = json_encode($response);
		echo $json_response;
		die();
	}
	
}

// DEPRECATED
// New filter in the wpv-sections-query-type.php file
// Also, too much information here: create specific filters for that

// add_filter('wpv-view-get-content-summary', 'wpv_users_content_summary_filter', 5, 3);

// Commented out in 1.7.0
/*
function wpv_users_content_summary_filter($summary, $post_id, $view_settings) {
    $summary = '';
    $result = '';
    $result1 = '';
    if(!isset($view_settings['query_type']) || (isset($view_settings['query_type']) && $view_settings['query_type'][0] == 'users')) {
    
            $user_role = '';
            $result = wpv_get_filter_users_summary_txt($view_settings , false, $post_id);
            if ( empty($result) ){
                if ( isset($view_settings['roles_type'][0]) ){
                    $user_role = $view_settings['roles_type'][0];
                }
                $result = sprintf(__('Select all users with role <strong>%s</strong>', 'wpv-views'),  $user_role);
            }
            $result1 = wpv_get_filter_users_summary_txt_addon( $view_settings );
            $summary = $result . $result1;
	
    }

    return $summary;
}

function wpv_get_filter_users_summary_txt_addon( $view_settings ){
    
    $output = '';
    $summary = '';
    foreach (array_keys($view_settings) as $key) {
            if (strpos($key, 'usermeta-field-') === 0 && strpos($key, '_compare') === strlen($key) - strlen('_compare')) {
                $name = substr($key, 0, strlen($key) - strlen('_compare'));
                if ($summary != '') {
                    if ($view_settings['usermeta_fields_relationship'] == 'OR') {
                        $summary .= __(' OR', 'wpv-views');
                    } else {
                        $summary .= __(' AND', 'wpv-views');
                    }
                }
                $summary .= wpv_get_usermeta_field_summary($name, $view_settings);
            }
    }
    if ( !empty($summary) ){
        $output .= __(' and ', 'wpv-views'). $summary;
    }
    if ( isset($view_settings['users_orderby']) ){
    	$output .=  __(' ordered by ', 'wpv-views'). $view_settings['users_orderby'];
	}
    $order = __('descending', 'wpv-views');
    if ( isset($view_settings['users_order']) && $view_settings['users_order'] == 'ASC') {
        $order = __('ascending', 'wpv-views');
    }
    $output .= ', '.$order;
    if ( isset($view_settings['users_limit']) && intval($view_settings['users_limit']) != -1 ) {
            if (intval($view_settings['users_limit']) == 1) {
                $output .= __(', limit to 1 item', 'wpv-views');
            } else {
                $output .= sprintf(__(', limit to %d items', 'wpv-views'),
                        intval($view_settings['users_limit']));
            }
    }
    if ( isset($view_settings['users_limit']) && intval($view_settings['users_offset']) != 0 ) {
            if (intval($view_settings['users_limit']) == 1) {
                $output .= __(', skip first item', 'wpv-views');
            } else {
                $output .= sprintf(__(', skip %d items', 'wpv-views'),
                        intval($view_settings['users_offset']));
            }
    }
    return $output;    
}
*/