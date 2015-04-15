<?php

/**
* Custom Field filter
*
* @package Views
*
* @since unknown
*/

WPV_Custom_Field_Filter::on_load();

/**
* WPV_Custom_Field_Filter
*
* Views Custom Field Filter Class
*
* @since 1.7.0
*/

class WPV_Custom_Field_Filter {

    static function on_load() {
        add_action( 'init', array( 'WPV_Custom_Field_Filter', 'init' ) );
		add_action( 'admin_init', array( 'WPV_Custom_Field_Filter', 'admin_init' ) );
    }

    static function init() {
		
    }
	
	static function admin_init() {
		// Register filters in lists and dialogs
		add_filter( 'wpv_filters_add_filter', array( 'WPV_Custom_Field_Filter', 'wpv_filters_add_filter_custom_field' ), 20, 2 );
		add_action( 'wpv_add_filter_list_item', array( 'WPV_Custom_Field_Filter', 'wpv_add_filter_custom_field_list_item' ), 1, 1 );
		//AJAX callbakcks
		add_action( 'wp_ajax_wpv_filter_custom_field_update', array( 'WPV_Custom_Field_Filter', 'wpv_filter_custom_field_update_callback' ) );
		add_action( 'wp_ajax_wpv_filter_custom_field_delete', array( 'WPV_Custom_Field_Filter', 'wpv_filter_custom_field_delete_callback' ) );
		add_filter( 'wpv-view-get-summary', array( 'WPV_Custom_Field_Filter', 'wpv_custom_field_summary_filter' ), 7, 3 );
		// Register scripts
		add_action( 'admin_enqueue_scripts', array( 'WPV_Custom_Field_Filter','admin_enqueue_scripts' ), 20 );
	}
	
	/**
	* admin_enqueue_scripts
	*
	* Register the needed script for this filter
	*
	* @since 1.7
	*/
	
	static function admin_enqueue_scripts( $hook ) {
		wp_register_script( 'views-filter-custom-field-js', ( WPV_URL . "/res/js/redesign/views_filter_custom_field.js" ), array( 'views-filters-js'), WPV_VERSION, true );
		if ( isset( $_GET['page'] ) && $_GET['page'] == 'views-editor' ) {
			wp_enqueue_script( 'views-filter-custom-field-js' );
		}
	}
	
	// TODO check what happens with all the _compare, _type and _value when the meta key has a space AND an underscore?

	static function wpv_filters_add_filter_custom_field( $filters ) {
		global $WP_Views;
		$meta_keys = $WP_Views->get_meta_keys();
		$all_types_fields = get_option( 'wpcf-fields', array() );
		foreach ( $meta_keys as $key ) {
			$key_nicename = '';
			if ( stripos( $key, 'wpcf-' ) === 0 ) {
				if ( isset( $all_types_fields[substr( $key, 5 )] ) && isset( $all_types_fields[substr( $key, 5 )]['name'] ) ) {
					$key_nicename = $all_types_fields[substr( $key, 5 )]['name'];
				} else {
					$key_nicename = $key;
				}
			} else if ( stripos($key, 'views_woo_') === 0 ) {
				if ( isset( $all_types_fields[$key] ) && isset( $all_types_fields[$key]['name'] ) ) {
					$key_nicename = $all_types_fields[$key]['name'];
				} else {
					$key_nicename = $key;
				}
			} else {
				$key_nicename = $key;
			}
			// Check if the field is in a Types group - if not, register with the full $key
			if( function_exists('wpcf_admin_fields_get_groups_by_field') ) {
				$g = '';
				foreach( wpcf_admin_fields_get_groups_by_field( $key_nicename ) as $gs ) {
					$g = $gs['name'];
				}
				$key_nicename = $g ? $key_nicename : $key;
			}
			$filters['custom-field-' . str_replace( ' ', '_', $key )] = array(
				'name' => sprintf( __( 'Custom field - %s', 'wpv-views' ), $key_nicename ),
				'present' => 'custom-field-' . $key . '_compare',
				'callback' => array( 'WPV_Custom_Field_Filter', 'wpv_add_new_filter_custom_field_list_item' ),
				'args' => array( 'name' =>'custom-field-' . $key )
			);
		}
		return $filters;
	}

