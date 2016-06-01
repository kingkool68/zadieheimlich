<?php
class On_This_Day {
	public function __construct() {
		add_filter( 'query_vars', array( $this, 'query_vars' ) );
		add_filter( 'rewrite_rules_array', array( $this, 'filter_rewrite_rules_array' ) );
		add_action( 'pre_get_posts', array( $this, 'pre_get_posts' ), 9 );
	}

	public function query_vars( $vars ) {
		$vars[] = 'zah-on-this-month';
		$vars[] = 'zah-on-this-day';

		return $vars;
	}

	public function filter_rewrite_rules_array( $rules ) {
		global $wp_rewrite;

		$pagename = 'on-this-day';
		$root = $wp_rewrite->root . $pagename;
		$new_rules = array(
			$root . '/([0-9]{2})/([0-9]{2})/?' => 'index.php?pagename=' . $pagename . '&zah-on-this-month=$matches[1]&zah-on-this-day=$matches[2]',
			// $root . '/([0-9]{2})/?' => 'index.php?pagename=' . $pagename . '&zah-on-this-month=$matches[1]',
		);

		return $new_rules + $rules;
	}
	public function pre_get_posts( $query ) {
		if ( ! $query->is_main_query() || is_admin() ) {
			return;
		}
		$pagename = get_query_var( 'pagename' );
		if ( ! $pagename ) {
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

		if ( ! empty( $date_query ) ) {
			$query->set( 'date_query', $date_query );
			$query->set( 'pagename', '' );
			$query->is_page = false;
			$query->is_date = true;
			$query->is_archive = true;
			$query->date_query=  $date_query;
		}
		// echo '<xmp>';
		// var_dump( $query );
		// echo '</xmp>';
	}
}
new On_This_Day();
