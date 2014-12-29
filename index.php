<?php
//My own human time diff function from http://www.php.net/manual/en/ref.datetime.php#90989
function rh_human_time_diff( $levels = 2, $from, $to = false ) {
	if( !$to ) {
		$to = time();
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

function img( $filename, $caption = '', $alt = '' ) {
?>
	<figure aria-describedby="<?php echo $filename; ?>">
		<img src="img/<?php echo $filename; ?>.jpg" alt="<?php echo $alt; ?>">
		<figcaption id="<?php echo $filename; ?>"><?php echo $caption;?></figcaption>
	</figure>
<?php
}

date_default_timezone_set('America/New_York');
$birth_date = strtotime('2014-12-28 7:04PM');
?>
<!doctype html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
<meta name="viewport" content="width=device-width">
<title>The Birth of Zadie Alyssa Heimlich</title>
<link href='http://fonts.googleapis.com/css?family=Crete+Round:400,400italic' rel='stylesheet' type='text/css'>
<link href="reset.css" rel="stylesheet" type="text/css">
<style>
html {
font-family: 'Crete Round', Arial, Helvetica, sans-serif;
background-color:#7b0e7f;
background-image:url(img/symphony-purple.png);
background-repeat:repeat;
background-attachment:fixed;
}
body {
margin:0 auto;
max-width:105em;
padding:0 2em;
color:#fff;
}
h1 {
font-size:8em;
font-weight:700;
margin:0.8em 0 0;
text-transform:uppercase;
}
h2 {
font-size:3em;
font-style:italic;
margin-bottom:0.4em;
}
#birth-announcement {
color:#fff;
font-size:40%;
white-space: nowrap;
font-family:Helvetica, Arial, sans-serif;
font-style:normal;
}
p {
font-size:1.8em;
line-height:1.5;
}
#her-age {
margin-bottom:3.5em;
}
img {
max-width:100%;
height:auto;
}
figure {
margin:0 0 8em;
}
figure img {
display:block;
margin:0 auto;
border:0.3em solid #fff;
border-radius:1em;
}
figcaption {
font-size:1.6em;
text-align:center;
display:block;
padding:0.8em 0;
font-family:Helvetica, Arial, sans-serif
}

@media (max-width: 37.5em) { /* 600px / 16 */
	h1 {
	margin-top:0.2em;
	margin-bottom:0.4em;
	}
	figure {
	margin-bottom:4em;
	}
}
</style>
</head>

<body>
	<h1>Zadie Alyssa Heimlich</h1>
	<h2>Born <time datetime="<?php echo date('c', $birth_date); ?>">Sunday December 28, 2014 at 7:04 pm</time> <a href="img/birth-announcement.jpg" id="birth-announcement">Birth Announcemnt</a></h2>
	<p id="her-age">She is <?php echo rh_human_time_diff(2, $birth_date);?> old.</p>
	
	<?php
		$imgs = array(
			array( 'zadie-meets-mommy', 'Zadie meets her mommy', 'Baby Zadie pressed against her mothers cheek' ),
			array( 'zadie-and-daddy', 'Zadie and her Daddy', 'Russell holding Zadie while in scrubs' ),
			array( 'the-new-heimlich-family', 'The New Heimlich Family', 'Kristina, Russell, and Zadie in the operating room' ),
			array( 'kung-fu-grip', 'She\'s got a kung fu grip', 'Baby hand grabbing an adult finger' ),
			array( 'zadies-first-bath', 'Zadie\'s first bath', 'A freshly bathed newborn' ),
			array( 'a-full-head-of-hair', 'She came with a full head of hair', 'A newborns head covered in brown hair' )
		);
		
		foreach( $imgs as $img ) {
			img( $img[0], $img[1], $img[2] );
		}
	?>
	
</body>
</html>