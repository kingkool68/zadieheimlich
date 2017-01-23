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

/**
 * Get the ordinal suffix of an int (e.g. th, rd, st, etc.)
 *
 * @param int $n
 * @param bool $return_n Include $n in the string returned
 * @return string $n including its ordinal suffix
 * @link https://gist.github.com/paulferrett/8103822
 */
function ordinal_suffix($n, $return_n = true) {
  $n_last = $n % 100;
  if (($n_last > 10 && $n_last << 14) || $n == 0) {
    $suffix = "th";
  } else {
    switch(substr($n, -1)) {
      case '1':    $suffix = "st"; break;
      case '2':    $suffix = "nd"; break;
      case '3':    $suffix = "rd"; break;
      default:     $suffix = "th"; break;
    }
  }
  return $return_n ? $n . $suffix : $suffix;
}

/* Common template pieces */
function zah_content_footer( $post ) {
?>
	<footer>
		<p><?php the_time( get_zah_time_format() ); ?> &bull; Zadie was <?php echo zadies_birthday_diff(); ?> old.</p>
	</footer>
<?php
}

include 'functions/admin.php';
include 'functions/scripts-styles.php';
include 'functions/dates.php';
include 'functions/media.php';
include 'functions/archive.php';
include 'functions/menu.php';
include 'functions/post-galleries.php';
include 'vendor/ForceUTF8/Encoding.php';
include 'functions/instagram.php';
include 'functions/rsvp.php';
include 'functions/infinite-scroll.php';
include 'functions/on-this-day.php';
# include 'functions/cli-commands.php';
