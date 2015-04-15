<?php

/**
* ID filter
*
* @package Views
*
* @since unknown
*/

WPV_ID_Filter::on_load();

/**
* WPV_ID_Filter
*
* Views ID Filter Class
*
* @since 1.7.0
*/

class WPV_ID_Filter {

    static function on_load() {
        add_action( 'init', array( 'WPV_ID_Filter', 'init' ) );
		add_action( 'admin_init', array( 'WPV_ID_Filter', 'admin_init' ) );
    }

    static function init() {
		
    }
	
	static function admin_init() {
		// Register filter in lists and dialogs
		add_filter( 'wpv_filters_add_filter', array( 'WPV_ID_Filter', 'wpv_filters_add_filter_post_id' ), 1, 1 );
		add_action( 'wpv_add_filter_list_item', array( 'WPV_ID_Filter', 'wpv_add_filter_post_id_list_item' ), 1, 1 );
		// AJAX calbacks
		add_action( 'wp_ajax_wpv_filter_post_id_update', array( 'WPV_ID_Filter', 'wpv_filter_post_id_update_callback' ) );
			// TODO This might not be needed here, maybe for summary filter
			add_action( 'wp_ajax_wpv_filter_id_sumary_update', array( 'WPV_ID_Filter', 'wpv_filter_id_sumary_update_callback' ) );
		add_action( 'wp_ajax_wpv_filter_post_id_delete', array( 'WPV_ID_Filter', 'wpv_filter_post_id_delete_callback' ) );
		add_filter( 'wpv-view-get-summary', array( 'WPV_ID_Filter', 'wpv_post_id_summary_filter' ), 5, 3 );
		// Register scripts
		add_action( 'admin_enqueue_scripts', array( 'WPV_ID_Filter','admin_enqueue_scripts' ), 20 );
	}
	
	/**
	* admin_enqueue_scripts
	*
	* Register the needed script for this filter
	*
	* @since 1.7
	*/
	
	static function admin_enqueue_scripts( $hook ) {
		wp_register_script( 'views-filter-id-js', ( WPV_URL . "/res/js/redesign/views_filter_id.js" ), array( 'suggest', 'views-filters-js'), WPV_VERSION, true );
		if ( isset( $_GET['page'] ) && $_GET['page'] == 'views-editor' ) {
			wp_enqueue_script( 'views-filter-id-js' );
		}
	}
	
	/**
	* wpv_filters_add_filter_post_id
	*
	* Register the ID filter in the popup dialog
	*
	* @param $filters
	*
	* @since unknown
	*/

	static function wpv_filters_add_filter_post_id( $filters ) {
		$filters['post_id'] = array(
			'name' => __( 'Post id', 'wpv-views' ),
			'present' => 'id_mode',
			'callback' => array( 'WPV_ID_Filter', 'wpv_add_new_filter_post_id_list_item' )
		);
		return $filters;
	}
	
	/**
	* wpv_add_new_filter_post_id_list_item
	*
	* Register the ID filter in the filters list
	*
	* @since unknown
	*/

	static function wpv_add_new_filter_post_id_list_item() {
		$args = array(
			'id_in_or_out' => 'in',
			'id_mode' => array( 'by_ids' )
		);
		WPV_ID_Filter::wpv_add_filter_post_id_list_item( $args );
	}
	
	/**
	* wpv_add_filter_post_id_list_item
	*
	* Render ID filter item in the filters list
	*
	* @param $view_settings
	*
	* @since unknown
	*/

	static function wpv_add_filter_post_id_list_item( $view_settings ) {
		if ( isset( $view_settings['id_mode'][0] ) ) {
			$li = WPV_ID_Filter::wpv_get_list_item_ui_post_id( $view_settings );
			WPV_Filter_Item::simple_filter_list_item( 'post_id', 'posts', 'post-id', __( 'Post ID filter', 'wpv-views' ), $li );
		}
	}
	
	/**
	* wpv_get_list_item_ui_post_id
	*
	* Render ID filter item content in the filters list
	*
	* @param $view_settings
	*
	* @since unknown
	*/

