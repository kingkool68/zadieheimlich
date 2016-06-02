<?php
$post = get_post();

$the_year = date('Y');
$the_month = get_query_var( 'zah-on-this-month' );
$the_day = get_query_var( 'zah-on-this-day' );
$the_time = strtotime( $the_year . '-' . $the_month . '-' . $the_day );
$the_date = date( 'F j<\s\up>S</\s\up>', $the_time );

$months = array(
	'January',
	'February',
	'March',
	'April',
	'May',
	'June',
	'July',
	'August',
	'September',
	'October',
	'November',
	'December',
);
$days = range(1, 31);
?>


<article class="page">

	<?php //do_action( 'zah_content_header', $post ); ?>
	<h1 class="title">Nothing happened on <?php echo $the_date; ?></h1>
	<form class="inner" action="<?php echo esc_url( get_site_url() ); ?>/on-this-day/">
		<label class="hidden" for="zah-on-this-month">Select a month</label>
		<select name="zah-on-this-month" id="zah-on-this-month">
			<?php foreach ( $months as $index => $m ) :
				$val = $index + 1;
				if ( $val < 10 ) {
					$val = '0' . $val;
				}
			?>
				<option value="<?php echo esc_attr( $val ); ?>" <?php selected( $val, $the_month ); ?>><?php echo $m; ?></option>
			<?php endforeach; ?>
		</select>
		<label class="hidden" for="zah-on-this-day">Select a day</label>
		<select name="zah-on-this-day" id="zah-on-this-day">
			<?php foreach ( $days as $num ) :
				$val = $num;
				if ( $val < 10 ) {
					$val = '0' . $num;
				}
			?>
			<option value="<?php echo esc_attr( $val ); ?>" <?php selected( $val, $the_day ); ?>><?php echo $num; ?></option>
		<?php endforeach; ?>
		</select>

		<input type="submit" value="Try Another Date">
	</form>

	<?php do_action( 'zah_content_footer', $post ); ?>

</article>
