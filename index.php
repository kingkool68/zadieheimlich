<?php get_header(); ?>
	
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
	if( $next_page_link = get_next_posts_link('More') ) {
		echo '<nav>' . $next_page_link . '</nav>';
	}
	?>

<?php get_footer(); ?>