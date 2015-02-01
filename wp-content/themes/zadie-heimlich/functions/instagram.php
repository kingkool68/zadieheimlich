<?php

class ZAH_Instagram {
	
	var $whitelisted_usernames = array( 'naudebynature', 'kingkool68' );
	
	public function __construct() {
		add_action( 'init', array( $this, 'init' ) );
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'wp_ajax_zah_instagram_manual_sync', array($this, 'manual_sync_ajax_callback' ) );
		add_action( 'pre_get_posts', array($this, 'pre_get_posts') );
		
		add_filter( 'the_content', array($this, 'the_content') );
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
		add_options_page( 'Zadie\'s Instagram Settings', 'Instagram', 'manage_options', 'zah-instagram', array($this, 'options_page') );
	}

	function options_page() {
		$action = $_GET['action'];
		
		//MANUAL SYNC
		if( isset( $action ) && $action == 'manual-sync' ) {
		?>
			<h1>Manaul Sync</h1>
			<div id="results">
			
			</div>
			<ul id="stats">
				<li>Total: <span id="total">0</span></li>
				<li>Skipped: <span id="skipped">0</span></li>
			</ul>
			
			<script>
			jQuery(document).ready(function($) {
				
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
								'next_max_id': next_max_id
							});
						}
					});
				}
				
				//Kick things off...
				send_the_ajax_request({
					'action': 'zah_instagram_manual_sync'
				});
		
				
			});
			</script>
		<?php
			return;
		}
		
		//DEFAULT
		?>
		<h1>Zadie's Instagram Settings</h1>
		<h2>Manaully Sync</h2>
		<p>Make sure all of the Instagram photos tagged with <strong>#ZadieAlyssa</strong> are saved as posts on this site. Nothing will be overwritten, only missing Instagram photos will be added.</p>
		<p><a class="button-primary" href="<?php echo admin_url( 'options-general.php?page=zah-instagram&action=manual-sync' );?>">Sync</a></p>
		
	<?php
	}
	
	public function manual_sync_ajax_callback() {
		global $wpdb;
		
		$max_id = NULL;
		if( isset($_POST['next_max_id']) ) {
			$max_id = trim( $_POST['next_max_id'] );
		}
		$resp = $this->fetch_instagram_tag( 'zadiealyssa', $max_id );
		
		$next_max_id = FALSE;
		if( isset( $resp->pagination->next_url ) ) {
			$next_max_id =  $resp->pagination->next_max_tag_id;
		}
		$output = array(
			'next_max_id' => $next_max_id,
			'imgs' => array(),
			'skipped' => 0,
			'total' => 0
		);
		$images = $resp->data;
		//$images = array( $images[0] );
		foreach( $images as $img ) {
			$output['total']++;
			$permalink = $img->link;
			$query = "SELECT `ID` FROM `" . $wpdb->posts . "` WHERE `guid` = '" . $permalink . "' LIMIT 0,1;";
			$found = $wpdb->get_var( $query );
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


	/* Helper Functions */

	public function get_instagram_access_token() {
		//Initialize instance of Keyring
		$kr = Keyring::init();
		
		//Get the registered services
		$services = $kr->get_registered_services();
		
		//Get our tokens
		$tokens = $kr->get_token_store()->get_tokens(array(
			'service' => 'instagram'
		));
		$instagram = $tokens[0];
		
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
		$updated_post = array(
			'ID' => $inserted,
			'post_content' => $updated_post_content,
			'guid' => $permalink
		);
		wp_update_post( $updated_post );
		
		update_post_meta( $inserted, 'instagram_username', $username );
		
		if( $post['post_status'] != 'publish' ) {
			//Send an email so we can approve the new photo ASAP!
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
}

/* Global helper functions */
function get_instagram_username( $post_id = FALSE ) {
	global $zah_instagram;
	return $zah_instagram->get_instagram_username( $post_id );
}

global $zah_instagram;
$zah_instagram = new ZAH_Instagram();