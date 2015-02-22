<?php
function zah_post_gallery_generate_rewrite_rules($wp_rewrite) {
	$new_rules = array(
		'([^/]+)/gallery/([^/]+)?/?' => 'index.php?name=$matches[1]&post_gallery=1&attachment=$matches[2]',
		'([^/]+)/gallery/?$' => 'index.php?name=$matches[1]&post_gallery=1&attachment='
	);
	
	$wp_rewrite->rules = $new_rules + $wp_rewrite->rules;

}
add_filter('generate_rewrite_rules', 'zah_post_gallery_generate_rewrite_rules');

function zah_post_gallery_query_vars( $query_vars ) {
	$query_vars[] = 'post_gallery';
	return $query_vars;
}
add_filter('query_vars', 'zah_post_gallery_query_vars' );

function zah_post_gallery_redirect_canonical($redirect_url, $requested_url) {
	if( is_post_gallery() ) {
		return false;
	}
 return $redirect_url;
}
add_filter( 'redirect_canonical','zah_post_gallery_redirect_canonical', 10, 2 );

function zah_post_gallery_template_redirect() {
	if( is_post_gallery() && !get_query_var('attachment') ) {
		$post = get_post();
		wp_redirect( get_permalink( $post->ID ), 301 );
		die();
	}
}
add_action('template_redirect', 'zah_post_gallery_template_redirect');

function zah_post_gallery_template_include( $orig_template ) {
	if( is_post_gallery() ) {
		if( $new_template = get_attachment_template() ) {
			return $new_template;
		}
		
		if( $new_template = get_single_template() ) {
			return $new_template;
		}
	}
	
	return $orig_template;
}
add_filter('template_include', 'zah_post_gallery_template_include');

function zah_post_gallery_pre_get_posts( $query ) {
	if( is_post_gallery() && get_query_var('attachment') && $query->is_main_query() ) {
		$query->set('original_name', get_query_var('name') );
		$query->set('name', get_query_var('attachment') );
	}
}
add_action( 'pre_get_posts', 'zah_post_gallery_pre_get_posts' );

/* Hooks & Filters */
function zah_gallery_before_template_part( $post ) {
	if( !is_post_gallery() ) {
		return;
	}
	
	$nav = zah_post_gallery_get_nav();
	$parent_post = $nav->parent;
?>

<p class="parent-post"><a href="<?php echo get_permalink( $parent_post->ID ); ?>"><span class="arrow">&larr;</span> <?php echo $parent_post->post_title; ?></a></p>

<?php
}
add_action( 'zah_attachment_before_template_part', 'zah_gallery_before_template_part' );

function zah_gallery_after_article( $post ) {
	if( !is_post_gallery() ) {
		return;
	}
	
	$nav = zah_post_gallery_get_nav();
	$parent_post = $nav->parent;
	
	//wp_enqueue_script( 'post-gallery' );
?>

<nav>
	<a href="<?php echo $nav->next_permalink ?>" class="next">Next <span class="arrow">&rarr;</span></a>
	<a href="<?php echo $nav->prev_permalink ?>" class="prev"><span class="arrow">&larr;</span> Prev</a>
	<p class="progress"><?php echo $nav->current;?>/<?php echo $nav->total;?></p>
</nav>
<?php
}
add_action( 'zah_attachment_after_article', 'zah_gallery_after_article' );

/* Helper Functions */

function is_post_gallery() {
	if( get_query_var( 'post_gallery' ) == '1' ) {
		return true;
	}
	
	return false;
}

function zah_get_post_by_slug( $the_slug ) {
	global $wpdb;
	if( !$the_slug ) {
		return array();
	}
	
	$args = array(
		'pagename' => $the_slug,
		'no_found_rows' => true,
		'post_type' => 'any',
		//'posts_per_page' => 1
	);
	$posts = new WP_Query( $args );
	$posts = $posts->posts;
	//var_dump( $posts, $args );
	echo $wpdb->last_query;
	
	if( !$posts ) {
		return array();
	}
	
	return $posts[0];
}

function zah_post_gallery_get_gallery_posts() {
	if( !is_post_gallery() || !get_query_var('attachment') ) {
		return array();
	}
	
	$parent_post = get_page_by_path( get_query_var('original_name'), 'OBJECT', get_post_types() );
	
	$defaults = array(
		'order' => 'ASC',
		'orderby' => 'post__in',
		'id' => $parent_post->ID,
		'include' => '',
		'exclude' => '',
		'numberposts' => -1
	);
		
		
	preg_match('/\[gallery(.+)?\]/i', $parent_post->post_content, $matches);
	if( $atts = $matches[1] ) {
		$atts = shortcode_parse_atts( $atts );
	}
	$sort_args = wp_parse_args( $atts, $defaults );
	
	$post_in = explode( ',', $sort_args['ids'] );
	$args = array(
		'post_type' => 'attachment',
		'orderby' => $sort_args['orderby'],
		'order' => $sort_args['order'],
		'post__in' => $post_in,
		'numberposts' => $sort_args['numberposts']
	);
	
	$output = (object) array(
		'parent_id' => $parent_post->ID,
		'attachments' => array()
	);
	foreach( get_posts($args) as $post ):
		$output->attachments[] = (object) array(
			'ID' => $post->ID,
			'post_name' => $post->post_name,
			'post_title' => $post->post_title,
			'post_url' => get_permalink($post->ID)
		);
	endforeach;
	
	return $output;
}

function zah_post_gallery_get_nav() {
	
	$output = wp_cache_get( 'zah_post_gallery_get_nav' );
	if( !$output ) {
		$posts = zah_post_gallery_get_gallery_posts();
		if( !$posts ) {
			return array();
		}
		
		$total_attachments = count( $posts->attachments );
		$count = 0;
		$current = $next = $prev = 0;
		$post_id = get_the_ID();
		foreach( $posts->attachments as $attachment ) {
			if( $attachment->ID == $post_id ) {
				$current = $count;
				$next = $count + 1;
				$prev = $count - 1;
				if( $prev < 0 ) {
					$prev = $total_attachments - 1;
				}
				if( $next >= $total_attachments ) {
					$next = 0;
				}
				break;
			}
			$count++;
		}
		
		//pre_dump( $posts->attachments, $current, $next, $prev );
		
		$parent_permalink = get_permalink( $posts->parent_id );
		$next_slug = $posts->attachments[ $next ]->post_name;
		$prev_slug = $posts->attachments[ $prev ]->post_name;
		
		$output = (object) array(
			'parent' => get_post( $posts->parent_id ),
			'next_permalink' => $parent_permalink . 'gallery/' . $next_slug . '/',
			'prev_permalink' => $parent_permalink . 'gallery/' . $prev_slug . '/',
			'total' => $total_attachments,
			'current' => $current + 1
		);
		
		wp_cache_set( 'zah_post_gallery_get_nav', $output );
	}
	
	return $output;
}

function zah_post_gallery_link( $parent_id = FALSE, $attachment_slug = FALSE ) {
	if( !$parent_id || !$attachment_slug ) {
		return '';
	}
	return get_permalink( $parent_id ) . 'gallery/' . $attachment_slug . '/';
}