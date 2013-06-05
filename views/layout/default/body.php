<?php
/**
 * Render the main body container
 * ******************************************************
 *
 * @author Paul Brighton <escape@null.net>
 * @link http://www.phpiphany.com/
 * @copyright Copyright &copy; 2012-2013 _MD_ ProductionS
 * @license http://www.phpiphany.com/license/
 * @package default view
 * @since 1.0
 *
 */


function render_body(&$view) {

	// Some sample text if no content was specified
	if (!$view->content) $view->content =<<<TXT

	<!-- Main hero unit for a primary marketing message or call to action -->
	<div class="hero-unit">
		<h1>Hello, world!</h1>

		<p>This is a template for a simple marketing or informational website. It includes a large callout called the
			hero unit and three supporting pieces of content. Use it as a starting point to create something more
			unique.</p>

		<p><a class="btn btn-primary btn-large">Learn more &raquo;</a></p>
	</div>

	<!-- Example row of columns -->
	<div class="row">
		<div class="span4">
			<h2>Heading</h2>

			<p>Donec id elit non mi porta gravida at eget metus. Fusce dapibus, tellus ac cursus commodo, tortor mauris
				condimentum nibh, ut fermentum massa justo sit amet risus. Etiam porta sem malesuada magna mollis
				euismod. Donec sed odio dui. </p>

			<p><a class="btn" href="1.html#">View details &raquo;</a></p>
		</div>

		<div class="span4">
			<h2>Heading</h2>

			<p>Donec id elit non mi porta gravida at eget metus. Fusce dapibus, tellus ac cursus commodo, tortor mauris
				condimentum nibh, ut fermentum massa justo sit amet risus. Etiam porta sem malesuada magna mollis
				euismod. Donec sed odio dui. </p>

			<p><a class="btn" href="1.html#">View details &raquo;</a></p>
		</div>
		<div class="span4">
			<h2>Heading</h2>

			<p>Donec sed odio dui. Cras justo odio, dapibus ac facilisis in, egestas eget quam. Vestibulum id ligula
				porta felis euismod semper. Fusce dapibus, tellus ac cursus commodo, tortor mauris condimentum nibh, ut
				fermentum massa justo sit amet risus.</p>

			<p><a class="btn" href="1.html#">View details &raquo;</a></p>
		</div>
	</div>

	<hr>

TXT;

	$menu = !$view->menu_enabled? '' : $view->load('layout/menu/' . $view->menu_style);

	$container = <<<HTML

$menu
<div class="container">
	<div class="content">
		$view->content
	</div>


HTML;

	return $container;

}
