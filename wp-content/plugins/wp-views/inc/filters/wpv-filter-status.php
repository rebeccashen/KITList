<?php

/**
* Status filter
*
* @package Views
*
* @since unknown
*/

WPV_Status_Filter::on_load();

/**
* WPV_Search_Filter
*
* Views Status Filter Class
*
* @since 1.7.0
*/

class WPV_Status_Filter {

    static function on_load() {
        add_action( 'init', array( 'WPV_Status_Filter', 'init' ) );
		add_action( 'admin_init', array( 'WPV_Status_Filter', 'admin_init' ) );
    }

    static function init() {
		
    }
	
	static function admin_init() {
		// Register filter in lists and dialogs
		add_filter( 'wpv_filters_add_filter', array( 'WPV_Status_Filter', 'wpv_filters_add_filter_post_status' ), 1, 1 );
		add_action( 'wpv_add_filter_list_item', array( 'WPV_Status_Filter', 'wpv_add_filter_post_status_list_item' ), 1, 1 );
		// AJAX calbacks
		add_action( 'wp_ajax_wpv_filter_post_status_update', array( 'WPV_Status_Filter', 'wpv_filter_post_status_update_callback' ) );
			// TODO This might not be needed here, maybe for summary filter
			add_action( 'wp_ajax_wpv_filter_status_sumary_update', array( 'WPV_Status_Filter', 'wpv_filter_post_status_sumary_update_callback' ) );
		add_action( 'wp_ajax_wpv_filter_post_status_delete', array( 'WPV_Status_Filter', 'wpv_filter_post_status_delete_callback' ) );
		add_filter( 'wpv-view-get-summary', array( 'WPV_Status_Filter', 'wpv_post_status_summary_filter' ), 5, 3 );
		// Register scripts
		add_action( 'admin_enqueue_scripts', array( 'WPV_Status_Filter','admin_enqueue_scripts' ), 20 );
    }
	
	/**
	* admin_enqueue_scripts
	*
	* Register the needed script for this filter
	*
	* @since 1.7
	*/
	
	static function admin_enqueue_scripts( $hook ) {
		wp_register_script( 'views-filter-status-js', ( WPV_URL . "/res/js/redesign/views_filter_status.js" ), array( 'views-filters-js'), WPV_VERSION, true );
		if ( isset( $_GET['page'] ) && $_GET['page'] == 'views-editor' ) {
			wp_enqueue_script( 'views-filter-status-js' );
		}
	}
	
	/**
	* wpv_filters_add_filter_post_status
	*
	* Register the status filter in the popup dialog
	*
	* @param $filters
	*
	* @since unknown
	*/
	
	static function wpv_filters_add_filter_post_status( $filters ) {
		$filters['post_status'] = array(
			'name' => __( 'Post status', 'wpv-views' ),
			'present' => 'post_status',
			'callback' => array( 'WPV_Status_Filter', 'wpv_add_new_filter_status_list_item' )
		);
		return $filters;
	}
	
	/**
	* wpv_add_new_filter_status_list_item
	*
	* Register the status filter in the filters list
	*
	* @since unknown
	*/
	
	static function wpv_add_new_filter_status_list_item() {
		$args = array(
			'post_status' => array()
		);
		WPV_Status_Filter::wpv_add_filter_post_status_list_item( $args );
	}
	
	/**
	* wpv_add_filter_post_status_list_item
	*
	* Render status filter item in the filters list
	*
	* @param $view_settings
	*
	* @since unknown
	*/

	static function wpv_add_filter_post_status_list_item( $view_settings ) {
		if ( isset( $view_settings['post_status'] ) ) {
			$li = WPV_Status_Filter::wpv_get_list_item_ui_post_status( $view_settings );
			WPV_Filter_Item::simple_filter_list_item( 'post_status', 'posts', 'post-status', __( 'Post status filter', 'wpv-views' ), $li );
		}
	}
	
	/**
	* wpv_get_list_item_ui_post_status
	*
	* Render status filter item content in the filters list
	*
	* @param $view_settings
	*
	* @since unknown
	*/
	
	static function wpv_get_list_item_ui_post_status( $view_settings = array() ) {
		if ( ! isset( $view_settings['post_status'] ) || ! is_array( $view_settings['post_status'] ) ) {
			$view_settings['post_status'] = array();
		}
		ob_start();
		?>
		<p class='wpv-filter-post-status-summary js-wpv-filter-summary js-wpv-filter-post-status-summary'>
			<?php echo wpv_get_filter_status_summary_txt( $view_settings ); ?>
		</p>
		<?php
		WPV_Filter_Item::simple_filter_list_item_buttons( 'post-status', 'wpv_filter_post_status_update', wp_create_nonce( 'wpv_view_filter_post_status_nonce' ), 'wpv_filter_post_status_delete', wp_create_nonce( 'wpv_view_filter_post_status_delete_nonce' ) );
		?>
		<div id="wpv-filter-post-status-edit" class="wpv-filter-edit js-wpv-filter-edit">
			<div id="wpv-filter-post-status" class="js-wpv-filter-options js-wpv-filter-post-status-options js-filter-post-status-list">
				<?php WPV_Status_Filter::wpv_render_post_status_options( $view_settings ); ?>
			</div>
		</div>
		<?php
		$res = ob_get_clean();
		return $res;
	}
	
