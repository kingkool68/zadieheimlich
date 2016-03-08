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
	if( isset( $_GET['debug-css'] ) ) {
		$suffix = '.css';
	}
	wp_register_style( 'zah-google-fonts', 'https://fonts.googleapis.com/css?family=Crete+Round:400,400italic|Open+Sans:300italic,700italic,300,700', array(), NULL, 'all' );
	wp_register_style( 'zadie-heimlich', get_stylesheet_directory_uri() . '/css/zadie-heimlich' . $suffix, array('zah-google-fonts'), NULL, 'all' );

	wp_enqueue_style( 'zadie-heimlich' );

	wp_register_script( 'post-gallery', get_template_directory_uri() . '/js/post-gallery.js', array('jquery'), NULL, true );

	// The mediaelement styles are rolled in to the zadie-heimlich.css file via Gulp
	if( !is_admin() ) {
		wp_deregister_style('wp-mediaelement');
	}
}
add_action( 'wp_enqueue_scripts', 'zah_wp_enqueue_scripts' );
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
