var WPViews = WPViews || {};

WPViews.ViewEditScreenInlineCT = function( $ ) {
	
	var self = this;
	
	self.view_id = $('.js-post_ID').val();
	
	self.codemirror_highlight_options = {
		className: 'wpv-codemirror-highlight'
	};
	self.spinner = '<span class="spinner ajax-loader"></span>&nbsp;&nbsp;';
	
	// ---------------------------------
	// Inline Content Template add dialog management
	// ---------------------------------
	
	// Open dialog
	
	$( document ).on( 'click', '.js-wpv-ct-assign-to-view', function() {
		var tid = $( this ).data('id'),
		data = {
			action : 'wpv_assign_ct_to_view',
			view_id : tid,
			wpnonce : $( '#wpv_inline_content_template' ).attr( 'value' )
		};
		$.colorbox({
			href: ajaxurl,
			data: data,
			onComplete:function() {
				$( '.js-wpv-assign-ct-already, .js-wpv-assign-ct-existing, .js-wpv-assign-ct-new' ).hide();
				$( '.js-wpv-inline-template-type' )
					.first()
						.trigger( 'click' );
				$( '.js-wpv-assign-inline-content-template' )
					.prop( 'disabled', true )
					.addClass( 'button-secondary' )
					.removeClass( 'button-primary' );
			}
		});
	});
	
	// Manage changes
	
	$( document ).on( 'change', '.js-wpv-inline-template-type', function() {
		var thiz = $( this ),
		thiz_val = thiz.val();
		$( '.js-wpv-assign-ct-already, .js-wpv-assign-ct-existing, .js-wpv-assign-ct-new' ).hide();
		$( '.js-wpv-assign-ct-' + thiz_val ).fadeIn( 'fast' );
		if ( thiz_val == 'already' ) {
			if ( $( '.js-wpv-inline-template-assigned-select' ).val() == 0 ) {
				$( '.js-wpv-assign-inline-content-template' )
					.prop( 'disabled', true )
					.addClass( 'button-secondary' )
					.removeClass( 'button-primary' );
			} else {
				$( '.js-wpv-assign-inline-content-template' )
					.prop( 'disabled', false )
					.removeClass( 'button-secondary' )
					.addClass( 'button-primary' );
			}
			$( '.js-wpv-inline-template-insert' ).hide();

		} else if ( thiz_val == 'existing' ) {
			if ( $( '.js-wpv-inline-template-existing-select').val() == 0 ) {
				$( '.js-wpv-assign-inline-content-template' )
					.prop( 'disabled', true )
					.addClass( 'button-secondary' )
					.removeClass( 'button-primary' );
			} else {
				$( '.js-wpv-assign-inline-content-template' )
					.prop( 'disabled', false )
					.removeClass( 'button-secondary' )
					.addClass( 'button-primary' );
			}
			$( '.js-wpv-inline-template-insert' ).show();
		} else if ( thiz_val == 'new' ) {
			if ( $( '.js-wpv-inline-template-new-name' ).val() == '' ) {
				$( '.js-wpv-assign-inline-content-template' )
					.prop( 'disabled', true )
					.addClass( 'button-secondary' )
					.removeClass( 'button-primary' );
			} else {
				$('.js-wpv-assign-inline-content-template')
					.prop( 'disabled', false )
					.removeClass( 'button-secondary' )
					.addClass( 'button-primary' );
			}
			$( '.js-wpv-inline-template-insert' ).show();
		}
	});
	
	$( document ).on( 'change', '.js-wpv-inline-template-assigned-select', function() {
		if ( $( '.js-wpv-inline-template-assigned-select' ).val() == 0 ) {
			$( '.js-wpv-assign-inline-content-template' )
				.prop( 'disabled', true )
				.addClass( 'button-secondary' )
				.removeClass( 'button-primary' );
		} else {
			$( '.js-wpv-assign-inline-content-template' )
				.prop( 'disabled', false )
				.removeClass( 'button-secondary' )
				.addClass( 'button-primary' );
		}
	});
	
	$( document ).on( 'change', '.js-wpv-inline-template-existing-select', function() {
		if ( $( '.js-wpv-inline-template-existing-select').val() == 0 ) {
			$( '.js-wpv-assign-inline-content-template' )
				.prop( 'disabled', true )
				.addClass( 'button-secondary' )
				.removeClass( 'button-primary' );
		} else {
			$( '.js-wpv-assign-inline-content-template' )
				.prop( 'disabled', false )
				.removeClass( 'button-secondary' )
				.addClass( 'button-primary' );
		}
	});
	
	$( document ).on( 'change keyup input cut paste', '.js-wpv-inline-template-new-name', function() {
		$( '.js-wpv-add-new-ct-name-error-container .toolset-alert' ).remove();
		if ( $( '.js-wpv-inline-template-new-name' ).val() == '' ) {
			$( '.js-wpv-assign-inline-content-template' )
				.prop( 'disabled', true )
				.addClass( 'button-secondary' )
				.removeClass( 'button-primary' );
		} else {
			$('.js-wpv-assign-inline-content-template')
				.prop( 'disabled', false )
				.removeClass( 'button-secondary' )
				.addClass( 'button-primary' );
		}
	});
	
	// Submit
	
	$( document ).on( 'click','.js-wpv-assign-inline-content-template', function() {
		// On AJAX, both #wpv_inline_content_template and #wpv-ct-inline-edit are valid nonces
		var thiz = $( this ),
		send_ajax = true,
		template_id = false,
		template_name = '',
		type = $( '.js-wpv-inline-template-type:checked' ).val(),
		spinnerContainer = $('<div class="spinner ajax-loader auto-update">').insertAfter( thiz ).show();
		thiz
			.prop( 'disabled', true )
			.removeClass( 'button-primary' )
			.addClass( 'button-secondary' );
		if ( type == 'existing' ) {
			if ( $( '.js-wpv-inline-template-existing-select' ).val() == '' ) {
				return;
			}
			template_id = $( '.js-wpv-inline-template-existing-select' ).val();
			template_name = $( '.js-wpv-inline-template-existing-select option:selected' ).text();
			data = {
				action : 'wpv_add_view_template',
				view_id : $( '.js-wpv-ct-assign-to-view' ).data( 'id' ),
				template_id : template_id,
				wpnonce : $( '#wpv_inline_content_template' ).attr( 'value' )
			};
		} else if ( type == 'new' ) {
			if ( $( '.js-wpv-inline-template-new-name' ).val() == '' ) {
				return;
			}
			template_name = $( '.js-wpv-inline-template-new-name' ).val();
			data = {
				action : 'wpv_add_view_template',
				view_id : $('.js-wpv-ct-assign-to-view').data('id'),
				template_name : template_name,
				wpnonce : $('#wpv-ct-inline-edit').attr('value')
			};
		} else if ( type == 'already' ) {
			send_ajax = false;
			template_id = $( '.js-wpv-inline-template-assigned-select' ).val();
			template_name = $( '.js-wpv-inline-template-assigned-select option:selected' ).text();
		}
		if ( send_ajax ) {
			$.post( ajaxurl, data, function( response ) {
				if ( response == 'error' ) {
					console.log('Error: Content template not found in database');
					$('.wpv_ct_inline_message').remove();
					return false;
				} else if ( response == 'error_name' ) {
					$( '.js-wpv-add-new-ct-name-error-container' ).wpvToolsetMessage({
						text: wpv_inline_templates_strings.new_template_name_in_use,
						stay: true,
						close: false,
						type: ''
				 	});
				 	$( '.wpv_ct_inline_message' ).remove();
				 	return false;
				} else {
					$( '.js-wpv-settings-inline-templates' ).show();
					if ( template_id && $('#wpv-ct-listing-' + template_id ).html() ) {
						$( '#wpv-ct-listing-' + template_id )
							.removeClass( 'hidden' );
					} else {
						$( '.js-wpv-content-template-view-list > ul' )
							.first()
								.append( response );
						template_id = $( '.js-wpv-content-template-view-list > ul > li' )
							.last()
								.data( 'id' );
					}
					self.add_content_template_shortcode( template_name, template_id );
					$( '.wpv_ct_inline_message' ).remove();
					$.colorbox.close();
				}
			})
			.fail( function( jqXHR, textStatus, errorThrown ) {
				//console.log( "Error: ", textStatus, errorThrown );
			})
			.always( function() {
				spinnerContainer.remove();
			});
		} else {
			self.add_content_template_shortcode( template_name, template_id );
			$( '.wpv_ct_inline_message' ).remove();
			$.colorbox.close();
		}
		return false;
	});
	
	// Insert shortcode into textarea
	
	self.add_content_template_shortcode = function( template_name, template_id ) {
		if ( $( '.js-wpv-add-to-editor-check' ).prop('checked') == true || $( '.js-wpv-inline-template-type:checked' ).val() == 'already' ) {
			var content = '[wpv-post-body view_template="' + template_name + '"]',
			current_cursor = codemirror_views_layout.getCursor( true );
            codemirror_views_layout.setSelection( current_cursor, current_cursor );
            codemirror_views_layout.replaceSelection( content, 'end' );
			var end_cursor = codemirror_views_layout.getCursor( true ),
			content_template_marker = codemirror_views_layout.markText( current_cursor, end_cursor, self.codemirror_highlight_options ),
			pointer_content = $( '#js-wpv-inline-content-templates-dialogs .js-wpv-inserted-inline-content-template-pointer' );
			if ( pointer_content.hasClass( 'js-wpv-pointer-dismissed' ) ) {
				setTimeout( function() {
					  content_template_marker.clear();
				}, 2000);
			} else {
				var content_template_pointer = $('.layout-html-editor  .wpv-codemirror-highlight').first().pointer({
					pointerClass: 'wp-toolset-pointer wp-toolset-views-pointer',
					pointerWidth: 400,
					content: pointer_content.html(),
					position: {
						edge: 'bottom',
						align: 'left'
					},
					show: function( event, t ) {
						t.pointer.show();
						t.opened();
						var button_scroll = $('<button class="button button-primary-toolset alignright js-wpv-scroll-this">' + wpv_inline_templates_strings.pointer_scroll_to_template + '</button>');
						button_scroll.bind( 'click.pointer', function(e) {//We need to scroll there down
							e.preventDefault();
							content_template_marker.clear();
							if ( t.pointer.find( '.js-wpv-dismiss-pointer:checked' ).length > 0 ) {
								var pointer_name = t.pointer.find( '.js-wpv-dismiss-pointer:checked' ).data( 'pointer' );
								$( document ).trigger( 'js_event_wpv_dismiss_pointer', [ pointer_name ] );
							}
							t.element.pointer('close');
							if ( template_id ) {
								$('html, body').animate({
									scrollTop: $( '#wpv-ct-listing-' + template_id ).offset().top - 100
								}, 1000);
							}
						});
						button_scroll.insertAfter(  t.pointer.find('.wp-pointer-buttons .js-wpv-close-this') );
					},
					buttons: function( event, t ) {
						var button_close = $('<button class="button button-secondary alignleft js-wpv-close-this">' + wpv_inline_templates_strings.pointer_close + '</button>');
						button_close.bind( 'click.pointer', function( e ) {
							e.preventDefault();
							content_template_marker.clear();
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
				content_template_pointer.pointer('open');
			}
		}
	};
	
	// ---------------------------------
	// Inline Content Template change and update management
	// ---------------------------------
	
	// Open
	
	$( document ).on( 'click', '.js-wpv-content-template-open', function( e ) {
		e.preventDefault();
		var thiz = $( this ),
		template_id = thiz.data( 'target' ),
		li_container = $( '.js-wpv-inline-editor-container-' + template_id ),
		arrow = thiz.find( '.js-wpv-open-close-arrow' );
		li_container.slideToggle( 400 ,function() {
			arrow
				.toggleClass( 'icon-caret-down icon-caret-up' );
			if ( ! li_container.is(':hidden') ) {
				if ( ! window["wpv_ct_inline_editor_" + template_id] ) {
					// First time we open the inline CT, so we must get it
					var $spinnerContainer = $( '<div class="spinner ajax-loader">' ).insertAfter( thiz ).show();
					data = {
						action : 'wpv_ct_loader_inline',
						id : template_id,
						include_instructions : 'inline_content_template',
						wpnonce : $( '#wpv-ct-inline-edit' ).attr( 'value' )
					};
					$.post( ajaxurl, data, function( response ) {
						if ( response == 'error' ) {
							console.log('Error, Content Template not found.');
						} else {
							$( '.js-wpv-inline-editor-container-' + template_id ).html( response );
							if ( typeof cred_cred != 'undefined' ) {
								cred_cred.posts();// this should be an event!!!
							}
							window["wpv_ct_inline_editor_" + template_id] = icl_editor.codemirror( 'wpv-ct-inline-editor-' + template_id, true );
							window["wpv_ct_inline_editor_" + template_id].refresh();
							window["wpv_ct_inline_editor_val_" + template_id] = window["wpv_ct_inline_editor_" + template_id].getValue();
							//Add quicktags toolbar
							var wpv_inline_editor_qt = quicktags( { id: 'wpv-ct-inline-editor-'+template_id, buttons: 'strong,em,link,block,del,ins,img,ul,ol,li,code,close' } );
							WPV_Toolset.CodeMirror_instance['wpv_ct_inline_editor_' + template_id] = window["wpv_ct_inline_editor_" + template_id];
							WPV_Toolset.add_qt_editor_buttons( wpv_inline_editor_qt, WPV_Toolset.CodeMirror_instance['wpv_ct_inline_editor_' + template_id] );
							$( '.js-wpv-ct-update-inline-' + template_id ).prop( 'disabled', true );
							window["wpv_ct_inline_editor_" + template_id].on( 'change', function() {
								if( window["wpv_ct_inline_editor_val_" + template_id] !=  window["wpv_ct_inline_editor_" + template_id].getValue() ) {
									$( '.js-wpv-ct-update-inline-' + template_id )
										.removeClass('button-secondary')
										.addClass( 'button-primary js-wpv-section-unsaved' )
										.prop( 'disabled', false );
									setConfirmUnload( true );
								} else {
									$( '.js-wpv-ct-update-inline-' + template_id )
										.removeClass( 'button-primary js-wpv-section-unsaved' )
										.addClass( 'button-secondary' )
										.prop( 'disabled', true );
									$( '.js-wpv-ct-update-inline-' + template_id )
										.parent()
											.find( '.toolset-alert-error' )
												.remove();
									if ( $( '.js-wpv-section-unsaved' ).length < 1 ) {
										setConfirmUnload( false );
									}
								}
							});
						}
						$spinnerContainer.remove();
					});
				} else {
					window["wpv_ct_inline_editor_" + template_id].refresh();
				}
			}
		});
		return false;
	});
	
	// Update

	$( document ).on( 'click', '.js-wpv-ct-update-inline', function() {
		var thiz = $( this ),
		thiz_container = thiz.parents('.js-wpv-ct-listing' ),
		ct_id = thiz.data( 'id' ),
		ct_value = window["wpv_ct_inline_editor_" + ct_id].getValue(),
		spinnerContainer = $( self.spinner ).insertBefore( thiz ).show(),
		data = {
			action : 'wpv_ct_update_inline',
			ct_value : ct_value,
			ct_id : ct_id,
			wpnonce : $( '#wpv_inline_content_template' ).attr( 'value' )
		};
		$.post( ajaxurl, data, function( response ) {
			 if ( response == 0 ) {
				// TODO manage this error!
			 	console.log('Content Template not found');
			 } else {
				$( '.js-wpv-ct-update-inline-'+ ct_id )
					.parent()
						.find('.toolset-alert-error')
							.remove();
				$( '.js-wpv-ct-update-inline-' + ct_id )
					.prop( 'disabled', true )
					.removeClass( 'button-primary js-wpv-section-unsaved' )
					.addClass( 'button-secondary' );
				thiz_container.addClass( 'wpv-inline-content-template-saved' );
				setTimeout( function () {
					thiz_container.removeClass( 'wpv-inline-content-template-saved' );
				}, 500 );
				window["wpv_ct_inline_editor_val_" + ct_id] = ct_value;
				if ( $( '.js-wpv-section-unsaved' ).length < 1 ) {
					setConfirmUnload( false );
				}
			 }
		})
		.fail( function( jqXHR, textStatus, errorThrown ) {
			//console.log( "Error: ", textStatus, errorThrown );
		})
		.always( function() {
			spinnerContainer.remove();
		});
	});
	
	// Remove dialog
	
	$( document ).on( 'click', '.js-wpv-ct-remove-from-view', function( e ) {
		e.preventDefault();
		var thiz = $( this ),
		thiz_container = thiz.parents('.js-wpv-ct-listing' ),
		ct_id = thiz_container.data( 'id' );
		if ( $( '.js-wpv-dialog-remove-content-template-from-view' ).hasClass( 'js-wpv-dialog-dismissed' ) ) {
			data = {
				action : 'wpv_remove_content_template_from_view',
				view_id : self.view_id,
				template_id : ct_id,
				dismiss : 'true',
				wpnonce : $('#wpv-ct-inline-edit').attr( 'value' )
			};
			$.post( ajaxurl, data, function( response ) {
				self.remove_inline_content_template( ct_id, thiz_container );
			});
		} else {
			$.colorbox({
				inline: true,
				href:'.js-wpv-dialog-remove-content-template-from-view',
				open: true,
				onComplete: function() {
					$( document ).off( 'click', '.js-wpv-remove-template-from-view' );
					$( '.js-wpv-remove-template-from-view' )
						.addClass( 'button-primary' )
						.removeClass( 'button-secondary' )
						.prop( 'disabled', false );;
					$( document ).on( 'click', '.js-wpv-remove-template-from-view', function( e ) {
						e.preventDefault();
						var thiz = $( this ),
						dismiss = 'false',
						spinnerContainer = $('<div class="spinner ajax-loader auto-update">').insertAfter( thiz ).show();
						thiz
							.addClass( 'button-secondary' )
							.removeClass( 'button-primary' )
							.prop( 'disabled', true );
						if ( $( '.js-wpv-dettach-inline-content-template-dismiss' ).prop('checked') ) {
							dismiss = 'true';
						}
						var data = {
							action : 'wpv_remove_content_template_from_view',
							view_id : self.view_id,
							template_id : ct_id,
							dismiss : dismiss,
							wpnonce : $('#wpv-ct-inline-edit').attr( 'value' )
						};
						$.post( ajaxurl, data, function( response ) {
							self.remove_inline_content_template( ct_id, thiz_container );
							if ( dismiss == 'true' ) {
								$( '.js-wpv-dialog-remove-content-template-from-view' ).addClass( 'js-wpv-dialog-dismissed' );
							}
						})
						.fail( function( jqXHR, textStatus, errorThrown ) {
							//console.log( "Error: ", textStatus, errorThrown );
						})
						.always( function() {
							spinnerContainer.remove();
							$.colorbox.close();
						});
					});
				}
			});
		}
		return false;
	});
	
	self.remove_inline_content_template = function( template_id, template_container ) {
		template_container
			.addClass( 'wpv-inline-content-template-deleted' )
			.fadeOut( 500, function() {
				if ( typeof window["wpv_ct_inline_editor_" + template_id] !== 'undefined' ) {
					window["wpv_ct_inline_editor_" + template_id].focus();
					delete window["wpv_ct_inline_editor_" + template_id];
					delete window["wpv_ct_inline_editor_val_" + template_id];
					// We also need to delete it from the iclCodeMirror collection
					delete window.iclCodemirror["wpv-ct-inline-editor-" + template_id];
				}
				$( this ).remove();
				if ( $( "ul.js-wpv-inline-content-template-listing > li" ).length < 1 ) {
					$( '.js-wpv-settings-inline-templates' ).hide();
				}
			});
	};
	
	// Remove
	/*
	$( document ).on( 'click', '.js-remove-template-from-view', function( e ) {
		e.preventDefault();
		var data = {
			action : 'wpv_remove_content_template_from_view_process',
			view_id : $(this).data('viewid'),
			id : $(this).data('id'),
			wpnonce : $('#wpv-ct-inline-edit').attr( 'value' )
		};
		$.post( ajaxurl, data, function( response ) {
			$.colorbox.close();
			//console.log( response );
			// @todo enought of this nonsense of listing-show and listing-delete
			// shown are there, delete are deleted, FGS
			$('#wpv-ct-listing-'+id).removeClass('js-wpv-ct-listing-show').addClass('js-wpv-ct-listing-delete').addClass('hidden');
			if ( $(".js-wpv-content-template-view-list ul li.js-wpv-ct-listing-show").size() < 1 ){
				$('.js-wpv-content-template-section-errors').wpvToolsetMessage({
					type: 'info',
					stay: true,
					classname: 'wpv_ct_inline_message',
					text : wpv_inline_templates_strings.no_inline_templates,
					close: true,
					onClose: function() {
						$('.wpv-settings-templates').hide();
					}
				});
			}
			$('.js-wpv-content-template-section-errors').wpvToolsetMessage({
				text:wpv_inline_templates_strings.unassign_success,
				stay:false,
				close:true,
				fadeOut:2000,
				fadeIn: 1000,
				type: 'success'
			});
		});
		return false;
	});
	*/
	// ---------------------------------
	// Inline Content Template add dialog management
	// ---------------------------------
	
	self.init = function() {
	
	};
	
	self.init();

};

jQuery( document ).ready( function( $ ) {
    WPViews.view_edit_screen_inline_content_templates = new WPViews.ViewEditScreenInlineCT( $ );
});