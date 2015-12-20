<?php
/* These functions enable us to make an RSVP form and store the results in a database table. */
/*
SQL to make the table to store the RSVPS:

CREATE TABLE `wp_3_rsvps` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` text,
  `attending` enum('YES','NO') DEFAULT NULL,
  `adults` int(2) NOT NULL DEFAULT '1',
  `children` int(2) NOT NULL DEFAULT '0',
  `message` text,
  `timestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

 */

function zah_redirect_rsvp_vanity_url() {
	global $wp_query;
	if( !is_404() ) {
		return;
	}
	if( $wp_query->query['name'] == 'rsvp' ) {
		$page = get_page_by_path( 'rsvp-zadies-first-birthday-party' );
		wp_safe_redirect( get_permalink( $page->ID ) );
		die();
	}
}
add_action( 'wp', 'zah_redirect_rsvp_vanity_url' );

function zah_add_rsvp_form_to_the_content( $content ) {
	if( !is_page( 'rsvp-zadies-first-birthday-party' ) ) {
		return $content;
	}

	if( isset( $_GET['attending'] ) && $_GET['attending'] == 'yes' ) {
		return zah_show_rsvp_response_page( 'yes' );
	}

	if( isset( $_GET['attending'] ) && $_GET['attending'] == 'no' ) {
		return zah_show_rsvp_response_page( 'no' );
	}

	$invitation_image = '<p><img src="' . get_template_directory_uri() . '/img/zadies-first-birthday-invitation.jpg" alt=""></p>';

$the_form = <<<'EOD'
	<form class="rsvp" method="POST" action="?submitted">
		<fieldset class="name">
			<label for="your-name">Your Name</label>
			<input type="text" id="your-name" name="your-name" class="full" required>
		</fieldset>

		<fieldset class="attendance">
			<legend>Will you be attending?</legend>
			<label><input type="radio" name="attending" value="Yes" required>Yes</label>
			<label><input type="radio" name="attending" value="No" required>No</label>
		</fieldset>

		<fieldset class="how-many">
			<legend>How many attendees?</legend>
			<label><input type="number" id="number-of-adults" name="number-of-adults" value="1" min="0" max="9" size="1"> adults</label>
			<label><input type="number" id="number-of-children" name="number-of-children" value="0" min="0" max="9" size="1"> children</label>
		</fieldset>

		<fieldset>
			<label for="your-message">Your Message (optional)</label>
			<textarea name="your-message" id="your-message" rows="5"></textarea>
		</fieldset>

		<fieldset class="hide-if-js">
			<label for="current-year">Current Year (YYYY)</label>
			<input type="text" id="current-year" name="current-year" value="">
			<script>document.getElementById('current-year').value = new Date().getFullYear();</script>
		</fieldset>

		<fieldset class="hide-if-js">
			<label>Leave this field blank</label>
			<input type="email" name="other-email" value="">
		</fieldset>

		<button type="submit" class="rounded-button">RSVP</button>
	</form>
EOD;
	return $the_form . $content;
}
add_filter( 'the_content', 'zah_add_rsvp_form_to_the_content' );

