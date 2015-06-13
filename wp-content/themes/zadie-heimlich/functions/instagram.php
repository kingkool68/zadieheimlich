<?php

class ZAH_Instagram {
	
	var $whitelisted_usernames = array( 'naudebynature', 'kingkool68', 'lilzadiebug' );
	
	public function __construct() {
		add_action( 'init', array( $this, 'init' ) );
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'wp_ajax_zah_instagram_manual_sync', array($this, 'manual_sync_ajax_callback' ) );
		add_action( 'pre_get_posts', array($this, 'pre_get_posts') );
		add_action( 'wp', array($this, 'wp') );
		add_action( 'instagram_subscription_tag_zadiealyssa', array($this, 'instagram_realtime_update') );
		add_action( 'manage_posts_custom_column' , array($this, 'manage_posts_custom_column') );
		
		add_filter( 'the_content', array($this, 'the_content') );
		add_filter( 'manage_instagram_posts_columns', array($this, 'manage_instagram_posts_columns') );

		add_action('wp_dashboard_setup', array($this, 'wp_dashboard_setup') );
	}
	
	function init() {
		$labels = array(
			'name'                => 'Instagram',
			'singular_name'       => 'Instagram',
			'menu_name'           => 'Instagram',
			'parent_item_colon'   => 'Parent Instagram:',
			'all_items'           => 'All Instagram Posts',
			'view_item'           => 'View Instagram',
			'add_new_item'        => 'Add New Instagram',
			'add_new'             => 'Add New',
			'edit_item'           => 'Edit Instagram',
			'update_item'         => 'Update Instagram',
			'search_items'        => 'Search Instagram',
			'not_found'           => 'Not found',
			'not_found_in_trash'  => 'Not found in Trash',
		);
		$args = array(
			'label'               => 'instagram',
			'description'         => 'Instagram posts',
			'labels'              => $labels,
			'supports'            => array( 'title', 'editor', 'thumbnail', 'comments', ),
			'taxonomies'          => array( 'category', 'post_tag' ),
			'hierarchical'        => false,
			'public'              => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_nav_menus'   => true,
			'show_in_admin_bar'   => true,
			'menu_position'       => 5,
			'menu_icon'           => 'dashicons-camera',
			'can_export'          => true,
			'has_archive'         => true,
			'exclude_from_search' => false,
			'publicly_queryable'  => true,
			'capability_type'     => 'page',
		);
		register_post_type( 'instagram', $args );
	}
	
	function admin_menu() {
		add_submenu_page( 'edit.php?post_type=instagram', 'Manual Sync', 'Manual Sync', 'manage_options', 'zah-instagram-sync', array($this, 'manual_sync_submenu') );
		add_submenu_page( 'edit.php?post_type=instagram', 'Subscriptions', 'Subscriptions', 'manage_options', 'zah-instagram-subscriptions', array($this, 'subscriptions_submenu') );
	}

	function manual_sync_submenu() {
		$action = '';
		if( isset($_GET['action']  ) ) {
			$action = $_GET['action'];
		}
		?>
		<?php
		//MANUAL SYNC
		if( isset( $action ) && $action == 'manual-sync' ) {

			$date_limit = 0;
			$from_date = '';
			if( isset( $_POST['date-limit'] ) ) {
				$date_limit = strtotime( $_POST['date-limit'] );
				$from_date = date( get_option( 'date_format' ), $date_limit );
			}
		?>
			<div class="wrap">
				<h1>Manaul Sync</h1>
				<?php if( $from_date ): ?>
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
				
				var dateLimit = <?php echo intval($date_limit); ?>;
				
				function update_the_page( data ) {
					if( imgs = data.imgs ) {
						$('#results').append( '<p>' + imgs.join('</p><p>') + '</p>' );
					}
					var total = data.total + parseInt( $('#total').text() );
					$('#total').html( total );
					
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
		
		//DEFAULT
		?>
		<div class="wrap">
			<h1>Manaully Sync Instagram</h1>
			<p>Make sure all of the Instagram photos tagged with <strong>#ZadieAlyssa</strong> are saved as posts on this site. Nothing will be overwritten, only missing Instagram photos will be added.</p>
			<form action="<?php echo admin_url( 'edit.php?post_type=instagram&page=zah-instagram-sync&action=manual-sync' );?>" method="post">
				
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
		$max_id = NULL;
		if( isset($_POST['next_max_id']) ) {
			$max_id = trim( $_POST['next_max_id'] );
		}
		$resp = $this->fetch_instagram_tag( 'zadiealyssa', $max_id );
		
		$next_max_id = FALSE;
		if( isset( $resp->pagination->next_url ) ) {
			$next_max_id =  $resp->pagination->next_max_tag_id;
		}
		$date_limit = 0;
		if( isset( $_POST['date-limit'] ) ) {
			$date_limit = intval( $_POST['date-limit'] );
		}
		
		$output = array(
			'next_max_id' => $next_max_id,
			'imgs' => array(),
			'skipped' => 0,
			'total' => 0
		);
		$images = $resp->data;
		foreach( $images as $img ) {
			//If the $img was posted later than our break limit then we need to stop
			if( intval( $img->caption->created_time ) < $date_limit ) {
				unset( $output[ 'next_max_id' ] );
				break;
			}
			
			$output['total']++;
			
			$found = $this->does_instagram_permalink_exist( $img->link );
			
			if( $found ) {
				$output['skipped']++;
			}
			
			if( !$found ) {
				$inserted = $this->insert_instagram_post( $img );
				if( $inserted ) {
					$wp_permalink = get_permalink( $inserted );
					$caption = $img->caption->text;
		
					$posted = date( 'Y-m-d H:i:s', intval( $img->caption->created_time ) ); //In GMT time
					
					$src = $img->images->low_resolution->url;
					$width = $height = $img->images->low_resolution->width;
					
					$output['imgs'][] = '<a href="' . $wp_permalink . '" target="_blank"><img src="' . $src . '" width="' . $width . '" height="' . $height . '"></a><br>' . $caption . '<br>' . get_date_from_gmt( $posted, 'F j, Y g:i a');
				}
			}
		}
		
		wp_send_json_success( (object) $output );
	}
	
	
	public function subscriptions_submenu() {
		if ($_SERVER['REQUEST_METHOD'] === 'POST') {
			$this->save_subscriptions();	
		}
		?>
		<h1>Instagram Subscriptions</h1>
		
		<form action="<?php echo admin_url( 'edit.php?post_type=instagram&page=zah-instagram-subscriptions' );?>" method="post">
		<?php if( $subscriptions = $this->get_instagram_subscriptions() ): ?>
			
			<h2>List of Subscriptions</h2>
			<table>
				<thead>
					<tr>
						<th></th>
						<th>Type</th>
						<th>Object ID</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach( $subscriptions as $sub ): ?>
					<tr>
						<td><input type="checkbox" name="delete-subscriptions[]" value="<?php echo $sub->id; ?>"></td>
						<td><?php echo $sub->object; ?></td>
						<td><?php echo $sub->object_id; ?></td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			
			<p><input type="submit" value="Delete Subscriptions" class="button button-primary"></p>

		<?php endif; ?>
		
		
		<h2>Add a New Subscription</h2>
		
		<label for="subscription-type">Type</label>
		<select id="subscription-type" name="subscription-type">
			<option value="tag">Tag</option>
			<option value="user">User</option>
			<option value="location">Location</option>
			<option value="geography">Geography</option>
		</select>
		
		<input type="text" name="object-id">
		
		<p><input type="submit" value="Add Subscription" class="button button-primary"></p>
		
		</form>
		<?php
	}
	
	public function save_subscriptions() {
		$instagram = $this->get_instagram_token();
		
		if( isset( $_POST['delete-subscriptions'] ) && !empty( $_POST['delete-subscriptions'] ) ) {
			$to_delete = array_map('intval', $_POST['delete-subscriptions']);
			
			$request_args = array(
				'method' => 'DELETE',
				'body' => array()
			);
			
			foreach( $to_delete as $id ) {
				$url_args = array(
					'client_id' => $instagram->service->key,
					'client_secret' => $instagram->service->secret,
					'id' => $id,
				);
				$url = add_query_arg( $url_args, 'https://api.instagram.com/v1/subscriptions' );
				$response = wp_remote_request( $url, $request_args );
			}
		}
		
		if( isset( $_POST['object-id'] ) && !empty( $_POST['object-id'] ) ) {
			
			$type = $_POST['subscription-type'];
			$object_id = $_POST['object-id'];
			
			$response = wp_remote_post( 'https://api.instagram.com/v1/subscriptions/', array(
				'headers' => array(),
				'body' => array(
					'client_id' => $instagram->service->key,
					'client_secret' => $instagram->service->secret,
					'object' => $type,
					'aspect' => 'media',
					'object_id' => $object_id,
					'verify_token' => $type . '-' . $object_id . '-token',
					'callback_url' => get_site_url() . '/instagram-subscription-callback/'
				)
			) );
			
			wp_redirect( admin_url( 'edit.php?post_type=instagram&page=zah-instagram-subscriptions' ) );
			die();
		}
	}
	
	public function get_instagram_subscriptions() {
		$instagram = $this->get_instagram_token();
		
		$args = array(
			'client_id' => $instagram->service->key,
			'client_secret' => $instagram->service->secret
		);
		
		$url = add_query_arg( $args, 'https://api.instagram.com/v1/subscriptions' );
		$response = wp_remote_get( $url );
		
		//Yay! The request was OK
		if( $response['response']['code'] == 200 ) {
			$output = json_decode( $response['body'] );
			return $output->data;
		}
		
		//Boo! There is some kind of problem
		$output = new WP_Error( 'get_instagram_subscriptions', 'There was an error getting the instagram subscriptions.', $response );
		return $output;
	}
	
	
	/* Filters */
	function pre_get_posts( $query ) {
		if ( 
			( $query->is_archive() || $query->is_home() ) &&
			$query->is_main_query() &&
			!is_admin()
		) {
        	$query->set( 'post_type', array('post', 'instagram') );
    	}
	}
	
	function the_content( $content ) {
		$post = get_post();
		if( $post->post_type == 'instagram' ) {
			$content = preg_replace('/\s(#(\w+))/im', ' <a href="http://iconosquare.com/tag/$2/">$1</a>', $content);
			//$content = preg_replace('/^(#(\w+))/im', '<a href="http://iconosquare.com/tag/$2/">$1</a>', $content);
			$content = preg_replace('/\s(@(\w+))/im', ' <a href="http://instagram.com/$2">$1</a>', $content);
			//$content = preg_replace('/^(@(\w+))/im', '<a href="http://instagram.com/$2">$1</a>', $content);
			//$via = ' via <a href="' . $permalink . '" target="_blank">' . $username . '</a>';
		}
		return $content;
	}
	
	function wp() {
		//Echo back the hub.challenge value returned from Instagram to make subscriptions work
		if( isset( $_GET['hub_challenge'] ) ) {
			echo $_GET['hub_challenge'];
			die();
		}
	}
	
	function set_html_content_type() {
		return 'text/html';
	}
	
	function instagram_realtime_update( $update ) {
		$resp = $this->fetch_instagram_tag( 'zadiealyssa' );
		$images = $resp->data;
		foreach( $images as $img ) {
			$found = $this->does_instagram_permalink_exist( $img->link );
			if( !$found ) {
				$inserted = $this->insert_instagram_post( $img );
			}
		}
	}
	
	function manage_instagram_posts_columns( $columns ) {
		$new_columns = array(
			'cb' => $columns['cb'],
			'title' => $columns['title'],
			'instagram_photo' => 'Photo',
			'instagram_permalink' => 'Instagram Permalink'
		);
		$remove_columns = array( 'cb', 'title', 'categories', 'tags' );
		foreach( $remove_columns as $col ) {
			unset( $columns[ $col ] );
		}
	
		return array_merge($new_columns, $columns);
	}
	
	function manage_posts_custom_column( $column, $post_id = 0 ) {
		
		switch( $column ) {
			case 'instagram_photo':
				$post = get_post( $post_id );
				$featured_id = get_post_thumbnail_id( $post->ID );
				if( !$featured_id ) {
					//We don't have one so let's try and get a featured image...
					$media = get_attached_media( 'image', $post->ID );
					$media_ids = array_keys( $media );
					$featured_id = $media_ids[0];
					
					add_post_meta( $post->ID, '_thumbnail_id', $featured_id );
				}
				
				$img = wp_get_attachment_image_src( $featured_id, 'thumbnail' );
				echo '<a href="' . get_permalink( $post->ID ) . '"><img src="' . $img[0] . '" width="' . $img[1] . '" height="' . $img[2] . '"></a>';
				
			break;
			
			case 'instagram_permalink':
				$post = get_post( $post_id );
				echo '<a href="' . $post->guid . '" target="_blank">@' . get_instagram_username() . '</a>';
			break;
		}
	}


	/* Quick Sync Dashboard Widget */
	function wp_dashboard_setup() {
		wp_add_dashboard_widget('instagram-quick-sync', 'Instagram Quick Sync', array($this, 'quick_sync_dashboard_widget') );
	}

	function quick_sync_dashboard_widget() {
		$two_days_ago = date( 'Y-m-d', strtotime('-2 days') );
		?>
		<form action="<?php echo admin_url( 'edit.php?post_type=instagram&page=zah-instagram-sync&action=manual-sync' );?>" method="post">
			<input type="hidden" name="date-limit" value="<?php echo $two_days_ago; ?>">
			<input type="submit" class="button button-primary" value="Sync Last 48 Hours">
		</form>
		<?php
	}



	/* Helper Functions */
	
	public function get_instagram_token() {
		//Initialize instance of Keyring
		$kr = Keyring::init();
		
		//Get the registered services
		$services = $kr->get_registered_services();
		
		//Get our tokens
		$tokens = $kr->get_token_store()->get_tokens(array(
			'service' => 'instagram'
		));
		
		return $tokens[0];
	}
	
	public function get_instagram_access_token() {
		$instagram = $this->get_instagram_token();
		return $instagram->token;
	}

	public function fetch_instagram_tag( $tag = 'zadiealyssa', $max_id = NULL, $min_id = NULL ) {
		$args = array(
			'access_token' => $this->get_instagram_access_token(),
		);
		
		if( $max_id ) {
			$args['max_tag_id'] = $max_id;
		}
		
		$request = add_query_arg( $args, 'https://api.instagram.com/v1/tags/' . $tag . '/media/recent' );
		$response = wp_remote_get( $request );
		
		return json_decode( $response['body'] );
	}
	
	public function insert_instagram_post( $img ) {
		if( !function_exists('download_url') ) {
			require_once ABSPATH . 'wp-admin/includes/admin.php';
		}
		
		//Assumes $img is an instagram media object returned from the API
		$src = $img->images->standard_resolution->url;
		
		$permalink = $img->link;
		$slug = str_replace('http://instagram.com/p/', '', $img->link );
		$slug = str_replace('/', '', $slug);
		
		$posted = date( 'Y-m-d H:i:s', intval( $img->caption->created_time ) ); //In GMT time
		$username = $img->caption->from->username;
		$full_name = $img->caption->from->full_name;
		$title = preg_replace('/\s#\w+/i', '', $img->caption->text);
		$caption = $img->caption->text;
		$filter = $img->filter;
		
		$post = array(
			'post_title' => $title,
			'post_content' => $caption,
			'post_status' => 'pending',
			'post_type' => 'instagram',
			'post_date' => get_date_from_gmt( $posted ),
			'post_date_gmt' => $posted,
			'guid' => $permalink
		);
		if( in_array( $username, $this->whitelisted_usernames) ) {
			$post['post_status'] = 'publish';
		}
		$inserted = wp_insert_post( $post );
		
		if( !$inserted ) {
			return false;
		}
		
		if( $img->type == 'video' ) {
			$video_file = $img->videos->standard_resolution->url;
			$tmp = download_url( $video_file );
			
			$file_array = array();
			$file_array['name'] = $slug . '.mp4';
			$file_array['tmp_name'] = $tmp;

			// If error storing temporarily, unlink
			if ( is_wp_error( $tmp ) ) {
				@unlink($file_array['tmp_name']);
				$file_array['tmp_name'] = '';
			}

			// do the validation and storage stuff
			$video_id = media_handle_sideload( $file_array, $inserted, $caption );

			// If error storing permanently, unlink
			if ( is_wp_error($video_id) ) {
				@unlink($file_array['tmp_name']);
			}
		}
		
		$attachment_data = array(
			'post_content' => $caption,
			'post_title' => 'Instagram: ' . $slug,
			'post_name' => $slug,
			'file_name' => $slug . '.jpg'
		);
		$attachment_id = media_sideload_image_return_id($src, $inserted, $caption, $attachment_data);
		$img_attr = array(
			'class' => 'aligncenter from-instagram',
			'alt' => ''
		);
		$updated_post_content = wp_get_attachment_image( $attachment_id, 'full', false, $img_attr ) . "\n\n" . $caption;
		if( $img->type == 'video' ) {
			$video_src = wp_get_attachment_url( $video_id );
			$poster = wp_get_attachment_image_src( $attachment_id, 'full' );
			$updated_post_content = '[video src="' . $video_src . '" poster="' . $poster[0] . '"]' . "\n\n" . $caption;
		}
		
		$updated_post = array(
			'ID' => $inserted,
			'post_content' => $updated_post_content,
			'guid' => $permalink
		);
		wp_update_post( $updated_post );
		
		update_post_meta( $inserted, 'instagram_username', $username );
		
		//Set the featured image
		add_post_meta( $inserted, '_thumbnail_id', $attachment_id );
		
		if( $post['post_status'] != 'publish' ) {
			//Send an email so we can approve the new photo ASAP!
			$this->send_pending_post_notification_email( $inserted, $attachment_id );
		}
		
		return $inserted;
	}
	
	public function get_instagram_username( $post_id = FALSE ) {
		if( $post_id ) {
			$post_id = intval( $post_id );
		}
		
		if( !$post_id ) {
			$post_id = get_the_ID();
		}
		
		$output = get_post_meta( $post_id, 'instagram_username', true );
		if( !$output ) {
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
		add_filter( 'wp_mail_content_type', array($this, 'set_html_content_type') );

		$post = get_post( $post_id );
		$img = wp_get_attachment_image_src( $attachment_id, 'medium');
		
		$subject = '#ZadieAlyssa: Pending post from ' . $this->get_instagram_username( $post_id );
		$html = '';
		$html .= '<p><a href="' . $post->guid . '" target="_blank"><img src="' . $img[0] . '" width="' . $img[1] . '" height="' . $img[2] . '"></a><p>';
		$html .= '<p>Edit this post at <a href="' . get_edit_post_link( $post_id ) . '" target="_blank">' . get_edit_post_link( $post_id ) . '</a></p>';
		
		//INSTAGRAM_PENDING_EMAIL_ADDRESS should be defined as a constant in wp-config.php so we don't post the email address publicly. 
		wp_mail( INSTAGRAM_PENDING_EMAIL_ADDRESS, $subject, $html );
		
		// Reset content-type to avoid conflicts -- http://core.trac.wordpress.org/ticket/23578
		remove_filter( 'wp_mail_content_type', array($this, 'set_html_content_type') );
	}
}

/* Global helper functions */
function get_instagram_username( $post_id = FALSE ) {
	global $zah_instagram;
	return $zah_instagram->get_instagram_username( $post_id );
}

global $zah_instagram;
$zah_instagram = new ZAH_Instagram();