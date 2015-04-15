jQuery(function($) {

    $.extend($.colorbox.settings, { // override some Colorbox defaults
        transition: 'fade',
        opacity: 0.3,
        speed: 150,
        fadeOut : 0,
        closeButton: false,
        trapFocus: false
    });

    // Add .colorbox-active to the body element when colorbox is active
    $(document).on('cbox_complete', function() {

        if ( $('#colorbox .no-scrollbar').length === 0 ) {
            $('body').addClass('disable-scrollbar');
        }

        // trigger .button-primary to click when ENTER key is pressed and colorbox popup is opened
        $(document).on('keypress.colorbox', function(e) {
            var keycode = parseInt((e.keyCode ? e.keyCode : e.which),10);
            if ( keycode === 13 ) { // 13 is for ENTER key
                $('#cboxContent .wpv-dialog-footer .button-primary').click(); // trigger click event on the currently opened popup
            }
        });

    });

    $(document).on('cbox_cleanup', function() {
         $('body').removeClass('disable-scrollbar');
    });

    // Bind close event to .js-dialog-close classes
    $(document).on('click', '.js-dialog-close', function(e) {
        e.preventDefault();
        $.colorbox.close();
        return false;
    });
    
});