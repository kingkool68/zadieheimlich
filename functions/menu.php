<?php
register_nav_menus(
	array(
		'main-menu' => 'Main Menu'
	)
);

/**
 * Enqueue the neccessary dependencies to make the menu work.
 */
function zah_menu_wp_enqueue_scripts() {
	wp_enqueue_script( 'jquery' );
}
add_action( 'wp_enqueue_scripts', 'zah_menu_wp_enqueue_scripts' );

/**
 * Output the markup for the menu in the footer of the site using a custom action called 'zah_footer'
 */
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

/**
 * Output jQuery needed to make the menu function. Inlining this JavaScript to save an HTTP request.
 * @return [type] [description]
 */
function zah_menu_wp_footer() {
?>
<script>
jQuery(document).ready(function($) {
	// Add the clss 'js' to th <body> and append an element to fade out the body of the site while the menu is open, and listen for events to close the menu.
	$('body').addClass('js').find('header').eq(0).before('<div id="magic-shield" />');

	// If the menu is open, pressing the escap key should close the menu.
	function bindEscapeKey(e) {
		if (e.keyCode != 27) {
			return;
		}
		$('#magic-shield').keydown();
	}

	// When the Menu button is clicked, open the menu.
	$('header .menu').click(function(e) {
		e.preventDefault();
		$body = $('body');
		$body.toggleClass('nav-open');

		if( $body.is('.nav-open') ) {
			// The menu is open, engage the bindEscapeKey() function
			$body.on('keyup.bindEscape', bindEscapeKey );
			$('#menu .close').focus();
		}
	});

	// If the close button or the magic shield is clicked close the menu. Pay attention if the event is a keyboard event or a mouse event.
	$('#menu .close, #magic-shield').on('click keydown', function(e) {
		if( e.keyCode && e.keyCode != 13 && e.keyCode != 27 ) {
			return;
		}

		e.preventDefault();
		$body = $('body');
		$body.toggleClass('nav-open');

		if( !$body.is('.nav-open') ) {
			// If the event was a keyboard event then we can set focus back to the Menu button for a better flow for those navigating via keyboard.
			if( e.keyCode ) {
				$('header .menu').focus();
			}
			// The menu is closed, disengage the bindEscapeKey() function
			$body.off('keyup.bindEscape', bindEscapeKey );
		}
	});
});
</script>
<?php
}
add_action( 'wp_footer', 'zah_menu_wp_footer' );
