<?php

add_action( 'wp_enqueue_scripts', 'theme_enqueue_styles' );
function theme_enqueue_styles() {
    wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );

}
?>
//REBECCA CUSTOMIZATION
<?php
remove_action( 'woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30 );

add_filter( 'woocommerce_payment_complete_order_status', 'virtual_order_payment_complete_order_status', 10, 2 );
function virtual_order_payment_complete_order_status( $order_status, $order_id ) {
    $order = new WC_Order( $order_id );
    if ( 'processing' == $order_status &&
        ( 'on-hold' == $order->status || 'pending' == $order->status || 'failed' == $order->status ) ) {
        $virtual_order = null;
        if ( count( $order->get_items() ) > 0 ) {
            foreach( $order->get_items() as $item ) {
                if ( 'line_item' == $item['type'] ) {
                    $_product = $order->get_product_from_item( $item );
                    if ( ! $_product->is_virtual() ) {
                        // once we've found one non-virtual product we know we're done, break out of the loop
                        $virtual_order = false;
                        break;
                    } else {
                        $virtual_order = true;
                    }
                }
            }
        }
        // virtual order, mark as completed
        if ( $virtual_order ) {
            return 'completed';
        }
    }
    // non-virtual order, return original status
    return $order_status;
}



?>