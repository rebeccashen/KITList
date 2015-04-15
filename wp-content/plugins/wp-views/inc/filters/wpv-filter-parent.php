<?php

/**
* Search filter & taxonomy search filter
*
* @package Views
*
* @since unknown
*/

WPV_Parent_Filter::on_load();

/**
* WPV_Search_Filter
*
* Views Search Filter Class
*
* @since 1.7.0
*/

class WPV_Parent_Filter {

    static function on_load() {
        add_action( 'init', array( 'WPV_Parent_Filter', 'init' ) );
		add_action( 'admin_init', array( 'WPV_Parent_Filter', 'admin_init' ) );
    }

    static function init() {
		
    }
	
	static function admin_init() {
		// Register filters in lists and dialogs
		add_filter( 'wpv_filters_add_filter', array( 'WPV_Parent_Filter', 'wpv_filters_add_filter_post_parent' ), 1, 1 );
		add_action( 'wpv_add_filter_list_item', array( 'WPV_Parent_Filter', 'wpv_add_filter_post_parent_list_item' ), 1, 1 );
		add_filter( 'wpv_taxonomy_filters_add_filter', array( 'WPV_Parent_Filter', 'wpv_filters_add_filter_taxonomy_parent' ), 1, 2 );
		add_action( 'wpv_add_taxonomy_filter_list_item', array( 'WPV_Parent_Filter', 'wpv_add_filter_taxonomy_parent_list_item' ), 1, 1 );
		//AJAX callbakcks
		add_action('wp_ajax_wpv_filter_post_parent_update', array( 'WPV_Parent_Filter', 'wpv_filter_post_parent_update_callback') );
			// TODO This might not be needed here, maybe for summary filter
			add_action('wp_ajax_wpv_filter_parent_sumary_update', array( 'WPV_Parent_Filter', 'wpv_filter_post_parent_sumary_update_callback') );
		add_action('wp_ajax_wpv_filter_post_parent_delete', array( 'WPV_Parent_Filter', 'wpv_filter_post_parent_delete_callback') );
		add_action( 'wp_ajax_wpv_filter_taxonomy_parent_update', array( 'WPV_Parent_Filter', 'wpv_filter_taxonomy_parent_update_callback' ) );
			// TODO This might not be needed here, maybe for summary filter
			add_action( 'wp_ajax_wpv_filter_taxonomy_parent_sumary_update', array( 'WPV_Parent_Filter', 'wpv_filter_taxonomy_parent_sumary_update_callback' ) );
		add_action(	'wp_ajax_wpv_filter_taxonomy_parent_delete', array( 'WPV_Parent_Filter', 'wpv_filter_taxonomy_parent_delete_callback' ) );
		add_filter( 'wpv-view-get-summary', array( 'WPV_Parent_Filter', 'wpv_parent_summary_filter' ), 5, 3 );
		add_action( 'wp_ajax_wpv_get_post_parent_post_select', array( 'WPV_Parent_Filter', 'wpv_get_post_parent_post_select_callback' ) );
		add_action( 'wp_ajax_update_taxonomy_parent_id_dropdown', array( 'WPV_Parent_Filter', 'update_taxonomy_parent_id_dropdown' ) );
		// Register scripts
		add_action( 'admin_enqueue_scripts', array( 'WPV_Parent_Filter','admin_enqueue_scripts' ), 20 );
	}
	
	/**
	* admin_enqueue_scripts
	*
	* Register the needed script for this filter
	*
	* @since 1.7
	*/
	
	static function admin_enqueue_scripts( $hook ) {
		wp_register_script( 'views-filter-parent-js', ( WPV_URL . "/res/js/redesign/views_filter_parent.js" ), array( 'views-filters-js'), WPV_VERSION, true );
		if ( isset( $_GET['page'] ) && $_GET['page'] == 'views-editor' ) {
			wp_enqueue_script( 'views-filter-parent-js' );
			$parent_strings = array(
				'post_type_missing' => __('There is no post type selected in the Content Selection section', 'wpv-views'),
				'post_type_flat' => __('This will filter out posts of the following types, because they are not hierarchical:', 'wpv-views'),
				'taxonomy_missing' => __('There is no taxonomy selected in the Content Selection section', 'wpv-views'),
				'taxonomy_flat' => __('The taxonomy selected in the Content Selection section is not hierarchical; this filter will not work for', 'wpv-views'),
				'taxonomy_changed' => __('The taxonomy selected in the Content Selection section has changed, so this filter might need some changes', 'wpv-views')
			);
			wp_localize_script( 'views-filter-parent-js', 'wpv_parent_strings', $parent_strings );
		}
	}
	
