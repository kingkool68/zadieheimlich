<?php
$the_year = date('Y');
$the_month = get_query_var( 'zah-on-this-month' );
$the_day = get_query_var( 'zah-on-this-day' );
$the_time = strtotime( $the_year . '-' . $the_month . '-' . $the_day );
$the_date = date( 'F j<\s\up>S</\s\up>', $the_time );

get_header();
?>
	<?php get_template_part( 'content', 'on-this-day-switch-date-form' ); ?>
	<div id="content">
		<article class="page">
			<h1 class="title">Nothing happened on <?php echo $the_date; ?></h1>
			<?php do_action( 'zah_content_footer', $post ); ?>
		</article>
	</div>

<?php get_footer();
