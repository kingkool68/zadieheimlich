<?php
//My own human time diff function from http://www.php.net/manual/en/ref.datetime.php#90989
function rh_human_time_diff( $levels = 2, $from, $to = false ) {
	if( !$to ) {
		$to = current_time('U');
	}
	$blocks = array(
		array('name'=>'year','amount'	=>	60*60*24*365	),
		array('name'=>'month','amount'	=>	60*60*24*31	),
		array('name'=>'week','amount'	=>	60*60*24*7	),
		array('name'=>'day','amount'	=>	60*60*24	),
		array('name'=>'hour','amount'	=>	60*60		),
		array('name'=>'minute','amount'	=>	60		),
		array('name'=>'second','amount'	=>	1		)
	);
   
	$diff = abs($from-$to);
   
	$current_level = 1;
	$result = array();
	foreach($blocks as $block)
		{
		if ($current_level > $levels) {break;}
		if ($diff/$block['amount'] >= 1)
			{
			$amount = floor($diff/$block['amount']);
			if ($amount>1) {$plural='s';} else {$plural='';}
			$result[] = $amount.' '.$block['name'].$plural;
			$diff -= $amount*$block['amount'];
			$current_level++;
			}
		}
	return implode(' ',$result);
}

function zah_fancy_time( $time ) {
	return preg_replace('/(\d+):(\d+) (am|pm)/i', '<span class="time">$1<span class="colon">:</span>$2 <span class="am-pm">$3</span></span>', $time);
}
add_filter('the_time', 'zah_fancy_time');

function get_zadies_birthday() {
	return strtotime('2014-12-28 7:04PM');
}

function zadies_birthday_diff( $levels = 2 ) {
	return rh_human_time_diff( $levels,  get_zadies_birthday(), get_the_time('U') );
}

function get_zadies_current_age( $levels = 2 ) {
	return rh_human_time_diff( $levels,  get_zadies_birthday() );
}

function how_old_was_zadie() {
	if( get_the_time('U') < get_zadies_birthday() ) {
		return zadies_birthday_diff() . ' before Zadie was born.';
	}
	
	return 'Zadie was ' . zadies_birthday_diff() . ' old.';
}

function get_zah_time_format() {
	return get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
}