	//-----------------------
	// Parent filter
	//-----------------------
	
	/**
	* wpv_filters_add_filter_post_parent
	*
	* Register the parent filter in the popup dialog
	*
	* @param $filters
	*
	* @since unknown
	*/

	static function wpv_filters_add_filter_post_parent( $filters ) {
		$filters['post_parent'] = array(
			'name' => __( 'Post parent', 'wpv-views' ),
			'present' => 'parent_mode',
			'callback' => array( 'WPV_Parent_Filter', 'wpv_add_new_filter_post_parent_list_item' )
		);
		return $filters;
	}
	
	/**
	* wpv_add_new_filter_post_parent_list_item
	*
	* Register the parent filter in the filters list
	*
	* @since unknown
	*/

	static function wpv_add_new_filter_post_parent_list_item() {
		$args = array(
			'parent_mode' => array( 'current_page' )
		);
		WPV_Parent_Filter::wpv_add_filter_post_parent_list_item( $args );
	}
	
	/**
	* wpv_add_filter_post_parent_list_item
	*
	* Render parent filter item in the filters list
	*
	* @param $view_settings
	*
	* @since unknown
	*/

	static function wpv_add_filter_post_parent_list_item( $view_settings ) {
		if ( isset( $view_settings['parent_mode'][0] ) ) {
			$li = WPV_Parent_Filter::wpv_get_list_item_ui_post_parent( $view_settings );
			WPV_Filter_Item::simple_filter_list_item( 'post_parent', 'posts', 'post-parent', __( 'Post parent filter', 'wpv-views' ), $li );
		}
	}
	
	/**
	* wpv_get_list_item_ui_post_parent
	*
	* Render parent filter item content in the filters list
	*
	* @param $view_settings
	*
	* @since unknown
	*/

	static function wpv_get_list_item_ui_post_parent( $view_settings = array() ) {
		global $sitepress;
		if ( isset( $view_settings['parent_mode'] ) && is_array( $view_settings['parent_mode'] ) ) {
			$view_settings['parent_mode'] = $view_settings['parent_mode'][0];
		}
		if ( isset( $sitepress ) && function_exists( 'icl_object_id' ) && isset( $view_settings['parent_id'] ) && !empty( $view_settings['parent_id'] ) ) {
			// Adjust for WPML support
			$target_post_type = get_post_type(  $view_settings['parent_id'] );
			if ( $target_post_type ) {
				$view_settings['parent_id'] = icl_object_id( $view_settings['parent_id'], $target_post_type, true );
			}
		}
		ob_start();
		?>
		<p class='wpv-filter-parent-edit-summary js-wpv-filter-summary js-wpv-filter-post-parent-summary'>
			<?php echo wpv_get_filter_post_parent_summary_txt( $view_settings ); ?>
		</p>
		<?php
		WPV_Filter_Item::simple_filter_list_item_buttons( 'post-parent', 'wpv_filter_post_parent_update', wp_create_nonce( 'wpv_view_filter_post_parent_nonce' ), 'wpv_filter_post_parent_delete', wp_create_nonce( 'wpv_view_filter_post_parent_delete_nonce' ) );
		?>
		<span class="wpv-filter-title-notice js-wpv-filter-post-parent-notice hidden">
			<i class="icon-bookmark icon-rotate-270 icon-large" title="<?php echo esc_attr( __( 'This filters needs some action', 'wpv-views' ) ); ?>"></i>
		</span>
		<div id="wpv-filter-parent-edit" class="wpv-filter-edit js-wpv-filter-edit" style="padding-bottom:28px;">
			<div id="wpv-filter-parent" class="js-wpv-filter-options js-wpv-filter-post-parent-options">
				<?php WPV_Parent_Filter::wpv_render_post_parent_options( $view_settings ); ?>
			</div>
			<div class="js-wpv-filter-toolset-messages"></div>
			<span class="filter-doc-help">
				<?php echo sprintf(__('%sLearn about filtering by Post Parent%s', 'wpv-views'),
					'<a class="wpv-help-link" href="' . WPV_FILTER_BY_POST_PARENT_LINK . '" target="_blank">',
					' &raquo;</a>'
				); ?>
			</span>
		</div>
		<?php
		$res = ob_get_clean();
		return $res;
	}
	