	static function wpv_get_list_item_ui_post_id( $view_settings = array() ) {
		global $wpdb, $sitepress;
		if ( isset( $view_settings['id_mode'] ) && is_array( $view_settings['id_mode'] ) ) {
			$view_settings['id_mode'] = $view_settings['id_mode'][0];
		}
		if ( isset( $sitepress ) && function_exists( 'icl_object_id' ) && isset( $view_settings['post_id_ids_list'] ) && ! empty( $view_settings['post_id_ids_list'] ) ) {
			// Adjust for WPML support
			$id_ids_list = array_map( 'trim', explode( ',', $view_settings['post_id_ids_list'] ) );
			$trans_ids = array();
			foreach ( $id_ids_list as $id_item ) {
				if ( !empty( $id_item ) ) {
					$target_post_type = $wpdb->get_var("SELECT post_type FROM {$wpdb->posts} WHERE ID='{$id_item}'");
					if ( $target_post_type ) {
						$trans_ids[] = icl_object_id( $id_item, $target_post_type, true );
					}
				}
			}
			if ( count( $trans_ids ) > 0 ) {
				$view_settings['post_id_ids_list'] = implode( ",", $trans_ids );
			}
			
		}
		ob_start();
		?>
		<p class='wpv-filter-post-id-edit-summary js-wpv-filter-summary js-wpv-filter-post-id-summary'>
			<?php echo wpv_get_filter_post_id_summary_txt( $view_settings ); ?>
		</p>
		<?php
		WPV_Filter_Item::simple_filter_list_item_buttons( 'post-id', 'wpv_filter_post_id_update', wp_create_nonce( 'wpv_view_filter_post_id_nonce' ), 'wpv_filter_post_id_delete', wp_create_nonce( 'wpv_view_filter_post_id_delete_nonce' ) );
		?>
		<div id="wpv-filter-post-id-edit" class="wpv-filter-edit js-wpv-filter-edit" style="padding-bottom:28px;">
			<div id="wpv-filter-post-id" class="js-wpv-filter-options js-wpv-filter-post-id-options">
				<?php WPV_ID_Filter::wpv_render_post_id_options( $view_settings ); ?>
			</div>
			<div class="js-wpv-filter-toolset-messages"></div>
			<span class="filter-doc-help">
				<?php echo sprintf(__('%sLearn about filtering by Post ID%s', 'wpv-views'),
					'<a class="wpv-help-link" href="' . WPV_FILTER_BY_POST_ID_LINK . '" target="_blank">',
					' &raquo;</a>'
				); ?>
			</span>
		</div>
		<?php
		$res = ob_get_clean();
		return $res;
	}
	
	/**
	* wpv_filter_post_id_update_callback
	*
	* Update ID filter callback
	*
	* @since unknown
	*/

	static function wpv_filter_post_id_update_callback() {
		$nonce = $_POST["wpnonce"];
		if ( ! wp_verify_nonce( $nonce, 'wpv_view_filter_post_id_nonce') ) {
			die( "Security check" );
		}
		if ( empty( $_POST['filter_options'] ) ) {
			echo $_POST['id'];
			die();
		}
		parse_str( $_POST['filter_options'], $filter_id );
		$change = false;
		$view_array = get_post_meta( $_POST["id"], '_wpv_settings', true );
		if ( ! isset( $filter_id['post_id_ids_list'] ) || '' == $filter_id['post_id_ids_list'] ) {
			$filter_id['post_id_ids_list'] = '';
		}
		$settings_to_check = array( 'id_in_or_out', 'id_mode', 'post_id_ids_list', 'post_ids_url', 'post_ids_shortcode' );
		foreach ( $settings_to_check as $set ) {
			if ( ! isset( $view_array[$set] ) || $filter_id[$set] != $view_array[$set] ) {
				$change = true;
				$view_array[$set] = $filter_id[$set];
			}
		}
		if ( $change ) {
			$result = update_post_meta( $_POST["id"], '_wpv_settings', $view_array );
		}
		$filter_id['id_mode'] = $filter_id['id_mode'][0];
		echo wpv_get_filter_post_id_summary_txt( $filter_id );
		die();
	}
	
	/**
	* Update ID filter summary callback
	*/

	static function wpv_filter_id_sumary_update_callback() {
		$nonce = $_POST["wpnonce"];
		if ( ! wp_verify_nonce($nonce, 'wpv_view_filter_id_nonce' ) ) {
			die( "Security check" );
		}
		parse_str( $_POST['filter_id'], $filter_id );
		$filter_id['id_mode'] = $filter_id['id_mode'][0];
		echo wpv_get_filter_post_id_summary_txt( $filter_id );
		die();
	}
	
	/**
	* wpv_filter_post_id_delete_callback
	*
	* Delete ID filter callback
	*
	* @since unknown
	*/

