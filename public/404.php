<?php

/**
 * Custom 404 page to show users if page is unavailable
 * ******************************************************
 *
 * @author Paul Brighton <escape@null.net>
 * @link http://www.phpiphany.com/
 * @copyright Copyright &copy; 2012-2013 _MD_ ProductionS
 * @license http://www.phpiphany.com/license/
 * @package main
 * @since 1.0
 *
 */


include_once(dirname(dirname(__FILE__)) . '/init.php');

global $config, $view;
$content =<<<HTML

		<div class="page-header">
			<div class="alert-message warning fullpage">
				<p><strong>ERROR 404</strong><br />
				The page you are trying to reach does not exists</p>
			</div>
		</div>
		<div class="row">
			<div style="margin-left:50px">
				<p><strong>Oh snap! You stumbled upon a 404 error!</strong> The page you are trying to access is temporary unavailable.<br />
				But <em>do not</em> panic! This is not the end of the world. Reasons for receiving this message:<br />
				<ul>
					<li>You may have mistyped the URL
					<li>The page or file you requested no longer exists
					<li>Content issues such as broken links or incorrect redirects
				</ul>
				<br />
				We suggest that you:
				<br />
				<ul>
					<li>Check the spelling of the URL you're trying to reach</li>
					<li>Go to the main page</li>
					<li>Try again later</li>
				</ul>
				<br />
				Alternatively, if the problem persists or you would like to leave a comment, you can reach out to the site administrator at <a href="mailto:$config->site_email">$config->site_email</a>
				<br /><br />
				For all inquiries, please include the following:
				<ul>
					<li>Domain name or URL you used to reach this page (in the address bar above).</li>
					<li>The link you clicked on that led you to this page</li>
					<li>Username you used to access password protected pages</li>
				</ul>
				<br /><br />
				Thank you for using <strong>$config->site_name</strong><br /><br /></p>
				<div class="alert-actions">
					<button class="btn default" onclick="window.location='$config->site_url'">Get me out of here</button>
				</div>
			</div>
		</div>

HTML;

/*
 *
 *
 * Request Not Found

 *
 *
 */
$view->render_page($content);