	/**
	* wpv_filter_post_parent_update_callback
	*
	* Update parent filter callback
	*
	* @since unknown
	*/

	static function wpv_filter_post_parent_update_callback() {
		$nonce = $_POST["wpnonce"];
		if ( ! wp_verify_nonce( $nonce, 'wpv_view_filter_post_parent_nonce' ) ) {
			die( "Security check" );
		}
		if ( empty( $_POST['filter_options'] ) ) {
			echo $_POST['id'];
			die();
		}
		$change = false;
		parse_str( $_POST['filter_options'], $filter_parent );
		$view_array = get_post_meta($_POST["id"], '_wpv_settings', true);
		if ( ! isset( $filter_parent['parent_id'] ) ) {
			$filter_parent['parent_id'] = 0;
		}
		if ( ! isset( $view_array['parent_mode'] ) || $filter_parent['parent_mode'] != $view_array['parent_mode'] ) {
			$change = true;
			$view_array['parent_mode'] = $filter_parent['parent_mode'];
		}
		if ( ! isset( $view_array['parent_id'] ) || $filter_parent['parent_id'] != $view_array['parent_id'] ) {
			$change = true;
			$view_array['parent_id'] = $filter_parent['parent_id'];
		}
		if ( $change ) {
			$result = update_post_meta( $_POST["id"], '_wpv_settings', $view_array );
		}
		echo wpv_get_filter_post_parent_summary_txt(
			array(
				'parent_mode'	=> $filter_parent['parent_mode'],
				'parent_id'	=> $filter_parent['parent_id']
			)
		);
		die();
	}
	
	/**
	* Update parent filter summary callback
	*/

	static function wpv_filter_post_parent_sumary_update_callback() {
		$nonce = $_POST["wpnonce"];
		if ( ! wp_verify_nonce( $nonce, 'wpv_view_filter_parent_nonce' ) ) {
			die( "Security check" );
		}
		if ( !isset( $_POST['parent_id'] ) ) {
			$_POST['parent_id'] = 0;
		}
		echo wpv_get_filter_post_parent_summary_txt(
			array(
				'parent_mode'	=> $_POST['parent_mode'],
				'parent_id'	=> $_POST['parent_id']
			)
		);
		die();
	}
	
	/**
	* wpv_filter_post_parent_delete_callback
	*
	* Delete parent filter callback
	*
	* @since unknown
	*/

	static function wpv_filter_post_parent_delete_callback() {
		$nonce = $_POST["wpnonce"];
		if ( ! wp_verify_nonce( $nonce, 'wpv_view_filter_post_parent_delete_nonce' ) ) {
			die( "Security check" );
		}
		$view_array = get_post_meta($_POST["id"], '_wpv_settings', true);
		if ( isset( $view_array['parent_mode'] ) ) {
			unset( $view_array['parent_mode'] );
		}
		if ( isset( $view_array['parent_id'] ) ) {
			unset( $view_array['parent_id'] );
		}
		update_post_meta( $_POST["id"], '_wpv_settings', $view_array );
		echo $_POST['id'];
		die();
	}
	
	//-----------------------
	// Taxonomy parent filter
	//-----------------------

	/**
	* wpv_filters_add_filter_taxonomy_parent
	*
	* Register the taxonomy parent filter in the popup dialog
	*
	* @param $filters
	* @paran $taxonomy_type
	*
	* @since unknown
	*/

	static function wpv_filters_add_filter_taxonomy_parent( $filters, $taxonomy_type ) {
		$filters['taxonomy_parent'] = array(
			'name' => __( 'Taxonomy parent', 'wpv-views' ),
			'present' => 'taxonomy_parent_mode',
			'callback' => array( 'WPV_Parent_Filter', 'wpv_add_new_filter_taxonomy_parent_list_item' ),
			'args' => $taxonomy_type
		);
		return $filters;
	}
	
