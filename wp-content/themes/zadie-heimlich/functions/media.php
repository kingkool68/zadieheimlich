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


function zah_post_gallery( $nothing, $attr ) {
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

	$id = intval( $atts['id'] );

	if ( ! empty( $atts['include'] ) ) {
		$_attachments = get_posts( array( 
			'include' => $atts['include'],
			'post_status' => 'inherit',
			'post_type' => 'attachment',
			'post_mime_type' => 'image',
			'order' => $atts['order'],
			'orderby' => $atts['orderby']
		) );

		$attachments = array();
		foreach ( $_attachments as $key => $val ) {
			$attachments[$val->ID] = $_attachments[$key];
		}
	} elseif ( ! empty( $atts['exclude'] ) ) {
		$attachments = get_children( array(
			'post_parent' => $id,
			'exclude' => $atts['exclude'],
			'post_status' => 'inherit',
			'post_type' => 'attachment',
			'post_mime_type' => 'image',
			'order' => $atts['order'],
			'orderby' => $atts['orderby']
		) );
	} else {
		$attachments = get_children( array(
			'post_parent' => $id,
			'post_status' => 'inherit',
			'post_type' => 'attachment',
			'post_mime_type' => 'image',
			'order' => $atts['order'],
			'orderby' => $atts['orderby']
		) );
	}

	if ( empty( $attachments ) ) {
		return '';
	}

	if ( is_feed() ) {
		$output = "\n";
		foreach ( $attachments as $att_id => $attachment ) {
			$output .= zah_get_attachment_link( $att_id, $atts['size'], true ) . "\n";
		}
		return $output;
	}

	$columns = intval( $atts['columns'] );
	$img_size = $atts['size'];
	$itemwidth = $columns > 0 ? floor(100/$columns) : 100;


	$size_class = sanitize_html_class( $atts['size'] );
	
	$output = "<section class=\"gallery gallery-{$columns}-column gallery-size-{$img_size}\">";

	$i = 0;
	foreach ( $attachments as $id => $attachment ) {
		
		$attr = array( 'class' => 'attachment-' . $atts['size'] );
		if( trim( $attachment->post_excerpt ) ) {
			$attr['aria-describedby'] = "selector-$id";
		}
		
		$image_meta  = wp_get_attachment_metadata( $id );
		$img_width = $image_meta['width'];
		$img_height = $image_meta['height'];
		if( isset( $image_meta['sizes'][ $atts['size'] ] ) ) {
			$img_width = $image_meta['sizes'][ $atts['size'] ]['width'];
			$img_height = $image_meta['sizes'][ $atts['size'] ]['height'];
		}
		$orientation = '';
		if ( isset( $img_height, $img_width ) ) {
			$orientation = ( $img_height > $img_width ) ? 'portrait' : 'landscape';
			if( $img_height == $img_width ) {
				$orientation = 'square';
			}
		}
		
		$attr['class'] .= ' ' . $orientation;
		
		if( $i % $columns == 0 ) {
			$attr['class'] .= ' end';
		}
		
		if ( ! empty( $atts['link'] ) && 'file' === $atts['link'] ) {
			$image_output = zah_get_attachment_link( $id, $atts['size'], false, false, false, $attr );
		} elseif ( ! empty( $atts['link'] ) && 'none' === $atts['link'] ) {
			$image_output = wp_get_attachment_image( $id, $atts['size'], false, $attr );
		} else {
			$image_output = zah_get_attachment_link( $id, $atts['size'], true, false, false, $attr );
		}
		
		$output .= "$image_output";
		$i++;
	}
	
	$output .= '</section>';
	
	return $output;
}
add_filter( 'post_gallery', 'zah_post_gallery', 10, 2 );

/* Helpers */
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

function zah_get_attachment_link( $id = 0, $size = 'thumbnail', $permalink = false, $icon = false, $text = false, $attr = '' ) {
	$id = intval( $id );
	$_post = get_post( $id );
	$parent_post = get_post();

	if ( empty( $_post ) || ( 'attachment' != $_post->post_type ) || ! $url = wp_get_attachment_url( $_post->ID ) )
		return __( 'Missing Attachment' );

	if ( $permalink ) {
		//$url = get_attachment_link( $_post->ID );
		$url = zah_post_gallery_link( $parent_post->ID, $_post->post_name );
	}

	if ( $text ) {
		$link_text = $text;
	} elseif ( $size && 'none' != $size ) {
		$link_text = wp_get_attachment_image( $id, $size, $icon, $attr );
	} else {
		$link_text = '';
	}

	if ( trim( $link_text ) == '' )
		$link_text = $_post->post_title;

	/**
	 * Filter a retrieved attachment page link.
	 *
	 * @since 2.7.0
	 *
	 * @param string      $link_html The page link HTML output.
	 * @param int         $id        Post ID.
	 * @param string      $size      Image size. Default 'thumbnail'.
	 * @param bool        $permalink Whether to add permalink to image. Default false.
	 * @param bool        $icon      Whether to include an icon. Default false.
	 * @param string|bool $text      If string, will be link text. Default false.
	 */
	return apply_filters( 'wp_get_attachment_link', "<a href='$url'>$link_text</a>", $id, $size, $permalink, $icon, $text );
}