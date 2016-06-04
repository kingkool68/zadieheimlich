<?php
class On_This_Day {

	private $pagename = 'on-this-day';

	public function __construct() {
		add_filter( 'query_vars', array( $this, 'query_vars' ) );
		add_filter( 'rewrite_rules_array', array( $this, 'filter_rewrite_rules_array' ) );
		add_action( 'template_redirect', array( $this, 'template_redirect' ) );
		add_filter( 'template_include', array( $this, 'template_include' ) );
		add_action( 'pre_get_posts', array( $this, 'pre_get_posts' ), 9 );
		add_action( 'zah_before_content', array( $this, 'zah_before_content' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'wp_enqueue_scripts' ) );
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

		if ( $day && $month ) {
			$days_in_month = 31;
			switch(  $month ) {
				// 30 days
				case '04':
				case '06':
				case '09':
				case '11':
					$days_in_month = 30;
				break;

				// 29 days because of leap years
				case '02':
					$days_in_month = 29;
				break;
			}
			if ( intval( $day ) > $days_in_month ) {
				$day = $days_in_month;
				$redirect_to = get_site_url() . '/' . $this->pagename . '/' . $month . '/' . $day . '/';
				wp_redirect( $redirect_to );
				die();
			}
		}

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
			$this->redirect_to_current_date();
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
			$pagename = get_query_var( 'name' );
		}
		if ( ! $pagename || $pagename != $this->pagename ) {
			return;
		}

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

		if ( empty( $date_query ) ) {
			$this->redirect_to_current_date();
		}

		$query->set( 'date_query', $date_query );
		$query->set( 'name', '' );
		$query->set( 'pagename', '' );
		$query->is_page = false;
		$query->is_404 = false;
		$query->is_date = true;
		$query->is_archive = true;
	}

	public function is_on_this_day() {
		$month = get_query_var( 'zah-on-this-month' );
		$day = get_query_var( 'zah-on-this-day' );
		return ( $month && $day );
	}

	public function redirect_to_current_date() {
		$redirect_to = get_site_url() . '/' . $this->pagename . '/' . current_time('m') . '/' . current_time('d') . '/';
		wp_redirect( $redirect_to );
		die();
	}

	public function zah_before_content() {
		if ( $this->is_on_this_day() ) {
			get_template_part( 'content', 'on-this-day-switch-date-form' );
		}
	}

	public function wp_enqueue_scripts() {
		wp_register_script( 'on-this-day', get_template_directory_uri() . '/js/on-this-day.js', array('jquery'), NULL, true );
	}
}
new On_This_Day();
