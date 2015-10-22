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
			<h2 class="title">Main Menu</h2>
			<a href="#" class="close">
				<span aria-hidden="true">&times;</span>
				<span class="alt-text">Close</span>
			</a>
			<?php
			$args = array(
				'menu' => 'main-menu',
				'container' => false,
				'menu_class' => false,
				'menu_id' => false,
			);
			wp_nav_menu( $args );
			?>
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

	function bindEscapeKey(e) {
		if (e.keyCode != 27) {
			return;
		}
		$('#magic-shield').keydown();
	}

	$('header .menu').click(function(e) {
		e.preventDefault();
		$body = $('body');
		$body.toggleClass('nav-open');

		if( $body.is('.nav-open') ) {
			$body.on('keyup.bindEscape', bindEscapeKey );
			$('#menu .close').focus();
		}
	});

	$('#menu .close, #magic-shield').on('click keydown', function(e) {
		if( e.keyCode && e.keyCode != 13 && e.keyCode != 27 ) {
			return;
		}

		e.preventDefault();
		$body = $('body');
		$body.toggleClass('nav-open');

		if( !$body.is('.nav-open') ) {
			if( e.keyCode ) {
				$('header .menu').focus();
			}
			$body.off('keyup.bindEscape', bindEscapeKey );
		}
	});
});
</script>
<?php
}
add_action( 'wp_footer', 'zah_menu_wp_footer' );