	/**
	* wpv_add_new_filter_taxonomy_parent_list_item
	*
	* Register the taxonomy parent filter in the filters list
	*
	* @param $taxonomy_type
	*
	* @since unknown
	*/

	static function wpv_add_new_filter_taxonomy_parent_list_item( $taxonomy_type ) {
		$args = array(
			'taxonomy_parent_mode' => array('current_view'),
			'taxonomy_type' => $taxonomy_type
		);
		WPV_Parent_Filter::wpv_add_filter_taxonomy_parent_list_item( $args );
	}
	
	/**
	* wpv_add_filter_taxonomy_parent_list_item
	*
	* Render taxonomy parent filter item in the filters list
	*
	* @param $view_settings
	*
	* @since unknown
	*/

	static function wpv_add_filter_taxonomy_parent_list_item( $view_settings ) {
		if ( isset( $view_settings['taxonomy_parent_mode'][0] ) && $view_settings['taxonomy_parent_mode'][0] != '' ) {
			$li = WPV_Parent_Filter::wpv_get_list_item_ui_taxonomy_parent( $view_settings );
			WPV_Filter_Item::simple_filter_list_item( 'taxonomy_parent', 'taxonomies', 'taxonomy-parent', __( 'Taxonomy parent filter', 'wpv-views' ), $li );
		}
	}
	
	/**
	* wpv_get_list_item_ui_taxonomy_parent
	*
	* Render taxonomy parent filter item content in the filters list
	*
	* @param $view_settings
	*
	* @since unknown
	*/

	static function wpv_get_list_item_ui_taxonomy_parent( $view_settings = array() ) {
		global $sitepress;
		if ( isset( $view_settings['taxonomy_type'] ) && is_array( $view_settings['taxonomy_type'] ) && sizeof( $view_settings['taxonomy_type'] ) > 0 ) {
			$view_settings['taxonomy_type'] = $view_settings['taxonomy_type'][0];
			if ( ! taxonomy_exists( $view_settings['taxonomy_type'] ) ) {
				return '<p class="toolset-alert">' . __( 'This View has a filter for a taxonomy that no longer exists. Please select one taxonomy and update the Content Selection section.', 'wpv-views' ) . '</p>';
			}
		}
		if ( isset( $view_settings['taxonomy_parent_mode'] ) && is_array( $view_settings['taxonomy_parent_mode'] ) ) {
			$view_settings['taxonomy_parent_mode'] = $view_settings['taxonomy_parent_mode'][0];
		}
		if ( 
			isset( $sitepress ) 
			&& function_exists( 'icl_object_id' )
			&& isset( $view_settings['taxonomy_type'] )
			&& isset( $view_settings['taxonomy_parent_id'] )
			&& ! empty( $view_settings['taxonomy_parent_id'] ) 
		) {
			// Adjust for WPML support
			$view_settings['taxonomy_parent_id'] = icl_object_id( $view_settings['taxonomy_parent_id'], $view_settings['taxonomy_type'], true );
		}
		ob_start();
		?>
		<p class='wpv-filter-taxonomy-parent-edit-summary js-wpv-filter-summary js-wpv-filter-taxonomy-parent-summary'>
			<?php echo wpv_get_filter_taxonomy_parent_summary_txt( $view_settings ); ?>
		</p>
		<?php
		WPV_Filter_Item::simple_filter_list_item_buttons( 'taxonomy-parent', 'wpv_filter_taxonomy_parent_update', wp_create_nonce( 'wpv_view_filter_taxonomy_parent_nonce' ), 'wpv_filter_taxonomy_parent_delete', wp_create_nonce( 'wpv_view_filter_taxonomy_parent_delete_nonce' ) );
		?>
		<span class="wpv-filter-title-notice js-wpv-filter-taxonomy-parent-notice hidden">
			<i class="icon-bookmark icon-rotate-270 icon-large" title="<?php echo esc_attr( __( 'This filters needs some action', 'wpv-views' ) ); ?>"></i>
		</span>
		<div id="wpv-filter-taxonomy-parent-edit" class="wpv-filter-edit js-wpv-filter-edit">
			<div id="wpv-filter-taxonomy-parent" class="js-wpv-filter-options js-wpv-filter-taxonomy-parent-options">
				<?php WPV_Parent_Filter::wpv_render_taxonomy_parent_options( $view_settings ); ?>
			</div>
			<div class="js-wpv-filter-toolset-messages"></div>
		</div>
		<?php
		$res = ob_get_clean();
		return $res;
		
	}
	