function zah_rsvp_process_form() {
	global $wpdb;
	if( !isset( $_GET['submitted'] ) || empty( $_POST ) ) {
		return;
	}

	if( !isset( $_POST['other-email'] ) || !empty($_POST['other-email']) ) {
		//This field should be present but blank. If it's filled in then we have an automated spam bot.
		wp_die( 'We suspect you are not a real person.' );
	}

	if( !isset( $_POST['current-year'] ) || $_POST['current-year'] != date('Y') ) {
		wp_die( 'You didn\'t fill in the correct year. We suspect you are not a real person.' );
	}

	$name = '';
	if( isset( $_POST['your-name'] ) ) {
		$name = sanitize_text_field( $_POST['your-name'] );
	}

	$attending = '';
	if( isset( $_POST['attending'] ) && in_array( $_POST['attending'], array('Yes', 'No') ) ) {
		$attending = $_POST['attending'];
	}

	$number_of_adults = false;
	if( isset( $_POST['number-of-adults'] ) ) {
		$number_of_adults = intval( $_POST['number-of-adults'] );
	}

	$number_of_children = false;
	if( isset( $_POST['number-of-children'] ) ) {
		$number_of_children = intval( $_POST['number-of-children'] );
	}

	$message = '';
	if( isset( $_POST['your-message'] ) ) {
		// In order to preserve line breaks we need to do the following... via http://stackoverflow.com/questions/20444042/wordpress-how-to-sanitize-multi-line-text-from-a-textarea-without-losing-line
		$message = implode( "\n", array_map( 'sanitize_text_field', explode( "\n", $_POST['your-message'] ) ) );
	}

	$answers = array(
		'Name' => $name,
		'Attending' => $attending,
		'Number of Adults' => $number_of_adults,
		'Number of Children' => $number_of_children,
		'Message' => $message,
	);

	$email_message = '';
	foreach( $answers as $label => $answer ) {
		$email_message .= $label . ': ' . $answer . "\n";
	}

	$status = 'can\'t make it!';
	if( $attending == 'Yes' ) {
		$status = 'is coming';
		$total_guests = $number_of_adults + $number_of_children;
		if( $total_guests > 1 ) {
			$total_guests = $total_guests - 1;
			$status .= ' with ' . $total_guests . ' guest!';
			if( $total_guests > 1 ) {
				$status = str_replace( '!', 's!', $status);
			}
		}
	}
	$subject = '[RSVP] ' . $name . ' ' . $status;
	$sent = wp_mail( 'us@12hugo.com', $subject, $email_message );

	$db_data = array(
		'name' => $name,
		'attending' => $attending,
		'adults' => $number_of_adults,
		'children' => $number_of_children,
		'message' => $message,
	);
	$formats = array(
		'%s', // name
		'%s', // attending
		'%d', // adults
		'%d', // children
		'%s', // message
	);
	$inserted = $wpdb->insert( $wpdb->prefix . 'rsvps', $db_data, $formats );
	$thing = $wpdb->last_error;

	$page = get_page_by_path( 'rsvp-zadies-first-birthday-party' );
	$url = add_query_arg( 'attending', strtolower( $attending ), get_permalink( $page->ID ) );

	wp_safe_redirect( $url );
	die();
}
add_action( 'init', 'zah_rsvp_process_form' );

function zah_show_rsvp_response_page( $status = 'no' ) {
	if( $status == 'yes' ) {
$content = <<<'EOD'
	<h2 class="birthday-rsvp-title">Great! We'll see you there.</h2>
	<link href="https://addtocalendar.com/atc/1.5/atc-style-button-icon.css" rel="stylesheet" type="text/css">
	<p class="addtocalendar">
        <var class="atc_event">
            <var class="atc_date_start">2016-01-09 13:00:00</var>
            <var class="atc_date_end">2016-01-09 16:00:00</var>
            <var class="atc_timezone">America/New_York</var>
            <var class="atc_title">Zadie's First Birthday Party</var>
            <var class="atc_description">http://zadieheimlich.com/rsvp/</var>
            <var class="atc_location">1301 Nordic Hill Cir, Silver Spring, MD 20906</var>
            <var class="atc_organizer">Kristina Heimlich</var>
            <var class="atc_organizer_email">us@12hugo.com</var>
        </var>
    </p>
	<script type="text/javascript">
		(function () {
            if (window.addtocalendar)if(typeof window.addtocalendar.start == "function")return;
            if (window.ifaddtocalendar == undefined) { window.ifaddtocalendar = 1;
                var d = document, s = d.createElement('script'), g = 'getElementsByTagName';
                s.type = 'text/javascript';s.charset = 'UTF-8';s.async = true;
                s.src = ('https:' == window.location.protocol ? 'https' : 'http')+'://addtocalendar.com/atc/1.5/atc.min.js';
                var h = d[g]('body')[0];h.appendChild(s); }})();
    </script>

EOD;
	$happy_img = '<p><img src="' . get_template_directory_uri() . '/img/zadie-is-ready-to-party.jpg" class="alignnone" alt="Zadie wearing a party hat with baloons"></p><p>Zadie is ready to party!</p>';
	$content .= $happy_img;
	}
	if( $status == 'no' ) {
$content = <<<'EOD'
	<h2 class="birthday-rsvp-title">Aw shucks! Maybe next time.</h2>
EOD;
	}

	return $content;
}
