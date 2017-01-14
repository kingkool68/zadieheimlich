<?php
if ( ! class_exists( 'WP_CLI' ) ) {
	return;
}

function fix_zadies_instagram_images() {
	$modified_posts = 0;
	// Get all Instagram posts
	$args = array(
		'posts_per_page' => -1,
		'post_type' => 'instagram',
		'post_status' => 'any',
	);
	$posts = get_posts( $args );
	foreach ( $posts as $post ) {
		$new_post_content = $post->post_content;
		if ( has_shortcode( $post->post_content, 'video' ) ) {
			// Get the ID of the Video attachment and store it as post_meta
			$args = array(
				'post_type' => 'attachment',
				'post_parent' => $post->ID,
				'post_mime_type' => 'video/mp4',
			);
			$video_posts = get_posts( $args );
			foreach ( $video_posts as $video_post ) {
				update_post_meta( $post->ID, '_video_id', $video_post->ID );
			}
		}

		// Strip inline images
		$new_post_content = preg_replace('/<img(.+) \/>/i', '', $new_post_content );

		// Strip all shortcodes
		$new_post_content = trim( strip_shortcodes( $new_post_content ) );

		if ( $new_post_content != $post->post_content ) {
			$new_post = array(
				'ID' => $post->ID,
				'post_content' => $new_post_content,
			);
			wp_update_post( $new_post );
			$modified_posts++;
		}
	}
	WP_CLI::line( 'Modified ' . $modified_posts . ' Instagram posts.' );
	WP_CLI::success( 'Done!' );
}
WP_CLI::add_command( 'zah-fix-instagrams', 'fix_zadies_instagram_images' );
