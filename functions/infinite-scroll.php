<?php

// Lets make rewrites work properly
function zah_infinite_scroll_rewrite_rules() {
	add_rewrite_endpoint( 'pages', EP_ALL );
}
add_action('init', 'zah_infinite_scroll_rewrite_rules');

function zah_infinite_scroll_pre_get_posts( $query ) {
    if ( is_admin() || !$query->is_main_query() ) {
        return;
	}


	if ( $query->is_archive() && ( $pages = zah_infinite_scroll_get_pages() ) ) {
		$start = $pages->start;

		if( $start == -1 ) {
			$query->set( 'nopaging', true );
		} else {
			$query->set( 'paged', $start );
		}

		return;
	}
}
add_action( 'pre_get_posts', 'zah_infinite_scroll_pre_get_posts' );

function zah_infinite_scroll_post_limits( $limit ) {
	global $wp_query;
	if ( is_admin() || !$wp_query->is_main_query() ) {
		return $limit;
	}

	if( $pages = zah_infinite_scroll_get_pages() ) {
		if( $pages->start == -1 ) {
			return $limit;
		}

		$new_limit = $pages->diff * $pages->posts_per_page + $pages->posts_per_page;
		$limit = str_replace(', ' . $pages->posts_per_page, ', ' . $new_limit, $limit);
	}
	return $limit;
}
add_filter( 'post_limits', 'zah_infinite_scroll_post_limits' );

function zah_infinite_scroll_wp() {
	global $wp_query, $paged;
	$pages = zah_infinite_scroll_get_pages();
	if( !$pages || !$pages->end ) {
		return;
	}

	$wp_query->set('paged', $pages->end);
	$paged = get_query_var( 'paged' );
}
add_action( 'wp', 'zah_infinite_scroll_wp' );

function zah_infinite_scroll_redirect_canonical( $redirect_url, $requested_url ) {
	// Kill the canonical 'paged' redirect if the pages query var is set. In other words we don't want /category/publications/?pages=1-10 to redirect to /category/publications/page/10/?pages=1-10 which is what would happen by default.
	if( get_query_var('pages') ) {
		$redirect_url = trailingslashit($requested_url);
	}
	return $redirect_url;
}
add_filter( 'redirect_canonical', 'zah_infinite_scroll_redirect_canonical', 10, 2 );

function zah_infinite_scroll_get_pagenum_link($result) {
	if( $pages = zah_infinite_scroll_get_pages() ) {
		$result = preg_replace('/pages\/([\d\-]+|all)\//i', '', $result); // Strip out anything that matches pages/{any digit or hypen or the word all}/
	}
	return $result;
}
add_filter('get_pagenum_link', 'zah_infinite_scroll_get_pagenum_link');

function zah_infinite_scroll_enqueue_script() {
    wp_register_script( 'zah-infinite-scroll', get_template_directory_uri() . '/js/infinite-scroll.js', array('jquery'), NULL, true );

	if( is_archive() || is_front_page() ) {
		wp_enqueue_script( 'zah-infinite-scroll' );
		$var = strtolower( get_query_var('pages') );
		if( $var == 'all' && !is_front_page() ) {
			wp_dequeue_script( 'zah-infinite-scroll' );
		}
	}
}
add_action( 'wp_enqueue_scripts', 'zah_infinite_scroll_enqueue_script' );

/***
* Helper Functions
***/

function zah_infinite_scroll_get_pages() {
	$pages_var = get_query_var('pages');
	if( !$pages_var ) {
		return false;
	}
	$start = 1;
	$end = false;
	$pages = explode( '-', $pages_var );

	if( count($pages) < 2 && isset($pages[0]) && !empty( $pages[0] ) ) {
		if( strtolower($pages[0]) == 'all' ) {
			$start = -1;
			$end = false;
		} else {
			$end = intval($pages[0]);
		}
	} else {
		$pages = array_map('intval', $pages);
		if( isset($pages[0]) && !empty( $pages[0] ) ) {
			$start = $pages[0];
		}

		if( isset( $pages[1] ) && !empty( $pages[1] ) ) {
			$end = $pages[1];
		}
	}

	return (object) array(
		'start' => $start,
		'end' => $end,
		'diff' => abs( $end - $start ),
		'posts_per_page' => intval(get_option('posts_per_page'))
	);
}
