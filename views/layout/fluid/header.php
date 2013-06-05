<?php
/**
 * Render the header
 * Added some CSS for the one page layout
 * ******************************************************
 *
 * @author Paul Brighton <escape@null.net>
 * @link http://www.phpiphany.com/
 * @copyright Copyright &copy; 2012-2013 _MD_ ProductionS
 * @license http://www.phpiphany.com/license/
 * @package full view
 * @since 1.0
 *
 */


function render_header(&$view) {

	if (isset($view->custom_css) and is_array($view->custom_css) and count($view->custom_css) > 0) {
		$custom_css = '';
		foreach ($view->custom_css as $file) {
			$custom_css .= "	<link href=\"$file\" rel=\"stylesheet\">\n";
		}
	} else {
		$custom_css = '';
	}
	$custom_css .= "\n	<link href=\"{$view->assets_dir}css/alertbox.css\" rel=\"stylesheet\">";

	$header = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="$view->charset">
	<meta http-equiv="Content-Type" content="text/html; charset=$view->charset">
	<title>$view->title</title>
	<meta name="keywords" content="$view->keywords">
	<meta name="description" content="$view->description">
	<meta name="author" content="$view->author">
	<meta name="copyright" content="$view->copyright">
	<meta name="robots" content="$view->robots">
	<meta http-equiv="cache-control" content="no-cache">
	<meta http-equiv="pragma" content="no-cache">
	<meta http-equiv="expires" content="-1">
	<link rel="alternate" type="application/atom+xml" title="phpiphany (ATOM 1.0)" href="http://www.phpiphany.com/feed">

	<!-- IE6-8 support of HTML5 elements -->
	<!--[if lt IE 9]>
	<script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>
	<![endif]-->

$custom_css
	<link href="{$view->assets_dir}css/$view->css" rel="stylesheet">
	<link href="{$view->assets_dir}css/bootstrap-responsive.css" rel="stylesheet">

	<!-- JS framework loaded via Google CDN. Only one JS include in the header -->
	$view->js_lib

	<style type="text/css">
		.sidebar-nav {
			padding: 9px 0;
		}
	</style>
	<!-- Le fav and touch icons -->
	<link rel="shortcut icon" href="{$view->assets_dir}icons/favicon.ico">
	<link rel="apple-touch-icon" href="{$view->assets_dir}icons/apple-touch-icon.png">
	<link rel="apple-touch-icon" sizes="72x72" href="{$view->assets_dir}icons/apple-touch-icon-72x72.png">
	<link rel="apple-touch-icon" sizes="114x114" href="{$view->assets_dir}icons/apple-touch-icon-114x114.png">
</head>

<body>

HTML;

	if ($view->navbar_enabled){
		include_once('navbar.php');
		$header .= render_navbar($view);
	}
	
	return $header;
}