	/**
	* Check that chosen term belongs to taxonomy when updating taxonomy in Content Selection callback
	*/
/*
	add_action('wp_ajax_wpv_filter_taxonomy_parent_test', 'wpv_filter_taxonomy_parent_test_callback');

	function wpv_filter_taxonomy_parent_test_callback() {
		$nonce = $_POST["wpnonce"];
		if ( ! wp_verify_nonce( $nonce, 'wpv_view_filter_taxonomy_parent_nonce' ) ) {
			die("Security check");
		}
		if ( $_POST['tax_parent_id'] == '0' ) {
			echo $_POST['tax_parent_id'];
		} else {
			echo wpv_get_tax_relationship_test( $_POST['tax_parent_id'], $_POST['tax_type'] );
		}
		die();
	}
*/
	/**
	* wpv_filter_taxonomy_parent_update_callback
	*
	* Update taxonomy parent filter callback
	*
	* @since unknown
	*/

	static function wpv_filter_taxonomy_parent_update_callback() {
		$nonce = $_POST["wpnonce"];
		if ( ! wp_verify_nonce( $nonce, 'wpv_view_filter_taxonomy_parent_nonce' ) ) {
			die( "Security check" );
		}
		if ( empty( $_POST['filter_options'] ) ) {
			echo $_POST['id'];
			die();
		}
		$change = false;
		parse_str( $_POST['filter_options'], $filter_tax_parent );
		$view_array = get_post_meta( $_POST["id"], '_wpv_settings', true );
		if ( ! isset( $filter_tax_parent['taxonomy_parent_id'] ) ) {
			$filter_tax_parent['taxonomy_parent_id'] = 0;
		}
		if ( ! isset( $view_array['taxonomy_parent_mode'] ) || $filter_tax_parent['taxonomy_parent_mode'] != $view_array['taxonomy_parent_mode'] ) {
			$change = true;
			$view_array['taxonomy_parent_mode'] = $filter_tax_parent['taxonomy_parent_mode'];
		}
		if ( ! isset( $view_array['taxonomy_parent_id'] ) || $filter_tax_parent['taxonomy_parent_id'] != $view_array['taxonomy_parent_id'] ) {
			$change = true;
			$view_array['taxonomy_parent_id'] = $filter_tax_parent['taxonomy_parent_id'];
		}
		if ( $change ) {
			$result = update_post_meta( $_POST["id"], '_wpv_settings', $view_array );
		}
		echo wpv_get_filter_taxonomy_parent_summary_txt( $view_array );
		die();
	}
	
	/**
	* Update taxonomy parent filter summary callback
	*/
	
	static function wpv_filter_taxonomy_parent_sumary_update_callback() {
		$nonce = $_POST["wpnonce"];
		if ( ! wp_verify_nonce( $nonce, 'wpv_view_filter_taxonomy_parent_nonce' ) ) {
			die( "Security check" );
		}
		if ( !isset( $_POST['tax_parent_id'] ) ) {
			$_POST['tax_parent_id'] = 0;
		}
		echo wpv_get_filter_taxonomy_parent_summary_txt(
			array(
				'taxonomy_parent_mode'	=> $_POST['tax_parent_mode'],
				'taxonomy_parent_id'	=> $_POST['tax_parent_id'],
				'taxonomy_type'		=> $_POST['tax_type']
			)
		);
		die();
	}
	
	/**
	* wpv_filter_taxonomy_parent_delete_callback
	*
	* Delete taxonomy parent filter callback
	*
	* @since unknown
	*/

	static function wpv_filter_taxonomy_parent_delete_callback() {
		$nonce = $_POST["wpnonce"];
		if ( ! wp_verify_nonce( $nonce, 'wpv_view_filter_taxonomy_parent_delete_nonce' ) ) {
			die( "Security check" );
		}
		$view_array = get_post_meta( $_POST["id"], '_wpv_settings', true );
		if ( isset( $view_array['taxonomy_parent_mode'] ) ) {
			unset( $view_array['taxonomy_parent_mode'] );
		}
		if ( isset( $view_array['taxonomy_parent_id'] ) ) {
			unset( $view_array['taxonomy_parent_id'] );
		}
		update_post_meta( $_POST["id"], '_wpv_settings', $view_array );
		echo $_POST['id'];
		die();

	}
	