	static function wpv_add_new_filter_custom_field_list_item( $args ) {
		$new_cf_filter_settings = array(
			$args['name'] . '_compare' => '=',
			$args['name'] . '_type' => 'CHAR',
			$args['name'] . '_value' => '',
		);
		WPV_Custom_Field_Filter::wpv_add_filter_custom_field_list_item( $new_cf_filter_settings );
	}

	static function wpv_add_filter_custom_field_list_item( $view_settings ) {
		if ( ! isset($view_settings['custom_fields_relationship'] ) ) {
			$view_settings['custom_fields_relationship'] = 'AND';
		}
		$summary = '';
		$td = '';
		$count = 0;
		foreach ( array_keys( $view_settings ) as $key ) {
			if ( strpos( $key, 'custom-field-' ) === 0 && strpos( $key, '_compare' ) === strlen( $key ) - strlen( '_compare' ) ) {
				$name = substr( $key, 0, strlen( $key ) - strlen( '_compare' ) );
				$td .= WPV_Custom_Field_Filter::wpv_get_list_item_ui_post_custom_field($name, $view_settings);
				$count++;
				if ( $summary != '' ) {
					if ( $view_settings['custom_fields_relationship'] == 'OR' ) {
						$summary .= __( ' OR', 'wpv-views' );
					} else {
						$summary .= __( ' AND', 'wpv-views' );
					}
				}
				$summary .= wpv_get_custom_field_summary( $name, $view_settings );
			}
		}
		if ( $count > 0 ) {
			ob_start();
			WPV_Filter_Item::filter_list_item_buttons( 'custom-field', 'wpv_filter_custom_field_update', wp_create_nonce( 'wpv_view_filter_custom_field_nonce' ), 'wpv_filter_custom_field_delete', wp_create_nonce( 'wpv_view_filter_custom_field_delete_nonce' ) );
			?>
				<?php if ( $summary != '' ) { ?>
					<p class='wpv-filter-custom-field-edit-summary js-wpv-filter-summary js-wpv-filter-custom-field-summary'>
					<?php _e('Select posts with custom field: ', 'wpv-views');
					echo $summary; ?>
					</p>
				<?php } ?>
				<div id="wpv-filter-custom-field-edit" class="wpv-filter-edit js-wpv-filter-edit js-wpv-filter-custom-field-edit js-wpv-filter-options" style="padding-bottom:28px;">
				<?php echo $td; ?>
					<div class="wpv-filter-custom-field-relationship wpv-filter-multiple-element js-wpv-filter-custom-field-relationship-container">
						<h4><?php _e( 'Custom field relationship:', 'wpv-views' ) ?></h4>
						<div class="wpv-filter-multiple-element-options">
							<?php _e( 'Relationship to use when querying with multiple custom fields:', 'wpv-views' ); ?>
							<select name="custom_fields_relationship" class="js-wpv-filter-custom-fields-relationship">
								<option value="AND" <?php echo selected( $view_settings['custom_fields_relationship'], 'AND' ); ?>><?php _e('AND', 'wpv-views'); ?>&nbsp;</option>
								<option value="OR" <?php echo selected( $view_settings['custom_fields_relationship'], 'OR' ); ?>><?php _e('OR', 'wpv-views'); ?>&nbsp;</option>
							</select>
						</div>
					</div>
					<div class="js-wpv-filter-toolset-messages"></div>
					<span class="filter-doc-help">
						<?php 
						echo sprintf(
							__( '%sLearn about filtering by custom fields%s', 'wpv-views' ),
							'<a class="wpv-help-link" href="' . WPV_FILTER_BY_CUSTOM_FIELD_LINK . '" target="_blank">',
							' &raquo;</a>'
						); ?>
					</span>
				</div>
		<?php 
			$li_content = ob_get_clean();
			WPV_Filter_Item::multiple_filter_list_item( 'custom-field', 'posts', __( 'Custom field filter', 'wpv-views' ), $li_content );
		}
	}

