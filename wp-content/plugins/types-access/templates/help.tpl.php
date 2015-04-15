<?php
if (!defined('ABSPATH'))  die('Security check');
if(!current_user_can('manage_options')) {
    die('Access Denied');
}

?>
<div class="wrap">
    <h2><?php _e('Access Help','wpcf_access') ?></h2>
    <h3 style="margin-top:3em;"><?php _e('Documentation and Support', 'wpcf_access'); ?></h3>
    <ul>
        <li><?php printf('<a target="_blank" href="http://wp-types.com/documentation/user-guides/#Access Plugin"><strong>%s</strong></a>'.__(' - everything you need to know about using Access', 'wpcf_access'),__('User Guides', 'wpcf_access')); ?></li>
        <li><?php printf('<a target="_blank" href="http://discover-wp.com/"><strong>%s</strong></a>'.__(' - learn to use Access by experimenting with fully-functional learning sites','wpcf_access'),__('Discover WP','wpcf_access') ); ?></li>
        <li><?php printf('<a target="_blank" href="http://wp-types.com/forums/forum/support-2/"><strong>%s</strong></a>'.__(' - online help by support staff', 'wpcf_access'),__('Support forum', 'wpcf_access') ); ?></li>
    </ul>
    <h3 style="margin-top:3em;"><?php _e('Debug information', 'wp-cred'); ?></h3>
    <p><?php
    printf(
    __( 'For retrieving debug information if asked by a support person, use the <a href="%s">debug information</a> page.', 'wpcf_access' ),
    admin_url('admin.php?page=types_access_debug')
    );
    ?></p>
</div>
