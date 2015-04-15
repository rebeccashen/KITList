/**
* Views Layout Wizard - script
*
* Controls the Layout Wizard interaction and output
*
* @package Views
*
* @since unknown
*/

var WPViews = WPViews || {};

WPViews.LayoutWizard = function( $ ) {
	
	var self = this;
	
	self.view_id = $('.js-post_ID').val();
	
	self.initial_settings = null;
	self.settings_from_wizard = null;
	self.add_field_ui = null;
	self.use_loop_template = false;
	self.use_loop_template_id = '';
	self.use_loop_template_title = '';
	
	// Types compatibility
	// @todo merge this into just one variable
	// @todo store too the style options and loop template state
	
	self.wizard_dialog = null;
	self.wizard_dialog_item = null;
	self.wizard_dialog_item_parent = null;
	self.wizard_dialog_style = null;
	self.wizard_dialog_fields = null;
	
	// ---------------------------------
	// Functions
	// ---------------------------------
	
	// Fetch initial settings - on document ready
	
	self.fetch_initial_settings = function() {
		var data = {
				action: 'wpv_layout_wizard',
				view_id: self.view_id
			};
		$.post( ajaxurl, data, function( response ) {
			if ( response.length <= 0 ) {
				return;
			}
			self.initial_settings = $.parseJSON( response );
		});
	};
	
	// Render the dialog, and apply the existing settings
	// @todo find a better way of managing the fields rendering
	
	self.render_dialog = function( response ) {
		$.colorbox({
			html: response.dialog,
			onComplete: function() {
				$( '.js-insert-layout' )
					.addClass( 'button-secondary' )
					.removeClass( 'button-primary' );
				// Set the first tab as active
				$( '.js-layout-wizard-tab' ).not( ':first-child' ).hide();
				// Show the overwrite notice when needed
				var layout_value = codemirror_views_layout.getValue(),
				loop_start_tag = layout_value.indexOf( '<wpv-loop' ),// This tag can contain wrap and pad attributes
				loop_start = 0,
				loop_end = layout_value.indexOf( '</wpv-loop>' ),
				layout_loop_content = '';
				if ( loop_start_tag >= 0 ) {
					loop_start = layout_value.indexOf( '>', loop_start_tag );
				}
				if ( ( loop_start >= 0 ) && ( loop_end >= 0 ) ) {
					layout_loop_content = layout_value.slice( loop_start, loop_end ).replace(/\s+/g, '');
					if ( layout_loop_content != '>' ) {
						$( '.js-wpv-layout-wizard-overwrite-notice' ).show();
					}
				}
				// Merge response with local settings
				data = self.merge_with_saved_settings( response.settings );
				// Set incoming values, if any
				if ( typeof( data.style ) != 'undefined' ) {
					$( 'input.js-wpv-layout-wizard-style[value="' + data.style + '"]' ).click();
				}
				if ( typeof( data.table_cols ) != 'undefined' ) {
					$( 'select.js-wpv-layout-wizard-table-cols[name="table_cols"]' ).val( data.table_cols );
				}
				if ( typeof( data.wpv_bootstrap_version ) != 'undefined' ) {
					if ( data.wpv_bootstrap_version == '1' ) {
						$( '#layout-wizard-style-bootstrap-grid' ).attr( 'disabled', true );
						$( 'label[for=layout-wizard-style-bootstrap-grid]' ).css({ opacity: 0.5 });
						$( '.js-wpv-bootstrap-disabled' ).show();
					} else {
						$( '#layout-wizard-style-bootstrap-grid' ).attr( 'disabled', false );
						$( 'label[for=layout-wizard-style-bootstrap-grid]' ).css({ opacity: 1 });
						$( '.js-wpv-bootstrap-disabled' ).hide();
						if ( data.wpv_bootstrap_version == 3 ) {
							$( 'input[name="bootstrap_grid_row_class"]' ).prop( 'disabled', true );
						} else {
							if ( typeof( data.bootstrap_grid_row_class ) != 'undefined' && data.bootstrap_grid_row_class === 'true' ) {
								$( 'input[name="bootstrap_grid_row_class"]' ).prop( 'checked', true );
							}
						}
					}
				}
				// Not sure why we remove the options and add them back, being the same
				// @todo review this
				$( 'select.js-wpv-layout-wizard-bootstrap-grid-cols[name="bootstrap_grid_cols"] option' ).remove();
				var grid_options = '';
				grid_options	+= '<option value="1">1</option>';
				grid_options	+= '<option value="2">2</option>';
				grid_options	+= '<option value="3">3</option>';
				grid_options	+= '<option value="4">4</option>';
				grid_options	+= '<option value="6">6</option>';
				grid_options	+= '<option value="12">12</option>';
				$( 'select.js-wpv-layout-wizard-bootstrap-grid-cols[name="bootstrap_grid_cols"]' ).append(grid_options);
				if ( typeof( data.bootstrap_grid_cols ) != 'undefined' ) {
					$( 'select.js-wpv-layout-wizard-bootstrap-grid-cols[name="bootstrap_grid_cols"]' ).val( data.bootstrap_grid_cols );
				}
				if ( typeof( data.bootstrap_grid_container ) != 'undefined' && data.bootstrap_grid_container === 'true' ) {
					$( 'input[name="bootstrap_grid_container"]' ).prop( 'checked', true );
				}
				$( '#bootstrap_grid_individual_yes' ).prop( 'checked', true );
				if ( typeof( data.bootstrap_grid_individual ) != 'undefined' && data.bootstrap_grid_individual != '' ) {
					$( '#bootstrap_grid_individual_yes' ).prop( 'checked', false );
					$( '#bootstrap_grid_individual_no' ).prop( 'checked', true );	
				}
				if ( typeof( data.include_field_names ) != 'undefined' ) {
					$('input[name="include_field_names"]').attr('checked', data.include_field_names === 'true');
				}
				// Load existing fields
				// @todo this needs a hard review
				if ( typeof( data.fields ) != 'undefined' ) {
					var ii = 0,
					vcount = 0,
					flist = [];
					for( s in data.fields ) {
						flist[ii] = data.fields[s];
						ii++;
						if ( ii % 6 == 0 ) {
							vcount++;
						}
					}
					if ( vcount == 0 ) {
						return;
					}
					for ( j = 0; j < vcount; j+=1 ) {
						$('.js-wpv-layout-wizard-layout-fields').append('<div class="spacer_' + j + '"></div>');
					}
					for ( i = 0; i < vcount; i+=1 ) {
						var sel = '';
						if ( typeof( data.real_fields) != 'undefined' ) {
							sel = data.real_fields[i];
						} else {
							if ( ( flist[( i*6 ) + 1 ] ) === 'types-field' )
								sel = flist[( i*6 ) + 3];
							else
								sel = '[' + flist[( i*6 ) + 1] + ']';
						}
						var ajaxdata = {
							action: 'layout_wizard_add_field',
							id: i,
							wpnonce : $('#layout_wizard_nonce').attr('value'),
							selected: sel,
							view_id: self.view_id
						};
						$.post( ajaxurl, ajaxdata, function( response ) {
							if ( response.length <= 0 ) {
								$.colorbox.close();
								$('.js-wpv-settings-layout-extra .js-wpv-toolset-messages')
									.wpvToolsetMessage({
										text: wpv_layout_wizard_strings.unknown_error,
										type:'error',
										inline:true,
										stay:false
									});
								return;
							}
							response = $.parseJSON( response );
							if ( response.selected_found === true ) {
								var field_html = response.html,
								count = $( '.wpv-dialog-nav-tab' ).index( $( 'li' ).has( '.active' ) );
								field = field_html.match(/(layout-wizard-style_)[0-9]+/);
								key = field[0].match(/[0-9]+/);
								$( field_html ).insertAfter( $('.js-wpv-layout-wizard-layout-fields div.spacer_' + key ) );
								// This should be done once all fields are finished
								self.manage_dialog_buttons( count );
								// This should be done once all fields are finished
								$.each( $('.js-wpv-dialog-layout-wizard select.js-select2' ),function() {
									if ( ! $( this ).hasClass( 'select2-offscreen' ) ) {
										$( this ).select2();
									}
								});
							}
						}, 'html' )
						.done( function() {
							if ( typeof key !== 'undefined' ) {
								$('div.spacer_' + key).remove();
							}
						});
						$('.js-wpv-layout-wizard-layout-fields').sortable({
							handle: ".js-layout-wizard-move-field",
							axis: 'y',
							containment: "parent",
							helper: 'clone',
							tolerance: "pointer"
						});
					}
				}
			}
		});
	};
	
	// Merge settings with existing ones
	
	self.merge_with_saved_settings = function( data ) {
		if ( self.settings_from_wizard ) {
			data.style = self.settings_from_wizard.style;
			data.table_cols = self.settings_from_wizard.table_cols;
			data.include_field_names = self.settings_from_wizard.include_field_names;
			data.fields = self.settings_from_wizard.fields;
			data.real_fields = self.settings_from_wizard.real_fields;
			data.bootstrap_grid_cols = self.settings_from_wizard.bootstrap_grid_cols;
			data.bootstrap_grid_container = self.settings_from_wizard.bootstrap_grid_container;
			data.bootstrap_grid_row_class = self.settings_from_wizard.bootstrap_grid_row_class;
			data.bootstrap_grid_individual = self.settings_from_wizard.bootstrap_grid_individual;			
		}
		return data;
	};
	
	// Change tab
	
	self.change_tab = function( backward ) {
		var count = $( '.wpv-dialog-nav-tab' ).index( $( 'li' ).has( '.active' ) );
		$( '.wpv-dialog-nav-tab a' ).removeClass('active');
		$( '.wpv-dialog-content-tab' ).hide();
		if ( backward ) {
			count--;
		} else {
			count++;
		}
		$( '.wpv-dialog-nav-tab a' )
			.eq( count )
				.addClass( 'active' );
		$( '.wpv-dialog-content-tab' )
			.eq( count )
				.fadeIn( 'fast' );
		self.manage_dialog_buttons( count );
	};
	
	// Navigate to a tab
	
	self.go_to_tab = function( tab_index ) {
		$( '.wpv-dialog-nav-tab a' ).removeClass( 'active' );
		$( '.wpv-dialog-content-tab' ).hide();
		$( '.wpv-dialog-nav-tab a' ).eq( tab_index ).addClass( 'active' );
		$( '.wpv-dialog-content-tab' ).eq( tab_index ).fadeIn( 'fast' );
		self.manage_dialog_buttons( tab_index );
	}
	
	// Update buttons
	
	self.manage_dialog_buttons = function( page_id ) {
		var next_enable = false,
		insert_button = $('.js-insert-layout'),
		prev_button = $('.js-dialog-prev');
		switch ( page_id ) {
			case 0:
				next_enable = ( $( '[name="layout-wizard-style"]:checked' ).length > 0 );
				insert_button.text( wpv_layout_wizard_strings.button_next );
				prev_button.hide();
				break;
			case 1:
				next_enable = ( $( 'ul.js-wpv-layout-wizard-layout-fields > li' ).length > 0 );
				insert_button.text( wpv_layout_wizard_strings.button_insert );
				prev_button.show();
				break;
		}
		if ( next_enable ) {
			insert_button
				.removeClass( 'button-secondary' )
				.addClass( 'button-primary' )
				.prop( 'disabled', false );
		} else {
			insert_button
				.removeClass( 'button-primary' )
				.addClass( 'button-secondary' )
				.prop( 'disabled', true );
		}
	};
	
	// Add field UI
	
	self.add_field_ui_callback = function( field_html ) {
		var count = $('li[id*="layout-wizard-style_"]').size(),
		field_html = field_html.replace( '__wpv_layout_count_placeholder__', count + 1 );
		$('.js-wpv-layout-wizard-layout-fields').append( field_html );
		$('.js-wpv-layout-wizard-layout-fields').sortable();
		self.manage_dialog_buttons( 1 );
		$.each( $('.js-wpv-dialog-layout-wizard select.js-select2' ),function() {
			if ( ! $( this ).hasClass( 'select2-offscreen' ) ) {
				$( this ).select2();
			}
		});
	};
	
	// Replace layout content
	
    self.replace_layout_loop_content = function( content, data ) {
        if ( content.search(/<!-- wpv-loop-start -->[\s\S]*\<!-- wpv-loop-end -->/g) == -1 ) {
            content += data;
        } else {
            content = content.replace(/<!-- wpv-loop-start -->[\s\S]*\<!-- wpv-loop-end -->/g, "<!-- wpv-loop-start -->\n" + data + "<!-- wpv-loop-end -->");
        }
        return content;
    };
	
	//Check if fields is just one content template
	
	self.check_for_only_content_template_field = function( fields ) {
		var out = false,
		fields_count = fields.length;
		if ( fields_count == 1 ) {
			for ( var i = 0; i < fields_count; i++ ) {
				if ( fields[i][4] == 'post-body' ) {
					out = true;
				}
			}
		}
	   return out;
	};
	
	// Process dialog data
	
	self.process_layout_wizard_data = function( fields, callback ) {
        var layout_style = $( '[name=layout-wizard-style]:checked' ).val(),
		number_of_columns = $('[name="table_cols"]').val(),
		bootstrap_grid_cols = $('[name="bootstrap_grid_cols"]').val(),
		bootstrap_grid_container = $('[name="bootstrap_grid_container"]').prop('checked'),
		bootstrap_grid_row_class = $('[name="bootstrap_grid_row_class"]').prop('checked'),
		bootstrap_grid_individual = $('[name="bootstrap_grid_individual"]:checked').val(),
		include_headers = ($('[name="include_field_names"]').attr('checked')) ? true : false,
		data = '',
		c = codemirror_views_layout.getValue(),
		current_editor_content = codemirror_views_layout.getValue(),
		codemirror_highlight_options = {
			className: 'wpv-codemirror-highlight'
		};
		// First, save the layout settings to a variable
		// These will be then be saved to the DB when we update the
		// Layout section or they'll be used when we open the wizard again
		var layout_data_to_store = {
			action: 'wpv_convert_layout_settings',
			view_id: self.view_id,
			layout_style: layout_style,
			fields: fields,
			numcol: number_of_columns,
			bootstrap_grid_cols: bootstrap_grid_cols,
			bootstrap_grid_container: bootstrap_grid_container,
			bootstrap_grid_row_class: bootstrap_grid_row_class,
			bootstrap_grid_individual: bootstrap_grid_individual,
			inc_headers: include_headers
		};
		$.ajax({
			async: false,
			type: "POST",
			url: ajaxurl,
			data: layout_data_to_store,
			success: function( response ) {
				self.settings_from_wizard = $.parseJSON( response );
			},
			error: function( ajaxContext ) {
				console.log( "Error: ", ajaxContext.responseText );
			},
			complete: function() {
				
			}
		});
		// Now, get the layout (and loop template if needed) content for each Loop output style
		switch ( layout_style ) {
			case "table":
				data = self.render_table_layout( fields, number_of_columns );
				break;
			case "bootstrap-grid":
				data = self.render_bootstrap_grid_layout( fields, bootstrap_grid_cols, bootstrap_grid_container, bootstrap_grid_individual, bootstrap_grid_row_class );
				break;
			case "table_of_fields":
				data = self.render_table_of_fields_layout( fields );
				break;
			case "ordered_list":
				data = self.render_ordered_list_layout( fields );
				break;
			case "un_ordered_list":
				data = self.render_unordered_list_layout( fields );
				break;
			default:
				data = self.render_unformatted_layout( fields );
				break;
		}
		if ( self.use_loop_template ) {
			var show_layout_template_loop_pointer_content = $( '.js-wpv-inserted-layout-loop-content-template-pointer' );
			// Make sure that the Loop Template is opened
			if ( $('.js-wpv-ct-listing-' + self.use_loop_template_id + ' .js-wpv-open-close-arrow').hasClass('icon-caret-down') ) {
				$('.js-wpv-ct-listing-' + self.use_loop_template_id + ' .js-wpv-content-template-open').click();
			}
			// Update the Layout content
			c = self.replace_layout_loop_content( c, data.layout_output );
			codemirror_views_layout.setValue( c );
			// Update the Loop Template content
			window.iclCodemirror["wpv-ct-inline-editor-" + self.use_loop_template_id].setValue( data.ct_output );
			// Highlight the Loop Template content
			var loop_template_ends = {'line':window.iclCodemirror["wpv-ct-inline-editor-" + self.use_loop_template_id].lineCount(),'ch':0},
			content_template_marker = window.iclCodemirror["wpv-ct-inline-editor-" + self.use_loop_template_id].markText( {'line':0,'ch':0}, loop_template_ends, codemirror_highlight_options );
			setTimeout( function() {
					content_template_marker.clear();
			}, 2000);
			// Highlight replace existing loop and add pointer
			var layout_loop_starts = codemirror_views_layout.getSearchCursor( '<!-- wpv-loop-start -->', false );
			layout_loop_ends = codemirror_views_layout.getSearchCursor( '<!-- wpv-loop-end -->', false );
			if ( layout_loop_starts.findNext() && layout_loop_ends.findNext() ) {
				var layout_loop_marker = codemirror_views_layout.markText( layout_loop_starts.from(), layout_loop_ends.to(), codemirror_highlight_options );
				if ( show_layout_template_loop_pointer_content.hasClass( 'js-wpv-pointer-dismissed' ) ) {
					setTimeout( function() {
						  layout_loop_marker.clear();
					}, 2000);
				} else {// Show the pointer
					var layout_template_loop_pointer = $('.layout-html-editor .wpv-codemirror-highlight').first().pointer({
						pointerClass: 'wp-toolset-pointer wp-toolset-views-pointer',
						pointerWidth: 400,
						content: show_layout_template_loop_pointer_content.html(),
						position: {
							edge: 'bottom',
							align: 'left'
						},
						show: function( event, t ) {
							t.pointer.show();
							t.opened();
							var button_scroll = $('<button class="button button-primary-toolset alignright js-wpv-scroll-this">Scroll to the Content Template</button>');
							button_scroll.bind( 'click.pointer', function(e) {//We need to scroll there down
								e.preventDefault();
								layout_loop_marker.clear();
								if ( t.pointer.find( '.js-wpv-dismiss-pointer:checked' ).length > 0 ) {
									var pointer_name = t.pointer.find( '.js-wpv-dismiss-pointer:checked' ).data( 'pointer' );
									$( document ).trigger( 'js_event_wpv_dismiss_pointer', [ pointer_name ] );
								}
								t.element.pointer('close');
								if ( self.use_loop_template_id != '' ) {
									$('html, body').animate({
										scrollTop: $( '#wpv-ct-listing-' + self.use_loop_template_id ).offset().top - 100
									}, 1000);
								}
							});
							button_scroll.insertAfter(  t.pointer.find('.wp-pointer-buttons .js-wpv-close-this') );
						},
						buttons: function( event, t ) {
							var button_close = $('<button class="button button-secondary alignleft js-wpv-close-this">Close</button>');
							button_close.bind( 'click.pointer', function( e ) {
								e.preventDefault();
								layout_loop_marker.clear();
								if ( t.pointer.find( '.js-wpv-dismiss-pointer:checked' ).length > 0 ) {
									var pointer_name = t.pointer.find( '.js-wpv-dismiss-pointer:checked' ).data( 'pointer' );
									$( document ).trigger( 'js_event_wpv_dismiss_pointer', [ pointer_name ] );
								}
								t.element.pointer('close');
								codemirror_views_layout.focus();
							});
							return button_close;
						}
					});
					layout_template_loop_pointer.pointer('open');
				}
			}
		} else {
			// Update the Layout content
			c = self.replace_layout_loop_content( c, data.layout_output );
			codemirror_views_layout.setValue( c );
			// Highlight and add pointer to the loop
			var show_layout_loop_pointer_content = $( '.js-wpv-inserted-layout-loop-pointer' ),
			layout_loop_starts = codemirror_views_layout.getSearchCursor( '<!-- wpv-loop-start -->', false );
			layout_loop_ends = codemirror_views_layout.getSearchCursor( '<!-- wpv-loop-end -->', false );
			if ( layout_loop_starts.findNext() && layout_loop_ends.findNext() ) {
				var layout_loop_marker = codemirror_views_layout.markText( layout_loop_starts.from(), layout_loop_ends.to(), codemirror_highlight_options );
				if ( show_layout_loop_pointer_content.hasClass( 'js-wpv-pointer-dismissed' ) ) {
					setTimeout( function() {
						  layout_loop_marker.clear();
					}, 2000);
				} else {// Show the pointer
					var layout_loop_pointer = $('.layout-html-editor .wpv-codemirror-highlight').first().pointer({
						pointerClass: 'wp-toolset-pointer wp-toolset-views-pointer',
						pointerWidth: 400,
						content: show_layout_loop_pointer_content.html(),
						position: {
							edge: 'bottom',
							align: 'left'
						},
						buttons: function( event, t ) {
							var button_close = $('<button class="button button-primary-toolset alignright js-wpv-close-this">Close</button>');
							button_close.bind( 'click.pointer', function( e ) {
								e.preventDefault();
								layout_loop_marker.clear();
								if ( t.pointer.find( '.js-wpv-dismiss-pointer:checked' ).length > 0 ) {
									var pointer_name = t.pointer.find( '.js-wpv-dismiss-pointer:checked' ).data( 'pointer' );
									$( document ).trigger( 'js_event_wpv_dismiss_pointer', [ pointer_name ] );
								}
								t.element.pointer('close');
								codemirror_views_layout.focus();
							});
							return button_close;
						}
					});
					layout_loop_pointer.pointer('open');
				}
			}
		}
		if ( callback && typeof callback === 'function' ) {
			callback();
		}
    };
	
	// ---------------------------------
	// Render functions
	// ---------------------------------
	
	//render functions
    //fields array
    //0 - prefix, text before [shortcode]
    //1 - [shortcode]
    //2 - suffix, text after [shortcode]
    //3 - field name
    //4 - header name
    //5 - row title <TH>
    //0,2 maybe not used in v1.3

    self.render_unformatted_layout = function( fields ) {
		var fields_length = fields.length,
		ct_output = '',
        layout_output = '',
		row_indent = "\t\t",
		row_ending = "\n";
		if ( self.use_loop_template ) {
			row_indent = "";
		}
        for ( var i = 0; i < fields_length; i++ ) {
            if ( 
				self.use_loop_template 
				&& i == ( fields_length - 1 )
			) {
				row_ending = "";
			}
			ct_output += row_indent + fields[i][0];// prefix
            ct_output += fields[i][1];// shortcode
            ct_output += fields[i][2] + row_ending;// suffix
        }
		layout_output = "\t<wpv-loop>\n";
        if ( self.use_loop_template ) {
			layout_output += "\t\t[wpv-post-body view_template=\"" + self.use_loop_template_title + "\"]\n";
        } else {
			layout_output += ct_output;
			ct_output = '';
		}
		layout_output += "\t</wpv-loop>\n\t";
        var out = {'layout_output':layout_output, 'ct_output':ct_output};
        return out;
    };

    self.render_unordered_list_layout = function( fields ) {
		var fields_length = fields.length,
		ct_output = '',
        layout_output = '',
		row_indent = "\t\t\t",
		row_ending = "\n";
		if ( self.use_loop_template ) {
			row_indent = "";
		}
        for ( var i = 0; i < fields_length; i++ ) {
            if ( 
				self.use_loop_template 
				&& i == ( fields_length - 1 )
			) {
				row_ending = "";
			}
			ct_output += row_indent + fields[i][0];// prefix
            ct_output += fields[i][1];// shortcode
            ct_output += fields[i][2] + row_ending;// suffix
        }
		layout_output = "\t<ul>\n";
		layout_output += "\t<wpv-loop>\n";
        if ( self.use_loop_template ) {
			layout_output += "\t\t<li>[wpv-post-body view_template=\"" + self.use_loop_template_title + "\"]</li>\n";
        } else {
			layout_output += "\t\t<li>\n" + ct_output + "\t\t</li>\n";
			ct_output = '';
		}
		layout_output += "\t</wpv-loop>\n";
		layout_output += "\t</ul>\n\t";
        var out = {'layout_output':layout_output, 'ct_output':ct_output};
        return out;
    };

    self.render_ordered_list_layout = function( fields ) {
        var fields_length = fields.length,
		ct_output = '',
        layout_output = '',
		row_indent = "\t\t\t",
		row_ending = "\n";
		if ( self.use_loop_template ) {
			row_indent = "";
		}
        for ( var i = 0; i < fields_length; i++ ) {
            if ( 
				self.use_loop_template 
				&& i == ( fields_length - 1 )
			) {
				row_ending = "";
			}
			ct_output += row_indent + fields[i][0];// prefix
            ct_output += fields[i][1];// shortcode
            ct_output += fields[i][2] + row_ending;// suffix
        }
		layout_output = "\t<ol>\n";
		layout_output += "\t<wpv-loop>\n";
        if ( self.use_loop_template ) {
			layout_output += "\t\t<li>[wpv-post-body view_template=\"" + self.use_loop_template_title + "\"]</li>\n";
        } else {
			layout_output += "\t\t<li>\n" + ct_output + "\t\t</li>\n";
			ct_output = '';
		}
		layout_output += "\t</wpv-loop>\n";
		layout_output += "\t</ol>\n\t";
        var out = {'layout_output':layout_output, 'ct_output':ct_output};
        return out;
    };

    self.render_table_of_fields_layout = function( fields ) {
        var fields_length = fields.length,
		ct_output = '',
        layout_output = '',
		loop_item_indent = "\t\t\t\t",
		loop_item_ending = "\n";
		if ( self.use_loop_template ) {
			loop_item_indent = "";
		}
		for ( var i = 0; i < fields_length; i++ ) {
            var body = fields[i][0];// prefix
            body += fields[i][1];// shortcode
            body += fields[i][2];// suffix
			if ( 
				self.use_loop_template 
				&& i == ( fields_length - 1 )
			) {
				loop_item_ending = "";
			}
			ct_output += loop_item_indent + "<td>" + body + "</td>" + loop_item_ending;
        }
		layout_output = "\t<table width=\"100%\">\n";
		if ( $('#include_field_names').attr('checked') ) {
            layout_output += "\t\t<thead>\n\t\t\t<tr>\n";
            for ( var i = 0; i < fields_length; i++ ) {
                layout_output += "\t\t\t\t<th>[wpv-heading name=\"" + fields[i][4] + "\"]" + fields[i][5] + "[/wpv-heading]</th>\n";
            }
            layout_output += "\t\t\t</tr>\n\t\t</thead>\n";
        }
		layout_output += "\t\t<tbody>\n";
        layout_output += "\t\t<wpv-loop>\n";
		layout_output += "\t\t\t<tr>\n";
		if ( self.use_loop_template ) {
			layout_output += "\t\t\t\t[wpv-post-body view_template=\""+ self.use_loop_template_title + "\"]\n"; 
		} else {
			layout_output += ct_output;
			ct_output = '';
		}
		layout_output += "\t\t\t</tr>\n";
        layout_output += "\t\t</wpv-loop>\n\t\t</tbody>\n\t</table>\n\t";
        var out = {'layout_output':layout_output,'ct_output':ct_output};
        return out;
    };
	
    self.render_table_layout = function( fields, cols ) {
        var fields_length = fields.length,
		ct_output = '',
        layout_output = '',
		row_indent = "\t\t\t\t",
		row_ending = "\n";
		if ( self.use_loop_template ) {
			row_indent = "";
		}
		for ( var i = 0; i < fields_length; i++ ) {
            if ( 
				self.use_loop_template 
				&& i == ( fields_length - 1 )
			) {
				row_ending = "";
			}
			ct_output += row_indent + fields[i][0];// prefix
            ct_output += fields[i][1];// shortcode
            ct_output += fields[i][2] + row_ending;// suffix
        }
		layout_output = "\t<table width=\"100%\">\n\t<wpv-loop wrap=\"" + cols + "\" pad=\"true\">\n";
		layout_output += "\t\t[wpv-item index=1]\n";
		if ( self.use_loop_template ) {
			layout_output += "\t\t<tr>\n\t\t\t<td>[wpv-post-body view_template=\""+ self.use_loop_template_title + "\"]</td>\n";
			layout_output += "\t\t[wpv-item index=other]\n";
			layout_output += "\t\t\t<td>[wpv-post-body view_template=\""+ self.use_loop_template_title + "\"]</td>\n";
			layout_output += "\t\t[wpv-item index=" + cols + "]\n";
			layout_output += "\t\t\t<td>[wpv-post-body view_template=\""+ self.use_loop_template_title + "\"]</td>\n\t\t</tr>\n";
		} else {
			layout_output += "\t\t<tr>\n\t\t\t<td>\n" + ct_output + "\t\t\t</td>\n";
			layout_output += "\t\t[wpv-item index=other]\n";
			layout_output += "\t\t\t<td>\n" + ct_output + "\t\t\t</td>\n";
			layout_output += "\t\t[wpv-item index=" + cols + "]\n";
			layout_output += "\t\t\t<td>\n" + ct_output + "\t\t\t</td>\n\t\t</tr>\n";
			ct_output = '';
		}
		layout_output += "\t\t[wpv-item index=pad]\n";
        layout_output += "\t\t\t<td></td>\n";
        layout_output += "\t\t[wpv-item index=pad-last]\n";
        layout_output += "\t\t\t<td></td>\n\t\t</tr>\n";
        layout_output += "\t</wpv-loop>\n\t</table>\n\t";
        var out = {'layout_output':layout_output,'ct_output':ct_output};
        return out;
    };
    
    self.render_bootstrap_grid_layout = function( fields, cols, container, individual, row_class ) {
        var fields_length = fields.length,
		ct_output = '',
        layout_output = '',
		col_num = 12/cols,
		row_style = '',
		col_style = 'col-sm-',
		row = '',
		close_columns_of_one = '',
		loop_item = '',
		loop_item_pad = '',
		row_indent = "\t\t\t\t",
		row_ending = "\n";
		if ( self.use_loop_template ) {
			row_indent = "";
		}
		for ( var i = 0; i < fields_length; i++ ) {
            if ( i == ( fields_length - 1 )	) {
				row_ending = "";
			}
			ct_output += row_indent + fields[i][0];// prefix
            ct_output += fields[i][1];// shortcode
            ct_output += fields[i][2] + row_ending;// suffix
        }
		if ( data.wpv_bootstrap_version == 2 ) {
			row_style = ' row-fluid';
			col_style = 'span';
		}
		if ( 
			row_class === true 
			|| data.wpv_bootstrap_version == 3
		) {
			row = "row";	
		}
		if ( cols == 1 ) {
        	close_columns_of_one = '\n\t\t</div>';	
        }
        if ( self.use_loop_template ) {
            loop_item = "<div class=\"" + col_style + col_num + "\">[wpv-post-body view_template=\"" + self.use_loop_template_title + "\"]</div>";
        } else {
			loop_item = "<div class=\"" + col_style + col_num + "\">\n" + ct_output + "\n\t\t\t</div>";
			ct_output = '';
		}
		loop_item_pad = "<div class=\"" + col_style + col_num + "\"></div>";
		if ( container === true ) {
			layout_output += "\t<div class=\"container\">\n";
		}
		layout_output += "\t<wpv-loop wrap=\"" + cols + "\" pad=\"true\">\n";
		layout_output += "\t\t[wpv-item index=1]\n";
		if ( individual == 1 ) {
        	layout_output += "\t\t<div class=\"" + row + row_style + "\">\n\t\t\t" + loop_item + close_columns_of_one + "\n";
        	for ( i = 2; i < cols; i++ ) {
        		layout_output += "\t\t[wpv-item index=" + i + "]\n";
        		layout_output += "\t\t\t" + loop_item + "\n";	
        	}
        } else {
        	layout_output += "\t\t<div class=\"" + row + row_style + "\">\n\t\t\t" + loop_item + close_columns_of_one + "\n";
	        layout_output += "\t\t[wpv-item index=other]\n";
	        layout_output += "\t\t\t" + loop_item + "\n";
	    }
	    if ( cols > 1 ) {
	        layout_output += "\t\t[wpv-item index=" + cols + "]\n";
	        layout_output += "\t\t\t" + loop_item + "\n\t\t</div>\n";
        }
        layout_output += "\t\t[wpv-item index=pad]\n";
        layout_output += "\t\t\t" + loop_item_pad + "\n";
		layout_output += "\t\t[wpv-item index=pad-last]\n";
		layout_output += "\t\t\t" + loop_item_pad + "\n";
        layout_output += "\t\t</div>\n";
        layout_output += "\t</wpv-loop>\n\t";
		if ( container === true ) {
			layout_output += "</div>\n\t";
		}
        var out = {'layout_output':layout_output, 'ct_output':ct_output};
        return out;

    };
	
	// ---------------------------------
	// Events
	// ---------------------------------
	
	// Open layout wizard dialog
	
	$( document ).on( 'click', '.js-open-meta-html-wizard', function() {
		if ( self.initial_settings ) {
			// We have a previous setting that we can use.
			self.render_dialog( self.initial_settings );
		} else {
			// fetch and load the popup via ajax.
			var data = {
				action: 'wpv_layout_wizard',
				view_id: self.view_id
			};
			$.post( ajaxurl, data, function( response ) {
				if ( response.length <= 0 ) {
					$( '.js-wpv-settings-layout-extra .js-wpv-toolset-messages' )
						.wpvToolsetMessage({
							text: wpv_layout_wizard_strings.unknown_error,
							type:'error',
							inline:true,
							stay:false
						});
					return;
				}
				self.initial_settings = $.parseJSON( response );
				self.render_dialog( self.initial_settings );
			});
		}
	});
	
	// Navigate clicking on the tabs
	
	$( document ).on( 'click', '.wpv-dialog-nav-tab a', function( e ) {
		e.preventDefault();
		var thiz = $( this ),
		thiz_tab = thiz.parents( '.wpv-dialog-nav-tab' ),
		thiz_index = $( '.wpv-dialog-nav-tab' ).index( thiz_tab );
		if ( 
			! thiz.hasClass( 'js-tab-not-visited' ) 
			&& ! thiz.hasClass( 'active') 
		) {
			self.go_to_tab( thiz_index );
		}
	});
	
	// Go back
	
	$( document ).on( 'click', '.js-dialog-prev', function() {
		self.change_tab( true );
	});
	
	// Set the loop template default on specific Loop output styles
	
	$( document ).on( 'change', '.js-wpv-layout-wizard-style', function() {
        var style_selected = $( '.js-wpv-layout-wizard-style:checked' ).val();
		if ( 
			style_selected === 'bootstrap-grid' 
			|| style_selected === 'table_of_fields'
            || style_selected === 'table' 
		) {
            $( '#js-wpv-use-view-loop-ct' ).prop( 'checked', true );
        } else {
            $( '#js-wpv-use-view-loop-ct' ).prop( 'checked', false );
        }
    });
	
	// Clear the dialog ui once the query type options have changed
	
	$( document ).on( 'js_event_wpv_query_type_options_saved', '.js-wpv-query-type-update', function() {
		self.add_field_ui = null;
	});
	
	// Remove a field
	
	$( document ).on( 'click', '.js-layout-wizard-remove-field', function( e ) {
		var row_to_delete = $( this ).parents( 'li' );
		row_to_delete.addClass( 'wpv-layout-wizard-field-deleted' );
		setTimeout( function () {
			row_to_delete.remove();
			self.manage_dialog_buttons( 1 );
		}, 500 );
	});
	
	// Change the Loop output style - display extra options for some styles
	
	$( document ).on( 'change', '.js-wpv-layout-wizard-style', function() {
		var style_selected = $( this ).val(),
		style_container = $( '.js-wpv-layout-wizard-layout-style' ),
		style_settings_container = $( '.js-wpv-layout-wizard-layout-style-options' ),
		dialog_pointer = $( '<div class="wpv-dialog-arrow-left js-wpv-dialog-arrow-left"></div>' );
		$( '.js-insert-layout' )
			.prop( 'disabled', false )
			.removeClass( 'button-secondary' )
			.addClass( 'button-primary' );
		$( '.js-layout-wizard-num-columns, .js-layout-wizard-include-fields-names, .js-layout-wizard-bootstrap-grid-box' ).hide();
		$( '.js-wpv-dialog-arrow-left' ).remove();
		style_container.removeClass( 'wpv-layout-wizard-layout-style-has-settings' );
		if ( style_settings_container.find( '.js-wpv-layout-wizard-layout-style-options-' + style_selected ).length > 0 ) {
			style_settings_container.find( '.js-wpv-layout-wizard-layout-style-options-' + style_selected ).show();
			style_container.addClass( 'wpv-layout-wizard-layout-style-has-settings' );
			$( 'input[value=' + style_selected + ']' )
				.parents( 'li' )
					.append( dialog_pointer );
		}
	});
	
	// Add a field
	
	$( document ).on( 'click', '.js-layout-wizard-add-field', function( e ) {
		if ( self.add_field_ui ) {
			self.add_field_ui_callback( self.add_field_ui );
		} else {
			data_view_id = $('.js-post_ID').val();
			var data = {
				action: 'layout_wizard_add_field',
				id: '__wpv_layout_count_placeholder__',
				wpnonce : $('#layout_wizard_nonce').attr('value'),
				view_id: data_view_id
			};
			$.post( ajaxurl, data, function( response ) {
				if (response.length <=0) {
					$.colorbox.close();
					$('.js-wpv-settings-layout-extra .js-wpv-toolset-messages')
						.wpvToolsetMessage({
							text: wpv_layout_wizard_strings.unknown_error,
							type:'error',
							inline:true,
							stay:false
						});
					return;
				}
				self.add_field_ui = $.parseJSON( response ).html;
				self.add_field_ui_callback( self.add_field_ui );
			}, 'html');
		}
	});
	
	// Shows or hides the Content Template dropdown when selecting a field
	// @todo rename this selector
	
	$( document ).on( 'change', 'select[name="layout-wizard-style"]', function() {
		var thiz = $( this ),
		thiz_container = thiz.parents( 'li' );
		option = thiz.find( ':selected' );
		if ( option.data( 'rowtitle' ) === 'Body' ) {
			thiz_container.find('.js-layout-wizard-body-template-text').show();
			thiz_container.find('.js-custom-types-fields').hide();
		} else if ( option.data( 'istype' ) == 1 ) {
			thiz_container.find('.js-layout-wizard-body-template-text').hide();
			thiz_container.find('.js-custom-types-fields')
				.attr( 'rel', option.data( 'typename' ) )
				.show();
		} else {
			thiz_container.find('.js-layout-wizard-body-template-text, .js-custom-types-fields').hide();
		}
	});
	
	// Set the Content Template when using a Body field
	
	$( document ).on( 'change', 'select.js-wpv-layout-wizard-body-template', function() {
		var thiz = $( this ),
		thiz_container = thiz.parents( 'li' );
        thiz_container.find('[name="layout-wizard-style"] [data-rowtitle="Body"]').val( thiz.val() );
    });
	
	// Insert layout
	
	$( document ).on( 'click', '.js-insert-layout', function( e ) {
		var thiz = $( this ),
		index = $('.wpv-dialog-nav-tab').index( $('li').has('.active') );
        if ( index === 1 ) {
            var fields = [],
			spinnerContainer = $( '<div class="spinner ajax-loader">' ).insertAfter( thiz ).show();
			$.each( $( 'select[name="layout-wizard-style"]' ), function( index ) {
				value = $(this).val();
				headname = $('[value="'+value+'"]').data('headename');
				rowtitle = $('[value="'+value+'"]').data('rowtitle');
				fields[index] = Array( '', editor_decode64(value), '', rowtitle, headname, rowtitle );
			});
            if ( 
				$( '#js-wpv-use-view-loop-ct' ).prop( 'checked' ) 
				&& self.check_for_only_content_template_field( fields ) === false 
			) {
                self.use_loop_template = true;
            } else {
                self.use_loop_template = false;
            }
			thiz
				.prop( 'disabled', true )
				.removeClass( 'button-primary' )
				.addClass( 'button-secondary' );
            if (
				self.use_loop_template_id == ''
				&& self.use_loop_template 
			) {
                var data = {
                    wpnonce : $('#layout_wizard_nonce').attr('value'),
                    action: 'wpv_create_layout_content_template',
                    view_id: self.view_id,
                    view_name: $('.js-title').val()
                };                
                $.ajax({
                    async:false,
                    type:"POST",
                    url:ajaxurl,
                    data:data,
                    dataType: 'json',
                    success: function( response ) {
						var template_id = response.success;
						if ( template_id == 'error' ) {
							self.use_loop_template_id = '';
							self.use_loop_template_title = '';
						} else {
							var template_name = response.title;
							self.use_loop_template_id = template_id;
							self.use_loop_template_title = template_name;
							$( '.js-wpv-settings-inline-templates' ).show();
							if ( 
								template_id 
								&& $( '#wpv-ct-listing-' + template_id ).html() 
							) {
								$( '#wpv-ct-listing-' + template_id ).removeClass( 'hidden' );
							} else {
								$( '.js-wpv-content-template-view-list > ul' )
									.first()
										.prepend( response.html );
							}
                            $('.js-wpv-ct-listing-' + template_id + ' .js-wpv-ct-remove-from-view').prop( 'disabled', true );  
						}
                    },
                    error: function( ajaxContext ) {
                        console.log( "Error: ", ajaxContext.responseText );
                    },
                    complete: function() {
                        self.process_layout_wizard_data( fields, function() {
							spinnerContainer.remove();
							$.colorbox.close();
							codemirror_views_layout.refresh();
							codemirror_views_layout.focus();
							if ( $( '.js-wpv-section-unsaved' ).length <= 0 ) {
								setConfirmUnload(false);
							}
						});
                    }
                });
            } else {
                if ( $( '.js-wpv-ct-listing-' + self.use_loop_template_id ).html() !== '' ) {
                    if ( ! self.use_loop_template ) {
                        $( '.js-wpv-ct-listing-' + self.use_loop_template_id ).hide();
                    } else {
                        $( '.js-wpv-ct-listing-' + self.use_loop_template_id ).show();
                    }
                 }
                self.process_layout_wizard_data( fields, function() {
					spinnerContainer.remove();
					$.colorbox.close();
					codemirror_views_layout.refresh();
					codemirror_views_layout.focus();
					if ( $( '.js-wpv-section-unsaved' ).length <= 0 ) {
						setConfirmUnload(false);
					}
				});
            }
		}
		index++;
		$( '.wpv-dialog-nav-tab a' ).eq( index ).removeClass( 'js-tab-not-visited' );
		self.change_tab( false );
	});
	
	// Select2 behaviour
	
	// Close select2 when clicking outside the dropdowns
	$( document ).on( 'mousedown','.js-wpv-dialog-layout-wizard, #cboxOverlay',function( e ) {
		if ( $( e.target ).parents( '.js-select2' ).length === 0 ) {
			$( 'select.js-select2' ).each( function() {
				$( this ).select2( 'close' );
			});
		}
	});
	
	// Close select2 when opening a new one
	$( document ).on( 'select2-opening', '.js-select2', function( e ) {
		$( 'select.js-select2' ).each( function() {
			$( this ).select2( 'close' );
		});
	});
	
	// ---------------------------------
	// Init
	// ---------------------------------
	
	self.init = function() {
		self.fetch_initial_settings();
		if ( $('#js-loop-content-template').val() !== '' ) {
			self.use_loop_template_id = $( '#js-loop-content-template' ).val();
			self.use_loop_template_title = $( '#js-loop-content-template-name' ).val();
			$( '.js-wpv-ct-listing-' + self.use_loop_template_id + ' .js-wpv-ct-remove-from-view' ).prop( 'disabled', true );
			if ( $('.js-wpv-ct-listing-' + self.use_loop_template_id + ' .js-wpv-open-close-arrow').hasClass('icon-caret-down') ) {
				$( '.js-wpv-ct-listing-' + self.use_loop_template_id + ' .js-wpv-content-template-open' ).click();
			}
		}
	};
	
	self.init();

};

