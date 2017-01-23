<?php
function zah_archive_headings( $post ) {
	global $wp_query;
	$obj = get_queried_object();
	$heading = '';
	$subheading = array();
	$found = '';
	if ( is_tag() && $obj && isset( $obj->name ) ) {
		$heading = ucwords( $obj->name );
	}

	$year = get_query_var( 'year' );
	$month = get_query_var( 'monthnum' );
	$day = get_query_var( 'day' );
	if ( is_year() ) {
		$heading = $year;
	}

	if ( is_month() ) {
		$heading = date( 'F Y', strtotime( $year . '-' . $month . '-01' ) );
	}

	if ( is_day() ) {
		$heading = date( 'F d, Y', strtotime( $year . '-' . $month . '-' . $day ) );
	}


	if ( $found = $wp_query->found_posts ) {
		$found = number_format( $found );
		$subheading[] = $found . ' items';
	}

	$page = intval( get_query_var( 'paged' ) );
	if ( $page > 1 ) {
		$suffix = ordinal_suffix( $page, false );
		$subheading[] = $page . '<sup>' . $suffix . '</sup> page';
	}

	$archive_header = '';
	if ( $heading ) {
		$heading = wptexturize( $heading );
		$archive_header .= '<h1 class="heading">' . $heading . '</h1>';

		if ( ! empty( $subheading ) ) {
			$separator = ' <span class="separator" aria-hidden="true">&bull;</span> ';
			$archive_header .= '<p class="subheading">' . implode( $separator, $subheading ) . '</p>';
		}
	}

	if ( $archive_header ) {
	?>
		<section class="archive"><?php echo $archive_header; ?></section>
	<?php
	}
}
add_action( 'zah_before_content', 'zah_archive_headings' );