	/**
	* wpv_parent_summary_filter
	
	* Show the parent filter on the View summary
	*
	* @since unknown
	*
	* @todo we may need something like that for taxonomy parent
	*/

	static function wpv_parent_summary_filter( $summary, $post_id, $view_settings ) {
		if ( 
			isset( $view_settings['query_type'] ) 
			&& $view_settings['query_type'][0] == 'posts' 
			&& isset( $view_settings['parent_mode'][0] ) 
		) {
			$view_settings['parent_mode'] = $view_settings['parent_mode'][0];
			$result = wpv_get_filter_post_parent_summary_txt( $view_settings, true );
			if ( $result != '' && $summary != '' ) {
				$summary .= '<br />';
			}
			$summary .= $result;
		}
		return $summary;
	}

	/**
	* wpv_render_post_parent_options
	*
	* Render parent filter options
	*
	* @param $view_settings
	*
	* @since unknown
	*/

	static function wpv_render_post_parent_options( $view_settings = array() ) {
		$defaults = array(
			'parent_mode' => 'current_page',
			'parent_id' => 0
		);
		$view_settings = wp_parse_args( $view_settings, $defaults );
		?>
		<h4><?php  _e( 'Select post with parent:', 'wpv-views' ); ?></h4>
		<ul class="wpv-filter-options-set">
			<li>
				<input type="radio" class="js-parent-mode" name="parent_mode[]" id="parent-mode-current-page" value="current_page" <?php checked( $view_settings['parent_mode'], 'current_page' ); ?> />
				<label for="parent-mode-current-page"><?php _e('Parent is the current page', 'wpv-views'); ?></label>
			</li>
			<li>
				<input type="radio" class="js-parent-mode" name="parent_mode[]" id="parent-mode-this-page" value="this_page" <?php checked( $view_settings['parent_mode'], 'this_page' ); ?> />
				<label for="parent-mode-this-page"><?php _e('Parent is:', 'wpv-views'); ?></label>
				<select id="wpv_parent_post_type" class="js-post-parent-post-type" name="parent_type" data-nonce="<?php echo wp_create_nonce( 'wpv_view_filter_parent_post_type_nonce' ); ?>">
				<?php
					$hierarchical_post_types = get_post_types( array( 'hierarchical' => true ), 'objects');
					if ( $view_settings['parent_id'] == 0 ) {
						$selected_type = 'page';
					} else {
						$selected_type = get_post_type( $view_settings['parent_id'] );
						if ( ! $selected_type ) {
							$selected_type = 'page';
						}
					}
					foreach ( $hierarchical_post_types as $post_type ) {
						echo '<option value="' . $post_type->name . '" ' . selected( $selected_type, $post_type->name, false ) . '>' . $post_type->labels->singular_name . '</option>';
					}
				?>
				</select>
				<?php wp_dropdown_pages(
					array(
						'name' => 'parent_id',
						'selected' => $view_settings['parent_id'],
						'post_type'=> $selected_type,
						'show_option_none' => __( 'None', 'wpv-views' ),
						'id' => 'post_parent_id'
					)
				); ?>
			</li>
		</ul>
		<?php
	}
	
	/**
	* wpv_get_post_parent_post_select_callback
	*
	* Render a select dropdown given a post type
	*
	* @since unknown
	*/
	
	static function wpv_get_post_parent_post_select_callback() {
		$nonce = $_POST["wpnonce"];
		if ( ! wp_verify_nonce( $nonce, 'wpv_view_filter_parent_post_type_nonce' ) ) {
			die( "Security check" );
		}
		wp_dropdown_pages(
			array(
				'name' => 'parent_id',
				'selected' => 0,
				'post_type'=> $_POST['post_type'],
				'show_option_none' => __( 'None', 'wpv-views' ),
				'id' => 'post_parent_id'
			)
		);
		die();
	}

	/**
	* wpv_render_taxonomy_parent_options
	*
	* Render taxonomy parent filter options
	*
	* @param $args
	*
	* @since unknown
	*/