jQuery( document ).ready( function( $ ) {
    WPViews.layout_wizard = new WPViews.LayoutWizard( $ );
});

/**
* Types fields compatibility
*/

// Types compatibility
	jQuery(document).on('click', '.js-custom-types-fields', function(){
		WPViews.layout_wizard.wizard_dialog_fields = [];
		$i = 0;
		jQuery('select.js-layout-wizard-item').each(function() {
			WPViews.layout_wizard.wizard_dialog_fields[$i++] = jQuery(this).find(':selected').val();
		});

		jQuery.each(jQuery('.js-wpv-dialog-layout-wizard select.js-select2'),function(){
				jQuery(this).select2('destroy');
		});
		
		WPViews.layout_wizard.wizard_dialog = jQuery('#colorbox').clone();
		WPViews.layout_wizard.wizard_dialog_item = jQuery(this).parent().find('[name=layout-wizard-style]').find(':selected');


		WPViews.layout_wizard.wizard_dialog_item_parent = jQuery(this).parent();
		WPViews.layout_wizard.wizard_dialog_style = jQuery('input[name="layout-wizard-style"]:checked').val();

		var current = Base64.decode(WPViews.layout_wizard.wizard_dialog_item.val());
		var metatype = current.search(/types.*?field=/g) == -1 ? 'usermeta' : 'postmeta';
		if ( typeof(jQuery(this).data('type')) !== 'undefined') {
			metatype = jQuery(this).data('type');
		}
		typesWPViews.wizardEditShortcode(jQuery(this).attr('rel'), metatype, -1, current);
	});

