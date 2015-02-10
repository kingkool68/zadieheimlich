<?php
//Custom image sizes
add_image_size( '350-wide', 350 );

function zah_image_size_names_choose($sizes) {
	$addsizes = array(
		'350-wide' => '350 Wide'
	);
	return array_merge($sizes, $addsizes);
}
add_filter('image_size_names_choose', 'zah_image_size_names_choose');

function html5_img_caption_shortcode($string, $attr, $content = null) {
    extract(shortcode_atts(array(
        'id'    => '',
        'align' => 'alignnone',
        'width' => '',
        'caption' => '',
		'class' => ''
    ), $attr)
    );
    
	if ( (int) $width < 1 || empty($caption) ) {
        return $content;
	}
    
	$described_by = '';
	if ( $id ) {
		//Underscores in attributes are yucky.
		$id_attr = str_replace( '_', '-', esc_attr($id) );
		$described_by = 'aria-describedby="' .  $id_attr . '"';
		$id = 'id="' . $id_attr . '" ';
	}
	
	$inline_width = '';
	if( $align === 'alignleft' || $align === 'alignright' ) {
		//$inline_width = 'style="width: '. (10 + (int) $width) . 'px"';
	}
	$class.= ' wp-caption ' . esc_attr($align);
	
    return '<figure ' . $described_by . 'class="' .  $class . '" ' . $inline_width . '>' .
        do_shortcode( $content ) .
        '<figcaption class="wp-caption-text" ' . $id . '>' . $caption . '</figcaption>'.
        '</figure>';
}
add_filter('img_caption_shortcode', 'html5_img_caption_shortcode', 1, 3 );

function wrap_video_embeds_to_make_them_responsive($return, $data, $url) {
	$not_rich_embeds = array( 'https://twitter.com' );
	switch ( $data->type ) {
		case 'video':
		case 'rich':
			if ( !empty( $data->html ) && is_string( $data->html ) && !in_array($data->provider_url, $not_rich_embeds) ) {
				$return = '<div class="responsive-embed">' . $return . '</div>';
			}
		break;
	}
	return $return;
}
add_filter('oembed_dataparse', 'wrap_video_embeds_to_make_them_responsive', 10, 3);

function zah_attachment_link( $link, $post_id ) {
	$post = get_post( $post_id );
	$new_link = get_site_url() . '/attachment/' . $post->post_name . '/';
	return $new_link;
}
add_filter( 'attachment_link', 'zah_attachment_link', 2, 10 );


function zah_post_gallery( $attr ) {
	$post = get_post();

	static $instance = 0;
	$instance++;

	if ( ! empty( $attr['ids'] ) ) {
		// 'ids' is explicitly ordered, unless you specify otherwise.
		if ( empty( $attr['orderby'] ) ) {
			$attr['orderby'] = 'post__in';
		}
		$attr['include'] = $attr['ids'];
	}
	
	$atts = shortcode_atts( array(
		'order'      => 'ASC',
		'orderby'    => 'menu_order ID',
		'id'         => $post ? $post->ID : 0,
		'columns'    => 3,
		'size'       => 'thumbnail',
		'include'    => '',
		'exclude'    => '',
		'link'       => ''
	), $attr, 'gallery' );
	
	var_dump( $atts['size'] );

	$id = intval( $atts['id'] );

	if ( ! empty( $atts['include'] ) ) {
		$_attachments = get_posts( array( 'include' => $atts['include'], 'post_status' => 'inherit', 'post_type' => 'attachment', 'post_mime_type' => 'image', 'order' => $atts['order'], 'orderby' => $atts['orderby'] ) );

		$attachments = array();
		foreach ( $_attachments as $key => $val ) {
			$attachments[$val->ID] = $_attachments[$key];
		}
	} elseif ( ! empty( $atts['exclude'] ) ) {
		$attachments = get_children( array( 'post_parent' => $id, 'exclude' => $atts['exclude'], 'post_status' => 'inherit', 'post_type' => 'attachment', 'post_mime_type' => 'image', 'order' => $atts['order'], 'orderby' => $atts['orderby'] ) );
	} else {
		$attachments = get_children( array( 'post_parent' => $id, 'post_status' => 'inherit', 'post_type' => 'attachment', 'post_mime_type' => 'image', 'order' => $atts['order'], 'orderby' => $atts['orderby'] ) );
	}

	if ( empty( $attachments ) ) {
		return '';
	}

	if ( is_feed() ) {
		$output = "\n";
		foreach ( $attachments as $att_id => $attachment ) {
			$output .= wp_get_attachment_link( $att_id, $atts['size'], true ) . "\n";
		}
		return $output;
	}

	$columns = intval( $atts['columns'] );
	$itemwidth = $columns > 0 ? floor(100/$columns) : 100;


	$size_class = sanitize_html_class( $atts['size'] );
	
	$output = "<section class=\"gallery gallery-{$columns}-column\">";

	$i = 0;
	foreach ( $attachments as $id => $attachment ) {

		$attr = ( trim( $attachment->post_excerpt ) ) ? array( 'aria-describedby' => "selector-$id" ) : '';
		if ( ! empty( $atts['link'] ) && 'file' === $atts['link'] ) {
			$image_output = wp_get_attachment_link( $id, $atts['size'], false, false, false, $attr );
		} elseif ( ! empty( $atts['link'] ) && 'none' === $atts['link'] ) {
			$image_output = wp_get_attachment_image( $id, $atts['size'], false, $attr );
		} else {
			$image_output = wp_get_attachment_link( $id, $atts['size'], true, false, false, $attr );
		}
		$image_meta  = wp_get_attachment_metadata( $id );

		$orientation = '';
		if ( isset( $image_meta['height'], $image_meta['width'] ) ) {
			$orientation = ( $image_meta['height'] > $image_meta['width'] ) ? 'portrait' : 'landscape';
		}
		$output .= "$image_output";
	}
	
	$output .= '</section>';
	
	return $output;
}

add_filter( 'post_gallery', 'zah_post_gallery' );

//media_sideload_image() would be so much better if it simply returned the attachment ID instead of HTML
function media_sideload_image_return_id( $file, $post_id, $desc = null, $post_data = array() ) {
	if ( ! empty( $file ) ) {
		
		$file_array = array();
		if( !isset($post_data['file_name']) ) {
			// Set variables for storage, fix file filename for query strings.
			preg_match( '/[^\?]+\.(jpe?g|jpe|gif|png)\b/i', $file, $matches );
			$file_array['name'] = basename( $matches[0] );
		} else {
			$file_array['name'] = $post_data['file_name'];
		}
		
		// Download file to temp location.
		$file_array['tmp_name'] = download_url( $file );

		// If error storing temporarily, return the error.
		if ( is_wp_error( $file_array['tmp_name'] ) ) {
			return $file_array['tmp_name'];
		}

		// Do the validation and storage stuff.
		$id = media_handle_sideload( $file_array, $post_id, $desc, $post_data );

		// If error storing permanently, unlink.
		if ( is_wp_error( $id ) ) {
			@unlink( $file_array['tmp_name'] );
			
		}

		return $id;
	}
}