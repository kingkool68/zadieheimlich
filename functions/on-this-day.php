<?php
class On_This_Day {

	private $pagename = 'on-this-day';

	public function __construct() {
		add_filter( 'query_vars', array( $this, 'query_vars' ) );
		add_filter( 'rewrite_rules_array', array( $this, 'filter_rewrite_rules_array' ) );
		add_action( 'template_redirect', array( $this, 'template_redirect' ) );
		add_filter( 'template_include', array( $this, 'template_include' ) );
		add_action( 'pre_get_posts', array( $this, 'pre_get_posts' ), 9 );
	}

	public function query_vars( $vars ) {
		$vars[] = 'zah-on-this-month';
		$vars[] = 'zah-on-this-day';

		return $vars;
	}

	public function filter_rewrite_rules_array( $rules ) {
		global $wp_rewrite;

		$root = $wp_rewrite->root . $this->pagename;
		$new_rules = array(
			$root . '/([0-9]{2})/([0-9]{2})/?' => 'index.php?pagename=' . $this->pagename . '&zah-on-this-month=$matches[1]&zah-on-this-day=$matches[2]',
		);

		return $new_rules + $rules;
	}

	public function template_redirect() {
		global $wp;
		$pagename = get_query_var( 'pagename' );
		$month = get_query_var( 'zah-on-this-month' );
		$day = get_query_var( 'zah-on-this-day' );

		// Automatically redirect ugly query strings to pretty permalinks
		$query_string = explode( '?', $_SERVER['REQUEST_URI'] );
		if ( isset( $query_string[1] ) ) {
			$query_string = wp_parse_args( $query_string[1] );
			if ( isset( $query_string['zah-on-this-month'] ) && isset( $query_string['zah-on-this-day'] ) ) {
				$redirect_to = get_site_url() . '/' . $this->pagename . '/' . $month . '/' . $day . '/';
				wp_redirect( $redirect_to );
				die();
			}
		}

		// No pagename so bail.
		if ( ! $pagename || $pagename != $this->pagename ) {
			return;
		}

		// $month and $day aren't set so we're assuming we landed on /on-this-day/ and we need to redirect to today
		if ( ! $month && ! $day ) {
			$redirect_to = get_site_url() . '/' . $this->pagename . '/' . current_time('m') . '/' . current_time('d') . '/';
			wp_redirect( $redirect_to );
			die();
		}
	}

	public function template_include( $template ) {
		global $wp_query;
		$month = get_query_var( 'zah-on-this-month' );
		$day = get_query_var( 'zah-on-this-day' );

		if ( $month && $day && is_404() ) {
			$template_paths = array(
				'404-' . $this->pagename . '.php',
				'404.php'
			);
			if ( $new_template = locate_template( $template_paths ) ) {
				return $new_template;
			}
		}

		return $template;
	}

	public function pre_get_posts( $query ) {
		if ( ! $query->is_main_query() || is_admin() ) {
			return;
		}
		$pagename = get_query_var( 'pagename' );
		if ( ! $pagename ) {
			return;
		}

		// TODO: Sanitize $month and $date and make sure they're valid i.e. not February 33rd (BAD DATE!)
		$date_query = array();
		if ( $month = get_query_var( 'zah-on-this-month' ) ) {
			$month = ltrim( $month, '0' );
			$month = intval( $month );
			$date_query['month'] = $month;
		}
		if ( $day = get_query_var( 'zah-on-this-day' ) ) {
			$day = ltrim( $day, '0' );
			$day = intval( $day );
			$date_query['day'] = $day;
		}

		if ( ! empty( $date_query ) ) {
			$query->set( 'date_query', $date_query );
			$query->set( 'pagename', '' );
			$query->is_page = false;
			$query->is_date = true;
			$query->is_archive = true;
		}
	}
}
new On_This_Day();