	static function wpv_get_list_item_ui_post_custom_field( $type, $view_settings = array() ) {
		$field_name = substr( $type, strlen( 'custom-field-' ) );
		$args = array('name' => $field_name);
		if ( ! isset( $view_settings[$type . '_compare'] ) ) {
			$view_settings[$type . '_compare'] = '=';
		}
		if ( ! isset( $view_settings[$type . '_type'] ) ) {
			$view_settings[$type . '_type'] = 'CHAR';
		}
		if ( ! isset( $view_settings[$type . '_value'] ) ) {
			$view_settings[$type . '_value'] = '';
		}
		$all_types_fields = get_option( 'wpcf-fields', array() );
		$field_nicename = '';
		if ( stripos( $field_name, 'wpcf-' ) === 0 ) {
			if ( isset( $all_types_fields[substr( $field_name, 5 )] ) && isset( $all_types_fields[substr( $field_name, 5 )]['name'] ) ) {
				$field_nicename = $all_types_fields[substr( $field_name, 5 )]['name'];
			} else {
				$field_nicename = $field_name;
			}
		} else if ( stripos( $field_name, 'views_woo_' ) === 0 ) {
			if ( isset( $all_types_fields[$field_name] ) && isset( $all_types_fields[$field_name]['name'] ) ) {
				$field_nicename = $all_types_fields[$field_name]['name'];
			} else {
				$field_nicename = $field_name;
			}
		} else {
			$field_nicename = $field_name;
		}
		// Check if the field is in a Types group - if not, register with the full $key
		if( function_exists( 'wpcf_admin_fields_get_groups_by_field' ) ) {
			$g = '';
			foreach( wpcf_admin_fields_get_groups_by_field( $field_nicename ) as $gs ) {
				$g = $gs['name'];
			}
			$field_nicename = $g ? $field_nicename : $field_name;
		}
		ob_start();
		?>
		<div class="wpv-filter-multiple-element js-wpv-filter-multiple-element js-wpv-filter-custom-field-multiple-element js-filter-row-custom-field-<?php echo $field_name; ?>" data-field="<?php echo $field_name; ?>">
			<h4><?php echo __('Custom field', 'wpv_views') . ' - ' . $field_nicename; ?></h4>
			<span class="wpv-filter-multiple-element-delete">
				<button class="button button-secondary button-small js-filter-remove" data-field="<?php echo $field_name; ?>" data-nonce="<?php echo wp_create_nonce( 'wpv_view_filter_custom_field_delete_nonce' );?>">
					<i class="icon-trash"></i>&nbsp;<?php _e( 'Delete', 'wpv-views' ); ?>
				</button>
			</span>
			<div class="wpv-filter-multiple-element-options">
			<?php WPV_Custom_Field_Filter::wpv_render_custom_field_options( $args, $view_settings ); ?>
			</div>
		</div>
		<?php
		$buffer = ob_get_clean();
		return $buffer;
	}