function wpv_restore_wizard_popup(shortcode) {
    jQuery.colorbox({
         html: jQuery(WPViews.layout_wizard.wizard_dialog).find('#cboxLoadedContent').html(),
         onComplete: function() {
            var select = jQuery('#'+WPViews.layout_wizard.wizard_dialog_item_parent.prop('id')+' select option[value="'+WPViews.layout_wizard.wizard_dialog_item.val()+'"]');

            jQuery('#'+WPViews.layout_wizard.wizard_dialog_item_parent.prop('id')+' select').val(WPViews.layout_wizard.wizard_dialog_item.val());


            $i = 0;
            jQuery('select.js-layout-wizard-item').each(function() {
                /*jQuery(this).find('[value="'+wpv_all_selected[$i]+'"]').click();*/
                jQuery(this).val(WPViews.layout_wizard.wizard_dialog_fields[$i]);
                $i++;
            });

            select.val(Base64.encode(shortcode));

            jQuery('input[name=layout-wizard-style][value='+WPViews.layout_wizard.wizard_dialog_style+']').click();

            jQuery.each(jQuery('.js-wpv-dialog-layout-wizard select.js-select2'),function(){
                    jQuery(this).select2();
            });

         }
     });
}

function wpv_cancel_wizard_popup() {
    jQuery.colorbox({
         html: jQuery(WPViews.layout_wizard.wizard_dialog).find('#cboxLoadedContent').html(),
         onComplete: function() {
            var select = jQuery('#'+WPViews.layout_wizard.wizard_dialog_item_parent.prop('id')+' select option[value="'+WPViews.layout_wizard.wizard_dialog_item.val()+'"]');

            jQuery('#'+WPViews.layout_wizard.wizard_dialog_item_parent.prop('id')+' select').val(WPViews.layout_wizard.wizard_dialog_item.val());

            $i = 0;
            jQuery('select.js-layout-wizard-item').each(function() {
                jQuery(this).val(WPViews.layout_wizard.wizard_dialog_fields[$i]);
                $i++;
            });

            jQuery('input[name=layout-wizard-style][value='+WPViews.layout_wizard.wizard_dialog_style+']').click();

            jQuery.each(jQuery('.js-wpv-dialog-layout-wizard select.js-select2'),function(){
                    jQuery(this).select2();
            });

         }
     });
}


