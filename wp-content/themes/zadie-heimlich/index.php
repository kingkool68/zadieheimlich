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
	
	<nav><?php echo get_next_posts_link('More');?> </nav>

<?php get_footer(); ?>