<article class="page">

	<?php //do_action( 'zah_content_header', $post ); ?>
	<h1 class="title"><?php the_title(); ?></h1>
	<div class="inner">

		<?php the_content(); ?>
	</div>

	<?php do_action( 'zah_content_footer', $post ); ?>

</article>