var Base64 = {

	// private property
	_keyStr : "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=",
	
	// public method for encoding
	encode : function (input) {
		var output = "";
		var chr1, chr2, chr3, enc1, enc2, enc3, enc4;
		var i = 0;
	
		input = Base64._utf8_encode(input);
	
		while (i < input.length) {
	
			chr1 = input.charCodeAt(i++);
			chr2 = input.charCodeAt(i++);
			chr3 = input.charCodeAt(i++);
	
			enc1 = chr1 >> 2;
			enc2 = ((chr1 & 3) << 4) | (chr2 >> 4);
			enc3 = ((chr2 & 15) << 2) | (chr3 >> 6);
			enc4 = chr3 & 63;
	
			if (isNaN(chr2)) {
				enc3 = enc4 = 64;
			} else if (isNaN(chr3)) {
				enc4 = 64;
			}
	
			output = output +
			this._keyStr.charAt(enc1) + this._keyStr.charAt(enc2) +
			this._keyStr.charAt(enc3) + this._keyStr.charAt(enc4);
	
		}
	
		return output;
	},
	
	// public method for decoding
	decode : function (input) {
		var output = "";
		var chr1, chr2, chr3;
		var enc1, enc2, enc3, enc4;
		var i = 0;
	
		input = input.replace(/[^A-Za-z0-9\+\/\=]/g, "");
	
		while (i < input.length) {
	
			enc1 = this._keyStr.indexOf(input.charAt(i++));
			enc2 = this._keyStr.indexOf(input.charAt(i++));
			enc3 = this._keyStr.indexOf(input.charAt(i++));
			enc4 = this._keyStr.indexOf(input.charAt(i++));
	
			chr1 = (enc1 << 2) | (enc2 >> 4);
			chr2 = ((enc2 & 15) << 4) | (enc3 >> 2);
			chr3 = ((enc3 & 3) << 6) | enc4;
	
			output = output + String.fromCharCode(chr1);
	
			if (enc3 != 64) {
				output = output + String.fromCharCode(chr2);
			}
			if (enc4 != 64) {
				output = output + String.fromCharCode(chr3);
			}
	
		}
	
		output = Base64._utf8_decode(output);
	
		return output;
	
	},
	
	// private method for UTF-8 encoding
	_utf8_encode : function (string) {
		string = string.replace(/\r\n/g,"\n");
		var utftext = "";
	
		for (var n = 0; n < string.length; n++) {
	
			var c = string.charCodeAt(n);
	
			if (c < 128) {
				utftext += String.fromCharCode(c);
			}
			else if((c > 127) && (c < 2048)) {
				utftext += String.fromCharCode((c >> 6) | 192);
				utftext += String.fromCharCode((c & 63) | 128);
			}
			else {
				utftext += String.fromCharCode((c >> 12) | 224);
				utftext += String.fromCharCode(((c >> 6) & 63) | 128);
				utftext += String.fromCharCode((c & 63) | 128);
			}
	
		}
	
		return utftext;
	},
	
	// private method for UTF-8 decoding
	_utf8_decode : function (utftext) {
		var string = "";
		var i = 0;
		var c = c1 = c2 = 0;
	
		while ( i < utftext.length ) {
	
			c = utftext.charCodeAt(i);
	
			if (c < 128) {
				string += String.fromCharCode(c);
				i++;
			}
			else if((c > 191) && (c < 224)) {
				c2 = utftext.charCodeAt(i+1);
				string += String.fromCharCode(((c & 31) << 6) | (c2 & 63));
				i += 2;
			}
			else {
				c2 = utftext.charCodeAt(i+1);
				c3 = utftext.charCodeAt(i+2);
				string += String.fromCharCode(((c & 15) << 12) | ((c2 & 63) << 6) | (c3 & 63));
				i += 3;
			}
	
		}
	
		return string;
	}

}