	static function wpv_render_taxonomy_parent_options( $view_settings = array() ) {
		$defaults = array(
			'taxonomy_parent_mode' => 'current_view',
			'taxonomy_parent_id' => 0
		);
		$view_settings = wp_parse_args( $view_settings, $defaults );
		?>
		<h4><?php  _e( 'Select terms with parent:', 'wpv-views' ); ?></h4>
		<ul class="wpv-filter-options-set">
			<li>
				<input type="radio" id="taxonomy-parent-mode-current-view" class="js-taxonomy-parent-mode" name="taxonomy_parent_mode[]" value="current_view" <?php checked( $view_settings['taxonomy_parent_mode'], 'current_view' ); ?> />
				<label for="taxonomy-parent-mode-current-view"><?php _e('Parent is the taxonomy selected by the <strong>parent view</strong>', 'wpv-views'); ?></label>
			</li>
			<li>
				<input type="radio" id="taxonomy-parent-mode-current-archive-loop" class="js-taxonomy-parent-mode" name="taxonomy_parent_mode[]" value="current_archive_loop" <?php checked( $view_settings['taxonomy_parent_mode'], 'current_archive_loop' ); ?> />
				<label for="taxonomy-parent-mode-current-archive-loop"><?php _e( 'Parent is the term of the <strong>current taxonomy archive</strong> page', 'wpv-views' ); ?></label>
			</li>
			<li>
				<input type="radio" id="taxonomy-parent-mode-this-parent" class="js-taxonomy-parent-mode" name="taxonomy_parent_mode[]" value="this_parent" <?php checked( $view_settings['taxonomy_parent_mode'], 'this_parent' ); ?> />
				<label for="taxonomy-parent-mode-this-parent"><?php _e('Parent is:', 'wpv-views'); ?></label>
				<?php
					if ( isset($view_settings['taxonomy_type']) && $view_settings['taxonomy_type'] != '' ) {
						$taxonomy = $view_settings['taxonomy_type'];
					} else {
						$taxonomy = 'category';
					}
				if ( taxonomy_exists( $taxonomy ) ) {
				?>
				<select name="taxonomy_parent_id" class="js-taxonomy-parent-id" data-taxonomy="<?php echo $taxonomy; ?>" data-nonce="<?php echo wp_create_nonce( 'wpv_view_filter_taxonomy_parent_id_nonce' ); ?>">
					<option value="0"><?php echo __('None', 'wpv-views'); ?></option>
					<?php 
						$my_walker = new Walker_Category_id_select( $view_settings['taxonomy_parent_id'] );
						echo wp_terms_checklist( 0, array( 'taxonomy' => $taxonomy, 'walker' => $my_walker ) );
					?>
				</select>
				<?php
				} else {
				?>
				<input type="hidden" value="0" name="taxonomy_parent_id" class="js-taxonomy-parent-id" data-taxonomy="blog" data-nonce="<?php echo wp_create_nonce( 'wpv_view_filter_taxonomy_parent_id_nonce' ); ?>" />
				<?php
				}
				?>
			</li>
		</ul>
		<?php
	}
	
	/**
	* Update parent list select when changing the parent post type callback
	*
	* PENDING IMPLEMENTATION
	*
	* Commented out in 1.7.0
	*/

	/*
	add_action('wp_ajax_wpv_get_parent_post_select', 'wpv_get_parent_post_select_callback');

	static function wpv_get_parent_post_select_callback() {
		$nonce = $_POST["wpnonce"];
		if (! wp_verify_nonce($nonce, 'wpv_view_filter_parent_post_type_nonce') ) die("Security check");
		wpv_show_posts_dropdown($_POST['post_type'], 'parent_id');
		die();
	}
	*/
	
	/**
	* update_taxonomy_parent_id_dropdown
	*
	* Update taxonomy parent filter dropdown when the one in the Content Selection section is changed
	*
	* @since unknown
	*/