	/**
	* wpv_filter_post_status_update_callback
	*
	* Update status filter callback
	*
	* @since unknown
	*/
	
	static function wpv_filter_post_status_update_callback() {
		$nonce = $_POST["wpnonce"];
		if ( ! wp_verify_nonce( $nonce, 'wpv_view_filter_post_status_nonce') ) {
			die( "Security check" );
		}
		$view_array = get_post_meta( $_POST["id"], '_wpv_settings', true );
		if ( empty( $_POST['filter_options'] ) ) {
			if ( isset( $view_array['post_status'] ) ) {
				unset ( $view_array['post_status'] );
				update_post_meta( $_POST["id"], '_wpv_settings', $view_array );
			}
		} else {
			parse_str( $_POST['filter_options'], $filter_status );
			if ( ! isset( $view_array['post_status'] ) || $view_array['post_status'] != $filter_status['post_status'] ) {
				$view_array['post_status'] = $filter_status['post_status'];
				$result = update_post_meta( $_POST["id"], '_wpv_settings', $view_array );
			}
		}
		if ( ! isset( $filter_status['post_status'] ) ) {
			$filter_status['post_status'] = array();
		}
		echo wpv_get_filter_status_summary_txt( $filter_status );
		die();
	}

	/**
	* Update status filter summary callback
	*/
	
	static function wpv_filter_post_status_sumary_update_callback() {
		$nonce = $_POST["wpnonce"];
		if ( ! wp_verify_nonce( $nonce, 'wpv_view_filter_post_status_nonce' ) ) {
			die( "Security check" );
		}
		parse_str( $_POST['filter_status'], $filter_status );
		if ( ! isset($filter_status['post_status'] ) ) {
			$filter_status['post_status'] = array();
		}
		echo wpv_get_filter_status_summary_txt( $filter_status );
		die();
	}
	
	/**
	* wpv_filter_post_status_delete_callback
	*
	* Delete status filter callback
	*
	* @since unknown
	*/

	static function wpv_filter_post_status_delete_callback() {
		$nonce = $_POST["wpnonce"];
		if ( ! wp_verify_nonce( $nonce, 'wpv_view_filter_post_status_delete_nonce' ) ) {
			die( "Security check" );
		}
		$view_array = get_post_meta( $_POST["id"], '_wpv_settings', true );
		if ( isset( $view_array['post_status'] ) ) {
			unset( $view_array['post_status'] );
		}
		update_post_meta( $_POST["id"], '_wpv_settings', $view_array );
		echo $_POST['id'];
		die();
	}
	
	/**
	* wpv_post_status_summary_filter
	
	* Show the status filter on the View summary
	*
	* @since unknown
	*/
    
	static function wpv_post_status_summary_filter( $summary, $post_id, $view_settings ) {
		if( isset( $view_settings['query_type'] ) && $view_settings['query_type'][0] == 'posts' && isset( $view_settings['post_status'] ) ) {			
			$result = wpv_get_filter_status_summary_txt( $view_settings, true );
			if ( $result != '' && $summary != '' ) {
				$summary .= '<br />';
			}
			$summary .= $result;
		}
		return $summary;
	}
	
	/**
	* wpv_render_status_options
	*
	* Render status filter options
	*
	* @param $view_settings
	*
	* @since unknown
	*/
	
	static function wpv_render_post_status_options( $view_settings = array() ) {
		$statuses = array( 'publish', 'pending', 'draft', 'auto-draft', 'future', 'private', 'inherit', 'trash', 'any' );
		$selected = ( isset( $view_settings['post_status'] ) &&  is_array( $view_settings['post_status'] ) ) ? $view_settings['post_status'] : array() ;
		?>
		<h4><?php  _e( 'Check statuses', 'wpv-views' ); ?></h4>
		<ul class="wpv-filter-options-set wpv-mightlong-list">'
		<?php
		foreach( $statuses as $status ) {
			if ( in_array( $status, $selected ) ) {
				$checked = ' checked="checked"';
			} else {
				$checked = '';
			}
			?>
			<li>
				<input type="checkbox" id="wpv-filter-status-<?php echo $status; ?>" name="post_status[]" value="<?php echo $status; ?>"<?php echo $checked; ?> />
				<label for="wpv-filter-status-<?php echo $status; ?>"><?php echo $status; ?></label>
			</li>
			<?php
		}
		?>
		</ul>
		<?php
	}
    
}