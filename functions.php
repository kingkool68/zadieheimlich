<?php

function zah_after_setup_theme() {
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'title-tag' );
	add_theme_support( 'html5', array( 'comment-list', 'comment-form', 'search-form', 'gallery', 'caption' ) );
}
add_action( 'after_setup_theme', 'zah_after_setup_theme' );

function remove_wp_menu_from_admin_bar() {
	global $wp_admin_bar;
	$wp_admin_bar->remove_menu( 'wp-logo' );
}

add_action('wp_before_admin_bar_render', 'remove_wp_menu_from_admin_bar', 0);

//CSS & JS
function zah_wp_enqueue_scripts() {
	$suffix = '.min.css';
	if( isset( $_GET['debug-css'] ) || ( function_exists( 'rh_is_dev' ) && rh_is_dev() ) ) {
		$suffix = '.css';
	}
	wp_register_style( 'zah-google-fonts', 'https://fonts.googleapis.com/css?family=Crete+Round:400,400italic|Open+Sans:300italic,700italic,300,700', array(), NULL, 'all' );
	wp_register_style( 'zadie-heimlich', get_stylesheet_directory_uri() . '/css/zadie-heimlich' . $suffix, array('zah-google-fonts'), NULL, 'all' );
	wp_enqueue_style( 'zadie-heimlich' );

	/*
	if( !rh_is_dev() && !is_admin() ) {
		wp_register_script( 'zah-global-scripts', get_template_directory_uri() . '/js/global.min.js', array(), NULL, true );
		wp_enqueue_script( 'zah-global-scripts' );
	}
	*/

	wp_register_script( 'post-gallery', get_template_directory_uri() . '/js/post-gallery.js', array('jquery'), NULL, true );

	// The mediaelement styles are rolled in to the zadie-heimlich.css file via Gulp
	if( !is_admin() ) {
		wp_deregister_style('wp-mediaelement');
	}
}
add_action( 'wp_enqueue_scripts', 'zah_wp_enqueue_scripts' );

function zah_dequeue_script() {
	if( rh_is_dev() || is_admin() ) {
		return;
	}
	// 'wp-mediaelement'
	$dequeue_script_handles = array( 'jquery', 'wp-embed', 'zah-menu' );
	foreach( $dequeue_script_handles as $handle ) {
		wp_dequeue_script( $handle );
	}

	global $wp_scripts;
    foreach( $wp_scripts->queue as $handle ) {
		var_dump( $wp_scripts->registered[ $handle ] );
		$deps = $wp_scripts->registered[ $handle ]->deps;
		foreach( $deps as $index => $dep ) {
			if( in_array( $dep, $dequeue_script_handles) ) {
				unset( $deps[ $index ] );
			}
		}
		$wp_scripts->registered[ $handle ]->deps = $deps;
	}
}
// add_action( 'wp_print_scripts', 'zah_dequeue_script', 5 );
// add_action( 'wp_print_footer_scripts', 'zah_dequeue_script', 5 );

// Move any scripts enquued to the wp_head action to the wp_footer action for performance reasons. See http://www.kevinleary.net/move-javascript-bottom-wordpress/ 
function zah_move_head_scripts_to_footer() {
	remove_action( 'wp_head', 'wp_print_scripts' );
	remove_action( 'wp_head', 'wp_print_head_scripts', 9 );

	add_action( 'wp_footer', 'wp_print_scripts', 5 );
	add_action( 'wp_footer', 'wp_print_head_scripts', 5 );
}
add_action( 'after_setup_theme', 'zah_move_head_scripts_to_footer' );

// Disable printing the WP Emjoi styles injected into the <head>. They're bundled into our compiled stylesheet.
remove_action( 'wp_print_styles', 'print_emoji_styles' );


function pre_dump() {
	echo '<pre>';
	var_dump( func_get_args() );
	echo '</pre>';
}

/* Common template pieces */
function zah_content_footer( $post ) {
?>
	<footer>
		<p><?php the_time( get_zah_time_format() ); ?> &bull; Zadie was <?php echo zadies_birthday_diff(); ?> old.</p>
	</footer>
<?php
}
//add_action( 'zah_content_footer', 'zah_content_footer' );

include 'functions/admin.php';
include 'functions/dates.php';
include 'functions/media.php';
include 'functions/menu.php';
include 'functions/post-galleries.php';
include 'functions/instagram.php';
include 'functions/rsvp.php';
include 'functions/infinite-scroll.php';
