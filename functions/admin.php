<?php
function zah_clear_dashboard_widgets() {
	global $wp_meta_boxes;
    $widgets = array(
        'normal' => array(
            'dashboard_activity',
            // 'dashboard_right_now',
            // 'dashboard_recent_comments',
            // 'dashboard_incoming_links',
            // 'dashboard_plugins',
            'wpseo-dashboard-overview' // Yoast SEO
        ),
        'side' => array(
            'dashboard_primary',
            'dashboard_quick_press',
            // 'dashboard_recent_drafts',
        ),
    );

    foreach( $widgets as $priotity => $keys ) {
        foreach( $keys as $key ) {
            unset( $wp_meta_boxes['dashboard'][ $priotity ]['core'][ $key ] );
        }
    }
}
add_action( 'wp_dashboard_setup', 'zah_clear_dashboard_widgets', 999 );
