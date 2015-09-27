<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$update = file_get_contents('php://input');
    $updates = json_decode($update);
	
	/*
	Sample of $photos...
	
	[
	  {
		"changed_aspect":"media",
		"object":"tag",
		"object_id":"nofilter",
		"time":1423116308,
		"subscription_id":16698275,
		"data":{
		  
		}
	  }
	]
	*/
	
	foreach( $updates as $update ) {
		$type = $update->object;
		$object_id = strtolower( $update->object_id );
		do_action( 'instagram_subscription_' . $type . '_' . $object_id, $update );
	}
}