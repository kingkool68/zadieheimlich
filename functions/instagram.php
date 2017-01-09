<?php

use \ForceUTF8\Encoding;

class ZAH_Instagram {

	var $whitelisted_usernames = array( 'naudebynature', 'kingkool68', 'lilzadiebug' );
	var $subscription_errors = array();

	public function __construct() {
		add_action( 'init', array( $this, 'init' ) );
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'wp_ajax_zah_instagram_manual_sync', array( $this, 'manual_sync_ajax_callback' ) );
		add_action( 'pre_get_posts', array( $this, 'pre_get_posts' ) );
		add_action( 'manage_posts_custom_column' , array( $this, 'manage_posts_custom_column' ) );
		add_action( 'restrict_manage_posts', array( $this, 'add_no_tags_filter' ) );
		add_action( 'pre_get_posts', array( $this, 'get_posts_with_no_tags' ) );

		add_filter( 'the_content', array( $this, 'the_content' ) );
		add_filter( 'manage_instagram_posts_columns', array( $this, 'manage_instagram_posts_columns' ) );

		add_action( 'wp_dashboard_setup', array( $this, 'wp_dashboard_setup' ) );
	}

	function init() {
		$labels = array(
			'name'				=> 'Instagram',
			'singular_name'	   => 'Instagram',
			'menu_name'		   => 'Instagram',
			'parent_item_colon'   => 'Parent Instagram:',
			'all_items'		   => 'All Instagram Posts',
			'view_item'		   => 'View Instagram',
			'add_new_item'		=> 'Add New Instagram',
			'add_new'			 => 'Add New',
			'edit_item'		   => 'Edit Instagram',
			'update_item'		 => 'Update Instagram',
			'search_items'		=> 'Search Instagram',
			'not_found'		   => 'Not found',
			'not_found_in_trash'  => 'Not found in Trash',
		);
		$args = array(
			'label'			   => 'instagram',
			'description'		 => 'Instagram posts',
			'labels'			  => $labels,
			'supports'			=> array( 'title', 'editor', 'thumbnail', 'comments' ),
			'taxonomies'		  => array( 'category', 'post_tag' ),
			'hierarchical'		=> false,
			'public'			  => true,
			'show_ui'			 => true,
			'show_in_menu'		=> true,
			'show_in_nav_menus'   => true,
			'show_in_admin_bar'   => true,
			'menu_position'	   => 5,
			'menu_icon'		   => 'dashicons-camera',
			'can_export'		  => true,
			'has_archive'		 => true,
			'exclude_from_search' => false,
			'publicly_queryable'  => true,
			'capability_type'	 => 'page',
		);
		register_post_type( 'instagram', $args );
	}

	function admin_menu() {
		add_submenu_page( 'edit.php?post_type=instagram', 'Manual Sync', 'Manual Sync', 'manage_options', 'zah-instagram-sync', array( $this, 'manual_sync_submenu' ) );
		add_submenu_page( 'edit.php?post_type=instagram', 'Private Sync', 'Private Sync', 'manage_options', 'zah-instagram-private-sync', array( $this, 'private_sync_submenu' ) );
	}

	function manual_sync_submenu() {
		$action = '';
		if ( isset($_GET['action']  ) ) {
			$action = $_GET['action'];
		}

		// MANUAL SYNC
		if ( isset( $action ) && $action == 'manual-sync' ) {

			$date_limit = 0;
			$from_date = '';
			if ( isset( $_POST['date-limit'] ) ) {
				$date_limit = strtotime( $_POST['date-limit'] );
				$from_date = date( get_option( 'date_format' ), $date_limit );
			}
		?>
			<div class="wrap">
				<h1>Manaul Sync</h1>
				<?php if ( $from_date ) : ?>
					<p>From <strong><?php echo $from_date; ?></strong> until now.</p>
				<?php endif; ?>
				<div id="results">

				</div>
				<ul id="stats">
					<li>Total: <span id="total">0</span></li>
					<li>Skipped: <span id="skipped">0</span></li>
				</ul>
			</div>

			<script>
			jQuery(document).ready(function($) {

				var dateLimit = <?php echo intval( $date_limit ); ?>;

				function update_the_page( data ) {
					if( imgs = data.imgs ) {
						$('#results' ).append( '<p>' + imgs.join('</p><p>') + '</p>' );
					}
					var total = data.total + parseInt( $('#total').text() );
					$('#total' ).html( total );

					var skipped = data.skipped + parseInt( $('#skipped').text() );
					$('#skipped').html( skipped );
				}

				function send_the_ajax_request( data ) {
					$.post(ajaxurl, data, function(resp) {
						update_the_page( resp.data );
						if( next_max_id = resp.data.next_max_id ) {
							send_the_ajax_request({
								'action': 'zah_instagram_manual_sync',
								'date-limit': dateLimit,
								'next_max_id': next_max_id
							});
						} else {
							var total = parseInt( $('#total').text() );
							var skipped = parseInt( $('#skipped').text() );
							$('#stats').after('<p>All done :-)</p>');
						}
					});
				}

				//Kick things off...
				send_the_ajax_request({
					'action': 'zah_instagram_manual_sync',
					'date-limit': dateLimit
				});


			});
			</script>
		<?php
			return;
		}

		// DEFAULT
		?>
		<div class="wrap">
			<h1>Manaully Sync Instagram</h1>
			<p>Make sure all of the Instagram photos tagged with <strong>#ZadieAlyssa</strong> are saved as posts on this site. Nothing will be overwritten, only missing Instagram photos will be added.</p>
			<form action="<?php echo esc_url( admin_url( 'edit.php?post_type=instagram&page=zah-instagram-sync&action=manual-sync' ) );?>" method="post">

				<table class="form-table">
					<tbody>
						<tr class="form-field">
							<th scope="row" valign="top"><label for="date-limit">Date Limit</label></th>
							<td>
								<input type="date" name="date-limit" id="date-limit" value=""><br>
								<em>Only Sync Instagram photos posted between now and this date</em>
							</td>
						</tr>
					</tbody>
				</table>


				<p><input type="submit" class="button-primary" value="Sync"></p>
			</form>
		</div>
	<?php
	}

	public function manual_sync_ajax_callback() {
		$max_id = null;
		if ( isset($_POST['next_max_id']) ) {
			$max_id = trim( $_POST['next_max_id'] );
		}
		$resp = $this->fetch_instagram_tag( 'zadiealyssa', $max_id );
		$nodes = $resp->entry_data->TagPage[0]->tag->media->nodes;
		$last_node = end( $nodes );

		$next_max_id = false;
		if ( isset( $last_node->id ) ) {
			$next_max_id = $last_node->id;
		}

		$date_limit = 0;
		if ( isset( $_POST['date-limit'] ) ) {
			$date_limit = intval( $_POST['date-limit'] );
		}

		$output = array(
			'next_max_id' => $next_max_id,
			'imgs' => array(),
			'skipped' => 0,
			'total' => 0,
		);

		if ( isset( $_POST['next_max_id'] ) &&  $next_max_id == $_POST['next_max_id'] ) {
			unset( $output['next_max_id'] );
		}

		foreach ( $nodes as $node ) {

			// If the $img was posted later than our break limit then we need to stop
			if ( intval( $node->date ) < $date_limit ) {
				unset( $output['next_max_id'] );
				break;
			}

			$output['total']++;

			$instagram_link = 'https://www.instagram.com/p/' . $node->code . '/';
			$found = $this->does_instagram_permalink_exist( $instagram_link );

			if ( $found ) {
				$output['skipped']++;
			}

			if ( ! $found ) {
				$inserted = $this->insert_instagram_post( $node );
				if ( $inserted ) {
					$wp_permalink = get_permalink( $inserted );
					$caption = $node->caption;

					$posted = date( 'Y-m-d H:i:s', intval( $node->date ) ); // In GMT time

					$src = $node->thumbnail_src;
					$width = $height = 150;

					$output['imgs'][] = '<a href="' . $wp_permalink . '" target="_blank"><img src="' . $src . '" width="' . $width . '" height="' . $height . '"></a><br>' . $caption . '<br>' . get_date_from_gmt( $posted, 'F j, Y g:i a' );
				}
			}
		}

		wp_send_json_success( (object) $output );
	}

	function private_sync_submenu() {

		$result = '';
		if ( isset( $_POST['instagram-source'] ) && ! empty( $_POST['instagram-source'] ) && check_admin_referer( 'zah-instagram-private-sync' ) ) {
			$instagram_source = wp_unslash( $_POST['instagram-source'] );
			$json = $this->get_instagram_json_from_html( $instagram_source );
			$node = $json->entry_data->PostPage[0]->media;

			$instagram_link = 'https://www.instagram.com/p/' . $node->code . '/';
			$found = $this->does_instagram_permalink_exist( $instagram_link );

			$inserted = $this->insert_instagram_post( $node, $force_publish_status = true );
			if ( $inserted ) {
				$wp_permalink = get_permalink( $inserted );
				$caption = $node->caption;

				$posted = date( 'Y-m-d H:i:s', intval( $node->date ) ); // In GMT time

				$src = $node->display_src;
				$width = 150;
				$height = '';
				if ( isset( $node->thumbnail_src ) ) {
					$width = $height = 150;
					$src = $node->thumbnail_src;
				}

				$status = 'Success!';
				if ( $found ) {
					$status = 'WARNING: This post already exists!';
				}

				$result .= '<h2>' . $status . '</h2>';
				$result .= '<p><a href="' . $wp_permalink . '" target="_blank"><img src="' . $src . '" width="' . $width . '" height="' . $height . '"></a><br>' . $caption . '<br>' . get_date_from_gmt( $posted, 'F j, Y g:i a' ) . '</p>';
				$result .= '<hr>';
			}
		}
	?>
		<style>
			#instagram-source {
				display: block;
				max-width:800px;
				width: 95%;
			}
		</style>
		<div class="wrap">
			<?php if ( $result ) { echo $result; } ?>

			<h1>Private Sync</h1>
			<p>Paste the HTML source of the private Instagram post to scrape and sync it with this site.</p>
			<form action="<?php echo esc_url( admin_url( 'edit.php?post_type=instagram&page=zah-instagram-private-sync' ) );?>" method="post">
				<?php wp_nonce_field( 'zah-instagram-private-sync' ); ?>
				<label for="instagram-source">HTML Source</label>
				<textarea name="instagram-source" id="instagram-source" rows="5"></textarea>
				<?php submit_button( 'Sync', 'primary' ); ?>
			</form>
		</div>
	<?php
	}


	/* Filters */
	function pre_get_posts( $query ) {
		if (
			( $query->is_archive() || $query->is_home() ) &&
			$query->is_main_query() &&
			! is_admin()
		) {
			$query->set( 'post_type', array( 'post', 'instagram' ) );
		}
	}

	function the_content( $content ) {
		$post = get_post();
		if ( $post->post_type == 'instagram' ) {
			$content = preg_replace( '/\s(#(\w+))/im', ' <a href="https://instagram.com/explore/tags/$2/">$1</a>', $content );
			// $content = preg_replace('/^(#(\w+))/im', '<a href="https://instagram.com/explore/tags/$2/">$1</a>', $content);
			$content = preg_replace( '/\s(@(\w+))/im', ' <a href="http://instagram.com/$2">$1</a>', $content );
			// $content = preg_replace('/^(@(\w+))/im', '<a href="http://instagram.com/$2">$1</a>', $content);
			// $via = ' via <a href="' . $permalink . '" target="_blank">' . $username . '</a>';
		}
		return $content;
	}

	function set_html_content_type() {
		return 'text/html';
	}

	function manage_instagram_posts_columns( $columns ) {
		$new_columns = array(
			'cb' => $columns['cb'],
			'title' => $columns['title'],
			'instagram_photo' => 'Photo',
			'instagram_permalink' => 'Instagram Permalink',
		);
		$remove_columns = array( 'cb', 'title', 'categories' );
		foreach ( $remove_columns as $col ) {
			unset( $columns[ $col ] );
		}

		return array_merge( $new_columns, $columns );
	}

	function manage_posts_custom_column( $column, $post_id = 0 ) {

		switch ( $column ) {
			case 'instagram_photo':
				$post = get_post( $post_id );
				$featured_id = get_post_thumbnail_id( $post->ID );
				if ( ! $featured_id ) {
					// We don't have one so let's try and get a featured image...
					$media = get_attached_media( 'image', $post->ID );
					$media_ids = array_keys( $media );
					$featured_id = $media_ids[0];

					add_post_meta( $post->ID, '_thumbnail_id', $featured_id );
				}

				$img = wp_get_attachment_image_src( $featured_id, 'thumbnail' );
				echo '<a href="' . esc_url( get_permalink( $post->ID ) ) . '"><img src="' . esc_url( $img[0] ) . '" width="' . esc_attr( $img[1] ) . '" height="' . esc_attr( $img[2] ) . '"></a>';

			break;

			case 'instagram_permalink':
				$post = get_post( $post_id );
				echo '<a href="' . esc_url( $post->guid ) . '" target="_blank">@' . get_instagram_username() . '</a>';
			break;
		}

	}

	function add_no_tags_filter() {
		$whitelisted_post_types = array( 'post', 'instagram' );
		if( !in_array( get_current_screen()->post_type, $whitelisted_post_types ) ) {
			return;
		}

		$selected = ( isset( $_GET['tag-filter'] ) && $_GET['tag-filter'] == 'no-tags' );
		?>
		<select name="tag-filter">
			<option value="">All Tags</option>
			<option value="no-tags" <?php echo selected( $selected ); ?>>No Tags</option>
		</select>
		<?php
	}

	function get_posts_with_no_tags( $query ) {
		if( !is_admin() || !$query->is_main_query() ) {
			return;
		}

		if( !isset( $_GET['tag-filter'] ) || $_GET['tag-filter'] != 'no-tags' ) {
			return;
		}

		$tag_ids = get_terms( 'post_tag', array( 'fields' => 'ids' ) );
		$query->set( 'tax_query', array(
			array(
				'taxonomy' => 'post_tag',
				'field'	=> 'id',
				'terms'	=> $tag_ids,
				'operator' => 'NOT IN'
			)
		) );
	}

	/* Quick Sync Dashboard Widget */
	function wp_dashboard_setup() {
		wp_add_dashboard_widget( 'instagram-quick-sync', 'Instagram Quick Sync', array( $this, 'quick_sync_dashboard_widget' ) );
	}

	function quick_sync_dashboard_widget() {
		$two_days_ago = date( 'Y-m-d', strtotime( '-2 days' ) );
		?>
		<form action="<?php echo esc_url( admin_url( 'edit.php?post_type=instagram&page=zah-instagram-sync&action=manual-sync' ) );?>" method="post">
			<input type="hidden" name="date-limit" value="<?php echo esc_attr( $two_days_ago ); ?>">
			<input type="submit" class="button button-primary" value="Sync Last 48 Hours">
		</form>
		<?php
	}



	/* Helper Functions */

	public function get_instagram_json_from_html( $html = '' ) {
		// Parse the page response and extract the JSON string.
		// via https://github.com/raiym/instagram-php-scraper/blob/849f464bf53f84a93f86d1ecc6c806cc61c27fdc/src/InstagramScraper/Instagram.php#L32
		$arr = explode( 'window._sharedData = ', $html );
		$json = explode( ';</script>', $arr[1] );
		$json = $json[0];

		return json_decode( $json );
	}

	public function fetch_instagram_tag( $tag = 'zadiealyssa', $max_id = NULL, $min_id = NULL ) {
		$args = array();
		if ( $max_id ) {
			$args['max_id'] = $max_id;
		}

		$request = add_query_arg( $args, 'https://www.instagram.com/explore/tags/' . $tag . '/' );
		$response = wp_remote_get( $request );

		return $this->get_instagram_json_from_html( $response['body'] );
	}

	public function fetch_single_instagram( $code = '' ) {
		if ( ! $code ) {
			return array();
		}

		$args = array();
		$request = add_query_arg( $args, 'https://www.instagram.com/p/' . $code . '/' );
		$response = wp_remote_get( $request );

		return $this->get_instagram_json_from_html( $response['body'] );
	}

	public function insert_instagram_post( $node, $force_publish_status = false ) {
		if ( ! function_exists( 'download_url' ) ) {
			require_once ABSPATH . 'wp-admin/includes/admin.php';
		}

		$img = $node;
		// Check to see if $node is already a PostPage object. If not, try and fetch a single instagram post.
		if ( ! isset( $node->owner->is_private ) ) {
			$payload = $this->fetch_single_instagram( $node->code );
			if ( empty( $payload ) || ! $payload ) {
				return;
			}
			$img = $payload->entry_data->PostPage[0]->media;
		}

		$src = $img->display_src;
		$slug =  $img->code;
		$permalink = 'https://www.instagram.com/p/' . $slug . '/';

		$posted = date( 'Y-m-d H:i:s', intval( $img->date ) ); // In GMT time
		$username = $img->owner->username;
		$full_name = $img->owner->full_name;
		$caption = Encoding::fixUTF8( $img->caption );
		$title = preg_replace( '/\s#\w+/i', '', $caption );

		$post = array(
			'post_title' => $title,
			'post_content' => $caption,
			'post_status' => 'pending',
			'post_type' => 'instagram',
			'post_date' => get_date_from_gmt( $posted ),
			'post_date_gmt' => $posted,
			'guid' => $permalink,
		);
		if ( in_array( $username, $this->whitelisted_usernames ) || $force_publish_status ) {
			$post['post_status'] = 'publish';
		}

		$inserted = wp_insert_post( $post );
		if ( ! $inserted ) {
			// Maybe it's because of bad characters in the caption and title? Try again.
			$post['post_content'] = Encoding::fixUTF8( $caption );
			$post['post_title'] = Encoding::fixUTF8( $title );
			$inserted = wp_insert_post( $post );
		}

		if ( ! $inserted ) {
			// Welp... we tried.
			return false;
		}

		if ( $img->is_video ) {
			$video_file = $img->video_url;
			$tmp = download_url( $video_file );

			$file_array = array();
			$file_array['name'] = $slug . '.mp4';
			$file_array['tmp_name'] = $tmp;

			// If error storing temporarily, unlink
			if ( is_wp_error( $tmp ) ) {
				@unlink( $file_array['tmp_name'] );
				$file_array['tmp_name'] = '';
			}

			// do the validation and storage stuff
			$video_id = media_handle_sideload( $file_array, $inserted, $caption );

			// If error storing permanently, unlink
			if ( is_wp_error( $video_id ) ) {
				@unlink( $file_array['tmp_name'] );
			}
		}

		$attachment_data = array(
			'post_content' => $caption,
			'post_title' => 'Instagram: ' . $slug,
			'post_name' => $slug,
			'file_name' => $slug . '.jpg',
		);
		$attachment_id = media_sideload_image_return_id( $src, $inserted, $caption, $attachment_data );
		update_post_meta( $inserted, 'instagram_username', $username );

		// Set the featured image
		add_post_meta( $inserted, '_thumbnail_id', $attachment_id );

		// If we have a video id, store it as post meta
		if ( $video_id ) {
			add_post_meta( $inserted, '_video_id', $video_id );
		}

		if ( $post['post_status'] != 'publish' ) {
			// Send an email so we can approve the new photo ASAP!
			$this->send_pending_post_notification_email( $inserted, $attachment_id );
		}

		return $inserted;
	}

	public function get_instagram_username( $post_id = FALSE ) {
		if ( $post_id ) {
			$post_id = intval( $post_id );
		}

		if ( ! $post_id ) {
			$post_id = get_the_ID();
		}

		$output = get_post_meta( $post_id, 'instagram_username', true );
		if ( ! $output ) {
			$output = '';
		}

		return $output;
	}

	public function does_instagram_permalink_exist( $permalink ) {
		global $wpdb;

		$parts = parse_url( $permalink );
		$id = $parts['path'];

		$query = "SELECT `ID` FROM `" . $wpdb->posts . "` WHERE `guid` LIKE '%" . $id . "%' LIMIT 0,1;";
		return $wpdb->get_var( $query );
	}

	public function send_pending_post_notification_email( $post_id, $attachment_id ) {
		add_filter( 'wp_mail_content_type', array( $this, 'set_html_content_type' ) );

		$post = get_post( $post_id );
		$img = wp_get_attachment_image_src( $attachment_id, 'medium' );

		$subject = '#ZadieAlyssa: Pending post from ' . $this->get_instagram_username( $post_id );
		$html = '';
		$html .= '<p><a href="' . $post->guid . '" target="_blank"><img src="' . $img[0] . '" width="' . $img[1] . '" height="' . $img[2] . '"></a><p>';
		$html .= '<p>Edit this post at <a href="' . get_edit_post_link( $post_id ) . '" target="_blank">' . get_edit_post_link( $post_id ) . '</a></p>';

		// INSTAGRAM_PENDING_EMAIL_ADDRESS should be defined as a constant in wp-config.php so we don't post the email address publicly.
		wp_mail( INSTAGRAM_PENDING_EMAIL_ADDRESS, $subject, $html );

		// Reset content-type to avoid conflicts -- http://core.trac.wordpress.org/ticket/23578
		remove_filter( 'wp_mail_content_type', array( $this, 'set_html_content_type' ) );
	}
}

/* Global helper functions */
function get_instagram_username( $post_id = false ) {
	global $zah_instagram;
	return $zah_instagram->get_instagram_username( $post_id );
}

global $zah_instagram;
$zah_instagram = new ZAH_Instagram();
