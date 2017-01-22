<?php
function zah_archive_headings( $post ) {
	global $wp_query;
	/*
	echo '<xmp>';
	var_dump( $wp_query );
	echo '</xmp>';
	*/
	$obj = get_queried_object();
	$heading = '';
	if ( is_tag() && $obj && isset( $obj->name ) ) {
		$found = $wp_query->found_posts;
		$found = number_format( $found );
		$heading = $found . ' tagged "' . ucwords( $obj->name ) . '"';
	}

	if ( $heading ) {
		$heading = wptexturize( $heading );
	?>
		<h1 class="archive-heading"><?php echo $heading; ?></h1>
	<?php
	}
}
add_action( 'zah_before_content', 'zah_archive_headings' );
get_header();
?>
	<?php do_action( 'zah_before_content', $post ); ?>
	<div id="content">
	<?php
	if ( have_posts() ) :
		while ( have_posts() ) : the_post();
			get_template_part( 'content', $post->post_type );
		endwhile;
	endif;
	?>
	</div>

	<?php
	global $wp_query, $wp_rewrite;

	// Setting up default values based on the current URL.
	$pagenum_link = html_entity_decode( get_pagenum_link() );
	$url_parts = explode( '?', $pagenum_link );

	// Get max pages and current page out of the current query, if available.
	$total = isset( $wp_query->max_num_pages ) ? $wp_query->max_num_pages : 1;
	$current = get_query_var( 'paged' ) ? intval( get_query_var( 'paged' ) ) : 1;

	// Append the format placeholder to the base URL.
	$pagenum_link = trailingslashit( $url_parts[0] ) . '%_%';

	// URL base depends on permalink settings.
	$format = $wp_rewrite->using_index_permalinks() && ! strpos( $pagenum_link, 'index.php' ) ? 'index.php/' : '';
	$format .= $wp_rewrite->using_permalinks() ? user_trailingslashit( $wp_rewrite->pagination_base . '/%#%', 'paged' ) : '?paged=%#%';


	if( $current < $total ) {
		$format = str_replace( '%#%', $current + 1, $format );
		$url = str_replace( '%_%', $format, $pagenum_link );
		echo '<a href="' . $url . '" class="rounded-button" id="pagination">More</a>';
	}
	?>

<?php get_footer(); ?>
