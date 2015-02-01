<!DOCTYPE html>
<!--[if lt IE 7 ]> <html class="ie ie6" <?php language_attributes(); ?>> <![endif]-->
<!--[if IE 7 ]>    <html class="ie ie7" <?php language_attributes(); ?>> <![endif]-->
<!--[if IE 8 ]>    <html class="ie ie8" <?php language_attributes(); ?>> <![endif]-->
<!--[if IE 9 ]>    <html class="ie ie9" <?php language_attributes(); ?>> <![endif]-->
<!--[if gt IE 9]><!--><html <?php language_attributes(); ?>><!--<![endif]-->
<head profile="http://gmpg.org/xfn/11" prefix="og: http://ogp.me/ns#">
<meta http-equiv="Content-Type" content="<?php bloginfo('html_type'); ?>; charset=<?php bloginfo('charset'); ?>">
<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
<meta name="viewport" content="width=device-width">
<meta name="date" content="<?= date('Ymd',strtotime($post->post_date)); ?>">

<link rel="alternate" type="application/rss+xml" title="<?php bloginfo('name'); ?> RSS Feed" href="<?php bloginfo('rss2_url'); ?>">
<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
	<a id="top" href="#content">Skip to Content</a>
	<header>
		<h1 class="site-title"><a href="<?php echo get_site_url(); ?>">Zadie Heimlich</a></h1>
		<p class="zadies-current-age"><?php echo get_zadies_current_age(); ?> old.</p>
	</header>
	
	<div class="holder">