	static function wpv_render_custom_field_options( $args, $view_settings = array() ) {
		$compare = array( '=', '!=', '>', '>=', '<', '<=', 'LIKE', 'NOT LIKE', 'IN', 'NOT IN', 'BETWEEN', 'NOT BETWEEN' );
		$types = array( 'CHAR', 'NUMERIC', 'BINARY', 'DATE', 'DATETIME', 'DECIMAL', 'SIGNED', 'TIME', 'UNSIGNED' );
		$name_sanitized = str_replace( ' ', '_', $args['name'] );
		if ( isset( $view_settings['custom-field-' . $name_sanitized . '_value'] ) ) {
			$value = $view_settings['custom-field-' . $name_sanitized . '_value'];
		} else {
			$value = '';
		}
		$parts = array( $value );
		$value = WPV_Filter_Item::encode_date( $value );
		if ( isset( $view_settings['custom-field-' . $name_sanitized . '_compare'] ) ) {
			$compare_selected = $view_settings['custom-field-' . $name_sanitized . '_compare'];
		} else {
			$compare_selected = '=';
		}
		if ( isset( $view_settings['custom-field-' . $name_sanitized . '_type'] ) ) {
			$type_selected = $view_settings['custom-field-' . $name_sanitized . '_type'];
		} else {
			$type_selected = 'CHAR';
		}
		$name = 'custom-field-' . $name_sanitized . '%s';
		$compare_count = 1;
		switch ( $compare_selected ) {
			case 'BETWEEN':
			case 'NOT BETWEEN':
				$compare_count = 2;
				$parts = explode( ',', $value );
				// Make sure we have only 2 items.
				while ( count( $parts ) < 2 ) {
					$parts[] = '';
				}
				while ( count( $parts ) > 2 ) {
					array_pop($parts);
				}
				break;
			case 'IN':
			case 'NOT IN':
				$parts = explode( ',', $value );
				$compare_count = count( $parts );
				if ( $compare_count < 1 ) {
					$compare_count = 1;
					$parts = array( $value );
				}
				break;
		}
		$value = WPV_Filter_Item::unencode_date($value);
		?>
			<?php _e( 'Comparison function:', 'wpv-views' ); ?>
			<p>
				<select name="<?php echo sprintf( $name, '_compare' ); ?>" class="wpv_custom_field_compare_select js-wpv-custom-field-compare-select">
					<?php
					foreach ( $compare as $com ) {
					?>
					<option value="<?php echo $com; ?>" <?php selected( $compare_selected, $com ); ?>><?php echo $com; ?></option>
					<?php
					}
					?>
				</select>
				<select name="<?php echo sprintf($name, '_type'); ?>" class="js-wpv-custom-field-type-select">
					<?php
					foreach($types as $type) {
					?>
					<option value="<?php echo $type; ?>" <?php selected( $type_selected, $type ); ?>><?php echo $type; ?></option>
					<?php
					}
					?>
				</select>
			</p>
			<div class="js-wpv-custom-field-values">
				<input type="hidden" class="js-wpv-custom-field-values-real" name="<?php echo sprintf( $name, '_value' ); ?>" value="<?php echo $value; ?>" />
				<?php
				$value_holders = count( $parts );
				$options = array(
					__( 'Constant', 'wpv-views' ) => 'constant',
					__( 'URL parameter', 'wpv-views' ) => 'url',
					__( 'Shortcode attribute', 'wpv-views' ) => 'attribute',
					'NOW' => 'now',
					'TODAY' => 'today',
					'FUTURE_DAY' => 'future_day',
					'PAST_DAY' => 'past_day',
					'THIS_MONTH' => 'this_month',
					'FUTURE_MONTH' => 'future_month',
					'PAST_MONTH' => 'past_month',
					'THIS_YEAR' => 'this_year',
					'FUTURE_YEAR' => 'future_year',
					'PAST_YEAR' => 'past_year',
					'SECONDS_FROM_NOW' => 'seconds_from_now',
					'MONTHS_FROM_NOW' => 'months_from_now',
					'YEARS_FROM_NOW' => 'years_from_now',
					'DATE' => 'date'
				);
				for ( $i = 0; $i < $value_holders; $i++ ) {
					?>
					<div class="wpv_custom_field_value_div js-wpv-custom-field-value-div">
						<?php
						$function_value = WPV_Filter_Item::get_custom_filter_function_and_value( $parts[$i] );
						echo wpv_form_control( 
							array(
								'field' => array(
									'#name' => 'wpv_custom_field_compare_mode-' . $name_sanitized . $i ,
									'#type' => 'select',
									'#attributes' => array(
										'style' => '',
										'class' => 'wpv_custom_field_compare_mode js-wpv-custom-field-compare-mode js-wpv-element-not-serialize'
									),
									'#inline' => true,
									'#options' => $options,
									'#default_value' => $function_value['function'],
								)
							)
						);
						?>
						<input type="text" class="js-wpv-custom-field-value-text js-wpv-element-not-serialize" value="<?php echo $function_value['value']; ?>" data-class="js-wpv-custom-field-<?php echo $args['name']; ?>-value-text" data-type="none" name="wpv-custom-field-<?php echo $args['name']; ?>-value-text"  />
						<?php
						WPV_Filter_Item::date_field_controls( $function_value['function'], $function_value['value'] );
						?>
						<button class="button-secondary js-wpv-custom-field-remove-value"><i class="icon-remove"></i> <?php echo __( 'Remove', 'wpv-views' ); ?></button>
					</div>
					<?php
				}
				?>
				<p>
					<button class="button button-secondary js-wpv-custom-field-add-value"><i class="icon-plus"></i> <?php echo __( 'Add another value', 'wpv-views' ); ?></button>
				</p>
			</div>
	<?php
	}

	

