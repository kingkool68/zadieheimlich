<?php
register_nav_menus(
	array(
		'main-menu' => 'Main Menu'
	)
);

function zah_menu_wp_enqueue_scripts() {
	wp_enqueue_script( 'jquery' );
}
add_action( 'wp_enqueue_scripts', 'zah_menu_wp_enqueue_scripts' );

function zah_menu_footer() {
?>
	<nav id="menu">
		<section>
			<ul>
				<li><a href="#">About Zadie</a></li>
				<li><a href="#">Videos</a></li>
				<li><a href="#">Galleries</a></li>
			</ul>
		</section>
	</nav>
<?php
}
add_action( 'zah_footer', 'zah_menu_footer' );

function zah_menu_wp_footer() {
?>
<script>
jQuery(document).ready(function($) {
	$('body').addClass('js').find('header').eq(0).before('<div id="magic-shield" />');
	$('header .menu').click(function(e) {
		e.preventDefault();
		$('body').toggleClass('nav-open');
	});
	$('#magic-shield').on('click', function(e) {
		$('body').toggleClass('nav-open');
	});
});
</script>
<?php
}
add_action( 'wp_footer', 'zah_menu_wp_footer' );
