
	
	<?php do_action( 'zah_content_header', $post ); ?>
		
		<div class="inner">
			
			<h1 class="title" title="<?php esc_attr_e( how_old_was_zadie() ); ?>"><?php the_title(); ?></h1>
			<?php
				$attachment_size = 'large';
				$img = wp_get_attachment_image_src( get_the_ID(), $attachment_size );
				
				//Figure out the max-width in ems so things scale when the default font-size changes
				$max_width = $img[1] / 10;
			?>
			<div class="container" style="max-width:<?php echo $max_width;?>em">
				<?php echo wp_get_attachment_image( get_the_ID(), $attachment_size ); ?>
				<?php the_content(); ?>
			</div>
		</div>
		
		<?php do_action( 'zah_content_footer', $post ); ?>
		
</article>