	static function wpv_filter_post_id_delete_callback() {
		$nonce = $_POST["wpnonce"];
		if ( ! wp_verify_nonce( $nonce, 'wpv_view_filter_post_id_delete_nonce' ) ) {
			die( "Security check" );
		}
		$view_array = get_post_meta( $_POST["id"], '_wpv_settings', true );
		$settings_to_check = array( 'id_in_or_out', 'id_mode', 'post_id_ids_list', 'post_ids_url', 'post_ids_shortcode' );
		foreach ( $settings_to_check as $set ) {
			if ( isset( $view_array[$set] ) ) {
				unset( $view_array[$set] );
			}
		}
		update_post_meta( $_POST["id"], '_wpv_settings', $view_array );
		echo $_POST['id'];
		die();
	}
    
	/**
	* wpv_post_id_summary_filter
	
	* Show the ID filter on the View summary
	*
	* @since unknown
	*/

	static function wpv_post_id_summary_filter( $summary, $post_id, $view_settings ) {
		if( isset( $view_settings['query_type'] ) && $view_settings['query_type'][0] == 'posts' && isset( $view_settings['id_mode'] ) ) {
			$view_settings['id_mode'] = $view_settings['id_mode'][0];
			$result = wpv_get_filter_post_id_summary_txt( $view_settings, true );
			if ( $result != '' && $summary != '' ) {
				$summary .= '<br />';
			}
			$summary .= $result;
		}
		return $summary;
	}

	/**
	* wpv_render_post_id_options
	*
	* Render ID filter options
	*
	* @param $view_settings
	*
	* @since unknown
	*/

	static function wpv_render_post_id_options( $view_settings = array() ) {
		$defaults = array(
			'id_in_or_out' => 'in',
			'id_mode' => 'by_ids',
			'post_id_ids_list' =>'',
			'post_ids_url' => 'post_ids',
			'post_ids_shortcode' => 'ids'
		);
		$view_settings = wp_parse_args( $view_settings, $defaults );
		?>
		<h4><?php _e( 'Include or exclude', 'wpv-views' ); ?></h4>
		<div class="wpv-filter-options-set">
			<label for="id_in_or_out"><?php _e('The View will filter posts to', 'wpv-views'); ?></label>
			<select id="id_in_or_out" name="id_in_or_out" class="js_id_in_or_out">
				<option value="in" <?php selected( 'in', $view_settings['id_in_or_out'] ); ?>><?php _e('include', 'wpv-views'); ?></option>
				<option value="out" <?php selected( 'out', $view_settings['id_in_or_out'] ); ?>><?php _e('exclude', 'wpv-views'); ?></option>
			</select>
		</div>
		<h4><?php _e( 'How to filter', 'wpv-views' ); ?></h4>
		<ul class="wpv-filter-options-set">
			<li>
				<input type="radio" id="wpv-filter-id-list" name="id_mode[]" value="by_ids" <?php checked( $view_settings['id_mode'], 'by_ids' ); ?> />
				<label for="wpv-filter-id-list"><?php _e( 'Posts with those IDs: ', 'wpv-views' ); ?></label>
				<input type='text' name="post_id_ids_list" value="<?php echo esc_attr( $view_settings['post_id_ids_list'] ); ?>" size="15" />
			</li>
			<li>
				<input type="radio" id="wpv-filter-id-url" name="id_mode[]" value="by_url" <?php checked( $view_settings['id_mode'], 'by_url' ); ?>>
				<label for="wpv-filter-id-url"><?php _e('Posts with IDs set by this URL parameter: ', 'wpv-views'); ?></label>
				<input type='text' class="js-wpv-filter-id-url js-wpv-filter-validate" data-type="url" data-class="js-wpv-filter-id-url" name="post_ids_url" value="<?php echo $view_settings['post_ids_url']; ?>" size="10" />
			</li>
			<li>
				<input type="radio" id="wpv-filter-id-shortcode" name="id_mode[]" value="shortcode" <?php checked( $view_settings['id_mode'], 'shortcode' ); ?>>
				<label for="wpv-filter-id-shortcode"><?php _e('Posts with IDs set by the View shortcode attribute: ', 'wpv-views'); ?></label>
				<input type='text' class="js-wpv-filter-id-shortcode js-wpv-filter-validate" data-type="shortcode" data-class="js-wpv-filter-id-shortcode" name="post_ids_shortcode" value="<?php echo $view_settings['post_ids_shortcode']; ?>" size="10" />
			</li>
		</ul>
		<div class="wpv_id_helper"></div>
		<?php
	}
	
}