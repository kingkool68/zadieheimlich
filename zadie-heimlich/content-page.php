<article class="page">
	
	<?php //do_action( 'zah_content_header', $post ); ?>
	
	<div class="inner">
		<h1 class="title"><?php the_title(); ?></h1>
		<?php the_content(); ?>
	</div>

	<?php do_action( 'zah_content_footer', $post ); ?>

</article>