	static function update_taxonomy_parent_id_dropdown() {
		if ( wp_verify_nonce( $_POST['wpnonce'], 'wpv_view_filter_taxonomy_parent_id_nonce' ) ) {
			$taxonomy = $_POST['taxonomy'];
			if ( taxonomy_exists( $taxonomy ) ) {
			?>
			<select name="taxonomy_parent_id" class="js-taxonomy-parent-id" data-taxonomy="<?php echo $taxonomy; ?>" data-nonce="<?php echo wp_create_nonce( 'wpv_view_filter_taxonomy_parent_id_nonce' ); ?>">
				<option value="0"><?php echo __('None', 'wpv-views'); ?></option>
				<?php 
					$my_walker = new Walker_Category_id_select( 0 );
					echo wp_terms_checklist( 0, array( 'taxonomy' => $taxonomy, 'walker' => $my_walker ) );
				?>
			</select>
			<?php
			} else {
			?>
			<input type="hidden" value="0" name="taxonomy_parent_id" class="js-taxonomy-parent-id" data-taxonomy="blog" data-nonce="<?php echo wp_create_nonce( 'wpv_view_filter_taxonomy_parent_id_nonce' ); ?>" />
			<?php
			}
		}
		die();
	}
	
}

/**
* Check if $term belongs to $taxonomy
*/
/*
function wpv_get_tax_relationship_test( $term, $taxonomy ) {
	$term = (int) $term;
	$term_check = term_exists( $term, $taxonomy );
	if ($term_check !== 0 && $term_check !== null) {
		return 'good';
	} else {
		return 'bad';
	}
}
*/
/**
* DEPRECATED test
*/
/*
function wpv_get_posts_select() {
    if ( wp_verify_nonce( $_POST['wpv_nonce'], 'wpv_get_posts_select_nonce' ) ) {
		wpv_show_posts_dropdown( $_POST['post_type'] );
    }
    die();
}
*/
/**
* Renders a select with the given $post_type posts as options, $name as name and $selected as selected option
*
* Move this to a more general file, for god's sake
*
* Used also in the post relationship filter
*
* @todo move this to the post relationship filter, it is not used here anymore
*/

function wpv_show_posts_dropdown( $post_type, $name = '_wpv_settings[parent_id]', $selected = 0 ) {
	$hierarchical_post_types = get_post_types( array( 'hierarchical' => true ) );	
	$attr = array(
		'name'=> $name,
		'post_type' => $post_type,
		'show_option_none' => __('None', 'wpv-views'),
		'selected' => $selected
	);
	if ( in_array( $post_type, $hierarchical_post_types ) ) {
		wp_dropdown_pages( $attr );
	} else {
		$defaults = array(
			'depth' => 0, 
			'child_of' => 0,
			'selected' => $selected,
			'echo' => 1,
			'name' => 'page_id',
			'id' => '',
			'show_option_none' => '',
			'show_option_no_change' => '',
			'option_none_value' => ''
		);
		$r = wp_parse_args( $attr, $defaults );
		extract( $r, EXTR_SKIP );		
		$pages = get_posts( array( 'numberposts' => -1, 'post_type' => $post_type, 'suppress_filters' => false ) );
		$output = '';
		// Back-compat with old system where both id and name were based on $name argument
		if ( empty( $id ) ) {
			$id = $name;
		}
		if ( ! empty( $pages ) ) {
			$output = "<select name='" . esc_attr( $name ) . "' id='" . esc_attr( $id ) . "'>\n";
			if ( $show_option_no_change )
				$output .= "\t<option value=\"-1\">$show_option_no_change</option>";
			if ( $show_option_none )
				$output .= "\t<option value=\"" . esc_attr( $option_none_value ) . "\">$show_option_none</option>\n";
			$output .= walk_page_dropdown_tree( $pages, $depth, $r );
			$output .= "</select>\n";
		}
		echo $output;	
	}
}

/**
* DEPRECATED test
*/
/*
function wpv_get_taxonomy_parents_select() {
    if (wp_verify_nonce($_POST['wpv_nonce'], 'wpv_get_taxonomy_select_nonce')) {
		$taxonomy = $_POST['taxonomy'];
		if ( taxonomy_exists( $taxonomy ) ) {
		?>
		<select name="wpv_taxonomy_parent_id">
			<option selected="selected" value="0"><?php echo __('None', 'wpv-views'); ?></option>
			<?php $my_walker = new Walker_Category_id_select(0);
			echo wp_terms_checklist(0, array('taxonomy' => $taxonomy, 'walker' => $my_walker));
		?>
		</select>
		<?php
		}
    }
    die();
	
}
*/
