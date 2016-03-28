<?php
class On_This_Day {
	public function __construct() {
		add_filter( 'query_vars', array( $this, 'query_vars' ) );
		add_filter( 'rewrite_rules_array', array( $this, 'filter_rewrite_rules_array' ) );
	}

	public function query_vars( $vars ) {
		$vars[] = 'zah-on-this-month';
		$vars[] = 'zah-on-this-day';
	}

	public function filter_rewrite_rules_array( $rules ) {
		global $wp_rewrite;

		$pagename = 'on-this-day';
		$root = $wp_rewrite->root . $pagename;
		$new_rules = array(
			$root . '/(\d+)/(\d+)/' => 'index.php?pagename=' . $pagename . '&zah-on-this-month=$matches[1]&zah-on-this-day=$matches[2]',
			$root . '/(\d+)/' => 'index.php?pagename=' . $pagename . '&zah-on-this-month=$matches[1]',
		);

		return $new_rules + $rules;
	}
}
$on_this_day = new On_This_Day();
