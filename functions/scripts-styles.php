<?php

/**
 * Conditional helper to determine if we should use the concatenated global JavaScript file built by Gulp.js
 * @return [boolean]
 */
function zah_use_global_script_file() {
	if( is_admin() ) {
		return false;
	}
	if( function_exists( 'rh_is_prod' ) && rh_is_prod() ) {
		return true;
	}

	return false;
}

/**
 * Loads styles and scripts
 */
function zah_wp_enqueue_scripts() {
	// CSS
	$css_suffix = '.min.css';
	if( isset( $_GET['debug-css'] ) || ( function_exists( 'rh_is_dev' ) && rh_is_dev() ) ) {
		$css_suffix = '.css';
	}
	wp_register_style( 'zah-google-fonts', 'https://fonts.googleapis.com/css?family=Crete+Round:400,400italic|Open+Sans:300italic,700italic,300,700', array(), NULL, 'all' );
	wp_register_style( 'zadie-heimlich', get_stylesheet_directory_uri() . '/css/zadie-heimlich' . $css_suffix, array('zah-google-fonts'), NULL, 'all' );
	wp_enqueue_style( 'zadie-heimlich' );

	// The mediaelement styles are rolled in to the zadie-heimlich.css file via Gulp
	if( !is_admin() ) {
		wp_deregister_style('wp-mediaelement');
	}

	// JavaScript
	wp_register_script( 'post-gallery', get_template_directory_uri() . '/js/post-gallery.js', array('jquery'), NULL, true );

	// Global JavaScript files bundled into one that gets loaded on every single page
	wp_register_script( 'zah-global-scripts', get_template_directory_uri() . '/js/global.min.js', array('jquery'), NULL, true );
	if( zah_use_global_script_file() ) {
		add_filter( 'script_loader_tag', 'zah_dont_load_bundled_scripts', 10, 3 );
		wp_enqueue_script( 'zah-global-scripts' );
	}
}
add_action( 'wp_enqueue_scripts', 'zah_wp_enqueue_scripts' );

/**
 * If we're loading a bundled version of scripts then we don't want to load individual JavaScript files for certain script handles.
 * @param  [string] $script_element		<script> element to be rendered
 * @param  [string] $handle 			script handle that was registered
 * @param  [string] $script_src			src sttribute of the <script>
 * @return [string]						New <script> element
 */
function zah_dont_load_bundled_scripts( $script_element, $handle, $script_src ) {
	if( !zah_use_global_script_file() ) {
		return $script_element;
	}

	// These scripts are bundled together in 'zah-global-scripts' so they don't need to be printed to the screen.
	$blacklisted = array( 'jquery-migrate', 'wp-embed', 'zah-menu', 'mediaelement', 'wp-mediaelement' );
	if( in_array( $handle, $blacklisted ) ) {
		return '';
	}

	return $script_element;
}

/**
 * Serve jQuery via conditional comments so IE 8 and below get jQuery 1.x and everyone else is served jQuery 2.x
 * @param  [string] $script_element		<script> element to be rendered
 * @param  [string] $handle 			script handle that was registered
 * @param  [string] $script_src			src sttribute of the <script>
 * @return [string]						New <script> element
 */
function zah_rejigger_jquery( $script_element, $handle, $script_src ) {
	if( is_admin() ) {
		return $script_element;
	}

	if( $handle == 'jquery-core' || $handle == 'jquery' ) {
		$new_script_element = '';

		// jQuery 1.x gets served to IE8 and below...
		$new_script_element .= '<!--[if lt IE 9]>';
		$new_script_element .= $script_element;
		$new_script_element .= '<![endif]-->';

		// jQuery 2.x gets served to everyone else...
		$new_script_element .= '<!--[if (gte IE 9) | (!IE)]><!-->';
		$new_script_element .= '<script src="' . get_template_directory_uri() . '/js/jquery-2.min.js"></script>';
		$new_script_element .= '<!--<![endif]-->';

		return $new_script_element;
	}

	return $script_element;
}
add_filter( 'script_loader_tag', 'zah_rejigger_jquery', 10, 3 );

// Disable printing the WP Emjoi styles injected into the <head>. They're bundled into our compiled stylesheet.
remove_action( 'wp_print_styles', 'print_emoji_styles' );

/**
 * Move any scripts enquued to the wp_head action to the wp_footer action for performance reasons.
 * @see http://www.kevinleary.net/move-javascript-bottom-wordpress/
 */
function zah_move_head_scripts_to_footer() {
	remove_action( 'wp_head', 'wp_print_scripts' );
	remove_action( 'wp_head', 'wp_print_head_scripts', 9 );
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );

	add_action( 'wp_footer', 'wp_print_scripts', 11 );
	add_action( 'wp_footer', 'wp_print_head_scripts', 11 );
	add_action( 'wp_footer', 'print_emoji_detection_script', 7 );
}
add_action( 'after_setup_theme', 'zah_move_head_scripts_to_footer' );
