<?php get_header(); ?>
	
	<div id="content">
	<?php
	if ( have_posts() ) :
		while ( have_posts() ) : the_post();
			$attachment_type = '';
			if( $post->post_mime_type ) {
				$pieces = explode('/', $post->post_mime_type);
				$attachment_type = $pieces[0];
			}
			?>
			
			<?php do_action( 'zah_attachment_before_article', $post ); ?>
			
			<article class="attachment-<?php echo $attachment_type; ?>">
				
				<?php do_action( 'zah_attachment_before_template_part', $post ); ?>
				<?php get_template_part( 'attachment', $attachment_type ); ?>
				<?php do_action( 'zah_attachment_after_template_part', $post ); ?>
			
			</article>
			
			<?php do_action( 'zah_attachment_after_article', $post ); ?>
			
			<?php
		endwhile;
	endif;
	?>
	</div>

<?php get_footer(); ?>