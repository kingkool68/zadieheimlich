<?php
register_nav_menus(
	array(
		'main-menu' => 'Main Menu',
		'more-menu' => 'Main Menu - More'
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
				'theme_location' => 'more-menu',
				'container' => false,
				'menu_class' => false,
				'menu_id' => false,
			);
			wp_nav_menu( $args );
			?>
			<p class="social-links">
				<a href="https://github.com/kingkool68/zadieheimlich" class="github" title="The code that powers this site is on GitHub"><?php echo zah_svg_icon( 'github' ); ?></a>
				<a href="https://www.instagram.com/lilzadiebug/" class="instagram" rel="me" title="Follow Zadie on Instagram @LilZadieBug"><?php echo zah_svg_icon( 'instagram' ); ?></a>
				<a href="https://www.facebook.com/media/set/?set=ft.10101891838917048&type=1" title="Zadie's on Facebook"><?php echo zah_svg_icon( 'facebook' ); ?></a>
			</p>
		</section>
	</nav>
<?php
}
add_action( 'zah_footer', 'zah_menu_footer' );

function zah_filter_nav_menu_items( $items, $args ) {
	if ( ! is_object( $args ) || ! isset( $args->theme_location ) || $args->theme_location != 'main-menu' ) {
		return $items;
	}
	$items .= '<li><a href="#" class="more-nav">More +</a></li>';
	return $items;
}
add_filter( 'wp_nav_menu_items', 'zah_filter_nav_menu_items', 10, 2 );

function zah_nav_menu_css_class($class, $item, $args){
	return array();
}
add_filter('nav_menu_css_class' , 'zah_nav_menu_css_class' , 10 , 3);

function zah_nav_menu_item_id($id) {
	return '';
}
add_filter('nav_menu_item_id', 'zah_nav_menu_item_id');
