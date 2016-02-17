<?php
function zah_flush_cache() {
    if( class_exists( 'HyperCache' ) ) {
        $hypercache = HyperCache::$instance;
        
        $folder = trailingslashit( $hypercache->get_folder() );
        $url = get_site_url();
        $parts = parse_url( $url );
        $host = $parts['host'];
        $hypercache->remove_dir( $folder . $host );
    }

    if( function_exists( 'keycdn_flush_tags' ) ) {
        keycdn_flush_tags( 'html' );
    }
}
