<?php
$themename = "Sahifa-child";
$themefolder = "sahifa-child";

define ('theme_name', $themename );
define ('theme_ver' , 1 );
add_action( 'wp_enqueue_scripts', 'theme_enqueue_styles' );
function theme_enqueue_styles() {
    wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );
}

remove_action( 'woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30 );
add_filter( 'woocommerce_payment_complete_order_status', 'virtual_order_payment_complete_order_status', 10, 2 );

/*function virtual_order_payment_complete_order_status( $order_status, $order_id ) {
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
}*/

/**
 * Auto Complete all WooCommerce orders.
 * Add to theme functions.php file
 */

add_action( 'woocommerce_thankyou', 'custom_woocommerce_auto_complete_order' );
function custom_woocommerce_auto_complete_order( $order_id ) {
	global $woocommerce;

	if ( !$order_id )
		return;
	$order = new WC_Order( $order_id );
	$order->update_status( 'completed' );
}
// Add WooCommerce customer username to edit/view order admin page
add_action( 'woocommerce_admin_order_data_after_billing_address', 'woo_display_order_username', 10, 1 );

function woo_display_order_username( $order ){

	global $post;

	$customer_user = get_post_meta( $post->ID, '_customer_user', true );
	echo '<p><strong style="display: block;">'.__('Customer Username').':</strong> <a href="user-edit.php?user_id=' . $customer_user . '">' . get_user_meta( $customer_user, 'nickname', true ) . '</a></p>';
}
?>