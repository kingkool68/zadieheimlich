<!DOCTYPE html>
<!--[if lt IE 7 ]> <html class="ie ie6" <?php language_attributes(); ?>> <![endif]-->
<!--[if IE 7 ]>    <html class="ie ie7" <?php language_attributes(); ?>> <![endif]-->
<!--[if IE 8 ]>    <html class="ie ie8" <?php language_attributes(); ?>> <![endif]-->
<!--[if IE 9 ]>    <html class="ie ie9" <?php language_attributes(); ?>> <![endif]-->
<!--[if gt IE 9]><!--><html <?php language_attributes(); ?>><!--<![endif]-->
<head profile="http://gmpg.org/xfn/11" prefix="og: http://ogp.me/ns#">
<meta http-equiv="Content-Type" content="<?php bloginfo('html_type'); ?>; charset=<?php bloginfo('charset'); ?>">
<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="referrer" content="unsafe-url">
<?php if( $post ) { ?>
<meta name="date" content="<?= date('Ymd',strtotime($post->post_date)); ?>">
<?php } ?>

<link rel="alternate" type="application/rss+xml" title="<?php bloginfo('name'); ?> RSS Feed" href="<?php bloginfo('rss2_url'); ?>">
<link rel="manifest" type="application/manifest+json" href="<?php echo esc_url( get_template_directory_uri() ); ?>/zadieheimlich.com.webmanifest.json">
<link rel="apple-touch-icon" sizes="57x57" href="<?php echo esc_url( get_template_directory_uri() ); ?>/favicons/apple-touch-icon-57x57.png">
<link rel="apple-touch-icon" sizes="60x60" href="<?php echo esc_url( get_template_directory_uri() ); ?>/favicons/apple-touch-icon-60x60.png">
<link rel="apple-touch-icon" sizes="72x72" href="<?php echo esc_url( get_template_directory_uri() ); ?>/favicons/apple-touch-icon-72x72.png">
<link rel="apple-touch-icon" sizes="76x76" href="<?php echo esc_url( get_template_directory_uri() ); ?>/favicons/apple-touch-icon-76x76.png">
<link rel="apple-touch-icon" sizes="114x114" href="<?php echo esc_url( get_template_directory_uri() ); ?>/favicons/apple-touch-icon-114x114.png">
<link rel="apple-touch-icon" sizes="120x120" href="<?php echo esc_url( get_template_directory_uri() ); ?>/favicons/apple-touch-icon-120x120.png">
<link rel="apple-touch-icon" sizes="144x144" href="<?php echo esc_url( get_template_directory_uri() ); ?>/favicons/apple-touch-icon-144x144.png">
<link rel="apple-touch-icon" sizes="152x152" href="<?php echo esc_url( get_template_directory_uri() ); ?>/favicons/apple-touch-icon-152x152.png">
<link rel="apple-touch-icon" sizes="180x180" href="<?php echo esc_url( get_template_directory_uri() ); ?>/favicons/apple-touch-icon-180x180.png">
<link rel="icon" type="image/png" href="<?php echo esc_url( get_template_directory_uri() ); ?>/favicons/favicon-32x32.png" sizes="32x32">
<link rel="icon" type="image/png" href="<?php echo esc_url( get_template_directory_uri() ); ?>/favicons/android-chrome-192x192.png" sizes="192x192">
<link rel="icon" type="image/png" href="<?php echo esc_url( get_template_directory_uri() ); ?>/favicons/favicon-96x96.png" sizes="96x96">
<link rel="icon" type="image/png" href="<?php echo esc_url( get_template_directory_uri() ); ?>/favicons/favicon-16x16.png" sizes="16x16">
<link rel="manifest" href="<?php echo esc_url( get_template_directory_uri() ); ?>/favicons/android-chrome-manifest.json">
<link rel="shortcut icon" href="<?php echo esc_url( get_template_directory_uri() ); ?>/favicons/favicon.ico">
<meta name="msapplication-TileColor" content="#7b0e7f">
<meta name="msapplication-TileImage" content="/themes/zadie-heimlich/favicons/mstile-144x144.png">
<meta name="msapplication-config" content="/themes/zadie-heimlich/favicons/browserconfig.xml">
<meta name="theme-color" content="#7b0e7f">
<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
	<?php include get_template_directory() . '/svg/zadie-heimlich-icons.svg'; ?>
	<a id="top" href="#content">Skip to Content</a>
	<header class="clearfix">
		<div class="holder">
			<h1 class="site-title"><a href="<?php echo get_site_url(); ?>" data-ga-category="nav" data-ga-label="Site Title|Zadie Heimlich">Zadie Heimlich</a></h1>
			<p class="zadies-current-age"><?php echo get_zadies_current_age(); ?> old.</p>
		</div>
	</header>

	<nav id="new-menu" class="new-menu">
		<?php
		$args = array(
			'theme_location' => 'main-menu',
			'container' => false,
			'menu_class' => 'holder',
			'menu_id' => false,
		);
		wp_nav_menu( $args );
		?>
	</nav>

	<div class="holder">
