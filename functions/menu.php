<?php
register_nav_menus(
	array(
		'main-menu' => 'Main Menu'
	)
);

/**
 * Enqueue the JavaScript and neccessary dependencies to make the menu work.
 */
function zah_menu_wp_enqueue_scripts() {
	wp_enqueue_script( 'zah-menu', get_template_directory_uri() . '/js/menu.js', array('jquery'), NULL, true );
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
