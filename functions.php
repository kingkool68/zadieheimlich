<?php

function zah_after_setup_theme() {
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'title-tag' );
	add_theme_support( 'html5', array( 'comment-list', 'comment-form', 'search-form', 'gallery', 'caption' ) );
}
add_action( 'after_setup_theme', 'zah_after_setup_theme' );


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
include 'functions/scripts-styles.php';
include 'functions/dates.php';
include 'functions/media.php';
include 'functions/menu.php';
include 'functions/post-galleries.php';
include 'functions/instagram.php';
include 'functions/rsvp.php';
include 'functions/infinite-scroll.php';
include 'functions/on-this-day.php';