	static function wpv_filter_custom_field_update_callback() {
		$nonce = $_POST["wpnonce"];
		if ( ! wp_verify_nonce( $nonce, 'wpv_view_filter_custom_field_nonce' ) ) {
			die( "Security check" );
		}
		$view_array = get_post_meta( $_POST["id"], '_wpv_settings', true );
		$change = false;
		$summary = '';
		parse_str( $_POST['filter_custom_fields'], $filter_custom_fields );
		foreach ( $filter_custom_fields as $filter_key => $filter_data ) {
			if ( ! isset( $view_array[$filter_key] ) || $filter_data != $view_array[$filter_key] ) {
				$change = true;
				$view_array[$filter_key] = $filter_data;
			}
		}
		if ( ! isset( $view_array['custom_fields_relationship'] ) ) {
			$view_array['custom_fields_relationship'] = 'AND';
			$change = true;
		}
		if ( $change ) {
			$result = update_post_meta( $_POST["id"], '_wpv_settings', $view_array );
		}
		foreach ( array_keys( $view_array ) as $key ) {
			if ( strpos( $key, 'custom-field-' ) === 0 && strpos( $key, '_compare' ) === strlen( $key ) - strlen( '_compare' ) ) {
				$name = substr( $key, 0, strlen( $key ) - strlen( '_compare' ) );
				if ( $summary != '' ) {
					if ( $view_array['custom_fields_relationship'] == 'OR' ) {
						$summary .= __( ' OR', 'wpv-views' );
					} else {
						$summary .= __( ' AND', 'wpv-views' );
					}
				}
				$summary .= wpv_get_custom_field_summary( $name, $view_array );
			}
		}
		_e( 'Select posts with custom field: ', 'wpv-views' );
		echo $summary;
		die();
	}

	

	static function wpv_filter_custom_field_delete_callback() {
		$nonce = $_POST["wpnonce"];
		if ( ! wp_verify_nonce( $nonce, 'wpv_view_filter_custom_field_delete_nonce' ) ) {
			die( "Security check" );
		}
		$view_array = get_post_meta( $_POST["id"], '_wpv_settings', true );
		$fields = is_array( $_POST['field'] ) ? $_POST['field'] : array( $_POST['field'] );
		foreach ( $fields as $field ) {
			$to_delete = array(
				'custom-field-' . $field . '_compare',
				'custom-field-' . $field . '_type',
				'custom-field-' . $field . '_value'
			);
			foreach ( $to_delete as $index ) {
				if ( isset( $view_array[$index] ) ) {
					unset( $view_array[$index] );
				}
			}
			$len = isset( $view_array['filter_controls_field_name'] ) ? count( $view_array['filter_controls_field_name'] ) : 0;
			$splice = false;
			for ( $i = 0; $i < $len; $i++ ) {
				if ( strpos( $view_array['filter_controls_field_name'][$i], $field ) !== false ) {
					$splice = $i;
				}
			}
			
			if ( $splice !== false ) {
				foreach ( Editor_addon_parametric::$prm_db_fields as $dbf ) {
					array_splice($view_array[$dbf], $splice, 1);
				}
			}
		}
		update_post_meta( $_POST["id"], '_wpv_settings', $view_array );
		echo $_POST['id'];
		die();
	}
	
	

	static function wpv_custom_field_summary_filter( $summary, $post_id, $view_settings ) {
		$result = '';
		$result = wpv_get_filter_custom_field_summary_txt( $view_settings );
		if ( $result != '' && $summary != '' ) {
			$summary .= '<br />';
		}
		$summary .= $result;
		return $summary;
	}
    
}

function wpv_custom_fields_get_url_params( $view_settings ) {
	global $WP_Views;
	$pattern = '/URL_PARAM\(([^(]*?)\)/siU';
	$meta_keys = array();
	$results = array();
	foreach ( array_keys( $view_settings ) as $key ) {
		if ( strpos( $key, 'custom-field-' ) === 0 && strpos( $key, '_compare' ) === strlen( $key ) - strlen( '_compare' ) ) {
			if ( empty( $meta_keys ) ) {
				$meta_keys = $WP_Views->get_meta_keys();
			}
			$name = substr( $key, 0, strlen( $key ) - strlen( '_compare' ) );
			$name = substr( $name, strlen( 'custom-field-' ) );
			$meta_name = $name;
			if ( ! in_array( $meta_name, $meta_keys ) ) {
				$meta_name = str_replace( '_', ' ', $meta_name );
			}
			$value = $view_settings['custom-field-' . $name . '_value'];
			if ( preg_match_all( $pattern, $value, $matches, PREG_SET_ORDER ) )  {
				foreach ( $matches as $match ) {
					$results[] = array( 'name' => $name, 'param' => $match[1], 'mode' => 'cf' );
				}
			}
		}
	}
	return $results;
}